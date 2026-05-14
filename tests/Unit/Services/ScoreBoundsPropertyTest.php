<?php

/**
 * Property-Based Test for ResumeOptimizationService Score Bounds
 * 
 * **Validates: Requirements 1.8**
 * 
 * This test validates:
 * - Property 1: Score Bounds — ∀ resumeText, internship: 0 ≤ scoreResume(...).overall_score ≤ 100
 * - Property 2: Score Component Weights Sum to 100% — overall = (skill×0.40) + (kw×0.25) + (exp×0.20) + (proj×0.10) + (comp×0.05) always in [0,100]
 */

use App\Models\Internship;
use App\Services\ResumeOptimizationService;

/**
 * Property Test 1: Overall Score Bounds
 * 
 * Tests that for any combination of resume text and internship:
 * The overall_score is always within the valid range [0, 100]
 */
test('property: overall score is always between 0 and 100', function (string $resumeText, array $jobSkills, string $description) {
    // Arrange
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Test Position',
        'organization' => 'Test Company',
        'description' => 'Test job description with keywords: software development programming',
    ]);
    
    // Create service instance and use reflection to test the private scoreResume method
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Act
    $result = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    // Assert: Overall score is within bounds
    expect($result['overall_score'])
        ->toBeGreaterThanOrEqual(0, "Overall score should be >= 0 for: {$description}")
        ->toBeLessThanOrEqual(100, "Overall score should be <= 100 for: {$description}");
        
})->with([
    // Edge cases
    ['', [], 'Empty resume and no skills'],
    ['', ['PHP', 'Laravel'], 'Empty resume with skills'],
    ['Software Engineer', [], 'Resume with no required skills'],
    
    // Minimal content
    ['PHP', ['PHP'], 'Single word resume matching single skill'],
    ['Java', ['PHP'], 'Single word resume not matching skill'],
    
    // Short resumes
    ['PHP developer', ['PHP', 'Laravel', 'MySQL'], 'Very short resume'],
    ['Experienced software engineer', ['Python', 'Django', 'PostgreSQL'], 'Short generic resume'],
    
    // Normal resumes
    [
        'Software Engineer with 3 years of experience in PHP and Laravel. Built RESTful APIs and worked with MySQL databases.',
        ['PHP', 'Laravel', 'MySQL'],
        'Normal resume with matching skills'
    ],
    [
        'Full-stack developer proficient in JavaScript, React, and Node.js. Developed web applications with MongoDB.',
        ['Python', 'Django', 'PostgreSQL'],
        'Normal resume with no matching skills'
    ],
    
    // Long resumes with all sections
    [
        "John Doe\nSoftware Engineer\n\nPROFESSIONAL SUMMARY\nExperienced software engineer with 5 years in web development.\n\nTECHNICAL SKILLS\nPHP, Laravel, MySQL, JavaScript, React\n\nEXPERIENCE\nSenior Developer | Tech Company | 2020-2023\n• Developed RESTful APIs using Laravel\n• Optimized database queries reducing response time by 40%\n• Led team of 3 developers\n\nPROJECTS\nE-commerce Platform\n• Built using Laravel and MySQL\n• Handled 10,000+ daily users\n\nEDUCATION\nBachelor of Computer Science | University | 2018",
        ['PHP', 'Laravel', 'MySQL', 'JavaScript', 'React'],
        'Complete resume with all sections and all skills'
    ],
    [
        "Jane Smith\nData Scientist\n\nPROFESSIONAL SUMMARY\nData scientist specializing in machine learning.\n\nTECHNICAL SKILLS\nPython, TensorFlow, Pandas, NumPy\n\nEXPERIENCE\nData Scientist | AI Company | 2021-2023\n• Developed ML models\n• Improved accuracy by 25%\n\nPROJECTS\nPredictive Analytics System\n• Built with Python and TensorFlow\n\nEDUCATION\nMaster of Data Science | University | 2020",
        ['PHP', 'Laravel', 'MySQL'],
        'Complete resume with no matching skills'
    ],
    
    // Special characters and formatting
    [
        "Developer with PHP/Laravel/MySQL experience. Built APIs & web apps. Worked with 100+ clients.",
        ['PHP', 'Laravel', 'MySQL'],
        'Resume with special characters'
    ],
    
    // Case variations
    [
        'EXPERIENCED PHP DEVELOPER WITH LARAVEL AND MYSQL EXPERTISE',
        ['php', 'laravel', 'mysql'],
        'All uppercase resume'
    ],
    [
        'experienced php developer with laravel and mysql expertise',
        ['PHP', 'Laravel', 'MySQL'],
        'All lowercase resume'
    ],
    
    // Large skill lists
    [
        'Full-stack developer with PHP, Laravel, MySQL, JavaScript, React, Vue.js, Node.js, Express, MongoDB, Redis, Docker, Git',
        ['PHP', 'Laravel', 'MySQL', 'JavaScript', 'React', 'Vue.js', 'Node.js', 'Express', 'MongoDB', 'Redis', 'Docker', 'Git'],
        'Resume with many skills all matching'
    ],
    [
        'Software engineer',
        ['PHP', 'Laravel', 'MySQL', 'JavaScript', 'React', 'Vue.js', 'Node.js', 'Express', 'MongoDB', 'Redis', 'Docker', 'Git', 'Python', 'Django', 'PostgreSQL'],
        'Minimal resume with many required skills'
    ],
    
    // Multi-word skills
    [
        'AI researcher specializing in Machine Learning and Deep Learning with Natural Language Processing expertise',
        ['Machine Learning', 'Deep Learning', 'Natural Language Processing'],
        'Resume with multi-word skills'
    ],
    
    // Numeric content
    [
        'Developer with 5 years experience. Improved performance by 50%. Reduced costs by $100,000. Managed team of 10 developers.',
        ['PHP', 'Laravel'],
        'Resume with many numbers'
    ],
]);

/**
 * Property Test 2: Score Component Weights
 * 
 * Tests that the weighted formula always produces values in [0,100]
 * when all component scores are in [0,100]
 */
test('property: weighted score formula always produces values in valid range', function (int $skillMatch, int $keywordScore, int $expScore, int $projScore, int $completeness) {
    // All inputs are guaranteed to be in [0,100] by the data provider
    
    // Apply the exact formula from scoreResume
    $overall = (int) round(
        ($skillMatch * 0.40) +
        ($keywordScore * 0.25) +
        ($expScore * 0.20) +
        ($projScore * 0.10) +
        ($completeness * 0.05)
    );
    
    // Assert: Result is within bounds
    expect($overall)
        ->toBeGreaterThanOrEqual(0, "Weighted score should be >= 0 for components: skill={$skillMatch}, kw={$keywordScore}, exp={$expScore}, proj={$projScore}, comp={$completeness}")
        ->toBeLessThanOrEqual(100, "Weighted score should be <= 100 for components: skill={$skillMatch}, kw={$keywordScore}, exp={$expScore}, proj={$projScore}, comp={$completeness}");
        
})->with(function () {
    // Generate test cases covering the range [0, 100] for each component
    $testCases = [];
    
    // Edge cases: all zeros
    $testCases[] = [0, 0, 0, 0, 0];
    
    // Edge cases: all 100s
    $testCases[] = [100, 100, 100, 100, 100];
    
    // Edge cases: one component at max, others at min
    $testCases[] = [100, 0, 0, 0, 0];
    $testCases[] = [0, 100, 0, 0, 0];
    $testCases[] = [0, 0, 100, 0, 0];
    $testCases[] = [0, 0, 0, 100, 0];
    $testCases[] = [0, 0, 0, 0, 100];
    
    // Edge cases: one component at min, others at max
    $testCases[] = [0, 100, 100, 100, 100];
    $testCases[] = [100, 0, 100, 100, 100];
    $testCases[] = [100, 100, 0, 100, 100];
    $testCases[] = [100, 100, 100, 0, 100];
    $testCases[] = [100, 100, 100, 100, 0];
    
    // Mid-range values
    $testCases[] = [50, 50, 50, 50, 50];
    $testCases[] = [60, 70, 80, 90, 100];
    $testCases[] = [25, 25, 25, 25, 25];
    $testCases[] = [75, 75, 75, 75, 75];
    
    // Random-like combinations
    $testCases[] = [85, 60, 45, 30, 70];
    $testCases[] = [40, 90, 55, 20, 80];
    $testCases[] = [95, 15, 70, 85, 40];
    $testCases[] = [10, 80, 30, 95, 60];
    $testCases[] = [65, 35, 90, 50, 15];
    
    // Boundary values for each component
    for ($i = 0; $i <= 100; $i += 25) {
        $testCases[] = [$i, 50, 50, 50, 50];
        $testCases[] = [50, $i, 50, 50, 50];
        $testCases[] = [50, 50, $i, 50, 50];
        $testCases[] = [50, 50, 50, $i, 50];
        $testCases[] = [50, 50, 50, 50, $i];
    }
    
    // Additional random combinations to increase coverage
    $testCases[] = [33, 67, 89, 12, 45];
    $testCases[] = [78, 22, 56, 91, 34];
    $testCases[] = [11, 99, 44, 66, 88];
    $testCases[] = [100, 100, 0, 0, 0];
    $testCases[] = [0, 0, 100, 100, 100];
    
    return $testCases;
});

/**
 * Property Test 3: All Component Scores Within Bounds
 * 
 * Tests that all individual component scores are also within [0, 100]
 */
test('property: all component scores are within bounds', function (string $resumeText, array $jobSkills) {
    // Arrange
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Software Engineer',
        'organization' => 'Tech Company',
        'description' => 'Software development position requiring programming skills and experience',
    ]);
    
    // Create service instance and use reflection to test the private scoreResume method
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Act
    $result = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    // Assert: All component scores are within bounds
    expect($result['skill_match'])
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(100);
        
    expect($result['keyword_score'])
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(100);
        
    expect($result['format_score']) // completeness
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(100);
        
    expect($result['exp_score'])
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(100);
        
    expect($result['proj_score'])
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(100);
        
})->with([
    ['', []],
    ['PHP developer', ['PHP']],
    ['Software Engineer with experience in web development', ['Python', 'Django']],
    [
        "PROFESSIONAL SUMMARY\nSoftware engineer\n\nTECHNICAL SKILLS\nPHP, Laravel\n\nEXPERIENCE\nDeveloper | Company | 2020-2023\n• Built web applications\n\nPROJECTS\nWeb App\n• Used Laravel\n\nEDUCATION\nBSc Computer Science | 2019",
        ['PHP', 'Laravel', 'MySQL']
    ],
]);

/**
 * Property Test 4: Score Consistency
 * 
 * Tests that the same inputs always produce the same scores (determinism)
 */
test('property: scoring is deterministic', function () {
    $resumeText = 'Experienced PHP and Laravel developer with MySQL database expertise. Built RESTful APIs and web applications.';
    $jobSkills = ['PHP', 'Laravel', 'MySQL', 'JavaScript'];
    
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Backend Developer',
        'organization' => 'Tech Company',
        'description' => 'Backend development position',
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Run scoring multiple times
    $result1 = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    $result2 = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    $result3 = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    // All results should be identical
    expect($result1['overall_score'])->toBe($result2['overall_score'])
        ->and($result2['overall_score'])->toBe($result3['overall_score'])
        ->and($result1['skill_match'])->toBe($result2['skill_match'])
        ->and($result1['keyword_score'])->toBe($result2['keyword_score'])
        ->and($result1['exp_score'])->toBe($result2['exp_score'])
        ->and($result1['proj_score'])->toBe($result2['proj_score'])
        ->and($result1['format_score'])->toBe($result2['format_score']);
});

/**
 * Property Test 5: Score Monotonicity
 * 
 * Tests that adding matching skills increases or maintains the skill_match score
 */
test('property: adding matching skills increases skill match score', function () {
    $jobSkills = ['PHP', 'Laravel', 'MySQL', 'Redis', 'Docker'];
    
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Backend Developer',
        'organization' => 'Tech Company',
        'description' => 'Backend development position',
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Progressive resumes with increasing skill matches
    $resume1 = 'Software developer';
    $resume2 = 'Software developer with PHP experience';
    $resume3 = 'Software developer with PHP and Laravel experience';
    $resume4 = 'Software developer with PHP, Laravel, and MySQL experience';
    $resume5 = 'Software developer with PHP, Laravel, MySQL, Redis, and Docker experience';
    
    $score1 = $scoreResumeMethod->invoke($service, $resume1, $internship);
    $score2 = $scoreResumeMethod->invoke($service, $resume2, $internship);
    $score3 = $scoreResumeMethod->invoke($service, $resume3, $internship);
    $score4 = $scoreResumeMethod->invoke($service, $resume4, $internship);
    $score5 = $scoreResumeMethod->invoke($service, $resume5, $internship);
    
    // Skill match scores should be monotonically increasing
    expect($score2['skill_match'])->toBeGreaterThanOrEqual($score1['skill_match'])
        ->and($score3['skill_match'])->toBeGreaterThanOrEqual($score2['skill_match'])
        ->and($score4['skill_match'])->toBeGreaterThanOrEqual($score3['skill_match'])
        ->and($score5['skill_match'])->toBeGreaterThanOrEqual($score4['skill_match']);
        
    // Overall scores should also generally increase
    expect($score5['overall_score'])->toBeGreaterThan($score1['overall_score']);
});
