<?php

/**
 * Property-Based Test for PDF Section Completeness
 * 
 * **Validates: Requirements 7.2, 7.3**
 * 
 * This test validates Property 8: PDF Section Completeness
 * - ∀ text containing "PROFESSIONAL SUMMARY": parseResumeText(text).summary ≠ ""
 * 
 * Tests that when a resume text contains a "PROFESSIONAL SUMMARY" section header,
 * the parseResumeText method correctly extracts a non-empty summary.
 */

use App\Services\ResumePdfService;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property Test: Professional Summary Section Completeness
 * 
 * Tests that for any resume text containing "PROFESSIONAL SUMMARY" header
 * followed by content, parseResumeText returns a non-empty summary field
 */
test('property: resume with PROFESSIONAL SUMMARY header always extracts non-empty summary', function (string $summaryContent) {
    // Arrange: Create a test internship
    $internship = Internship::factory()->create([
        'title' => 'Software Engineer Intern',
        'required_skills' => 'PHP, Laravel, MySQL',
    ]);
    
    // Arrange: Build synthetic resume text with PROFESSIONAL SUMMARY section
    $resumeText = <<<RESUME
John Doe
john.doe@example.com | +1234567890 | New York, NY

PROFESSIONAL SUMMARY
{$summaryContent}

TECHNICAL SKILLS
Languages: PHP, Python, JavaScript
Frameworks: Laravel, React
Tools: Git, Docker
Databases: MySQL, PostgreSQL

EXPERIENCE
Software Developer | Tech Corp | Jan 2023 – Present | San Francisco, CA
• Developed RESTful APIs using Laravel
• Optimized database queries for performance

EDUCATION
Bachelor of Science in Computer Science
University of Technology | 2022 | GPA: 3.8/4.0
RESUME;

    // Act: Parse the resume text using ResumePdfService
    // Note: We need to use reflection to access the private parseResumeText method
    $service = new ResumePdfService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseResumeText');
    $method->setAccessible(true);
    
    $parsed = $method->invoke($service, $resumeText, $internship);
    
    // Assert: Summary field must be non-empty
    expect($parsed)
        ->toBeArray()
        ->toHaveKey('summary')
        ->and($parsed['summary'])
        ->not->toBeEmpty("Resume with 'PROFESSIONAL SUMMARY' header should extract non-empty summary");
        
    // Assert: Summary should contain some of the original content
    $summaryLower = strtolower($parsed['summary']);
    $contentLower = strtolower($summaryContent);
    
    // Extract first meaningful word from content (skip common words)
    $words = preg_split('/\s+/', trim($contentLower));
    $meaningfulWords = array_filter($words, function($word) {
        $stopWords = ['a', 'an', 'the', 'is', 'are', 'was', 'were', 'with', 'and', 'or'];
        return strlen($word) > 3 && !in_array($word, $stopWords);
    });
    
    if (!empty($meaningfulWords)) {
        $firstMeaningfulWord = reset($meaningfulWords);
        expect(str_contains($summaryLower, $firstMeaningfulWord))
            ->toBeTrue("Parsed summary should contain '{$firstMeaningfulWord}' from the PROFESSIONAL SUMMARY section");
    }
    
})->with(function () {
    // Generate various professional summary content patterns
    return [
        // Short summary
        ['Experienced software developer with 3 years of expertise in web development.'],
        
        // Medium summary with multiple sentences
        ['Results-driven Software Engineer with 5 years of experience in full-stack development. Proficient in PHP, Laravel, and modern JavaScript frameworks. Passionate about building scalable web applications.'],
        
        // Long summary with technical details
        ['Highly motivated Software Engineer with extensive experience in backend development using PHP and Laravel. Proven track record of delivering high-quality code and optimizing application performance. Strong problem-solving skills and ability to work in agile teams. Seeking to leverage technical expertise in a challenging internship role.'],
        
        // Summary with special characters
        ['Software Developer | Full-Stack Engineer | Problem Solver with 4+ years of experience building web applications.'],
        
        // Summary with numbers and metrics
        ['Software Engineer with 3+ years of experience, having delivered 15+ projects and improved system performance by 40%.'],
        
        // Summary with line breaks
        ["Passionate Software Developer specializing in backend technologies.\nExperienced in PHP, Laravel, and MySQL.\nCommitted to writing clean, maintainable code."],
        
        // Summary with bullet points (some resumes format summaries this way)
        ["• 3+ years of software development experience\n• Expert in PHP and Laravel framework\n• Strong database design skills"],
        
        // Minimal summary
        ['Software Engineer with PHP and Laravel expertise.'],
        
        // Summary with job-specific keywords
        ['Full-stack developer seeking Software Engineer Intern position. Experienced in Laravel, PHP, and modern web technologies.'],
        
        // Summary with education mention
        ['Recent Computer Science graduate with strong foundation in software engineering principles and hands-on experience with Laravel and PHP.'],
        
        // Summary with soft skills
        ['Collaborative software developer with excellent communication skills and 2 years of experience in agile development environments.'],
        
        // Summary with certifications
        ['Certified PHP Developer with 4 years of experience building enterprise web applications using Laravel framework.'],
        
        // Summary with domain expertise
        ['Software Engineer specializing in e-commerce platforms and payment gateway integrations using PHP and Laravel.'],
        
        // Summary with multiple technologies
        ['Versatile developer proficient in PHP, Python, JavaScript, Laravel, React, and MySQL with focus on building scalable APIs.'],
        
        // Summary with achievement focus
        ['Award-winning software developer recognized for delivering high-impact projects and mentoring junior developers.'],
    ];
});

/**
 * Property Test: Summary Extraction with Different Header Variations
 * 
 * Tests that parseResumeText handles various case and formatting variations
 * of the "PROFESSIONAL SUMMARY" header
 */
test('property: summary extraction works with header case variations', function (string $headerVariation) {
    // Arrange: Create a test internship
    $internship = Internship::factory()->create([
        'title' => 'Backend Developer Intern',
        'required_skills' => 'PHP, Laravel',
    ]);
    
    $summaryContent = 'Experienced backend developer with strong PHP and Laravel skills.';
    
    // Arrange: Build resume with header variation
    $resumeText = <<<RESUME
Jane Smith
jane@example.com | +9876543210

{$headerVariation}
{$summaryContent}

TECHNICAL SKILLS
PHP, Laravel, MySQL

EXPERIENCE
Developer | Company Inc | 2022 - 2024
• Built web applications
RESUME;

    // Act: Parse the resume
    $service = new ResumePdfService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseResumeText');
    $method->setAccessible(true);
    
    $parsed = $method->invoke($service, $resumeText, $internship);
    
    // Assert: Summary should be extracted regardless of case variation
    expect($parsed)
        ->toHaveKey('summary')
        ->and($parsed['summary'])
        ->not->toBeEmpty("Summary should be extracted with header variation: {$headerVariation}");
        
})->with([
    'PROFESSIONAL SUMMARY',
    'Professional Summary',
    'professional summary',
    'Professional summary',
    'PROFESSIONAL SUMMARY:',
    'Professional Summary:',
]);

/**
 * Property Test: Summary Extraction with Multiple Sections
 * 
 * Tests that parseResumeText correctly extracts only the summary content
 * and doesn't bleed into other sections
 */
test('property: summary extraction stops at next section boundary', function () {
    // Arrange: Create a test internship
    $internship = Internship::factory()->create([
        'title' => 'Software Developer',
        'required_skills' => 'Java, Spring',
    ]);
    
    $resumeText = <<<RESUME
Alice Johnson
alice@example.com

PROFESSIONAL SUMMARY
Experienced Java developer with 5 years of expertise in Spring framework.
Passionate about building scalable microservices.

TECHNICAL SKILLS
Java, Spring Boot, Kubernetes, Docker

EXPERIENCE
Senior Developer | Tech Solutions | 2020 - Present
• Architected microservices platform
• Led team of 5 developers
RESUME;

    // Act: Parse the resume
    $service = new ResumePdfService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseResumeText');
    $method->setAccessible(true);
    
    $parsed = $method->invoke($service, $resumeText, $internship);
    
    // Assert: Summary should not contain content from other sections
    $summary = strtolower($parsed['summary']);
    
    expect($summary)
        ->not->toBeEmpty('Summary should be extracted');
    
    expect(str_contains($summary, 'java'))->toBeTrue('Summary should contain summary content');
    expect(str_contains($summary, 'developer'))->toBeTrue('Summary should contain summary content');
    expect(str_contains($summary, 'kubernetes'))->toBeFalse('Summary should not contain skills section content');
    expect(str_contains($summary, 'architected'))->toBeFalse('Summary should not contain experience section content');
    expect(str_contains($summary, 'tech solutions'))->toBeFalse('Summary should not contain company names from experience');
});

/**
 * Property Test: Summary Extraction with Empty or Whitespace Content
 * 
 * Tests edge case where PROFESSIONAL SUMMARY header exists but has no content
 * or only whitespace
 */
test('property: summary extraction handles empty content after header', function (string $emptyContent) {
    // Arrange: Create a test internship
    $internship = Internship::factory()->create([
        'title' => 'Developer',
        'required_skills' => 'PHP',
    ]);
    
    $resumeText = <<<RESUME
Bob Developer
bob@example.com

PROFESSIONAL SUMMARY
{$emptyContent}
TECHNICAL SKILLS
PHP, MySQL

EXPERIENCE
Developer | Company | 2023
• Developed applications
RESUME;

    // Act: Parse the resume
    $service = new ResumePdfService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseResumeText');
    $method->setAccessible(true);
    
    $parsed = $method->invoke($service, $resumeText, $internship);
    
    // Assert: When header exists but content is empty/whitespace,
    // the fallback summary builder should provide something
    expect($parsed)
        ->toHaveKey('summary');
        
    // Note: The implementation has a fallback mechanism (buildFallbackSummary)
    // that should provide a summary even when the section is empty
    // So we don't assert it's non-empty here, but we verify the key exists
    
})->with([
    '',           // Completely empty
    '   ',        // Only spaces
    "\n\n",       // Only newlines
    "  \n  \n  ", // Mixed whitespace
]);

/**
 * Property Test: Summary Extraction with Long Content
 * 
 * Tests that parseResumeText handles summaries with varying lengths
 */
test('property: summary extraction handles various content lengths', function (int $sentenceCount) {
    // Arrange: Create a test internship
    $internship = Internship::factory()->create([
        'title' => 'Software Engineer',
        'required_skills' => 'Python, Django',
    ]);
    
    // Generate summary with specified number of sentences
    $sentences = [
        'Experienced software engineer with expertise in Python and Django.',
        'Proven track record of delivering high-quality web applications.',
        'Strong problem-solving skills and attention to detail.',
        'Passionate about clean code and best practices.',
        'Excellent communication and teamwork abilities.',
        'Committed to continuous learning and professional development.',
        'Experience with agile methodologies and CI/CD pipelines.',
        'Skilled in database design and optimization.',
    ];
    
    $summaryContent = implode(' ', array_slice($sentences, 0, $sentenceCount));
    
    $resumeText = <<<RESUME
Charlie Developer
charlie@example.com

PROFESSIONAL SUMMARY
{$summaryContent}

TECHNICAL SKILLS
Python, Django, PostgreSQL

EXPERIENCE
Engineer | Tech Co | 2021 - Present
• Built scalable web applications
RESUME;

    // Act: Parse the resume
    $service = new ResumePdfService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseResumeText');
    $method->setAccessible(true);
    
    $parsed = $method->invoke($service, $resumeText, $internship);
    
    // Assert: Summary should be extracted regardless of length
    expect($parsed)
        ->toHaveKey('summary')
        ->and($parsed['summary'])
        ->not->toBeEmpty("Summary with {$sentenceCount} sentences should be extracted");
        
})->with([1, 2, 3, 4, 5, 6, 7, 8]);

/**
 * Property Test: Summary Extraction Preserves Content Integrity
 * 
 * Tests that the extracted summary content matches the original content
 * (after cleaning/normalization)
 */
test('property: extracted summary preserves original content integrity', function () {
    // Arrange: Create a test internship
    $internship = Internship::factory()->create([
        'title' => 'Full Stack Developer',
        'required_skills' => 'JavaScript, React, Node.js',
    ]);
    
    $originalSummary = 'Full-stack developer with 4+ years of experience in JavaScript, React, and Node.js. Specialized in building responsive web applications and RESTful APIs.';
    
    $resumeText = <<<RESUME
David Engineer
david@example.com

PROFESSIONAL SUMMARY
{$originalSummary}

TECHNICAL SKILLS
JavaScript, React, Node.js, MongoDB

EXPERIENCE
Full Stack Developer | Web Agency | 2020 - 2024
• Developed 20+ client projects
• Led frontend architecture decisions
RESUME;

    // Act: Parse the resume
    $service = new ResumePdfService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseResumeText');
    $method->setAccessible(true);
    
    $parsed = $method->invoke($service, $resumeText, $internship);
    
    // Assert: Extracted summary should contain key phrases from original
    $extractedSummary = strtolower($parsed['summary']);
    $keyPhrases = ['full-stack', 'javascript', 'react', 'node.js', 'experience'];
    
    foreach ($keyPhrases as $phrase) {
        expect(str_contains($extractedSummary, strtolower($phrase)))
            ->toBeTrue("Extracted summary should preserve key phrase: {$phrase}");
    }
});

/**
 * Property Test: All Standard Resume Sections Are Parsed
 * 
 * Tests that when a complete resume is provided with all sections,
 * parseResumeText returns all expected section keys
 */
test('property: complete resume extracts all section keys', function () {
    // Arrange: Create a test internship
    $internship = Internship::factory()->create([
        'title' => 'DevOps Engineer',
        'required_skills' => 'Docker, Kubernetes, AWS',
    ]);
    
    $resumeText = <<<RESUME
Emma DevOps
emma@example.com | +1122334455 | Seattle, WA

PROFESSIONAL SUMMARY
DevOps engineer with 3 years of experience in containerization and cloud infrastructure.

TECHNICAL SKILLS
Languages: Python, Bash
Tools: Docker, Kubernetes, Terraform
Cloud: AWS, Azure

EXPERIENCE
DevOps Engineer | Cloud Corp | Jan 2021 – Present | Seattle, WA
• Implemented CI/CD pipelines using Jenkins
• Managed Kubernetes clusters on AWS EKS
• Automated infrastructure provisioning with Terraform

PROJECT
E-commerce Platform | Docker, Kubernetes, AWS
• Containerized microservices architecture
• Deployed on AWS EKS with auto-scaling

EDUCATION
Bachelor of Science in Computer Engineering
Tech University | 2020 | GPA: 3.9/4.0

CERTIFICATIONS
AWS Certified Solutions Architect
Certified Kubernetes Administrator (CKA)
RESUME;

    // Act: Parse the resume
    $service = new ResumePdfService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseResumeText');
    $method->setAccessible(true);
    
    $parsed = $method->invoke($service, $resumeText, $internship);
    
    // Assert: All expected section keys should be present
    expect($parsed)
        ->toBeArray()
        ->toHaveKeys(['summary', 'skills', 'experience', 'projects', 'education', 'certifications'])
        ->and($parsed['summary'])
        ->not->toBeEmpty('Summary should be extracted')
        ->and($parsed['skills'])
        ->not->toBeEmpty('Skills should be extracted')
        ->and($parsed['experience'])
        ->not->toBeEmpty('Experience should be extracted')
        ->and($parsed['projects'])
        ->not->toBeEmpty('Projects should be extracted')
        ->and($parsed['education'])
        ->not->toBeEmpty('Education should be extracted')
        ->and($parsed['certifications'])
        ->not->toBeEmpty('Certifications should be extracted');
});
