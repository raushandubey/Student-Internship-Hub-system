<?php

use App\Services\Resume\ResumeOptimizationQualityGate;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('services.resume_optimizer.min_score_delta', 1);
    Config::set('services.resume_optimizer.min_text_delta', 0.05);
});

test('unchanged optimized resume is rejected', function () {
    $resume = [
        'summary' => 'Software developer.',
        'skills' => ['PHP'],
        'projects' => [
            ['title' => 'API', 'bullets' => ['Worked on API features.']],
        ],
        'raw_text' => 'Software developer. PHP Worked on API features.',
    ];

    $result = app(ResumeOptimizationQualityGate::class)
        ->evaluate($resume, $resume, ['required_skills' => ['PHP', 'Laravel']], 69, 69);

    expect($result['passed'])->toBeFalse()
        ->and($result['reason'])->toBe('optimized_resume_not_materially_different')
        ->and($result['score_delta'])->toBe(0);
});

test('changed sections added keywords and score delta pass quality gate', function () {
    $original = [
        'summary' => 'Software developer.',
        'skills' => ['PHP'],
        'projects' => [
            ['title' => 'API', 'bullets' => ['Worked on API features.']],
        ],
    ];

    $optimized = [
        'summary' => 'Backend software developer building Laravel APIs and MySQL services.',
        'skills' => ['PHP', 'Laravel', 'MySQL'],
        'projects' => [
            ['title' => 'API', 'bullets' => ['Developed Laravel API endpoints backed by MySQL services.']],
        ],
    ];

    $result = app(ResumeOptimizationQualityGate::class)->evaluate(
        $original,
        $optimized,
        ['required_skills' => ['Laravel', 'MySQL'], 'keywords' => ['API']],
        69,
        75
    );

    expect($result['passed'])->toBeTrue()
        ->and($result['score_delta'])->toBe(6)
        ->and($result['changed_sections'])->toContain('summary')
        ->and($result['changed_sections'])->toContain('skills')
        ->and($result['changed_sections'])->toContain('projects')
        ->and($result['rewritten_bullet_count'])->toBeGreaterThan(0)
        ->and($result['added_keywords'])->toContain('Laravel')
        ->and($result['added_keywords'])->toContain('MySQL');
});

test('material text change without score improvement is rejected', function () {
    $original = [
        'summary' => 'Developer.',
        'skills' => ['PHP'],
        'projects' => [
            ['title' => 'API', 'bullets' => ['Worked on API features.']],
        ],
    ];

    $optimized = [
        'summary' => 'Backend developer with Laravel API experience.',
        'skills' => ['PHP', 'Laravel'],
        'projects' => [
            ['title' => 'API', 'bullets' => ['Developed Laravel API features.']],
        ],
    ];

    $result = app(ResumeOptimizationQualityGate::class)->evaluate(
        $original,
        $optimized,
        ['required_skills' => ['Laravel']],
        69,
        69
    );

    expect($result['passed'])->toBeFalse()
        ->and($result['reason'])->toBe('ats_score_did_not_improve');
});
