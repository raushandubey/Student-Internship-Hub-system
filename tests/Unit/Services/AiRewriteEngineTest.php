<?php

use App\Services\Resume\AiProviderGateway;
use App\Services\Resume\AiRewriteEngine;
use App\Services\Resume\ResumeOptimizationQualityGate;

test('structured ai rewrite rebuilds canonical raw text instead of keeping original raw text', function () {
    $gateway = new class extends AiProviderGateway {
        public function complete(string $purpose, string $systemPrompt, string $userPrompt, array $metadata = []): array
        {
            return [
                'success' => true,
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'content' => json_encode([
                    'optimized_sections' => [
                        'summary' => [
                            'optimized' => true,
                            'content' => 'Backend developer building Laravel APIs and MySQL systems.',
                        ],
                        'skills' => [
                            'optimized' => true,
                            'content' => ['PHP', 'Laravel', 'MySQL'],
                        ],
                        'projects' => [
                            'optimized' => true,
                            'content' => [
                                [
                                    'title' => 'API Platform',
                                    'bullets' => ['Developed Laravel API endpoints backed by MySQL.'],
                                ],
                            ],
                        ],
                    ],
                ]),
                'tokens' => ['total' => 42],
                'attempts' => [],
            ];
        }
    };

    $engine = new AiRewriteEngine($gateway, app(ResumeOptimizationQualityGate::class));

    $result = $engine->rewrite(
        [
            'summary' => 'Developer.',
            'skills' => ['PHP'],
            'projects' => [
                ['title' => 'API Platform', 'bullets' => ['Worked on API features.']],
            ],
            'education' => [
                ['degree' => 'B.Tech', 'school' => 'Test University'],
            ],
            'raw_text' => 'Developer. PHP Worked on API features.',
        ],
        ['required_skills' => ['Laravel', 'MySQL'], 'job_title' => 'Backend Engineer'],
        ['missing_skills' => ['Laravel', 'MySQL']],
        ['tier' => 'average', 'locked_sections' => []]
    );

    $decoded = json_decode($result['rewritten_text'], true);

    expect($result['success'])->toBeTrue()
        ->and($decoded['raw_text'])->not->toBe('Developer. PHP Worked on API features.')
        ->and($decoded['raw_text'])->toContain('Laravel APIs')
        ->and($decoded['raw_text'])->toContain('MySQL');
});

test('rule-based fallback runs when all ai providers are unavailable', function () {
    $gateway = new class extends AiProviderGateway {
        public function complete(string $purpose, string $systemPrompt, string $userPrompt, array $metadata = []): array
        {
            return [
                'success' => false,
                'error' => 'All configured AI providers failed or are missing credentials.',
                'attempts' => [
                    ['provider' => 'openai', 'success' => false, 'error' => 'api_key_missing'],
                ],
            ];
        }
    };

    $engine = new AiRewriteEngine($gateway, app(ResumeOptimizationQualityGate::class));

    $result = $engine->rewrite(
        [
            'summary' => 'Developer looking for work.',
            'skills' => ['PHP'],
            'experience' => [
                [
                    'org' => 'Acme',
                    'title' => 'Intern',
                    'date' => '2024',
                    'bullets' => ['Worked on REST API features for internal tools.'],
                ],
            ],
            'education' => [
                ['degree' => 'B.Tech', 'school' => 'Test University'],
            ],
            'raw_text' => 'Developer. PHP Worked on REST API features.',
        ],
        [
            'required_skills' => ['Laravel', 'MySQL'],
            'job_title' => 'Backend Engineer',
            'organization' => 'Tech Corp',
        ],
        [
            'missing_skills' => ['Laravel', 'MySQL'],
            'summary_weak' => true,
        ],
        ['tier' => 'average', 'locked_sections' => [], 'preservation_mode' => false]
    );

    $decoded = json_decode($result['rewritten_text'], true);

    expect($result['success'])->toBeTrue()
        ->and($result['ai_used'])->toBeFalse()
        ->and($result['engine'])->toBe('rule_engine')
        ->and($result['mode'])->toBe('rule_based')
        ->and($decoded['skills'])->toContain('Laravel')
        ->and($decoded['skills'])->toContain('MySQL')
        ->and($decoded['summary'])->toContain('Backend Engineer')
        ->and($decoded['experience'][0]['bullets'][0])->toStartWith('Developed');
});
