<?php

namespace App\Services\Resume;

/**
 * CandidateRankingEngine — Step 12 of the Recruitment Intelligence Pipeline.
 *
 * Ranks all applicants for a given internship using the Phase 2 scoring formula:
 *
 *   Skill Match       → 35%
 *   Keyword Match     → 20%
 *   Experience Match  → 15%
 *   Project Relevance → 15%
 *   Technical Depth   → 10%
 *   Formatting        →  5%
 *
 * Generates recruiter trust indicators:
 *   - Role compatibility (Backend/Frontend/FullStack/etc.)
 *   - Project quality (Excellent/Strong/Medium/Weak)
 *   - Technical depth (High/Medium/Low)
 *   - Recruiter readability
 *   - Missing skills classified as Critical vs Optional
 */
class CandidateRankingEngine
{
    public function __construct(
        private ?SemanticSkillMatcher $skillMatcher = null,
    ) {}

    private function skillMatcher(): SemanticSkillMatcher
    {
        return $this->skillMatcher ??= new SemanticSkillMatcher();
    }

    // ── Phase 2 Scoring Weights ──────────────────────────────────────────
    private const WEIGHTS = [
        'skill_match'      => 0.35,
        'keyword_match'    => 0.20,
        'experience_match' => 0.15,
        'project_relevance'=> 0.15,
        'technical_depth'  => 0.10,
        'format_quality'   => 0.05,
    ];

    // ── Critical Skills (role-specific) ──────────────────────────────────
    private const CRITICAL_SKILLS_BY_ROLE = [
        'backend'   => ['php','python','java','node','api','rest','database','sql','laravel','django','express','spring'],
        'frontend'  => ['javascript','react','vue','angular','html','css','typescript','responsive'],
        'fullstack' => ['javascript','react','node','api','database','html','css'],
        'mobile'    => ['flutter','react native','kotlin','swift','android','ios'],
        'devops'    => ['docker','kubernetes','aws','ci/cd','linux','terraform'],
        'data'      => ['python','machine learning','pandas','numpy','sql','tensorflow','pytorch'],
    ];

    // ── Technical Depth Signals ───────────────────────────────────────────
    private const TECH_DEPTH_SIGNALS = [
        'architecture'  => ['microservices','distributed','architecture','system design','scalable','fault-tolerant'],
        'engineering'   => ['optimized','performance','latency','throughput','concurrent','async','caching'],
        'quality'       => ['unit test','tdd','bdd','code review','ci/cd','clean code','solid'],
        'security'      => ['jwt','oauth','authentication','authorization','encryption','ssl'],
        'data'          => ['database design','indexing','query optimization','normalization','replication'],
    ];

    /* ------------------------------------------------------------------ */
    /*  Public API                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Score and rank all candidates for a given internship.
     *
     * @param  array $candidateProfiles  Array of [user_id, parsed_resume, resume_text]
     * @param  array $jdAnalysis         From JobDescriptionAnalyzer::analyze()
     * @return array Ranked candidates with full intelligence data
     */
    public function rankCandidates(array $candidateProfiles, array $jdAnalysis): array
    {
        $ranked = [];

        foreach ($candidateProfiles as $candidate) {
            $intelligence = $this->evaluateCandidate(
                $candidate,
                $jdAnalysis
            );
            $ranked[] = $intelligence;
        }

        // Sort by overall score descending
        usort($ranked, fn($a, $b) => $b['overall_score'] <=> $a['overall_score']);

        // Assign rank positions and tiers
        $total = count($ranked);
        foreach ($ranked as $idx => &$candidate) {
            $candidate['rank_position']  = $idx + 1;
            $candidate['total_candidates'] = $total;
            $candidate['rank_tier']      = $this->computeRankTier($idx + 1, $total);
        }
        unset($candidate);

        return $ranked;
    }

    /**
     * Evaluate a single candidate for a given JD.
     */
    public function evaluateCandidate(array $candidate, array $jdAnalysis): array
    {
        $resumeText  = strtolower($candidate['resume_text'] ?? $candidate['raw_text'] ?? '');
        $parsed      = $candidate['parsed'] ?? $candidate;
        $reqSkills   = array_map('strtolower', $jdAnalysis['required_skills'] ?? []);
        $keywords    = $jdAnalysis['keywords'] ?? [];
        $roleCategory= $jdAnalysis['role_category'] ?? 'general';

        // ── Component Scores ──────────────────────────────────────────
        [$skillScore, $matching, $missing] = $this->scoreSkillMatch($reqSkills, $resumeText, $jdAnalysis);
        [$keywordScore]                    = $this->scoreKeywordMatch($keywords, $resumeText);
        $experienceScore                   = $this->scoreExperienceMatch($parsed, $jdAnalysis, $reqSkills);
        $projectScore                      = $this->scoreProjectRelevance($parsed, $reqSkills, $roleCategory);
        $techDepthScore                    = $this->scoreTechnicalDepth($resumeText, $roleCategory);
        $formatScore                       = $this->scoreFormatQuality($parsed);

        // ── Overall Weighted Score ─────────────────────────────────────
        $overall = (int) round(
            $skillScore      * self::WEIGHTS['skill_match']
            + $keywordScore  * self::WEIGHTS['keyword_match']
            + $experienceScore * self::WEIGHTS['experience_match']
            + $projectScore  * self::WEIGHTS['project_relevance']
            + $techDepthScore * self::WEIGHTS['technical_depth']
            + $formatScore   * self::WEIGHTS['format_quality']
        );
        $overall = min(100, max(0, $overall));

        // ── Recruiter Trust Indicators ─────────────────────────────────
        $trustIndicators = $this->buildTrustIndicators(
            $skillScore, $experienceScore, $projectScore,
            $techDepthScore, $formatScore, $roleCategory, $resumeText
        );

        // ── Classify Missing Skills ────────────────────────────────────
        [$criticalMissing, $optionalMissing] = $this->classifyMissingSkills(
            $missing, $roleCategory
        );

        // ── Weak Areas Summary ─────────────────────────────────────────
        $weakAreas = $this->summarizeWeakAreas(
            $skillScore, $keywordScore, $experienceScore, $projectScore, $techDepthScore
        );

        // ── Recommendations ────────────────────────────────────────────
        $recommendations = $this->buildRecommendations(
            $criticalMissing, $optionalMissing, $weakAreas,
            $roleCategory, $techDepthScore, $experienceScore
        );

        return [
            'user_id'                => $candidate['user_id'],
            'overall_score'          => $overall,
            'skill_match_score'      => $skillScore,
            'keyword_score'          => $keywordScore,
            'experience_score'       => $experienceScore,
            'project_score'          => $projectScore,
            'technical_depth_score'  => $techDepthScore,
            'format_score'           => $formatScore,
            'role_category'          => $roleCategory,
            'matching_skills'        => $matching,
            'missing_skills'         => $missing,
            'critical_missing_skills'=> $criticalMissing,
            'optional_missing_skills'=> $optionalMissing,
            'weak_areas'             => $weakAreas,
            'recommendations'        => $recommendations,
            'trust_indicators'       => $trustIndicators,
            'backend_match'          => $trustIndicators['backend_match'],
            'frontend_match'         => $trustIndicators['frontend_match'],
            'project_quality'        => $trustIndicators['project_quality'],
            'technical_depth'        => $trustIndicators['technical_depth_label'],
            'recruiter_readability'  => $trustIndicators['recruiter_readability'],
            'experience_level'       => $trustIndicators['experience_level'],
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Scoring Components                                                  */
    /* ------------------------------------------------------------------ */

    private function scoreSkillMatch(array $reqSkills, string $resumeText, array $jdAnalysis = []): array
    {
        if (empty($reqSkills)) {
            return [50, [], []];
        }

        $result = $this->skillMatcher()->scoreSkills($reqSkills, $resumeText, $jdAnalysis);

        return [$result['score'], $result['matching'], $result['missing']];
    }

    private function scoreKeywordMatch(array $keywords, string $resumeText): array
    {
        if (empty($keywords)) return [50];
        $found = 0;
        foreach ($keywords as $kw) {
            if (str_contains($resumeText, strtolower($kw))) $found++;
        }
        return [(int) round(($found / count($keywords)) * 100)];
    }

    private function scoreExperienceMatch(array $parsed, array $jdAnalysis, array $reqSkills): int
    {
        $experience = $parsed['experience'] ?? [];
        if (empty($experience)) return 15;

        $expText = '';
        foreach ($experience as $exp) {
            $expText .= strtolower(($exp['org'] ?? '') . ' ' . ($exp['title'] ?? '') . ' ');
            foreach ($exp['bullets'] ?? [] as $b) $expText .= strtolower($b) . ' ';
        }

        $hits = 0;
        $total = count($reqSkills) + min(5, count($jdAnalysis['keywords'] ?? []));
        if ($total === 0) return 50;

        foreach ($reqSkills as $skill) {
            if (str_contains($expText, $skill)) $hits++;
        }
        foreach (array_slice($jdAnalysis['keywords'] ?? [], 0, 5) as $kw) {
            if (str_contains($expText, strtolower($kw))) $hits++;
        }

        // Bonus for strong action verbs
        $actionVerbs = ['developed','built','implemented','designed','led','optimized','reduced','increased','deployed','automated'];
        foreach ($actionVerbs as $v) {
            if (str_contains($expText, $v)) { $hits += 2; break; }
        }
        // Bonus for metrics
        if (preg_match('/\d+[%x]|\d+\s*(users|requests|ms\b|clients)/i', $expText)) $hits += 2;

        return min(100, (int) round(($hits / ($total + 2)) * 100));
    }

    private function scoreProjectRelevance(array $parsed, array $reqSkills, string $roleCategory): int
    {
        $projects = $parsed['projects'] ?? [];
        if (empty($projects)) return 20;

        $projText = '';
        foreach ($projects as $proj) {
            $projText .= strtolower(($proj['title'] ?? '') . ' ' . ($proj['tech'] ?? '') . ' ');
            foreach ($proj['bullets'] ?? [] as $b) $projText .= strtolower($b) . ' ';
        }

        $hits = 0;
        foreach ($reqSkills as $skill) {
            if (str_contains($projText, $skill)) $hits++;
        }

        $roleKws = $this->getRoleKeywords($roleCategory);
        foreach ($roleKws as $kw) {
            if (str_contains($projText, $kw)) $hits++;
        }

        $total = count($reqSkills) + count($roleKws);
        if ($total === 0) return 50;

        return min(100, (int) round(($hits / $total) * 100));
    }

    private function scoreTechnicalDepth(string $resumeText, string $roleCategory): int
    {
        $score = 0;

        foreach (self::TECH_DEPTH_SIGNALS as $category => $signals) {
            foreach ($signals as $signal) {
                if (str_contains($resumeText, $signal)) {
                    $score += 12;
                    break;
                }
            }
        }

        // Role-specific depth bonus
        $criticalSkills = self::CRITICAL_SKILLS_BY_ROLE[$roleCategory] ?? [];
        $found = 0;
        foreach ($criticalSkills as $skill) {
            if (str_contains($resumeText, $skill)) $found++;
        }
        if (!empty($criticalSkills)) {
            $score += (int) round(($found / count($criticalSkills)) * 40);
        }

        // Impact metrics bonus
        if (preg_match('/\d+[%x]|\d+\s*(users|ms\b|requests|clients|concurrent)/i', $resumeText)) {
            $score += 10;
        }

        return min(100, max(0, $score));
    }

    private function scoreFormatQuality(array $parsed): int
    {
        $score = 0;
        if (!empty(trim($parsed['summary'] ?? '')))      $score += 20;
        if (!empty($parsed['skills'] ?? []))             $score += 20;
        if (!empty($parsed['experience'] ?? []))         $score += 25;
        if (!empty($parsed['education'] ?? []))          $score += 15;
        if (!empty($parsed['projects'] ?? []))           $score += 10;
        if (!empty($parsed['email'] ?? ''))              $score += 5;
        if (!empty($parsed['phone'] ?? ''))              $score += 5;
        return min(100, $score);
    }

    /* ------------------------------------------------------------------ */
    /*  Recruiter Trust Indicators                                          */
    /* ------------------------------------------------------------------ */

    private function buildTrustIndicators(
        int $skillScore, int $expScore, int $projScore,
        int $techScore, int $formatScore,
        string $roleCategory, string $resumeText
    ): array {
        // Backend match score
        $backendSignals = ['api', 'rest', 'database', 'server', 'backend', 'sql', 'authentication', 'endpoint'];
        $backendHits = 0;
        foreach ($backendSignals as $sig) {
            if (str_contains($resumeText, $sig)) $backendHits++;
        }
        $backendScore   = (int) round(($backendHits / count($backendSignals)) * 100);
        $backendMatch   = $this->qualityLabel($backendScore);

        // Frontend match score
        $frontendSignals = ['javascript', 'react', 'vue', 'angular', 'html', 'css', 'responsive', 'component', 'ui', 'ux'];
        $frontendHits = 0;
        foreach ($frontendSignals as $sig) {
            if (str_contains($resumeText, $sig)) $frontendHits++;
        }
        $frontendScore = (int) round(($frontendHits / count($frontendSignals)) * 100);
        $frontendMatch = $this->qualityLabel($frontendScore);

        // Project quality (based on project score + bullet depth)
        $projectQuality = $this->qualityLabel($projScore);

        // Technical depth label
        $techDepthLabel = $this->depthLabel($techScore);

        // Recruiter readability (based on format + summary + experience quality)
        $readabilityScore   = (int) round(($formatScore * 0.5) + ($expScore * 0.3) + ($skillScore * 0.2));
        $recruiterReadability = $this->qualityLabel($readabilityScore);

        // Experience level
        $expLevel = 'entry';
        if ($expScore >= 70) $expLevel = 'mid';
        elseif ($expScore >= 85) $expLevel = 'senior';
        elseif ($expScore >= 50) $expLevel = 'junior';

        return [
            'backend_match'        => $backendMatch,
            'frontend_match'       => $frontendMatch,
            'project_quality'      => $projectQuality,
            'technical_depth_label'=> $techDepthLabel,
            'recruiter_readability'=> $recruiterReadability,
            'experience_level'     => $expLevel,
            'backend_score'        => $backendScore,
            'frontend_score'       => $frontendScore,
        ];
    }

    private function qualityLabel(int $score): string
    {
        if ($score >= 80) return 'Strong';
        if ($score >= 60) return 'Medium';
        if ($score >= 35) return 'Weak';
        return 'None';
    }

    private function depthLabel(int $score): string
    {
        if ($score >= 75) return 'High';
        if ($score >= 50) return 'Medium';
        if ($score >= 25) return 'Low';
        return 'Minimal';
    }

    /* ------------------------------------------------------------------ */
    /*  Missing Skills Classification                                       */
    /* ------------------------------------------------------------------ */

    private function classifyMissingSkills(array $missingSkills, string $roleCategory): array
    {
        $criticalPool = array_map('strtolower', self::CRITICAL_SKILLS_BY_ROLE[$roleCategory] ?? []);
        $critical = $optional = [];

        foreach ($missingSkills as $skill) {
            $skillLower = strtolower($skill);
            if (in_array($skillLower, $criticalPool)) {
                $critical[] = $skill;
            } else {
                $optional[] = $skill;
            }
        }

        return [$critical, $optional];
    }

    /* ------------------------------------------------------------------ */
    /*  Weak Areas Summary                                                  */
    /* ------------------------------------------------------------------ */

    private function summarizeWeakAreas(
        int $skillScore, int $keywordScore,
        int $expScore, int $projScore, int $techScore
    ): array {
        $areas = [];
        if ($skillScore < 50)   $areas[] = 'Low technical skill match';
        if ($keywordScore < 40) $areas[] = 'Poor ATS keyword coverage';
        if ($expScore < 40)     $areas[] = 'Work experience lacks role relevance';
        if ($projScore < 35)    $areas[] = 'Projects do not demonstrate required technologies';
        if ($techScore < 30)    $areas[] = 'Insufficient technical depth in resume';
        return $areas;
    }

    /* ------------------------------------------------------------------ */
    /*  Recommendations Builder                                             */
    /* ------------------------------------------------------------------ */

    private function buildRecommendations(
        array $criticalMissing, array $optionalMissing, array $weakAreas,
        string $roleCategory, int $techScore, int $expScore
    ): array {
        $recs = [];

        if (!empty($criticalMissing)) {
            $recs[] = [
                'priority' => 'critical',
                'text'     => 'Add critical missing skills: ' . implode(', ', array_slice($criticalMissing, 0, 4)),
            ];
        }

        if ($techScore < 40) {
            $recs[] = [
                'priority' => 'high',
                'text'     => 'Add technical depth: system design, performance optimization, or security-related achievements',
            ];
        }

        if ($expScore < 45) {
            $recs[] = [
                'priority' => 'high',
                'text'     => 'Rewrite experience bullets with measurable impact and role-specific technology mentions',
            ];
        }

        if (!empty($optionalMissing)) {
            $recs[] = [
                'priority' => 'medium',
                'text'     => 'Consider adding: ' . implode(', ', array_slice($optionalMissing, 0, 3)),
            ];
        }

        if (in_array('Poor ATS keyword coverage', $weakAreas)) {
            $recs[] = [
                'priority' => 'medium',
                'text'     => 'Integrate job description keywords naturally in your summary and experience sections',
            ];
        }

        // Role-specific recommendations
        if ($roleCategory === 'backend' && $techScore < 60) {
            $recs[] = ['priority' => 'low', 'text' => 'Add backend architecture terminology: REST APIs, database design, authentication, caching'];
        }
        if ($roleCategory === 'frontend' && $techScore < 60) {
            $recs[] = ['priority' => 'low', 'text' => 'Mention component architecture, responsive design, and state management patterns'];
        }
        if (in_array($roleCategory, ['backend', 'fullstack']) && $techScore < 50) {
            $recs[] = ['priority' => 'low', 'text' => 'Include measurable impact metrics in project bullets (response time, user load, etc.)'];
        }

        return $recs;
    }

    /* ------------------------------------------------------------------ */
    /*  Rank Tier                                                           */
    /* ------------------------------------------------------------------ */

    private function computeRankTier(int $position, int $total): string
    {
        if ($total === 0) return 'unranked';
        $percentile = ($position / $total) * 100;
        if ($percentile <= 10) return 'top10';
        if ($percentile <= 25) return 'top25';
        if ($percentile <= 60) return 'average';
        return 'below';
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function getRoleKeywords(string $role): array
    {
        $map = [
            'backend'   => ['api', 'rest', 'database', 'server', 'authentication', 'backend', 'sql'],
            'frontend'  => ['component', 'responsive', 'ui', 'ux', 'css', 'javascript', 'frontend', 'design'],
            'fullstack' => ['api', 'database', 'responsive', 'component', 'full stack', 'end-to-end'],
            'mobile'    => ['app', 'mobile', 'android', 'ios', 'flutter', 'native'],
            'devops'    => ['deployment', 'docker', 'ci/cd', 'cloud', 'infrastructure', 'kubernetes'],
            'data'      => ['model', 'dataset', 'training', 'prediction', 'accuracy', 'pipeline', 'analytics'],
        ];
        return $map[$role] ?? [];
    }
}
