<?php

namespace App\Services;

use App\Models\Internship;
use App\Models\ResumeVersion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * ResumePdfService
 *
 * Converts plain-text resume content (from AI rewrite or original)
 * into a professionally formatted, downloadable PDF.
 *
 * Architecture:
 *   1. parseResumeText()  — splits plain text into structured sections
 *   2. buildPdfData()     — enriches with user profile + internship context
 *   3. generatePdf()      — renders via Dompdf → download response
 */
class ResumePdfService
{
    /* ------------------------------------------------------------------ */
    /*  Public Entry Point                                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Generate and return a PDF download response for a given resume version.
     *
     * @param  User        $user
     * @param  Internship  $internship
     * @param  int|null    $versionId   — if null, uses the latest version for this user+job
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf(User $user, Internship $internship, ?int $versionId = null): Response
    {
        // 1. Fetch the resume version content
        if ($versionId) {
            $version = ResumeVersion::where('id', $versionId)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            // Latest AI rewrite for this user+job
            $version = ResumeVersion::where('user_id', $user->id)
                ->where('internship_id', $internship->id)
                ->where('type', 'ai_rewrite')
                ->latest()
                ->first();
        }

        if (!$version) {
            abort(404, 'Resume version not found. Please run the AI rewrite first.');
        }

        // 2. Build structured data from plain text
        $data = $this->buildPdfData($user, $internship, $version->content);

        // 3. Render PDF via Dompdf
        $pdf = Pdf::loadView('resume.pdf-template', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('defaultMediaType', 'print')
            ->setOption('fontHeightRatio', 1.1);

        $filename = $this->buildFilename($user, $internship);

        Log::info('ResumePdf: PDF generated', [
            'user_id'       => $user->id,
            'internship_id' => $internship->id,
            'version_id'    => $version->id,
            'filename'      => $filename,
        ]);

        return $pdf->download($filename);
    }

    /* ------------------------------------------------------------------ */
    /*  Data Builder                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Build the full view-data array for the PDF template.
     */
    private function buildPdfData(User $user, Internship $internship, string $resumeText): array
    {
        $profile  = $user->profile;
        $sections = $this->parseResumeText($resumeText, $internship);

        // ── Content sanitization before rendering ──
        $sections = $this->sanitizeSections($sections);

        // Derive contact details from profile
        $name     = $profile?->name ?? $user->name ?? 'Candidate';
        $email    = $user->email ?? '';
        $phone    = $this->extractPhone($resumeText) ?: '';
        $location = $profile?->location ?? $this->extractLocation($resumeText) ?? '';

        // Target role — the internship title they optimised for
        $targetRole = $internship->title . ' — ' . $internship->organization;

        return compact('name', 'email', 'phone', 'location', 'targetRole', 'sections', 'internship');
    }

    /* ------------------------------------------------------------------ */
    /*  Plain-Text Resume Parser                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Parse a plain-text resume into structured sections for the PDF template.
     *
     * Handles resumes that use common section headers like SUMMARY, EXPERIENCE,
     * SKILLS, EDUCATION, PROJECTS, CERTIFICATIONS (case-insensitive).
     *
     * Returns an associative array:
     *   summary        => string
     *   skills         => string[]
     *   experience     => [{title, org, date, location, bullets[]}]
     *   projects       => [{title, tech, date, bullets[]}]
     *   education      => [{degree, school, meta}]
     *   certifications => string[]
     */
    private function parseResumeText(string $text, Internship $internship): array
    {
        $sections = [
            'summary'        => '',
            'skills'         => [],
            'experience'     => [],
            'projects'       => [],
            'education'      => [],
            'certifications' => [],
        ];

        // ── Normalise line endings ──
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);

        // ── Identify section boundaries ──
        $sectionMap = $this->detectSections($lines);

        // ── Extract each section ──
        foreach ($sectionMap as $sectionKey => $lineRange) {
            [$start, $end] = $lineRange;
            $sectionLines  = array_slice($lines, $start, $end - $start);
            $sectionText   = trim(implode("\n", $sectionLines));

            switch ($sectionKey) {
                case 'summary':
                    $sections['summary'] = $this->cleanText($sectionText);
                    break;

                case 'skills':
                    $sections['skills'] = $this->parseSkills($sectionText, $internship);
                    break;

                case 'experience':
                    $sections['experience'] = $this->parseEntries($sectionText);
                    break;

                case 'projects':
                    $sections['projects'] = $this->parseProjects($sectionText);
                    break;

                case 'education':
                    $sections['education'] = $this->parseEducation($sectionText);
                    break;

                case 'certifications':
                    $sections['certifications'] = $this->parseCertifications($sectionText);
                    break;
            }
        }

        // ── Fallback: if nothing parsed properly, build from the whole text ──
        if (empty($sections['summary'])) {
            $sections['summary'] = $this->buildFallbackSummary($text, $internship);
        }
        if (empty($sections['skills'])) {
            $sections['skills'] = $this->buildFallbackSkills($text, $internship);
        }

        return $sections;
    }

    /**
     * Find section header positions in the line array.
     * Returns ['summary' => [startLine, endLine], ...]
     */
    private function detectSections(array $lines): array
    {
        $patterns = [
            'summary'        => '/^(professional\s+summary|summary|objective|profile|about\s+me)/i',
            'experience'     => '/^(work\s+experience|experience|employment|professional\s+experience)/i',
            'skills'         => '/^(skills|core\s+skills|technical\s+skills|key\s+skills|competencies|technologies)/i',
            'projects'       => '/^(projects?|personal\s+projects?|academic\s+projects?|key\s+projects?|portfolio)/i',
            'education'      => '/^(education|academic\s+background|qualifications)/i',
            'certifications' => '/^(certifications?|courses?|training|achievements?|awards?)/i',
        ];

        $found = []; // ['sectionKey' => lineNumber]

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || strlen($trimmed) > 60) continue;

            foreach ($patterns as $key => $pattern) {
                if (preg_match($pattern, $trimmed) && !isset($found[$key])) {
                    $found[$key] = $i + 1; // content starts next line
                }
            }
        }

        if (empty($found)) {
            return [];
        }

        // Sort by line number
        asort($found);

        // Build [start, end] ranges
        $result = [];
        $keys   = array_keys($found);
        $vals   = array_values($found);
        $total  = count($lines);

        for ($i = 0; $i < count($keys); $i++) {
            $start = $vals[$i];
            $end   = isset($vals[$i + 1]) ? $vals[$i + 1] - 1 : $total;
            $result[$keys[$i]] = [$start, $end];
        }

        return $result;
    }

    /* ------------------------------------------------------------------ */
    /*  Section-specific parsers                                            */
    /* ------------------------------------------------------------------ */

    private function parseSkills(string $text, Internship $internship): array
    {
        // Check if the text uses grouped format: "Languages: PHP, Python | Frameworks: Laravel"
        // If so, return the groups as-is to be rendered in the template
        $hasCategories = preg_match(
            '/\b(Languages|Frameworks|Tools|Databases|Cloud|DevOps|Libraries|Platforms)\s*:/i',
            $text
        );

        if ($hasCategories) {
            // Return each category group as one element
            $groups = preg_split('/\s*[|\n]\s*/', $text);
            $result = [];
            foreach ($groups as $g) {
                $g = trim($g, ' ,•-');
                if (strlen($g) > 3) {
                    $result[] = $g;
                }
            }
            return $result;
        }

        // Flat list: split by common separators
        $headerWords = [
            'PROFESSIONAL', 'SUMMARY', 'OBJECTIVE', 'EXPERIENCE', 'EDUCATION',
            'SKILLS', 'PROJECTS', 'CERTIFICATIONS', 'PROFILE', 'ABOUT',
            'CONTACT', 'REFERENCES', 'ACHIEVEMENTS', 'LANGUAGES', 'HOBBIES',
        ];
        $raw    = preg_split('/[,•\-\|\/\n]+/', $text);
        $skills = [];
        foreach ($raw as $s) {
            $s = trim($s);
            if (strlen($s) > 1 && strlen($s) < 45 && !in_array(strtoupper($s), $headerWords)) {
                $skills[] = $s;
            }
        }

        // Merge extracted skills with required job skills (job skills first for ATS)
        $jobSkills = is_array($internship->required_skills)
            ? $internship->required_skills
            : (is_string($internship->required_skills) && !empty($internship->required_skills)
                ? array_map('trim', explode(',', $internship->required_skills))
                : []);

        // Only prepend job skills not already in the extracted list
        $skillsLower = array_map('strtolower', $skills);
        $toAdd = [];
        foreach ($jobSkills as $js) {
            if (!in_array(strtolower($js), $skillsLower)) {
                $toAdd[] = $js;
            }
        }

        $merged = array_merge($toAdd, $skills);
        return array_values(array_filter(
            array_unique($merged),
            fn($s) => !empty(trim($s))
        ));
    }

    private function parseEntries(string $text): array
    {
        $entries = [];

        // Split by one or more blank lines — each block is one job entry
        $blocks = preg_split('/\n\s*\n/', trim($text));

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;

            $lines = array_values(array_filter(
                array_map('trim', explode("\n", $block))
            ));
            if (empty($lines)) continue;

            $org      = '';
            $title    = '';
            $date     = '';
            $location = '';
            $bullets  = [];
            $lineIdx  = 0;

            // ── Line 0: could be pipe-separated OR "Company MonthYear" merged ──
            $firstLine = $lines[0];

            if (substr_count($firstLine, '|') >= 1) {
                // Pipe-separated: "Company | Role | Date | Location"
                $parts    = array_map('trim', explode('|', $firstLine));
                $org      = $parts[0] ?? '';
                $title    = $parts[1] ?? '';
                $date     = $parts[2] ?? '';
                $location = $parts[3] ?? '';
                $lineIdx  = 1;
            } else {
                // Try to extract a date range from the end of the line
                // Matches: "Aug 2025 – Present", "Jun 2025 - Sep 2025", "2024 – 2025"
                $datePattern = '/\s+(' .
                    '(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4}' .
                    '(?:\s*[-–—]\s*(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4}|' .
                    '\s*[-–—]\s*[Pp]resent)?' .
                    '|\d{4}\s*[-–—]\s*(?:\d{4}|[Pp]resent)' .
                    ')$/';

                if (preg_match($datePattern, $firstLine, $m, PREG_OFFSET_CAPTURE)) {
                    $org  = trim(substr($firstLine, 0, $m[0][1]));
                    $date = trim($m[1][0]);
                } else {
                    $org = $firstLine;
                }
                $lineIdx = 1;

                // ── Line 1: role/title (+ optional location) ──
                if (isset($lines[1])) {
                    $line2 = $lines[1];
                    $isBullet = str_starts_with($line2, '•')
                             || str_starts_with($line2, '-')
                             || str_starts_with($line2, '*');

                    if (!$isBullet && strlen($line2) < 100) {
                        // Extract location keyword from end
                        $locPattern = '/\s+(Remote|On-?site|Hybrid|Bangalore|Bengaluru|Mumbai|Delhi|Pune' .
                                      '|Noida|Gurugram|Hyderabad|Chennai|Kolkata|USA|US|India|UK|Dubai)\s*$/i';
                        if (preg_match($locPattern, $line2, $lm)) {
                            $location = trim($lm[1]);
                            $title    = trim(str_ireplace($lm[0], '', $line2));
                        } else {
                            $title = $line2;
                        }
                        $lineIdx = 2;
                    }
                }
            }

            // ── Remaining lines: bullets ──
            for ($i = $lineIdx; $i < count($lines); $i++) {
                $bullet = ltrim($lines[$i], '•-* ');
                if (!empty($bullet) && strlen($bullet) > 5) {
                    $bullets[] = $bullet;
                }
            }

            if (!empty($org)) {
                $entries[] = compact('org', 'title', 'date', 'location', 'bullets');
            }
        }

        return array_slice($entries, 0, 5);
    }


    private function parseProjects(string $text): array
    {
        $projects = [];

        // Split by blank lines — each project is a separate block
        $blocks = preg_split('/\n\s*\n/', trim($text));

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;

            $lines = array_values(array_filter(array_map('trim', explode("\n", $block))));
            if (empty($lines)) continue;

            $title   = '';
            $tech    = '';
            $date    = '';
            $bullets = [];

            // Line 0: Title (+ optional tech stack after |)
            $firstLine = $lines[0];
            if (substr_count($firstLine, '|') >= 1) {
                $parts = array_map('trim', explode('|', $firstLine));
                $title = $parts[0];
                $tech  = $parts[1] ?? '';
                $date  = $parts[2] ?? '';
            } else {
                // Try to split off a date
                if (preg_match('/(\d{4}(?:\s*[-–]\s*(?:\d{4}|[Pp]resent))?)\s*$/', $firstLine, $dm)) {
                    $title = trim(str_replace($dm[0], '', $firstLine));
                    $date  = trim($dm[1]);
                } else {
                    $title = $firstLine;
                }
            }

            // Remaining lines: tech stack detection or bullets
            for ($i = 1; $i < count($lines); $i++) {
                $line = $lines[$i];
                $isBullet = str_starts_with($line, '•')
                         || str_starts_with($line, '-')
                         || str_starts_with($line, '*');

                if (!$isBullet && empty($tech) && strlen($line) < 120 &&
                    preg_match('/\b(React|Vue|Angular|Node|Next|Python|Java|PHP|Laravel|Django|Flask|MongoDB|MySQL|PostgreSQL|AWS|Docker|Git|TypeScript|JavaScript|Express|Redis|GraphQL|REST)\b/i', $line)) {
                    $tech = $line;
                } else {
                    $bullet = ltrim($line, '•-* ');
                    if (!empty($bullet) && strlen($bullet) > 5) {
                        $bullets[] = $bullet;
                    }
                }
            }

            if (!empty($title)) {
                $projects[] = compact('title', 'tech', 'date', 'bullets');
            }
        }

        return array_slice($projects, 0, 4);
    }

    private function parseEducation(string $text): array
    {
        $edu    = [];
        $blocks = preg_split('/\n\s*\n/', trim($text));

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;
            $lines = array_values(array_filter(array_map('trim', explode("\n", $block))));
            if (empty($lines)) continue;
            $entry = $this->buildEduEntry($lines);
            if ($entry) $edu[] = $entry;
        }

        // Fallback: if blank-line splitting gave nothing, parse line by line
        if (empty($edu)) {
            $lines = array_filter(array_map('trim', explode("\n", $text)));
            $chunk = [];
            foreach ($lines as $line) {
                if (empty($line)) {
                    if (!empty($chunk)) { $edu[] = $this->buildEduEntry($chunk); $chunk = []; }
                } else {
                    $chunk[] = $line;
                }
            }
            if (!empty($chunk)) $edu[] = $this->buildEduEntry($chunk);
        }

        return array_slice(array_filter($edu), 0, 3);
    }

    private function buildEduEntry(array $lines): ?array
    {
        if (empty($lines)) return null;

        $degree = '';
        $school = '';
        $year   = '';
        $extras = [];

        // First line may be "School Name | Degree" or "Degree, School"
        $first = $lines[0];
        if (substr_count($first, '|') >= 1) {
            $parts  = array_map('trim', explode('|', $first));
            $school = $parts[0];
            $degree = $parts[1] ?? '';
            // Remaining pipe parts may be year/CGPA
            for ($i = 2; $i < count($parts); $i++) {
                if (preg_match('/\d{4}/', $parts[$i])) {
                    $year = trim($parts[$i]);
                } else {
                    $extras[] = trim($parts[$i]);
                }
            }
        } else {
            $degree = $first;
        }

        // Scan remaining lines for school name, year, CGPA
        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            // Year range detection: "2020 – 2024" or "2022 2026"
            if (preg_match('/(\d{4}\s*[-–—]\s*(?:\d{4}|[Pp]resent)|\d{4}\s+\d{4}|\d{4})/i', $line)) {
                if (empty($year)) $year = trim($line);
                else $extras[] = trim($line);
            } elseif (preg_match('/cgpa|gpa|grade|percentage|%/i', $line)) {
                $extras[] = trim($line);
            } elseif (empty($school)) {
                $school = trim($line);
            } else {
                $extras[] = trim($line);
            }
        }

        $meta = trim(implode(' | ', array_filter(array_merge([$year], $extras))));

        return [
            'degree' => $degree,
            'school' => $school,
            'meta'   => $meta,
        ];
    }

    private function parseCertifications(string $text): array
    {
        $certs = [];
        foreach (explode("\n", $text) as $line) {
            $line = ltrim(trim($line), '•-*✓ ');
            if (strlen($line) > 5 && strlen($line) < 120) {
                $certs[] = $line;
            }
        }
        return array_slice($certs, 0, 6);
    }

    /* ------------------------------------------------------------------ */
    /*  Fallback builders (when section parsing finds nothing)              */
    /* ------------------------------------------------------------------ */

    private function buildFallbackSummary(string $text, Internship $internship): string
    {
        // Take first meaningful paragraph
        $paragraphs = array_filter(explode("\n\n", $text));
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (strlen($para) > 80 && strlen($para) < 600) {
                return $this->cleanText($para);
            }
        }
        return "Motivated professional seeking the {$internship->title} position at {$internship->organization}, "
             . "with a passion for delivering measurable impact through hands-on project experience.";
    }

    private function buildFallbackSkills(string $text, Internship $internship): array
    {
        $skills = $internship->required_skills ?? [];
        // Extract capitalized words as potential tech skills
        preg_match_all('/\b([A-Z][a-zA-Z+#.]{1,20})\b/', $text, $m);
        $techWords = array_unique($m[1] ?? []);
        $stopWords = ['The', 'A', 'An', 'In', 'At', 'For', 'And', 'Or', 'Is', 'Are', 'Be', 'Will', 'With', 'From', 'On'];
        $techWords = array_filter($techWords, fn($w) => !in_array($w, $stopWords));

        return array_unique(array_merge($skills, array_slice(array_values($techWords), 0, 12)));
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function cleanText(string $text): string
    {
        // Remove PDF binary artifacts
        $text = preg_replace('/%PDF-[\d.]+.*$/m', '', $text);
        $text = preg_replace('/\d+\s+\d+\s+obj[\s\S]*?endobj/i', '', $text);
        $text = preg_replace('/<<[^>]{0,200}>>/s', '', $text);
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
        return trim(preg_replace('/\s{2,}/', ' ', $text));
    }

    private function extractPhone(string $text): string
    {
        if (preg_match('/(?:\+91[\s-]?)?[6-9]\d{9}|\+?\d[\d\s\-().]{8,14}\d/', $text, $m)) {
            return trim($m[0]);
        }
        return '';
    }

    private function extractLocation(string $text): string
    {
        // Common Indian cities
        $cities = ['Mumbai', 'Delhi', 'Bangalore', 'Bengaluru', 'Hyderabad', 'Chennai', 'Kolkata',
                   'Pune', 'Ahmedabad', 'Jaipur', 'Lucknow', 'Noida', 'Gurugram', 'Gurgaon'];
        foreach ($cities as $city) {
            if (stripos($text, $city) !== false) return $city;
        }
        return '';
    }

    private function buildFilename(User $user, Internship $internship): string
    {
        $name = preg_replace('/[^a-zA-Z0-9]/', '_', $user->name ?? 'Resume');
        $job  = preg_replace('/[^a-zA-Z0-9]/', '_', $internship->title);
        return "AI_Optimised_{$name}_{$job}.pdf";
    }

    /* ------------------------------------------------------------------ */
    /*  Content Sanitizer — runs before PDF rendering                       */
    /* ------------------------------------------------------------------ */

    /**
     * Clean up parsed sections to remove junk content, oversized text,
     * and non-renderable lines before the PDF template receives the data.
     */
    private function sanitizeSections(array $sections): array
    {
        // ── 1. Summary: cap at 2 sentences / 300 chars ──────────────────
        if (!empty($sections['summary'])) {
            $summary = $sections['summary'];
            // Remove PDF junk
            $summary = preg_replace('/https?:\/\/\S+/i', '', $summary);
            $summary = preg_replace('/\s{2,}/', ' ', $summary);
            // Limit to 2 sentences
            $sentences = preg_split('/(?<=[.!?])\s+/', $summary);
            $summary   = implode(' ', array_slice($sentences, 0, 2));
            // Hard cap at 300 chars
            if (strlen($summary) > 300) {
                $summary = wordwrap($summary, 300, "\n", true);
                $summary = explode("\n", $summary)[0] . '…';
            }
            $sections['summary'] = trim($summary);
        }

        // ── 2. Skills: remove URLs, junk items, duplicates ──────────────
        if (!empty($sections['skills'])) {
            $junkPatterns = [
                '/https?:\/\//i',          // URLs
                '/^(link|github|demo|live|portfolio|website|www\.)/i',
                '/^[^a-zA-Z]+$/',          // no alpha chars at all
            ];
            $cleaned = [];
            foreach ($sections['skills'] as $skill) {
                $skill = trim($skill);
                if (strlen($skill) < 2 || strlen($skill) > 50) continue;
                $bad = false;
                foreach ($junkPatterns as $p) {
                    if (preg_match($p, $skill)) { $bad = true; break; }
                }
                if (!$bad) $cleaned[] = $skill;
            }
            $sections['skills'] = array_values(array_unique($cleaned));
        }

        // ── 3. Experience: trim bullets, strip junk lines ────────────────
        if (!empty($sections['experience'])) {
            $junkBulletPatterns = [
                '/^(link|github|http|www\.|live demo|see more|click|view)/i',
                '/^[^a-zA-Z]+$/',
            ];
            foreach ($sections['experience'] as &$exp) {
                // Layout Intelligence: Max 3 bullets, 140 chars each (fits in 2 lines)
                $exp['bullets'] = $this->sanitizeBullets($exp['bullets'] ?? [], $junkBulletPatterns, 3, 140);
            }
            unset($exp);
        }

        // ── 4. Projects: trim bullets, strip "Links:" lines ─────────────
        if (!empty($sections['projects'])) {
            $junkBulletPatterns = [
                '/^(link|github|http|www\.|live demo|see more|click|view)/i',
                '/^[^a-zA-Z]+$/',
            ];
            foreach ($sections['projects'] as &$proj) {
                // Remove junk from title
                $proj['title'] = preg_replace('/\s*(links?|github|live demo)\s*:?.*/i', '', $proj['title']);
                $proj['title'] = trim($proj['title']);
                // Clean tech string
                if (!empty($proj['tech'])) {
                    $proj['tech'] = preg_replace('/https?:\/\/\S+/i', '', $proj['tech']);
                    $proj['tech'] = trim($proj['tech']);
                }
                // Layout Intelligence: Max 2 bullets, 140 chars each
                $proj['bullets'] = $this->sanitizeBullets($proj['bullets'] ?? [], $junkBulletPatterns, 2, 140);
            }
            unset($proj);
            // Remove projects with no content
            $sections['projects'] = array_values(array_filter(
                $sections['projects'],
                fn($p) => !empty(trim($p['title'] ?? ''))
            ));
        }

        // ── 5. Certifications: strip junk, cap at 3 ─────────────────────
        if (!empty($sections['certifications'])) {
            $cleaned = [];
            foreach ($sections['certifications'] as $cert) {
                $cert = preg_replace('/https?:\/\/\S+/i', '', $cert);
                $cert = trim($cert);
                if (strlen($cert) > 5 && strlen($cert) < 150) {
                    $cleaned[] = $cert;
                }
            }
            // Layout Intelligence: Cap at 3 certifications
            $sections['certifications'] = array_slice($cleaned, 0, 3);
        }

        return $sections;
    }

    /**
     * Sanitize a list of bullet strings:
     * - Remove junk lines matching $junkPatterns
     * - Trim bullets to $maxChars
     * - Limit to $maxCount bullets
     */
    private function sanitizeBullets(array $bullets, array $junkPatterns, int $maxCount, int $maxChars): array
    {
        $clean = [];
        foreach ($bullets as $bullet) {
            $bullet = trim($bullet);
            if (strlen($bullet) < 8) continue;

            $bad = false;
            foreach ($junkPatterns as $p) {
                if (preg_match($p, $bullet)) { $bad = true; break; }
            }
            if ($bad) continue;

            // Trim to maxChars at a word boundary
            if (strlen($bullet) > $maxChars) {
                $bullet = wordwrap($bullet, $maxChars, "\n", true);
                $bullet = explode("\n", $bullet)[0];
                // Don't end mid-word awkwardly — add ellipsis only if sentence incomplete
                if (!preg_match('/[.!?]$/', $bullet)) $bullet .= '…';
            }

            $clean[] = $bullet;
            if (count($clean) >= $maxCount) break;
        }
        return $clean;
    }
}
