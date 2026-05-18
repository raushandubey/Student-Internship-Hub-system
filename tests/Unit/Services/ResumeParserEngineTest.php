<?php

use App\Services\Resume\ResumeParserEngine;

test('parseRawText rejects person name as education degree', function () {
    $raw = <<<'TEXT'
Raushan Dubey
raushan@example.com | 9876543210

EDUCATION
Raushan Dubey
ABC Institute of Technology
B.Tech Computer Science 2024

SKILLS
JavaScript, Vue.js, Node.js, MongoDB

EXPERIENCE
Startup | Frontend Intern | 2024
- Built responsive UI components with Vue.js serving 500+ users.
TEXT;

    $parsed = (new ResumeParserEngine())->parseRawText($raw);

    expect($parsed['name'])->toBe('Raushan Dubey')
        ->and($parsed['education'])->not->toBeEmpty();

    foreach ($parsed['education'] as $entry) {
        expect(strtolower($entry['degree'] ?? ''))->not->toBe('raushan dubey');
    }
});

test('parseRawText extracts experience bullets without bullet prefix', function () {
    $raw = <<<'TEXT'
John Doe
john@example.com

EXPERIENCE
Acme Corp | Developer | 2023
1. Implemented REST APIs with Laravel and MySQL for internal tools.
2. Reduced page load time by 30% through query optimization.

SKILLS
PHP, Laravel, MySQL
TEXT;

    $parsed = (new ResumeParserEngine())->parseRawText($raw);

    expect($parsed['experience'])->not->toBeEmpty()
        ->and($parsed['experience'][0]['bullets'] ?? [])->not->toBeEmpty();
});

test('parseRawText infers sections when headers are informal', function () {
    $raw = <<<'TEXT'
Jane Smith
jane@example.com

Skills: React, TypeScript, CSS

Work Experience
Design Co | UI Intern | 2025
- Created wireframes and prototypes in Figma for mobile onboarding.

Education
B.Des Interaction Design | Design University | 2026
TEXT;

    $parsed = (new ResumeParserEngine())->parseRawText($raw);

    expect($parsed['skills'])->not->toBeEmpty()
        ->and($parsed['experience'])->not->toBeEmpty()
        ->and($parsed['education'])->not->toBeEmpty();
});
