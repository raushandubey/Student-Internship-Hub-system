<?php

use App\Services\Resume\AiProviderGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.openai.api_key', null);
    Config::set('services.anthropic.api_key', null);
    Config::set('services.openrouter.api_key', null);
    Config::set('services.openai.model', 'gpt-5.1');
    Config::set('services.openai.endpoint', 'https://api.openai.com/v1/responses');
    Config::set('services.anthropic.model', 'claude-test');
    Config::set('services.anthropic.endpoint', 'https://api.anthropic.com/v1/messages');
    Config::set('services.openrouter.model', 'test/openrouter');
    Config::set('services.openrouter.endpoint', 'https://openrouter.ai/api/v1/chat/completions');
});

test('missing provider keys fail closed', function () {
    Http::fake();

    $result = app(AiProviderGateway::class)->complete('resume_rewrite', 'system', 'user');

    expect($result['success'])->toBeFalse()
        ->and($result['attempts'])->toHaveCount(3);

    Http::assertNothingSent();
});

test('openai success returns provider metadata', function () {
    Config::set('services.openai.api_key', 'test-openai-key');

    Http::fake([
        'api.openai.com/*' => Http::response([
            'output_text' => '{"summary":"Optimized","skills":["PHP"],"projects":[{"title":"A","bullets":["Built API"]}]}',
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20, 'total_tokens' => 30],
        ], 200),
    ]);

    $result = app(AiProviderGateway::class)->complete('resume_rewrite', 'system', 'user');

    expect($result['success'])->toBeTrue()
        ->and($result['provider'])->toBe('openai')
        ->and($result['model'])->toBe('gpt-5.1')
        ->and($result['tokens']['total'])->toBe(30);
});

test('openai failure falls through to anthropic', function () {
    Config::set('services.openai.api_key', 'test-openai-key');
    Config::set('services.anthropic.api_key', 'test-anthropic-key');

    Http::fake([
        'api.openai.com/*' => Http::response(['error' => ['message' => 'bad model']], 404),
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'text', 'text' => '{"summary":"Optimized","skills":["PHP"],"projects":[{"title":"A","bullets":["Built API"]}]}'],
            ],
            'usage' => ['input_tokens' => 5, 'output_tokens' => 7],
        ], 200),
    ]);

    $result = app(AiProviderGateway::class)->complete('resume_rewrite', 'system', 'user');

    expect($result['success'])->toBeTrue()
        ->and($result['provider'])->toBe('anthropic')
        ->and($result['attempts'][0]['success'])->toBeFalse()
        ->and($result['attempts'][1]['success'])->toBeTrue();
});
