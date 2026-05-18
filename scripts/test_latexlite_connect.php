<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = (string) config('services.latexlite.api_key', '');
$apiUrl = (string) config('services.latexlite.url', 'https://latexlite.com/v1/renders-sync');

echo "API key: " . ($apiKey === '' ? 'EMPTY' : 'set (' . strlen($apiKey) . ' chars)') . PHP_EOL;
echo "API URL: {$apiUrl}" . PHP_EOL;

$templatePath = resource_path('latex/ats-resume-template.tex');
$template = is_file($templatePath) ? file_get_contents($templatePath) : '';

$payload = [
    'name' => 'TEST USER',
    'email' => 'test@example.com',
    'email_href' => 'test@example.com',
    'tagline' => 'Software Intern',
    'contact_row_two' => '  & \\\\',
    'contact_row_three' => '  & \\\\',
    'summary_section' => '',
    'experience_section' => "\\section{Professional Experience}\n\n\\cvEntry{Intern}{2024}{Acme}{}\n\\pointsStart\n\\cvPoint{Built REST APIs with Laravel.}\n\\pointsEnd\n\n",
    'projects_section' => '',
    'education_section' => '',
    'skills' => '\\cvPoint{PHP, Laravel}' . "\n",
    'certifications_section' => '',
];

try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
        'Accept' => 'application/pdf',
    ])->timeout(60)->post($apiUrl, [
        'template' => $template,
        'data' => $payload,
    ]);

    echo "HTTP status: " . $response->status() . PHP_EOL;
    echo "Content-Type: " . ($response->header('Content-Type') ?? 'n/a') . PHP_EOL;
    echo "Body size: " . strlen($response->body()) . PHP_EOL;
    echo "Starts with PDF: " . (str_starts_with($response->body(), '%PDF-') ? 'yes' : 'no') . PHP_EOL;
    if (!$response->successful()) {
        echo "Body preview: " . substr($response->body(), 0, 500) . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "EXCEPTION: " . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
}
