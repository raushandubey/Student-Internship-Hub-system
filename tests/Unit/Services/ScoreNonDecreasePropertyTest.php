<?php

/**
 * Property-Based Test for Score Non-Decrease After Rewrite
 * 
 * **Validates: Requirements 6.1, 6.2, 6.3**
 * 
 * This test validates Property 6: Score Non-Decrease After Rewrite
 * - When the rewritten text contains all job-required skills, 
 *   scoreResume(rewritten).skill_match ≥ scoreResume(original).skill_match
 */

use App\Models\Internship;
use App\Services\ResumeOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property Test: Skill Match Score Never Decreases When All Skills Are Added
 * 
 * Tests that when a rewritten resume contains all job-required skills,
 * the skill match score is always greater than or equal to the original score.
 */
test('property: skill match score never decreases when all required skills are added', function (
    array $requiredSkills,
    array $originalSkills,
    array $additionalContent
) {
    // Arrange: Create a test internship with required skills
    $internship = Internship::factory()->create([
        'title' => 'Software Engineer Intern',
        'organization' => 'Test Company',
        'location' => 'Remote',
        'required_skills' => $requiredSkills,
        'description' => 'Test internship for property-based testing',
    ]);

    // Build original resume text (missing some skills)
    $originalResume = buildResumeText(
        name: 'Test Candidate',
        skills: $originalSkills,
        experience: $additionalContent['experience'] ?? 'Worked on various projects',
        projects: $additionalContent['projects'] ?? 'Built web applications',
        education: $additionalContent['education'] ?? 'Bachelor of Computer Science'
    );

    // Build rewritten resume text (contains ALL required skills)
    $rewrittenResume = buildResumeText(
        name: 'Test Candidate',
        skills: array_unique(array_merge($originalSkills, $requiredSkills)),
        experience: $additionalContent['experience'] ?? 'Developed and implemented various projects',
        projects: $additionalContent['projects'] ?? 'Built and deployed web applications',
        education: $additionalContent['education'] ?? 'Bachelor of Computer Science'
    );

    // Act: Score both resumes using the service
    $service = new ResumeOptimizationService();
    
    // Use reflection to access the private scoreResume method
    $reflection = new ReflectionClass($service);
    $scoreMethod = $reflection->getMethod('scoreResume');
    $scoreMethod->setAccessible(true);

    $originalScore = $scoreMethod->invoke($service, $originalResume, $internship);
    $rewrittenScore = $scoreMethod->invoke($service, $rewrittenResume, $internship);

    // Assert: Skill match score should never decrease
    expect($rewrittenScore['skill_match'])
        ->toBeGreaterThanOrEqual(
            $originalScore['skill_match'],
            "Rewritten skill match ({$rewrittenScore['skill_match']}) should be >= original ({$originalScore['skill_match']})"
        );

    // Additional assertion: If all skills are present in rewritten, skill_match should be 100
    if (count($requiredSkills) > 0) {
        expect($rewrittenScore['skill_match'])
            ->toBe(100, "Rewritten resume with all required skills should have 100% skill match");
    }

})->with(function () {
    $testCases = [];

    // Test Case 1: Original has no skills, rewritten has all required skills
    $testCases[] = [
        'requiredSkills' => ['PHP', 'Laravel', 'MySQL'],
        'originalSkills' => [],
        'additionalContent' => [
            'experience' => 'Worked on web development projects',
            'projects' => 'Built various applications',
            'education' => 'Bachelor of Computer Science',
        ],
    ];

    // Test Case 2: Original has some skills, rewritten has all required skills
    $testCases[] = [
        'requiredSkills' => ['PHP', 'Laravel', 'MySQL', 'JavaScript'],
        'originalSkills' => ['PHP', 'MySQL'],
        'additionalContent' => [
            'experience' => 'Developed backend systems using PHP',
            'projects' => 'Created database-driven applications',
            'education' => 'Bachelor of Computer Science',
        ],
    ];

    // Test Case 3: Original has most skills, rewritten has all required skills
    $testCases[] = [
        'requiredSkills' => ['Python', 'Django', 'PostgreSQL', 'Docker'],
        'originalSkills' => ['Python', 'Django', 'PostgreSQL'],
        'additionalContent' => [
            'experience' => 'Built web applications with Django framework',
            'projects' => 'Developed RESTful APIs',
            'education' => 'Master of Computer Science',
        ],
    ];

    // Test Case 4: Single required skill
    $testCases[] = [
        'requiredSkills' => ['React'],
        'originalSkills' => [],
        'additionalContent' => [
            'experience' => 'Frontend development experience',
            'projects' => 'Built user interfaces',
            'education' => 'Bachelor of Computer Science',
        ],
    ];

    // Test Case 5: Many required skills, original has few
    $testCases[] = [
        'requiredSkills' => ['Java', 'Spring Boot', 'Hibernate', 'Maven', 'JUnit', 'REST API'],
        'originalSkills' => ['Java', 'Maven'],
        'additionalContent' => [
            'experience' => 'Java development with enterprise frameworks',
            'projects' => 'Built microservices architecture',
            'education' => 'Bachelor of Computer Science',
        ],
    ];

    // Test Case 6: Multi-word skills
    $testCases[] = [
        'requiredSkills' => ['Machine Learning', 'Deep Learning', 'TensorFlow', 'Python'],
        'originalSkills' => ['Python'],
        'additionalContent' => [
            'experience' => 'Data science and AI projects',
            'projects' => 'Developed predictive models',
            'education' => 'Master of Data Science',
        ],
    ];

    // Test Case 7: Skills with special characters
    $testCases[] = [
        'requiredSkills' => ['C++', 'C#', '.NET', 'ASP.NET'],
        'originalSkills' => ['C++'],
        'additionalContent' => [
            'experience' => 'Systems programming and application development',
            'projects' => 'Built desktop applications',
            'education' => 'Bachelor of Computer Science',
        ],
    ];

    // Test Case 8: Original already has all skills (edge case)
    $testCases[] = [
        'requiredSkills' => ['Node.js', 'Express', 'MongoDB'],
        'originalSkills' => ['Node.js', 'Express', 'MongoDB'],
        'additionalContent' => [
            'experience' => 'Full-stack JavaScript development',
            'projects' => 'Built MERN stack applications',
            'education' => 'Bachelor of Computer Science',
        ],
    ];

    // Test Case 9: Large skill set
    $testCases[] = [
        'requiredSkills' => [
            'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular',
            'Node.js', 'Express', 'MongoDB', 'PostgreSQL', 'Redis',
            'Docker', 'Kubernetes', 'AWS', 'Git', 'CI/CD'
        ],
        'originalSkills' => ['JavaScript', 'React', 'Node.js', 'MongoDB', 'Git'],
        'additionalContent' => [
            'experience' => 'Full-stack development with modern frameworks',
            'projects' => 'Built scalable web applications with cloud deployment',
            'education' => 'Bachelor of Computer Science',
        ],
    ];

    // Test Case 10: Empty required skills (edge case)
    $testCases[] = [
        'requiredSkills' => [],
        'originalSkills' => ['PHP', 'Laravel'],
        'additionalContent' => [
            'experience' => 'Web development',
            'projects' => 'Various projects',
            'education' => 'Bachelor of Computer Science',
        ],
    ];

    return $testCases;
});

/**
 * Property Test: Overall Score Never Decreases When Skills Are Added
 * 
 * Tests that when all required skills are added to a resume,
 * the overall score should increase or stay the same.
 */
test('property: overall score never decreases when all required skills are added', function (
    array $requiredSkills,
    array $originalSkills
) {
    // Arrange
    $internship = Internship::factory()->create([
        'title' => 'Backend Developer Intern',
        'organization' => 'Tech Corp',
        'required_skills' => $requiredSkills,
        'description' => 'Backend development internship',
    ]);

    $originalResume = buildResumeText(
        name: 'Jane Doe',
        skills: $originalSkills,
        experience: 'Software development experience',
        projects: 'Built backend systems',
        education: 'Bachelor of Computer Science'
    );

    $rewrittenResume = buildResumeText(
        name: 'Jane Doe',
        skills: array_unique(array_merge($originalSkills, $requiredSkills)),
        experience: 'Developed and optimized backend systems',
        projects: 'Built and deployed scalable backend systems',
        education: 'Bachelor of Computer Science'
    );

    // Act
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreMethod = $reflection->getMethod('scoreResume');
    $scoreMethod->setAccessible(true);

    $originalScore = $scoreMethod->invoke($service, $originalResume, $internship);
    $rewrittenScore = $scoreMethod->invoke($service, $rewrittenResume, $internship);

    // Assert: Overall score should never decrease
    expect($rewrittenScore['overall_score'])
        ->toBeGreaterThanOrEqual(
            $originalScore['overall_score'],
            "Rewritten overall score ({$rewrittenScore['overall_score']}) should be >= original ({$originalScore['overall_score']})"
        );

})->with(function () {
    return [
        [['PHP', 'Laravel', 'MySQL'], []],
        [['Python', 'Django', 'PostgreSQL'], ['Python']],
        [['JavaScript', 'React', 'Node.js'], ['JavaScript', 'React']],
        [['Java', 'Spring Boot'], ['Java']],
        [['Ruby', 'Rails', 'PostgreSQL'], ['Ruby', 'Rails', 'PostgreSQL']],
    ];
});

/**
 * Property Test: Missing Skills List Decreases When Skills Are Added
 * 
 * Tests that when skills are added to a resume,
 * the missing_skills array should decrease or stay empty.
 */
test('property: missing skills list decreases when skills are added', function (
    array $requiredSkills,
    array $originalSkills
) {
    // Arrange
    $internship = Internship::factory()->create([
        'title' => 'Full Stack Developer Intern',
        'organization' => 'Startup Inc',
        'required_skills' => $requiredSkills,
        'description' => 'Full stack development position',
    ]);

    $originalResume = buildResumeText(
        name: 'John Smith',
        skills: $originalSkills,
        experience: 'Full stack development',
        projects: 'Web applications',
        education: 'Bachelor of Computer Science'
    );

    $rewrittenResume = buildResumeText(
        name: 'John Smith',
        skills: array_unique(array_merge($originalSkills, $requiredSkills)),
        experience: 'Developed full stack applications',
        projects: 'Built and deployed web applications',
        education: 'Bachelor of Computer Science'
    );

    // Act
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreMethod = $reflection->getMethod('scoreResume');
    $scoreMethod->setAccessible(true);

    $originalScore = $scoreMethod->invoke($service, $originalResume, $internship);
    $rewrittenScore = $scoreMethod->invoke($service, $rewrittenResume, $internship);

    // Assert: Missing skills should decrease or be empty
    expect(count($rewrittenScore['missing_skills']))
        ->toBeLessThanOrEqual(
            count($originalScore['missing_skills']),
            "Rewritten missing skills count (" . count($rewrittenScore['missing_skills']) . 
            ") should be <= original (" . count($originalScore['missing_skills']) . ")"
        );

    // If all required skills are in rewritten, missing_skills should be empty
    if (count($requiredSkills) > 0) {
        expect($rewrittenScore['missing_skills'])
            ->toBeEmpty("Rewritten resume with all required skills should have no missing skills");
    }

})->with(function () {
    return [
        [['HTML', 'CSS', 'JavaScript'], []],
        [['Vue.js', 'Vuex', 'Nuxt.js'], ['Vue.js']],
        [['Angular', 'TypeScript', 'RxJS'], ['Angular', 'TypeScript']],
        [['Swift', 'iOS', 'Xcode'], []],
        [['Kotlin', 'Android', 'Jetpack'], ['Kotlin']],
    ];
});

/**
 * Property Test: Matching Skills List Increases When Skills Are Added
 * 
 * Tests that when skills are added to a resume,
 * the matching_skills array should increase or stay the same.
 */
test('property: matching skills list increases when skills are added', function (
    array $requiredSkills,
    array $originalSkills
) {
    // Arrange
    $internship = Internship::factory()->create([
        'title' => 'DevOps Engineer Intern',
        'organization' => 'Cloud Services Ltd',
        'required_skills' => $requiredSkills,
        'description' => 'DevOps and cloud infrastructure',
    ]);

    $originalResume = buildResumeText(
        name: 'Alice Johnson',
        skills: $originalSkills,
        experience: 'DevOps and infrastructure management',
        projects: 'Cloud deployment projects',
        education: 'Bachelor of Computer Science'
    );

    $rewrittenResume = buildResumeText(
        name: 'Alice Johnson',
        skills: array_unique(array_merge($originalSkills, $requiredSkills)),
        experience: 'Implemented DevOps practices and managed cloud infrastructure',
        projects: 'Automated deployment pipelines and cloud infrastructure',
        education: 'Bachelor of Computer Science'
    );

    // Act
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $scoreMethod = $reflection->getMethod('scoreResume');
    $scoreMethod->setAccessible(true);

    $originalScore = $scoreMethod->invoke($service, $originalResume, $internship);
    $rewrittenScore = $scoreMethod->invoke($service, $rewrittenResume, $internship);

    // Assert: Matching skills should increase or stay the same
    expect(count($rewrittenScore['matching_skills']))
        ->toBeGreaterThanOrEqual(
            count($originalScore['matching_skills']),
            "Rewritten matching skills count (" . count($rewrittenScore['matching_skills']) . 
            ") should be >= original (" . count($originalScore['matching_skills']) . ")"
        );

    // If all required skills are in rewritten, all should be matching
    if (count($requiredSkills) > 0) {
        expect(count($rewrittenScore['matching_skills']))
            ->toBe(count($requiredSkills), "All required skills should be matching in rewritten resume");
    }

})->with(function () {
    return [
        [['Docker', 'Kubernetes', 'Jenkins'], []],
        [['AWS', 'Azure', 'GCP'], ['AWS']],
        [['Terraform', 'Ansible', 'Chef'], ['Terraform', 'Ansible']],
        [['Linux', 'Bash', 'Python'], ['Linux']],
        [['CI/CD', 'Git', 'GitHub Actions'], ['Git']],
    ];
});

/**
 * Helper function to build a synthetic resume text for testing
 */
function buildResumeText(
    string $name,
    array $skills,
    string $experience,
    string $projects,
    string $education
): string {
    $skillsList = !empty($skills) ? implode(', ', $skills) : 'General programming skills';
    
    return <<<RESUME
{$name}
email@example.com | +1234567890 | City, Country

PROFESSIONAL SUMMARY
Motivated software developer with strong technical skills and hands-on experience.

TECHNICAL SKILLS
{$skillsList}

EXPERIENCE
Software Developer | Tech Company | 2022 - Present
• {$experience}
• Collaborated with cross-functional teams
• Delivered high-quality solutions

PROJECTS
Personal Project
• {$projects}
• Utilized modern development practices

EDUCATION
{$education}
University Name | 2020 - 2024
RESUME;
}
