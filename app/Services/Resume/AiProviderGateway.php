<?php

namespace App\Services\Resume;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiProviderGateway
{
    private const TIMEOUT_SECONDS = 90;
    private const MAX_OUTPUT_TOKENS = 3200;

    public function complete(string $purpose, string $systemPrompt, string $userPrompt, array $metadata = []): array
    {
        $providers = $this->providers();
        $attempts = [];

        Log::info('[AI_REQUEST_STARTED]', [
            'purpose' => $purpose,
            'providers_configured' => array_values(array_filter(
                array_map(fn (array $provider) => $provider['key_present'] ? $provider['name'] : null, $providers)
            )),
        ] + $this->safeMetadata($metadata));
        Log::info('[AI_STARTED]', [
            'purpose' => $purpose,
        ] + $this->safeMetadata($metadata));

        foreach ($providers as $index => $provider) {
            if (!$provider['key_present']) {
                $attempts[] = [
                    'provider' => $provider['name'],
                    'model' => $provider['model'],
                    'success' => false,
                    'error' => 'api_key_missing',
                ];
                continue;
            }

            if ($index > 0) {
                Log::warning('[FALLBACK_TRIGGERED]', [
                    'purpose' => $purpose,
                    'provider' => $provider['name'],
                    'model' => $provider['model'],
                ]);
            }

            Log::info('[AI_PROVIDER_SELECTED]', [
                'purpose' => $purpose,
                'provider' => $provider['name'],
                'model' => $provider['model'],
            ]);
            Log::info('[MODEL_USED]', [
                'purpose' => $purpose,
                'provider' => $provider['name'],
                'model' => $provider['model'],
            ]);

            try {
                $startedAt = microtime(true);
                Log::info('[API_REQUEST_SENT]', [
                    'purpose' => $purpose,
                    'provider' => $provider['name'],
                    'endpoint_host' => parse_url($provider['endpoint'], PHP_URL_HOST),
                ]);

                $response = $this->callProvider($provider, $systemPrompt, $userPrompt);
                $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

                Log::info('[API_RESPONSE_RECEIVED]', [
                    'purpose' => $purpose,
                    'provider' => $provider['name'],
                    'status' => $response->status(),
                    'latency_ms' => $latencyMs,
                    'body_size' => strlen($response->body()),
                ]);

                if (!$response->successful()) {
                    throw new \RuntimeException('Provider returned HTTP ' . $response->status());
                }

                $json = $response->json();
                $content = $this->extractContent(is_array($json) ? $json : []);

                if (trim($content) === '') {
                    throw new \RuntimeException('Provider returned an empty content payload');
                }

                $tokens = $this->extractTokenUsage(is_array($json) ? $json : []);
                Log::info('[TOKENS_RETURNED]', [
                    'purpose' => $purpose,
                    'provider' => $provider['name'],
                    'input_tokens' => $tokens['input'] ?? null,
                    'output_tokens' => $tokens['output'] ?? null,
                    'total_tokens' => $tokens['total'] ?? null,
                ]);

                Log::info('[OPTIMIZED_CONTENT_GENERATED]', [
                    'purpose' => $purpose,
                    'provider' => $provider['name'],
                    'model' => $provider['model'],
                    'content_length' => strlen($content),
                ]);
                Log::info('[AI_COMPLETED]', [
                    'purpose' => $purpose,
                    'provider' => $provider['name'],
                    'model' => $provider['model'],
                ]);

                $attempts[] = [
                    'provider' => $provider['name'],
                    'model' => $provider['model'],
                    'success' => true,
                    'status' => $response->status(),
                    'latency_ms' => $latencyMs,
                ];

                return [
                    'success' => true,
                    'provider' => $provider['name'],
                    'model' => $provider['model'],
                    'content' => $content,
                    'tokens' => $tokens,
                    'status' => $response->status(),
                    'attempts' => $attempts,
                ];
            } catch (\Throwable $e) {
                if ($index === 0) {
                    Log::error('[PRIMARY_AI_FAILED]', [
                        'purpose' => $purpose,
                        'provider' => $provider['name'],
                        'model' => $provider['model'],
                        'error_class' => $e::class,
                        'error' => $e->getMessage(),
                    ]);
                } else {
                    Log::error('[AI_PROVIDER_FAILED]', [
                        'purpose' => $purpose,
                        'provider' => $provider['name'],
                        'model' => $provider['model'],
                        'error_class' => $e::class,
                        'error' => $e->getMessage(),
                    ]);
                }

                $attempts[] = [
                    'provider' => $provider['name'],
                    'model' => $provider['model'],
                    'success' => false,
                    'error_class' => $e::class,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'All configured AI providers failed or are missing credentials.',
            'attempts' => $attempts,
        ];
    }

    public function providers(): array
    {
        return [
            [
                'name' => 'openai',
                'api_key' => (string) config('services.openai.api_key', ''),
                'key_present' => filled(config('services.openai.api_key')),
                'model' => (string) config('services.openai.model', 'gpt-5.1'),
                'endpoint' => (string) config('services.openai.endpoint', 'https://api.openai.com/v1/responses'),
            ],
            [
                'name' => 'anthropic',
                'api_key' => (string) config('services.anthropic.api_key', ''),
                'key_present' => filled(config('services.anthropic.api_key')),
                'model' => (string) config('services.anthropic.model', 'claude-3-5-sonnet-latest'),
                'endpoint' => (string) config('services.anthropic.endpoint', 'https://api.anthropic.com/v1/messages'),
            ],
            [
                'name' => 'openrouter',
                'api_key' => (string) config('services.openrouter.api_key', ''),
                'key_present' => filled(config('services.openrouter.api_key')),
                'model' => (string) config('services.openrouter.model', 'deepseek/deepseek-chat'),
                'endpoint' => (string) config('services.openrouter.endpoint', 'https://openrouter.ai/api/v1/chat/completions'),
            ],
        ];
    }

    private function callProvider(array $provider, string $systemPrompt, string $userPrompt): Response
    {
        return match ($provider['name']) {
            'openai' => $this->callOpenAi($provider, $systemPrompt, $userPrompt),
            'anthropic' => $this->callAnthropic($provider, $systemPrompt, $userPrompt),
            'openrouter' => $this->callOpenRouter($provider, $systemPrompt, $userPrompt),
            default => throw new \InvalidArgumentException('Unknown AI provider: ' . $provider['name']),
        };
    }

    private function callOpenAi(array $provider, string $systemPrompt, string $userPrompt): Response
    {
        $request = Http::withToken($provider['api_key'])
            ->acceptJson()
            ->timeout(self::TIMEOUT_SECONDS);

        if (str_contains($provider['endpoint'], '/responses')) {
            return $request->post($provider['endpoint'], [
                'model' => $provider['model'],
                'input' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_output_tokens' => self::MAX_OUTPUT_TOKENS,
            ]);
        }

        return $request->post($provider['endpoint'], [
            'model' => $provider['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => self::MAX_OUTPUT_TOKENS,
            'temperature' => 0.2,
        ]);
    }

    private function callAnthropic(array $provider, string $systemPrompt, string $userPrompt): Response
    {
        return Http::withHeaders([
            'x-api-key' => $provider['api_key'],
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->acceptJson()->timeout(self::TIMEOUT_SECONDS)->post($provider['endpoint'], [
            'model' => $provider['model'],
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => self::MAX_OUTPUT_TOKENS,
            'temperature' => 0.2,
        ]);
    }

    private function callOpenRouter(array $provider, string $systemPrompt, string $userPrompt): Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $provider['api_key'],
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name', 'Resume Optimizer'),
        ])->acceptJson()->timeout(self::TIMEOUT_SECONDS)->post($provider['endpoint'], [
            'model' => $provider['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => self::MAX_OUTPUT_TOKENS,
            'temperature' => 0.2,
        ]);
    }

    private function extractContent(array $json): string
    {
        if (isset($json['output_text']) && is_string($json['output_text'])) {
            return $json['output_text'];
        }

        if (isset($json['choices'][0]['message']['content'])) {
            $content = $json['choices'][0]['message']['content'];
            if (is_string($content)) {
                return $content;
            }
            if (is_array($content)) {
                return $this->flattenContentParts($content);
            }
        }

        if (isset($json['content']) && is_array($json['content'])) {
            return $this->flattenContentParts($json['content']);
        }

        if (isset($json['output']) && is_array($json['output'])) {
            $parts = [];
            foreach ($json['output'] as $outputItem) {
                if (!is_array($outputItem)) {
                    continue;
                }

                if (isset($outputItem['content']) && is_array($outputItem['content'])) {
                    $parts[] = $this->flattenContentParts($outputItem['content']);
                }
            }

            return trim(implode("\n", array_filter($parts)));
        }

        return '';
    }

    private function flattenContentParts(array $parts): string
    {
        $text = [];

        foreach ($parts as $part) {
            if (is_string($part)) {
                $text[] = $part;
                continue;
            }

            if (!is_array($part)) {
                continue;
            }

            foreach (['text', 'output_text', 'content'] as $key) {
                if (isset($part[$key]) && is_string($part[$key])) {
                    $text[] = $part[$key];
                    break;
                }
            }
        }

        return trim(implode("\n", $text));
    }

    private function extractTokenUsage(array $json): array
    {
        $usage = $json['usage'] ?? [];

        if (!is_array($usage)) {
            return [];
        }

        return [
            'input' => $usage['input_tokens'] ?? $usage['prompt_tokens'] ?? null,
            'output' => $usage['output_tokens'] ?? $usage['completion_tokens'] ?? null,
            'total' => $usage['total_tokens'] ?? null,
        ];
    }

    private function safeMetadata(array $metadata): array
    {
        unset($metadata['api_key'], $metadata['authorization'], $metadata['secret']);

        return $metadata;
    }
}
