<?php

use App\Models\Internship;
use App\Services\ResumePdfService;

test('pdf merge restores experience and education from original snapshot text', function () {
    $internship = new Internship([
        'title' => 'UI Designer Intern',
        'organization' => 'IBM India',
        'required_skills' => ['JavaScript', 'Vue.js'],
    ]);

    $optimizedJson = json_encode([
        'summary' => 'Optimized summary for UI Designer Intern at IBM India.',
        'skills' => ['vue.js', 'JavaScript', 'Node.js'],
        'experience' => [],
        'projects' => [
            [
                'title' => 'Real-Time Dashboard',
                'tech' => 'Vue.js, Node.js',
                'bullets' => ['Developed responsive UI using Vue.js.'],
            ],
        ],
        'education' => [],
        'certifications' => [],
        'raw_text' => 'Optimized summary only',
    ]);

    $originalText = <<<'TXT'
RAUSHAN DUBEY
raushandubey2005@gmail.com | +91 9934898643

PROFESSIONAL SUMMARY
Full Stack Developer with Laravel and React experience.

TECHNICAL SKILLS
Languages: Java, JavaScript, PHP
Backend: Laravel, Node.js

PROFESSIONAL EXPERIENCE
Trapigo | Laravel Developer Intern | Jun 2025 – Sep 2025 | On-site
- Built REST APIs using Laravel and MySQL.
- Integrated JWT authentication for mobile clients.

EDUCATION
Gopal Narayan Singh University | B.Tech Computer Science | 2022 – 2026
CGPA: 7.5 / 10

CERTIFICATIONS
Top 25 Teams - Code For Bharat Hackathon
TXT;

    $service = app(ResumePdfService::class);
    $reflection = new \ReflectionClass(ResumePdfService::class);
    $method = $reflection->getMethod('buildPdfData');
    $method->setAccessible(true);

    $user = new \App\Models\User(['name' => 'Raushan Dubey', 'email' => 'raushandubey2005@gmail.com']);
    $user->setRelation('profile', new \App\Models\Profile(['name' => 'Raushan Dubey', 'phone' => '+91 9934898643', 'location' => 'Delhi']));

    $data = $method->invoke($service, $user, $internship, $optimizedJson, $originalText);
    $sections = $data['sections'];

    expect($sections['summary'])->toContain('Optimized summary')
        ->and($sections['experience'])->not->toBeEmpty()
        ->and($sections['education'])->not->toBeEmpty()
        ->and(collect($sections['experience'])->pluck('org')->filter()->first())->toContain('Trapigo');
});
