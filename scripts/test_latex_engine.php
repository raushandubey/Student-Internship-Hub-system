<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Resume\LatexTemplateEngine;

$result = app(LatexTemplateEngine::class)->generatePdfResult([
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
]);

echo json_encode([
    'success' => $result['success'],
    'stage' => $result['stage'] ?? null,
    'message' => $result['message'] ?? null,
    'http_status' => $result['http_status'] ?? null,
    'pdf_size' => isset($result['pdf']) ? strlen((string) $result['pdf']) : 0,
], JSON_PRETTY_PRINT) . PHP_EOL;
