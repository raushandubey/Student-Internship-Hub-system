<?php

namespace App\Services\Resume;

use App\Traits\ResilientJsonTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fault-tolerant n8n webhook client with retries and schema validation.
 */
class N8nOrchestrator
{
    use ResilientJsonTrait;

    public function __construct(
        private ?PipelineTracer $tracer = null,
        private ?AiProductionDiagnosticsService $diagnostics = null,
    ) {}

    /**
     * Attach a diagnostics service for production failure logging.
     */
    public function withDiagnostics(AiProductionDiagnosticsService $diagnostics): static
    {
        $this->diagnostics = $diagnostics;

        return $this;
    }

    /**
     * Dispatch a request to the n8n webhook with exponential backoff retry logic.
     *
     * Retries up to $maxAttempts times with delays of 2^attempt seconds (2s, 4s, 8s).
     * On final failure, returns a structured error response with diagnostic info and
     * continues the pipeline in degraded mode (n8n-dependent steps are skipped).
     *
     * @return array{
     *   success: bool,
     *   data: ?array,
     *   error: ?string,
     *   attempts: array<int, array{attempt: int, error: string, delay_seconds: int}>,
     *   last_error: ?string,
     *   diagnostic: array{correlation_id: ?string, degraded_mode: bool, skipped_steps: string[]}
     * }
     */
    public function dispatch(string $systemPrompt, string $userPrompt, int $maxAttempts = 3): array
    {
        $webhookUrl    = config('services.n8n.webhook_url');
        $apiKey        = config('services.resume_intelligence.api_key');
        $correlationId = $this->tracer?->correlationId();

        if (empty($webhookUrl)) {
            return [
                'success'    => false,
                'data'       => null,
                'error'      => 'webhook_not_configured',
                'attempts'   => [],
                'last_error' => 'webhook_not_configured',
                'diagnostic' => [
                    'correlation_id' => $correlationId,
                    'degraded_mode'  => true,
                    'skipped_steps'  => ['n8n_orchestration'],
                ],
            ];
        }

        $attempt      = 0;
        $lastError    = null;
        $attemptLog   = [];

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $startedAt = microtime(true);

                $response = Http::withHeaders(array_merge([
                    'X-Resume-Intelligence-Key' => $apiKey,
                    'Content-Type'              => 'application/json',
                ], $this->tracer?->headers() ?? []))
                    ->connectTimeout(15)
                    ->timeout(30)
                    ->post($webhookUrl, [
                        'system_prompt' => $systemPrompt,
                        'user_prompt'   => $userPrompt,
                    ]);

                $duration = microtime(true) - $startedAt;

                if (!$response->successful()) {
                    $lastError = 'http_' . $response->status();

                    Log::warning('N8nOrchestrator: HTTP failure', [
                        'status'         => $response->status(),
                        'attempt'        => $attempt,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->diagnostics?->logApiFailure(
                        provider: 'n8n',
                        endpoint: $webhookUrl,
                        exception: new \RuntimeException('N8N webhook returned HTTP ' . $response->status()),
                        context: [
                            'attempt'        => $attempt,
                            'status'         => $response->status(),
                            'correlation_id' => $correlationId,
                            'failure_reason' => $lastError,
                            'payload_size'   => strlen($systemPrompt) + strlen($userPrompt),
                        ],
                        correlationId: $correlationId,
                    );

                    $attemptLog[] = [
                        'attempt'       => $attempt,
                        'error'         => $lastError,
                        'delay_seconds' => 2 ** $attempt,
                    ];

                    // Exponential backoff: 2s after attempt 1, 4s after attempt 2, 8s after attempt 3
                    if ($attempt < $maxAttempts) {
                        sleep(2 ** $attempt);
                    }
                    continue;
                }

                $json = $response->json();
                if (!is_array($json) || !($json['success'] ?? false)) {
                    $lastError = 'invalid_response_schema';

                    $this->diagnostics?->logApiFailure(
                        provider: 'n8n',
                        endpoint: $webhookUrl,
                        exception: new \RuntimeException('N8N webhook returned invalid response schema'),
                        context: [
                            'attempt'        => $attempt,
                            'correlation_id' => $correlationId,
                            'failure_reason' => $lastError,
                        ],
                        correlationId: $correlationId,
                    );

                    $attemptLog[] = [
                        'attempt'       => $attempt,
                        'error'         => $lastError,
                        'delay_seconds' => 2 ** $attempt,
                    ];

                    if ($attempt < $maxAttempts) {
                        sleep(2 ** $attempt);
                    }
                    continue;
                }

                $data = $this->safeJsonDecode($json['data'] ?? '');
                if (!$data) {
                    $lastError = 'invalid_data_json';

                    $attemptLog[] = [
                        'attempt'       => $attempt,
                        'error'         => $lastError,
                        'delay_seconds' => 2 ** $attempt,
                    ];

                    if ($attempt < $maxAttempts) {
                        sleep(2 ** $attempt);
                    }
                    continue;
                }

                // Log successful call
                $this->diagnostics?->logApiCall(
                    provider: 'n8n',
                    endpoint: $webhookUrl,
                    request: ['attempt' => $attempt, 'correlation_id' => $correlationId],
                    response: ['status' => $response->status(), 'body_size' => strlen($response->body())],
                    duration: $duration,
                    correlationId: $correlationId,
                );

                return [
                    'success'    => true,
                    'data'       => $data,
                    'error'      => null,
                    'attempts'   => $attemptLog,
                    'last_error' => null,
                    'diagnostic' => [
                        'correlation_id' => $correlationId,
                        'degraded_mode'  => false,
                        'skipped_steps'  => [],
                    ],
                ];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();

                Log::error('N8nOrchestrator: exception', [
                    'error'          => $lastError,
                    'attempt'        => $attempt,
                    'correlation_id' => $correlationId,
                ]);

                $this->diagnostics?->logApiFailure(
                    provider: 'n8n',
                    endpoint: $webhookUrl,
                    exception: $e,
                    context: [
                        'attempt'        => $attempt,
                        'correlation_id' => $correlationId,
                        'failure_reason' => $lastError,
                        'payload_size'   => strlen($systemPrompt) + strlen($userPrompt),
                    ],
                    correlationId: $correlationId,
                );

                $attemptLog[] = [
                    'attempt'       => $attempt,
                    'error'         => $lastError,
                    'delay_seconds' => 2 ** $attempt,
                ];

                // Exponential backoff: 2s after attempt 1, 4s after attempt 2, 8s after attempt 3
                if ($attempt < $maxAttempts) {
                    sleep(2 ** $attempt);
                }
            }
        }

        // All attempts exhausted — log structured diagnostic and enter degraded mode
        // (pipeline continues without n8n-dependent steps)
        Log::error('N8nOrchestrator: all attempts exhausted, entering degraded mode', [
            'attempts'       => $attemptLog,
            'last_error'     => $lastError,
            'correlation_id' => $correlationId,
        ]);

        return [
            'success'    => false,
            'data'       => null,
            'error'      => $lastError,
            'attempts'   => $attemptLog,
            'last_error' => $lastError,
            'diagnostic' => [
                'correlation_id' => $correlationId,
                'degraded_mode'  => true,
                'skipped_steps'  => ['n8n_orchestration'],
            ],
        ];
    }
}
