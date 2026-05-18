<?php

use App\Services\Resume\ResumeTextNormalizer;

test('normalizePersonName collapses per-character PDF spacing', function () {
    expect(ResumeTextNormalizer::normalizePersonName('R A U S H A N D U B E Y'))
        ->toBe('Raushan Dubey');
});

test('normalizePersonName leaves normal names unchanged', function () {
    expect(ResumeTextNormalizer::normalizePersonName('Raushan Dubey'))
        ->toBe('Raushan Dubey');
});

test('profession label detects UI designer intern role', function () {
    $label = ResumeTextNormalizer::professionLabelFromJd([
        'job_title' => 'UI Designer Intern',
        'role_category' => 'design',
    ]);

    expect($label)->toBe('UI/UX designer');
});

test('buildRoleTargetedSummary preserves candidate identity when provided', function () {
    $summary = ResumeTextNormalizer::buildRoleTargetedSummary(
        [
            'job_title' => 'UI Designer Intern',
            'organization' => 'IBM India',
            'role_category' => 'design',
            'required_skills' => ['Figma', 'Wireframing', 'Prototyping'],
        ],
        [],
        [
            'candidate_type' => 'Backend Engineer',
            'role_category' => 'backend',
            'core_stack' => ['Laravel', 'MySQL'],
        ]
    );

    expect($summary)
        ->toContain('Backend Engineer')
        ->toContain('UI Designer Intern')
        ->toContain('applying for');
});

test('isValidDegreeLine accepts common degree formats', function () {
    expect(ResumeTextNormalizer::isValidDegreeLine('B.Tech Computer Science'))->toBeTrue();
    expect(ResumeTextNormalizer::isValidDegreeLine('Raushan Dubey'))->toBeFalse();
});

test('namesMatch detects spaced and normal variants', function () {
    expect(ResumeTextNormalizer::namesMatch('R A U S H A N D U B E Y', 'Raushan Dubey'))->toBeTrue();
});
