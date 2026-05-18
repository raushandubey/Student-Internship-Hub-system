<?php

use App\Services\Resume\SemanticSkillMatcher;

beforeEach(function () {
    SemanticSkillMatcher::resetCache();
});

test('problem solving is satisfied by debugging and architecture evidence', function () {
    $matcher = new SemanticSkillMatcher();

    $resumeText = implode(' ', [
        'scalable systems',
        'architecture',
        'optimization',
        'REST APIs',
        'debugging',
        'production engineering',
    ]);

    $result = $matcher->evaluate('problem solving', $resumeText);

    expect($result['matched'])->toBeTrue()
        ->and($result['via'])->toBeIn(['cluster', 'inferred', 'alias', 'literal']);
});

test('system design is satisfied by architecture signals', function () {
    $matcher = new SemanticSkillMatcher();

    $resumeText = 'Designed scalable microservices architecture for high availability';

    expect($matcher->isSatisfied('system design', $resumeText))->toBeTrue();
});

test('backend engineering is satisfied by REST APIs', function () {
    $matcher = new SemanticSkillMatcher();

    $resumeText = 'Built REST APIs with Laravel and MySQL';

    expect($matcher->isSatisfied('backend engineering', $resumeText))->toBeTrue();
});

test('literal match still works', function () {
    $matcher = new SemanticSkillMatcher();

    expect($matcher->isSatisfied('react', 'Proficient in React and TypeScript'))->toBeTrue();
});

test('unrelated skill remains missing', function () {
    $matcher = new SemanticSkillMatcher();

    $resumeText = 'Built REST APIs with Laravel';

    expect($matcher->isSatisfied('figma', $resumeText))->toBeFalse();
});

test('scoreSkills returns explanations for semantic matches', function () {
    $matcher = new SemanticSkillMatcher();

    $resumeText = 'Expert in debugging production incidents and scalable architecture';

    $result = $matcher->scoreSkills(
        ['problem solving', 'figma'],
        $resumeText
    );

    expect($result['matching'])->toContain('problem solving')
        ->and($result['missing'])->toContain('figma')
        ->and($result['explanations'])->toHaveKey('problem solving');
});
