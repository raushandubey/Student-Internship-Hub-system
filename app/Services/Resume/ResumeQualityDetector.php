<?php

namespace App\Services\Resume;

/**
 * ResumeQualityDetector — Step 0 of the Preservation Pipeline.
 *
 * Classifies resume quality BEFORE any AI touching occurs.
 *
 * Tiers:
 *   elite   → 85-100  : Preservation Mode — NO full rewrites, keyword-only
 *   strong  → 65-84   : Enhancement Mode — rewrite weak sections only
 *   average → 40-64   : Standard Mode    — rewrite weak sections, add structure
 *   weak    → 0-39    : Full Mode        — major improvements allowed
 *
 * A LOCKED section (section score ≥ 85) cannot be fully rewritten by AI.
 */
class ResumeQualityDetector
{
    use \App\Traits\ResilientJsonTrait;
    // Elite signals: only resumes with these patterns are truly elite
    private const ELITE_SIGNALS = [
        'architecture' => ['architected','designed system','scalable','distributed','microservices','system design'],
        'impact'       => ['%', 'reduced by', 'improved by', 'increased by', 'latency', 'throughput', 'requests per'],
        'scale'        => ['users', 'concurrent', 'production', 'enterprise', 'million', 'thousand'],
        'leadership'   => ['led','managed team','spearheaded','mentored','drove'],
        'tech_depth'   => ['jwt','oauth','redis','docker','kubernetes','ci/cd','rest api','graphql','microservice'],
    ];

    private const STRONG_VERBS = [
        'architected','developed','built','implemented','designed','engineered','deployed',
        'integrated','optimized','automated','delivered','established','reduced','improved',
        'increased','launched','streamlined','created','constructed','configured',
    ];

    /* ------------------------------------------------------------------ */
    /*  Public API                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Classify the full resume quality and score each section.
     *
     * @return array{
     *   tier: string,
     *   quality_score: int,
     *   section_scores: array,
     *   locked_sections: array,
     *   preservation_mode: bool,
     *   weak_sections: array,
     *   quality_signals: array
     * }
     */
    public function detect(array $parsedResume): array
    {
        $webhookUrl = config('services.n8n.webhook_url');
        $webhookKey = config('services.resume_intelligence.api_key');

        if (!empty($webhookUrl)) {
            try {
                \Illuminate\Support\Facades\Log::info('ResumeQuality: Dispatching to AI Section Analyzer');
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-Resume-Intelligence-Key' => $webhookKey,
                    'Content-Type'              => 'application/json',
                ])->timeout(60)->post($webhookUrl, [
                    'system_prompt' => $this->buildAiSystemPrompt(),
                    'user_prompt'   => "Analyze the following resume sections:\n" . json_encode($parsedResume),
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if (($json['success'] ?? false) && !empty($json['data'])) {
                        $aiData = $this->safeJsonDecode($json['data']);
                        if ($aiData) {
                            \Illuminate\Support\Facades\Log::info('ResumeQuality: AI Analysis success');
                            return $this->parseAiQualityReport($aiData, $parsedResume);
                        }
                    }
                }
                \Illuminate\Support\Facades\Log::error('ResumeQuality: AI Analysis failure', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('ResumeQuality: AI Analysis exception', ['error' => $e->getMessage()]);
            }
        }

        \Illuminate\Support\Facades\Log::warning('ResumeQuality: Falling back to rule-based detection');
        return $this->ruleBasedDetect($parsedResume);
    }

    private function buildAiSystemPrompt(): string
    {
        return <<<PROMPT
You are a Senior ATS Intelligence Engineer, Resume Quality Analyst, Recruiter Psychology Specialist, and Production Resume Optimization Architect.

PRIMARY OBJECTIVE:
Analyze each section of the provided resume independently.
Score each section from 0-100 based on:
1. Recruiter Readability
2. Technical Depth
3. ATS Relevance
4. Quantified Impact
5. Clarity
6. Conciseness
7. Engineering Credibility
8. Keyword Alignment
9. Role Relevance

CRITICAL RULES:
- If a section score is >= 85, you MUST set "locked": true.
- Locked sections cannot be rewritten in the next pipeline step. They must preserve technical depth, architecture terminology, APIs, metrics, and engineering language.
- DO NOT simplify strong technical content.
- Preserve authenticity and natural engineering tone. The resume must feel human-written and recruiter-approved, NOT generic AI.

Return STRICT structured JSON ONLY. NO markdown, NO explanations.
Example format:
{
  "summary": { "score": 62, "locked": false, "reason": "Generic wording and weak recruiter targeting." },
  "experience": { "score": 94, "locked": true, "reason": "Strong quantified engineering bullets." },
  "projects": { "score": 87, "locked": true, "reason": "Good technical depth." },
  "skills": { "score": 90, "locked": true, "reason": "Well-categorized." }
}
PROMPT;
    }

    private function parseAiQualityReport(array $aiData, array $parsedResume): array
    {
        $sectionScores = [];
        $lockedSections = [];
        $weakSections = [];
        $totalScore = 0;
        $count = 0;

        foreach (['summary', 'skills', 'experience', 'projects', 'education', 'certifications'] as $key) {
            if (isset($aiData[$key])) {
                $score = (int) ($aiData[$key]['score'] ?? 0);
                $isLocked = filter_var($aiData[$key]['locked'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $sectionScores[$key] = $score;
                
                if ($isLocked || $score >= 85) {
                    $lockedSections[] = $key;
                }
                if ($score < 60) {
                    $weakSections[] = $key;
                }

                $totalScore += $score;
                $count++;
            } else {
                $sectionScores[$key] = 0;
            }
        }

        $qualityScore = $count > 0 ? (int) round($totalScore / $count) : 0;
        $tier = $this->classifyTier($qualityScore);
        $preservationMode = in_array($tier, ['elite', 'strong']);

        return [
            'tier'              => $tier,
            'quality_score'     => $qualityScore,
            'section_scores'    => $sectionScores,
            'locked_sections'   => $lockedSections,
            'preservation_mode' => $preservationMode,
            'weak_sections'     => $weakSections,
            'quality_signals'   => $this->collectQualitySignals(strtolower($parsedResume['raw_text'] ?? ''), $parsedResume),
        ];
    }

    public function ruleBasedDetect(array $parsedResume): array
    {
        $rawText = strtolower($parsedResume['raw_text'] ?? '');

        // ── Score each section individually ───────────────────────────
        $sectionScores = [
            'summary'        => $this->scoreSummary($parsedResume),
            'skills'         => $this->scoreSkillsSection($parsedResume),
            'experience'     => $this->scoreExperienceSection($parsedResume),
            'projects'       => $this->scoreProjectsSection($parsedResume),
            'education'      => $this->scoreEducationSection($parsedResume),
            'certifications' => $this->scoreCertificationsSection($parsedResume),
        ];

        // ── Collect quality signals ────────────────────────────────────
        $qualitySignals = $this->collectQualitySignals($rawText, $parsedResume);

        // ── Compute overall quality score ─────────────────────────────
        $qualityScore = $this->computeOverallQuality($sectionScores, $qualitySignals, $rawText);

        // ── Classify tier ─────────────────────────────────────────────
        $tier = $this->classifyTier($qualityScore);

        // ── Determine locked sections (score ≥ 85 → LOCKED) ──────────
        $lockedSections = array_keys(array_filter($sectionScores, fn($s) => $s >= 85));

        // ── Determine weak sections (score < 60) ─────────────────────
        $weakSections = array_keys(array_filter($sectionScores, fn($s) => $s < 60));

        $preservationMode = in_array($tier, ['elite', 'strong']);

        return [
            'tier'              => $tier,
            'quality_score'     => $qualityScore,
            'section_scores'    => $sectionScores,
            'locked_sections'   => $lockedSections,
            'preservation_mode' => $preservationMode,
            'weak_sections'     => $weakSections,
            'quality_signals'   => $qualitySignals,
        ];
    }
    /* ------------------------------------------------------------------ */
    /*  Section Scorers                                                     */
    /* ------------------------------------------------------------------ */

    private function scoreSummary(array $parsed): int
    {
        $summary = trim($parsed['summary'] ?? '');
        if (empty($summary)) return 0;

        $score = 20; // base for having a summary
        $lower = strtolower($summary);

        // Length check
        $wc = str_word_count($summary);
        if ($wc >= 30 && $wc <= 80) $score += 15;
        elseif ($wc >= 15) $score += 8;

        // Role specificity
        if (preg_match('/\b(developer|engineer|architect|specialist|expert)\b/i', $summary)) $score += 10;
        // Tech mentions
        if (preg_match('/\b(api|backend|frontend|full.?stack|cloud|devops|machine learning|data)\b/i', $summary)) $score += 15;
        // Confidence language (not generic)
        if (preg_match('/\b(years of experience|proven|delivered|built|architected|specializing)\b/i', $summary)) $score += 15;
        // Not generic filler
        if (!preg_match('/\b(hardworking|passionate|team player|quick learner|motivated individual)\b/i', $summary)) $score += 10;
        // Has a specific technology mentioned
        if (preg_match('/\b(laravel|react|node|python|java|php|django|spring|aws|docker)\b/i', $summary)) $score += 15;

        return min(100, $score);
    }

    private function scoreSkillsSection(array $parsed): int
    {
        $skills = $parsed['skills'] ?? [];
        if (empty($skills)) return 0;

        $score  = 15; // base for having skills
        $count  = count($skills);

        if ($count >= 15) $score += 30;
        elseif ($count >= 8) $score += 20;
        elseif ($count >= 4) $score += 10;

        // Grouped format check (Languages/Frameworks/Tools/Databases)
        $skillStr = is_array($skills) ? implode(' ', $skills) : (string)$skills;
        if (preg_match('/(Languages|Frameworks|Tools|Databases|Cloud|DevOps)\s*:/i', $skillStr)) {
            $score += 25;
        }

        // Has backend tools
        $backendTools = ['rest api','jwt','docker','redis','aws','postgresql','mysql','mongodb','rabbitmq','elasticsearch'];
        foreach ($backendTools as $t) {
            if (stripos($skillStr, $t) !== false) { $score += 5; break; }
        }

        // Has frontend tools
        $frontendTools = ['react','vue','angular','typescript','next.js','tailwind'];
        foreach ($frontendTools as $t) {
            if (stripos($skillStr, $t) !== false) { $score += 5; break; }
        }

        // Breadth bonus
        $techCategories = [
            ['php','python','java','javascript','typescript','c++'],
            ['laravel','django','react','vue','express','spring'],
            ['mysql','postgresql','mongodb','redis'],
            ['docker','git','aws','linux','nginx'],
        ];
        $categoriesHit = 0;
        foreach ($techCategories as $cat) {
            foreach ($cat as $tech) {
                if (stripos($skillStr, $tech) !== false) { $categoriesHit++; break; }
            }
        }
        $score += $categoriesHit * 5;

        return min(100, $score);
    }

    private function scoreExperienceSection(array $parsed): int
    {
        $experience = $parsed['experience'] ?? [];
        if (empty($experience)) return 0;

        $score = 15; // base for having experience

        // Number of roles
        $roles = count($experience);
        if ($roles >= 2) $score += 15;
        elseif ($roles >= 1) $score += 8;

        $allBullets = [];
        $hasDate = false;
        $hasOrg  = false;

        foreach ($experience as $exp) {
            if (!empty($exp['date']))  $hasDate = true;
            if (!empty($exp['org']))   $hasOrg  = true;
            foreach ($exp['bullets'] ?? [] as $b) {
                $allBullets[] = strtolower($b);
            }
        }

        if ($hasDate) $score += 10;
        if ($hasOrg)  $score += 5;

        // Bullet quality analysis
        $strongBulletCount = 0;
        $metricsCount      = 0;
        $techDepthCount    = 0;

        foreach ($allBullets as $bullet) {
            // Strong verbs
            foreach (self::STRONG_VERBS as $verb) {
                if (str_starts_with($bullet, $verb)) { $strongBulletCount++; break; }
            }
            // Quantified metrics
            if (preg_match('/\d+\s*(%|users|requests|ms\b|endpoints|apis|clients|concurrent|k\b|x\b)/i', $bullet)) {
                $metricsCount++;
            }
            // Technical depth signals
            if (preg_match('/\b(api|endpoint|database|authentication|optimization|latency|performance|architecture|microservice|cache|jwt|oauth|rest|graphql)\b/i', $bullet)) {
                $techDepthCount++;
            }
        }

        $totalBullets = max(1, count($allBullets));
        $strongRatio  = $strongBulletCount / $totalBullets;
        $metricsRatio = $metricsCount / $totalBullets;

        // Strong verbs ratio
        if ($strongRatio >= 0.8) $score += 20;
        elseif ($strongRatio >= 0.5) $score += 12;
        elseif ($strongRatio >= 0.3) $score += 5;

        // Metrics
        if ($metricsCount >= 3) $score += 20;
        elseif ($metricsCount >= 1) $score += 10;

        // Tech depth
        if ($techDepthCount >= 4) $score += 15;
        elseif ($techDepthCount >= 2) $score += 8;

        return min(100, $score);
    }

    private function scoreProjectsSection(array $parsed): int
    {
        $projects = $parsed['projects'] ?? [];
        if (empty($projects)) return 20; // not critical if has strong experience

        $score = 15; // base for having projects

        if (count($projects) >= 3) $score += 15;
        elseif (count($projects) >= 2) $score += 10;
        elseif (count($projects) >= 1) $score += 5;

        $allBullets = [];
        foreach ($projects as $proj) {
            if (!empty($proj['tech'])) $score += 10; // has tech stack
            foreach ($proj['bullets'] ?? [] as $b) $allBullets[] = strtolower($b);
        }

        foreach ($allBullets as $bullet) {
            if (preg_match('/\d+\s*(%|users|requests|ms\b|endpoints|apis|clients)/i', $bullet)) {
                $score += 10; break;
            }
        }

        foreach ($allBullets as $bullet) {
            foreach (self::STRONG_VERBS as $verb) {
                if (str_starts_with($bullet, $verb)) { $score += 10; break 2; }
            }
        }

        return min(100, $score);
    }

    private function scoreEducationSection(array $parsed): int
    {
        $education = $parsed['education'] ?? [];
        if (empty($education)) return 30; // not penalised heavily
        $score = 50;
        foreach ($education as $edu) {
            if (!empty($edu['school'])) $score += 15;
            if (!empty($edu['degree'])) $score += 15;
            if (!empty($edu['year']))   $score += 10;
            if (!empty($edu['meta']) && preg_match('/\d\.\d|\d+%/i', $edu['meta'])) $score += 10;
        }
        return min(100, $score);
    }

    private function scoreCertificationsSection(array $parsed): int
    {
        $certs = $parsed['certifications'] ?? [];
        if (empty($certs)) return 50; // optional section
        return min(100, 60 + count($certs) * 10);
    }

    /* ------------------------------------------------------------------ */
    /*  Quality Signals                                                     */
    /* ------------------------------------------------------------------ */

    private function collectQualitySignals(string $lowerText, array $parsed): array
    {
        $signals = [];

        // Architecture signals
        foreach (self::ELITE_SIGNALS['architecture'] as $sig) {
            if (str_contains($lowerText, $sig)) { $signals['has_architecture'] = true; break; }
        }
        // Impact/metrics signals
        $metricsMatches = preg_match_all('/\d+\s*(%|users|requests|ms\b|endpoints|apis|concurrent|k\b|x\b)/i', $lowerText, $m);
        $signals['metrics_count'] = $metricsMatches;
        $signals['has_metrics']   = $metricsMatches >= 2;

        // Scale signals
        foreach (self::ELITE_SIGNALS['scale'] as $sig) {
            if (str_contains($lowerText, $sig)) { $signals['has_scale_language'] = true; break; }
        }
        // Tech depth signals
        $techDepthHits = 0;
        foreach (self::ELITE_SIGNALS['tech_depth'] as $sig) {
            if (str_contains($lowerText, $sig)) $techDepthHits++;
        }
        $signals['tech_depth_hits'] = $techDepthHits;
        $signals['has_tech_depth']  = $techDepthHits >= 3;

        // Strong verbs count
        $strongVerbCount = 0;
        foreach (self::STRONG_VERBS as $v) {
            if (str_contains($lowerText, $v)) $strongVerbCount++;
        }
        $signals['strong_verb_count'] = $strongVerbCount;
        $signals['has_strong_verbs']  = $strongVerbCount >= 5;

        // Generic filler red flags
        $fillerPhrases = ['hardworking','passionate about','team player','quick learner','enthusiastic','go-getter'];
        $fillerCount = 0;
        foreach ($fillerPhrases as $f) {
            if (str_contains($lowerText, $f)) $fillerCount++;
        }
        $signals['filler_count'] = $fillerCount;
        $signals['is_generic']   = $fillerCount >= 2;

        // Word count
        $signals['word_count'] = str_word_count($lowerText);

        return $signals;
    }

    /* ------------------------------------------------------------------ */
    /*  Overall Quality Score                                               */
    /* ------------------------------------------------------------------ */

    private function computeOverallQuality(array $sectionScores, array $signals, string $lowerText): int
    {
        // Weighted section average (experience matters most)
        $weightedAvg = (
            ($sectionScores['summary']    * 0.15) +
            ($sectionScores['skills']     * 0.20) +
            ($sectionScores['experience'] * 0.35) +
            ($sectionScores['projects']   * 0.20) +
            ($sectionScores['education']  * 0.10)
        );

        $score = (int) round($weightedAvg);

        // Elite signal bonuses
        if ($signals['has_metrics'] ?? false)        $score += 8;
        if ($signals['has_architecture'] ?? false)   $score += 5;
        if ($signals['has_tech_depth'] ?? false)     $score += 5;
        if ($signals['has_strong_verbs'] ?? false)   $score += 4;
        if ($signals['has_scale_language'] ?? false) $score += 3;

        // Filler penalties
        $filler = $signals['filler_count'] ?? 0;
        if ($filler >= 3) $score -= 10;
        elseif ($filler >= 1) $score -= 4;

        return min(100, max(0, $score));
    }

    private function classifyTier(int $score): string
    {
        if ($score >= 80) return 'elite';
        if ($score >= 60) return 'strong';
        if ($score >= 40) return 'average';
        return 'weak';
    }
}
