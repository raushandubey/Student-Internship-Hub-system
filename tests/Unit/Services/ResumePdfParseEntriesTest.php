<?php

use App\Models\Internship;
use App\Services\ResumePdfService;

test('parse entries handles date suffix lines without regex errors', function () {
    $internship = new Internship([
        'title' => 'Intern',
        'organization' => 'Test Co',
        'required_skills' => [],
    ]);

    $service = app(ResumePdfService::class);
    $reflection = new ReflectionClass(ResumePdfService::class);
    $parse = $reflection->getMethod('parseResumeTextToSections');
    $parse->setAccessible(true);

    $text = <<<'TXT'
PROFESSIONAL EXPERIENCE
Vizva Consultancy | Software Intern | Jun 2024 – Aug 2024 | Remote
- Built REST APIs using Laravel.
TXT;

    $sections = $parse->invoke($service, $text, $internship);

    expect($sections['experience'])->not->toBeEmpty()
        ->and($sections['experience'][0]['org'] ?? '')->toContain('Vizva');
});
