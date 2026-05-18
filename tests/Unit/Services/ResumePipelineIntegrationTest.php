<?php

use App\Services\Resume\AiOutputSanitizer;
use App\Services\Resume\AtsScoreEngine;
use App\Services\Resume\IdentityPreservationEngine;
use App\Services\Resume\SemanticSkillMatcher;

test('pipeline integration: parse identity, semantic score, sanitize output', function () {
    SemanticSkillMatcher::resetCache();

    $parsed = [
        'summary' => 'Full stack engineer building REST APIs and scalable architecture with Laravel.',
        'skills' => ['Laravel', 'PHP', 'MySQL', 'REST APIs'],
        'experience' => [
            ['title' => 'Full Stack Developer', 'org' => 'Tech Co', 'bullets' => ['Debugged production issues and optimized API latency by 25%']],
        ],
        'projects' => [],
        'education' => [['degree' => 'B.Tech', 'school' => 'University']],
        'raw_text' => 'full stack laravel rest api architecture debugging scalable',
    ];

    $jd = [
        'job_title' => 'Backend Intern',
        'role_category' => 'backend',
        'required_skills' => ['problem solving', 'laravel'],
        'keywords' => ['api', 'backend'],
        'semantic_clusters' => [],
    ];

    $identity = (new IdentityPreservationEngine())->extract($parsed);
    expect($identity['candidate_type'])->toContain('Engineer');

    $weaknessSkills = [];
    $matcher = new SemanticSkillMatcher();
    foreach ($jd['required_skills'] as $skill) {
        if (!$matcher->isSatisfied($skill, $parsed['raw_text'], $jd)) {
            $weaknessSkills[] = $skill;
        }
    }
    expect($weaknessSkills)->not->toContain('problem solving');

    $optimized = $parsed;
    $optimized['skills'][] = 'Team Collaboration';
    $optimized['raw_text'] .= ' team collaboration';

    $scores = (new AtsScoreEngine(skillMatcher: $matcher))->evaluateImprovement($parsed, $optimized, $jd, 'strong');
    expect($scores['after_score'])->toBeGreaterThanOrEqual($scores['before_score']);

    $json = json_encode([
        'summary' => $optimized['summary'],
        'skills' => $optimized['skills'],
        'experience' => $optimized['experience'],
        'education' => $optimized['education'],
    ]);

    $sanitized = (new AiOutputSanitizer())->sanitizeAndValidate($json);
    expect($sanitized['valid'])->toBeTrue();
});
