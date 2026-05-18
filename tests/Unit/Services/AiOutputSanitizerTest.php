<?php

use App\Services\Resume\AiOutputSanitizer;

test('sanitizer strips markdown and validates schema', function () {
    $raw = <<<'JSON'
```json
{
  "summary": "Backend engineer with Laravel expertise.",
  "skills": ["Laravel", "PHP"],
  "experience": [{"title": "Dev", "org": "Co", "bullets": ["Built APIs"]}],
  "education": [{"degree": "B.Tech", "school": "Uni"}]
}
```
JSON;

    $result = (new AiOutputSanitizer())->sanitizeAndValidate($raw);

    expect($result['valid'])->toBeTrue()
        ->and($result['data']['summary'])->toContain('Backend engineer');
});

test('sanitizer rejects forbidden latex keys', function () {
    $raw = json_encode([
        'summary' => 'Test',
        'skills' => ['PHP'],
        'experience' => [['title' => 'Dev', 'bullets' => ['Did stuff']]],
        'latex' => '\\documentclass{article}',
    ]);

    $result = (new AiOutputSanitizer())->sanitizeAndValidate($raw);

    expect($result['valid'])->toBeFalse();
});
