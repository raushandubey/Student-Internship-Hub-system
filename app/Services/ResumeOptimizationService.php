<?php

namespace App\Services;

use App\Models\Internship;
use App\Models\Profile;
use App\Models\ResumeScore;
use App\Models\ResumeVersion;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI;

/**
 * ResumeOptimizationService
 *
 * Handles ALL AI resume logic:
 *   1. PDF text extraction (local disk only — no S3 read needed)
 *   2. Scoring engine (skill match + keyword + format analysis)
 *   3. AI-powered rewrite via OpenAI GPT
 *   4. Before/After comparison
 *
 * Designed to be completely ISOLATED from existing services.
 * No existing service or model is modified.
 */
class ResumeOptimizationService
{
    private const MODEL = 'gpt-4o-mini';  // cost-efficient, fast

    private ?OpenAI\Client $openai = null;

    /* ------------------------------------------------------------------ */
    /*  Entry Points                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Analyse a user's resume against a specific internship and return a full
     * score report. Caches the result in resume_scores table.
     *
     * @param  User        $user
     * @param  Internship  $internship
     * @return array{
     *   score: int,
     *   skill_match: int,
     *   keyword_score: int,
     *   format_score: int,
     *   matching_skills: array,
     *   missing_skills: array,
     *   issues: array,
     *   strengths: array,
     *   tier: string,
     *   gate_message: string,
     *   badge_class: string,
     *   resume_text: string,
     * }
     */
    public function analyseResume(User $user, Internship $internship): array
    {
        $profile = $user->profile;

        if (!$profile || !$profile->resume_path) {
            return $this->emptyScore('No resume uploaded. Please upload your resume from your profile page.');
        }

        // Step 1: Extract text from PDF
        $resumeText = $this->extractResumeText($profile);

        if (empty($resumeText)) {
            return $this->emptyScore('Unable to read your resume. Please ensure it is a valid PDF.');
        }

        // Step 2: Run scoring (rule-based + lightweight AI analysis)
        $breakdown = $this->scoreResume($resumeText, $internship);

        // Step 3: Persist score (overwrite any previous score for this user+job)
        $score = ResumeScore::updateOrCreate(
            ['user_id' => $user->id, 'internship_id' => $internship->id],
            [
                'overall_score'    => $breakdown['overall_score'],
                'skill_match_score' => $breakdown['skill_match'],
                'keyword_score'    => $breakdown['keyword_score'],
                'format_score'     => $breakdown['format_score'],
                'matching_skills'  => $breakdown['matching_skills'],
                'missing_skills'   => $breakdown['missing_skills'],
                'issues'           => $breakdown['issues'],
                'strengths'        => $breakdown['strengths'],
                'match_tier'       => ResumeScore::scoreTier($breakdown['overall_score']),
            ]
        );

        return array_merge($breakdown, [
            'score'        => $breakdown['overall_score'],
            'tier'         => $score->match_tier,
            'gate_message' => $score->gateMessage(),
            'badge_class'  => $score->badgeClass(),
            'resume_text'  => $resumeText,
        ]);
    }

    /**
     * Rewrite the user's resume using OpenAI and save as a new version.
     * Returns the new version content plus a before/after comparison object.
     *
     * @param  User       $user
     * @param  Internship $internship
     * @return array{
     *   success: bool,
     *   rewritten_text: string,
     *   before_score: int,
     *   after_score: int,
     *   improvements: array,
     *   version_id: int,
     * }
     */
    public function rewriteResume(User $user, Internship $internship): array
    {
        $profile = $user->profile;

        if (!$profile || !$profile->resume_path) {
            return ['success' => false, 'error' => 'No resume found. Please upload a resume first.'];
        }

        $resumeText = $this->extractResumeText($profile);

        if (empty($resumeText)) {
            return ['success' => false, 'error' => 'Unable to read resume PDF.'];
        }

        // Get current (before) score
        $beforeBreakdown = $this->scoreResume($resumeText, $internship);
        $beforeScore     = $beforeBreakdown['overall_score'];

        // AI rewrite
        $rewrittenText = $this->callOpenAIRewrite($resumeText, $internship);

        if (empty($rewrittenText)) {
            return ['success' => false, 'error' => 'AI service is currently unavailable. Please try again later.'];
        }

        // Validate the AI output quality
        $validationIssues = $this->validateRewriteOutput($rewrittenText, $internship);
        if (!empty($validationIssues)) {
            Log::warning('ResumeOptimization: Output validation failed', ['issues' => $validationIssues]);
            $rewrittenText = $this->ruleBasedRewrite($resumeText, $internship);
        }

        // Score the rewritten resume
        $afterBreakdown = $this->scoreResume($rewrittenText, $internship);
        $afterScore     = $afterBreakdown['overall_score'];

        // Determine the next version number for this user+internship
        $nextVersion = (ResumeVersion::where('user_id', $user->id)
            ->where('internship_id', $internship->id)
            ->max('version_number') ?? 0) + 1;

        // Save original version snapshot if it doesn't exist yet
        $this->saveOriginalSnapshot($user->id, $internship->id, $resumeText, $beforeScore);

        // ── Sanitize before persisting (strip any PDF binary artefacts) ──
        $rewrittenText = $this->sanitizeForStorage($rewrittenText);

        // Save new AI version
        $newVersion = ResumeVersion::create([
            'user_id'          => $user->id,
            'internship_id'    => $internship->id,
            'version_number'   => $nextVersion,
            'label'            => 'AI Optimized — v' . $nextVersion,
            'content'          => $rewrittenText,
            'score_at_creation' => $afterScore,
            'type'             => 'ai_rewrite',
        ]);

        // Update the score record with the new version
        ResumeScore::updateOrCreate(
            ['user_id' => $user->id, 'internship_id' => $internship->id],
            [
                'overall_score'    => $afterScore,
                'skill_match_score' => $afterBreakdown['skill_match'],
                'keyword_score'    => $afterBreakdown['keyword_score'],
                'format_score'     => $afterBreakdown['format_score'],
                'matching_skills'  => $afterBreakdown['matching_skills'],
                'missing_skills'   => $afterBreakdown['missing_skills'],
                'issues'           => $afterBreakdown['issues'],
                'strengths'        => $afterBreakdown['strengths'],
                'match_tier'       => ResumeScore::scoreTier($afterScore),
                'resume_version_id' => $newVersion->id,
            ]
        );

        $improvements = $this->buildImprovementsList($beforeBreakdown, $afterBreakdown);

        Log::info('Resume rewritten by AI', [
            'user_id'        => $user->id,
            'internship_id'  => $internship->id,
            'before_score'   => $beforeScore,
            'after_score'    => $afterScore,
            'version_id'     => $newVersion->id,
        ]);

        return [
            'success'        => true,
            'rewritten_text' => $rewrittenText,
            'before_score'   => $beforeScore,
            'after_score'    => $afterScore,
            'improvements'   => $improvements,
            'version_id'     => $newVersion->id,
        ];
    }

    /**
     * Return the latest score record for a user+job combo (or null).
     */
    public function getExistingScore(User $user, Internship $internship): ?ResumeScore
    {
        return ResumeScore::where('user_id', $user->id)
            ->where('internship_id', $internship->id)
            ->latest()
            ->first();
    }

    /* ------------------------------------------------------------------ */
    /*  Scoring Engine (rule-based, no AI cost)                            */
    /* ------------------------------------------------------------------ */

    /**
     * Core scoring function — runs entirely locally, no API calls.
     */
    private function scoreResume(string $resumeText, Internship $internship): array
    {
        $resumeLower = strtolower($resumeText);
        $jobSkills   = array_map('strtolower', $internship->required_skills ?? []);
        $jobKeywords = $this->extractJobKeywords($internship);

        // ── 1. Skill Match (40%) ───────────────────────────────────────
        $matchingSkills = [];
        $missingSkills  = [];
        foreach ($jobSkills as $skill) {
            if ($this->skillExistsInText($skill, $resumeLower)) {
                $matchingSkills[] = $skill;
            } else {
                $missingSkills[] = $skill;
            }
        }
        $skillMatch = count($jobSkills) > 0
            ? (int) round((count($matchingSkills) / count($jobSkills)) * 100)
            : 50;

        // ── 2. Keyword Match (25%) ────────────────────────────────────
        $keywordsFound = 0;
        foreach ($jobKeywords as $kw) {
            if (str_contains($resumeLower, strtolower($kw))) {
                $keywordsFound++;
            }
        }
        $keywordScore = count($jobKeywords) > 0
            ? (int) round(($keywordsFound / count($jobKeywords)) * 100)
            : 50;

        // ── 3. Experience Alignment (15%) ─────────────────────────────
        $expScore = $this->scoreExperienceRelevance($resumeLower, $jobSkills, $jobKeywords);

        // ── 4. Project Relevance (15%) ────────────────────────────────
        $projScore = $this->scoreProjectRelevance($resumeLower, $jobSkills);

        // ── 5. Resume Completeness (5%) ───────────────────────────────
        $completeness = $this->scoreCompleteness($resumeText);

        // ── Weighted Overall: Skill 40% | Keyword 25% | Project 15% | Experience 15% | Completeness 5% ──
        $overall = (int) round(
            ($skillMatch   * 0.40) +
            ($keywordScore * 0.25) +
            ($projScore    * 0.15) +
            ($expScore     * 0.15) +
            ($completeness * 0.05)
        );

        // ── Issues Detection ──────────────────────────────────────────
        $issues    = [];
        $strengths = [];

        if (!preg_match('/\d+[%x]|\d+ (users|clients|students|projects|team|months|years|requests|queries|ms\b)/i', $resumeText)) {
            $issues[] = 'No measurable impact — add numbers/percentages to achievements';
        }
        $weakVerbs = ['worked on', 'helped with', 'did some', 'was responsible', 'assisted in'];
        foreach ($weakVerbs as $v) {
            if (stripos($resumeText, $v) !== false) {
                $issues[] = 'Weak phrasing detected ("' . $v . '") — use action verbs: developed, implemented, architected';
                break;
            }
        }
        if (!empty($missingSkills)) {
            $issues[] = 'Missing required skills: ' . implode(', ', array_slice($missingSkills, 0, 4));
        }
        if ($completeness < 60) {
            $issues[] = 'Resume missing key sections (Summary, Skills, Experience, Education)';
        }
        if ($expScore < 40) {
            $issues[] = 'Work experience does not clearly align with job requirements';
        }

        // ── Strengths ─────────────────────────────────────────────────
        if ($skillMatch >= 70) $strengths[] = 'Strong technical skill alignment with job requirements';
        if ($keywordScore >= 65) $strengths[] = 'Good keyword density for ATS parsing';
        if ($expScore >= 60) $strengths[] = 'Relevant work experience for this role';
        if ($projScore >= 60) $strengths[] = 'Relevant projects demonstrating hands-on ability';
        if (preg_match('/\d+[%x]|\d+ (users|clients|projects|requests)/i', $resumeText)) {
            $strengths[] = 'Contains quantified achievements';
        }

        return [
            'overall_score'    => min(100, max(0, $overall)),
            'skill_match'      => $skillMatch,
            'keyword_score'    => $keywordScore,
            'format_score'     => $completeness, // kept for DB compatibility
            'exp_score'        => $expScore,
            'proj_score'       => $projScore,
            'matching_skills'  => $matchingSkills,
            'missing_skills'   => $missingSkills,
            'issues'           => $issues,
            'strengths'        => $strengths,
        ];
    }

    /**
     * Check whether a skill term exists in the resume text using word-boundary matching.
     *
     * Primary check: exact word-boundary regex (handles single-word and multi-word skills).
     * Fallback: substring match for multi-word skills (e.g. "machine learning") where
     * word boundaries may not align perfectly across hyphenated or concatenated tokens.
     * Special handling: For skills with non-word characters (C++, C#, .NET), use
     * boundary patterns that work with special characters.
     *
     * @param  string $skill        The skill to search for (already lowercased).
     * @param  string $resumeLower  The full resume text (already lowercased).
     * @return bool
     */
    private function skillExistsInText(string $skill, string $resumeLower): bool
    {
        // Check if skill contains non-word characters (like +, #, .)
        $hasSpecialChars = preg_match('/[^\w\s-]/', $skill);
        
        if ($hasSpecialChars) {
            // For skills with special characters, use a more flexible boundary pattern
            // Match if the skill appears with word boundaries OR common delimiters (comma, space, newline, start/end of string)
            $escaped = preg_quote($skill, '/');
            $pattern = '/(^|[\s,;|]|^)' . $escaped . '($|[\s,;|]|$)/i';
            if (preg_match($pattern, $resumeLower)) {
                return true;
            }
        } else {
            // Primary: exact word-boundary match for normal skills (case-insensitive flag kept for safety)
            if (preg_match('/\b' . preg_quote($skill, '/') . '\b/i', $resumeLower)) {
                return true;
            }
        }

        // Fallback: partial substring match for multi-word skills
        if (str_word_count($skill) > 1 && str_contains($resumeLower, $skill)) {
            return true;
        }

        return false;
    }

    /**
     * Score how well the experience section matches the target role.
     * Checks for job-related keywords within the experience block.
     */
    private function scoreExperienceRelevance(string $resumeLower, array $jobSkills, array $jobKeywords): int
    {
        // Find the experience section
        $expStart = strpos($resumeLower, 'experience');
        if ($expStart === false) return 20; // no experience section found

        // Find the next section header after the experience section start,
        // using the same header keywords as ResumePdfService::detectSections()
        $nextSectionKeywords = [
            'education', 'academic', 'qualifications',
            'projects', 'personal projects', 'academic projects', 'key projects',
            'skills', 'core skills', 'technical skills', 'competencies', 'key skills',
            'certifications', 'certification', 'courses', 'course', 'training', 'achievements',
            'awards', 'honours', 'honors',
        ];

        $nextSectionPos = false;
        foreach ($nextSectionKeywords as $keyword) {
            // Search for the keyword after the experience section start (offset by 1 to skip the word "experience" itself)
            $pos = strpos($resumeLower, $keyword, $expStart + strlen('experience'));
            if ($pos !== false) {
                if ($nextSectionPos === false || $pos < $nextSectionPos) {
                    $nextSectionPos = $pos;
                }
            }
        }

        // Extract the experience block using section boundaries, or fall back to 2000-char window
        if ($nextSectionPos !== false) {
            $expBlock = substr($resumeLower, $expStart, $nextSectionPos - $expStart);
        } else {
            $expBlock = substr($resumeLower, $expStart, 2000);
        }

        $hits = 0;
        $total = count($jobSkills) + min(5, count($jobKeywords));
        if ($total === 0) return 50;

        foreach ($jobSkills as $skill) {
            if (str_contains($expBlock, $skill)) $hits++;
        }
        foreach (array_slice($jobKeywords, 0, 5) as $kw) {
            if (str_contains($expBlock, strtolower($kw))) $hits++;
        }

        // Bonus: action verbs in experience
        $actionVerbs = ['developed', 'built', 'implemented', 'designed', 'led', 'architected',
                        'optimized', 'reduced', 'increased', 'deployed', 'integrated', 'automated'];
        foreach ($actionVerbs as $v) {
            if (str_contains($expBlock, $v)) { $hits++; break; }
        }

        return min(100, (int) round(($hits / ($total + 1)) * 100));
    }

    /**
     * Score how relevant the projects section is to the job.
     */
    private function scoreProjectRelevance(string $resumeLower, array $jobSkills): int
    {
        $projStart = strpos($resumeLower, 'project');
        if ($projStart === false) return 10;

        $projBlock = substr($resumeLower, $projStart, 1500);
        if (empty($jobSkills)) return 50;

        $hits = 0;
        foreach ($jobSkills as $skill) {
            if (str_contains($projBlock, $skill)) $hits++;
        }
        return min(100, (int) round(($hits / count($jobSkills)) * 100));
    }

    /**
     * Score resume completeness — checks for the 5 required ATS sections + contact.
     */
    private function scoreCompleteness(string $text): int
    {
        $score = 0;
        $lower = strtolower($text);

        $required = [
            'summary'    => ['summary', 'objective', 'profile'],
            'skills'     => ['skills', 'competencies', 'technologies'],
            'experience' => ['experience', 'employment', 'work history'],
            'education'  => ['education', 'degree', 'university', 'college'],
            'projects'   => ['projects', 'portfolio'],
        ];

        foreach ($required as $section => $variants) {
            foreach ($variants as $v) {
                if (str_contains($lower, $v)) { $score += 16; break; }
            }
        }

        // Contact info present
        if (preg_match('/[\w.+-]+@[\w-]+\.\w+/', $text)) $score += 10;
        // Phone number present
        if (preg_match('/[6-9]\d{9}|\+\d{10,}/', $text))  $score += 5;
        // Dates present (experience timeline)
        if (preg_match('/20\d{2}/', $text))                $score += 5;

        return min(100, $score);
    }

    /**
     * Extract rich contextual keywords from the job description + title.
     * Excludes common stop words and single-character tokens.
     */
    private function extractJobKeywords(Internship $internship): array
    {
        $stopWords = [
            'the','a','an','and','or','in','at','for','to','of','is','are','will','be',
            'with','as','on','this','that','our','your','we','you','have','has','can',
            'must','should','would','able','work','team','good','strong','knowledge',
            'experience','required','preferred','looking','join','role','position',
            // Additional filler/action words that add noise without discriminating value
            'using','develop','build','manage','ensure','provide','support','maintain',
            'create','make','help','need','new','also','well','both','all','any',
            'other','more','into','from','about','over','such','like','than',
        ];

        $text  = strtolower(
            ($internship->title ?? '') . ' ' .
            ($internship->description ?? '') . ' ' .
            implode(' ', $internship->required_skills ?? [])
        );
        $words = preg_split('/[\W_]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $keywords = [];
        foreach ($words as $w) {
            if (strlen($w) > 2 && !in_array($w, $stopWords) && !is_numeric($w)) {
                $keywords[] = $w;
            }
        }

        $keywords = array_values(array_unique($keywords));

        // Deduplicate: remove any keyword that already appears in required_skills
        // to avoid double-counting the same term in both skill score and keyword score.
        $requiredSkillsLower = array_map('strtolower', $internship->required_skills ?? []);
        // Flatten multi-word skills into individual tokens for comparison
        $skillTokens = [];
        foreach ($requiredSkillsLower as $skill) {
            foreach (preg_split('/[\W_]+/', $skill, -1, PREG_SPLIT_NO_EMPTY) as $token) {
                if (strlen($token) > 2) {
                    $skillTokens[] = $token;
                }
            }
        }
        $skillTokens = array_unique($skillTokens);

        $keywords = array_values(array_filter($keywords, function (string $kw) use ($skillTokens) {
            return !in_array($kw, $skillTokens, true);
        }));

        return $keywords;
    }

    /* ------------------------------------------------------------------ */
    /*  PDF Text Extraction                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Extract plain text from the user's uploaded PDF resume.
     * Uses smalot/pdfparser as primary extractor, falls back to BT/ET regex.
     */
    private function extractResumeText(Profile $profile): string
    {
        if (!$profile->resume_path) {
            return '';
        }

        try {
            $disk           = config('filesystems.default');
            $normalizedPath = ltrim($profile->resume_path, '/');

            // Resolve absolute file path
            if ($disk === 's3') {
                if (!Storage::disk('s3')->exists($normalizedPath)) {
                    Log::warning('ResumeOptimization: Resume not on S3', ['path' => $normalizedPath]);
                    return '';
                }
                // Write to a temp file so pdfparser can read it
                $tmpFile = sys_get_temp_dir() . '/resume_' . $profile->id . '.pdf';
                file_put_contents($tmpFile, Storage::disk('s3')->get($normalizedPath));
                $absolutePath = $tmpFile;
            } else {
                $absolutePath = storage_path('app/public/' . $normalizedPath);
                if (!file_exists($absolutePath)) {
                    if (Storage::disk('public')->exists($normalizedPath)) {
                        $absolutePath = Storage::disk('public')->path($normalizedPath);
                    } else {
                        Log::warning('ResumeOptimization: Resume not found', ['path' => $absolutePath]);
                        return '';
                    }
                }
            }

            // ── Primary: smalot/pdfparser (proper text extraction) ──
            $text = $this->extractWithPdfParser($absolutePath);

            // ── Fallback: BT/ET regex stream extraction ──
            if (strlen(trim($text)) < 80) {
                $raw  = file_get_contents($absolutePath);
                $text = $this->parsePdfTextFallback($raw);
            }

            // Clean up temp file for S3
            if ($disk === 's3' && isset($tmpFile) && file_exists($tmpFile)) {
                unlink($tmpFile);
            }

            return $text;

        } catch (\Exception $e) {
            Log::error('ResumeOptimization: Text extraction failed', [
                'profile_id' => $profile->id,
                'error'      => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Extract text using smalot/pdfparser — handles most modern PDFs correctly.
     */
    private function extractWithPdfParser(string $filePath): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($filePath);
            $text   = $pdf->getText();

            // Basic cleanup
            $text = preg_replace('/[ \t]+/', ' ', $text);
            $text = preg_replace('/(\r?\n){3,}/', "\n\n", $text);
            $text = trim($text);

            Log::info('ResumeOptimization: PdfParser extracted text', [
                'length' => strlen($text),
                'path'   => basename($filePath),
            ]);

            return $text;
        } catch (\Exception $e) {
            Log::warning('ResumeOptimization: PdfParser failed, using fallback', [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Fallback BT/ET regex extraction (for encrypted or non-standard PDFs).
     */
    private function parsePdfTextFallback(string $pdfContent): string
    {
        $text = '';

        // Extract from BT...ET text blocks
        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $pdfContent, $matches)) {
            foreach ($matches[1] as $block) {
                if (preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)\s*Tj/s', $block, $m)) {
                    foreach ($m[1] as $str) {
                        $text .= $this->decodePdfString($str) . ' ';
                    }
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $m)) {
                    foreach ($m[1] as $arr) {
                        if (preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)/', $arr, $items)) {
                            foreach ($items[1] as $item) {
                                $text .= $this->decodePdfString($item) . ' ';
                            }
                        }
                    }
                }
            }
        }

        // Last resort: printable ASCII pass
        if (strlen(trim($text)) < 80) {
            $text = preg_replace('/[^\x20-\x7E\n\r\t]/', ' ', $pdfContent);
            $text = preg_replace('/\s{3,}/', "\n", $text);
            $text = preg_replace('/\b(BT|ET|Td|TD|Tm|Tf|Tj|TJ|cm|q|Q|re|f|S|n|W|w|j|J|d|gs|cs|sc|Do|BMC|BDC|EMC)\b/', '', $text);
        }

        // Strip PDF binary noise
        $text = preg_replace('/%PDF-[\d.]+.*?(\n|\r\n)/s', '', $text);
        $text = preg_replace('/\d+\s+\d+\s+obj[\s\S]*?endobj/i', '', $text);
        $text = preg_replace('/xref[\s\S]*?%%EOF/i', '', $text);
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/(\r?\n){3,}/', "\n\n", $text);

        return trim($text);
    }


    /**
     * Parse text from raw PDF bytes (pure PHP, no external dependency).
     * Extracts readable text blocks from PDF content streams.
     */
    private function parsePdfText(string $pdfContent): string
    {
        $text = '';

        // Method 1: Extract from text streams (BT...ET blocks)
        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $pdfContent, $matches)) {
            foreach ($matches[1] as $block) {
                // Extract strings inside parentheses (Tj operator)
                if (preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)\s*Tj/s', $block, $strMatches)) {
                    foreach ($strMatches[1] as $str) {
                        $text .= $this->decodePdfString($str) . ' ';
                    }
                }
                // Extract TJ array format
                if (preg_match_all('/\[((?:[^[\]]|\[(?:[^[\]]|\[[^\[\]]*\])*\])*)\]\s*TJ/s', $block, $arrayMatches)) {
                    foreach ($arrayMatches[1] as $arr) {
                        if (preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)/', $arr, $itemMatches)) {
                            foreach ($itemMatches[1] as $item) {
                                $text .= $this->decodePdfString($item) . ' ';
                            }
                        }
                    }
                }
            }
        }

        // Method 2: Fallback — extract all printable text (for simple PDFs)
        if (strlen(trim($text)) < 100) {
            $raw = preg_replace('/[^\x20-\x7E\n\r\t]/', ' ', $pdfContent);
            $raw = preg_replace('/\s{3,}/', "\n", $raw);
            // Remove PDF operators
            $raw = preg_replace('/\b(BT|ET|Td|TD|Tm|T\*|Tf|Tj|TJ|cm|q|Q|re|f|S|n|W|w|j|J|M|d|ri|i|gs|cs|CS|sc|SC|g|G|rg|RG|k|K|sh|Do|MP|DP|BMC|BDC|EMC|BX|EX)\b/', '', $raw);
            $text = $raw;
        }

        // ── Always sanitize: strip PDF binary noise and garbage characters ──
        // Remove raw PDF header/object markers
        $text = preg_replace('/%PDF-[\d.]+.*?(\n|\r\n)/s', '', $text);
        $text = preg_replace('/\d+\s+\d+\s+obj[\s\S]*?endobj/i', '', $text);
        $text = preg_replace('/<<[^>]{0,200}>>/s', '', $text);
        $text = preg_replace('/\/[A-Za-z]+[\d]+/m', '', $text); // /F1 /C1 etc
        // Remove hex-encoded strings
        $text = preg_replace('/<[0-9a-fA-F\s]{4,}>/m', '', $text);
        // Strip leftover non-printable chars (keep newlines, tabs)
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
        // Collapse whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/(\r?\n){3,}/', "\n\n", $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Decode PDF escape sequences in a string.
     */
    private function decodePdfString(string $str): string
    {
        $str = str_replace('\\n', "\n", $str);
        $str = str_replace('\\r', "\r", $str);
        $str = str_replace('\\t', "\t", $str);
        $str = str_replace('\\(', '(', $str);
        $str = str_replace('\\)', ')', $str);
        $str = str_replace('\\\\', '\\', $str);
        return $str;
    }

    /* ------------------------------------------------------------------ */
    /*  AI Rewrite (OpenAI)                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Call OpenAI to rewrite the resume optimised for the target job.
     *
     * The prompt instructs GPT to output a STRUCTURED plain-text resume using
     * explicit pipe-separated headers and bullet markers. This structured format
     * is reliably parsed by ResumePdfService::parseEntries() and related methods,
     * eliminating the ambiguity that caused broken PDF layouts.
     *
     * Output format contract:
     *   - Experience entries: "Company | Role | Date | Location"
     *   - Project entries:    "Project Name | Tech Stack"
     *   - Education entries:  "Institution | Degree | Year | CGPA"
     *   - Bullets:            "• bullet text" (one per line)
     *   - Skills:             "Languages: X, Y | Frameworks: A, B | Tools: C | Databases: D"
     */
    private function callOpenAIRewrite(string $resumeText, Internship $internship): string
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            Log::warning('ResumeOptimization: OPENAI_API_KEY not configured — using rule-based rewrite');
            return $this->ruleBasedRewrite($resumeText, $internship);
        }

        try {
            $client = $this->getOpenAIClient($apiKey);

            $requiredSkills = implode(', ', $internship->required_skills ?? []);
            $jobDescription = implode("\n", [
                'Job Title: '       . $internship->title,
                'Organization: '    . $internship->organization,
                'Location: '        . ($internship->location ?? 'N/A'),
                'Required Skills: ' . $requiredSkills,
                'Description: '     . substr($internship->description ?? '', 0, 800),
            ]);

            $prompt = <<<PROMPT
You are an elite ATS resume architect and senior technical recruiter. Your output will be rendered into a premium FAANG-quality PDF resume. 
The formatting you produce DIRECTLY controls how the PDF looks — follow the structure below with surgical precision.

━━━ TARGET JOB ━━━
{$jobDescription}

━━━ CANDIDATE'S ORIGINAL RESUME ━━━
{$resumeText}

━━━ REQUIRED OUTPUT FORMAT ━━━

Output the resume in EXACTLY this structure. Do not add any commentary, preamble, or markdown.

[FULL NAME IN CAPS]
[Phone] | [Email] | [City, State]

PROFESSIONAL SUMMARY
[2-3 sentences. Mention the exact job title "{$internship->title}" and 2-3 required skills naturally. Be specific and confident.]

TECHNICAL SKILLS
Languages: [list] | Frameworks: [list] | Tools: [list] | Databases: [list]

EXPERIENCE

[Company Name] | [Job Title] | [Month Year – Month Year or Present] | [City or Remote]
• [Action verb + what + technology + measurable impact. Max 140 chars / 2 lines.]
• [Action verb + what + technology + measurable impact. Max 140 chars / 2 lines.]

[Next Company] | [Job Title] | [Date Range] | [Location]
• [bullet]
• [bullet]

PROJECTS

[Project Name] | [Tech Stack: comma-separated]
[1-line project summary describing what the project is.]
• [What it does + your contribution + scale/impact. Max 140 chars / 2 lines.]
• [Technical detail or feature architecture. Max 140 chars / 2 lines.]

EDUCATION

[University/College Name] | [Degree, Major] | [Year] | [CGPA if present]

CERTIFICATIONS

• [Certification name — only if present in original resume]

━━━ STRICT RULES ━━━
1. PRESERVE LAYOUT SKELETON — Keep EVERY company name, date, role, institution, and degree exactly as in the original. DO NOT invent or change any factual timeline or historical data. You are ONLY enhancing targeting/content, NOT inventing a new person.
2. PIPE FORMAT — use " | " (space-pipe-space) to separate fields in entry headers. This is critical for PDF rendering.
3. BULLET FORMAT — every bullet MUST start with "• " (bullet + space). No dashes, no asterisks. Limit to max 2 lines (approx 140 characters). No giant paragraphs.
4. STRONG BULLETS — each bullet: [Strong Action Verb] + [specific technology/tool] + [measurable or qualitative impact]. Never start with "Worked on", "Helped with", "Was responsible for".
5. ONE PAGE — total output must be extremely tight. Cut weak bullets before cutting sections. Prioritize high-impact experience.
6. NO FABRICATION — DO NOT hallucinate. Do not add fake metrics, fake companies, or fake experience. If a metric does not exist in the original, use qualitative impact language ("improving scalability", "enhancing maintainability").
7. SKILLS FORMAT — always use the grouped format: "Languages: X | Frameworks: Y | Tools: Z | Databases: W". Add required skills ({$requiredSkills}) to the appropriate group if not already present.
8. BLANK LINES — put exactly ONE blank line between entries within a section.
9. SECTION HEADERS — write section headers in ALL CAPS on their own line: PROFESSIONAL SUMMARY, TECHNICAL SKILLS, EXPERIENCE, PROJECTS, EDUCATION, CERTIFICATIONS.
10. OUTPUT ONLY — output only the resume text. No "Here is your resume:", no markdown (##, **), no extra commentary.

OUTPUT:
PROMPT;

            $response = $client->chat()->create([
                'model'       => self::MODEL,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'You are an elite ATS resume architect. You produce FAANG-quality, recruiter-approved one-page resumes. You follow output format instructions with absolute precision — the format you output is directly rendered into a PDF, so structural accuracy is critical. You NEVER fabricate experience, companies, dates, or metrics. You ALWAYS preserve the original resume layout skeleton.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens'  => 1400,
                'temperature' => 0.3,
            ]);

            $result = trim($response->choices[0]->message->content ?? '');

            if (empty($result)) {
                Log::warning('ResumeOptimization: OpenAI returned empty response');
                return $this->ruleBasedRewrite($resumeText, $internship);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('ResumeOptimization: OpenAI call failed', ['error' => $e->getMessage()]);
            return $this->ruleBasedRewrite($resumeText, $internship);
        }
    }

    /**
     * Rule-based rewrite when OpenAI is unavailable.
     *
     * Produces output in the SAME pipe-structured format as the AI prompt output,
     * so ResumePdfService::parseEntries() and parseProjects() parse it correctly.
     * This ensures the fallback path produces a well-structured PDF, not a broken one.
     *
     * Format contract (mirrors the AI prompt output format):
     *   - Experience: "Company | Role | Date | Location"
     *   - Projects:   "Project Name | Tech Stack"
     *   - Education:  "Institution | Degree | Year | CGPA"
     *   - Bullets:    "• bullet text"
     *   - Skills:     "Languages: X | Frameworks: Y | Tools: Z | Databases: W"
     */
    private function ruleBasedRewrite(string $resumeText, Internship $internship): string
    {
        $skills   = $internship->required_skills ?? [];
        $jobTitle = $internship->title;
        $org      = $internship->organization;
        $loc      = $internship->location ?? '';

        // ── Parse the existing resume ──
        $parsed          = $this->parseExistingResume($resumeText);
        $name            = $parsed['name']     ?: 'Candidate';
        $contact         = $parsed['contact']  ?: '';
        $extractedSkills = $parsed['skills']   ?? [];
        $apparentRole    = $parsed['apparent_role'] ?? 'professional';

        // ── PROFESSIONAL SUMMARY ──────────────────────────────────────
        $summarySkills = implode(', ', array_slice($skills, 0, 3));
        $summary  = "Results-driven {$apparentRole} seeking the {$jobTitle} position";
        $summary .= $loc ? " in {$loc}" : '';
        $summary .= " at {$org}. ";
        if ($summarySkills) {
            $summary .= "Proficient in {$summarySkills}. ";
        }
        $summary .= "Demonstrated ability to deliver measurable results through hands-on project experience and continuous learning.";

        // ── TECHNICAL SKILLS (grouped format) ────────────────────────
        // Merge job-required skills with extracted skills
        $allSkills = array_unique(array_merge($skills, $extractedSkills));

        // Attempt to group into categories
        $langKeywords  = ['php', 'python', 'java', 'javascript', 'typescript', 'c++', 'c#', 'ruby', 'go', 'rust', 'swift', 'kotlin', 'dart', 'r', 'matlab', 'scala', 'perl', 'bash', 'html', 'css', 'sql'];
        $fwKeywords    = ['laravel', 'django', 'flask', 'react', 'vue', 'angular', 'next', 'nuxt', 'express', 'spring', 'rails', 'fastapi', 'node', 'nodejs', 'bootstrap', 'tailwind', 'jquery', 'redux', 'graphql'];
        $toolKeywords  = ['git', 'docker', 'kubernetes', 'jenkins', 'aws', 'azure', 'gcp', 'linux', 'nginx', 'apache', 'postman', 'jira', 'figma', 'webpack', 'vite', 'terraform', 'ansible', 'ci/cd', 'github', 'gitlab'];
        $dbKeywords    = ['mysql', 'postgresql', 'mongodb', 'redis', 'sqlite', 'oracle', 'mssql', 'elasticsearch', 'cassandra', 'dynamodb', 'firebase', 'supabase'];

        $langs = $fws = $tools = $dbs = $other = [];
        foreach ($allSkills as $skill) {
            $sl = strtolower($skill);
            if (in_array($sl, $langKeywords))     $langs[]  = $skill;
            elseif (in_array($sl, $fwKeywords))   $fws[]    = $skill;
            elseif (in_array($sl, $toolKeywords)) $tools[]  = $skill;
            elseif (in_array($sl, $dbKeywords))   $dbs[]    = $skill;
            else                                   $other[]  = $skill;
        }

        $skillParts = [];
        if ($langs)  $skillParts[] = 'Languages: '  . implode(', ', array_slice($langs,  0, 6));
        if ($fws)    $skillParts[] = 'Frameworks: '  . implode(', ', array_slice($fws,   0, 6));
        if ($tools)  $skillParts[] = 'Tools: '       . implode(', ', array_slice($tools, 0, 6));
        if ($dbs)    $skillParts[] = 'Databases: '   . implode(', ', array_slice($dbs,   0, 5));
        if ($other && empty($skillParts)) {
            // If no categorization worked, just list them all
            $skillParts[] = 'Core Skills: ' . implode(', ', array_slice($other, 0, 15));
        } elseif ($other) {
            // Append uncategorized to Tools
            $lastIdx = count($skillParts) - 1;
            $skillParts[$lastIdx] .= ', ' . implode(', ', array_slice($other, 0, 4));
        }
        $skillsLine = implode(' | ', $skillParts) ?: implode(', ', array_slice($allSkills, 0, 15));

        // ── EXPERIENCE (pipe-structured format) ──────────────────────
        // Build structured entries from parsed data, but also capture the raw
        // original body text as a safety net for data that the parser may miss
        // (e.g. multiple date lines, all-lowercase company names, unusual formats).
        $expLines = '';
        foreach ($parsed['experience'] ?? [] as $exp) {
            $header = implode(' | ', array_filter([
                $exp['org']      ?? $exp['title'] ?? '',
                $exp['title']    ?? '',
                $exp['date']     ?? '',
                $exp['location'] ?? '',
            ]));
            $expLines .= $header . "\n";
            foreach (array_slice($exp['bullets'] ?? [], 0, 4) as $bullet) {
                $enhanced = $this->enhanceBulletPoints([$bullet], $skills)[0];
                $expLines .= '• ' . $enhanced . "\n";
            }
            $expLines .= "\n";
        }

        // ── PROJECTS (pipe-structured format) ────────────────────────
        $projLines = '';
        foreach ($parsed['projects'] ?? [] as $proj) {
            $header = $proj['title'] ?? '';
            if (!empty($proj['tech'])) $header .= ' | ' . $proj['tech'];
            $projLines .= $header . "\n";
            foreach (array_slice($proj['bullets'] ?? [], 0, 3) as $bullet) {
                $projLines .= '• ' . $bullet . "\n";
            }
            $projLines .= "\n";
        }

        // ── EDUCATION (pipe-structured format) ───────────────────────
        $eduLines = '';
        foreach ($parsed['education'] ?? [] as $edu) {
            $parts = array_filter([
                $edu['school'] ?? $edu['degree'] ?? '',
                $edu['degree'] ?? '',
                $edu['year']   ?? '',
            ]);
            $eduLines .= implode(' | ', $parts) . "\n";
        }

        // ── Safety net: extract original body text ───────────────────
        // If the structured parse produced no experience/education entries,
        // fall back to the original body text verbatim to guarantee data preservation.
        // This handles edge cases: all-lowercase companies, unusual date formats,
        // multiple date lines, etc.
        $originalBodyLines = explode("\n", $resumeText);
        $bodyStartIdx = -1;
        $bodyPatterns = [
            '/^(work\s+experience|experience|employment|professional\s+experience)/i',
            '/^(projects|personal\s+projects|academic\s+projects|key\s+projects)/i',
            '/^(education|academic|qualifications|degree)/i',
        ];
        foreach ($originalBodyLines as $i => $line) {
            $trimmed = trim($line);
            if (strlen($trimmed) > 60) continue;
            if ($bodyStartIdx === -1) {
                foreach ($bodyPatterns as $p) {
                    if (preg_match($p, $trimmed)) {
                        $bodyStartIdx = $i;
                        break;
                    }
                }
            }
        }
        $originalBody = $bodyStartIdx !== -1
            ? implode("\n", array_slice($originalBodyLines, $bodyStartIdx))
            : '';

        // ── Assemble ─────────────────────────────────────────────────
        // Strategy: Always preserve the original body text verbatim to guarantee
        // 100% data preservation (companies, dates, institutions, bullets).
        // Only the Summary and Skills sections are replaced with improved versions.
        // This is the safest approach for the rule-based fallback path.
        $resume  = strtoupper($name) . "\n";
        $resume .= $contact ? $contact . "\n" : '';
        $resume .= "\n";

        $resume .= "PROFESSIONAL SUMMARY\n";
        $resume .= $summary . "\n\n";

        $resume .= "TECHNICAL SKILLS\n";
        $resume .= $skillsLine . "\n\n";

        // Append the original body (experience, projects, education, certifications)
        // verbatim — this guarantees all company names, dates, and institutions are preserved
        if ($originalBody) {
            $resume .= trim($originalBody);
        } elseif ($expLines || $projLines || $eduLines) {
            // Fallback to structured entries if original body extraction failed
            if ($expLines) {
                $resume .= "EXPERIENCE\n\n" . trim($expLines) . "\n\n";
            }
            if ($projLines) {
                $resume .= "PROJECTS\n\n" . trim($projLines) . "\n\n";
            }
            if ($eduLines) {
                $resume .= "EDUCATION\n\n" . trim($eduLines) . "\n";
            }
        }

        Log::info('ResumeOptimization: Rule-based rewrite used (structured format)', [
            'has_experience' => !empty($parsed['experience']),
            'has_projects'   => !empty($parsed['projects']),
            'has_education'  => !empty($parsed['education']),
        ]);

        return trim($resume);
    }

    /**
     * Enhance bullet points by replacing weak verb prefixes with strong action verbs.
     *
     * Checks each bullet (case-insensitively) against a map of weak verb phrases.
     * When a bullet starts with a weak verb, the prefix is replaced with the
     * corresponding strong verb. The rest of the bullet text is preserved exactly.
     *
     * @param  array $bullets   Array of bullet point strings to enhance.
     * @param  array $jobSkills Job-required skills (reserved for future keyword injection).
     * @return array            Enhanced bullets array with weak verbs replaced.
     *
     * Requirements: 5.1, 13.1
     */
    private function enhanceBulletPoints(array $bullets, array $jobSkills): array
    {
        $weakVerbs = [
            'worked on'       => 'Developed',
            'helped with'     => 'Contributed to',
            'did some'        => 'Implemented',
            'was responsible' => 'Led',
            'assisted in'     => 'Supported',
            'did'             => 'Executed',
        ];

        $enhanced = [];
        foreach ($bullets as $bullet) {
            $lower = strtolower($bullet);
            foreach ($weakVerbs as $weak => $strong) {
                if (str_starts_with($lower, $weak)) {
                    $bullet = $strong . ' ' . substr($bullet, strlen($weak));
                    break;
                }
            }
            $enhanced[] = $bullet;
        }

        return $enhanced;
    }

    /**
     * Parse the existing extracted resume text into named sections.
     * Returns: name, contact, apparent_role, skills[], experience[], education[], projects[]
     */
    private function parseExistingResume(string $text): array
    {
        $result = [
            'name'          => '',
            'contact'       => '',
            'apparent_role' => 'professional',
            'skills'        => [],
            'experience'    => [],
            'education'     => [],
            'projects'      => [],
            'sections'      => [],
        ];

        if (empty(trim($text))) return $result;

        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines, fn($l) => !empty($l));
        $lines = array_values($lines);

        // ── Detect name (first multi-word line that looks like a person's name) ──
        $skipWords = [
            'github', 'linkedin', 'portfolio', 'website', 'twitter', 'instagram',
            'facebook', 'resume', 'cv', 'curriculum', 'vitae', 'contact', 'profile',
        ];
        foreach (array_slice($lines, 0, 8) as $line) {
            $lower = strtolower(trim($line));
            // Skip if it's a known single social/tech word
            if (in_array($lower, $skipWords)) continue;
            // Skip URLs
            if (preg_match('/https?:\/\/|www\./i', $line)) continue;
            // Skip emails / phone numbers
            if (str_contains($line, '@') || preg_match('/\d{7,}/', $line)) continue;
            // Skip pipe-separated contact lines
            if (substr_count($line, '|') >= 2) continue;
            // Must be short (a name, not a sentence)
            if (strlen($line) < 50 && strlen($line) > 2) {
                $result['name'] = $line;
                break;
            }
        }

        // ── Detect contact line (email / phone) ──
        foreach (array_slice($lines, 0, 8) as $line) {
            if (str_contains($line, '@') || preg_match('/[\d\s\-+()]{9,}/', $line)) {
                $result['contact'] = $line;
                break;
            }
        }

        // ── Section detection patterns ──
        $sectionPatterns = [
            'skills'      => '/^(skills|core skills|technical skills|key skills|competencies)/i',
            'experience'  => '/^(experience|work experience|employment|professional experience|internship)/i',
            'education'   => '/^(education|academic|qualifications|degree)/i',
            'projects'    => '/^(projects|personal projects|academic projects|key projects)/i',
        ];

        $sectionBoundaries = []; // ['skills' => 5, 'experience' => 12, ...]
        foreach ($lines as $i => $line) {
            if (strlen($line) > 60) continue;
            foreach ($sectionPatterns as $key => $pat) {
                if (preg_match($pat, $line) && !isset($sectionBoundaries[$key])) {
                    $sectionBoundaries[$key] = $i;
                }
            }
        }

        // Sort by line number
        asort($sectionBoundaries);
        $sectionKeys  = array_keys($sectionBoundaries);
        $sectionStarts = array_values($sectionBoundaries);
        $total = count($lines);

        foreach ($sectionKeys as $idx => $key) {
            $start = $sectionStarts[$idx] + 1;
            $end   = isset($sectionStarts[$idx + 1]) ? $sectionStarts[$idx + 1] : $total;
            $block = array_slice($lines, $start, $end - $start);
            $blockText = implode("\n", $block);

            if ($key === 'skills') {
                $raw = preg_split('/[,\|\n•\-]+/', $blockText);
                foreach ($raw as $s) {
                    $s = trim($s);
                    if (strlen($s) > 1 && strlen($s) < 40) {
                        $result['skills'][] = $s;
                    }
                }
            } elseif ($key === 'experience') {
                $result['experience'] = $this->parseExperienceBlock($block);
                $result['sections']['experience'] = $result['experience'];
            } elseif ($key === 'education') {
                $result['education'] = $this->parseEducationBlock($block);
                $result['sections']['education'] = $result['education'];
            } elseif ($key === 'projects') {
                $result['projects'] = $this->parseProjectsBlock($block);
                $result['sections']['projects'] = $result['projects'];
            }
        }

        // ── Infer apparent role from text ──
        $roles = ['developer', 'engineer', 'analyst', 'designer', 'manager', 'consultant', 'intern', 'student'];
        $ltext = strtolower($text);
        foreach ($roles as $role) {
            if (str_contains($ltext, $role)) {
                $result['apparent_role'] = $role;
                break;
            }
        }

        return $result;
    }

    private function parseExperienceBlock(array $lines): array
    {
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $isBullet = str_starts_with($line, '•')
                     || str_starts_with($line, '-')
                     || str_starts_with($line, '*');

            // New entry: short line without bullet
            // Accept any case (lower, upper, mixed) — just not a bullet
            if (!$isBullet && strlen($line) < 100) {
                // If it looks like a date-only line, attach to current entry
                if ($current !== null && preg_match('/^(\d{4}|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)/i', $line)) {
                    if (empty($current['date'])) {
                        $current['date'] = $line;
                    }
                    continue;
                }
                if ($current) $entries[] = $current;
                $current = ['title' => $line, 'org' => $line, 'date' => '', 'location' => '', 'bullets' => []];
            } elseif ($current !== null) {
                $bullet = ltrim($line, '•-* ');
                if (!empty($bullet)) {
                    $current['bullets'][] = $bullet;
                }
            }
        }
        if ($current) $entries[] = $current;

        return array_slice($entries, 0, 4);
    }

    private function parseEducationBlock(array $lines): array
    {
        $entries = [];
        $i = 0;
        while ($i < count($lines)) {
            $line = trim($lines[$i]);
            if (empty($line)) { $i++; continue; }

            $entry = ['degree' => $line, 'school' => '', 'year' => ''];
            if (isset($lines[$i + 1])) $entry['school'] = trim($lines[$i + 1]);
            if (isset($lines[$i + 2]) && preg_match('/\d{4}/', $lines[$i + 2])) {
                $entry['year'] = trim($lines[$i + 2]);
                $i += 3;
            } else {
                $i += 2;
            }
            $entries[] = $entry;
        }
        return array_slice($entries, 0, 3);
    }

    private function parseProjectsBlock(array $lines): array
    {
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (!str_starts_with($line, '•') && !str_starts_with($line, '-') && strlen($line) < 70) {
                if ($current) $entries[] = $current;
                $current = ['title' => $line, 'bullets' => []];
            } elseif ($current) {
                $current['bullets'][] = ltrim($line, '•-* ');
            }
        }
        if ($current) $entries[] = $current;

        return array_slice($entries, 0, 4);
    }

    /**
     * Lazy-load OpenAI client.
     */
    private function getOpenAIClient(string $apiKey): OpenAI\Client
    {
        if ($this->openai === null) {
            $this->openai = OpenAI::client($apiKey);
        }
        return $this->openai;
    }

    /* ------------------------------------------------------------------ */
    /*  Helper Utilities                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Build a human-readable list of improvements made by the rewrite.
     */
    private function buildImprovementsList(array $before, array $after): array
    {
        $improvements = [];

        if ($after['skill_match'] > $before['skill_match']) {
            $improvements[] = '+ Added ' . ($after['skill_match'] - $before['skill_match']) . '% more matched skills';
        }

        if ($after['keyword_score'] > $before['keyword_score']) {
            $improvements[] = '+ Improved keyword coverage by ' . ($after['keyword_score'] - $before['keyword_score']) . '%';
        }

        if ($after['format_score'] > $before['format_score']) {
            $improvements[] = '+ Enhanced ATS-friendly formatting';
        }

        $newSkills = array_diff($after['matching_skills'], $before['matching_skills']);
        if (!empty($newSkills)) {
            $improvements[] = '+ Added missing keywords: ' . implode(', ', array_slice($newSkills, 0, 3));
        }

        if (count($after['issues']) < count($before['issues'])) {
            $improvements[] = '+ Resolved ' . (count($before['issues']) - count($after['issues'])) . ' resume issues';
        }

        $improvements[] = '+ Rewrote bullet points with measurable impact language';
        $improvements[] = '+ Better alignment with job role requirements';

        return $improvements;
    }

    /**
     * Empty score response for edge cases (no resume, parse failure, etc.).
     */
    private function emptyScore(string $reason = ''): array
    {
        return [
            'score'          => 0,
            'skill_match'    => 0,
            'keyword_score'  => 0,
            'format_score'   => 0,
            'matching_skills' => [],
            'missing_skills'  => [],
            'issues'          => [$reason ?: 'Unable to analyse resume'],
            'strengths'       => [],
            'tier'            => 'low',
            'gate_message'    => '🔴 Unable to score resume — ' . $reason,
            'badge_class'     => 'bg-red-100 text-red-800',
            'resume_text'     => '',
            'overall_score'   => 0,
        ];
    }

    /**
     * Save an original version snapshot (once per user+job combo).
     */
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

    /**
     * Validate the AI-rewritten resume output against quality gates.
     *
     * Checks:
     *   1. All four required section headers are present.
     *   2. Word count does not exceed 700 (580-word target + 20% buffer for parsing variance).
     *   3. No PDF binary artifacts are present in the output.
     *
     * NOTE: The job-title-in-summary check was removed — GPT sometimes paraphrases
     * the title (e.g. "Backend Engineer" → "backend engineering role") which is
     * semantically correct but fails a literal string match, causing unnecessary
     * fallback to the weaker rule-based rewrite.
     *
     * Returns an array of issue strings; an empty array means the output is valid.
     *
     * Requirements: 9.1, 9.2
     */
    private function validateRewriteOutput(string $text, Internship $internship): array
    {
        $issues = [];
        $lower  = strtolower($text);

        // 1. Required sections present — check for at least 3 of the 4 core sections
        //    (some resumes legitimately omit certifications or have combined sections)
        $requiredSections = ['experience', 'education', 'skills'];
        foreach ($requiredSections as $section) {
            if (!str_contains($lower, $section)) {
                $issues[] = "Missing required section: {$section}";
            }
        }

        // 2. Word count sanity check — reject if absurdly long (likely hallucination)
        $wordCount = str_word_count($text);
        if ($wordCount > 700) {
            $issues[] = "Word count {$wordCount} exceeds 700-word safety limit";
        }

        // 3. Minimum content check — reject if suspiciously short
        if ($wordCount < 80) {
            $issues[] = "Word count {$wordCount} is too short — output appears incomplete";
        }

        // 4. No PDF binary artifacts
        if (preg_match('/%PDF-|endobj|xref|%%EOF/', $text)) {
            $issues[] = 'Output contains PDF binary artifacts';
        }

        return $issues;
    }

    /**
     * Strip PDF binary artefacts from rewritten text before storing to DB.
     * Ensures the stored content is always clean, human-readable plain text.
     */
    private function sanitizeForStorage(string $text): string
    {
        // Remove PDF header and binary object streams
        $text = preg_replace('/%PDF-[\d.]+[\s\S]*?(?=PROFESSIONAL|SUMMARY|OBJECTIVE|SKILLS|EXPERIENCE|EDUCATION|\z)/i', '', $text);
        $text = preg_replace('/\d+\s+\d+\s+obj[\s\S]*?endobj/i', '', $text);
        $text = preg_replace('/xref[\s\S]*?%%EOF/i', '', $text);
        $text = preg_replace('/<<[^>]{0,300}>>/s', '', $text);
        $text = preg_replace('/<[0-9a-fA-F\s]{4,}>/m', '', $text);
        // Strip non-printable characters (keep tabs, newlines)
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
        // Collapse excessive whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/(\r?\n){3,}/', "\n\n", $text);
        return trim($text);
    }
}
