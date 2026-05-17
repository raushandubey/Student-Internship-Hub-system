<?php

namespace App\Services\Resume;

use Illuminate\Support\Facades\Log;

/**
 * AiRewriteEngine — Preservation + Enhancement Engine (Rebuilt).
 *
 * CRITICAL RULES:
 *  - NEVER rewrite locked sections (quality ≥ 85)
 *  - NEVER downgrade strong bullets
 *  - ONLY enhance weak sections
 *  - Preservation mode for elite/strong resumes
 */
class AiRewriteEngine
{
    use \App\Traits\ResilientJsonTrait;
    private const MAX_TOKENS = 2400;

    private const WEAK_VERB_MAP = [
        'worked on'       => 'Developed',
        'helped with'     => 'Supported',
        'assisted in'     => 'Collaborated on',
        'was responsible' => 'Owned',
        'did some'        => 'Implemented',
        'participated in' => 'Contributed to',
        'involved in'     => 'Delivered',
        'part of a team'  => 'Collaborated to',
        'responsible for' => 'Managed',
        'tasked with'     => 'Executed',
    ];

    public function __construct(
        private ?AiProviderGateway $aiGateway = null,
        private ?ResumeOptimizationQualityGate $qualityGate = null,
    ) {}

    /* ------------------------------------------------------------------ */
    /*  Public API                                                          */
    /* ------------------------------------------------------------------ */

    public function rewrite(array $parsedResume, array $jdAnalysis, array $weaknessReport, array $qualityReport = [], bool $strongerPrompt = false): array
    {
        $tier             = $qualityReport['tier'] ?? 'average';

        $systemPrompt = $this->buildSystemPrompt($tier);
        $userPrompt   = $this->buildSelectivePrompt($parsedResume, $jdAnalysis, $weaknessReport, $qualityReport);

        if ($strongerPrompt) {
            $userPrompt .= "\n\n━━━ RETRY QUALITY REQUIREMENT ━━━\n"
                . "The previous rewrite was too weak. Rewrite every eligible weak bullet and summary more materially, "
                . "add missing required ATS keywords naturally, and ensure the optimized sections are measurably different while preserving facts.";
        }

        $gatewayResult = $this->gateway()->complete('resume_rewrite', $systemPrompt, $userPrompt, [
            'tier' => $tier,
            'retry' => $strongerPrompt,
        ]);

        if (!($gatewayResult['success'] ?? false)) {
            $fallback = $this->structuredRuleBasedRewrite(
                $parsedResume,
                $jdAnalysis,
                $weaknessReport,
                $qualityReport,
                'ai_providers_unavailable'
            );

            if ($fallback !== null) {
                return $fallback + ['attempts' => $gatewayResult['attempts'] ?? []];
            }

            return [
                'success' => false,
                'error' => $gatewayResult['error'] ?? 'AI providers unavailable.',
                'stage_failed' => 'AI_UNAVAILABLE',
                'attempts' => $gatewayResult['attempts'] ?? [],
                'ai_used' => false,
            ];
        }

        $resultText = $this->normalizeAiOutput($gatewayResult['content'] ?? '', $parsedResume, $gatewayResult);

        if ($resultText === null) {
            $fallback = $this->structuredRuleBasedRewrite(
                $parsedResume,
                $jdAnalysis,
                $weaknessReport,
                $qualityReport,
                'ai_output_invalid'
            );

            if ($fallback !== null) {
                return $fallback + ['attempts' => $gatewayResult['attempts'] ?? []];
            }

            return [
                'success' => false,
                'error' => 'AI provider returned invalid or unusable rewrite JSON.',
                'stage_failed' => 'AI_OUTPUT_INVALID',
                'attempts' => $gatewayResult['attempts'] ?? [],
                'ai_used' => true,
                'engine' => $gatewayResult['provider'] ?? 'unknown',
            ];
        }

        return [
            'success'        => true, 
            'rewritten_text' => $resultText, 
            'ai_used'        => true, 
            'engine'         => $gatewayResult['provider'] ?? 'unknown',
            'provider'       => $gatewayResult['provider'] ?? 'unknown',
            'model'          => $gatewayResult['model'] ?? null,
            'mode'           => $strongerPrompt ? 'targeted_retry' : 'targeted',
            'tokens'         => $gatewayResult['tokens'] ?? [],
            'attempts'       => $gatewayResult['attempts'] ?? [],
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Keyword-Only Enhancement (Elite Resumes)                            */
    /* ------------------------------------------------------------------ */

    private function normalizeAiOutput(string $content, array $parsedResume, array $gatewayResult): ?string
    {
        $aiData = $this->safeJsonDecode($content);

        if (!$aiData) {
            return null;
        }

        $finalResume = $parsedResume;

        if (isset($aiData['optimized_sections']) && is_array($aiData['optimized_sections'])) {
            foreach (['summary', 'skills', 'experience', 'projects', 'education', 'certifications'] as $key) {
                $section = $aiData['optimized_sections'][$key] ?? null;

                if (!is_array($section)) {
                    continue;
                }

                if (($section['optimized'] ?? false) && array_key_exists('content', $section)) {
                    $finalResume[$key] = $section['content'];
                }
            }
        } else {
            $hasResumeSections = false;
            foreach (['summary', 'skills', 'experience', 'projects', 'education', 'certifications'] as $key) {
                if (array_key_exists($key, $aiData)) {
                    $finalResume[$key] = $aiData[$key];
                    $hasResumeSections = true;
                }
            }

            if (!$hasResumeSections) {
                return null;
            }
        }

        $finalResume['raw_text'] = $this->qualityGate()->canonicalRawText($finalResume);
        $finalResume['optimization_meta'] = [
            'ai_used' => true,
            'provider' => $gatewayResult['provider'] ?? 'unknown',
            'model' => $gatewayResult['model'] ?? null,
            'generated_at' => now()->toISOString(),
        ];

        return json_encode($finalResume);
    }

    private function gateway(): AiProviderGateway
    {
        return $this->aiGateway ??= app(AiProviderGateway::class);
    }

    private function qualityGate(): ResumeOptimizationQualityGate
    {
        return $this->qualityGate ??= app(ResumeOptimizationQualityGate::class);
    }

    private function keywordOnlyEnhancement(array $parsed, array $jd, array $weakness): string
    {
        $raw           = $parsed['raw_text'] ?? '';
        $missingSkills = array_slice($weakness['missing_skills'] ?? [], 0, 4);

        if (empty($missingSkills) || empty($raw)) return $raw;

        $additions = implode(', ', $missingSkills);

        // Strategy 1: append to the first grouped skills line ("Languages: ...", "Frameworks: ..." etc.)
        $modified = preg_replace_callback(
            '/^(Languages|Frameworks|Tools|Core Skills|Technical Skills|Skills)[\s:][^\n]*/im',
            fn($m) => rtrim($m[0], ' ,') . ', ' . $additions,
            $raw,
            1,
            $count
        );

        // Strategy 2: no grouped line found — find the skills section and append after it
        if ($count === 0) {
            $modified = preg_replace_callback(
                '/^(TECHNICAL SKILLS|CORE SKILLS|SKILLS)[^\n]*\n([^\n]+)/im',
                fn($m) => $m[0] . ', ' . $additions,
                $raw,
                1,
                $count
            );
        }

        // Strategy 3: just append at the top-level skills line
        if ($count === 0) {
            $modified = $raw . "\n" . $additions;
        }

        return $modified ?? $raw;
    }



    /* ------------------------------------------------------------------ */
    /*  System Prompt — Tier-Aware                                          */
    /* ------------------------------------------------------------------ */

    private function buildSystemPrompt(string $tier): string
    {
        $preservationRule = match($tier) {
            'elite'  => 'This is an ELITE resume. You are ONLY allowed to add missing keywords to skill groups. DO NOT rewrite ANY bullets, summary, or experience entries. They are locked.',
            'strong' => 'This is a STRONG resume. You may ONLY rewrite sections explicitly marked [WEAK]. All other sections marked [LOCKED] must be copied EXACTLY as provided — character for character.',
            default  => 'Rewrite ONLY the sections marked [WEAK]. Copy all sections marked [LOCKED] exactly as provided.',
        };

        return "You are a Senior Recruiter Intelligence Engineer, ATS Resume Optimization Specialist, and Resume Bullet Architect producing FAANG-quality resumes.
{$preservationRule}

CRITICAL RULES:
1. ALIGN THE CANDIDATE. DO NOT REINVENT THE CANDIDATE. Preserve their original technical identity, actual experience, and domain expertise.
2. NEVER transform the candidate into a different profession (e.g., do NOT turn a Backend Developer into a UX Expert).
3. NEVER invent experience, technologies, seniority, or leadership claims.
4. Surface transferable skills naturally. If the job matches weakly, preserve the original domain identity and only lightly align terminology.
5. Emphasize relevant aspects (e.g., API design for backend, UI collaboration for frontend) ONLY if supported by the original resume.
6. The resume must feel believable, professional, and technically authentic. NEVER use exaggerated or keyword-stuffed corporate language.

WORLD-CLASS BULLET ENGINE RULES:
- Every rewritten bullet MUST follow this exact structure: [ACTION VERB] + [TECHNOLOGY / TOOL / FRAMEWORK] + [TECHNICAL OR BUSINESS IMPACT].
- STRICTLY FORBIDDEN weak verbs: 'worked on', 'helped with', 'responsible for', 'participated in', 'involved in', 'handled tasks related to'.
- Max 2 lines per bullet. Keep them concise, technical, and ATS-friendly.
- Preserve ALL original quantified impact (percentages, performance metrics, scaling improvements). NEVER remove numbers.
- Maintain deep technical architecture, backend/frontend/auth systems, and deployment terminology. NEVER oversimplify.

Output ONLY structured JSON. No commentary, no markdown wrapping.";
    }

    /* ------------------------------------------------------------------ */
    /*  Selective Prompt Builder — Locks Strong Sections                    */
    /* ------------------------------------------------------------------ */

    private function buildSelectivePrompt(array $parsed, array $jd, array $weakness, array $quality): string
    {
        $lockedSections   = $quality['locked_sections'] ?? [];
        $sectionScores    = $quality['section_scores'] ?? [];
        $tier             = $quality['tier'] ?? 'average';

        $jobTitle        = $jd['job_title'] ?? '';
        $org             = $jd['organization'] ?? '';
        $requiredSkills  = implode(', ', $jd['required_skills'] ?? []);
        $missingSkills   = implode(', ', array_slice($weakness['missing_skills'] ?? [], 0, 5));
        $descSnip        = substr($jd['description_raw'] ?? '', 0, 400);
        $recruiterFocus  = implode(', ', $jd['recruiter_terms'] ?? []);
        
        $semanticClusters = '';
        if (!empty($jd['semantic_clusters'])) {
            $semanticClusters = "Semantic Clusters:\n";
            foreach ($jd['semantic_clusters'] as $clusterName => $clusterSkills) {
                $semanticClusters .= "- {$clusterName}: " . implode(', ', $clusterSkills) . "\n";
            }
        }

        // Build section blocks with LOCKED vs WEAK labels
        $summaryScore  = $sectionScores['summary'] ?? 0;
        $expScore      = $sectionScores['experience'] ?? 0;
        $projScore     = $sectionScores['projects'] ?? 0;

        $summaryBlock  = $this->buildSectionBlock('PROFESSIONAL SUMMARY', $parsed['summary'] ?? '', $summaryScore, $lockedSections, 'summary');
        $expBlock      = $this->buildExperienceBlock($parsed['experience'] ?? [], $expScore, $lockedSections, $jd);
        $projBlock     = $this->buildProjectsBlock($parsed['projects'] ?? [], $projScore, $lockedSections);
        $skillsBlock   = $this->buildSkillsBlock($parsed, $weakness, $jd);

        $edu = $this->buildSimpleSection('EDUCATION', $parsed['education'] ?? [], $parsed);
        $cert = !empty($parsed['certifications']) ? "CERTIFICATIONS\n" . implode("\n", array_map(fn($c) => "• $c", $parsed['certifications'])) : '';

        $name    = strtoupper($parsed['name'] ?? 'CANDIDATE');
        $contact = $parsed['contact'] ?? trim(($parsed['email'] ?? '') . (!empty($parsed['phone']) ? ' | ' . $parsed['phone'] : ''));

        $tierInstruction = match($tier) {
            'elite'  => "This is an ELITE resume. Preserve all strong bullets verbatim. Only add missing ATS keywords to skills and lightly adjust weak summary text if it exists.",
            'strong' => "This is a STRONG resume. ONLY rewrite sections marked [WEAK — REWRITE]. Copy [LOCKED] sections VERBATIM.",
            'average'=> "This is an AVERAGE resume. Rewrite sections marked [WEAK] with stronger language. Keep [LOCKED] sections exactly.",
            'weak'   => "This is a WEAK resume. Improve all [WEAK] sections significantly. Keep any [LOCKED] sections exactly.",
            default  => "Rewrite [WEAK] sections only. Copy [LOCKED] sections verbatim.",
        };

        return <<<PROMPT
━━━ INSTRUCTION ━━━
{$tierInstruction}

━━━ TARGET JOB ━━━
Title: {$jobTitle}
Organization: {$org}
Required Skills: {$requiredSkills}
Recruiter Focus: {$recruiterFocus}
{$semanticClusters}
JD Excerpt: {$descSnip}

━━━ MISSING SKILLS TO ADD ━━━
{$missingSkills}

━━━ RESUME SECTIONS ━━━

{$name}
{$contact}

{$summaryBlock}

{$skillsBlock}

{$expBlock}

{$projBlock}

{$edu}

{$cert}

━━━ OUTPUT FORMAT RULES ━━━
1. DO NOT output [LOCKED] sections. Instead, set "optimized": false for them.
2. [WEAK] sections: rewrite bullets with ACTION VERB + TECHNOLOGY + MEASURABLE IMPACT. Set "optimized": true and provide the rewritten array/string in "content".
3. NO fabricated metrics, companies, or dates.
4. Add missing skills ({$missingSkills}) to skills groups naturally.
5. RETURN ONLY VALID JSON matching this structure exactly:
{
  "optimized_sections": {
    "summary": {
      "optimized": true,
      "content": "Professional Summary"
    },
    "skills": {
      "optimized": true,
      "content": ["Category: skill1, skill2", "Category: skill3"]
    },
    "experience": {
      "optimized": false,
      "reason": "Section locked due to strong engineering quality."
    },
    "projects": {
      "optimized": true,
      "content": [
        {
          "title": "Project Name",
          "tech": "Tech Stack",
          "date": "Dates",
          "bullets": [
            "Action verb + tech + impact bullet 1.",
            "Action verb + tech + impact bullet 2."
          ]
        }
      ]
    }
  }
}
6. OUTPUT ONLY structured JSON — no comments, no markdown wrapping.

OUTPUT:
PROMPT;
    }

    /* ------------------------------------------------------------------ */
    /*  Section Block Builders                                              */
    /* ------------------------------------------------------------------ */

    private function buildSectionBlock(string $header, string $content, int $score, array $locked, string $key): string
    {
        $isLocked = in_array($key, $locked) || $score >= 85;
        $label    = $isLocked ? '[LOCKED — COPY VERBATIM]' : '[WEAK — REWRITE]';
        if (empty(trim($content))) {
            return "{$header}\n[WEAK — GENERATE NEW SECTION]";
        }
        return "{$header} {$label}\n{$content}";
    }

    private function buildExperienceBlock(array $experience, int $sectionScore, array $locked, array $jd): string
    {
        if (empty($experience)) return "EXPERIENCE\n[WEAK — ADD EXPERIENCE SECTION]";

        $lines = "EXPERIENCE\n";
        foreach ($experience as $exp) {
            $header = implode(' | ', array_filter([
                $exp['org'] ?? '', $exp['title'] ?? '', $exp['date'] ?? '', $exp['location'] ?? ''
            ]));
            $lines .= "{$header}\n";

            foreach ($exp['bullets'] ?? [] as $bullet) {
                $bulletScore = $this->scoreBulletQuality($bullet);
                // Lock individual strong bullets
                if ($bulletScore >= 80) {
                    $lines .= "[LOCKED] • {$bullet}\n";
                } else {
                    $lines .= "[WEAK] • {$bullet}\n";
                }
            }
            $lines .= "\n";
        }
        return $lines;
    }

    private function buildProjectsBlock(array $projects, int $sectionScore, array $locked): string
    {
        if (empty($projects)) return "PROJECTS\n[WEAK — ADD PROJECT SECTION]";

        $lines = "PROJECTS\n";
        foreach ($projects as $proj) {
            $header = $proj['title'] ?? '';
            if (!empty($proj['tech'])) $header .= ' | ' . $proj['tech'];
            $lines .= "{$header}\n";
            foreach ($proj['bullets'] ?? [] as $bullet) {
                $score = $this->scoreBulletQuality($bullet);
                $label = $score >= 75 ? '[LOCKED]' : '[WEAK]';
                $lines .= "{$label} • {$bullet}\n";
            }
            $lines .= "\n";
        }
        return $lines;
    }

    private function buildSkillsBlock(array $parsed, array $weakness, array $jd): string
    {
        $skills        = $parsed['skills'] ?? [];
        $missingSkills = array_slice($weakness['missing_skills'] ?? [], 0, 5);
        $allSkills     = array_unique(array_merge(
            is_array($skills) ? $skills : [],
            $missingSkills
        ));

        $grouped = $this->groupSkills($allSkills);
        return "TECHNICAL SKILLS [LOCKED — only add missing skills]\n{$grouped}";
    }

    private function buildSimpleSection(string $header, array $items, array $parsed): string
    {
        if (empty($items)) return '';
        $lines = "{$header}\n";
        foreach ($items as $item) {
            $parts = array_filter([$item['school'] ?? $item['degree'] ?? '', $item['degree'] ?? '', $item['year'] ?? '', $item['meta'] ?? '']);
            $lines .= implode(' | ', $parts) . "\n";
        }
        return $lines;
    }

    /* ------------------------------------------------------------------ */
    /*  Bullet Quality Scorer                                               */
    /* ------------------------------------------------------------------ */

    private function scoreBulletQuality(string $bullet): int
    {
        $score = 0;
        $lower = strtolower(trim($bullet));

        // Strong verb start
        $strongVerbs = ['architected','developed','built','implemented','designed','engineered','deployed','integrated','optimized','automated','delivered','reduced','improved','launched'];
        foreach ($strongVerbs as $v) {
            if (str_starts_with($lower, $v)) { $score += 30; break; }
        }

        // Quantified metric
        if (preg_match('/\d+\s*(%|users|requests|ms\b|endpoints|concurrent|clients|k\b|x\b)/i', $bullet)) {
            $score += 30;
        }

        // Technical depth
        if (preg_match('/\b(api|endpoint|database|authentication|cache|jwt|oauth|rest|performance|latency|architecture|microservice|docker|kubernetes|ci\/cd)\b/i', $bullet)) {
            $score += 25;
        }

        // Not weak
        $weakPhrases = ['worked on', 'helped', 'was responsible', 'assisted', 'involved in', 'participated'];
        foreach ($weakPhrases as $w) {
            if (str_contains($lower, $w)) { $score -= 25; break; }
        }

        // Length check
        $wc = str_word_count($bullet);
        if ($wc >= 10 && $wc <= 25) $score += 15;
        elseif ($wc < 5) $score -= 15;

        return max(0, min(100, $score));
    }

    /* ------------------------------------------------------------------ */
    /*  Structured Rule-Based Fallback (no AI)                              */
    /* ------------------------------------------------------------------ */

    public function structuredRuleBasedRewrite(
        array $parsed,
        array $jd,
        array $weakness,
        array $qualityReport = [],
        string $reason = 'ai_providers_unavailable'
    ): ?array {
        $lockedSections   = $qualityReport['locked_sections'] ?? [];
        $preservationMode = (bool) ($qualityReport['preservation_mode'] ?? false);
        $tier             = $qualityReport['tier'] ?? 'average';

        if ($tier === 'elite') {
            $preservationMode = true;
        }

        Log::warning('[RULE_ENGINE_USED]', [
            'reason' => $reason,
            'preservation_mode' => $preservationMode,
            'locked_sections' => $lockedSections,
        ]);

        $optimized = $this->prepareParsedForRuleFallback($parsed, $jd, $weakness);
        if ($optimized === null) {
            return null;
        }

        $skills = is_array($optimized['skills'] ?? null) ? $optimized['skills'] : [];
        $missing = array_slice($weakness['missing_skills'] ?? [], 0, 5);
        $optimized['skills'] = array_values(array_unique(array_merge($skills, $missing)));

        $summaryLocked = in_array('summary', $lockedSections, true);
        $shouldRewriteSummary = !$summaryLocked && (
            ($weakness['summary_weak'] ?? false)
            || trim((string) ($parsed['summary'] ?? '')) === ''
        );

        if ($shouldRewriteSummary) {
            $jobTitle  = $jd['job_title'] ?? 'the position';
            $org       = $jd['organization'] ?? '';
            $topSkills = implode(', ', array_slice($jd['required_skills'] ?? [], 0, 3));
            $optimized['summary'] = "Results-driven software engineer targeting the {$jobTitle} role"
                . ($org ? " at {$org}" : '') . '. '
                . ($topSkills ? "Skilled in {$topSkills}. " : '')
                . 'Experienced in building scalable, production-grade systems with measurable impact.';
        }

        if (!in_array('experience', $lockedSections, true) && !empty($optimized['experience']) && is_array($optimized['experience'])) {
            foreach ($optimized['experience'] as $i => $exp) {
                if (!is_array($exp) || empty($exp['bullets']) || !is_array($exp['bullets'])) {
                    continue;
                }

                foreach ($exp['bullets'] as $j => $bullet) {
                    if (!is_string($bullet) || trim($bullet) === '') {
                        continue;
                    }

                    $bulletScore = $this->scoreBulletQuality($bullet);
                    if ($preservationMode || $bulletScore >= 70) {
                        continue;
                    }

                    $optimized['experience'][$i]['bullets'][$j] = $this->enhanceBullet($bullet);
                }
            }
        }

        if (!in_array('projects', $lockedSections, true) && !empty($optimized['projects']) && is_array($optimized['projects'])) {
            foreach ($optimized['projects'] as $i => $proj) {
                if (!is_array($proj) || empty($proj['bullets']) || !is_array($proj['bullets'])) {
                    continue;
                }

                foreach ($proj['bullets'] as $j => $bullet) {
                    if (!is_string($bullet) || trim($bullet) === '') {
                        continue;
                    }

                    $bulletScore = $this->scoreBulletQuality($bullet);
                    if ($preservationMode || $bulletScore >= 70) {
                        continue;
                    }

                    $optimized['projects'][$i]['bullets'][$j] = $this->enhanceBullet($bullet);
                }
            }
        }

        $optimized['raw_text'] = $this->qualityGate()->canonicalRawText($optimized);
        $optimized['optimization_meta'] = [
            'ai_used' => false,
            'provider' => 'rule_engine',
            'model' => null,
            'generated_at' => now()->toISOString(),
            'fallback_reason' => $reason,
        ];

        $optimized = $this->ensureRuleFallbackStructure($optimized, $jd, $weakness);

        $encoded = json_encode($optimized);
        if ($encoded === false) {
            Log::error('[RULE_ENGINE_FAIL]', ['reason' => 'json_encode_failed']);

            return null;
        }

        $issues = $this->validateOutput($encoded);
        if (!empty($issues)) {
            Log::warning('[RULE_ENGINE_VALIDATION]', ['issues' => $issues]);
            $optimized = $this->ensureRuleFallbackStructure($optimized, $jd, $weakness);
            $encoded = json_encode($optimized);
            $issues = $encoded !== false ? $this->validateOutput($encoded) : ['json_encode_failed'];
        }

        if ($encoded === false || !empty($issues)) {
            Log::error('[RULE_ENGINE_FAIL]', ['issues' => $issues]);

            return null;
        }

        return [
            'success'        => true,
            'rewritten_text' => $encoded,
            'ai_used'        => false,
            'engine'         => 'rule_engine',
            'provider'       => 'rule_engine',
            'model'          => null,
            'mode'           => 'rule_based',
            'tokens'         => [],
        ];
    }

    private function prepareParsedForRuleFallback(array $parsed, array $jd, array $weakness): ?array
    {
        $optimized = json_decode(json_encode($parsed), true);

        return is_array($optimized)
            ? $this->ensureRuleFallbackStructure($optimized, $jd, $weakness)
            : null;
    }

    private function ensureRuleFallbackStructure(array $parsed, array $jd, array $weakness): array
    {
        $skills = $parsed['skills'] ?? [];
        if (is_string($skills)) {
            $skills = array_filter(array_map('trim', preg_split('/[,|•]+/', $skills)));
        }
        if (!is_array($skills)) {
            $skills = [];
        }

        $jdSkills = array_slice($jd['required_skills'] ?? $jd['keywords'] ?? [], 0, 8);
        $missing  = array_slice($weakness['missing_skills'] ?? [], 0, 5);
        $parsed['skills'] = array_values(array_unique(array_filter(array_merge($skills, $missing, $jdSkills))));

        if (empty($parsed['skills'])) {
            $parsed['skills'] = ['Communication', 'Problem Solving', 'Team Collaboration'];
        }

        if (trim((string) ($parsed['summary'] ?? '')) === '') {
            $raw = trim((string) ($parsed['raw_text'] ?? ''));
            if (strlen($raw) > 80) {
                $parsed['summary'] = substr(preg_replace('/\s+/', ' ', $raw), 0, 280);
            } else {
                $jobTitle = $jd['job_title'] ?? 'the role';
                $parsed['summary'] = "Motivated candidate targeting the {$jobTitle} position with relevant technical experience.";
            }
        }

        $hasBody = !empty($parsed['experience']) || !empty($parsed['projects']) || !empty($parsed['education']);
        if (!$hasBody) {
            $parsed = $this->hydrateBodyFromRawText($parsed);
        }

        foreach ($parsed['experience'] ?? [] as $i => $exp) {
            if (!is_array($exp)) {
                unset($parsed['experience'][$i]);
                continue;
            }

            $bullets = $exp['bullets'] ?? [];
            if (!is_array($bullets) || empty(array_filter($bullets, fn ($b) => is_string($b) && trim($b) !== ''))) {
                $parsed['experience'][$i]['bullets'] = [
                    'Delivered technical contributions aligned with role requirements and team objectives.',
                ];
            }
        }

        if (is_array($parsed['experience'] ?? null)) {
            $parsed['experience'] = array_values($parsed['experience']);
        }

        return $parsed;
    }

    private function hydrateBodyFromRawText(array $parsed): array
    {
        $raw = (string) ($parsed['raw_text'] ?? '');
        $bullets = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^[-•*]\s*(.+)/u', $line, $match) && strlen($match[1]) > 12) {
                $bullets[] = $match[1];
            }
        }

        $bullets = array_values(array_unique(array_slice($bullets, 0, 6)));

        if (!empty($bullets)) {
            $parsed['experience'] = [[
                'org'     => '',
                'title'   => 'Professional Experience',
                'date'    => '',
                'bullets' => $bullets,
            ]];

            return $parsed;
        }

        if (trim($raw) !== '') {
            $parsed['education'] = [[
                'degree' => 'Academic & Professional Background',
                'school' => substr(preg_replace('/\s+/', ' ', $raw), 0, 160),
                'year'   => '',
            ]];
        }

        return $parsed;
    }

    /* ------------------------------------------------------------------ */
    /*  Preservation-Aware Rule-Based Fallback                              */
    /* ------------------------------------------------------------------ */

    public function preservationAwareRuleRewrite(array $parsed, array $jd, array $weakness, array $lockedSections = [], bool $preservationMode = false): string
    {
        Log::warning('[RULE_ENGINE_USED]', [
            'reason' => 'manual_rule_rewrite_path_invoked',
            'preservation_mode' => $preservationMode,
            'locked_sections' => $lockedSections,
        ]);

        $name    = strtoupper($parsed['name'] ?? 'CANDIDATE');
        $contact = $parsed['contact'] ?? '';
        $skills  = $parsed['skills'] ?? [];
        $missing = array_slice($weakness['missing_skills'] ?? [], 0, 5);
        $allSkills = array_unique(array_merge(is_array($skills) ? $skills : [], $missing));
        $grouped = $this->groupSkills($allSkills);

        // Summary — preserve if locked, rewrite only if weak
        $summary = $parsed['summary'] ?? '';
        $summaryLocked = in_array('summary', $lockedSections);
        if (!$summaryLocked && ($weakness['summary_weak'] ?? false)) {
            $jobTitle = $jd['job_title'] ?? 'the position';
            $org      = $jd['organization'] ?? '';
            $topSkills = implode(', ', array_slice($jd['required_skills'] ?? [], 0, 3));
            $summary  = "Results-driven software engineer targeting the {$jobTitle} role"
                       . ($org ? " at {$org}" : '') . ". "
                       . ($topSkills ? "Skilled in {$topSkills}. " : '')
                       . "Experienced in building scalable, production-grade systems with measurable impact.";
        }

        // Experience — preserve locked bullets verbatim
        $expLines = $this->buildPreservingExperienceSection($parsed['experience'] ?? [], $jd['required_skills'] ?? [], $preservationMode);

        // Projects — preserve locked sections
        $projLocked = in_array('projects', $lockedSections);
        $projLines  = $projLocked
            ? $this->buildProjectsVerbatim($parsed['projects'] ?? [])
            : $this->buildProjectsSection($parsed['projects'] ?? []);

        $eduLines  = $this->buildEducationSection($parsed['education'] ?? []);
        $certLines = $this->buildCertSection($parsed['certifications'] ?? []);

        $resume  = "{$name}\n";
        $resume .= $contact ? "{$contact}\n" : '';
        if ($summary)  $resume .= "\nPROFESSIONAL SUMMARY\n{$summary}\n";
        $resume .= "\nTECHNICAL SKILLS\n{$grouped}\n";
        if ($expLines)  $resume .= "\nEXPERIENCE\n\n{$expLines}";
        if ($projLines) $resume .= "\nPROJECTS\n\n{$projLines}";
        if ($eduLines)  $resume .= "\nEDUCATION\n\n{$eduLines}";
        if ($certLines) $resume .= "\nCERTIFICATIONS\n\n{$certLines}";

        return trim($resume);
    }

    // Keep old name as alias for backward compatibility
    public function ruleBasedRewrite(array $parsed, array $jd, array $weakness): string
    {
        return $this->preservationAwareRuleRewrite($parsed, $jd, $weakness, [], false);
    }

    /* ------------------------------------------------------------------ */
    /*  Section Builders                                                    */
    /* ------------------------------------------------------------------ */

    private function buildPreservingExperienceSection(array $experience, array $jdSkills, bool $preservationMode): string
    {
        $lines = '';
        $maxBullets = $preservationMode ? 5 : 4;

        foreach ($experience as $exp) {
            $header = implode(' | ', array_filter([
                $exp['org'] ?? '', $exp['title'] ?? '', $exp['date'] ?? '', $exp['location'] ?? ''
            ]));
            $lines .= $header . "\n";

            foreach (array_slice($exp['bullets'] ?? [], 0, $maxBullets) as $bullet) {
                $bulletScore = $this->scoreBulletQuality($bullet);
                // Preserve strong bullets, only enhance weak ones
                if ($preservationMode || $bulletScore >= 70) {
                    $lines .= '- ' . $bullet . "\n";
                } else {
                    $lines .= '- ' . $this->enhanceBullet($bullet) . "\n";
                }
            }
            $lines .= "\n";
        }
        return $lines;
    }

    private function buildProjectsVerbatim(array $projects): string
    {
        $lines = '';
        foreach ($projects as $proj) {
            $header = $proj['title'] ?? '';
            if (!empty($proj['tech'])) $header .= ' | ' . $proj['tech'];
            $lines .= $header . "\n";
            foreach ($proj['bullets'] ?? [] as $bullet) {
                $lines .= '• ' . $bullet . "\n";
            }
            $lines .= "\n";
        }
        return $lines;
    }

    private function buildProjectsSection(array $projects): string
    {
        return $this->buildProjectsVerbatim($projects); // same for now — preserve project bullets
    }

    private function buildEducationSection(array $education): string
    {
        $lines = '';
        foreach ($education as $edu) {
            $parts = array_filter([$edu['school'] ?? $edu['degree'] ?? '', $edu['degree'] ?? '', $edu['year'] ?? '', $edu['meta'] ?? '']);
            $lines .= implode(' | ', $parts) . "\n";
        }
        return $lines;
    }

    private function buildCertSection(array $certs): string
    {
        $lines = '';
        foreach ($certs as $cert) {
            $lines .= '• ' . $cert . "\n";
        }
        return $lines;
    }

    private function groupSkills(array $skills): string
    {
        $langKw  = ['php','python','java','javascript','typescript','c++','c#','ruby','go','rust','swift','kotlin','html','css','sql','dart'];
        $fwKw    = ['laravel','django','flask','react','vue','angular','next','nuxt','express','spring','rails','fastapi','node','bootstrap','tailwind','jquery','redux','graphql'];
        $toolKw  = ['git','docker','kubernetes','jenkins','aws','azure','gcp','linux','nginx','postman','jira','figma','webpack','vite','terraform','github'];
        $dbKw    = ['mysql','postgresql','mongodb','redis','sqlite','oracle','elasticsearch','firebase','supabase'];

        $langs = $fws = $tools = $dbs = $other = [];
        foreach ($skills as $s) {
            $sl = strtolower($s);
            if (in_array($sl, $langKw))      $langs[]  = $s;
            elseif (in_array($sl, $fwKw))    $fws[]    = $s;
            elseif (in_array($sl, $toolKw))  $tools[]  = $s;
            elseif (in_array($sl, $dbKw))    $dbs[]    = $s;
            else                              $other[]  = $s;
        }

        $parts = [];
        if ($langs)  $parts[] = 'Languages: '  . implode(', ', array_slice($langs, 0, 7));
        if ($fws)    $parts[] = 'Frameworks: '  . implode(', ', array_slice($fws,   0, 7));
        if ($tools)  $parts[] = 'Tools: '       . implode(', ', array_slice($tools, 0, 6));
        if ($dbs)    $parts[] = 'Databases: '   . implode(', ', array_slice($dbs,   0, 5));
        if ($other && empty($parts)) $parts[] = 'Skills: ' . implode(', ', array_slice($other, 0, 15));

        return implode(' | ', $parts) ?: implode(', ', array_slice($skills, 0, 15));
    }

    private function enhanceBullet(string $bullet): string
    {
        $lower = strtolower($bullet);
        foreach (self::WEAK_VERB_MAP as $weak => $strong) {
            if (str_starts_with($lower, $weak)) {
                return $strong . ' ' . substr($bullet, strlen($weak));
            }
        }
        return $bullet;
    }

    /* ------------------------------------------------------------------ */
    /*  Validation & Sanitization                                           */
    /* ------------------------------------------------------------------ */

    public function validateOutput(string $text): array
    {
        $issues = [];
        $decoded = $this->safeJsonDecode($text);
        
        if (!$decoded) {
            return ["Invalid JSON format or corrupted AI response structure"];
        }

        foreach (['summary', 'skills'] as $section) {
            if (empty($decoded[$section])) {
                $issues[] = "Missing or empty section: {$section}";
            }
        }

        if (empty($decoded['experience']) && empty($decoded['projects']) && empty($decoded['education'])) {
            $issues[] = 'Missing resume body sections: expected experience, projects, or education';
        }

        if (!empty($decoded['experience']) && is_array($decoded['experience'])) {
            foreach ($decoded['experience'] as $i => $exp) {
                if (empty($exp['bullets']) || !is_array($exp['bullets'])) {
                    $issues[] = "Experience entry {$i} is missing bullets array";
                }
            }
        }

        return $issues;
    }

    public function sanitizeForStorage(string $text): string
    {
        $decoded = $this->safeJsonDecode($text);
        
        if (!$decoded) {
            $text = preg_replace('/%PDF-[\d.]+[\s\S]*?(?=PROFESSIONAL|SUMMARY|SKILLS|EXPERIENCE|\z)/i', '', $text);
            $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
            return trim($text);
        }

        $sanitized = $this->recursiveSanitize($decoded);
        return json_encode($sanitized);
    }
}
