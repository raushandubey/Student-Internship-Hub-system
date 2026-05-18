<?php

use App\Services\Resume\LatexTemplateEngine;
use Illuminate\Support\Facades\Http;

test('latex template builds valid structure with clean data', function () {
    $engine = app(LatexTemplateEngine::class);

    $data = [
        'name' => 'Raushan Dubey',
        'email' => 'raushandubey2005@gmail.com',
        'phone' => '+91 9934898643',
        'location' => 'Noida',
        'links' => [
            'https://github.com/raushandubey',
            'https://linkedin.com/in/raushandubey'
        ],
        'sections' => [
            'summary' => 'Software Engineer in training.',
            'skills' => ['PHP', 'Laravel', 'Vue.js'],
            'experience' => [
                [
                    'org' => 'Company A',
                    'title' => 'Developer',
                    'date' => '2024',
                    'location' => 'Remote',
                    'bullets' => ['Wrote clean code.']
                ]
            ]
        ]
    ];

    $reflection = new \ReflectionClass(LatexTemplateEngine::class);
    $method = $reflection->getMethod('buildTexContent');
    $method->setAccessible(true);
    $tex = $method->invoke($engine, $data);

    expect($engine->validateLatex($tex))->toBeTrue();
});

test('latex escaping handles special characters without ruining brace balance', function () {
    $engine = app(LatexTemplateEngine::class);

    // Data containing potential break-points for sequential str_replace: backslashes, braces, underscores
    $data = [
        'name' => 'John Doe',
        'email' => 'john_doe@example.com',
        'phone' => '123-456-7890',
        'location' => 'New York',
        'links' => [
            'https://github.com/john_doe'
        ],
        'sections' => [
            'summary' => 'Proficient with backslashes \\, underscores _, braces { and }, ampersands &, dollars $, percents %, hashes #, tildes ~, and carets ^.',
            'skills' => ['C#', 'C++', 'Java & Kotlin'],
            'experience' => [
                [
                    'org' => 'Developer_Corp',
                    'title' => 'Sr. Engineer',
                    'date' => '2020-2024',
                    'bullets' => [
                        'Fixed bugs in legacy backslash \\ implementations.',
                        'Dealt with matched { braces } and unmatched { brace.'
                    ]
                ]
            ]
        ]
    ];

    $reflection = new \ReflectionClass(LatexTemplateEngine::class);
    $method = $reflection->getMethod('buildTexContent');
    $method->setAccessible(true);
    $tex = $method->invoke($engine, $data);

    expect($engine->validateLatex($tex))->toBeTrue();
});

test('latex payload filters garbage education and groups skills', function () {
    $engine = app(LatexTemplateEngine::class);

    $reflection = new \ReflectionClass(LatexTemplateEngine::class);
    $method = $reflection->getMethod('mapToLatexliteData');
    $method->setAccessible(true);

    $payload = $method->invoke($engine, [
        'name' => 'Raushan Dubey',
        'email' => 'test@example.com',
        'phone' => '+91 9999999999',
        'location' => 'Delhi',
        'targetRole' => 'UI Designer Intern',
        'links' => ['https://github.com/test', 'https://linkedin.com/in/test'],
        'sections' => [
            'summary' => 'Full stack developer with JavaScript experience.',
            'skills' => ['vue.js', 'Vue.js', 'JavaScript', 'Node.js'],
            'experience' => [
                [
                    'title' => 'Developer Intern',
                    'org' => 'Acme',
                    'date' => '2024',
                    'bullets' => ['Developed REST APIs using Node.js.'],
                ],
            ],
            'education' => [
                [
                    'degree' => 'Academic & Professional Background',
                    'school' => 'RAUSHAN DUBEY PROFESSIONAL SUMMARY garbage text that should be removed',
                    'year' => '',
                ],
                [
                    'degree' => 'B.Tech Computer Science',
                    'school' => 'Test University',
                    'year' => '2025',
                ],
            ],
        ],
    ]);

    expect($payload['education_section'])->toContain('Test University')
        ->and($payload['education_section'])->not->toContain('PROFESSIONAL SUMMARY')
        ->and($payload['skills'])->toContain('Node.js')
        ->and($payload['skills'])->not->toContain('vue.js, Vue.js')
        ->and($payload['email_href'])->toBe('test@example.com');
});

test('validateMappedPayload accepts skills-only resumes', function () {
    $engine = app(LatexTemplateEngine::class);

    $reflection = new \ReflectionClass(LatexTemplateEngine::class);
    $mapMethod = $reflection->getMethod('mapToLatexliteData');
    $mapMethod->setAccessible(true);
    $validateMethod = $reflection->getMethod('validateMappedPayload');
    $validateMethod->setAccessible(true);

    $payload = $mapMethod->invoke($engine, [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'sections' => [
            'summary' => 'Backend developer.',
            'skills' => ['PHP', 'Laravel'],
            'experience' => [],
            'projects' => [],
            'education' => [],
        ],
    ]);

    expect($validateMethod->invoke($engine, $payload))->toBeTrue();
});

test('generatePdfResult returns binary pdf from http 200', function () {
    config(['services.latexlite.api_key' => 'test-key', 'services.latexlite.url' => 'https://latexlite.com/v1/renders-sync']);

    $pdfBody = "%PDF-1.4\n%%EOF\n";

    Http::fake([
        'latexlite.com/*' => Http::response($pdfBody, 200, ['Content-Type' => 'application/pdf']),
    ]);

    $result = app(LatexTemplateEngine::class)->generatePdfResult(sampleLatexResumeData());

    expect($result['success'])->toBeTrue()
        ->and($result['pdf'])->toBe($pdfBody)
        ->and($result['http_status'])->toBe(200);

    Http::assertSent(fn ($request) => $request->hasHeader('Accept', 'application/pdf'));
});

test('generatePdfResult accepts http 201 watermarked pdf', function () {
    config([
        'services.latexlite.api_key' => 'test-key',
        'services.latexlite.url' => 'https://latexlite.com/v1/renders-sync',
    ]);

    $pdfBody = "%PDF-1.4\n%%EOF\n";

    Http::fake([
        'latexlite.com/*' => Http::response($pdfBody, 201, ['Content-Type' => 'application/pdf']),
    ]);

    $result = app(LatexTemplateEngine::class)->generatePdfResult(sampleLatexResumeData());

    expect($result['success'])->toBeTrue()
        ->and($result['http_status'])->toBe(201);
});

test('generatePdfResult decodes json pdf_base64 responses', function () {
    config([
        'services.latexlite.api_key' => 'test-key',
        'services.latexlite.url' => 'https://latexlite.com/v1/renders-sync',
    ]);

    $pdfBody = "%PDF-1.4\n%%EOF\n";

    Http::fake([
        '*' => Http::response([
            'success' => true,
            'render_status' => 'OK',
            'data' => [
                'content_type' => 'application/pdf',
                'pdf_base64' => base64_encode($pdfBody),
            ],
        ], 200, ['Content-Type' => 'application/json']),
    ]);

    $result = app(LatexTemplateEngine::class)->generatePdfResult(sampleLatexResumeData());

    expect($result['success'])->toBeTrue()
        ->and($result['pdf'])->toBe($pdfBody);
});

test('generatePdfResult returns api_key_missing when key is empty', function () {
    config(['services.latexlite.api_key' => '', 'services.latexlite.url' => 'https://latexlite.com/v1/renders-sync']);

    Http::fake();

    $result = app(LatexTemplateEngine::class)->generatePdfResult(sampleLatexResumeData());

    expect($result['success'])->toBeFalse()
        ->and($result['stage'])->toBe('api_key_missing');

    Http::assertNothingSent();
});

test('generatePdfResult maps http 401 to api_unauthorized', function () {
    config([
        'services.latexlite.api_key' => 'test-key',
        'services.latexlite.url' => 'https://latexlite.com/v1/renders-sync',
    ]);

    Http::fake([
        'latexlite.com/*' => Http::response([
            'success' => false,
            'error' => ['message' => 'Invalid API key'],
        ], 401),
    ]);

    $result = app(LatexTemplateEngine::class)->generatePdfResult(sampleLatexResumeData());

    expect($result['stage'])->toBe('api_unauthorized')
        ->and($result['http_status'])->toBe(401)
        ->and($result['message'])->toContain('Invalid API key');
});

test('generatePdfResult maps http 408 to render_timeout', function () {
    config([
        'services.latexlite.api_key' => 'test-key',
        'services.latexlite.url' => 'https://latexlite.com/v1/renders-sync',
    ]);

    Http::fake([
        'latexlite.com/*' => Http::response([
            'success' => false,
            'error' => ['message' => 'render timed out'],
        ], 408),
    ]);

    $result = app(LatexTemplateEngine::class)->generatePdfResult(sampleLatexResumeData());

    expect($result['stage'])->toBe('render_timeout')
        ->and($result['http_status'])->toBe(408);
});

test('generatePdfResult classifies ssl connection failures', function () {
    config([
        'services.latexlite.api_key' => 'test-key',
        'services.latexlite.url' => 'https://latexlite.com/v1/renders-sync',
        'services.latexlite.retry_times' => 0,
    ]);

    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException(
            new \GuzzleHttp\Exception\ConnectException(
                'cURL error 60: SSL certificate problem: unable to get local issuer certificate',
                new \GuzzleHttp\Psr7\Request('POST', 'https://latexlite.com/v1/renders-sync')
            )
        );
    });

    $result = app(LatexTemplateEngine::class)->generatePdfResult(sampleLatexResumeData());

    expect($result['stage'])->toBe('ssl_error')
        ->and($result['message'])->toContain('SSL certificate');
});

test('normalizeApiUrl appends renders-sync when base url only', function () {
    $engine = app(LatexTemplateEngine::class);
    $method = (new \ReflectionClass($engine))->getMethod('normalizeApiUrl');
    $method->setAccessible(true);

    expect($method->invoke($engine, 'https://latexlite.com'))
        ->toBe('https://latexlite.com/v1/renders-sync');
});

test('generatePdfResult surfaces compilation errors on http 422', function () {
    config([
        'services.latexlite.api_key' => 'test-key',
        'services.latexlite.url' => 'https://latexlite.com/v1/renders-sync',
    ]);

    Http::fake([
        'latexlite.com/*' => Http::response([
            'success' => false,
            'error' => ['message' => 'LaTeX compilation failed: ! Undefined control sequence.'],
        ], 422),
    ]);

    $result = app(LatexTemplateEngine::class)->generatePdfResult(sampleLatexResumeData());

    expect($result['success'])->toBeFalse()
        ->and($result['stage'])->toBe('compilation_failed')
        ->and($result['message'])->toContain('LaTeX compilation failed');
});

test('sanitizeForLatex collapses newlines in escaped summary', function () {
    $engine = app(LatexTemplateEngine::class);

    $reflection = new \ReflectionClass(LatexTemplateEngine::class);
    $escape = $reflection->getMethod('escape');
    $escape->setAccessible(true);

    $escaped = $escape->invoke($engine, "Line one\nLine two\tTab");

    expect($escaped)->not->toContain("\n")
        ->and($escaped)->toContain('Line one Line two Tab');
});

test('generatePdfResult reaches live LaTeXLite API when enabled', function () {
    config([
        'services.latexlite.api_key' => env('LATEXLITE_API_KEY'),
        'services.latexlite.url' => env('LATEXLITE_API_URL', 'https://latexlite.com/v1/renders-sync'),
        'services.latexlite.retry_times' => 1,
    ]);

    $result = app(LatexTemplateEngine::class)->generatePdfResult(sampleLatexResumeData());

    expect($result['success'])->toBeTrue()
        ->and($result['pdf'])->toStartWith('%PDF-');
})->group('integration')->skip(
    fn () => !filter_var(env('LATEXLITE_LIVE_TEST', false), FILTER_VALIDATE_BOOL),
    'Set LATEXLITE_LIVE_TEST=1 and LATEXLITE_API_KEY to run live LaTeXLite connectivity test.'
);

function sampleLatexResumeData(): array
{
    return [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+1 555 0100',
        'location' => 'Remote',
        'targetRole' => 'Software Intern',
        'links' => [],
        'sections' => [
            'summary' => 'Developer with API experience.',
            'skills' => ['PHP', 'Laravel'],
            'experience' => [
                [
                    'org' => 'Acme',
                    'title' => 'Intern',
                    'date' => '2024',
                    'bullets' => ['Built REST APIs with Laravel and MySQL.'],
                ],
            ],
            'projects' => [],
            'education' => [],
        ],
    ];
}
