<?php

use App\Services\Resume\AiProviderGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.openai.api_key', null);
    Config::set('services.anthropic.api_key', null);
    Config::set('services.openrouter.api_key', null);
    Config::set('services.openrouter.model', 'deepseek/deepseek-v4-flash:free');
    Config::set('services.openrouter.endpoint', 'https://openrouter.ai/api/v1/chat/completions');
});

test('missing provider keys fail closed', function () {
    Http::fake();

    $result = app(AiProviderGateway::class)->complete('resume_rewrite', 'system', 'user');

    expect($result['success'])->toBeFalse()
        ->and($result['attempts'])->toHaveCount(1);

    Http::assertNothingSent();
});

test('openrouter success returns provider metadata', function () {
    Config::set('services.openrouter.api_key', 'test-openrouter-key');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => '{"summary":"Optimized","skills":["PHP"],"projects":[{"title":"A","bullets":["Built API"]}]}'
                    ]
                ]
            ],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
        ], 200),
    ]);

    $result = app(AiProviderGateway::class)->complete('resume_rewrite', 'system', 'user');

    expect($result['success'])->toBeTrue()
        ->and($result['provider'])->toBe('openrouter')
        ->and($result['model'])->toBe('deepseek/deepseek-v4-flash:free')
        ->and($result['tokens']['total'])->toBe(30);
});

test('openai is tried before openrouter when both keys are configured', function () {
    Config::set('services.openai.api_key', 'test-openai-key');
    Config::set('services.openai.model', 'gpt-4o-mini');
    Config::set('services.openai.endpoint', 'https://api.openai.com/v1/chat/completions');
    Config::set('services.openrouter.api_key', 'test-openrouter-key');

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '{"summary":"OK"}']],
            ],
        ], 200),
        'openrouter.ai/*' => Http::response(['error' => 'should not be called'], 500),
    ]);

    $result = app(AiProviderGateway::class)->complete('resume_rewrite', 'system', 'user');

    expect($result['success'])->toBeTrue()
        ->and($result['provider'])->toBe('openai');

    Http::assertSentCount(1);
});

test('openrouter failure returns failure', function () {
    Config::set('services.openrouter.api_key', 'test-openrouter-key');

    Http::fake([
        'openrouter.ai/*' => Http::response(['error' => ['message' => 'bad model']], 404),
    ]);

    $result = app(AiProviderGateway::class)->complete('resume_rewrite', 'system', 'user');

    expect($result['success'])->toBeFalse()
        ->and($result['attempts'][0]['success'])->toBeFalse();
});
