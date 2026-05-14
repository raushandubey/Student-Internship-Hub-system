<?php

namespace App\Services\Resume;

use Illuminate\Support\Facades\Log;

/**
 * ResumeParserEngine — Step 1 of the Resume Intelligence Pipeline.
 *
 * Extracts text from PDF, detects sections, extracts metadata,
 * and identifies weak bullets in the candidate's resume.
 */
class ResumeParserEngine
{
    private const SECTION_PATTERNS = [
        'summary'        => '/^(professional\s+summary|career\s+summary|executive\s+summary|summary|objective|career\s+objective|profile|about\s+me|about)/i',
        'skills'         => '/^(technical\s+skills|core\s+skills|key\s+skills|skills|competencies|technologies|tech\s+stack)/i',
        'experience'     => '/^(professional\s+experience|work\s+experience|experience|employment\s+history|internship\s+experience|internships?|work\s+history)/i',
        'projects'       => '/^(projects?|personal\s+projects?|academic\s+projects?|key\s+projects?|portfolio|notable\s+projects?)/i',
        'education'      => '/^(education|academic\s+background|educational\s+qualifications?|qualifications?|academics?)/i',
        'certifications' => '/^(certifications?|courses?|training|achievements?|awards?|honors?|honours?)/i',
    ];

    private const WEAK_VERB_PATTERNS = [
        '/^worked\s+on\b/i',
        '/^helped\s+(with|to)\b/i',
        '/^assisted\s+(in|with)\b/i',
        '/^was\s+responsible\s+for\b/i',
        '/^did\s+(some|a|the)\b/i',
        '/^participated\s+in\b/i',
        '/^involved\s+in\b/i',
        '/^part\s+of\s+a\s+team\b/i',
        '/^supported\s+(the|a)\b/i',
    ];

    /**
     * Parse a resume PDF and return structured data.
     */
    public function parse(string $absolutePath): array
    {
        $rawText = $this->extractText($absolutePath);

        // Accept any text with meaningful content (> 50 chars)
        if (strlen(trim($rawText)) < 50) {
            Log::error('ResumeParser: Extraction produced insufficient text', [
                'path'        => basename($absolutePath),
                'text_length' => strlen(trim($rawText)),
            ]);
            return $this->emptyParsed('Could not extract text from PDF.');
        }

        $cleanText = $this->normalizeText($rawText);
        $lines     = array_map('trim', explode("\n", $cleanText));
        $meta      = $this->extractMetadata($lines);
        $sections  = $this->detectSectionBoundaries($lines);
        $parsed    = $this->extractSections($lines, $sections);
        $weakBullets = $this->detectWeakBullets($parsed);

        return array_merge($meta, $parsed, [
            'raw_text'     => $cleanText,
            'lines'        => $lines,
            'weak_bullets' => $weakBullets,
            'section_map'  => $sections,
            'parse_error'  => null,
        ]);
    }

    // ── Text Extraction ──────────────────────────────────────────────────

    public function extractText(string $filePath): string
    {
        $text = $this->extractWithPdfParser($filePath);
        Log::info('ResumeParser: PdfParser result', ['length' => strlen(trim($text))]);

        if (strlen(trim($text)) < 200) {
            $raw = @file_get_contents($filePath);
            if ($raw !== false) {
                $btEtText = $this->extractWithBtEt($raw);
                Log::info('ResumeParser: BT/ET result', ['length' => strlen(trim($btEtText))]);
                if (strlen(trim($btEtText)) > strlen(trim($text))) {
                    $text = $btEtText;
                }
            }
        }

        if (strlen(trim($text)) < 100) {
            $raw = @file_get_contents($filePath);
            if ($raw !== false) {
                $binaryText = preg_replace('/[^\x20-\x7E\n\r\t]/', ' ', $raw);
                $binaryText = preg_replace('/\s{3,}/', "\n", $binaryText);
                $binaryText = preg_replace('/\b(BT|ET|Td|TD|Tm|Tf|Tj|TJ|cm|q|Q|re|f|S|n|W|w|j|J|d|gs|cs|sc|Do)\b/', '', $binaryText);
                Log::info('ResumeParser: Binary fallback result', ['length' => strlen(trim($binaryText))]);
                if (strlen(trim($binaryText)) > strlen(trim($text))) {
                    $text = $binaryText;
                }
            }
        }

        Log::info('ResumeParser: Final extracted text length', ['length' => strlen(trim($text ?? ''))]);
        return $text ?? '';
    }

    private function extractWithPdfParser(string $filePath): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Exception $e) {
            Log::warning('ResumeParser: PdfParser failed', ['error' => $e->getMessage()]);
            return '';
        }
    }

    private function extractWithBtEt(string $pdfContent): string
    {
        $text = '';
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
        return $text;
    }

    private function decodePdfString(string $str): string
    {
        return str_replace(['\\n','\\r','\\t','\\(','\\)','\\\\'], ["\n","\r","\t",'(',')',"\\"], $str);
    }

    // ── Normalization ────────────────────────────────────────────────────

    private function normalizeText(string $text): string
    {
        $text = preg_replace('/%PDF-[\d.]+[\s\S]*?(?=\n[A-Z]|\z)/s', '', $text);
        $text = preg_replace('/\d+\s+\d+\s+obj[\s\S]*?endobj/i', '', $text);
        $text = preg_replace('/xref[\s\S]*?%%EOF/i', '', $text);
        $text = preg_replace('/<<[^>]{0,300}>>/s', '', $text);
        $text = preg_replace('/<[0-9a-fA-F\s]{4,}>/m', '', $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Convert Unicode bullet/dash variants to ASCII BEFORE stripping non-ASCII
        $text = str_replace(["\u{2022}", "\u{25CF}", "\u{25E6}", "\u{2023}"], ['• ', '• ', '• ', '• '], $text);
        $text = str_replace(["\u{2013}", "\u{2014}"], ['-', '-'], $text);
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/(\n\s*){3,}/', "\n\n", $text);
        return trim($text);
    }

    // ── Metadata Extraction ──────────────────────────────────────────────

    private function extractMetadata(array $lines): array
    {
        $name = $email = $phone = $contact = '';
        $links = [];
        $skipWords = ['github','linkedin','portfolio','website','twitter','resume','cv','contact','profile','about','me'];

        foreach (array_slice($lines, 0, 12) as $line) {
            if (empty($line)) continue;
            if ($this->isSectionHeader($line)) continue;

            $lower = strtolower(trim($line));

            if (preg_match('/[\w.+-]+@[\w-]+\.\w+/', $line, $em)) {
                $email = $em[0];
                if (empty($contact)) $contact = $line;
            }
            if (preg_match('/(?:\+91[\s-]?)?[6-9]\d{9}|\+?\d[\d\s\-(). ]{8,14}\d/', $line, $pm)) {
                $phone = trim($pm[0]);
                if (empty($contact)) $contact = $line;
            }
            if (preg_match('/https?:\/\/\S+|github\.com\/\S+|linkedin\.com\/\S+/', $line, $lm)) {
                $links[] = $lm[0];
            }
            if (empty($name) && strlen($line) > 2 && strlen($line) < 55) {
                if (in_array($lower, $skipWords)) continue;
                if (str_contains($line, '@')) continue;
                if (preg_match('/\d{7,}/', $line)) continue;
                if (preg_match('/https?:\/\/|www\./i', $line)) continue;
                if (substr_count($line, '|') >= 2) continue;
                if (!preg_match('/[a-zA-Z]/', $line)) continue;
                $name = $line;
            }
        }

        return [
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'contact' => $contact ?: implode(' | ', array_filter([$phone, $email])),
            'links'   => $links,
        ];
    }

    // ── Section Detection ────────────────────────────────────────────────

    private function detectSectionBoundaries(array $lines): array
    {
        $found = [];
        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || strlen($trimmed) > 70) continue;
            foreach (self::SECTION_PATTERNS as $key => $pattern) {
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
            $start = $vals[$i];
            $end   = isset($vals[$i + 1]) ? $vals[$i + 1] - 1 : $total;
            $result[$keys[$i]] = [$start, $end];
        }
        return $result;
    }

    private function isSectionHeader(string $line): bool
    {
        if (strlen(trim($line)) > 70) return false;
        foreach (self::SECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, trim($line))) return true;
        }
        return false;
    }

    // ── Section Content Extraction ───────────────────────────────────────

    private function extractSections(array $lines, array $sectionMap): array
    {
        $result = [
            'summary' => '', 'skills' => [], 'experience' => [],
            'projects' => [], 'education' => [], 'certifications' => [],
        ];

        foreach ($sectionMap as $key => [$start, $end]) {
            $block = array_slice($lines, $start, $end - $start);
            $text  = trim(implode("\n", $block));

            switch ($key) {
                case 'summary':        $result['summary']        = $this->cleanText($text);                    break;
                case 'skills':         $result['skills']         = $this->extractSkills($text);                break;
                case 'experience':     $result['experience']     = $this->extractExperience($block);           break;
                case 'projects':       $result['projects']       = $this->extractProjects($block);             break;
                case 'education':      $result['education']      = $this->extractEducation($block);            break;
                case 'certifications': $result['certifications'] = $this->extractCertifications($block);       break;
            }
        }
        return $result;
    }

    private function extractSkills(string $text): array
    {
        if (preg_match('/\b(Languages|Frameworks|Tools|Databases|Cloud|DevOps)\s*:/i', $text)) {
            $groups = preg_split('/\s*[\|\n]\s*/', $text);
            return array_values(array_filter(
                array_map(fn($g) => trim($g, ' ,•-'), $groups),
                fn($g) => strlen($g) > 3
            ));
        }
        $raw = preg_split('/[,•\-\|\/\n]+/', $text);
        $skills = [];
        foreach ($raw as $s) {
            $s = trim($s);
            if (strlen($s) > 1 && strlen($s) < 45 && !in_array(strtoupper($s), ['SKILLS','TECHNICAL','CORE','KEY'])) {
                $skills[] = $s;
            }
        }
        return array_values(array_unique(array_filter($skills)));
    }

    private function extractExperience(array $lines): array
    {
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $isBullet = preg_match('/^[•\-\*]\s+/', $line);

            if (!$isBullet && strlen($line) < 120) {
                if ($current !== null && preg_match('/^(\d{4}|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)/i', $line)) {
                    if (empty($current['date'])) { $current['date'] = $line; }
                    continue;
                }
                if ($current) $entries[] = $current;

                if (substr_count($line, '|') >= 1) {
                    $parts = array_map('trim', explode('|', $line));
                    $current = ['org' => $parts[0] ?? '', 'title' => $parts[1] ?? '', 'date' => $parts[2] ?? '', 'location' => $parts[3] ?? '', 'bullets' => []];
                } else {
                    $datePattern = '/\s+((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4}(?:\s*[-–—]\s*(?:\w+\.?\s+)?\d{4}|\s*[-–—]\s*Present)?|\d{4}\s*[-–—]\s*(?:\d{4}|Present))$/i';
                    if (preg_match($datePattern, $line, $m, PREG_OFFSET_CAPTURE)) {
                        $current = ['org' => trim(substr($line, 0, $m[0][1])), 'title' => '', 'date' => trim($m[1][0]), 'location' => '', 'bullets' => []];
                    } else {
                        $current = ['org' => $line, 'title' => '', 'date' => '', 'location' => '', 'bullets' => []];
                    }
                }
            } elseif ($current !== null && $isBullet) {
                $bullet = trim(preg_replace('/^[•\-\*]\s+/', '', $line));
                if (!empty($bullet) && strlen($bullet) > 5) $current['bullets'][] = $bullet;
            } elseif ($current !== null && !$isBullet && empty($current['title'])) {
                $current['title'] = $line;
            }
        }
        if ($current) $entries[] = $current;
        return array_slice($entries, 0, 5);
    }

    private function extractProjects(array $lines): array
    {
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $isBullet = preg_match('/^[•\-\*]\s+/', $line);

            if (!$isBullet && strlen($line) < 100) {
                if ($current) $entries[] = $current;
                if (substr_count($line, '|') >= 1) {
                    $parts = array_map('trim', explode('|', $line));
                    $current = ['title' => $parts[0] ?? '', 'tech' => $parts[1] ?? '', 'bullets' => []];
                } else {
                    $current = ['title' => $line, 'tech' => '', 'bullets' => []];
                }
            } elseif ($current !== null) {
                $bullet = trim(preg_replace('/^[•\-\*]\s+/', '', $line));
                if (!empty($bullet) && strlen($bullet) > 5) $current['bullets'][] = $bullet;
            }
        }
        if ($current) $entries[] = $current;
        return array_slice($entries, 0, 5);
    }

    private function extractEducation(array $lines): array
    {
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if ($current) { $entries[] = $current; $current = null; }
                continue;
            }
            if (substr_count($line, '|') >= 1) {
                if ($current) $entries[] = $current;
                $parts = array_map('trim', explode('|', $line));
                $current = ['school' => $parts[0] ?? '', 'degree' => $parts[1] ?? '', 'year' => $parts[2] ?? '', 'meta' => $parts[3] ?? ''];
                continue;
            }
            if ($current === null) {
                $current = ['school' => '', 'degree' => $line, 'year' => '', 'meta' => ''];
            } elseif (empty($current['school'])) {
                $current['school'] = $line;
            } elseif (preg_match('/\d{4}/', $line) && empty($current['year'])) {
                $current['year'] = $line;
            } else {
                $current['meta'] = $current['meta'] ? $current['meta'] . ' | ' . $line : $line;
            }
        }
        if ($current) $entries[] = $current;
        return array_slice(array_filter($entries, fn($e) => !empty($e['degree'])), 0, 3);
    }

    private function extractCertifications(array $lines): array
    {
        $certs = [];
        foreach ($lines as $line) {
            $line = ltrim(trim($line), '•-*✓ ');
            if (strlen($line) > 5 && strlen($line) < 150) $certs[] = $line;
        }
        return array_slice($certs, 0, 6);
    }

    // ── Weak Bullet Detection ────────────────────────────────────────────

    private function detectWeakBullets(array $parsed): array
    {
        $weak = [];
        $allBullets = [];

        foreach ($parsed['experience'] ?? [] as $exp) {
            foreach ($exp['bullets'] ?? [] as $b) $allBullets[] = ['text' => $b, 'section' => 'experience'];
        }
        foreach ($parsed['projects'] ?? [] as $proj) {
            foreach ($proj['bullets'] ?? [] as $b) $allBullets[] = ['text' => $b, 'section' => 'projects'];
        }

        foreach ($allBullets as $b) {
            $issues = [];
            foreach (self::WEAK_VERB_PATTERNS as $pattern) {
                if (preg_match($pattern, $b['text'])) { $issues[] = 'weak_verb'; break; }
            }
            if (!preg_match('/\d+[%x]|\d+\s*(users|requests|ms\b|sec\b|clients|projects|team|members|endpoints)/i', $b['text'])) {
                $issues[] = 'no_impact';
            }
            if (preg_match('/\b(various|many|multiple|several|some|a lot|things)\b/i', $b['text'])) {
                $issues[] = 'generic_language';
            }
            if (strlen($b['text']) < 30) $issues[] = 'too_short';
            if (strlen($b['text']) > 180) $issues[] = 'too_long';

            if (!empty($issues)) {
                $weak[] = ['text' => $b['text'], 'section' => $b['section'], 'issues' => $issues];
            }
        }
        return $weak;
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
        return trim(preg_replace('/\s{2,}/', ' ', $text));
    }

    private function emptyParsed(string $error = ''): array
    {
        return [
            'name' => '', 'email' => '', 'phone' => '', 'contact' => '', 'links' => [],
            'summary' => '', 'skills' => [], 'experience' => [], 'projects' => [],
            'education' => [], 'certifications' => [], 'raw_text' => '', 'lines' => [],
            'weak_bullets' => [], 'section_map' => [], 'parse_error' => $error,
        ];
    }
}
