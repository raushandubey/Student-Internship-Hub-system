<?php

namespace App\Services;

use App\Models\Internship;
use App\Models\ResumeVersion;
use App\Models\User;
use App\Services\Resume\ResumeParserEngine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * ResumePdfService
 *
 * Converts AI-rewritten resume text into a professionally formatted PDF.
 * Uses the fixed ATS template — AI generates content only, formatting is fixed.
 */
class ResumePdfService
{
    public function __construct(
        private readonly ResumeParserEngine $parser,
        private readonly \App\Services\Resume\LatexTemplateEngine $latexEngine,
    ) {}

    /* ------------------------------------------------------------------ */
    /*  Public Entry Point                                                  */
    /* ------------------------------------------------------------------ */

    public function downloadPdf(User $user, Internship $internship, ?int $versionId = null)
    {
        // Fetch the resume version
        if ($versionId) {
            $version = ResumeVersion::where('id', $versionId)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            $version = ResumeVersion::where('user_id', $user->id)
                ->where('internship_id', $internship->id)
                ->where('type', 'ai_rewrite')
                ->latest()
                ->first();
        }

        if (!$version) {
            abort(404, 'No AI-optimised resume found. Please run the Resume Optimizer first.');
        }

        $data = $this->buildPdfData($user, $internship, $version->content);
        $filename = $this->buildFilename($user, $internship);

        // ── Enterprise LaTeX Rendering (Primary) ──────────────────────
        $pdfBinary = $this->latexEngine->generatePdf($data);

        if ($pdfBinary) {
            Log::info('ResumePdf: LaTeX PDF generated successfully', [
                'user_id'       => $user->id,
                'internship_id' => $internship->id,
                'version_id'    => $version->id,
            ]);

            return response($pdfBinary)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        // ── DomPDF Rendering (Safe Fallback) ──────────────────────────
        Log::warning('ResumePdf: LaTeX engine failed. Activating DomPDF safe fallback.');
        
        $pdf = Pdf::loadView('resume.pdf-template', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('defaultMediaType', 'print')
            ->setOption('fontHeightRatio', 1.1)
            ->setOption('chroot', base_path());

        return $pdf->download($filename);
    }

    /* ------------------------------------------------------------------ */
    /*  Data Builder                                                        */
    /* ------------------------------------------------------------------ */

    private function buildPdfData(User $user, Internship $internship, string $resumeText): array
    {
        // Use the parser to get structured sections from the rewritten text
        $sections = $this->parseResumeTextToSections($resumeText, $internship);
        $sections = $this->sanitizeSections($sections);

        $profile  = $user->profile;
        $name     = $profile?->name ?? $user->name ?? 'Candidate';
        $email    = $user->email ?? '';
        $phone    = $this->extractPhone($resumeText) ?: ($profile?->phone ?? '');
        $location = $profile?->location ?? $this->extractLocation($resumeText) ?? '';
        $links    = $this->extractLinks($resumeText);

        $targetRole = $internship->title . ' — ' . $internship->organization;

        return compact('name', 'email', 'phone', 'location', 'links', 'targetRole', 'sections', 'internship');
    }

    /* ------------------------------------------------------------------ */
    /*  Text → Structured Sections Parser                                  */
    /* ------------------------------------------------------------------ */

    private function parseResumeTextToSections(string $text, Internship $internship): array
    {
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Already structured by AI Engine
            return [
                'summary'        => $decoded['summary'] ?? '',
                'skills'         => $decoded['skills'] ?? [],
                'experience'     => $decoded['experience'] ?? [],
                'projects'       => $decoded['projects'] ?? [],
                'education'      => $decoded['education'] ?? [],
                'certifications' => $decoded['certifications'] ?? [],
            ];
        }

        \Illuminate\Support\Facades\Log::warning('ResumePdf: Received raw text instead of JSON, applying legacy regex parser.');

        $sections = [
            'summary'        => '',
            'skills'         => [],
            'experience'     => [],
            'projects'       => [],
            'education'      => [],
            'certifications' => [],
        ];

        // Strip AI labelling artifacts before parsing
        $text = preg_replace('/\[(LOCKED|WEAK)[^\]]*\]\s*/i', '', $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Collapse 3+ blank lines into 2
        $text  = preg_replace('/(\n\s*){3,}/', "\n\n", $text);
        $lines = explode("\n", $text);

        // Detect section boundaries
        $sectionMap = $this->detectSections($lines);

        foreach ($sectionMap as $key => [$start, $end]) {
            $block     = array_slice($lines, $start, $end - $start);
            $blockText = trim(implode("\n", $block));

            switch ($key) {
                case 'summary':
                    $sections['summary'] = $this->cleanText($blockText);
                    break;
                case 'skills':
                    $sections['skills'] = $this->parseSkills($blockText, $internship);
                    break;
                case 'experience':
                    $sections['experience'] = $this->parseEntries($blockText);
                    break;
                case 'projects':
                    $sections['projects'] = $this->parseProjects($blockText);
                    break;
                case 'education':
                    $sections['education'] = $this->parseEducation($blockText);
                    break;
                case 'certifications':
                    $sections['certifications'] = $this->parseCertifications($blockText);
                    break;
            }
        }

        if (empty($sections['experience'])) {
            $sections['experience'] = $this->parseEntries($text);
        }

        if (empty($sections['summary'])) {
            $paragraphs = array_filter(explode("\n\n", $text));
            foreach ($paragraphs as $para) {
                $para = trim($para);
                if (strlen($para) > 40 && !preg_match('/^[A-Z\s]{3,30}$/', $para)) {
                    $sections['summary'] = $this->cleanText($para);
                    break;
                }
            }
            if (empty($sections['summary'])) {
                $sections['summary'] = "Professional seeking the {$internship->title} role at {$internship->organization}.";
            }
        }
        if (empty($sections['skills'])) {
            $sections['skills'] = $internship->required_skills ?? [];
        }

        return $sections;
    }


    private function detectSections(array $lines): array
    {
        $patterns = [
            'summary'        => '/^(professional\s+summary|career\s+summary|summary|objective|profile|about\s+me|about)/i',
            'skills'         => '/^(technical\s+skills|core\s+skills|skills|competencies|technologies|tech\s+stack)/i',
            'experience'     => '/^(professional\s+experience|work\s+experience|experience|employment|internship)/i',
            'projects'       => '/^(projects?|personal\s+projects?|academic\s+projects?|key\s+projects?|portfolio)/i',
            'education'      => '/^(education|academic\s+background|qualifications?)/i',
            'certifications' => '/^(certifications?|courses?|training|achievements?|awards?)/i',
        ];

        $found = [];
        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || strlen($trimmed) > 65) continue;

            foreach ($patterns as $key => $pattern) {
                if (preg_match($pattern, $trimmed) && !isset($found[$key])) {
                    $found[$key] = $i + 1;
                }
            }
        }

        if (empty($found)) return [];

        asort($found);
        $result = [];
        $keys   = array_keys($found);
        $vals   = array_values($found);
        $total  = count($lines);

        for ($i = 0; $i < count($keys); $i++) {
            $result[$keys[$i]] = [$vals[$i], isset($vals[$i + 1]) ? $vals[$i + 1] - 1 : $total];
        }

        return $result;
    }

    /* ------------------------------------------------------------------ */
    /*  Section Parsers                                                     */
    /* ------------------------------------------------------------------ */

    private function parseSkills(string $text, Internship $internship): array
    {
        if (preg_match('/\b(Languages|Frameworks|Tools|Databases|Cloud|DevOps)\s*:/i', $text)) {
            $groups = preg_split('/\s*[\|\n]\s*/', $text);
            return array_values(array_filter(
                array_map(fn($g) => trim($g, ' ,•-'), $groups),
                fn($g) => strlen($g) > 3
            ));
        }

        $headerWords = ['PROFESSIONAL','SUMMARY','OBJECTIVE','EXPERIENCE','EDUCATION','SKILLS','PROJECTS','CERTIFICATIONS'];
        $raw    = preg_split('/[,•\-\|\/\n]+/', $text);
        $skills = [];
        foreach ($raw as $s) {
            $s = trim($s);
            if (strlen($s) > 1 && strlen($s) < 45 && !in_array(strtoupper($s), $headerWords)) {
                $skills[] = $s;
            }
        }

        $jobSkills = is_array($internship->required_skills) ? $internship->required_skills : [];
        $skillsLower = array_map('strtolower', $skills);
        $toAdd = [];
        foreach ($jobSkills as $js) {
            if (!in_array(strtolower($js), $skillsLower)) $toAdd[] = $js;
        }

        return array_values(array_unique(array_merge($toAdd, $skills)));
    }

    private function parseEntries(string $text): array
    {
        $entries = [];

        // Normalise line endings and collapse 3+ blank lines
        $text  = preg_replace('/(\n\s*){3,}/', "\n\n", $text);
        $lines = array_map('trim', explode("\n", $text));

        $current = null;
        $datePattern = '/(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4}(?:\s*[-\x{2013}\x{2014}]\s*(?:\w+\.?\s+)?\d{4}|\s*[-\x{2013}\x{2014}]\s*Present)?|\d{4}\s*[-\x{2013}\x{2014}]\s*(?:\d{4}|Present)/iu';

        foreach ($lines as $line) {
            if (empty($line)) continue;

            $isBullet     = preg_match('/^[•\-\*]\s+/', $line);
            $hasMultiPipe = substr_count($line, '|') >= 1;
            $looksLikeHeader = !$isBullet
                && strlen($line) < 120
                && ($hasMultiPipe || preg_match($datePattern, $line));

            if ($looksLikeHeader) {
                // Save previous entry
                if ($current !== null) $entries[] = $current;

                $org = $title = $date = $location = '';

                if ($hasMultiPipe) {
                    $parts    = array_map('trim', explode('|', $line));
                    $org      = $parts[0] ?? '';
                    $title    = $parts[1] ?? '';
                    $date     = $parts[2] ?? '';
                    $location = $parts[3] ?? '';
                } else {
                    // Extract date from end of line
                    if (preg_match('/\s+(' . $datePattern . ')$/u', $line, $m, PREG_OFFSET_CAPTURE)) {
                        $org  = trim(substr($line, 0, $m[0][1]));
                        $date = trim($m[1][0]);
                    } else {
                        $org = $line;
                    }
                }

                $current = compact('org', 'title', 'date', 'location') + ['bullets' => []];
            } elseif ($current !== null && $isBullet) {
                $b = trim(preg_replace('/^[•\-\*]\s+/', '', $line));
                if (!empty($b) && strlen($b) > 5) $current['bullets'][] = $b;
            } elseif ($current !== null && !$isBullet && empty($current['title']) && strlen($line) < 80) {
                // Second line after header with no bullets yet = role title
                $current['title'] = $line;
            } elseif ($current !== null && $isBullet === 0 && strlen($line) > 15) {
                // Plain text line (no bullet marker) — treat as standalone bullet
                $current['bullets'][] = $line;
            }
        }

        if ($current !== null) $entries[] = $current;

        // Filter out entries with no org AND no bullets (noise)
        $entries = array_values(array_filter($entries, fn($e) =>
            !empty(trim($e['org'] ?? '')) || !empty($e['bullets'])
        ));

        return array_slice($entries, 0, 5);
    }

    private function parseProjects(string $text): array
    {
        $projects = [];
        $text     = preg_replace('/(\n\s*){3,}/', "\n\n", $text);
        $lines    = array_map('trim', explode("\n", $text));

        $current = null;
        $techPattern = '/\b(React|Vue|Angular|Node|Next|Python|Java|PHP|Laravel|Django|Flask|MongoDB|MySQL|PostgreSQL|AWS|Docker|TypeScript|JavaScript|Express|Redis|GraphQL|Flutter|Spring|FastAPI|Tailwind|Bootstrap)\b/i';

        foreach ($lines as $line) {
            if (empty($line)) continue;

            $isBullet     = preg_match('/^[•\-\*]\s+/', $line);
            $hasMultiPipe = substr_count($line, '|') >= 1;

            // A project header: short non-bullet line
            if (!$isBullet && strlen($line) < 100 && ($hasMultiPipe || !preg_match('/^[a-z]/', $line))) {
                if ($current !== null) $projects[] = $current;

                $title = $tech = '';
                if ($hasMultiPipe) {
                    $parts = array_map('trim', explode('|', $line));
                    $title = $parts[0];
                    $tech  = $parts[1] ?? '';
                } else {
                    $title = $line;
                }
                $current = compact('title', 'tech') + ['bullets' => []];
            } elseif ($current !== null && !$isBullet && empty($current['tech']) && preg_match($techPattern, $line)) {
                $current['tech'] = $line;
            } elseif ($current !== null && $isBullet) {
                $b = trim(preg_replace('/^[•\-\*]\s+/', '', $line));
                if (!empty($b) && strlen($b) > 5) $current['bullets'][] = $b;
            } elseif ($current !== null && strlen($line) > 15) {
                $current['bullets'][] = $line;
            }
        }

        if ($current !== null) $projects[] = $current;

        $projects = array_values(array_filter($projects, fn($p) =>
            !empty(trim($p['title'] ?? ''))
        ));

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

            $degree = $school = $year = $meta = '';
            $first  = $lines[0];

            if (substr_count($first, '|') >= 1) {
                $parts  = array_map('trim', explode('|', $first));
                $school = $parts[0];
                $degree = $parts[1] ?? '';
                for ($i = 2; $i < count($parts); $i++) {
                    if (preg_match('/\d{4}/', $parts[$i]) && empty($year)) $year = trim($parts[$i]);
                    else $meta = trim($parts[$i]);
                }
            } else {
                $degree = $first;
            }

            for ($i = 1; $i < count($lines); $i++) {
                $line = $lines[$i];
                if (preg_match('/\d{4}/', $line) && empty($year)) $year = $line;
                elseif (preg_match('/cgpa|gpa|grade|%/i', $line)) $meta = $line;
                elseif (empty($school)) $school = $line;
            }

            $meta = $meta ?: $year;
            if (!empty($degree) || !empty($school)) {
                $edu[] = compact('degree', 'school', 'year', 'meta');
            }
        }

        if (empty($edu)) {
            $lines = array_filter(array_map('trim', explode("\n", $text)));
            $chunk = [];
            foreach ($lines as $line) {
                if (empty($line)) {
                    if (!empty($chunk)) {
                        $edu[] = $this->buildEduFromChunk($chunk);
                        $chunk = [];
                    }
                } else {
                    $chunk[] = $line;
                }
            }
            if (!empty($chunk)) $edu[] = $this->buildEduFromChunk($chunk);
        }

        return array_slice(array_filter($edu), 0, 3);
    }

    private function buildEduFromChunk(array $lines): array
    {
        $degree = $lines[0] ?? '';
        $school = $lines[1] ?? '';
        $year   = '';
        foreach ($lines as $line) {
            if (preg_match('/\d{4}/', $line)) { $year = $line; break; }
        }
        return ['degree' => $degree, 'school' => $school, 'year' => $year, 'meta' => $year];
    }

    private function parseCertifications(string $text): array
    {
        $certs = [];
        foreach (explode("\n", $text) as $line) {
            $line = ltrim(trim($line), '•-*✓ ');
            if (strlen($line) > 5 && strlen($line) < 120) $certs[] = $line;
        }
        return array_slice($certs, 0, 5);
    }

    /* ------------------------------------------------------------------ */
    /*  Content Sanitizer                                                   */
    /* ------------------------------------------------------------------ */

    private function sanitizeSections(array $sections): array
    {
        // Summary: cap at 3 sentences / 320 chars
        if (!empty($sections['summary'])) {
            $summary   = preg_replace('/https?:\/\/\S+/i', '', $sections['summary']);
            $summary   = preg_replace('/\s{2,}/', ' ', $summary);
            $sentences = preg_split('/(?<=[.!?])\s+/', $summary);
            $summary   = implode(' ', array_slice($sentences, 0, 3));
            if (strlen($summary) > 320) {
                $summary = wordwrap($summary, 320, "\n", true);
                $summary = explode("\n", $summary)[0] . '…';
            }
            $sections['summary'] = trim($summary);
        }

        // Skills: remove URLs and junk
        if (!empty($sections['skills'])) {
            $cleaned = [];
            foreach ($sections['skills'] as $skill) {
                $skill = trim($skill);
                if (strlen($skill) < 2 || strlen($skill) > 55) continue;
                if (preg_match('/https?:\/\//i', $skill)) continue;
                if (!preg_match('/[a-zA-Z]/', $skill)) continue;
                $cleaned[] = $skill;
            }
            $sections['skills'] = array_values(array_unique($cleaned));
        }

        // Experience: sanitize bullets
        $junkPatterns = ['/^(link|github|http|www\.|live demo|see more)/i', '/^[^a-zA-Z]+$/'];
        foreach ($sections['experience'] as &$exp) {
            $exp['bullets'] = $this->sanitizeBullets($exp['bullets'] ?? [], $junkPatterns, 4, 150);
        }
        unset($exp);

        // Projects: sanitize
        foreach ($sections['projects'] as &$proj) {
            $proj['title']   = preg_replace('/\s*(links?|github|live demo)\s*:?.*/i', '', $proj['title'] ?? '');
            $proj['bullets'] = $this->sanitizeBullets($proj['bullets'] ?? [], $junkPatterns, 3, 150);
        }
        unset($proj);
        $sections['projects'] = array_values(array_filter($sections['projects'], fn($p) => !empty(trim($p['title'] ?? ''))));

        // Certifications: cap at 5
        if (!empty($sections['certifications'])) {
            $cleaned = [];
            foreach ($sections['certifications'] as $cert) {
                $cert = trim(preg_replace('/https?:\/\/\S+/i', '', $cert));
                if (strlen($cert) > 5 && strlen($cert) < 150) $cleaned[] = $cert;
            }
            $sections['certifications'] = array_slice($cleaned, 0, 5);
        }

        return $sections;
    }

    private function sanitizeBullets(array $bullets, array $junkPatterns, int $max, int $maxChars): array
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

            if (strlen($bullet) > $maxChars) {
                $bullet = wordwrap($bullet, $maxChars, "\n", true);
                $bullet = explode("\n", $bullet)[0];
                if (!preg_match('/[.!?]$/', $bullet)) $bullet .= '…';
            }

            $clean[] = $bullet;
            if (count($clean) >= $max) break;
        }
        return $clean;
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function extractPhone(string $text): string
    {
        if (preg_match('/(?:\+91[\s-]?)?[6-9]\d{9}|\+?\d[\d\s\-(). ]{8,14}\d/', $text, $m)) {
            return trim($m[0]);
        }
        return '';
    }

    private function extractLocation(string $text): string
    {
        $cities = ['Mumbai','Delhi','Bangalore','Bengaluru','Hyderabad','Chennai','Kolkata','Pune','Ahmedabad','Noida','Gurugram','Gurgaon','Jaipur','Lucknow'];
        foreach ($cities as $city) {
            if (stripos($text, $city) !== false) return $city;
        }
        return '';
    }

    private function extractLinks(string $text): array
    {
        $links = [];
        if (preg_match_all('/https?:\/\/\S+|github\.com\/\S+|linkedin\.com\/\S+/', $text, $m)) {
            $links = array_unique($m[0]);
        }
        return array_slice($links, 0, 2);
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/%PDF-[\d.]+.*$/m', '', $text);
        // Replace Unicode bullet/dash variants with ASCII equivalents BEFORE stripping
        $text = str_replace(["\u{2022}", "\u{25CF}", "\u{25E6}", "\u{2013}", "\u{2014}"], ['-', '-', '-', '-', '-'], $text);
        // Strip remaining non-printable non-ASCII; keep tab, LF, CR, space-tilde
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
        return trim(preg_replace('/\s{2,}/', ' ', $text));
    }

    private function buildFilename(User $user, Internship $internship): string
    {
        $name = preg_replace('/[^a-zA-Z0-9]/', '_', $user->name ?? 'Resume');
        $job  = preg_replace('/[^a-zA-Z0-9]/', '_', $internship->title);
        return "ATS_Optimised_{$name}_{$job}.pdf";
    }
}
