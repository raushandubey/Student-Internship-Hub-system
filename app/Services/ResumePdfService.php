<?php

namespace App\Services;

use App\Models\Internship;
use App\Models\ResumeVersion;
use App\Models\User;
use App\Services\Resume\PdfBinaryValidator;
use App\Services\Resume\ResumeParserEngine;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * ResumePdfService
 *
 * Renders optimized resume JSON to PDF via LaTeXLite (ATS LaTeX template only).
 */
class ResumePdfService
{
    public function __construct(
        private readonly ResumeParserEngine $parser,
        private readonly \App\Services\Resume\LatexTemplateEngine $latexEngine,
        private readonly PdfBinaryValidator $pdfValidator,
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

        $originalVersion = ResumeVersion::where('user_id', $user->id)
            ->where('internship_id', $internship->id)
            ->where('type', 'original')
            ->latest()
            ->first();

        $data = $this->buildPdfData(
            $user,
            $internship,
            $version->content,
            $originalVersion?->content
        );
        $filename = $this->buildFilename($user, $internship);

        $renderResult = $this->latexEngine->generatePdfResult($data);
        $pdfBinary    = $renderResult['pdf'] ?? null;

        if (!$pdfBinary) {
            $stage   = $renderResult['stage'] ?? 'unknown';
            $message = $renderResult['message'] ?? 'PDF could not be generated.';

            Log::error('ResumePdf: LaTeXLite PDF generation failed', [
                'user_id' => $user->id,
                'internship_id' => $internship->id,
                'version_id' => $version->id,
                'stage' => $stage,
                'http_status' => $renderResult['http_status'] ?? null,
                'message' => $message,
            ]);

            [$stageFailed, $httpCode, $userMessage] = match ($stage) {
                'api_key_missing' => ['LATEX_API_KEY_MISSING', 503, 'PDF rendering is not configured. Set LATEXLITE_API_KEY on the server.'],
                'payload_invalid' => ['LATEX_PAYLOAD_EMPTY', 422, $message],
                'payload_too_large' => ['LATEX_PAYLOAD_TOO_LARGE', 422, $message],
                'payload_encoding_error' => ['LATEX_PAYLOAD_ENCODING', 422, $message],
                'compilation_failed' => ['LATEX_COMPILATION_FAILED', 422, $message],
                'api_unauthorized' => ['LATEX_API_UNAUTHORIZED', 502, $message],
                'render_timeout' => ['LATEX_RENDER_TIMEOUT', 504, $message],
                'rate_limited' => ['LATEX_RATE_LIMITED', 429, $message],
                'invalid_pdf' => ['LATEX_INVALID_PDF_RESPONSE', 502, 'PDF renderer returned invalid output. Please try again later.'],
                'ssl_error' => ['LATEX_SSL_ERROR', 502, $message],
                'timeout' => ['LATEX_TIMEOUT', 504, $message],
                'dns_error' => ['LATEX_DNS_ERROR', 502, $message],
                'connection_refused' => ['LATEX_CONNECTION_REFUSED', 502, $message],
                'network_error' => ['LATEX_NETWORK_ERROR', 502, $message ?: 'Could not reach the PDF rendering service. Please try again.'],
                default => ['LATEX_PDF_RENDER_FAILED', 502, $message],
            };

            return response()->json([
                'success' => false,
                'stage_failed' => $stageFailed,
                'error' => $userMessage,
            ], $httpCode);
        }

        $validation = $this->pdfValidator->validate($pdfBinary);

        if (!$validation['valid']) {
            Log::error('ResumePdf: LaTeX PDF failed final validation', $validation + [
                'user_id' => $user->id,
                'internship_id' => $internship->id,
                'version_id' => $version->id,
            ]);

            return response()->json([
                'success' => false,
                'stage_failed' => 'PDF_BINARY_VALIDATION_FAILED',
                'error' => 'PDF renderer returned invalid output. Please try again later.',
                'validation' => $validation,
            ], 502);
        }

        Log::info('[PDF_MAGIC_BYTES_VALID]', [
            'source' => 'latexlite',
            'size' => $validation['size'],
        ]);
        Log::info('[PDF_RENDER_SUCCESS]', [
            'source' => 'latexlite',
            'user_id' => $user->id,
            'internship_id' => $internship->id,
            'version_id' => $version->id,
        ]);

        return $this->binaryPdfResponse($pdfBinary, $filename);
    }

    private function binaryPdfResponse(string $pdfBinary, string $filename): Response
    {
        if (PHP_SAPI !== 'cli') {
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
        }

        Log::info('[DOWNLOAD_READY]', [
            'filename' => $filename,
            'content_type' => 'application/pdf',
            'size' => strlen($pdfBinary),
            'first_bytes' => substr($pdfBinary, 0, 5),
        ]);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($pdfBinary),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Data Builder                                                        */
    /* ------------------------------------------------------------------ */

    private function buildPdfData(User $user, Internship $internship, string $resumeText, ?string $originalContent = null): array
    {
        $sections = $this->parseResumeTextToSections($resumeText, $internship);

        if ($originalContent !== null && trim($originalContent) !== '') {
            $originalSections = $this->parseResumeTextToSections($originalContent, $internship);
            $sections         = $this->mergePdfSections($sections, $originalSections);
        }

        $sections = $this->sanitizeSections($sections);
        $sections = $this->backfillSectionsFromRawText($sections, $resumeText);

        $profile  = $user->profile;
        $name     = $profile?->name ?? $user->name ?? 'Candidate';
        $email    = $user->email ?? '';
        $phone    = $this->extractPhone($resumeText) ?: ($profile?->phone ?? '');
        $location = $profile?->location ?? $this->extractLocation($resumeText) ?? '';
        $links    = $this->extractLinks($resumeText . "\n" . ($originalContent ?? ''));

        $identityEngine = new \App\Services\Resume\IdentityPreservationEngine();
        $parsedForIdentity = is_array($sections) ? array_merge($sections, ['raw_text' => $resumeText]) : ['raw_text' => $resumeText];
        $identity = $identityEngine->extract($parsedForIdentity);

        $headline   = $identity['candidate_type'] ?? 'Software Engineer';
        $targetRole = 'Applying for: ' . trim($internship->title)
            . ($internship->organization ? ' — ' . $internship->organization : '');

        return compact('name', 'email', 'phone', 'location', 'links', 'targetRole', 'headline', 'sections', 'internship');
    }

    private function mergePdfSections(array $optimized, array $baseline): array
    {
        if (trim((string) ($optimized['summary'] ?? '')) === '' && !empty($baseline['summary'])) {
            $optimized['summary'] = $baseline['summary'];
        }

        if (!$this->hasSubstantiveExperience($optimized['experience'] ?? [])) {
            $optimized['experience'] = $baseline['experience'] ?? [];
        }

        if (empty($optimized['education']) && !empty($baseline['education'])) {
            $optimized['education'] = $baseline['education'];
        }

        if (empty($optimized['certifications']) && !empty($baseline['certifications'])) {
            $optimized['certifications'] = $baseline['certifications'];
        }

        $optimized['projects'] = $this->mergeProjectSections(
            $optimized['projects'] ?? [],
            $baseline['projects'] ?? []
        );

        $optimized['skills'] = $this->mergeSkillLists(
            $optimized['skills'] ?? [],
            $baseline['skills'] ?? []
        );

        if (!empty($baseline['skills_grouped'])) {
            $optimized['skills_grouped'] = $baseline['skills_grouped'];
        }

        return $optimized;
    }

    private function hasSubstantiveExperience(array $experience): bool
    {
        foreach ($experience as $exp) {
            if (!is_array($exp)) {
                continue;
            }

            $bullets = array_filter($exp['bullets'] ?? [], fn ($b) => is_string($b) && strlen(trim($b)) > 8);
            if (!empty($bullets)) {
                return true;
            }
        }

        return false;
    }

    private function mergeProjectSections(array $optimized, array $baseline): array
    {
        $byTitle = [];

        foreach (array_merge($baseline, $optimized) as $proj) {
            if (!is_array($proj) || empty(trim($proj['title'] ?? ''))) {
                continue;
            }

            $key = strtolower(trim($proj['title']));
            if (!isset($byTitle[$key]) || count($proj['bullets'] ?? []) > count($byTitle[$key]['bullets'] ?? [])) {
                $byTitle[$key] = $proj;
            }
        }

        return array_values($byTitle);
    }

    private function mergeSkillLists(array $optimized, array $baseline): array
    {
        $seen = [];
        $out  = [];

        foreach (array_merge($optimized, $baseline) as $skill) {
            if (!is_string($skill)) {
                continue;
            }

            $skill = trim($skill);
            $key   = strtolower($skill);

            if ($skill === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[]      = $skill;
        }

        return $out;
    }

    private function backfillSectionsFromRawText(array $sections, string $resumeText): array
    {
        $hasContent = $this->hasSubstantiveExperience($sections['experience'] ?? [])
            || !empty($sections['education'])
            || !empty($sections['projects'])
            || !empty($sections['skills'])
            || trim((string) ($sections['summary'] ?? '')) !== '';

        if ($hasContent) {
            return $sections;
        }

        $decoded = json_decode($resumeText, true);
        $rawText = is_array($decoded) ? trim((string) ($decoded['raw_text'] ?? '')) : '';

        if ($rawText === '') {
            return $sections;
        }

        $parsed = $this->parser->parseRawText($rawText);

        return $this->mergePdfSections($sections, [
            'summary'        => $parsed['summary'] ?? '',
            'skills'         => $parsed['skills'] ?? [],
            'skills_grouped' => $this->extractGroupedSkills($rawText),
            'experience'     => $parsed['experience'] ?? [],
            'projects'       => $parsed['projects'] ?? [],
            'education'      => $parsed['education'] ?? [],
            'certifications' => $parsed['certifications'] ?? [],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Text → Structured Sections Parser                                  */
    /* ------------------------------------------------------------------ */

    private function parseResumeTextToSections(string $text, Internship $internship): array
    {
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $sections = [
                'summary'        => $decoded['summary'] ?? '',
                'skills'         => $decoded['skills'] ?? [],
                'experience'     => $decoded['experience'] ?? [],
                'projects'       => $decoded['projects'] ?? [],
                'education'      => $decoded['education'] ?? [],
                'certifications' => $decoded['certifications'] ?? [],
            ];

            $rawText = trim((string) ($decoded['raw_text'] ?? ''));
            if ($rawText !== '') {
                $parsedFromRaw = $this->parser->parseRawText($rawText);
                $sections      = $this->mergePdfSections($sections, [
                    'summary'        => $parsedFromRaw['summary'] ?? '',
                    'skills'         => $parsedFromRaw['skills'] ?? [],
                    'skills_grouped' => $this->extractGroupedSkills($rawText),
                    'experience'     => $parsedFromRaw['experience'] ?? [],
                    'projects'       => $parsedFromRaw['projects'] ?? [],
                    'education'      => $parsedFromRaw['education'] ?? [],
                    'certifications' => $parsedFromRaw['certifications'] ?? [],
                ]);
            }

            return $sections;
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
        $seen      = [];
        $merged    = [];

        foreach (array_merge($skills, $jobSkills) as $skill) {
            $skill = trim((string) $skill);
            $key   = strtolower($skill);
            if ($skill === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $merged[]   = $skill;
        }

        return $merged;
    }

    private function parseEntries(string $text): array
    {
        $entries = [];

        // Normalise line endings and collapse 3+ blank lines
        $text  = preg_replace('/(\n\s*){3,}/', "\n\n", $text);
        $lines = array_map('trim', explode("\n", $text));

        $current = null;
        $datePatternCore = '(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4}(?:\s*[-\x{2013}\x{2014}]\s*(?:\w+\.?\s+)?\d{4}|\s*[-\x{2013}\x{2014}]\s*Present)?|\d{4}\s*[-\x{2013}\x{2014}]\s*(?:\d{4}|Present)';

        foreach ($lines as $line) {
            if (empty($line)) continue;

            $isBullet     = preg_match('/^[•\-\*]\s+/', $line);
            $hasMultiPipe = substr_count($line, '|') >= 1;
            $looksLikeHeader = !$isBullet
                && strlen($line) < 120
                && ($hasMultiPipe || preg_match('/' . $datePatternCore . '/iu', $line));

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
                    if (preg_match('/\s+(' . $datePatternCore . ')$/iu', $line, $m, PREG_OFFSET_CAPTURE)) {
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
            $seen    = [];
            foreach ($sections['skills'] as $skill) {
                $skill = trim(is_string($skill) ? $skill : (string) $skill);
                if (strlen($skill) < 2 || strlen($skill) > 55) continue;
                if (preg_match('/https?:\/\//i', $skill)) continue;
                if (!preg_match('/[a-zA-Z]/', $skill)) continue;

                $key = strtolower($skill);
                if (isset($seen[$key])) continue;

                $seen[$key] = true;
                $cleaned[]  = $skill;
            }
            $sections['skills'] = $cleaned;
        }

        if (!empty($sections['education'])) {
            $sections['education'] = array_values(array_filter(
                array_map([$this, 'normalizeEducationEntry'], $sections['education']),
                fn ($edu) => is_array($edu) && !$this->isGarbageEducation($edu)
            ));
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

        if (preg_match_all('#https?://[^\s<>"\']+#i', $text, $m)) {
            foreach ($m[0] as $url) {
                $url = rtrim($url, '.,);');
                if (stripos($url, 'linkedin') !== false || stripos($url, 'github') !== false) {
                    $links[] = $url;
                }
            }
        }

        if (preg_match_all('#(?:https?://)?(?:www\.)?linkedin\.com/in/[\w\-./]+#i', $text, $linkedin)) {
            foreach ($linkedin[0] as $url) {
                $links[] = str_starts_with($url, 'http') ? $url : 'https://' . ltrim($url, '/');
            }
        }

        if (preg_match_all('#(?:https?://)?(?:www\.)?github\.com/[\w\-./]+#i', $text, $github)) {
            foreach ($github[0] as $url) {
                $links[] = str_starts_with($url, 'http') ? $url : 'https://' . ltrim($url, '/');
            }
        }

        return array_values(array_unique(array_slice($links, 0, 4)));
    }

    private function normalizeEducationEntry(mixed $edu): array
    {
        if (!is_array($edu)) {
            return ['degree' => '', 'school' => '', 'year' => '', 'meta' => ''];
        }

        return [
            'degree' => (string) ($edu['degree'] ?? ''),
            'school' => (string) ($edu['school'] ?? $edu['institution'] ?? ''),
            'year'   => (string) ($edu['year'] ?? $edu['date'] ?? ''),
            'meta'   => (string) ($edu['meta'] ?? $edu['gpa'] ?? ''),
        ];
    }

    private function isGarbageEducation(array $edu): bool
    {
        $school = strtolower((string) ($edu['school'] ?? $edu['institution'] ?? ''));
        $degree = strtolower((string) ($edu['degree'] ?? ''));

        if (strlen($school) > 90 || strlen($degree) > 90) {
            return true;
        }

        foreach (['professional summary', 'technical skills', 'portfolio', 'leetcode', 'github', 'linkedin'] as $needle) {
            if (str_contains($school, $needle) || str_contains($degree, $needle)) {
                return true;
            }
        }

        return $degree === 'academic & professional background';
    }

    private function extractGroupedSkills(string $rawText): array
    {
        $groups = [];
        $lines  = explode("\n", str_replace(["\r\n", "\r"], "\n", $rawText));
        $inSkills = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^(technical\s+skills|core\s+skills|skills)\s*$/i', $trimmed)) {
                $inSkills = true;
                continue;
            }

            if ($inSkills && preg_match('/^(experience|education|projects|certifications)\s*$/i', $trimmed)) {
                break;
            }

            if ($inSkills && preg_match('/^([A-Za-z][A-Za-z\s&\/]+):\s*(.+)$/', $trimmed, $match)) {
                $label = trim($match[1]);
                $items = array_map('trim', preg_split('/,\s*/', $match[2]));
                $groups[$label] = array_values(array_filter($items));
            }
        }

        return $groups;
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
