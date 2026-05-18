<?php

namespace App\Services\Resume;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiProductionDiagnosticsService
{
    /**
     * In-memory store for per-correlation-ID call and failure records.
     * Keyed by correlation ID.
     *
     * @var array<string, array{api_calls: list<array<string,mixed>>, failures: list<array<string,mixed>>}>
     */
    private array $store = [];

    public function __construct(
        private readonly AiProviderGateway $gateway,
        private readonly PdfBinaryValidator $pdfValidator,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Enterprise-Grade Logging API (Requirements 2.16, 2.17, 2.18)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Log a successful (or completed) API call with request/response details.
     *
     * Sanitizes all data before logging: API keys, tokens, and secrets are
     * masked — only key names are logged, never values.
     *
     * @param  string  $provider     e.g. 'openai', 'openrouter', 'latexlite', 'n8n'
     * @param  string  $endpoint     Full URL or path called
     * @param  array   $request      Request payload/headers (will be sanitized)
     * @param  array   $response     Response payload/headers (will be sanitized)
     * @param  float   $duration     Wall-clock duration in seconds
     * @param  string|null $correlationId  Request tracing ID (auto-generated if null)
     */
    public function logApiCall(
        string $provider,
        string $endpoint,
        array $request,
        array $response,
        float $duration,
        ?string $correlationId = null,
    ): void {
        $correlationId ??= (string) Str::uuid();

        $record = [
            'event'          => 'API_CALL',
            'provider'       => $provider,
            'endpoint'       => $this->sanitizeEndpoint($endpoint),
            'correlation_id' => $correlationId,
            'duration_ms'    => (int) round($duration * 1000),
            'request'        => $this->sanitizePayload($request),
            'response'       => $this->sanitizePayload($response),
            'status_code'    => $response['status'] ?? $response['http_status'] ?? null,
            'environment'    => app()->environment(),
            'timestamp'      => now()->toISOString(),
        ];

        $this->storeRecord($correlationId, 'api_calls', $record);

        Log::channel('diagnostic')->info('[AI_DIAGNOSTIC_API_CALL]', $record);
        Log::info('[AI_DIAGNOSTIC_API_CALL]', [
            'provider'       => $provider,
            'correlation_id' => $correlationId,
            'duration_ms'    => $record['duration_ms'],
            'status_code'    => $record['status_code'],
        ]);
    }

    /**
     * Log a production API failure with full exception context and environment state.
     *
     * Captures production-specific failure signals: SSL verification errors,
     * timeouts, permission issues, config caching problems.
     *
     * @param  string     $provider     e.g. 'openai', 'openrouter', 'latexlite', 'n8n'
     * @param  string     $endpoint     Full URL or path that failed
     * @param  \Throwable $exception    The exception that was thrown
     * @param  array      $context      Additional context (attempt number, payload, etc.)
     * @param  string|null $correlationId  Request tracing ID
     */
    public function logApiFailure(
        string $provider,
        string $endpoint,
        \Throwable $exception,
        array $context = [],
        ?string $correlationId = null,
    ): void {
        $correlationId ??= (string) Str::uuid();

        $failureType = $this->classifyFailure($exception);

        $record = [
            'event'           => 'API_FAILURE',
            'provider'        => $provider,
            'endpoint'        => $this->sanitizeEndpoint($endpoint),
            'correlation_id'  => $correlationId,
            'failure_type'    => $failureType,
            'error_class'     => $exception::class,
            'error_message'   => $exception->getMessage(),
            'error_code'      => $exception->getCode(),
            'context'         => $this->sanitizePayload($context),
            'environment'     => $this->captureEnvironmentState(),
            'timestamp'       => now()->toISOString(),
        ];

        $this->storeRecord($correlationId, 'failures', $record);

        Log::channel('diagnostic')->error('[AI_DIAGNOSTIC_API_FAILURE]', $record);
        Log::error('[AI_DIAGNOSTIC_API_FAILURE]', [
            'provider'        => $provider,
            'correlation_id'  => $correlationId,
            'failure_type'    => $failureType,
            'error_class'     => $exception::class,
            'error_message'   => $exception->getMessage(),
        ]);
    }

    /**
     * Generate a structured diagnostic report for a given correlation ID.
     *
     * Returns all API calls, failures, sanitized environment state, and
     * actionable recommendations for the given request trace.
     *
     * @param  string  $correlationId  The correlation ID to report on
     * @return array{
     *   correlation_id: string,
     *   api_calls: list<array<string,mixed>>,
     *   failures: list<array<string,mixed>>,
     *   environment: array<string,mixed>,
     *   recommendations: list<string>,
     *   generated_at: string,
     * }
     */
    public function generateDiagnosticReport(string $correlationId): array
    {
        $data     = $this->store[$correlationId] ?? ['api_calls' => [], 'failures' => []];
        $apiCalls = $data['api_calls'];
        $failures = $data['failures'];

        $environment     = $this->captureEnvironmentState();
        $recommendations = $this->buildRecommendations($failures, $environment);

        $report = [
            'correlation_id'  => $correlationId,
            'api_calls'       => $apiCalls,
            'failures'        => $failures,
            'environment'     => $environment,
            'recommendations' => $recommendations,
            'generated_at'    => now()->toISOString(),
        ];

        Log::channel('diagnostic')->info('[AI_DIAGNOSTIC_REPORT_GENERATED]', [
            'correlation_id'   => $correlationId,
            'api_call_count'   => count($apiCalls),
            'failure_count'    => count($failures),
            'recommendation_count' => count($recommendations),
        ]);

        return $report;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Store a record in the in-memory store for later report generation.
     */
    private function storeRecord(string $correlationId, string $bucket, array $record): void
    {
        if (!isset($this->store[$correlationId])) {
            $this->store[$correlationId] = ['api_calls' => [], 'failures' => []];
        }
        $this->store[$correlationId][$bucket][] = $record;
    }

    /**
     * Sanitize a payload array: mask secret values, keep key names only.
     *
     * Secret keys: api_key, authorization, token, secret, password, key, bearer.
     */
    private function sanitizePayload(array $payload): array
    {
        $secretPatterns = [
            'api_key', 'apikey', 'api-key',
            'authorization', 'auth',
            'token', 'access_token', 'refresh_token',
            'secret', 'client_secret',
            'password', 'passwd',
            'bearer',
            'x-api-key', 'x-resume-intelligence-key',
        ];

        $sanitized = [];
        foreach ($payload as $key => $value) {
            $lowerKey = strtolower((string) $key);

            $isSensitive = false;
            foreach ($secretPatterns as $pattern) {
                if (str_contains($lowerKey, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED:' . $lowerKey . ']';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
            } elseif (is_string($value) && strlen($value) > 2000) {
                // Truncate very large string values (e.g. full resume text in request)
                $sanitized[$key] = substr($value, 0, 500) . '...[truncated:' . strlen($value) . 'chars]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize an endpoint URL: strip any embedded credentials or tokens from query strings.
     */
    private function sanitizeEndpoint(string $endpoint): string
    {
        // Remove query parameters that may contain tokens
        $parsed = parse_url($endpoint);
        if (!$parsed) {
            return $endpoint;
        }

        $clean = ($parsed['scheme'] ?? 'https') . '://'
            . ($parsed['host'] ?? '')
            . (isset($parsed['port']) ? ':' . $parsed['port'] : '')
            . ($parsed['path'] ?? '');

        return $clean;
    }

    /**
     * Classify a Throwable into a production-specific failure type.
     */
    private function classifyFailure(\Throwable $exception): string
    {
        $message = strtolower($exception->getMessage());
        $class   = strtolower($exception::class);

        if (str_contains($message, 'ssl') || str_contains($message, 'certificate') || str_contains($message, 'verify')) {
            return 'ssl_verification_error';
        }
        if (str_contains($message, 'timed out') || str_contains($message, 'timeout') || str_contains($class, 'timeout')) {
            return 'timeout';
        }
        if (str_contains($message, 'connection refused') || str_contains($message, 'connection reset')) {
            return 'connection_refused';
        }
        if (str_contains($message, 'dns') || str_contains($message, 'could not resolve')) {
            return 'dns_resolution_error';
        }
        if (str_contains($message, '401') || str_contains($message, 'unauthorized') || str_contains($message, 'authentication')) {
            return 'authentication_error';
        }
        if (str_contains($message, '403') || str_contains($message, 'forbidden') || str_contains($message, 'permission')) {
            return 'permission_error';
        }
        if (str_contains($message, '429') || str_contains($message, 'rate limit') || str_contains($message, 'too many requests')) {
            return 'rate_limited';
        }
        if (str_contains($message, 'config') || str_contains($message, 'cached')) {
            return 'config_caching_issue';
        }
        if (str_contains($message, 'http 5') || str_contains($message, 'server error')) {
            return 'provider_server_error';
        }

        return 'unknown_error';
    }

    /**
     * Capture sanitized environment state for diagnostic context.
     * Never logs secret values — only key presence and config state.
     */
    private function captureEnvironmentState(): array
    {
        return [
            'app_environment'    => app()->environment(),
            'config_cached'      => app()->configurationIsCached(),
            'cache_driver'       => config('cache.default'),
            'ssl_verify'         => (bool) config('services.openai.verify_ssl', true),
            'openai_timeout'     => config('services.openai.timeout', 35),
            'openrouter_timeout' => config('services.openrouter.timeout', 35),
            'latexlite_timeout'  => config('services.latexlite.timeout', 60),
            'n8n_timeout'        => config('services.n8n.timeout', 30),
            'api_keys_present'   => [
                'OPENAI_API_KEY'              => filled(config('services.openai.api_key')),
                'ANTHROPIC_API_KEY'           => filled(config('services.anthropic.api_key')),
                'OPENROUTER_API_KEY'          => filled(config('services.openrouter.api_key')),
                'LATEXLITE_API_KEY'           => filled(config('services.latexlite.api_key')),
                'N8N_WEBHOOK_URL'             => filled(config('services.n8n.webhook_url')),
                'RESUME_INTELLIGENCE_API_KEY' => filled(config('services.resume_intelligence.api_key')),
            ],
            'php_version'        => PHP_VERSION,
            'laravel_version'    => app()->version(),
        ];
    }

    /**
     * Build actionable recommendations based on observed failures and environment state.
     *
     * @param  list<array<string,mixed>>  $failures
     * @param  array<string,mixed>        $environment
     * @return list<string>
     */
    private function buildRecommendations(array $failures, array $environment): array
    {
        $recommendations = [];
        $failureTypes    = array_column($failures, 'failure_type');

        if (in_array('ssl_verification_error', $failureTypes, true)) {
            $recommendations[] = 'SSL verification failed. Check that the server CA bundle is up to date (curl.cainfo in php.ini). In production, never disable SSL verification.';
        }

        if (in_array('timeout', $failureTypes, true)) {
            $recommendations[] = 'API call timed out. Consider increasing timeout values in config/services.php. Current OpenAI timeout: ' . ($environment['openai_timeout'] ?? 'unknown') . 's.';
        }

        if (in_array('dns_resolution_error', $failureTypes, true)) {
            $recommendations[] = 'DNS resolution failed. Verify outbound DNS is configured correctly on the server. Check /etc/resolv.conf or equivalent.';
        }

        if (in_array('authentication_error', $failureTypes, true)) {
            $recommendations[] = 'Authentication failed (HTTP 401). Verify API keys are set correctly in environment variables and that config cache is cleared after changes (php artisan config:clear).';
        }

        if (in_array('permission_error', $failureTypes, true)) {
            $recommendations[] = 'Permission denied (HTTP 403). Verify the API key has the required scopes/permissions for the requested operation.';
        }

        if (in_array('rate_limited', $failureTypes, true)) {
            $recommendations[] = 'Rate limit exceeded (HTTP 429). Implement request queuing or reduce request frequency. Consider upgrading the API plan.';
        }

        if (in_array('config_caching_issue', $failureTypes, true)) {
            $recommendations[] = 'Config caching issue detected. Run "php artisan config:clear && php artisan config:cache" to refresh the configuration cache.';
        }

        if (in_array('connection_refused', $failureTypes, true)) {
            $recommendations[] = 'Connection refused. Verify the API endpoint URL is correct and the service is reachable from this server. Check firewall rules.';
        }

        if ($environment['config_cached'] && !empty($failures)) {
            $recommendations[] = 'Config is cached. If you recently changed environment variables, run "php artisan config:clear" to apply them.';
        }

        // Check for missing API keys
        $missingKeys = array_keys(array_filter(
            $environment['api_keys_present'] ?? [],
            fn ($present) => !$present
        ));
        if (!empty($missingKeys)) {
            $recommendations[] = 'Missing API keys detected: ' . implode(', ', $missingKeys) . '. Set these in your .env file and clear config cache.';
        }

        if (empty($recommendations) && !empty($failures)) {
            $recommendations[] = 'Unknown failures detected. Review the full diagnostic log at storage/logs/ai-diagnostics.log for detailed error context.';
        }

        return $recommendations;
    }

    public function run(bool $probe = true): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'app' => [
                'environment' => app()->environment(),
                'config_cached' => app()->configurationIsCached(),
                'cache_driver' => config('cache.default'),
            ],
            'env_presence' => $this->envPresence(),
            'outbound_hosts' => [
                'api.openai.com',
                'openrouter.ai',
                parse_url((string) config('services.latexlite.url'), PHP_URL_HOST) ?: 'latexlite.com',
            ],
            'providers' => $this->providerDiagnostics($probe),
            'pdf_renderer' => $this->pdfRendererDiagnostics($probe),
            'n8n' => $this->n8nDiagnostics($probe),
        ];
    }

    private function envPresence(): array
    {
        return [
            'OPENAI_API_KEY' => $this->presence(env('OPENAI_API_KEY'), config('services.openai.api_key')),
            'ANTHROPIC_API_KEY' => $this->presence(env('ANTHROPIC_API_KEY'), config('services.anthropic.api_key')),
            'OPENROUTER_API_KEY' => $this->presence(env('OPENROUTER_API_KEY'), config('services.openrouter.api_key')),
            'LATEXLITE_API_KEY' => $this->presence(env('LATEXLITE_API_KEY'), config('services.latexlite.api_key')),
            'N8N_WEBHOOK_URL' => $this->presence(env('N8N_WEBHOOK_URL'), config('services.n8n.webhook_url')),
            'RESUME_INTELLIGENCE_API_KEY' => $this->presence(env('RESUME_INTELLIGENCE_API_KEY'), config('services.resume_intelligence.api_key')),
        ];
    }

    private function presence(mixed $envValue, mixed $configValue): array
    {
        $envPresent = filled($envValue);
        $configPresent = filled($configValue);

        return [
            'env_present' => $envPresent,
            'config_present' => $configPresent,
            'mismatch' => $envPresent && !$configPresent,
            'redacted' => true,
        ];
    }

    private function providerDiagnostics(bool $probe): array
    {
        $diagnostics = [];

        foreach ($this->gateway->providers() as $provider) {
            $diagnostics[$provider['name']] = [
                'configured' => $provider['key_present'],
                'model' => $provider['model'],
                'endpoint_host' => parse_url($provider['endpoint'], PHP_URL_HOST),
                'connectivity' => $this->probeProvider($provider, $probe),
            ];
        }

        return $diagnostics;
    }

    private function probeProvider(array $provider, bool $probe): array
    {
        if (!$provider['key_present']) {
            return [
                'status' => 'not_configured',
                'http_status' => null,
                'error_class' => null,
                'error' => 'api_key_missing',
            ];
        }

        if (!$probe) {
            return [
                'status' => 'skipped',
                'http_status' => null,
                'error_class' => null,
                'error' => null,
            ];
        }

        try {
            $response = match ($provider['name']) {
                'openai' => Http::withToken($provider['api_key'])
                    ->acceptJson()
                    ->timeout(10)
                    ->get('https://api.openai.com/v1/models/' . rawurlencode($provider['model'])),
                'anthropic' => Http::withHeaders([
                    'x-api-key' => $provider['api_key'],
                    'anthropic-version' => '2023-06-01',
                ])->acceptJson()
                    ->timeout(10)
                    ->get('https://api.anthropic.com/v1/models/' . rawurlencode($provider['model'])),
                'openrouter' => Http::withHeaders([
                    'Authorization' => 'Bearer ' . $provider['api_key'],
                ])->acceptJson()
                    ->timeout(10)
                    ->get('https://openrouter.ai/api/v1/models'),
                default => throw new \InvalidArgumentException('Unknown provider: ' . $provider['name']),
            };

            return [
                'status' => $response->successful() ? 'ok' : 'failed',
                'http_status' => $response->status(),
                'model_access' => $this->modelAccess($provider, $response->body(), $response->successful()),
                'body_snippet' => substr($response->body(), 0, 240),
                'error_class' => null,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'exception',
                'http_status' => null,
                'model_access' => false,
                'body_snippet' => null,
                'error_class' => $e::class,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function modelAccess(array $provider, string $body, bool $successful): bool
    {
        if (!$successful) {
            return false;
        }

        if ($provider['name'] === 'openrouter') {
            return str_contains($body, $provider['model']);
        }

        return true;
    }

    private function pdfRendererDiagnostics(bool $probe): array
    {
        $syntheticValidation = $this->pdfValidator->validate("%PDF-1.4\n1 0 obj\n<< /Type /Page >>\n%%EOF");

        $latexliteProbe = ['status' => 'skipped'];
        if ($probe && filled(config('services.latexlite.api_key'))) {
            try {
                $response = Http::withToken((string) config('services.latexlite.api_key'))
                    ->connectTimeout(5)
                    ->timeout(10)
                    ->get('https://latexlite.com');
                $latexliteProbe = [
                    'status' => $response->successful() ? 'reachable' : 'failed',
                    'http_status' => $response->status(),
                ];
            } catch (\Throwable $e) {
                $latexliteProbe = ['status' => 'exception', 'error' => $e->getMessage()];
            }
        }

        return [
            'latexlite' => [
                'configured' => filled(config('services.latexlite.api_key')) && filled(config('services.latexlite.url')),
                'endpoint_host' => parse_url((string) config('services.latexlite.url'), PHP_URL_HOST),
                'api_key_present' => filled(config('services.latexlite.api_key')),
                'connectivity' => $latexliteProbe,
            ],
            'dompdf' => [
                'available' => class_exists(\Barryvdh\DomPDF\Facade\Pdf::class),
                'production_path' => 'disabled',
            ],
            'binary_validator' => $syntheticValidation,
        ];
    }

    private function n8nDiagnostics(bool $probe): array
    {
        $url = config('services.n8n.webhook_url');

        return [
            'configured' => filled($url),
            'endpoint_host' => $url ? parse_url((string) $url, PHP_URL_HOST) : null,
            'api_key_present' => filled(config('services.resume_intelligence.api_key')),
            'connectivity' => $probe && filled($url)
                ? $this->probeN8n((string) $url)
                : ['status' => 'skipped'],
        ];
    }

    private function probeN8n(string $url): array
    {
        try {
            $response = Http::timeout(5)->post($url, [
                'system_prompt' => 'ping',
                'user_prompt' => 'ping',
            ]);

            return [
                'status' => $response->status() < 500 ? 'reachable' : 'failed',
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'exception', 'error' => $e->getMessage()];
        }
    }
}
