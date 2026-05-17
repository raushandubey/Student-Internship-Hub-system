<?php

namespace App\Services\Resume;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * LatexTemplateEngine — Phase 7: World-Class ATS PDF Rendering Engine
 *
 * Replaces unstable HTML-to-PDF (DomPDF) with a strict, deterministic 
 * LaTeX infrastructure mimicking enterprise ATS templates.
 */
class LatexTemplateEngine
{
    private const LATEX_API_URL = 'https://latexlite.com/v1/renders-sync';

    public function __construct(
        private ?PdfBinaryValidator $pdfValidator = null,
    ) {}

    /**
     * Generate PDF from parsed AI data using strict LaTeX layout.
     */
    public function generatePdf(array $data): ?string
    {
        $tex = $this->buildTexContent($data);

        if (!$this->validateLatex($tex)) {
            Log::error('LatexTemplate: Validation failed. Malformed LaTeX detected.');
            return null;
        }

        try {
            $apiKey = (string) config('services.latexlite.api_key', '');
            $apiUrl = (string) config('services.latexlite.url', self::LATEX_API_URL);

            if ($apiKey === '') {
                Log::warning('LatexTemplate: LaTeXLite API key missing');
                return null;
            }
            
            // Dispatch to enterprise compilation API
            Log::info('[LATEX_REQUEST_SENT]', [
                'endpoint_host' => parse_url($apiUrl, PHP_URL_HOST),
                'tex_size' => strlen($tex),
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(45)->post($apiUrl, [
                'template' => $tex
            ]);

            Log::info('[LATEX_RESPONSE_RECEIVED]', [
                'status' => $response->status(),
                'body_size' => strlen($response->body()),
            ]);

            if ($response->successful()) {
                $pdfBinary = $response->body();
                $validation = $this->validator()->validate($pdfBinary);

                if ($validation['valid']) {
                    Log::info('[LATEX_BINARY_VALID]', $validation);
                    Log::info('[PDF_MAGIC_BYTES_VALID]', [
                        'source' => 'latexlite',
                        'size' => $validation['size'],
                    ]);

                    return $pdfBinary;
                }

                Log::error('LatexTemplate: API returned invalid PDF binary', $validation);
            }
            
            Log::error('LatexTemplate: Compilation API failed', ['status' => $response->status(), 'error' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('LatexTemplate: Compilation API exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function validator(): PdfBinaryValidator
    {
        return $this->pdfValidator ??= app(PdfBinaryValidator::class);
    }

    /**
     * Escape special LaTeX characters to prevent compilation failures.
     */
    private function escape(string $text): string
    {
        $text = str_replace(
            ['\\', '&', '%', '$', '#', '_', '{', '}', '~', '^'],
            ['\textbackslash{}', '\&', '\%', '\$', '\#', '\_', '\{', '\}', '\textasciitilde{}', '\textasciicircum{}'],
            $text
        );
        return $text;
    }

    /**
     * Validates the generated LaTeX for missing braces and critical syntax errors.
     */
    public function validateLatex(string $tex): bool
    {
        if (empty(trim($tex))) return false;
        
        $openBraces = substr_count($tex, '{');
        $closeBraces = substr_count($tex, '}');
        if ($openBraces !== $closeBraces) return false;

        $openEnvs = substr_count($tex, '\begin{');
        $closeEnvs = substr_count($tex, '\end{');
        if ($openEnvs !== $closeEnvs) return false;

        if (!str_contains($tex, '\begin{document}') || !str_contains($tex, '\end{document}')) {
            return false;
        }

        return true;
    }

    /**
     * Build the deterministic fixed LaTeX template string.
     */
    private function buildTexContent(array $data): string
    {
        $name     = $this->escape(strtoupper($data['name'] ?? 'CANDIDATE'));
        $email    = $this->escape($data['email'] ?? '');
        $phone    = $this->escape($data['phone'] ?? '');
        $location = $this->escape($data['location'] ?? '');
        $links    = array_map([$this, 'escape'], $data['links'] ?? []);
        
        $tex = <<<'TEX'
\documentclass[a4paper,11pt]{article}
\usepackage[empty]{fullpage}
\usepackage{titlesec}
\usepackage{hyperref}
\usepackage{enumitem}
\usepackage{fancyhdr}

\pagestyle{fancy}
\fancyhf{}
\renewcommand{\headrulewidth}{0pt}
\renewcommand{\footrulewidth}{0pt}

\addtolength{\oddsidemargin}{-0.4in}
\addtolength{\evensidemargin}{-0.4in}
\addtolength{\textwidth}{0.8in}
\addtolength{\topmargin}{-0.6in}
\addtolength{\textheight}{1.2in}
\setlength{\footskip}{5pt}

\urlstyle{same}
\raggedright

\titleformat{\section}{\scshape\large}{}{0em}{}[\titlerule]

\newcommand{\cvEntry}[4]{
  \vspace{1pt}\textbf{#1} \hfill #2 \\
  \textit{#3} \hfill \textit{#4}
}

\newcommand{\cvPoint}[1]{\item{#1}}
\newcommand{\pointsStart}{\begin{itemize}[leftmargin=*]}
\newcommand{\pointsEnd}{\end{itemize}}

\begin{document}

\begin{tabular*}{\textwidth}{l@{\extracolsep{\fill}}r}

TEX;

        $tex .= "  \\textbf{{\\LARGE {$name}}} & \\href{mailto:{$email}}{{{$email}}} \\\\\n";

        if (count($links) > 0 || $phone) {
            $link0 = count($links) > 0 ? '\\href{'.$links[0].'}{'.$links[0].'}' : '';
            $tex .= "  {$link0} & {$phone} \\\\\n";
        }
        
        if (count($links) > 1 || $location) {
            $link1 = count($links) > 1 ? '\\href{'.$links[1].'}{'.$links[1].'}' : '';
            $tex .= "  {$link1} & {$location} \\\\\n";
        }

        $tex .= "\\end{tabular*}\n\n\\vspace{6pt}\n";

        $sections = $data['sections'] ?? [];

        // 1. PROFESSIONAL SUMMARY
        if (!empty($sections['summary'])) {
            $summary = $this->escape($sections['summary']);
            $tex .= "\\section{Professional Summary}\n{$summary}\n\n";
        }

        // 2. EXPERIENCE
        if (!empty($sections['experience']) && is_array($sections['experience'])) {
            $tex .= "\\section{Professional Experience}\n\n";
            foreach ($sections['experience'] as $exp) {
                $org   = $this->escape($exp['org'] ?? $exp['company'] ?? '');
                $role  = $this->escape($exp['title'] ?? $exp['role'] ?? '');
                $date  = $this->escape($exp['date'] ?? '');
                $loc   = $this->escape($exp['location'] ?? '');
                
                $tex .= "\\cvEntry{{$role}}{{$date}}{{$org}}{{$loc}}\n\\pointsStart\n";

                $bullets = $exp['bullets'] ?? [];
                if (!empty($bullets)) {
                    foreach ($bullets as $bullet) {
                        if (trim($bullet) !== '') {
                            $tex .= "\\cvPoint{" . $this->escape(preg_replace('/^[\-\•]\s*/', '', $bullet)) . "}\n";
                        }
                    }
                }
                $tex .= "\\pointsEnd\n\n";
            }
        }

        // 3. EDUCATION
        if (!empty($sections['education']) && is_array($sections['education'])) {
            $tex .= "\\section{Education}\n\n";
            foreach ($sections['education'] as $edu) {
                $school = $this->escape($edu['school'] ?? $edu['institution'] ?? '');
                $degree = $this->escape($edu['degree'] ?? '');
                $date   = $this->escape($edu['date'] ?? $edu['year'] ?? '');
                $loc   = $this->escape($edu['location'] ?? '');
                $meta   = $this->escape($edu['meta'] ?? $edu['gpa'] ?? '');
                
                if (empty($loc) && !empty($meta)) {
                    $loc = $meta;
                    $meta = '';
                }

                $tex .= "\\cvEntry{{$school}}{{$date}}{{$degree}}{{$loc}}\n";
                if (!empty($meta)) {
                    $tex .= "\\pointsStart\n\\cvPoint{{$meta}}\n\\pointsEnd\n\n";
                } else {
                    $tex .= "\n";
                }
            }
        }

        // 4. TECHNICAL SKILLS
        if (!empty($sections['skills'])) {
            $tex .= "\\section{Technical Skills}\n\\pointsStart\n";
            
            // Skills can be associative array of categories or sequential list
            $isAssoc = count(array_filter(array_keys($sections['skills']), 'is_string')) > 0;
            if ($isAssoc) {
                foreach ($sections['skills'] as $category => $items) {
                    $cat = $this->escape($category);
                    $itemsJoined = is_array($items) ? $this->escape(implode(', ', $items)) : $this->escape($items);
                    $tex .= "\\cvPoint{\\textbf{{$cat}:} {$itemsJoined}}\n";
                }
            } else {
                $skillsJoined = $this->escape(implode(', ', $sections['skills']));
                $tex .= "\\cvPoint{{$skillsJoined}}\n";
            }
            $tex .= "\\pointsEnd\n\n";
        }
        
        // 5. PROJECTS
        if (!empty($sections['projects']) && is_array($sections['projects'])) {
            $tex .= "\\section{Projects}\n\n";
            foreach ($sections['projects'] as $proj) {
                $title = $this->escape($proj['title'] ?? $proj['name'] ?? '');
                $tech  = $this->escape($proj['tech'] ?? '');
                $date  = $this->escape($proj['date'] ?? '');
                
                $tex .= "\\cvEntry{{$title}}{{$date}}{{$tech}}{}\n\\pointsStart\n";

                $bullets = $proj['bullets'] ?? [];
                if (!empty($bullets)) {
                    foreach ($bullets as $bullet) {
                        if (trim($bullet) !== '') {
                            $tex .= "\\cvPoint{" . $this->escape(preg_replace('/^[\-\•]\s*/', '', $bullet)) . "}\n";
                        }
                    }
                }
                $tex .= "\\pointsEnd\n\n";
            }
        }
        
        // 6. CERTIFICATIONS
        if (!empty($sections['certifications'])) {
            $tex .= "\\section{Certifications}\n\\pointsStart\n";
            foreach ($sections['certifications'] as $cert) {
                $certStr = is_string($cert) ? $cert : ($cert['name'] ?? '');
                if (trim($certStr) !== '') {
                    $tex .= "\\cvPoint{" . $this->escape(preg_replace('/^[\-\•]\s*/', '', $certStr)) . "}\n";
                }
            }
            $tex .= "\\pointsEnd\n\n";
        }

        $tex .= "\\end{document}\n";

        return $tex;
    }
}
