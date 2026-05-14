<?php

namespace App\Services;

use App\Models\AtsAnalyticsEvent;
use App\Models\Application;
use App\Models\CandidateIntelligence;
use App\Models\Internship;
use App\Models\Profile;
use App\Models\ResumeScore;
use App\Models\User;
use App\Services\Resume\AtsScoreEngine;
use App\Services\Resume\CandidateRankingEngine;
use App\Services\Resume\JobDescriptionAnalyzer;
use App\Services\Resume\ResumeParserEngine;
use App\Services\Resume\WeaknessDetectionEngine;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * RecruitmentIntelligenceService — Phase 2 Master Service
 *
 * Orchestrates:
 *   1. Candidate ranking for a given internship
 *   2. Recruiter trust layer computation
 *   3. Candidate insight dashboard data
 *   4. Job match intelligence
 *   5. Analytics event tracking
 *   6. Smart recommendations
 */
class RecruitmentIntelligenceService
{
    public function __construct(
        private readonly ResumeParserEngine     $parser,
        private readonly JobDescriptionAnalyzer $jdAnalyzer,
        private readonly WeaknessDetectionEngine $weaknessEngine,
        private readonly AtsScoreEngine         $scoreEngine,
        private readonly CandidateRankingEngine  $rankingEngine,
    ) {}

    /* ================================================================== */
    /*  RECRUITER-SIDE: Candidate Intelligence Dashboard                    */
    /* ================================================================== */

    /**
     * Get ranked candidates for a recruiter's internship.
     * Returns full intelligence data for the recruiter trust layer.
     */
    public function getRankedCandidates(int $internshipId, int $recruiterId): array
    {
        $internship = Internship::where('id', $internshipId)
            ->where('recruiter_id', $recruiterId)
            ->firstOrFail();

        $applications = Application::where('internship_id', $internshipId)
            ->with(['user.profile'])
            ->get();

        if ($applications->isEmpty()) {
            return ['internship' => $internship, 'candidates' => [], 'stats' => $this->emptyStats()];
        }

        $jdAnalysis = $this->jdAnalyzer->analyze($internship);

        // Build candidate intelligence for each applicant
        $candidateProfiles = [];
        foreach ($applications as $app) {
            $profile = $app->user?->profile;
            if (!$profile) continue;

            // Check if existing intelligence is fresh (< 24 hours)
            $existing = CandidateIntelligence::where('user_id', $app->user_id)
                ->where('internship_id', $internshipId)
                ->where('updated_at', '>=', now()->subHours(24))
                ->first();

            if ($existing) {
                // Use cached data
                $candidateProfiles[] = [
                    'user_id'          => $app->user_id,
                    'application'      => $app,
                    'intelligence'     => $existing,
                    'name'             => $app->user->name,
                    'email'            => $app->user->email,
                    'profile'          => $profile,
                    'from_cache'       => true,
                    'overall_score'    => $existing->overall_ats_score,
                ];
                continue;
            }

            // Parse resume and compute fresh intelligence
            $resumePath = $this->resolveResumePath($profile);
            if (!$resumePath) {
                $candidateProfiles[] = $this->noResumePlaceholder($app);
                continue;
            }

            $parsedResume = $this->parser->parse($resumePath);
            $scoreData    = $this->rankingEngine->evaluateCandidate([
                'user_id'     => $app->user_id,
                'parsed'      => $parsedResume,
                'resume_text' => $parsedResume['raw_text'],
            ], $jdAnalysis);

            $weaknessReport = $this->weaknessEngine->detect($parsedResume, $jdAnalysis);

            // Persist intelligence
            $intelligence = CandidateIntelligence::updateOrCreate(
                ['user_id' => $app->user_id, 'internship_id' => $internshipId],
                [
                    'application_id'          => $app->id,
                    'overall_ats_score'        => $scoreData['overall_score'],
                    'skill_match_score'        => $scoreData['skill_match_score'],
                    'keyword_score'            => $scoreData['keyword_score'],
                    'experience_score'         => $scoreData['experience_score'],
                    'project_score'            => $scoreData['project_score'],
                    'technical_depth_score'    => $scoreData['technical_depth_score'],
                    'format_score'             => $scoreData['format_score'],
                    'role_category'            => $scoreData['role_category'],
                    'backend_match'            => $scoreData['backend_match'],
                    'frontend_match'           => $scoreData['frontend_match'],
                    'project_quality'          => $scoreData['project_quality'],
                    'technical_depth'          => $scoreData['technical_depth'],
                    'recruiter_readability'    => $scoreData['recruiter_readability'],
                    'experience_level'         => $scoreData['experience_level'],
                    'matching_skills'          => $scoreData['matching_skills'],
                    'missing_skills'           => $scoreData['missing_skills'],
                    'critical_missing_skills'  => $scoreData['critical_missing_skills'],
                    'optional_missing_skills'  => $scoreData['optional_missing_skills'],
                    'weak_areas'               => $weaknessReport['weak_areas'],
                    'recommendations'          => $weaknessReport['recommendations'],
                    'summary_weak'             => $weaknessReport['summary_weak'],
                    'weak_bullet_count'        => count($weaknessReport['weak_bullets']),
                ]
            );

            $candidateProfiles[] = [
                'user_id'       => $app->user_id,
                'application'   => $app,
                'intelligence'  => $intelligence,
                'name'          => $app->user->name,
                'email'         => $app->user->email,
                'profile'       => $profile,
                'from_cache'    => false,
                'overall_score' => $scoreData['overall_score'],
            ];
        }

        // Sort by score
        usort($candidateProfiles, fn($a, $b) => $b['overall_score'] <=> $a['overall_score']);

        // Assign rank positions and update DB
        $total = count($candidateProfiles);
        foreach ($candidateProfiles as $idx => &$cp) {
            $rank  = $idx + 1;
            $tier  = $this->computeRankTier($rank, $total);
            $cp['rank']      = $rank;
            $cp['rank_tier'] = $tier;

            if (!($cp['from_cache'] ?? false)) {
                CandidateIntelligence::where('user_id', $cp['user_id'])
                    ->where('internship_id', $internshipId)
                    ->update([
                        'rank_position'    => $rank,
                        'total_candidates' => $total,
                        'rank_tier'        => $tier,
                    ]);
            }
        }
        unset($cp);

        // Log analytics event
        AtsAnalyticsEvent::log(
            AtsAnalyticsEvent::RANK_COMPUTED,
            $recruiterId,
            $internshipId,
            ['total_candidates' => $total],
            'recruiter'
        );

        $stats = $this->computeInternshipStats($candidateProfiles, $jdAnalysis);

        return [
            'internship'  => $internship,
            'candidates'  => $candidateProfiles,
            'stats'       => $stats,
            'jd_analysis' => $jdAnalysis,
        ];
    }

    /* ================================================================== */
    /*  CANDIDATE-SIDE: Profile Intelligence Dashboard                      */
    /* ================================================================== */

    /**
     * Build the candidate's intelligence profile across all their applications.
     */
    public function getCandidateInsightDashboard(User $user): array
    {
        $profile = $user->profile;

        if (!$profile || !$profile->resume_path) {
            return $this->emptyInsightDashboard('No resume uploaded. Upload your resume to get AI-powered insights.');
        }

        // Get all intelligence records for this user
        $intelligenceRecords = CandidateIntelligence::where('user_id', $user->id)
            ->with('internship')
            ->orderByDesc('overall_ats_score')
            ->get();

        // Get their applications
        $applications = Application::where('user_id', $user->id)
            ->with('internship')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Compute profile strength dimensions
        $profileStrength = $this->computeProfileStrength($profile, $intelligenceRecords);

        // ATS score trend
        $scoreTrend = $this->computeScoreTrend($user->id);

        // Best matching internship
        $bestMatch = $intelligenceRecords->first();

        // Aggregate missing skills across all applications
        $allMissingSkills = $this->aggregateMissingSkills($intelligenceRecords);

        // Aggregate weak areas
        $allWeakAreas = $this->aggregateWeakAreas($intelligenceRecords);

        // Smart recommendations
        $smartRecs = $this->generateSmartRecommendations(
            $profile, $intelligenceRecords, $profileStrength
        );

        // Recruiter visibility score (based on profile completeness + ATS performance)
        $visibilityScore = $this->computeRecruiterVisibilityScore($profile, $intelligenceRecords);

        return [
            'user'              => $user,
            'profile'           => $profile,
            'intelligence_records' => $intelligenceRecords,
            'applications'      => $applications,
            'profile_strength'  => $profileStrength,
            'score_trend'       => $scoreTrend,
            'best_match'        => $bestMatch,
            'all_missing_skills'=> $allMissingSkills,
            'all_weak_areas'    => $allWeakAreas,
            'smart_recommendations' => $smartRecs,
            'visibility_score'  => $visibilityScore,
            'has_resume'        => true,
        ];
    }

    /* ================================================================== */
    /*  Job Match Intelligence                                              */
    /* ================================================================== */

    /**
     * Get detailed job match intelligence for a specific internship.
     */
    public function getJobMatchIntelligence(User $user, Internship $internship): array
    {
        $intelligence = CandidateIntelligence::where('user_id', $user->id)
            ->where('internship_id', $internship->id)
            ->first();

        $resumeScore = ResumeScore::where('user_id', $user->id)
            ->where('internship_id', $internship->id)
            ->first();

        $overallScore = $intelligence?->overall_ats_score
                     ?? $resumeScore?->overall_score
                     ?? 0;

        $roleLabel = ucfirst($intelligence?->role_category ?? 'General');

        // Build compatibility breakdown
        $compatibility = [
            'skill_match'      => ['score' => $intelligence?->skill_match_score ?? 0,     'label' => CandidateIntelligence::matchLabel($intelligence?->skill_match_score ?? 0)],
            'technical_depth'  => ['score' => $intelligence?->technical_depth_score ?? 0, 'label' => $intelligence?->technical_depth ?? 'Unknown'],
            'project_relevance'=> ['score' => $intelligence?->project_score ?? 0,         'label' => $intelligence?->project_quality ?? 'Unknown'],
            'experience_fit'   => ['score' => $intelligence?->experience_score ?? 0,      'label' => CandidateIntelligence::matchLabel($intelligence?->experience_score ?? 0)],
        ];

        // Backend compatibility specific for role
        $roleCompatibility = $roleLabel . ' Compatibility';
        $roleScore = match($intelligence?->role_category ?? '') {
            'backend', 'fullstack' => $intelligence?->skill_match_score ?? 0,
            'frontend'             => $intelligence?->skill_match_score ?? 0,
            default                => $overallScore,
        };

        $recommendation = $this->buildApplicationRecommendation($overallScore, $intelligence);

        return [
            'internship'         => $internship,
            'overall_score'      => $overallScore,
            'role_label'         => $roleLabel,
            'compatibility'      => $compatibility,
            'role_compatibility' => ['label' => $roleCompatibility, 'score' => $roleScore],
            'missing_skills'     => $intelligence?->missing_skills ?? $resumeScore?->missing_skills ?? [],
            'critical_missing'   => $intelligence?->critical_missing_skills ?? [],
            'optional_missing'   => $intelligence?->optional_missing_skills ?? [],
            'weak_areas'         => $intelligence?->weak_areas ?? [],
            'recommendation'     => $recommendation,
            'tier'               => ResumeScore::scoreTier($overallScore),
        ];
    }

    /* ================================================================== */
    /*  Analytics Tracking                                                  */
    /* ================================================================== */

    public function trackRecruiterView(int $applicationId, int $recruiterId): void
    {
        $app = Application::with('user.profile')->find($applicationId);
        if (!$app) return;

        CandidateIntelligence::where('user_id', $app->user_id)
            ->where('internship_id', $app->internship_id)
            ->update([
                'recruiter_viewed'    => true,
                'recruiter_viewed_at' => now(),
            ]);

        AtsAnalyticsEvent::log(
            AtsAnalyticsEvent::RECRUITER_VIEWED,
            $app->user_id,
            $app->internship_id,
            ['application_id' => $applicationId, 'recruiter_id' => $recruiterId],
            'recruiter'
        );
    }

    public function trackShortlisted(int $applicationId): void
    {
        $app = Application::find($applicationId);
        if (!$app) return;

        CandidateIntelligence::where('user_id', $app->user_id)
            ->where('internship_id', $app->internship_id)
            ->update(['shortlisted' => true, 'shortlisted_at' => now()]);

        AtsAnalyticsEvent::log(
            AtsAnalyticsEvent::SHORTLISTED,
            $app->user_id,
            $app->internship_id,
            ['application_id' => $applicationId],
            'recruiter'
        );
    }

    /* ================================================================== */
    /*  Private Helpers                                                     */
    /* ================================================================== */

    private function computeProfileStrength(Profile $profile, $intelligenceRecords): array
    {
        $avgScore      = $intelligenceRecords->avg('overall_ats_score') ?? 0;
        $avgBackend    = $this->labelToScore($intelligenceRecords->first()?->backend_match ?? 'Unknown');
        $avgFrontend   = $this->labelToScore($intelligenceRecords->first()?->frontend_match ?? 'Unknown');
        $avgTechDepth  = $intelligenceRecords->avg('technical_depth_score') ?? 0;
        $avgProjQuality= $this->labelToScore($intelligenceRecords->first()?->project_quality ?? 'Unknown');

        $skillCount = count($profile->skills ?? []);

        return [
            'overall'        => ['score' => (int) round($avgScore),     'label' => $this->strengthLabel($avgScore)],
            'backend'        => ['score' => $avgBackend,                 'label' => $intelligenceRecords->first()?->backend_match ?? 'Unknown'],
            'frontend'       => ['score' => $avgFrontend,                'label' => $intelligenceRecords->first()?->frontend_match ?? 'Unknown'],
            'technical_depth'=> ['score' => (int) round($avgTechDepth), 'label' => CandidateIntelligence::matchLabel((int) round($avgTechDepth))],
            'project_quality'=> ['score' => $avgProjQuality,             'label' => $intelligenceRecords->first()?->project_quality ?? 'Unknown'],
            'profile_completeness' => $this->computeProfileCompleteness($profile),
            'skill_count'    => $skillCount,
        ];
    }

    private function computeProfileCompleteness(Profile $profile): int
    {
        $score = 0;
        if (!empty($profile->name))                $score += 15;
        if (!empty($profile->resume_path))         $score += 25;
        if (!empty($profile->skills))              $score += 20;
        if (!empty($profile->academic_background)) $score += 15;
        if (!empty($profile->career_interests))    $score += 10;
        if (!empty($profile->location))            $score += 10;
        if (!empty($profile->profile_photo))       $score += 5;
        return min(100, $score);
    }

    private function computeScoreTrend(int $userId): array
    {
        return CandidateIntelligence::where('user_id', $userId)
            ->with('internship:id,title')
            ->orderBy('updated_at')
            ->take(5)
            ->get()
            ->map(fn($r) => [
                'label' => $r->internship?->title ?? 'Unknown',
                'score' => $r->overall_ats_score,
                'date'  => $r->updated_at?->format('M d'),
            ])
            ->toArray();
    }

    private function aggregateMissingSkills($records): array
    {
        $freq = [];
        foreach ($records as $r) {
            foreach ($r->missing_skills ?? [] as $skill) {
                $freq[strtolower($skill)] = ($freq[strtolower($skill)] ?? 0) + 1;
            }
        }
        arsort($freq);
        return array_slice(array_keys($freq), 0, 10);
    }

    private function aggregateWeakAreas($records): array
    {
        $freq = [];
        foreach ($records as $r) {
            foreach ($r->weak_areas ?? [] as $area) {
                $freq[$area] = ($freq[$area] ?? 0) + 1;
            }
        }
        arsort($freq);
        return array_slice(array_keys($freq), 0, 5);
    }

    private function generateSmartRecommendations(Profile $profile, $intelligenceRecords, array $profileStrength): array
    {
        $recs = [];

        // Profile completeness
        $completeness = $profileStrength['profile_completeness'];
        if ($completeness < 80) {
            $recs[] = ['priority' => 'high', 'icon' => '👤', 'text' => 'Complete your profile — recruiters view complete profiles 3x more often'];
        }

        // Skills gap
        $missingSkills = $this->aggregateMissingSkills($intelligenceRecords);
        if (!empty($missingSkills)) {
            $top3 = implode(', ', array_slice($missingSkills, 0, 3));
            $recs[] = ['priority' => 'high', 'icon' => '🎯', 'text' => "Add these frequently-required skills to your resume: {$top3}"];
        }

        // Technical depth
        $avgTechDepth = $intelligenceRecords->avg('technical_depth_score') ?? 0;
        if ($avgTechDepth < 45) {
            $recs[] = ['priority' => 'medium', 'icon' => '⚙️', 'text' => 'Add technical architecture terminology — system design, API patterns, database optimization'];
        }

        // No projects
        if ($profileStrength['project_quality']['score'] < 40) {
            $recs[] = ['priority' => 'medium', 'icon' => '🚀', 'text' => 'Build a notable project with measurable impact — "served 500+ users" or "reduced latency by 40%"'];
        }

        // Low ATS score
        $avgScore = $intelligenceRecords->avg('overall_ats_score') ?? 0;
        if ($avgScore > 0 && $avgScore < 55) {
            $recs[] = ['priority' => 'medium', 'icon' => '📊', 'text' => 'Use the AI Resume Optimizer before applying — improve your ATS match score by up to 25%'];
        }

        // Weak bullets
        $avgWeakBullets = $intelligenceRecords->avg('weak_bullet_count') ?? 0;
        if ($avgWeakBullets > 2) {
            $recs[] = ['priority' => 'low', 'icon' => '✍️', 'text' => 'Rewrite experience bullets with: Action Verb + Technology + Measurable Impact formula'];
        }

        // Summary
        $summaryWeak = $intelligenceRecords->where('summary_weak', true)->count();
        if ($summaryWeak > 0) {
            $recs[] = ['priority' => 'low', 'icon' => '📝', 'text' => 'Improve your Professional Summary — mention your target role and 2-3 key technologies'];
        }

        return $recs;
    }

    private function computeRecruiterVisibilityScore(Profile $profile, $records): array
    {
        $completeness = $this->computeProfileCompleteness($profile);
        $avgScore     = $records->avg('overall_ats_score') ?? 0;
        $hasResume    = !empty($profile->resume_path);
        $hasSkills    = !empty($profile->skills);

        $visibility = (int) round(
            ($completeness * 0.3) +
            ($avgScore * 0.5) +
            ($hasResume ? 15 : 0) +
            ($hasSkills ? 5 : 0)
        );

        return [
            'score' => min(100, $visibility),
            'label' => $this->strengthLabel($visibility),
            'color' => CandidateIntelligence::scoreColor($visibility),
        ];
    }

    private function computeInternshipStats(array $candidates, array $jdAnalysis): array
    {
        if (empty($candidates)) return $this->emptyStats();

        $scores    = array_column($candidates, 'overall_score');
        $avgScore  = round(array_sum($scores) / count($scores));
        $maxScore  = max($scores);
        $highMatch = count(array_filter($scores, fn($s) => $s >= 70));
        $medMatch  = count(array_filter($scores, fn($s) => $s >= 50 && $s < 70));
        $lowMatch  = count(array_filter($scores, fn($s) => $s < 50));

        return [
            'total'          => count($candidates),
            'avg_score'      => $avgScore,
            'top_score'      => $maxScore,
            'high_match'     => $highMatch,
            'medium_match'   => $medMatch,
            'low_match'      => $lowMatch,
            'role_category'  => ucfirst($jdAnalysis['role_category'] ?? 'General'),
            'required_skills'=> array_slice($jdAnalysis['required_skills'] ?? [], 0, 8),
        ];
    }

    private function buildApplicationRecommendation(int $score, ?CandidateIntelligence $intel): array
    {
        if ($score >= 75) {
            return ['action' => 'Apply Now', 'color' => '#6fcf97', 'icon' => '✅', 'text' => 'Strong match — your profile aligns well with this role'];
        }
        if ($score >= 55) {
            $criticalMissing = $intel?->critical_missing_skills ?? [];
            $tip = empty($criticalMissing) ? 'Optimize your resume to improve keyword coverage' : 'Add: ' . implode(', ', array_slice($criticalMissing, 0, 3));
            return ['action' => 'Apply After Optimization', 'color' => '#f2c94c', 'icon' => '⚡', 'text' => $tip];
        }
        $criticalMissing = $intel?->critical_missing_skills ?? [];
        $tip = empty($criticalMissing) ? 'Use the AI Resume Optimizer before applying' : 'Critical gaps: ' . implode(', ', array_slice($criticalMissing, 0, 3));
        return ['action' => 'Improve Before Applying', 'color' => '#eb5757', 'icon' => '⚠️', 'text' => $tip];
    }

    private function computeRankTier(int $position, int $total): string
    {
        if ($total === 0) return 'unranked';
        $pct = ($position / $total) * 100;
        if ($pct <= 10) return 'top10';
        if ($pct <= 25) return 'top25';
        if ($pct <= 60) return 'average';
        return 'below';
    }

    private function resolveResumePath(Profile $profile): ?string
    {
        if (!$profile->resume_path) return null;
        $normalized = ltrim($profile->resume_path, '/');

        if (config('filesystems.default') === 's3') {
            if (!Storage::disk('s3')->exists($normalized)) return null;
            $tmp = sys_get_temp_dir() . '/resume_' . $profile->id . '_' . time() . '.pdf';
            file_put_contents($tmp, Storage::disk('s3')->get($normalized));
            return $tmp;
        }

        $path = storage_path('app/public/' . $normalized);
        if (file_exists($path)) return $path;

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->path($normalized);
        }
        return null;
    }

    private function noResumePlaceholder(Application $app): array
    {
        return [
            'user_id'       => $app->user_id,
            'application'   => $app,
            'intelligence'  => null,
            'name'          => $app->user->name,
            'email'         => $app->user->email,
            'profile'       => $app->user->profile,
            'from_cache'    => false,
            'overall_score' => 0,
            'no_resume'     => true,
        ];
    }

    private function labelToScore(string $label): int
    {
        return match($label) {
            'Strong'   => 80,
            'Medium'   => 55,
            'Weak'     => 30,
            'None'     => 10,
            default    => 0,
        };
    }

    private function strengthLabel(float $score): string
    {
        if ($score >= 75) return 'Strong';
        if ($score >= 55) return 'Medium';
        if ($score >= 35) return 'Developing';
        return 'Weak';
    }

    private function emptyStats(): array
    {
        return ['total' => 0, 'avg_score' => 0, 'top_score' => 0, 'high_match' => 0, 'medium_match' => 0, 'low_match' => 0, 'role_category' => 'General', 'required_skills' => []];
    }

    private function emptyInsightDashboard(string $reason = ''): array
    {
        return [
            'has_resume' => false,
            'message'    => $reason,
            'profile_strength'       => [],
            'score_trend'            => [],
            'all_missing_skills'     => [],
            'all_weak_areas'         => [],
            'smart_recommendations'  => [],
            'visibility_score'       => ['score' => 0, 'label' => 'No Resume', 'color' => '#eb5757'],
            'intelligence_records'   => collect([]),
            'applications'           => collect([]),
        ];
    }
}
