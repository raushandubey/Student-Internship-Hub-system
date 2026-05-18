<?php

use App\Services\Resume\IdentityPreservationEngine;
use App\Services\Resume\ResumeTextNormalizer;

test('backend resume identity is preserved for design intern JD summary', function () {
    $parsed = [
        'summary' => 'Backend engineer with Laravel, REST APIs, and scalable system architecture.',
        'skills' => ['Laravel', 'PHP', 'MySQL', 'REST APIs'],
        'experience' => [
            ['title' => 'Backend Developer', 'org' => 'Acme', 'bullets' => ['Built REST APIs and optimized production systems']],
        ],
        'projects' => [],
        'raw_text' => 'backend laravel rest api architecture debugging',
    ];

    $jd = [
        'job_title' => 'UI Designer Intern',
        'organization' => 'IBM',
        'role_category' => 'design',
        'required_skills' => ['Figma'],
    ];

    $identity = (new IdentityPreservationEngine())->extract($parsed);
    $summary = ResumeTextNormalizer::buildRoleTargetedSummary($jd, [], $identity);

    expect($summary)
        ->toContain('Backend Engineer')
        ->toContain('UI Designer Intern')
        ->not->toContain('Motivated UI/UX designer');
});

test('identity validation detects summary domain corruption', function () {
    $identity = [
        'candidate_type' => 'Backend Engineer',
        'role_category' => 'backend',
        'forbidden_domains' => ['UI Designer', 'Graphic Designer'],
    ];

    $original = [
        'summary' => 'Backend engineer specializing in Laravel APIs and distributed systems architecture for production.',
        'experience' => [['title' => 'Backend Developer']],
    ];

    $optimized = [
        'summary' => 'Motivated UI/UX designer passionate about visual design systems.',
        'experience' => [['title' => 'Backend Developer']],
    ];

    $result = (new IdentityPreservationEngine())->validate($identity, $optimized, $original);

    expect($result['valid'])->toBeFalse()
        ->and($result['violations'])->not->toBeEmpty();
});
