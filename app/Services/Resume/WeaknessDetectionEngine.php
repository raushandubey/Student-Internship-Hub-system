<?php

namespace App\Services\Resume;

/**
 * WeaknessDetectionEngine — Step 5 of the Resume Intelligence Pipeline.
 *
 * Detects weaknesses in a parsed resume against a JD analysis:
 *   - Weak bullets (verb, impact, length)
 *   - Missing ATS keywords
 *   - Poor technical depth
 *   - Weak summary signals
 *   - Missing recruiter terminology
 *   - Low ATS keyword density
 *
 * Returns a structured weakness report with prioritized recommendations.
 */
class WeaknessDetectionEngine
{
    public function __construct(
        private ?SemanticSkillMatcher $skillMatcher = null,
    ) {}

    private function skillMatcher(): SemanticSkillMatcher
    {
        return $this->skillMatcher ??= new SemanticSkillMatcher();
    }

    private const STRONG_ACTION_VERBS = [
        'developed','built','implemented','architected','deployed','integrated',
        'optimized','engineered','designed','migrated','refactored','automated',
        'created','established','led','managed','directed','coordinated',
        'improved','increased','reduced','enhanced','accelerated','streamlined',
        'analyzed','researched','evaluated','identified','monitored','measured',
        'launched','delivered','shipped','produced','generated','authored',
        'collaborated','partnered','facilitated','executed','achieved',
    ];

    private const WEAK_VERB_PATTERNS = [
        '/^worked\s+on\b/i'            => 'Use "Developed" or "Built" instead of "worked on"',
        '/^helped\s+(with|to)\b/i'     => 'Use "Contributed to" or "Supported" instead of "helped"',
        '/^assisted\s+(in|with)\b/i'   => 'Use "Collaborated on" instead of "assisted"',
        '/^was\s+responsible\s+for\b/i'=> 'Lead with an action verb — "Owned" or "Managed"',
        '/^did\s+(some|a|the)\b/i'     => 'Use a specific action verb instead of "did"',
        '/^participated\s+in\b/i'      => 'Describe your specific contribution, not just participation',
        '/^involved\s+in\b/i'          => 'Describe what you built/delivered, not that you were "involved"',
        '/^part\s+of\s+a\s+team\b/i'  => 'Lead with what YOU delivered, not team membership',
    ];

    private const GENERIC_PHRASES = [
        'various',   'many',      'multiple',  'several',
        'a lot',     'things',    'stuff',     'aspects',
        'different', 'numerous',  'kind of',   'sort of',
    ];

    /* ------------------------------------------------------------------ */
    /*  Public API                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Detect all weaknesses in the resume relative to the JD analysis.
     *
     * @param  array $parsedResume  Output from ResumeParserEngine::parse()
     * @param  array $jdAnalysis    Output from JobDescriptionAnalyzer::analyze()
     * @return array{
     *   summary_weak: bool,
     *   missing_keywords: array,
     *   missing_skills: array,
     *   weak_bullets: array,
     *   sections_missing: array,
     *   weak_areas: array,
     *   overall_weakness_score: int,
     *   recommendations: array,
     * }
     */
    public function detect(array $parsedResume, array $jdAnalysis): array
    {
        $resumeText  = strtolower($parsedResume['raw_text'] ?? '');
        $reqSkills   = array_map('strtolower', $jdAnalysis['required_skills'] ?? []);
        $keywords    = $jdAnalysis['keywords'] ?? [];

        // ── 1. Missing Skills (semantic + literal) ───────────────────────
        $skillResult = $this->skillMatcher()->scoreSkills($reqSkills, $resumeText, $jdAnalysis);
        $missingSkills  = $skillResult['missing'];
        $matchingSkills = $skillResult['matching'];
        $skillExplanations = $skillResult['explanations'];

        // ── 2. Missing Keywords ───────────────────────────────────────────
        $missingKeywords = [];
        $keywordsFound   = 0;
        foreach ($keywords as $kw) {
            if (str_contains($resumeText, strtolower($kw))) {
                $keywordsFound++;
            } else {
                $missingKeywords[] = $kw;
            }
        }

        // ── 3. Bullet Weakness Analysis ───────────────────────────────────
        $weakBullets = $this->analyzeBullets($parsedResume);

        // ── 4. Summary Weakness ───────────────────────────────────────────
        $summaryWeak   = $this->analyzeSummary($parsedResume['summary'] ?? '', $jdAnalysis);
        $summaryIssues = $summaryWeak['issues'];

        // ── 5. Missing Sections ───────────────────────────────────────────
        $sectionsMissing = $this->detectMissingSections($parsedResume);

        // ── 6. Technical Depth ────────────────────────────────────────────
        $techDepthIssues = $this->analyzeTechnicalDepth($parsedResume, $jdAnalysis);

        // ── 7. Recruiter Terminology ──────────────────────────────────────
        $recruiterTerms = $jdAnalysis['recruiter_terms'] ?? [];
        $missingTerms   = [];
        foreach ($recruiterTerms as $term) {
            if (!str_contains($resumeText, strtolower($term))) {
                $missingTerms[] = $term;
            }
        }

        // ── 8. Compute Weakness Score (0-100, higher = weaker) ────────────
        $weaknessScore = $this->computeWeaknessScore(
            count($missingSkills), count($reqSkills),
            count($weakBullets), count($missingKeywords), count($keywords),
            $summaryWeak['is_weak'], $sectionsMissing
        );

        // ── 9. Compile Recommendations ────────────────────────────────────
        $recommendations = $this->buildRecommendations(
            $missingSkills, $missingKeywords, $weakBullets,
            $summaryIssues, $techDepthIssues, $sectionsMissing, $missingTerms
        );

        // ── 10. Compile Weak Areas (high-level) ──────────────────────────
        $weakAreas = [];
        if (!empty($missingSkills))   $weakAreas[] = 'Missing required technical skills';
        if (!empty($weakBullets))     $weakAreas[] = 'Weak bullet points — lacks impact and action verbs';
        if ($summaryWeak['is_weak'])  $weakAreas[] = 'Professional summary is too generic';
        if (!empty($techDepthIssues)) $weakAreas[] = 'Insufficient technical depth in descriptions';
        if (!empty($sectionsMissing)) $weakAreas[] = 'Missing resume sections: ' . implode(', ', $sectionsMissing);

        return [
            'missing_skills'        => $missingSkills,
            'matching_skills'       => $matchingSkills,
            'skill_match_explanations' => $skillExplanations,
            'missing_keywords'      => array_slice($missingKeywords, 0, 15),
            'weak_bullets'          => $weakBullets,
            'summary_weak'          => $summaryWeak['is_weak'],
            'summary_issues'        => $summaryIssues,
            'sections_missing'      => $sectionsMissing,
            'tech_depth_issues'     => $techDepthIssues,
            'missing_recruiter_terms' => array_slice($missingTerms, 0, 8),
            'weak_areas'            => $weakAreas,
            'overall_weakness_score'=> $weaknessScore,
            'recommendations'       => $recommendations,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Bullet Analysis                                                     */
    /* ------------------------------------------------------------------ */

    private function analyzeBullets(array $parsedResume): array
    {
        $weakBullets = [];
        $allBullets  = [];

        foreach ($parsedResume['experience'] ?? [] as $exp) {
            foreach ($exp['bullets'] ?? [] as $b) {
                $allBullets[] = ['text' => $b, 'section' => 'experience'];
            }
        }
        foreach ($parsedResume['projects'] ?? [] as $proj) {
            foreach ($proj['bullets'] ?? [] as $b) {
                $allBullets[] = ['text' => $b, 'section' => 'projects'];
            }
        }

        foreach ($allBullets as $b) {
            $issues  = [];
            $advice  = [];
            $text    = $b['text'];

            // Weak verb check
            foreach (self::WEAK_VERB_PATTERNS as $pattern => $adviceText) {
                if (preg_match($pattern, $text)) {
                    $issues[] = 'weak_verb';
                    $advice[] = $adviceText;
                    break;
                }
            }

            // No strong action verb at start
            $firstWord = strtolower(explode(' ', trim($text))[0] ?? '');
            if (!in_array($firstWord, self::STRONG_ACTION_VERBS) && empty($issues)) {
                $issues[] = 'no_action_verb';
                $advice[] = 'Start with a strong action verb: ' . implode(', ', array_slice(self::STRONG_ACTION_VERBS, 0, 5));
            }

            // Missing quantifiable impact
            if (!preg_match('/\d+[%x]|\d+\s*(users|requests|ms\b|seconds?|clients|projects|team|members|endpoints|records|transactions|concurrent)/i', $text)) {
                $issues[] = 'no_measurable_impact';
                $advice[] = 'Add quantifiable results (e.g., "reduced latency by 40%", "served 500+ users")';
            }

            // Generic phrases
            foreach (self::GENERIC_PHRASES as $phrase) {
                if (stripos($text, $phrase) !== false) {
                    $issues[] = 'generic_language';
                    $advice[] = 'Replace vague language ("' . $phrase . '") with specifics';
                    break;
                }
            }

            // Length issues
            if (strlen($text) < 40) {
                $issues[] = 'too_short';
                $advice[] = 'Expand with: what you built + which technology + what impact';
            }
            if (strlen($text) > 200) {
                $issues[] = 'too_long';
                $advice[] = 'Trim to under 2 lines (~140 chars) — ATS scanners penalize long bullets';
            }

            if (!empty($issues)) {
                $weakBullets[] = [
                    'text'    => $text,
                    'section' => $b['section'],
                    'issues'  => $issues,
                    'advice'  => $advice,
                ];
            }
        }

        return $weakBullets;
    }

    /* ------------------------------------------------------------------ */
    /*  Summary Analysis                                                    */
    /* ------------------------------------------------------------------ */

    private function analyzeSummary(string $summary, array $jdAnalysis): array
    {
        $issues  = [];
        $isWeak  = false;
        $lower   = strtolower($summary);

        // Hard weakness: missing or too short
        if (empty($summary) || strlen($summary) < 60) {
            $issues[] = 'Summary is missing or too short — add 2-3 targeted sentences';
            $isWeak   = true;
        }

        if (!empty($summary)) {
            // WEAK only if 2+ generic HR filler phrases — this truly degrades quality
            $genericPhrases = ['hardworking', 'passionate', 'self-motivated', 'team player',
                               'detail-oriented', 'go-getter', 'fast learner', 'quick learner'];
            $genericCount = 0;
            foreach ($genericPhrases as $phrase) {
                if (str_contains($lower, $phrase)) $genericCount++;
            }
            if ($genericCount >= 2) {
                $issues[] = 'Summary uses generic HR filler phrases — replace with specific technical achievements';
                $isWeak   = true;
            }

            // Soft suggestion (does NOT mark weak): JD alignment
            $jobTitle = strtolower($jdAnalysis['job_title'] ?? '');
            if ($jobTitle) {
                $titleWords    = array_filter(explode(' ', $jobTitle), fn($w) => strlen($w) > 3);
                $titleMentioned = false;
                foreach ($titleWords as $word) {
                    if (str_contains($lower, $word)) { $titleMentioned = true; break; }
                }
                if (!$titleMentioned) {
                    $issues[] = 'Tip: Mention the target role or key tech from the JD to improve alignment';
                    // NOT setting $isWeak — a strong summary without the JD title is still strong
                }
            }

            // Soft suggestion: no required skills in summary
            $reqSkills = array_map('strtolower', $jdAnalysis['required_skills'] ?? []);
            $skillsInSummary = 0;
            foreach ($reqSkills as $skill) {
                if (str_contains($lower, $skill)) $skillsInSummary++;
            }
            if ($skillsInSummary === 0 && !empty($reqSkills) && !$isWeak) {
                $issues[] = 'Tip: Reference 1-2 required skills from the JD in your summary';
                // NOT setting $isWeak
            }
        }

        return ['is_weak' => $isWeak, 'issues' => $issues];
    }

    /* ------------------------------------------------------------------ */
    /*  Missing Section Detection                                           */
    /* ------------------------------------------------------------------ */

    private function detectMissingSections(array $parsedResume): array
    {
        $missing = [];

        if (empty(trim($parsedResume['summary'] ?? ''))) {
            $missing[] = 'Professional Summary';
        }
        if (empty($parsedResume['skills'] ?? [])) {
            $missing[] = 'Technical Skills';
        }
        if (empty($parsedResume['experience'] ?? []) && empty($parsedResume['projects'] ?? [])) {
            $missing[] = 'Experience or Projects';
        }
        if (empty($parsedResume['education'] ?? [])) {
            $missing[] = 'Education';
        }

        return $missing;
    }

    /* ------------------------------------------------------------------ */
    /*  Technical Depth Analysis                                            */
    /* ------------------------------------------------------------------ */

    private function analyzeTechnicalDepth(array $parsedResume, array $jdAnalysis): array
    {
        $issues      = [];
        $resumeText  = strtolower($parsedResume['raw_text'] ?? '');
        $roleCategory = $jdAnalysis['role_category'] ?? 'general';

        // Check for technical architecture terms
        $archTerms = ['api', 'database', 'server', 'client', 'endpoint', 'request', 'response', 'authentication'];
        $archFound = 0;
        foreach ($archTerms as $term) {
            if (str_contains($resumeText, $term)) $archFound++;
        }
        if ($archFound < 2) {
            $issues[] = 'Lacks technical architecture terminology (API, database, authentication, etc.)';
        }

        // Check for impact metrics
        if (!preg_match('/\d+[%x]|\d+\s*(users|ms\b|requests|clients|concurrent)/i', $parsedResume['raw_text'] ?? '')) {
            $issues[] = 'No measurable impact metrics — add numbers to demonstrate scale and results';
        }

        // Role-specific depth checks
        if (in_array($roleCategory, ['backend', 'fullstack'])) {
            $backendTerms = ['api', 'rest', 'database', 'sql', 'authentication', 'server', 'endpoint'];
            $found = 0;
            foreach ($backendTerms as $term) {
                if (str_contains($resumeText, $term)) $found++;
            }
            if ($found < 3) {
                $issues[] = 'Backend role expects API/database depth — add more technical specifics';
            }
        }

        if (in_array($roleCategory, ['frontend', 'fullstack'])) {
            $frontendTerms = ['responsive', 'component', 'ui', 'ux', 'css', 'javascript', 'html'];
            $found = 0;
            foreach ($frontendTerms as $term) {
                if (str_contains($resumeText, $term)) $found++;
            }
            if ($found < 2) {
                $issues[] = 'Frontend role expects UI/UX depth — mention component architecture or design work';
            }
        }

        return $issues;
    }

    /* ------------------------------------------------------------------ */
    /*  Weakness Scoring                                                    */
    /* ------------------------------------------------------------------ */

    private function computeWeaknessScore(
        int $missingSkillsCount, int $totalSkills,
        int $weakBulletsCount,
        int $missingKeywordsCount, int $totalKeywords,
        bool $summaryWeak,
        array $sectionsMissing
    ): int {
        $score = 0;

        // Missing skills penalty (up to 40 points)
        if ($totalSkills > 0) {
            $score += (int) round(($missingSkillsCount / $totalSkills) * 40);
        }

        // Weak bullets penalty (up to 25 points)
        $totalBullets = max(1, $weakBulletsCount + 5); // assume ~5 good bullets
        $score += (int) round(min(25, ($weakBulletsCount / $totalBullets) * 25));

        // Missing keywords penalty (up to 20 points)
        if ($totalKeywords > 0) {
            $score += (int) round(($missingKeywordsCount / $totalKeywords) * 20);
        }

        // Summary weakness (10 points)
        if ($summaryWeak) $score += 10;

        // Missing sections (5 points each)
        $score += count($sectionsMissing) * 5;

        return min(100, max(0, $score));
    }

    /* ------------------------------------------------------------------ */
    /*  Recommendations Builder                                             */
    /* ------------------------------------------------------------------ */

    private function buildRecommendations(
        array $missingSkills, array $missingKeywords,
        array $weakBullets, array $summaryIssues,
        array $techDepthIssues, array $sectionsMissing,
        array $missingTerms
    ): array {
        $recs = [];

        if (!empty($missingSkills)) {
            $recs[] = [
                'priority' => 'critical',
                'area'     => 'Skills',
                'action'   => 'Add missing skills to your Technical Skills section: ' . implode(', ', array_slice($missingSkills, 0, 5)),
            ];
        }

        foreach (array_slice($summaryIssues, 0, 2) as $issue) {
            $recs[] = ['priority' => 'high', 'area' => 'Summary', 'action' => $issue];
        }

        if (!empty($weakBullets)) {
            $recs[] = [
                'priority' => 'high',
                'area'     => 'Bullet Points',
                'action'   => count($weakBullets) . ' bullet(s) need stronger action verbs and measurable impact',
            ];
        }

        foreach (array_slice($techDepthIssues, 0, 2) as $issue) {
            $recs[] = ['priority' => 'medium', 'area' => 'Technical Depth', 'action' => $issue];
        }

        if (!empty($missingKeywords)) {
            $recs[] = [
                'priority' => 'medium',
                'area'     => 'ATS Keywords',
                'action'   => 'Integrate these JD keywords naturally: ' . implode(', ', array_slice($missingKeywords, 0, 6)),
            ];
        }

        if (!empty($missingTerms)) {
            $recs[] = [
                'priority' => 'low',
                'area'     => 'Recruiter Terminology',
                'action'   => 'Add industry terms: ' . implode(', ', array_slice($missingTerms, 0, 4)),
            ];
        }

        return $recs;
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

}
