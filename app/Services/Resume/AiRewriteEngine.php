<?php

namespace App\Services\Resume;

use Illuminate\Support\Facades\Http;
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

    /* ------------------------------------------------------------------ */
    /*  Public API                                                          */
    /* ------------------------------------------------------------------ */

    public function rewrite(array $parsedResume, array $jdAnalysis, array $weaknessReport, array $qualityReport = []): array
    {
        $tier             = $qualityReport['tier'] ?? 'average';
        $lockedSections   = $qualityReport['locked_sections'] ?? [];
        $preservationMode = $qualityReport['preservation_mode'] ?? false;

        // ELITE resumes: keyword-only enhancement, NO structural rewrite
        if ($tier === 'elite') {
            $enhanced = $this->keywordOnlyEnhancement($parsedResume, $jdAnalysis, $weaknessReport);
            return ['success' => true, 'rewritten_text' => $enhanced, 'ai_used' => false, 'mode' => 'keyword_only'];
        }

        $webhookUrl = config('services.n8n.webhook_url');
        $webhookKey = config('services.resume_intelligence.api_key');

        if (!empty($webhookUrl)) {
            $systemPrompt = $this->buildSystemPrompt($tier);
            $userPrompt   = $this->buildSelectivePrompt($parsedResume, $jdAnalysis, $weaknessReport, $qualityReport);

            try {
                \Illuminate\Support\Facades\Log::info('AiRewrite: Dispatching to n8n AI orchestrator');
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-Resume-Intelligence-Key' => $webhookKey,
                    'Content-Type'              => 'application/json',
                ])->timeout(90)->post($webhookUrl, [
                    'system_prompt'   => $systemPrompt,
                    'user_prompt'     => $userPrompt,
                    'resume'          => $parsedResume,
                    'job_description' => $jdAnalysis,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if (($json['success'] ?? false) && !empty($json['data'])) {
                        $aiData = $this->safeJsonDecode($json['data']);
                        
                        if ($aiData && isset($aiData['optimized_sections'])) {
                            // Merge optimized sections into original parsed resume
                            $finalResume = $parsedResume;
                            foreach (['summary', 'skills', 'experience', 'projects', 'education', 'certifications'] as $key) {
                                if (!empty($aiData['optimized_sections'][$key]['optimized']) && isset($aiData['optimized_sections'][$key]['content'])) {
                                    $finalResume[$key] = $aiData['optimized_sections'][$key]['content'];
                                }
                            }
                            $resultText = json_encode($finalResume);
                        } else {
                            $resultText = is_string($json['data'] ?? '') ? $json['data'] : json_encode($json['data'] ?? []);
                        }

                        \Illuminate\Support\Facades\Log::info('AiRewrite: n8n orchestration success', [
                            'provider' => $json['provider'] ?? 'unknown',
                            'response_size' => strlen($resultText)
                        ]);

                        return [
                            'success'        => true, 
                            'rewritten_text' => $resultText, 
                            'ai_used'        => true, 
                            'engine'         => $json['provider'] ?? 'n8n', 
                            'mode'           => 'targeted'
                        ];
                    }
                }
                \Illuminate\Support\Facades\Log::error('AiRewrite: n8n failure', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('AiRewrite: n8n orchestration exception', ['error' => $e->getMessage()]);
            }
        }

        // ── Fallback Safety ───────────────────────────────────────────
        \Illuminate\Support\Facades\Log::warning('AiRewrite: n8n failed or unavailable — activating rule-based fallback');
        $rewritten = $this->preservationAwareRuleRewrite($parsedResume, $jdAnalysis, $weaknessReport, $lockedSections, $preservationMode);
        
        return [
            'success'        => true,
            'rewritten_text' => $rewritten,
            'ai_used'        => false,
            'mode'           => 'rule_based',
            'ai_offline'     => true,
            'api_errors'     => ['n8n' => 'Fallback activated']
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Keyword-Only Enhancement (Elite Resumes)                            */
    /* ------------------------------------------------------------------ */

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
        $contact = $parsed['contact'] ?? ($parsed['email'] ?? '') . ($parsed['phone'] ? ' | ' . $parsed['phone'] : '');

        $tierInstruction = match($tier) {
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
    /*  Preservation-Aware Rule-Based Fallback                              */
    /* ------------------------------------------------------------------ */

    public function preservationAwareRuleRewrite(array $parsed, array $jd, array $weakness, array $lockedSections = [], bool $preservationMode = false): string
    {
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

        foreach (['summary', 'experience', 'education', 'skills'] as $section) {
            if (empty($decoded[$section])) {
                $issues[] = "Missing or empty section: {$section}";
            }
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
