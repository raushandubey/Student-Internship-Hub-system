<?php

use App\Services\Resume\AiProductionDiagnosticsService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('production diagnostics redact secrets and can skip outbound probes', function () {
    Config::set('services.openrouter.api_key', 'secret-openrouter-key');
    Config::set('services.openrouter.model', 'deepseek/deepseek-v4-flash:free');
    Config::set('services.openrouter.endpoint', 'https://openrouter.ai/api/v1/chat/completions');

    Http::fake();

    $result = app(AiProductionDiagnosticsService::class)->run(false);

    expect($result['env_presence']['OPENROUTER_API_KEY']['redacted'])->toBeTrue()
        ->and($result['providers']['openrouter']['model'])->toBe('deepseek/deepseek-v4-flash:free')
        ->and($result['providers']['openrouter']['connectivity']['status'])->toBe('skipped')
        ->and($result['pdf_renderer']['binary_validator']['valid'])->toBeTrue();
    expect(array_key_exists('value', $result['env_presence']['OPENROUTER_API_KEY']))->toBeFalse();

    Http::assertNothingSent();
});
