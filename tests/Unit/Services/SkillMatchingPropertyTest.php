<?php

/**
 * Property-Based Test for ResumeOptimizationService Skill Matching
 * 
 * **Validates: Requirements 1.3, 1.12**
 * 
 * This test validates Property 4: Missing Skills Complement
 * For any set of job skills and any resume text:
 *   - matchingSkills ∪ missingSkills = jobSkills
 *   - matchingSkills ∩ missingSkills = ∅
 */

use App\Models\Internship;
use App\Services\ResumeOptimizationService;

/**
 * Property Test: Missing Skills Complement
 * 
 * Tests that for any combination of job skills and resume text:
 * 1. The union of matching_skills and missing_skills equals the complete set of job required_skills
 * 2. The intersection of matching_skills and missing_skills is empty (no skill appears in both)
 */
test('property: matching and missing skills form a complete partition of job skills', function (array $jobSkills, string $resumeText, string $description) {
    // Arrange
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Test Position',
        'organization' => 'Test Company',
        'description' => 'Test job description',
    ]);
    
    // Create service instance and use reflection to test the private scoreResume method
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Act
    $result = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    // Extract the results
    $matchingSkills = $result['matching_skills'] ?? [];
    $missingSkills = $result['missing_skills'] ?? [];
    
    // Normalize all arrays to lowercase for comparison (since matching is case-insensitive)
    $jobSkillsLower = array_map('strtolower', $jobSkills);
    $matchingSkillsLower = array_map('strtolower', $matchingSkills);
    $missingSkillsLower = array_map('strtolower', $missingSkills);
    
    // Assert Property 1: Union equals job skills
    // matchingSkills ∪ missingSkills = jobSkills
    $union = array_unique(array_merge($matchingSkillsLower, $missingSkillsLower));
    sort($union);
    sort($jobSkillsLower);
    
    expect($union)
        ->toBe($jobSkillsLower, "Union of matching and missing skills should equal job skills for: {$description}");
    
    // Assert Property 2: Intersection is empty
    // matchingSkills ∩ missingSkills = ∅
    $intersection = array_intersect($matchingSkillsLower, $missingSkillsLower);
    
    expect($intersection)
        ->toBeEmpty("No skill should appear in both matching and missing for: {$description}");
    
    // Additional assertion: Total count
    expect(count($matchingSkills) + count($missingSkills))
        ->toBe(count($jobSkills), "Total count of matching + missing should equal job skills count for: {$description}");
    
})->with([
    [[], 'Software Engineer with experience in PHP, Laravel, MySQL', 'Empty job skills list'],
    [['PHP', 'Laravel', 'MySQL'], 'Experienced developer with PHP, Laravel, and MySQL expertise.', 'All skills present'],
    [['Python', 'Django', 'PostgreSQL'], 'Software Engineer with PHP, Laravel, MySQL experience.', 'No skills present'],
    [['JavaScript', 'React', 'Node.js', 'MongoDB'], 'Full-stack developer proficient in JavaScript and React.', 'Partial match'],
    [['javascript', 'react', 'nodejs'], 'JAVASCRIPT and REACT developer. Worked with NODEJS backend.', 'Case-insensitive'],
    [['Machine Learning', 'Deep Learning'], 'AI researcher specializing in Machine Learning and Deep Learning.', 'Multi-word skills'],
    [['PHP', 'Laravel', 'MySQL', 'JavaScript', 'Vue.js'], 'Full-stack developer with PHP, Laravel, MySQL, JavaScript, and Vue.js.', 'Large skill list'],
    [['Python', 'TensorFlow', 'Keras'], 'Developed machine learning models using Python and TensorFlow.', 'Skills in sentences'],
    [['Java', 'JavaScript'], 'Experienced JavaScript developer. Built web applications with JavaScript.', 'Word boundary test'],
    [['PHP', 'Laravel', 'MySQL'], '', 'Empty resume'],
]);

test('property: skill matching is deterministic', function () {
    $jobSkills = ['PHP', 'Laravel', 'MySQL', 'Redis'];
    $resumeText = 'Experienced PHP and Laravel developer. Built web applications with MySQL databases.';
    
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    $result1 = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    $result2 = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    expect($result1['matching_skills'])->toBe($result2['matching_skills'])
        ->and($result1['missing_skills'])->toBe($result2['missing_skills']);
});

test('property: empty job skills results in empty arrays', function () {
    $internship = Internship::factory()->create(['required_skills' => []]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    $result = $scoreResumeMethod->invoke($service, 'Software Engineer with PHP, Laravel, MySQL.', $internship);
    
    expect($result['matching_skills'])->toBeEmpty()
        ->and($result['missing_skills'])->toBeEmpty();
});

test('property: all skills missing when none match', function () {
    $jobSkills = ['Rust', 'Go', 'Elixir', 'Haskell'];
    $internship = Internship::factory()->create(['required_skills' => $jobSkills]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    $result = $scoreResumeMethod->invoke($service, 'PHP and JavaScript developer with Laravel and React experience.', $internship);
    
    expect($result['matching_skills'])->toBeEmpty()
        ->and(count($result['missing_skills']))->toBe(count($jobSkills));
});

test('property: all skills present when all match', function () {
    $jobSkills = ['PHP', 'Laravel', 'MySQL'];
    $internship = Internship::factory()->create(['required_skills' => $jobSkills]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    $result = $scoreResumeMethod->invoke($service, 'Senior PHP developer with extensive Laravel framework experience. Expert in MySQL database design.', $internship);
    
    expect($result['missing_skills'])->toBeEmpty()
        ->and(count($result['matching_skills']))->toBe(count($jobSkills));
});

test('property: skill matching respects word boundaries', function () {
    $internship = Internship::factory()->create(['required_skills' => ['Java']]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    $result = $scoreResumeMethod->invoke($service, 'Experienced JavaScript developer. Built applications with JavaScript and TypeScript.', $internship);
    
    expect($result['matching_skills'])->toBeEmpty()
        ->and($result['missing_skills'])->toBe(['java']);
});
