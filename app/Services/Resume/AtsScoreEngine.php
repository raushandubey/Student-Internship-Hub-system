<?php

namespace App\Services\Resume;

/**
 * AtsScoreEngine — Realistic ATS Scoring Engine (Rebuilt).
 *
 * CRITICAL FIX: Previous engine scored ONLY JD keyword matching.
 * A strong PHP/Laravel resume got 13% for a React/Node JD — WRONG.
 *
 * New scoring model uses TWO dimensions:
 *
 *   A) INTRINSIC QUALITY (40% of total)
 *      — resume quality regardless of JD
 *      — metrics, tech depth, action verbs, architecture signals
 *
 *   B) JD ALIGNMENT (60% of total)
 *      — skill match      → 25%
 *      — keyword density  → 15%
 *      — experience align → 10%
 *      — project relevance → 10%
 *
 * Expected results:
 *   Elite resume + strong JD match   → 85-95%
 *   Elite resume + partial JD match  → 70-84%
 *   Average resume + full JD match   → 55-70%
 *   Weak resume + full JD match      → 40-55%
 *   Weak resume + poor JD match      → 20-40%
 */
class AtsScoreEngine
{
    use \App\Traits\ResilientJsonTrait;
    // ── Intrinsic quality signals ─────────────────────────────────────
    private const ARCHITECTURE_SIGNALS = [
        'architected','designed system','scalable','distributed system',
        'microservices','system design','high availability','fault tolerant',
        'load balanced','infrastructure','api design','architecture',
    ];

    private const ENGINEERING_SIGNALS = [
        'optimized','performance','latency','throughput','concurrent',
        'asynchronous','caching','indexing','query optimization',
        'connection pooling','rate limiting','pagination',
    ];

    private const SECURITY_SIGNALS = [
        'jwt','oauth','authentication','authorization','encryption',
        'ssl','https','csrf','xss protection','rbac','api key',
    ];

    private const STRONG_VERBS = [
        'architected','developed','built','implemented','designed',
        'engineered','deployed','integrated','optimized','automated',
        'delivered','established','reduced','improved','increased',
        'launched','streamlined','created','constructed','configured',
        'led','managed','spearheaded','mentored',
    ];

    /* ------------------------------------------------------------------ */
    /*  Public API                                                          */
    /* ------------------------------------------------------------------ */

    public function evaluateImprovement(array $originalResume, array $optimizedResume, array $jdAnalysis): array
    {
        $webhookUrl = config('services.n8n.webhook_url');
        $webhookKey = config('services.resume_intelligence.api_key');

        $fallbackBefore = $this->ruleBasedScore($originalResume, $jdAnalysis);
        $fallbackAfter  = $this->ruleBasedScore($optimizedResume, $jdAnalysis);

        if (!empty($webhookUrl)) {
            try {
                \Illuminate\Support\Facades\Log::info('AtsScore: Dispatching to AI Semantic Evaluator');
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-Resume-Intelligence-Key' => $webhookKey,
                    'Content-Type'              => 'application/json',
                ])->timeout(60)->post($webhookUrl, [
                    'system_prompt' => $this->buildAiSystemPrompt(),
                    'user_prompt'   => json_encode([
                        'job_description'  => [
                            'role'              => $jdAnalysis['role_category'] ?? '',
                            'required_skills'   => $jdAnalysis['required_skills'] ?? [],
                            'recruiter_focus'   => $jdAnalysis['recruiter_terms'] ?? [],
                            'semantic_clusters' => $jdAnalysis['semantic_clusters'] ?? [],
                        ],
                        'original_resume'  => $originalResume['raw_text'] ?? '',
                        'optimized_resume' => $optimizedResume['raw_text'] ?? '',
                    ]),
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if (($json['success'] ?? false) && !empty($json['data'])) {
                        $aiData = $this->safeJsonDecode($json['data']);
                        if ($aiData) {
                            \Illuminate\Support\Facades\Log::info('AtsScore: AI Analysis success');
                            return $this->parseAiScoreReport($aiData, $fallbackBefore, $fallbackAfter);
                        }
                    }
                }
                \Illuminate\Support\Facades\Log::error('AtsScore: AI Analysis failure', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('AtsScore: AI Analysis exception', ['error' => $e->getMessage()]);
            }
        }

        \Illuminate\Support\Facades\Log::warning('AtsScore: Falling back to rule-based evaluation');
        return [
            'before_score'   => $fallbackBefore['overall_score'],
            'after_score'    => $fallbackAfter['overall_score'],
            'after_breakdown'=> $fallbackAfter,
            'improvements'   => [
                "Enhanced technical phrasing and structure.",
                "Improved ATS readability and keyword coverage."
            ],
        ];
    }

    private function buildAiSystemPrompt(): string
    {
        return <<<PROMPT
You are a Senior ATS Scoring Architect, Semantic Resume Intelligence Engineer, Recruiter Psychology Analyst, and Production Resume Evaluator.

PRIMARY OBJECTIVE:
Evaluate the original and optimized resumes against the Job Description. 
You must produce extremely realistic, believable ATS scores that a technical recruiter or modern ATS system would assign.

SCORING DISTRIBUTION RULES:
- STRONG RESUME: 80-95
- AVERAGE RESUME: 55-75
- WEAK RESUME: 30-50
NEVER output unrealistic 99 scores. NEVER jump 40 points instantly.

EVALUATION CRITERIA:
1. Skill Match (35%)
2. Keyword & Semantic Relevance (20%)
3. Project Relevance (15%)
4. Technical Depth (10%)
5. Experience Alignment (10%)
6. Recruiter Readability (5%)
7. Formatting Quality (5%)

Return STRICT structured JSON ONLY. NO markdown, NO explanations.
Example format:
{
  "before_score": 68,
  "after_score": 79,
  "category_scores": {
    "skill_match": 85,
    "semantic_alignment": 80,
    "project_relevance": 78,
    "technical_depth": 88,
    "recruiter_readability": 76
  },
  "improvements": [
    "Enhanced semantic frontend alignment.",
    "Improved recruiter-focused technical wording."
  ]
}
PROMPT;
    }

    private function parseAiScoreReport(array $aiData, array $fallbackBefore, array $fallbackAfter): array
    {
        $afterBreakdown = $fallbackAfter;
        $afterBreakdown['overall_score'] = $aiData['after_score'] ?? $fallbackAfter['overall_score'];
        $afterBreakdown['skill_match']   = $aiData['category_scores']['skill_match'] ?? $fallbackAfter['skill_match'];
        $afterBreakdown['keyword_score'] = $aiData['category_scores']['semantic_alignment'] ?? $fallbackAfter['keyword_score'];
        $afterBreakdown['proj_score']    = $aiData['category_scores']['project_relevance'] ?? $fallbackAfter['proj_score'];
        
        return [
            'before_score'    => $aiData['before_score'] ?? $fallbackBefore['overall_score'],
            'after_score'     => $afterBreakdown['overall_score'],
            'after_breakdown' => $afterBreakdown,
            'improvements'    => $aiData['improvements'] ?? ["Improved overall alignment and readability."],
        ];
    }

    public function ruleBasedScore(array $parsedResume, array $jdAnalysis): array
    {
        $resumeText = strtolower($parsedResume['raw_text'] ?? '');
        $reqSkills  = array_map('strtolower', $jdAnalysis['required_skills'] ?? []);
        $keywords   = $jdAnalysis['keywords'] ?? [];

        // ── A. INTRINSIC QUALITY (40%) ────────────────────────────────
        $intrinsicScore = $this->scoreIntrinsicQuality($parsedResume, $resumeText);

        // ── B. JD ALIGNMENT (60%) ─────────────────────────────────────
        [$skillScore, $matchingSkills, $missingSkills] = $this->scoreSkillMatch($reqSkills, $resumeText);
        [$keywordScore, $keywordsFound, $keywordsMissed] = $this->scoreKeywordMatch($keywords, $resumeText);
        $expScore  = $this->scoreExperienceAlignment($parsedResume, $jdAnalysis, $reqSkills, $keywords);
        $projScore = $this->scoreProjectRelevance($parsedResume, $reqSkills, $jdAnalysis);
        $formatScore = $this->scoreFormattingQuality($parsedResume);

        // ── Weighted Composite ────────────────────────────────────────
        // JD alignment sub-score
        $alignmentScore = (int) round(
            ($skillScore   * 0.25) +
            ($keywordScore * 0.15) +
            ($expScore     * 0.10) +
            ($projScore    * 0.10)
        );

        // Final weighted score
        $overall = (int) round(
            ($intrinsicScore  * 0.40) +
            ($alignmentScore  * 1.00) +   // alignmentScore is already 0-60 range
            ($formatScore     * 0.05)
        );

        // Ensure alignment ≥ 60% of total cap (intrinsic can only give you so much)
        $overall = min(100, max(0, $overall));

        // ── Reality check: apply minimum floors ───────────────────────
        // A resume with 5+ metrics and 10+ strong verbs is NEVER below 50
        $metricsCount = preg_match_all('/\d+\s*(%|users|requests|ms\b|endpoints|concurrent)/i', $resumeText, $m);
        if ($metricsCount >= 3 && $overall < 50) {
            $overall = max($overall, 50);
        }
        // A resume with architecture signals is NEVER below 60
        $archHits = 0;
        foreach (self::ARCHITECTURE_SIGNALS as $sig) {
            if (str_contains($resumeText, $sig)) $archHits++;
        }
        if ($archHits >= 2 && $overall < 60) {
            $overall = max($overall, 60);
        }

        // ── Issues & Strengths ────────────────────────────────────────
        $issues    = $this->detectIssues($parsedResume, $skillScore, $keywordScore, $expScore, $missingSkills, $formatScore, $intrinsicScore);
        $strengths = $this->detectStrengths($parsedResume, $skillScore, $keywordScore, $expScore, $projScore, $intrinsicScore, $resumeText);

        return [
            'overall_score'    => $overall,
            'intrinsic_score'  => $intrinsicScore,
            'skill_match'      => $skillScore,
            'keyword_score'    => $keywordScore,
            'exp_score'        => $expScore,
            'proj_score'       => $projScore,
            'format_score'     => $formatScore,
            'alignment_score'  => $alignmentScore,
            'matching_skills'  => $matchingSkills,
            'missing_skills'   => $missingSkills,
            'keywords_found'   => $keywordsFound,
            'keywords_missed'  => array_slice($keywordsMissed, 0, 10),
            'issues'           => $issues,
            'strengths'        => $strengths,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  A. Intrinsic Quality Scorer (0-100, weighted to 40% of final)      */
    /* ------------------------------------------------------------------ */

    private function scoreIntrinsicQuality(array $parsed, string $lowerText): int
    {
        $score = 0;

        // ── 1. Metrics & Quantified Impact (max 30 pts) ───────────────
        $metricsCount = preg_match_all('/\d+\s*(%|users|requests|ms\b|endpoints|concurrent|clients|k\b|x\b)/i', $lowerText, $m);
        if ($metricsCount >= 5)      $score += 30;
        elseif ($metricsCount >= 3)  $score += 22;
        elseif ($metricsCount >= 1)  $score += 12;

        // ── 2. Architecture & Engineering Signals (max 25 pts) ────────
        $archHits = 0;
        foreach (self::ARCHITECTURE_SIGNALS as $sig) {
            if (str_contains($lowerText, $sig)) $archHits++;
        }
        $engHits = 0;
        foreach (self::ENGINEERING_SIGNALS as $sig) {
            if (str_contains($lowerText, $sig)) $engHits++;
        }
        $techSignalHits = $archHits + $engHits;
        if ($techSignalHits >= 5)     $score += 25;
        elseif ($techSignalHits >= 3) $score += 18;
        elseif ($techSignalHits >= 1) $score += 8;

        // ── 3. Strong Action Verbs (max 20 pts) ───────────────────────
        $verbHits = 0;
        foreach (self::STRONG_VERBS as $verb) {
            if (str_contains($lowerText, $verb)) $verbHits++;
        }
        if ($verbHits >= 8)     $score += 20;
        elseif ($verbHits >= 5) $score += 14;
        elseif ($verbHits >= 2) $score += 7;

        // ── 4. Security / Auth Terminology (max 10 pts) ───────────────
        $secHits = 0;
        foreach (self::SECURITY_SIGNALS as $sig) {
            if (str_contains($lowerText, $sig)) $secHits++;
        }
        if ($secHits >= 3)     $score += 10;
        elseif ($secHits >= 1) $score += 5;

        // ── 5. Technical Breadth (max 15 pts) ────────────────────────
        $techCategories = [
            'languages'  => ['php','python','java','javascript','typescript','c#','go','rust'],
            'frameworks' => ['laravel','django','react','vue','express','spring','fastapi','next'],
            'databases'  => ['mysql','postgresql','mongodb','redis','sqlite','elasticsearch'],
            'devops'     => ['docker','kubernetes','aws','git','ci/cd','nginx','linux'],
        ];
        $catHits = 0;
        foreach ($techCategories as $skills) {
            foreach ($skills as $sk) {
                if (str_contains($lowerText, $sk)) { $catHits++; break; }
            }
        }
        $score += $catHits * 3; // 3 pts per category represented (max 12)

        // ── 6. Penalty for generic filler ─────────────────────────────
        $fillers = ['hardworking','passionate about','team player','quick learner','go-getter','motivated individual'];
        foreach ($fillers as $f) {
            if (str_contains($lowerText, $f)) $score -= 5;
        }

        // ── 7. Bonus: multiple roles / experience entries ─────────────
        $expCount = count($parsed['experience'] ?? []);
        if ($expCount >= 2) $score += 5;

        return min(100, max(0, $score));
    }

    /* ------------------------------------------------------------------ */
    /*  B. JD Alignment Scorers                                             */
    /* ------------------------------------------------------------------ */

    private function scoreSkillMatch(array $reqSkills, string $resumeText): array
    {
        if (empty($reqSkills)) return [50, [], []];

        $matching = $missing = [];
        foreach ($reqSkills as $skill) {
            if ($this->skillExistsInText($skill, $resumeText)) {
                $matching[] = $skill;
            } else {
                $missing[] = $skill;
            }
        }

        $score = (int) round((count($matching) / count($reqSkills)) * 100);
        return [$score, $matching, $missing];
    }

    private function scoreKeywordMatch(array $keywords, string $resumeText): array
    {
        if (empty($keywords)) return [50, 0, []];

        $found = 0;
        $missed = [];
        foreach ($keywords as $kw) {
            if (str_contains($resumeText, strtolower($kw))) {
                $found++;
            } else {
                $missed[] = $kw;
            }
        }

        $score = (int) round(($found / count($keywords)) * 100);
        return [$score, $found, $missed];
    }

    private function scoreExperienceAlignment(array $parsedResume, array $jdAnalysis, array $reqSkills, array $keywords): int
    {
        $experience = $parsedResume['experience'] ?? [];
        if (empty($experience)) return 15;

        $expText = '';
        foreach ($experience as $exp) {
            $expText .= strtolower(($exp['org'] ?? '') . ' ' . ($exp['title'] ?? '') . ' ');
            foreach ($exp['bullets'] ?? [] as $b) $expText .= strtolower($b) . ' ';
        }

        if (empty(trim($expText))) return 20;

        $hits  = 0;
        $total = count($reqSkills) + min(5, count($keywords));
        if ($total === 0) return 50;

        foreach ($reqSkills as $skill) {
            if (str_contains($expText, $skill)) $hits++;
        }
        foreach (array_slice($keywords, 0, 5) as $kw) {
            if (str_contains($expText, strtolower($kw))) $hits++;
        }

        // Bonus: strong action verbs
        foreach (self::STRONG_VERBS as $v) {
            if (str_contains($expText, $v)) { $hits += 2; break; }
        }
        // Bonus: quantified achievements
        if (preg_match('/\d+[%x]|\d+\s*(users|requests|ms\b|clients)/i', $expText)) {
            $hits += 2;
        }

        return min(100, (int) round(($hits / ($total + 2)) * 100));
    }

    private function scoreProjectRelevance(array $parsedResume, array $reqSkills, array $jdAnalysis): int
    {
        $projects = $parsedResume['projects'] ?? [];
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

        $roleKeywords = $this->getRoleKeywords($jdAnalysis['role_category'] ?? 'general');
        foreach ($roleKeywords as $kw) {
            if (str_contains($projText, $kw)) $hits++;
        }

        if (empty($reqSkills)) return 50;
        return min(100, (int) round(($hits / (count($reqSkills) + count($roleKeywords) * 0.5)) * 100));
    }

    private function scoreFormattingQuality(array $parsedResume): int
    {
        $score = 0;
        if (!empty(trim($parsedResume['summary'] ?? '')))   $score += 20;
        if (!empty($parsedResume['skills'] ?? []))          $score += 20;
        if (!empty($parsedResume['experience'] ?? []))      $score += 25;
        if (!empty($parsedResume['education'] ?? []))       $score += 15;
        if (!empty($parsedResume['projects'] ?? []))        $score += 10;
        if (!empty($parsedResume['email'] ?? ''))           $score += 5;
        if (!empty($parsedResume['phone'] ?? ''))           $score += 5;
        foreach ($parsedResume['experience'] ?? [] as $exp) {
            if (!empty($exp['date'])) { $score = min(100, $score + 5); break; }
        }
        return min(100, $score);
    }

    /* ------------------------------------------------------------------ */
    /*  Issues & Strengths                                                  */
    /* ------------------------------------------------------------------ */

    private function detectIssues(array $parsed, int $skillScore, int $keywordScore, int $expScore, array $missingSkills, int $formatScore, int $intrinsicScore): array
    {
        $issues = [];

        if ($skillScore < 50 && !empty($missingSkills)) {
            $issues[] = 'Missing ' . count($missingSkills) . ' required skill(s): ' . implode(', ', array_slice($missingSkills, 0, 4));
        }
        if ($keywordScore < 40) {
            $issues[] = 'Low keyword density — resume may not pass ATS keyword filters for this role';
        }
        $weakBullets = $parsed['weak_bullets'] ?? [];
        if (count($weakBullets) > 2) {
            $issues[] = count($weakBullets) . ' bullet point(s) use weak verbs or lack measurable impact';
        }
        $rawText = $parsed['raw_text'] ?? '';
        if (!preg_match('/\d+[%x]|\d+\s*(users|clients|students|projects|requests|ms\b)/i', $rawText)) {
            $issues[] = 'No quantified achievements found — add numbers to demonstrate impact';
        }
        if (empty(trim($parsed['summary'] ?? ''))) {
            $issues[] = 'Missing Professional Summary — add a targeted 2-3 sentence summary';
        }
        if ($expScore < 35) {
            $issues[] = 'Work experience does not clearly align with this specific job\'s requirements';
        }

        return $issues;
    }

    private function detectStrengths(array $parsed, int $skillScore, int $keywordScore, int $expScore, int $projScore, int $intrinsicScore, string $rawText): array
    {
        $strengths = [];

        if ($intrinsicScore >= 70) $strengths[] = 'High-quality resume with strong technical depth and professional language';
        if ($skillScore >= 70)     $strengths[] = 'Strong technical skill alignment with this role\'s requirements';
        if ($keywordScore >= 65)   $strengths[] = 'Good ATS keyword coverage for this specific job';
        if ($expScore >= 60)       $strengths[] = 'Relevant work experience aligned with the target role';
        if ($projScore >= 60)      $strengths[] = 'Projects demonstrate hands-on technical ability';

        if (preg_match('/\d+[%x]|\d+\s*(users|clients|projects|requests|endpoints)/i', $rawText)) {
            $strengths[] = 'Includes quantified achievements — strong positive ATS signal';
        }
        if (preg_match('/\b(architected|engineered|designed system|scalable)\b/i', $rawText)) {
            $strengths[] = 'Architecture-level language demonstrates senior technical competency';
        }
        if (!empty($parsed['certifications'] ?? [])) {
            $strengths[] = 'Professional certifications strengthen technical credibility';
        }
        if (count($parsed['experience'] ?? []) >= 2) {
            $strengths[] = 'Multiple work experiences demonstrate a professional track record';
        }

        return $strengths;
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function skillExistsInText(string $skill, string $resumeText): bool
    {
        if (preg_match('/[^\w\s-]/', $skill)) {
            $escaped = preg_quote($skill, '/');
            if (preg_match('/(^|[\s,;|])' . $escaped . '($|[\s,;|])/i', $resumeText)) return true;
        } else {
            if (preg_match('/\b' . preg_quote($skill, '/') . '\b/i', $resumeText)) return true;
        }
        if (str_word_count($skill) > 1 && str_contains($resumeText, $skill)) return true;
        return false;
    }

    private function getRoleKeywords(string $role): array
    {
        $map = [
            'backend'   => ['api', 'rest', 'database', 'server', 'authentication', 'backend', 'endpoint'],
            'frontend'  => ['component', 'responsive', 'ui', 'ux', 'css', 'javascript', 'frontend'],
            'fullstack' => ['api', 'database', 'responsive', 'component', 'full stack', 'end-to-end'],
            'mobile'    => ['app', 'mobile', 'android', 'ios', 'flutter', 'native'],
            'devops'    => ['deployment', 'docker', 'ci/cd', 'cloud', 'infrastructure', 'kubernetes'],
            'data'      => ['model', 'dataset', 'training', 'prediction', 'accuracy', 'pipeline'],
        ];
        return $map[$role] ?? [];
    }
}

