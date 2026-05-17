<?php

use App\Services\Resume\AiProductionDiagnosticsService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('production diagnostics redact secrets and can skip outbound probes', function () {
    Config::set('services.openai.api_key', 'secret-openai-key');
    Config::set('services.openai.model', 'gpt-5.1');
    Config::set('services.openai.endpoint', 'https://api.openai.com/v1/responses');

    Http::fake();

    $result = app(AiProductionDiagnosticsService::class)->run(false);

    expect($result['env_presence']['OPENAI_API_KEY']['redacted'])->toBeTrue()
        ->and($result['providers']['openai']['model'])->toBe('gpt-5.1')
        ->and($result['providers']['openai']['connectivity']['status'])->toBe('skipped')
        ->and($result['pdf_renderer']['binary_validator']['valid'])->toBeTrue();
    expect(array_key_exists('value', $result['env_presence']['OPENAI_API_KEY']))->toBeFalse();

    Http::assertNothingSent();
});
