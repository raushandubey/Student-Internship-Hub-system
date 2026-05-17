<?php

namespace App\Services\Resume;

use Illuminate\Support\Facades\Http;

class AiProductionDiagnosticsService
{
    public function __construct(
        private readonly AiProviderGateway $gateway,
        private readonly PdfBinaryValidator $pdfValidator,
    ) {}

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
            'providers' => $this->providerDiagnostics($probe),
            'pdf_renderer' => $this->pdfRendererDiagnostics(),
        ];
    }

    private function envPresence(): array
    {
        return [
            'OPENAI_API_KEY' => $this->presence(env('OPENAI_API_KEY'), config('services.openai.api_key')),
            'ANTHROPIC_API_KEY' => $this->presence(env('ANTHROPIC_API_KEY'), config('services.anthropic.api_key')),
            'OPENROUTER_API_KEY' => $this->presence(env('OPENROUTER_API_KEY'), config('services.openrouter.api_key')),
            'LATEXLITE_API_KEY' => $this->presence(env('LATEXLITE_API_KEY'), config('services.latexlite.api_key')),
        ];
    }

    private function presence(mixed $envValue, mixed $configValue): array
    {
        return [
            'env_present' => filled($envValue),
            'config_present' => filled($configValue),
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

    private function pdfRendererDiagnostics(): array
    {
        $syntheticValidation = $this->pdfValidator->validate("%PDF-1.4\n%%EOF");

        return [
            'latexlite' => [
                'configured' => filled(config('services.latexlite.api_key')) && filled(config('services.latexlite.url')),
                'endpoint_host' => parse_url((string) config('services.latexlite.url'), PHP_URL_HOST),
                'api_key_present' => filled(config('services.latexlite.api_key')),
            ],
            'dompdf' => [
                'available' => class_exists(\Barryvdh\DomPDF\Facade\Pdf::class),
            ],
            'binary_validator' => $syntheticValidation,
        ];
    }
}
