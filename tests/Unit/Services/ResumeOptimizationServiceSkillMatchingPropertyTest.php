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
 * 
 * The test uses Pest PHP datasets to generate random combinations of:
 *   - Job skill lists (various sizes and formats)
 *   - Resume text content (with and without skills)
 */

use App\Models\Internship;
use App\Models\Profile;
use App\Models\User;
use App\Services\ResumeOptimizationService;
use Illuminate\Support\Facades\Storage;

// Simple test to verify file is being loaded
test('file loads correctly', function () {
    expect(true)->toBeTrue();
});

/**
 * Property Test: Missing Skills Complement
 * 
 * Tests that for any combination of job skills and resume text:
 * 1. The union of matching_skills and missing_skills equals the complete set of job required_skills
 * 2. The intersection of matching_skills and missing_skills is empty (no skill appears in both)
 */
test('property: matching and missing skills form a complete partition of job skills', function (array $jobSkills, string $resumeText, string $description) {
    // Arrange
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'resume_path' => 'resumes/test_resume.pdf',
    ]);
    
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Test Position',
        'organization' => 'Test Company',
        'description' => 'Test job description',
    ]);
    
    // Mock the PDF extraction to return our test resume text
    Storage::fake('public');
    Storage::disk('public')->put('resumes/test_resume.pdf', 'fake pdf content');
    
    // Create service instance and use reflection to test the private scoreResume method
    $service = new ResumeOptimizationService();
    
    // Use reflection to access the private scoreResume method
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
        ->toBe($jobSkillsLower)
        ->and(count($union))
        ->toBe(count($jobSkillsLower), "Union of matching and missing skills should equal job skills count for: {$description}");
    
    // Assert Property 2: Intersection is empty
    // matchingSkills ∩ missingSkills = ∅
    $intersection = array_intersect($matchingSkillsLower, $missingSkillsLower);
    
    expect($intersection)
        ->toBeEmpty("No skill should appear in both matching and missing for: {$description}");
    
    // Additional assertions for data integrity
    expect($matchingSkills)
        ->toBeArray()
        ->and($missingSkills)
        ->toBeArray()
        ->and(count($matchingSkills) + count($missingSkills))
        ->toBe(count($jobSkills), "Total count of matching + missing should equal job skills count for: {$description}");
    
})->with([
    // Scenario 1: Empty job skills
    [[], 'Software Engineer with experience in PHP, Laravel, MySQL', 'Empty job skills list'],
    
    // Scenario 2: All skills present in resume
    [['PHP', 'Laravel', 'MySQL'], 'Experienced developer with PHP, Laravel, and MySQL expertise. Built multiple web applications.', 'All job skills present in resume'],
    
    // Scenario 3: No skills present in resume
    [['Python', 'Django', 'PostgreSQL'], 'Software Engineer with experience in PHP, Laravel, MySQL. No Python experience.', 'No job skills present in resume'],
    
    // Scenario 4: Partial skill match
    [['JavaScript', 'React', 'Node.js', 'MongoDB'], 'Full-stack developer proficient in JavaScript and React. Experience with frontend development.', 'Partial skill match (2 out of 4)'],
    
    // Scenario 5: Case-insensitive matching
    [['javascript', 'react', 'nodejs'], 'JAVASCRIPT and REACT developer. Worked with NODEJS backend services.', 'Case-insensitive skill matching'],
    
    // Scenario 6: Multi-word skills
    [['Machine Learning', 'Deep Learning', 'Natural Language Processing'], 'AI researcher specializing in Machine Learning and Deep Learning. Published papers on neural networks.', 'Multi-word skills (2 out of 3 present)'],
    
    // Scenario 7: Skills with special characters
    [['C++', 'C#', '.NET', 'ASP.NET'], 'Backend developer with C++ and C# experience. Built applications using .NET framework.', 'Skills with special characters'],
    
    // Scenario 8: Large skill list
    [['PHP', 'Laravel', 'MySQL', 'JavaScript', 'Vue.js', 'Redis', 'Docker', 'Git', 'AWS', 'REST API'], 'Full-stack developer with PHP, Laravel, MySQL, JavaScript, and Vue.js. Experience with Docker and Git.', 'Large skill list (7 out of 10 present)'],
    
    // Scenario 9: Skills embedded in sentences
    [['Python', 'TensorFlow', 'Keras'], 'Developed machine learning models using Python and TensorFlow. Implemented neural networks with various frameworks.', 'Skills embedded in sentences (2 out of 3)'],
    
    // Scenario 10: Similar skill names (word boundary test)
    [['Java', 'JavaScript'], 'Experienced JavaScript developer. Built web applications with modern JavaScript frameworks.', 'Similar skill names - JavaScript should not match Java'],
    
    // Scenario 11: Skills in bullet points
    [['React', 'Redux', 'TypeScript'], "TECHNICAL SKILLS\n• React\n• Redux\n• HTML/CSS\n\nEXPERIENCE\nFrontend Developer", 'Skills in bullet point format (2 out of 3)'],
    
    // Scenario 12: Empty resume
    [['PHP', 'Laravel', 'MySQL'], '', 'Empty resume text'],
    
    // Scenario 13: Single skill
    [['Python'], 'Python developer with 5 years of experience.', 'Single skill present'],
    
    // Scenario 14: Skills with hyphens
    [['Node.js', 'Express.js', 'Next.js'], 'Backend developer using Node.js and Express.js for API development.', 'Skills with dots/hyphens (2 out of 3)'],
    
    // Scenario 15: Skills in different sections
    [['AWS', 'Docker', 'Kubernetes'], "SKILLS: AWS, Docker\n\nEXPERIENCE:\nDeployed applications to cloud infrastructure.\n\nPROJECTS:\nContainerized microservices.", 'Skills scattered across sections (2 out of 3)'],
    
    // Scenario 16: Repeated skills in resume
    [['React', 'Node.js'], 'React developer. Built React applications. Experience with React hooks. Also worked with Node.js backend.', 'Repeated skills in resume (both present)'],
    
    // Scenario 17: Skills as part of compound words
    [['SQL', 'NoSQL'], 'Database expert with SQL and NoSQL experience. Worked with MySQL and MongoDB.', 'Skills as part of compound words'],
    
    // Scenario 18: Mixed case multi-word skills
    [['Machine learning', 'data science', 'Deep Learning'], 'Data Scientist with expertise in MACHINE LEARNING and deep learning algorithms.', 'Mixed case multi-word skills (2 out of 3)'],
    
    // Scenario 19: Skills with version numbers
    [['Python', 'Django', 'PostgreSQL'], 'Python 3.9 developer using Django 4.0 framework. Experience with PostgreSQL 14 database.', 'Skills with version numbers (all 3 present)'],
    
    // Scenario 20: Very long resume with many skills
    [['PHP', 'Laravel', 'Vue.js', 'MySQL', 'Redis'], str_repeat('Software Engineer with extensive experience in web development. ', 50) . 'Technical skills include PHP, Laravel, Vue.js, and MySQL. ' . str_repeat('Built scalable applications. ', 30), 'Long resume text (4 out of 5 skills present)'],
]);

/**
 * Additional property test: Skill matching is deterministic
 * 
 * Running the same input twice should produce identical results
 */
test('property: skill matching is deterministic', function () {
    // Arrange
    $jobSkills = ['PHP', 'Laravel', 'MySQL', 'Redis'];
    $resumeText = 'Experienced PHP and Laravel developer. Built web applications with MySQL databases.';
    
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Backend Developer',
        'organization' => 'Tech Company',
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Act - Run the same scoring twice
    $result1 = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    $result2 = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    // Assert - Results should be identical
    expect($result1['matching_skills'])
        ->toBe($result2['matching_skills'])
        ->and($result1['missing_skills'])
        ->toBe($result2['missing_skills'])
        ->and($result1['skill_match'])
        ->toBe($result2['skill_match']);
});

/**
 * Additional property test: Empty job skills edge case
 * 
 * When job has no required skills, both matching and missing should be empty
 */
test('property: empty job skills results in empty matching and missing arrays', function () {
    // Arrange
    $jobSkills = [];
    $resumeText = 'Software Engineer with PHP, Laravel, MySQL, JavaScript, React, Node.js experience.';
    
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Developer',
        'organization' => 'Company',
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Act
    $result = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    // Assert
    expect($result['matching_skills'])
        ->toBeEmpty()
        ->and($result['missing_skills'])
        ->toBeEmpty();
});

/**
 * Additional property test: All skills missing
 * 
 * When resume contains none of the job skills, all should be in missing_skills
 */
test('property: when no skills match, all job skills are in missing array', function () {
    // Arrange
    $jobSkills = ['Rust', 'Go', 'Elixir', 'Haskell'];
    $resumeText = 'PHP and JavaScript developer with Laravel and React experience.';
    
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Systems Programmer',
        'organization' => 'Tech Startup',
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Act
    $result = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    // Assert
    expect($result['matching_skills'])
        ->toBeEmpty()
        ->and(count($result['missing_skills']))
        ->toBe(count($jobSkills))
        ->and(array_map('strtolower', $result['missing_skills']))
        ->toBe(array_map('strtolower', $jobSkills));
});

/**
 * Additional property test: All skills present
 * 
 * When resume contains all job skills, all should be in matching_skills
 */
test('property: when all skills match, all job skills are in matching array', function () {
    // Arrange
    $jobSkills = ['PHP', 'Laravel', 'MySQL'];
    $resumeText = 'Senior PHP developer with extensive Laravel framework experience. Expert in MySQL database design and optimization.';
    
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Senior Backend Developer',
        'organization' => 'Enterprise Corp',
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Act
    $result = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    // Assert
    expect($result['missing_skills'])
        ->toBeEmpty()
        ->and(count($result['matching_skills']))
        ->toBe(count($jobSkills))
        ->and(array_map('strtolower', $result['matching_skills']))
        ->toBe(array_map('strtolower', $jobSkills));
});

/**
 * Additional property test: Skill matching respects word boundaries
 * 
 * "Java" should not match "JavaScript"
 */
test('property: skill matching respects word boundaries', function () {
    // Arrange
    $jobSkills = ['Java'];
    // Resume only mentions JavaScript and TypeScript — "Java" as a standalone word is NOT present
    $resumeText = 'Experienced JavaScript developer. Built applications with JavaScript and TypeScript. Strong frontend skills.';
    
    $internship = Internship::factory()->create([
        'required_skills' => $jobSkills,
        'title' => 'Java Developer',
        'organization' => 'Enterprise',
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreResumeMethod = $reflection->getMethod('scoreResume');
    $scoreResumeMethod->setAccessible(true);
    
    // Act
    $result = $scoreResumeMethod->invoke($service, $resumeText, $internship);
    
    // Assert - Java should NOT be matched (JavaScript should not count as Java)
    expect($result['matching_skills'])
        ->toBeEmpty()
        ->and($result['missing_skills'])
        ->toBe(['java']);
});
