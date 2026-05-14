<?php

namespace App\Services;

use App\Models\Internship;
use App\Models\Profile;
use App\Models\ResumeScore;
use App\Models\ResumeVersion;
use App\Models\User;
use App\Services\Resume\AiRewriteEngine;
use App\Services\Resume\AtsScoreEngine;
use App\Services\Resume\JobDescriptionAnalyzer;
use App\Services\Resume\ResumeParserEngine;
use App\Services\Resume\ResumeQualityDetector;
use App\Services\Resume\WeaknessDetectionEngine;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ResumeOptimizationService — Master Pipeline Orchestrator (Rebuilt).
 *
 * Full Pipeline:
 *  1. ResumeParserEngine     → extract + structure resume text
 *  2. JobDescriptionAnalyzer → extract JD intelligence
 *  3. ResumeQualityDetector  → classify resume tier (elite/strong/average/weak)
 *                              + lock strong sections from AI rewriting
 *  4. WeaknessDetectionEngine→ detect only genuinely weak areas
 *  5. AtsScoreEngine         → realistic weighted ATS score (intrinsic + JD alignment)
 *  6. AiRewriteEngine        → selective enhancement (locked sections preserved)
 *  7. Persist results
 */
class ResumeOptimizationService
{
    public function __construct(
        private readonly ResumeParserEngine      $parser,
        private readonly JobDescriptionAnalyzer  $jdAnalyzer,
        private readonly ResumeQualityDetector   $qualityDetector,
        private readonly WeaknessDetectionEngine $weaknessEngine,
        private readonly AtsScoreEngine          $scoreEngine,
        private readonly AiRewriteEngine         $rewriteEngine,
    ) {}

    /* ------------------------------------------------------------------ */
    /*  Analyse Resume (Score Only)                                         */
    /* ------------------------------------------------------------------ */

    public function analyseResume(User $user, Internship $internship): array
    {
        try {
            Log::info('[Pipeline] START: Resume Analysis', ['user_id' => $user->id, 'internship_id' => $internship->id]);

            $profile = $user->profile;
            if (!$profile || !$profile->resume_path) {
                Log::warning('[Pipeline] FAIL: No resume path found');
                return $this->emptyScore('No resume uploaded. Please upload your resume from your profile page.');
            }

            $absolutePath = $this->resolveResumePath($profile);
            if (!$absolutePath) {
                Log::warning('[Pipeline] FAIL: Resume file not found on disk');
                return $this->emptyScore('Unable to locate your resume file. Please re-upload your resume.');
            }

            // ── Stage 1: Parsing ──────────────────────────────────────────
            $parsedResume = $this->parser->parse($absolutePath);
            Log::info('[Pipeline] Stage 1 COMPLETE: Resume Parsed', ['text_length' => strlen($parsedResume['raw_text'] ?? '')]);

            if (!empty($parsedResume['parse_error']) && empty(trim($parsedResume['raw_text']))) {
                Log::error('[Pipeline] Stage 1 FAIL: Parser error', ['error' => $parsedResume['parse_error']]);
                return $this->emptyScore('Unable to read your resume PDF. Please ensure it is a valid, non-encrypted PDF.');
            }

            // ── Stage 2: JD Intelligence ──────────────────────────────────
            $jdAnalysis = $this->jdAnalyzer->analyze($internship);
            Log::info('[Pipeline] Stage 2 COMPLETE: JD Analyzed', ['role' => $jdAnalysis['role_category'] ?? 'unknown']);

            // ── Stage 3: Quality Detection ───────────────────────────────
            $qualityReport = $this->qualityDetector->detect($parsedResume);
            Log::info('[Pipeline] Stage 3 COMPLETE: Quality Detected', ['tier' => $qualityReport['tier'] ?? 'unknown']);

            // ── Stage 4: Weakness Detection ──────────────────────────────
            $weaknessReport = $this->weaknessEngine->detect($parsedResume, $jdAnalysis);
            Log::info('[Pipeline] Stage 4 COMPLETE: Weaknesses Detected', ['count' => count($weaknessReport['weak_areas'] ?? [])]);

            // ── Stage 5: ATS Scoring ─────────────────────────────────────
            $scoreBreakdown = $this->scoreEngine->ruleBasedScore($parsedResume, $jdAnalysis);
            Log::info('[Pipeline] Stage 5 COMPLETE: ATS Scored', ['score' => $scoreBreakdown['overall_score'] ?? 0]);

            // ── Persist Score ─────────────────────────────────────────────
            $score = ResumeScore::updateOrCreate(
                ['user_id' => $user->id, 'internship_id' => $internship->id],
                [
                    'overall_score'     => $scoreBreakdown['overall_score'],
                    'skill_match_score' => $scoreBreakdown['skill_match'],
                    'keyword_score'     => $scoreBreakdown['keyword_score'],
                    'format_score'      => $scoreBreakdown['format_score'],
                    'matching_skills'   => $scoreBreakdown['matching_skills'],
                    'missing_skills'    => $scoreBreakdown['missing_skills'],
                    'issues'            => $scoreBreakdown['issues'],
                    'strengths'         => $scoreBreakdown['strengths'],
                    'match_tier'        => ResumeScore::scoreTier($scoreBreakdown['overall_score']),
                ]
            );

            Log::info('[Pipeline] SUCCESS: Resume Analysis Finished');

            return [
                'score'              => $scoreBreakdown['overall_score'],
                'skill_match'        => $scoreBreakdown['skill_match'],
                'keyword_score'      => $scoreBreakdown['keyword_score'],
                'format_score'       => $scoreBreakdown['format_score'],
                'exp_score'          => $scoreBreakdown['exp_score'],
                'proj_score'         => $scoreBreakdown['proj_score'],
                'intrinsic_score'    => $scoreBreakdown['intrinsic_score'] ?? 0,
                'overall_score'      => $scoreBreakdown['overall_score'],
                'matching_skills'    => $scoreBreakdown['matching_skills'],
                'missing_skills'    => $scoreBreakdown['missing_skills'],
                'issues'             => $scoreBreakdown['issues'],
                'strengths'          => $scoreBreakdown['strengths'],
                'weak_areas'         => $weaknessReport['weak_areas'],
                'recommendations'    => $weaknessReport['recommendations'],
                'role_category'      => $jdAnalysis['role_category'],
                'tier'               => $score->match_tier,
                'gate_message'       => $score->gateMessage(),
                'badge_class'        => $score->badgeClass(),
                'resume_text'        => $parsedResume['raw_text'],
                'quality_tier'       => $qualityReport['tier'],
                'quality_score'      => $qualityReport['quality_score'],
                'section_scores'     => $qualityReport['section_scores'],
                'locked_sections'    => $qualityReport['locked_sections'],
                'preservation_mode'  => $qualityReport['preservation_mode'],
            ];

        } catch (\Exception $e) {
            Log::error('[Pipeline] FATAL: Analysis crashed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->emptyScore('Internal processing error: ' . $e->getMessage(), 'CRITICAL_SYSTEM_FAILURE');
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Rewrite Resume (AI Optimize)                                        */
    /* ------------------------------------------------------------------ */

    public function rewriteResume(User $user, Internship $internship): array
    {
        try {
            Log::info('[Pipeline] START: Resume Rewrite', ['user_id' => $user->id, 'internship_id' => $internship->id]);

            $profile = $user->profile;
            if (!$profile || !$profile->resume_path) {
                Log::warning('[Pipeline] FAIL: No resume path found');
                return ['success' => false, 'error' => 'No resume found. Please upload a resume first.'];
            }

            $absolutePath = $this->resolveResumePath($profile);
            if (!$absolutePath) {
                Log::warning('[Pipeline] FAIL: Resume file not found on disk');
                return ['success' => false, 'error' => 'Resume file not found. Please re-upload your resume.'];
            }

            // ── Stage 1: Pre-Analysis ────────────────────────────────────
            $parsedResume   = $this->parser->parse($absolutePath);
            $jdAnalysis     = $this->jdAnalyzer->analyze($internship);
            $qualityReport  = $this->qualityDetector->detect($parsedResume);
            $weaknessReport = $this->weaknessEngine->detect($parsedResume, $jdAnalysis);
            $beforeBreakdown= $this->scoreEngine->ruleBasedScore($parsedResume, $jdAnalysis);
            $beforeScore    = $beforeBreakdown['overall_score'];

            Log::info('[Pipeline] Stage 1 COMPLETE: Pre-Analysis Done', [
                'quality_tier' => $qualityReport['tier'],
                'before_score' => $beforeScore
            ]);

            // ── Stage 2: AI Enhancement ──────────────────────────────────
            Log::info('[Pipeline] Stage 2 START: AI Enhancement Request');
            $rewriteResult = $this->rewriteEngine->rewrite(
                $parsedResume,
                $jdAnalysis,
                $weaknessReport,
                $qualityReport
            );

            if (!$rewriteResult['success']) {
                Log::warning('[Pipeline] Stage 2 WARNING: AI Rewrite failed, falling back', ['error' => $rewriteResult['error'] ?? 'unknown']);
                $rewrittenText = $this->rewriteEngine->preservationAwareRuleRewrite(
                    $parsedResume,
                    $jdAnalysis,
                    $weaknessReport,
                    $qualityReport['locked_sections'] ?? [],
                    $qualityReport['preservation_mode'] ?? false
                );
            } else {
                Log::info('[Pipeline] Stage 2 COMPLETE: AI Rewrite Success');
                $rewrittenText = $rewriteResult['rewritten_text'];
            }

            // ── Stage 3: Validation & Sanitization ───────────────────────
            $validationIssues = $this->rewriteEngine->validateOutput($rewrittenText);
            if (!empty($validationIssues)) {
                Log::warning('[Pipeline] Stage 3 WARNING: Validation failed, applying safety net', ['issues' => $validationIssues]);
                $rewrittenText = $this->rewriteEngine->preservationAwareRuleRewrite(
                    $parsedResume,
                    $jdAnalysis,
                    $weaknessReport,
                    $qualityReport['locked_sections'] ?? [],
                    $qualityReport['preservation_mode'] ?? false
                );
            }

            $rewrittenText = $this->rewriteEngine->sanitizeForStorage($rewrittenText);
            Log::info('[Pipeline] Stage 3 COMPLETE: Sanitization Done');

            // ── Stage 4: AI Semantic Scoring ─────────────────────────────
            Log::info('[Pipeline] Stage 4 START: Semantic Scoring');
            // rewrittenText may be plain text (rule-based) or JSON (AI rewrite).
            // Build a minimal parsed array for scoring in either case.
            $afterParsed = json_decode($rewrittenText, true);
            if (!is_array($afterParsed)) {
                // Plain-text fallback: wrap in a minimal structure so evaluateImprovement works
                $afterParsed = array_merge($parsedResume, ['raw_text' => $rewrittenText]);
            }
            
            $aiScoreData    = $this->scoreEngine->evaluateImprovement($parsedResume, $afterParsed, $jdAnalysis);
            $beforeScore    = $aiScoreData['before_score'];
            $afterScore     = $aiScoreData['after_score'];
            $afterBreakdown = $aiScoreData['after_breakdown'];
            $improvements   = $aiScoreData['improvements'];

            // Apply ATS Consistency Bounds
            $afterScore = max($beforeScore, $afterScore);
            $tier = $qualityReport['tier'];
            if ($tier === 'weak' && $afterScore > 85) { $afterScore = 80; }
            elseif ($tier === 'elite' && $afterScore < 85) { $afterScore = max(88, $beforeScore); }
            elseif ($tier === 'strong' && $afterScore < 75) { $afterScore = max(80, $beforeScore); }

            Log::info('[Pipeline] Stage 4 COMPLETE: Semantic Scoring Success', ['after_score' => $afterScore]);

            // ── Stage 5: Persistence ─────────────────────────────────────
            $nextVersion = (ResumeVersion::where('user_id', $user->id)
                ->where('internship_id', $internship->id)
                ->max('version_number') ?? 0) + 1;

            $this->saveOriginalSnapshot($user->id, $internship->id, $parsedResume['raw_text'], $beforeScore);

            $newVersion = ResumeVersion::create([
                'user_id'           => $user->id,
                'internship_id'     => $internship->id,
                'version_number'    => $nextVersion,
                'label'             => $this->buildVersionLabel($qualityReport['tier'], $rewriteResult),
                'content'           => $rewrittenText,
                'score_at_creation' => $afterScore,
                'type'              => 'ai_rewrite',
            ]);

            ResumeScore::updateOrCreate(
                ['user_id' => $user->id, 'internship_id' => $internship->id],
                [
                    'overall_score'     => $afterScore,
                    'skill_match_score' => $afterBreakdown['skill_match'],
                    'keyword_score'     => $afterBreakdown['keyword_score'],
                    'format_score'      => $afterBreakdown['format_score'],
                    'matching_skills'   => $afterBreakdown['matching_skills'] ?? [],
                    'missing_skills'    => $afterBreakdown['missing_skills'] ?? [],
                    'issues'            => $afterBreakdown['issues'] ?? [],
                    'strengths'         => $afterBreakdown['strengths'] ?? [],
                    'match_tier'        => ResumeScore::scoreTier($afterScore),
                    'resume_version_id' => $newVersion->id,
                ]
            );

            Log::info('[Pipeline] SUCCESS: Resume Rewrite Finished');

            return [
                'success'           => true,
                'rewritten_text'    => $rewrittenText,
                'before_score'      => $beforeScore,
                'after_score'       => $afterScore,
                'improvements'      => $improvements,
                'version_id'        => $newVersion->id,
                'ai_disabled'       => !($rewriteResult['ai_used'] ?? false),
                'role_category'     => $jdAnalysis['role_category'],
                'quality_tier'      => $qualityReport['tier'],
                'preservation_mode' => $qualityReport['preservation_mode'],
                'rewrite_mode'      => $rewriteResult['mode'] ?? 'rule_based',
                'locked_sections'   => $qualityReport['locked_sections'],
            ];

        } catch (\Exception $e) {
            Log::error('[Pipeline] FATAL: Rewrite crashed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success'      => false,
                'stage_failed' => 'REWRITE_ORCHESTRATION_FAILURE',
                'error'        => 'An internal system error occurred during optimization: ' . $e->getMessage()
            ];
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    public function getExistingScore(User $user, Internship $internship): ?ResumeScore
    {
        return ResumeScore::where('user_id', $user->id)
            ->where('internship_id', $internship->id)
            ->latest()
            ->first();
    }

    private function resolveResumePath(Profile $profile): ?string
    {
        $disk           = config('filesystems.default');
        $normalizedPath = ltrim($profile->resume_path, '/');

        if ($disk === 's3') {
            if (!Storage::disk('s3')->exists($normalizedPath)) return null;
            $tmpFile = sys_get_temp_dir() . '/resume_' . $profile->id . '_' . time() . '.pdf';
            file_put_contents($tmpFile, Storage::disk('s3')->get($normalizedPath));
            return $tmpFile;
        }

        $path1 = storage_path('app/public/' . $normalizedPath);
        if (file_exists($path1)) return $path1;

        if (Storage::disk('public')->exists($normalizedPath)) {
            return Storage::disk('public')->path($normalizedPath);
        }

        return null;
    }

    private function buildVersionLabel(string $tier, array $rewriteResult): string
    {
        $mode   = $rewriteResult['mode'] ?? 'rule_based';
        $engine = $rewriteResult['engine'] ?? 'rule-based';

        if ($mode === 'keyword_only') return 'Elite Preservation — Keywords Enhanced';
        if ($mode === 'selective')    return 'Selective AI Enhancement — v' . now()->format('H:i');

        return ucfirst($tier) . ' Resume — AI Optimized';
    }

    private function buildImprovementsList(array $before, array $after, array $weakness, array $quality, array $rewriteResult): array
    {
        $list = [];
        $tier = $quality['tier'] ?? 'average';
        $mode = $rewriteResult['mode'] ?? 'rule_based';

        // Quality tier context
        $tierLabels = ['elite' => '🏆 Elite', 'strong' => '⭐ Strong', 'average' => '📊 Average', 'weak' => '⚠️ Weak'];
        $list[] = "Resume Quality: {$tierLabels[$tier]} — " . ucfirst($mode) . " mode applied";

        // Score improvements
        $skillDiff = $after['skill_match'] - $before['skill_match'];
        if ($skillDiff > 0) $list[] = "+ Improved skill match by {$skillDiff}%";

        $kwDiff = $after['keyword_score'] - $before['keyword_score'];
        if ($kwDiff > 0) $list[] = "+ Increased ATS keyword coverage by {$kwDiff}%";

        // Locked sections
        $locked = $quality['locked_sections'] ?? [];
        if (!empty($locked)) {
            $list[] = "🔒 Preserved " . count($locked) . " high-quality section(s): " . implode(', ', $locked);
        }

        // Rewrites
        $weakCount = count($weakness['weak_bullets'] ?? []);
        if ($weakCount > 0 && $mode !== 'keyword_only') {
            $list[] = "+ Rewrote {$weakCount} weak bullet(s) with action verbs and technical depth";
        }

        if ($weakness['summary_weak'] ?? false) {
            $list[] = "+ Rewrote Professional Summary to target the specific role";
        }

        if (!empty($weakness['missing_skills']) && $mode !== 'keyword_only') {
            $added = implode(', ', array_slice($weakness['missing_skills'], 0, 3));
            $list[] = "+ Added missing skills to skills section: {$added}";
        }

        if ($mode === 'keyword_only') {
            $list[] = "🔒 Elite resume preserved — only injected missing ATS keywords into skills";
        }

        $engine = $rewriteResult['engine'] ?? null;
        if ($engine === 'claude')  $list[] = "+ Optimized by Claude AI with surgical precision";
        if ($engine === 'openai')  $list[] = "+ Optimized by GPT-4 for ATS compatibility";

        return $list;
    }

    private function saveOriginalSnapshot(int $userId, int $internshipId, string $content, int $score): void
    {
        $exists = ResumeVersion::where('user_id', $userId)
            ->where('internship_id', $internshipId)
            ->where('type', 'original')
            ->exists();

        if (!$exists) {
            ResumeVersion::create([
                'user_id'           => $userId,
                'internship_id'     => $internshipId,
                'version_number'    => 1,
                'label'             => 'Original',
                'content'           => $content,
                'score_at_creation' => $score,
                'type'              => 'original',
            ]);
        }
    }

    public function checkPipelineHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'dependencies' => [],
        ];

        // 1. Check n8n Webhook
        $n8nUrl = config('services.n8n.webhook_url');
        $n8nKey = config('services.resume_intelligence.api_key');
        
        $health['dependencies']['n8n'] = [
            'status' => !empty($n8nUrl) ? 'configured' : 'missing',
            'endpoint' => $n8nUrl ? parse_url($n8nUrl, PHP_URL_HOST) : null,
            'key_present' => !empty($n8nKey),
        ];

        // 2. Check LaTeX Rendering
        $latexUrl = config('services.latex_lite.api_url');
        $latexKey = config('services.latex_lite.api_key');
        
        $health['dependencies']['latex_render'] = [
            'status' => !empty($latexUrl) ? 'configured' : 'missing',
            'endpoint' => $latexUrl ? parse_url($latexUrl, PHP_URL_HOST) : null,
            'key_present' => !empty($latexKey),
        ];

        // 3. Overall status
        foreach ($health['dependencies'] as $dep) {
            if ($dep['status'] !== 'configured' || !$dep['key_present']) {
                $health['status'] = 'degraded';
                break;
            }
        }

        return $health;
    }

    private function emptyScore(string $reason = '', string $stage = 'UNKNOWN'): array
    {
        return [
            'success'         => false,
            'stage_failed'    => $stage,
            'score' => 0, 'skill_match' => 0, 'keyword_score' => 0,
            'format_score' => 0, 'exp_score' => 0, 'proj_score' => 0,
            'intrinsic_score' => 0, 'overall_score' => 0,
            'matching_skills' => [], 'missing_skills' => [],
            'issues'          => [$reason ?: 'Unable to analyse resume'],
            'strengths'       => [],
            'weak_areas'      => [], 'recommendations' => [], 'role_category' => 'general',
            'tier'            => 'low',
            'gate_message'    => '🔴 Analysis Failed — ' . ($stage !== 'UNKNOWN' ? "[{$stage}] " : '') . $reason,
            'badge_class'     => 'bg-red-100 text-red-800',
            'resume_text'     => '',
            'quality_tier'    => 'unknown', 'quality_score' => 0,
            'section_scores'  => [], 'locked_sections' => [], 'preservation_mode' => false,
        ];
    }
}
