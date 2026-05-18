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
        ->and($decoded['raw_text'])->toContain('Laravel')
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
        ->and($result['engine'])->toBe('light_optimizer')
        ->and($result['mode'])->toBe('light_optimized')
        ->and($decoded['skills'])->toContain('Laravel')
        ->and($decoded['skills'])->toContain('MySQL')
        ->and($decoded['summary'])->toContain('Developer looking for work')
        ->and($decoded['experience'][0]['bullets'][0])->toBe('Worked on REST API features for internal tools.');
});

test('light touch optimize preserves original summary and experience', function () {
    $engine = new AiRewriteEngine(null, app(ResumeOptimizationQualityGate::class));

    $parsed = [
        'name' => 'Raushan Dubey',
        'summary' => 'Frontend developer with Vue.js experience building dashboards.',
        'skills' => ['Vue.js', 'JavaScript'],
        'experience' => [
            ['org' => 'Startup', 'title' => 'Intern', 'bullets' => ['Built UI components with Vue.js.']],
        ],
        'education' => [
            ['degree' => 'R A U S H A N D U B E Y', 'school' => 'ABC College'],
            ['degree' => 'B.Tech CSE', 'school' => 'ABC College', 'year' => '2024'],
        ],
        'projects' => [],
        'raw_text' => 'Frontend developer...',
    ];

    $result = $engine->lightTouchOptimize(
        $parsed,
        ['job_title' => 'UI Designer Intern', 'required_skills' => ['Figma']],
        ['missing_skills' => ['Figma', 'Wireframing']],
        'test'
    );

    $decoded = json_decode($result['rewritten_text'], true);

    expect($decoded['summary'])->toContain('Vue.js')
        ->and($decoded['experience'][0]['bullets'][0])->toBe('Built UI components with Vue.js.')
        ->and($decoded['skills'])->toContain('Figma')
        ->and(collect($decoded['education'])->pluck('degree')->join(' '))->not->toContain('R A U S H A N');
});

test('rule-based fallback succeeds when parsed resume has sparse sections', function () {
    $gateway = new class extends AiProviderGateway {
        public function complete(string $purpose, string $systemPrompt, string $userPrompt, array $metadata = []): array
        {
            return ['success' => false, 'error' => 'no keys', 'attempts' => []];
        }
    };

    $engine = new AiRewriteEngine($gateway, app(ResumeOptimizationQualityGate::class));

    $result = $engine->rewrite(
        [
            'summary' => '',
            'skills' => [],
            'experience' => [],
            'projects' => [],
            'education' => [],
            'raw_text' => "JOHN DOE\n- Worked on REST APIs for internal dashboards\n- Helped deploy services to cloud hosting\nB.Tech Computer Science 2025",
        ],
        ['required_skills' => ['Laravel', 'AWS'], 'job_title' => 'SDE Intern'],
        ['missing_skills' => ['Laravel', 'AWS']],
        ['tier' => 'average', 'locked_sections' => []]
    );

    expect($result['success'])->toBeTrue()
        ->and($result['ai_used'])->toBeFalse()
        ->and(json_decode($result['rewritten_text'], true)['skills'])->not->toBeEmpty();
});

test('ai optimization preserves experience when model omits it from flat json', function () {
    $gateway = new class extends AiProviderGateway {
        public function complete(string $purpose, string $systemPrompt, string $userPrompt, array $metadata = []): array
        {
            return [
                'success' => true,
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'content' => json_encode([
                    'summary' => 'Backend engineer with Laravel and MySQL.',
                    'skills' => ['Laravel', 'MySQL', 'PHP'],
                    'experience' => [],
                    'projects' => [],
                ]),
                'tokens' => [],
                'attempts' => [],
            ];
        }
    };

    $engine = new AiRewriteEngine($gateway, app(ResumeOptimizationQualityGate::class));

    $originalExperience = [
        [
            'org' => 'Acme Corp',
            'title' => 'Backend Intern',
            'date' => '2024',
            'bullets' => ['Developed Laravel REST APIs backed by MySQL.'],
        ],
    ];

    $result = $engine->rewrite(
        [
            'summary' => 'Developer.',
            'skills' => ['PHP'],
            'experience' => $originalExperience,
            'education' => [['degree' => 'B.Tech', 'school' => 'Test University']],
            'raw_text' => 'Developer PHP Developed Laravel REST APIs.',
        ],
        ['required_skills' => ['Laravel'], 'job_title' => 'Backend Engineer'],
        ['missing_skills' => ['Laravel'], 'summary_weak' => true],
        ['tier' => 'average', 'locked_sections' => []]
    );

    $decoded = json_decode($result['rewritten_text'], true);

    expect($result['success'])->toBeTrue()
        ->and($decoded['experience'])->toBe($originalExperience)
        ->and($decoded['education'])->not->toBeEmpty();
});

test('rule-based fallback deduplicates skills case-insensitively', function () {
    $gateway = new class extends AiProviderGateway {
        public function complete(string $purpose, string $systemPrompt, string $userPrompt, array $metadata = []): array
        {
            return ['success' => false, 'error' => 'no keys', 'attempts' => []];
        }
    };

    $engine = new AiRewriteEngine($gateway, app(ResumeOptimizationQualityGate::class));

    $result = $engine->rewrite(
        [
            'summary' => 'Developer.',
            'skills' => ['vue.js', 'Vue.js', 'JavaScript'],
            'experience' => [
                ['org' => 'Acme', 'title' => 'Intern', 'date' => '2024', 'bullets' => ['Worked on APIs.']],
            ],
            'education' => [['degree' => 'B.Tech CSE', 'school' => 'Test University', 'year' => '2025']],
            'raw_text' => 'Developer vue.js',
        ],
        ['required_skills' => ['Node.js'], 'job_title' => 'Intern'],
        ['missing_skills' => ['Node.js']],
        ['tier' => 'average', 'locked_sections' => []]
    );

    $skills = json_decode($result['rewritten_text'], true)['skills'];
    $lower  = array_map('strtolower', $skills);

    expect($result['success'])->toBeTrue()
        ->and(count($lower))->toBe(count(array_unique($lower)))
        ->and(in_array('vue.js', $lower, true))->toBeTrue()
        ->and(in_array('node.js', $lower, true))->toBeTrue();
});

test('rule-based fallback guarantees non-empty body sections for sparse inputs', function () {
    $engine = new AiRewriteEngine(null, app(ResumeOptimizationQualityGate::class));

    $sparseParsed = [
        'name' => 'Raushan Dubey',
        'summary' => '',
        'skills' => [],
        'experience' => [],
        'projects' => [],
        'education' => [],
        'raw_text' => 'Raushan Dubey',
    ];

    $result = $engine->lightTouchOptimize(
        $sparseParsed,
        ['job_title' => 'Software Intern', 'required_skills' => ['Laravel']],
        ['missing_skills' => ['Laravel']],
        'testing_empty_body'
    );

    $decoded = json_decode($result['rewritten_text'], true);

    expect($result['success'])->toBeTrue()
        ->and($decoded['experience'])->not->toBeEmpty()
        ->and($decoded['experience'][0]['bullets'])->not->toBeEmpty();
});
