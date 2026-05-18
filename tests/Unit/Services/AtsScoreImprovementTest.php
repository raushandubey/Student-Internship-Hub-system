<?php

use App\Services\Resume\AtsScoreEngine;
use App\Services\Resume\SemanticSkillMatcher;

beforeEach(function () {
    SemanticSkillMatcher::resetCache();
});

test('optimized resume with added skills scores higher than original', function () {
    $engine = new AtsScoreEngine(aiGateway: null, skillMatcher: new SemanticSkillMatcher());

    $jd = [
        'required_skills' => ['problem solving', 'laravel', 'rest api'],
        'keywords' => ['backend', 'api', 'database'],
        'role_category' => 'backend',
        'semantic_clusters' => [],
    ];

    $original = [
        'summary' => 'Developer with some PHP experience.',
        'skills' => ['PHP'],
        'experience' => [
            ['title' => 'Developer', 'org' => 'Co', 'bullets' => ['Built features']],
        ],
        'projects' => [],
        'education' => [['degree' => 'B.Tech', 'school' => 'University']],
        'raw_text' => 'developer php built features',
    ];

    $optimized = $original;
    $optimized['skills'] = ['PHP', 'Laravel', 'REST API', 'Problem Solving'];
    $optimized['raw_text'] = 'developer php laravel rest api debugging architecture problem solving built features';

    $before = $engine->ruleBasedScore($original, $jd);
    $after = $engine->ruleBasedScore($optimized, $jd);

    expect($after['overall_score'])->toBeGreaterThan($before['overall_score']);
});

test('scores stay below unrealistic ceiling', function () {
    $engine = new AtsScoreEngine(aiGateway: null, skillMatcher: new SemanticSkillMatcher());

    $jd = [
        'required_skills' => ['php'],
        'keywords' => ['php'],
        'role_category' => 'backend',
    ];

    $elite = [
        'summary' => 'Senior backend engineer architected scalable microservices with 99% uptime.',
        'skills' => ['PHP', 'Laravel', 'Redis', 'Docker'],
        'experience' => [
            ['title' => 'Senior Backend Engineer', 'org' => 'BigTech', 'bullets' => [
                'Architected scalable distributed system serving 2M users with 40% latency reduction',
                'Engineered REST APIs and optimized MySQL queries reducing load by 35%',
            ]],
        ],
        'projects' => [['title' => 'Platform', 'tech' => 'Laravel', 'bullets' => ['Built API']]],
        'education' => [['degree' => 'B.Tech CS', 'school' => 'IIT']],
        'raw_text' => 'architected scalable microservices rest api laravel optimized 40% latency 2M users',
    ];

    $score = $engine->ruleBasedScore($elite, $jd);

    expect($score['overall_score'])->toBeLessThanOrEqual(95);
});
