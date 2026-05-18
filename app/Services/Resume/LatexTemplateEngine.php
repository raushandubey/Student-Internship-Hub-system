<?php

namespace App\Services\Resume;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * LaTeXLite PDF engine — uses official template + data payload (renders-sync API).
 */
class LatexTemplateEngine
{
    private const LATEX_API_URL = 'https://latexlite.com/v1/renders-sync';

    public function __construct(
        private ?PdfBinaryValidator $pdfValidator = null,
    ) {}

    /**
     * Forbidden keys that indicate AI-generated LaTeX/formatting directives.
     * Input data containing these keys is rejected to enforce strict template boundaries.
     */
    private const FORBIDDEN_INPUT_KEYS = ['latex', 'formatting', 'layout', 'spacing', 'template'];

    // ─────────────────────────────────────────────────────────────────────────
    // Deterministic PHP LaTeX Rendering (Task 7.1 — Property 5 fix)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Render structured JSON resume data into a complete LaTeX document string.
     *
     * Accepts ONLY structured JSON data. Rejects any input containing LaTeX
     * formatting, layout instructions, or spacing directives from AI responses.
     *
     * @param  array<string, mixed>  $resumeData  Structured resume data (JSON-sourced)
     * @return string  Complete LaTeX document with \begin{document}...\end{document}
     *
     * @throws \InvalidArgumentException  When input contains forbidden formatting keys
     */
    public function render(array $resumeData): string
    {
        $this->validateInputData($resumeData);

        $payload = $this->mapToLatexliteData($resumeData);
        $tex     = $this->buildLatexDocument($payload);

        $this->assertNoWhitespaceCorruption($tex);

        return $tex;
    }

    /**
     * Render with a named template variant.
     *
     * Currently supports 'default' only; additional templates can be added
     * by placing .tex files in resources/latex/.
     *
     * @param  array<string, mixed>  $resumeData
     * @param  string                $template    Template name (default: 'default')
     * @return string  Complete LaTeX document string
     *
     * @throws \InvalidArgumentException  When input contains forbidden formatting keys
     */
    public function renderWithTemplate(array $resumeData, string $template = 'default'): string
    {
        $this->validateInputData($resumeData);

        $payload     = $this->mapToLatexliteData($resumeData);
        $templateTex = $this->loadNamedTemplate($template);
        $tex         = $this->injectPayloadIntoTemplate($payload, $templateTex);

        $this->assertNoWhitespaceCorruption($tex);

        return $tex;
    }

    /**
     * Validate that input data does not contain forbidden AI-generated formatting keys.
     *
     * @throws \InvalidArgumentException  When forbidden keys are detected
     */
    private function validateInputData(array $resumeData): void
    {
        $forbidden = $this->detectForbiddenKeys($resumeData);

        if (!empty($forbidden)) {
            throw new \InvalidArgumentException(
                'LatexTemplateEngine::render() received forbidden formatting keys in input data: '
                . implode(', ', $forbidden)
                . '. Input must be structured JSON data only — no LaTeX formatting, '
                . 'layout instructions, or spacing directives from AI responses.'
            );
        }
    }

    /**
     * Recursively detect forbidden keys in the input array.
     *
     * @param  array<string, mixed>  $data
     * @return array<string>  List of forbidden keys found
     */
    private function detectForbiddenKeys(array $data, int $depth = 0): array
    {
        if ($depth > 5) {
            return [];
        }

        $found = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::FORBIDDEN_INPUT_KEYS, true)) {
                $found[] = $key;
            }

            if (is_array($value) && $depth < 5) {
                $nested = $this->detectForbiddenKeys($value, $depth + 1);
                $found  = array_merge($found, $nested);
            }
        }

        return array_unique($found);
    }

    /**
     * Build a complete LaTeX document by injecting payload into the default template.
     *
     * @param  array<string, string>  $payload  Mapped and escaped template variables
     * @return string  Complete LaTeX document
     */
    private function buildLatexDocument(array $payload): string
    {
        $template = $this->loadTemplate();

        return $this->injectPayloadIntoTemplate($payload, $template);
    }

    /**
     * Inject payload variables into a LaTeX template string.
     *
     * Replaces [[.key]] placeholders with escaped values.
     *
     * @param  array<string, string>  $payload
     * @param  string                 $template
     * @return string
     */
    private function injectPayloadIntoTemplate(array $payload, string $template): string
    {
        foreach ($payload as $key => $value) {
            $template = str_replace('[[.' . $key . ']]', (string) $value, $template);
        }

        // Remove any remaining unreplaced placeholders
        $template = preg_replace('/\[\[\.[a-z_]+\]\]/', '', $template) ?? $template;

        // Collapse 3+ consecutive newlines to exactly 2 (prevent whitespace corruption)
        $template = preg_replace('/\n{3,}/', "\n\n", $template) ?? $template;

        return $template;
    }

    /**
     * Load a named template from resources/latex/.
     *
     * @param  string  $name  Template name (without .tex extension)
     * @return string  Raw template content
     *
     * @throws \RuntimeException  When template file is not found
     */
    private function loadNamedTemplate(string $name): string
    {
        // Sanitize template name to prevent path traversal
        $safeName = preg_replace('/[^a-z0-9\-]/', '', strtolower($name));

        if ($safeName === '' || $safeName === 'default') {
            return $this->loadTemplate();
        }

        $path = resource_path('latex/' . $safeName . '-resume-template.tex');

        if (!is_file($path)) {
            // Fall back to default template if named template not found
            Log::warning('LatexTemplateEngine: Named template not found, using default.', [
                'requested' => $name,
                'path'      => $path,
            ]);

            return $this->loadTemplate();
        }

        return (string) file_get_contents($path);
    }

    /**
     * Assert that the rendered LaTeX has no whitespace corruption.
     *
     * Checks for:
     * - 3+ consecutive newlines (whitespace corruption indicator)
     * - Empty \cvPoint{} entries (bullet corruption)
     *
     * Logs a warning if corruption is detected but does NOT throw — the output
     * is still returned so the caller can decide how to handle it.
     *
     * @param  string  $latex  Rendered LaTeX string
     */
    private function assertNoWhitespaceCorruption(string $latex): void
    {
        if (preg_match('/\n{3,}/', $latex)) {
            Log::warning('LatexTemplateEngine: Whitespace corruption detected (3+ consecutive newlines) in rendered output.');
        }

        if (preg_match('/\\\\cvPoint\{\s*\}/', $latex)) {
            Log::warning('LatexTemplateEngine: Empty \\cvPoint{} entries detected in rendered output.');
        }
    }

    /**
     * Validate the quality of rendered LaTeX output.
     *
     * @param  string  $latex  Rendered LaTeX string
     * @return array{valid: bool, issues: array<string>}
     */
    public function validateRenderedLatex(string $latex): array
    {
        $issues = [];

        if (!str_contains($latex, '\begin{document}')) {
            $issues[] = 'Missing \\begin{document}';
        }

        if (!str_contains($latex, '\end{document}')) {
            $issues[] = 'Missing \\end{document}';
        }

        if (preg_match('/\n{3,}/', $latex)) {
            $issues[] = 'Whitespace corruption: 3+ consecutive newlines detected';
        }

        if (preg_match('/\\\\cvPoint\{\s*\}/', $latex)) {
            $issues[] = 'Bullet corruption: empty \\cvPoint{} entries detected';
        }

        return [
            'valid'  => empty($issues),
            'issues' => $issues,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LaTeXLite API PDF Generation (preserved — Requirements 3.10–3.12)
    // ─────────────────────────────────────────────────────────────────────────

    public function generatePdf(array $data): ?string
    {
        return $this->generatePdfResult($data)['pdf'] ?? null;
    }

    /**
     * @return array{success: bool, pdf: ?string, stage: ?string, message: ?string, http_status: ?int}
     */
    public function generatePdfResult(array $data): array
    {
        $template = $this->loadTemplate();
        $payload  = $this->mapToLatexliteData($data);

        if (!$this->validateMappedPayload($payload)) {
            Log::error('LatexTemplate: Payload validation failed — missing required resume sections.', [
                'data_keys' => array_keys($payload),
            ]);

            return [
                'success' => false,
                'pdf' => null,
                'stage' => 'payload_invalid',
                'message' => 'Resume has no renderable sections (experience, education, projects, skills, or summary).',
                'http_status' => null,
            ];
        }

        try {
            $apiKey = trim((string) config('services.latexlite.api_key', ''));
            $apiUrl = $this->normalizeApiUrl((string) config('services.latexlite.url', self::LATEX_API_URL));

            if ($apiKey === '') {
                Log::warning('LatexTemplate: LaTeXLite API key missing');

                return [
                    'success' => false,
                    'pdf' => null,
                    'stage' => 'api_key_missing',
                    'message' => 'LATEXLITE_API_KEY is not configured.',
                    'http_status' => null,
                ];
            }

            $requestBody = [
                'template' => $template,
                'data'     => $this->sanitizePayloadForJson($payload),
            ];

            $encodedSize = $this->estimateJsonBodySize($requestBody);
            $maxBody     = (int) config('services.latexlite.max_body_bytes', 1_048_576);

            if ($encodedSize > $maxBody) {
                Log::error('LatexTemplate: Request body exceeds LaTeXLite limit', [
                    'bytes' => $encodedSize,
                    'max'   => $maxBody,
                ]);

                return [
                    'success' => false,
                    'pdf' => null,
                    'stage' => 'payload_too_large',
                    'message' => 'Resume content is too large for PDF rendering (' . $encodedSize . ' bytes; max ' . $maxBody . ').',
                    'http_status' => null,
                ];
            }

            Log::info('[LATEX_REQUEST_SENT]', [
                'endpoint'  => $apiUrl,
                'data_keys' => array_keys($payload),
                'body_bytes'=> $encodedSize,
            ]);

            $response = $this->latexliteHttpClient($apiKey)->post($apiUrl, $requestBody);

            Log::info('[LATEX_RESPONSE_RECEIVED]', [
                'status'    => $response->status(),
                'body_size' => strlen($response->body()),
            ]);

            if ($this->isSuccessfulPdfResponse($response)) {
                $pdfBinary = $this->extractPdfFromResponse($response);

                if ($pdfBinary === null) {
                    Log::error('LatexTemplate: Could not extract PDF from LaTeXLite response', [
                        'status' => $response->status(),
                        'content_type' => $response->header('Content-Type'),
                    ]);

                    return [
                        'success' => false,
                        'pdf' => null,
                        'stage' => 'invalid_pdf',
                        'message' => 'LaTeXLite returned a response without valid PDF data.',
                        'http_status' => $response->status(),
                    ];
                }

                $validation = $this->validator()->validate($pdfBinary);

                if ($validation['valid']) {
                    Log::info('[PDF_MAGIC_BYTES_VALID]', [
                        'source' => 'latexlite',
                        'size'   => $validation['size'],
                    ]);

                    return [
                        'success' => true,
                        'pdf' => $pdfBinary,
                        'stage' => null,
                        'message' => null,
                        'http_status' => $response->status(),
                    ];
                }

                Log::error('LatexTemplate: API returned invalid PDF binary', $validation);

                return [
                    'success' => false,
                    'pdf' => null,
                    'stage' => 'invalid_pdf',
                    'message' => 'PDF validation failed: ' . ($validation['reason'] ?? 'unknown'),
                    'http_status' => $response->status(),
                ];
            }

            return $this->mapFailedHttpResponse($response);
        } catch (ConnectionException $e) {
            $classified = $this->classifyNetworkFailure($e);

            Log::error('LatexTemplate: LaTeXLite connection failed', [
                'stage'   => $classified['stage'],
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'pdf' => null,
                'stage' => $classified['stage'],
                'message' => $classified['message'],
                'http_status' => null,
            ];
        } catch (JsonException $e) {
            Log::error('LatexTemplate: Could not encode LaTeXLite request JSON', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'pdf' => null,
                'stage' => 'payload_encoding_error',
                'message' => 'Resume data could not be encoded for PDF rendering.',
                'http_status' => null,
            ];
        } catch (\Throwable $e) {
            $classified = $this->classifyNetworkFailure($e);

            Log::error('LatexTemplate: Compilation API exception', [
                'stage' => $classified['stage'],
                'type'  => $e::class,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'pdf' => null,
                'stage' => $classified['stage'],
                'message' => $classified['message'],
                'http_status' => null,
            ];
        }
    }

    private function latexliteHttpClient(string $apiKey): PendingRequest
    {
        $timeout        = max(5, (int) config('services.latexlite.timeout', 60));
        $connectTimeout = max(3, (int) config('services.latexlite.connect_timeout', 15));
        $retries        = max(0, (int) config('services.latexlite.retry_times', 2));
        $retrySleep     = max(0, (int) config('services.latexlite.retry_sleep_ms', 500));

        $client = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/pdf',
        ])
            ->timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->withOptions(['verify' => $this->resolveSslVerify()]);

        if ($retries > 0) {
            $client = $client->retry(
                $retries,
                $retrySleep,
                fn ($exception) => $exception instanceof ConnectionException,
                throw: false,
            );
        }

        return $client;
    }

    private function normalizeApiUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return self::LATEX_API_URL;
        }

        if (!str_contains($url, '/v1/renders-sync')) {
            $url = rtrim($url, '/') . '/v1/renders-sync';
        }

        return $url;
    }

    /**
     * @return bool|string Path to CA bundle, true for system default, or false to skip verification (local dev only).
     */
    private function resolveSslVerify(): bool|string
    {
        $configured = config('services.latexlite.verify_ssl', true);

        if ($configured === false || $configured === 'false' || $configured === '0') {
            return false;
        }

        if (is_string($configured) && $configured !== '' && $configured !== 'true' && is_readable($configured)) {
            return $configured;
        }

        $iniCa = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
        if (is_string($iniCa) && $iniCa !== '' && is_readable($iniCa)) {
            return $iniCa;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayloadForJson(array $payload): array
    {
        $clean = [];

        foreach ($payload as $key => $value) {
            $clean[$key] = is_string($value)
                ? $this->sanitizeUtf8($value)
                : $value;
        }

        return $clean;
    }

    private function sanitizeUtf8(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_convert_encoding')) {
            $converted = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

            return is_string($converted) ? $converted : $text;
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function estimateJsonBodySize(array $body): int
    {
        try {
            $json = json_encode($body, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);

            return strlen($json);
        } catch (JsonException) {
            return PHP_INT_MAX;
        }
    }

    /**
     * @return array{success: false, pdf: null, stage: string, message: string, http_status: int}
     */
    private function mapFailedHttpResponse(Response $response): array
    {
        $status     = $response->status();
        $apiMessage = $this->parseApiErrorMessage($response);

        Log::error('LatexTemplate: LaTeXLite API failed', [
            'status' => $status,
            'error'  => $apiMessage ?? substr($response->body(), 0, 500),
        ]);

        $stage = match ($status) {
            401, 403 => 'api_unauthorized',
            408      => 'render_timeout',
            429      => 'rate_limited',
            422      => 'compilation_failed',
            default  => 'api_error',
        };

        $message = match ($stage) {
            'api_unauthorized' => $apiMessage ?? 'LaTeXLite API key is invalid or expired.',
            'render_timeout'   => $apiMessage ?? 'PDF rendering timed out. Try again or shorten resume content.',
            'rate_limited'     => $apiMessage ?? 'LaTeXLite rate limit exceeded. Please wait and try again.',
            'compilation_failed' => $apiMessage ?? 'LaTeX compilation failed.',
            default            => $apiMessage ?? 'LaTeXLite API request failed (HTTP ' . $status . ').',
        };

        return [
            'success' => false,
            'pdf' => null,
            'stage' => $stage,
            'message' => $message,
            'http_status' => $status,
        ];
    }

    /**
     * @return array{stage: string, message: string}
     */
    private function classifyNetworkFailure(\Throwable $e): array
    {
        $msg = strtolower($e->getMessage());

        if (str_contains($msg, 'ssl') || str_contains($msg, 'certificate') || str_contains($msg, 'curl error 60')) {
            return [
                'stage' => 'ssl_error',
                'message' => 'SSL certificate verification failed when calling LaTeXLite. '
                    . 'Set CURL_CA_BUNDLE to a valid CA file, or LATEXLITE_VERIFY_SSL=false for local dev only.',
            ];
        }

        if (str_contains($msg, 'timed out') || str_contains($msg, 'timeout') || str_contains($msg, 'curl error 28')) {
            return [
                'stage' => 'timeout',
                'message' => 'LaTeXLite PDF rendering timed out. Check your network or try again.',
            ];
        }

        if (str_contains($msg, 'could not resolve host') || str_contains($msg, 'curl error 6') || str_contains($msg, 'getaddrinfo')) {
            return [
                'stage' => 'dns_error',
                'message' => 'Could not resolve latexlite.com. Check DNS and outbound HTTPS access.',
            ];
        }

        if (str_contains($msg, 'connection refused') || str_contains($msg, 'curl error 7') || str_contains($msg, 'failed to connect')) {
            return [
                'stage' => 'connection_refused',
                'message' => 'Connection to LaTeXLite was refused. Check firewall, proxy, or LATEXLITE_API_URL.',
            ];
        }

        return [
            'stage' => 'network_error',
            'message' => 'Could not reach LaTeXLite: ' . $e->getMessage(),
        ];
    }

    private function isSuccessfulPdfResponse(Response $response): bool
    {
        $status = $response->status();

        return $status >= 200 && $status < 300;
    }

    private function extractPdfFromResponse(Response $response): ?string
    {
        $body = $response->body();

        if ($body !== '' && str_starts_with($body, '%PDF-')) {
            return $body;
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return null;
        }

        if (($json['success'] ?? false) === true) {
            $encoded = $json['data']['pdf_base64'] ?? null;
            if (is_string($encoded) && $encoded !== '') {
                $decoded = base64_decode($encoded, true);

                return $decoded !== false && str_starts_with($decoded, '%PDF-') ? $decoded : null;
            }
        }

        return null;
    }

    private function parseApiErrorMessage(Response $response): ?string
    {
        $json = json_decode($response->body(), true);
        if (!is_array($json)) {
            return null;
        }

        $message = $json['error']['message'] ?? $json['message'] ?? null;

        return is_string($message) && $message !== '' ? $message : null;
    }

    /**
     * @deprecated Used by unit tests — builds full TeX for brace validation.
     */
    public function validateLatex(string $tex): bool
    {
        if (empty(trim($tex))) {
            return false;
        }

        $cleanTex = str_replace('\\\\', '', $tex);
        $cleanTex = str_replace(['\\{', '\\}'], '', $cleanTex);

        if (substr_count($cleanTex, '{') !== substr_count($cleanTex, '}')) {
            return false;
        }

        if (substr_count($cleanTex, '\begin{') !== substr_count($cleanTex, '\end{')) {
            return false;
        }

        return str_contains($cleanTex, '\begin{document}')
            && str_contains($cleanTex, '\end{document}');
    }

    /**
     * @deprecated Used by unit tests.
     */
    private function buildTexContent(array $data): string
    {
        $mapped = $this->mapToLatexliteData($data);
        $tex    = $this->loadTemplate();

        foreach ($mapped as $key => $value) {
            $tex = str_replace('[[ .' . $key . ' ]]', $value, $tex);
        }

        return $tex;
    }

    private function loadTemplate(): string
    {
        $path = resource_path('latex/ats-resume-template.tex');

        if (!is_file($path)) {
            throw new \RuntimeException('LaTeX resume template not found: ' . $path);
        }

        return (string) file_get_contents($path);
    }

    private function mapToLatexliteData(array $data): array
    {
        $sections = $data['sections'] ?? [];
        $links    = $this->normalizeLinks($data['links'] ?? []);

        $summary  = trim((string) ($sections['summary'] ?? ''));
        $headline = trim((string) ($data['headline'] ?? ''));
        $targetRole = trim((string) ($data['targetRole'] ?? ''));
        $tagline  = $headline !== '' ? $headline : $targetRole;

        if ($headline !== '' && $targetRole !== '') {
            $tagline = $headline . ' | ' . $targetRole;
        } elseif ($tagline === '' && $summary !== '') {
            $tagline = mb_strlen($summary) > 90
                ? mb_substr($summary, 0, 87) . '...'
                : $summary;
        }

        if ($tagline === '') {
            $tagline = 'Professional Candidate';
        }

        $experience = array_values(array_filter(
            $sections['experience'] ?? [],
            fn ($exp) => is_array($exp) && (!empty($exp['bullets']) || !empty($exp['org']) || !empty($exp['title']))
        ));

        $projects = array_values(array_filter(
            $sections['projects'] ?? [],
            fn ($p) => is_array($p) && !empty(trim($p['title'] ?? ''))
        ));

        $education = array_values(array_filter(
            $sections['education'] ?? [],
            fn ($edu) => is_array($edu) && !$this->isGarbageEducation($edu)
        ));

        $skills = $this->uniqueSkills($sections['skills'] ?? []);

        $contactRowTwo   = $this->buildContactRow($links['linkedin_url'], $links['linkedin_label'], $data['phone'] ?? '');
        $contactRowThree = $this->buildContactRow($links['github_url'], $links['github_label'], $data['location'] ?? '');
        $emailRaw        = trim((string) ($data['email'] ?? ''));

        return [
            'name'                  => $this->escape(ResumeTextNormalizer::formatDisplayName($data['name'] ?? 'CANDIDATE')),
            'email'                 => $this->escape($emailRaw),
            'email_href'            => $this->emailForHref($emailRaw),
            'tagline'               => $this->escape($tagline),
            'contact_row_two'       => $contactRowTwo,
            'contact_row_three'     => $contactRowThree,
            'summary_section'       => $summary !== '' && $summary !== $tagline
                ? "\\section{Professional Summary}\n" . $this->escape($summary) . "\n\n"
                : '',
            'experience_section'    => $this->renderExperienceSection($experience),
            'projects_section'      => $this->renderProjectsSection($projects),
            'education_section'     => $this->renderEducationSection($education),
            'skills'                => $this->skillsToCvPoints($skills, $sections['skills_grouped'] ?? null),
            'certifications_section'=> $this->renderCertificationsSection($sections['certifications'] ?? []),
        ];
    }

    private function validateMappedPayload(array $payload): bool
    {
        return trim($payload['experience_section'] ?? '') !== ''
            || trim($payload['education_section'] ?? '') !== ''
            || trim($payload['projects_section'] ?? '') !== ''
            || trim($payload['summary_section'] ?? '') !== ''
            || trim($payload['skills'] ?? '') !== ''
            || trim($payload['certifications_section'] ?? '') !== '';
    }

    private function renderExperienceSection(array $experience): string
    {
        $blocks = $this->renderExperienceBlocks($experience);

        return $blocks !== ''
            ? "\\section{Professional Experience}\n\n" . $blocks
            : '';
    }

    private function renderEducationSection(array $education): string
    {
        $blocks = $this->renderEducationBlocks($education);

        return $blocks !== ''
            ? "\\section{Education}\n\n" . $blocks
            : '';
    }

    private function buildContactRow(string $url, string $label, string $right): string
    {
        $left = '';
        if ($url !== '' && $label !== '') {
            $left = '\\href{' . $this->escapeUrl($url) . '}{' . $this->escape($label) . '}';
        }

        $rightEsc = $this->escape($right);

        if ($left === '' && $rightEsc === '') {
            return '  & \\\\';
        }

        return '  ' . $left . ' & ' . $rightEsc . ' \\\\';
    }

    private function renderExperienceBlocks(array $experience): string
    {
        if (empty($experience)) {
            return '';
        }

        $blocks = '';
        foreach (array_slice($experience, 0, 4) as $exp) {
            $position = $this->escape($exp['title'] ?? $exp['role'] ?? 'Role');
            $company  = $this->escape($exp['org'] ?? $exp['company'] ?? '');
            $dates    = $this->escape($exp['date'] ?? '');
            $location = $this->escape($exp['location'] ?? '');

            $blocks .= "\\cvEntry{{$position}}{{$dates}}{{$company}}{{$location}}\n";
            $blocks .= "\\pointsStart\n";
            $blocks .= $this->bulletsToCvPoints($exp['bullets'] ?? []);
            $blocks .= "\\pointsEnd\n\n";
        }

        return $blocks;
    }

    private function renderProjectsSection(array $projects): string
    {
        if (empty($projects)) {
            return '';
        }

        $tex = "\\section{Projects}\n\n";
        foreach (array_slice($projects, 0, 3) as $proj) {
            $title = $this->escape($proj['title'] ?? $proj['name'] ?? 'Project');
            $tech  = $this->escape($proj['tech'] ?? '');
            $tex  .= "\\cvEntry{{$title}}{}{{$tech}}{}\n\\pointsStart\n";
            $tex  .= $this->bulletsToCvPoints($proj['bullets'] ?? [], 3);
            $tex  .= "\\pointsEnd\n\n";
        }

        return $tex;
    }

    private function renderEducationBlocks(array $education): string
    {
        if (empty($education)) {
            return '';
        }

        $blocks = '';
        foreach (array_slice($education, 0, 3) as $edu) {
            $school = $this->escape($edu['school'] ?? $edu['institution'] ?? '');
            $degree = $this->escape($edu['degree'] ?? '');
            $year   = $this->escape($edu['year'] ?? $edu['date'] ?? '');
            $meta   = trim((string) ($edu['meta'] ?? $edu['gpa'] ?? ''));

            if ($school === '' && $degree === '') {
                continue;
            }

            $blocks .= "\\cvEntry{{$school}}{{$year}}{{$degree}}{}\n";

            if ($meta !== '' && $meta !== $year) {
                $blocks .= "\\pointsStart\n\\cvPoint{" . $this->escape($meta) . "}\n\\pointsEnd\n";
            }

            $blocks .= "\n";
        }

        return $blocks;
    }

    private function renderCertificationsSection(array $certs): string
    {
        $certs = array_values(array_filter(array_map(function ($cert) {
            return is_string($cert) ? trim($cert) : trim((string) ($cert['name'] ?? ''));
        }, $certs)));

        if (empty($certs)) {
            return '';
        }

        $tex = "\\section{Certifications \\& Training}\n\\pointsStart\n";
        foreach (array_slice($certs, 0, 5) as $cert) {
            $tex .= '\\cvPoint{' . $this->escape(preg_replace('/^[\-\•]\s*/', '', $cert)) . "}\n";
        }
        $tex .= "\\pointsEnd\n\n";

        return $tex;
    }

    private function bulletsToCvPoints(array $bullets, int $max = 4): string
    {
        $lines = '';
        $count = 0;

        foreach ($bullets as $bullet) {
            if (!is_string($bullet)) {
                continue;
            }

            $bullet = trim(preg_replace('/^[\-\•*]\s*/', '', $bullet));
            $bullet = str_replace(['\\', "\r", "\n"], ' ', $bullet);
            if ($bullet === '' || strlen($bullet) < 8) {
                continue;
            }

            if (mb_strlen($bullet) > 220) {
                $bullet = mb_substr($bullet, 0, 217) . '...';
            }

            $lines .= '\\cvPoint{' . $this->escape($bullet) . "}\n";
            $count++;

            if ($count >= $max) {
                break;
            }
        }

        if ($lines === '') {
            $lines = '\\cvPoint{Delivered measurable results aligned with role requirements.}' . "\n";
        }

        return $lines;
    }

    private function skillsToCvPoints(array $skills, ?array $groupedOverride = null): string
    {
        $skills = $this->uniqueSkills($skills);

        if (empty($skills) && empty($groupedOverride)) {
            return '\\cvPoint{Technical skills listed in experience and projects above.}';
        }

        if (!empty($groupedOverride) && is_array($groupedOverride)) {
            $grouped = $groupedOverride;
        } else {
            $grouped = $this->groupSkills($skills);
        }

        $lines = '';

        foreach ($grouped as $label => $items) {
            if (empty($items)) {
                continue;
            }

            $joined = $this->escape(implode(', ', $items));
            $lines .= '\\cvPoint{\\textbf{' . $this->escape($label) . ':} ' . $joined . '}' . "\n";
        }

        if ($lines === '') {
            $lines = '\\cvPoint{' . $this->escape(implode(', ', array_slice($skills, 0, 15))) . '}' . "\n";
        }

        return $lines;
    }

    private function groupSkills(array $skills): array
    {
        $langKw = ['php', 'python', 'java', 'javascript', 'typescript', 'c++', 'c#', 'ruby', 'go', 'rust', 'kotlin', 'sql', 'html', 'css'];
        $fwKw   = ['laravel', 'django', 'flask', 'react', 'vue', 'vue.js', 'angular', 'next', 'node', 'node.js', 'express', 'spring', 'graphql'];
        $toolKw = ['git', 'docker', 'kubernetes', 'aws', 'azure', 'jenkins', 'linux', 'mongodb', 'redis', 'firebase'];
        $dbKw   = ['mysql', 'postgresql', 'sqlite', 'oracle', 'elasticsearch'];

        $groups = [
            'Languages'  => [],
            'Frameworks' => [],
            'Tools'      => [],
            'Databases'  => [],
            'Other'      => [],
        ];

        foreach ($skills as $skill) {
            $sl = strtolower($skill);
            if (in_array($sl, $langKw, true)) {
                $groups['Languages'][] = $skill;
            } elseif (in_array($sl, $fwKw, true)) {
                $groups['Frameworks'][] = $skill;
            } elseif (in_array($sl, $dbKw, true)) {
                $groups['Databases'][] = $skill;
            } elseif (in_array($sl, $toolKw, true)) {
                $groups['Tools'][] = $skill;
            } else {
                $groups['Other'][] = $skill;
            }
        }

        if (empty($groups['Languages']) && empty($groups['Frameworks']) && !empty($groups['Other'])) {
            return ['Skills' => $groups['Other']];
        }

        unset($groups['Other']);

        return array_filter($groups);
    }

    private function uniqueSkills(array $skills): array
    {
        $seen = [];
        $out  = [];

        foreach ($skills as $skill) {
            if (is_string($skill)) {
                $skill = trim($skill);
            } elseif (!is_scalar($skill)) {
                continue;
            } else {
                $skill = trim((string) $skill);
            }

            if ($skill === '' || strlen($skill) > 55) {
                continue;
            }

            $key = strtolower($skill);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[]      = $skill;
        }

        return $out;
    }

    private function isGarbageEducation(array $edu): bool
    {
        $school = strtolower((string) ($edu['school'] ?? $edu['institution'] ?? ''));
        $degree = strtolower((string) ($edu['degree'] ?? ''));

        if (strlen($school) > 90) {
            return true;
        }

        foreach (['professional summary', 'technical skills', 'experience', 'projects', 'portfolio', 'linkedin', 'github'] as $needle) {
            if (str_contains($school, $needle) || str_contains($degree, $needle)) {
                return true;
            }
        }

        $degreeRaw = (string) ($edu['degree'] ?? '');
        if ($degreeRaw !== ''
            && ResumeTextNormalizer::isLikelyPersonName($degreeRaw)
            && !ResumeTextNormalizer::isValidDegreeLine($degreeRaw)) {
            return true;
        }

        return $degree === 'academic & professional background' && strlen($school) > 40;
    }

    private function normalizeLinks(array $links): array
    {
        $linkedin = '';
        $github   = '';

        foreach ($links as $link) {
            if (!is_string($link) || trim($link) === '') {
                continue;
            }

            $link = trim($link);
            if (stripos($link, 'linkedin') !== false) {
                $linkedin = $link;
            } elseif (stripos($link, 'github') !== false) {
                $github = $link;
            }
        }

        return [
            'linkedin_url'   => $linkedin,
            'linkedin_label' => $this->linkLabel($linkedin, 'LinkedIn'),
            'github_url'     => $github,
            'github_label'   => $this->linkLabel($github, 'GitHub'),
        ];
    }

    private function linkLabel(string $url, string $fallback): string
    {
        if ($url === '') {
            return '';
        }

        $label = preg_replace('#^https?://(www\.)?#i', '', $url);
        $label = rtrim((string) $label, '/');

        return $label !== '' ? $label : $fallback;
    }

    private function escapeUrl(string $url): string
    {
        $url = $this->sanitizeForLatex($url);

        return str_replace(['%', '#', '&'], ['\%', '\#', '\&'], $url);
    }

    private function emailForHref(string $email): string
    {
        $email = trim($email);

        if ($email === '') {
            return '';
        }

        return preg_replace('/[^\w.@+-]/', '', $email) ?? '';
    }

    private function sanitizeForLatex(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function escape(string $text): string
    {
        $text = $this->sanitizeForLatex($text);

        return strtr($text, [
            '\\' => '\textbackslash{}',
            '&'  => '\&',
            '%'  => '\%',
            '$'  => '\$',
            '#'  => '\#',
            '_'  => '\_',
            '{'  => '\{',
            '}'  => '\}',
            '~'  => '\textasciitilde{}',
            '^'  => '\textasciicircum{}',
        ]);
    }

    private function validator(): PdfBinaryValidator
    {
        return $this->pdfValidator ??= app(PdfBinaryValidator::class);
    }
}
