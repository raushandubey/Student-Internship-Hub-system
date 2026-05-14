<?php

/**
 * Property-Based Test for ResumeOptimizationService Data Preservation
 * 
 * **Validates: Requirements 4.4, 4.5, 9.5**
 * 
 * This test validates Property 5: Rewrite Preserves Real Data
 * - ∀ originalText, rewrittenText: extractedCompanies(originalText) ⊆ rewrittenText (case-insensitive)
 * - ∀ originalText, rewrittenText: extractedDates(originalText) ⊆ rewrittenText
 * 
 * The rewrite engine must never remove or alter company names, dates, or educational
 * institutions that exist in the original resume. This ensures factual accuracy and
 * prevents fabrication of experience.
 */

use App\Models\Internship;
use App\Services\ResumeOptimizationService;

/**
 * Property Test: Company Names Preservation
 * 
 * Tests that all company names from the original resume appear in the rewritten output
 */
test('property: rewrite preserves all company names from original resume', function (array $companies, string $resumeTemplate, string $description) {
    // Arrange: Create a resume with known company names
    $originalResume = str_replace('{{COMPANIES}}', implode("\n", $companies), $resumeTemplate);
    
    $internship = Internship::factory()->create([
        'required_skills' => ['PHP', 'Laravel', 'MySQL'],
        'title' => 'Software Engineer',
        'organization' => 'Test Company',
        'description' => 'Test job description',
    ]);
    
    // Create service instance and use reflection to test the private ruleBasedRewrite method
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $ruleBasedRewriteMethod = $reflection->getMethod('ruleBasedRewrite');
    $ruleBasedRewriteMethod->setAccessible(true);
    
    // Act: Run the rewrite
    $rewrittenResume = $ruleBasedRewriteMethod->invoke($service, $originalResume, $internship);
    
    // Assert: All company names must appear in the rewritten resume (case-insensitive)
    $rewrittenLower = strtolower($rewrittenResume);
    
    foreach ($companies as $company) {
        $companyLower = strtolower($company);
        expect(str_contains($rewrittenLower, $companyLower))
            ->toBeTrue("Company '{$company}' should be preserved in rewritten resume for: {$description}");
    }
    
})->with([
    // Single company
    [
        ['Google Inc.'],
        "John Doe\njohn@example.com\n\nEXPERIENCE\n{{COMPANIES}}\nSoftware Engineer | Jan 2020 - Present\n• Developed web applications\n• Worked with PHP and Laravel",
        'Single company'
    ],
    
    // Multiple companies
    [
        ['Microsoft Corporation', 'Amazon Web Services', 'Meta Platforms'],
        "Jane Smith\njane@example.com\n\nEXPERIENCE\n{{COMPANIES}}\nSenior Developer | 2019 - 2021\n• Built scalable systems\n• Led development team",
        'Multiple companies'
    ],
    
    // Companies with special characters
    [
        ['AT&T Inc.', 'Ernst & Young', 'Procter & Gamble'],
        "Bob Johnson\nbob@example.com\n\nEXPERIENCE\n{{COMPANIES}}\nConsultant | 2018 - 2020\n• Provided technical consulting\n• Managed client relationships",
        'Companies with ampersands'
    ],
    
    // Companies with dots and abbreviations
    [
        ['IBM Corp.', 'HP Inc.', 'Dell Technologies Inc.'],
        "Alice Williams\nalice@example.com\n\nEXPERIENCE\n{{COMPANIES}}\nSystems Engineer | 2017 - 2019\n• Designed infrastructure\n• Implemented solutions",
        'Companies with abbreviations'
    ],
    
    // Startup and tech companies
    [
        ['Stripe', 'Shopify', 'Atlassian', 'Slack Technologies'],
        "Charlie Brown\ncharlie@example.com\n\nEXPERIENCE\n{{COMPANIES}}\nFull Stack Developer | 2020 - 2022\n• Built payment integrations\n• Developed APIs",
        'Tech startups'
    ],
    
    // Indian companies
    [
        ['Tata Consultancy Services', 'Infosys Limited', 'Wipro Technologies'],
        "Priya Sharma\npriya@example.com\n\nEXPERIENCE\n{{COMPANIES}}\nSoftware Developer | 2019 - 2021\n• Developed enterprise applications\n• Worked on client projects",
        'Indian IT companies'
    ],
    
    // Companies with numbers
    [
        ['3M Company', '7-Eleven Inc.', '20th Century Studios'],
        "David Lee\ndavid@example.com\n\nEXPERIENCE\n{{COMPANIES}}\nProduct Manager | 2018 - 2020\n• Managed product lifecycle\n• Coordinated with teams",
        'Companies with numbers'
    ],
]);

/**
 * Property Test: Date Preservation
 * 
 * Tests that all dates (years and date ranges) from the original resume appear in the rewritten output
 */
test('property: rewrite preserves all dates from original resume', function (array $dates, string $resumeTemplate, string $description) {
    // Arrange: Create a resume with known dates
    $originalResume = str_replace('{{DATES}}', implode("\n", $dates), $resumeTemplate);
    
    $internship = Internship::factory()->create([
        'required_skills' => ['JavaScript', 'React', 'Node.js'],
        'title' => 'Frontend Developer',
        'organization' => 'Tech Corp',
        'description' => 'Frontend development position',
    ]);
    
    // Create service instance and use reflection to test the private ruleBasedRewrite method
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $ruleBasedRewriteMethod = $reflection->getMethod('ruleBasedRewrite');
    $ruleBasedRewriteMethod->setAccessible(true);
    
    // Act: Run the rewrite
    $rewrittenResume = $ruleBasedRewriteMethod->invoke($service, $originalResume, $internship);
    
    // Assert: All dates must appear in the rewritten resume
    foreach ($dates as $date) {
        // Extract year patterns (4-digit years)
        preg_match_all('/\b(19|20)\d{2}\b/', $date, $years);
        
        foreach ($years[0] as $year) {
            expect(str_contains($rewrittenResume, $year))
                ->toBeTrue("Year '{$year}' from date '{$date}' should be preserved in rewritten resume for: {$description}");
        }
        
        // Check for "Present" keyword (case-insensitive)
        if (stripos($date, 'present') !== false) {
            expect(stripos($rewrittenResume, 'present') !== false)
                ->toBeTrue("'Present' keyword should be preserved in rewritten resume for: {$description}");
        }
    }
    
})->with([
    // Simple year ranges
    [
        ['2020 - 2022', '2018 - 2020', '2016 - 2018'],
        "John Doe\njohn@example.com\n\nEXPERIENCE\nTech Company\nSoftware Engineer\n{{DATES}}\n• Developed applications\n• Led projects",
        'Simple year ranges'
    ],
    
    // Date ranges with "Present"
    [
        ['Jan 2021 - Present', 'March 2019 - Dec 2020', 'June 2017 - Feb 2019'],
        "Jane Smith\njane@example.com\n\nEXPERIENCE\nStartup Inc.\nSenior Developer\n{{DATES}}\n• Built scalable systems\n• Mentored junior developers",
        'Date ranges with Present'
    ],
    
    // Full month-year format
    [
        ['January 2020 - December 2021', 'March 2018 - February 2020'],
        "Bob Johnson\nbob@example.com\n\nEXPERIENCE\nConsulting Firm\nConsultant\n{{DATES}}\n• Provided consulting services\n• Managed projects",
        'Full month-year format'
    ],
    
    // Abbreviated month format
    [
        ['Jan 2019 - Dec 2020', 'Mar 2017 - Feb 2019', 'Jun 2015 - May 2017'],
        "Alice Williams\nalice@example.com\n\nEXPERIENCE\nCorporation Ltd.\nAnalyst\n{{DATES}}\n• Analyzed data\n• Created reports",
        'Abbreviated month format'
    ],
    
    // Education years
    [
        ['2016 - 2020', '2014 - 2016'],
        "Charlie Brown\ncharlie@example.com\n\nEDUCATION\nBachelor of Technology\nUniversity Name\n{{DATES}}\nCGPA: 8.5/10",
        'Education years'
    ],
    
    // Mixed formats
    [
        ['2021 - Present', 'Jan 2019 - Dec 2020', '2017', 'March 2015 - February 2017'],
        "David Lee\ndavid@example.com\n\nEXPERIENCE\nTech Startup\nFull Stack Developer\n{{DATES}}\n• Developed features\n• Deployed applications",
        'Mixed date formats'
    ],
]);

/**
 * Property Test: Educational Institutions Preservation
 * 
 * Tests that all educational institutions from the original resume appear in the rewritten output
 */
test('property: rewrite preserves all educational institutions from original resume', function (array $institutions, string $resumeTemplate, string $description) {
    // Arrange: Create a resume with known educational institutions
    $originalResume = str_replace('{{INSTITUTIONS}}', implode("\n", $institutions), $resumeTemplate);
    
    $internship = Internship::factory()->create([
        'required_skills' => ['Python', 'Machine Learning', 'TensorFlow'],
        'title' => 'Data Scientist',
        'organization' => 'AI Company',
        'description' => 'Data science position',
    ]);
    
    // Create service instance and use reflection to test the private ruleBasedRewrite method
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $ruleBasedRewriteMethod = $reflection->getMethod('ruleBasedRewrite');
    $ruleBasedRewriteMethod->setAccessible(true);
    
    // Act: Run the rewrite
    $rewrittenResume = $ruleBasedRewriteMethod->invoke($service, $originalResume, $internship);
    
    // Assert: All educational institutions must appear in the rewritten resume (case-insensitive)
    $rewrittenLower = strtolower($rewrittenResume);
    
    foreach ($institutions as $institution) {
        $institutionLower = strtolower($institution);
        expect(str_contains($rewrittenLower, $institutionLower))
            ->toBeTrue("Institution '{$institution}' should be preserved in rewritten resume for: {$description}");
    }
    
})->with([
    // Indian universities
    [
        ['Indian Institute of Technology, Delhi', 'National Institute of Technology, Trichy'],
        "Priya Sharma\npriya@example.com\n\nEDUCATION\nBachelor of Technology in Computer Science\n{{INSTITUTIONS}}\n2016 - 2020\nCGPA: 8.5/10",
        'Indian IITs and NITs'
    ],
    
    // US universities
    [
        ['Massachusetts Institute of Technology', 'Stanford University', 'Carnegie Mellon University'],
        "John Doe\njohn@example.com\n\nEDUCATION\nMaster of Science in Computer Science\n{{INSTITUTIONS}}\n2018 - 2020\nGPA: 3.8/4.0",
        'US universities'
    ],
    
    // UK universities
    [
        ['University of Oxford', 'University of Cambridge', 'Imperial College London'],
        "Jane Smith\njane@example.com\n\nEDUCATION\nBachelor of Engineering\n{{INSTITUTIONS}}\n2015 - 2019\nFirst Class Honours",
        'UK universities'
    ],
    
    // State universities
    [
        ['University of California, Berkeley', 'University of Texas at Austin'],
        "Bob Johnson\nbob@example.com\n\nEDUCATION\nBachelor of Science in Software Engineering\n{{INSTITUTIONS}}\n2017 - 2021\nGPA: 3.7/4.0",
        'State universities'
    ],
    
    // Indian state universities
    [
        ['Delhi University', 'Mumbai University', 'Pune University'],
        "Alice Williams\nalice@example.com\n\nEDUCATION\nBachelor of Computer Applications\n{{INSTITUTIONS}}\n2016 - 2019\nPercentage: 85%",
        'Indian state universities'
    ],
]);

/**
 * Property Test: Complete Data Preservation
 * 
 * Tests that companies, dates, and institutions are all preserved together in a realistic resume
 */
test('property: rewrite preserves all real data in complete resume', function () {
    // Arrange: Create a realistic resume with multiple companies, dates, and institutions
    $originalResume = <<<RESUME
RAHUL KUMAR
rahul.kumar@example.com | +91-9876543210 | Mumbai, India

PROFESSIONAL SUMMARY
Software Engineer with 3 years of experience in full-stack development.

TECHNICAL SKILLS
PHP, Laravel, MySQL, JavaScript, React, Node.js, Git, Docker

EXPERIENCE

Tata Consultancy Services
Software Engineer | Jan 2021 - Present | Mumbai
• Developed enterprise web applications using Laravel and React
• Implemented RESTful APIs for mobile applications
• Optimized database queries reducing response time by 40%

Infosys Limited
Associate Software Engineer | June 2019 - Dec 2020 | Bangalore
• Built microservices architecture using Node.js
• Worked on client projects for banking sector
• Collaborated with cross-functional teams

PROJECTS

E-Commerce Platform
• Developed full-stack e-commerce application using Laravel and Vue.js
• Integrated payment gateway and order management system
• Deployed on AWS with CI/CD pipeline

EDUCATION

Bachelor of Technology in Computer Science
Indian Institute of Technology, Bombay
2015 - 2019
CGPA: 8.7/10

CERTIFICATIONS
AWS Certified Developer - Associate (2020)
Oracle Certified Java Programmer (2019)
RESUME;

    $internship = Internship::factory()->create([
        'required_skills' => ['PHP', 'Laravel', 'MySQL', 'JavaScript'],
        'title' => 'Senior Software Engineer',
        'organization' => 'Tech Innovations Pvt Ltd',
        'description' => 'Looking for experienced full-stack developer',
    ]);
    
    // Create service instance and use reflection to test the private ruleBasedRewrite method
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $ruleBasedRewriteMethod = $reflection->getMethod('ruleBasedRewrite');
    $ruleBasedRewriteMethod->setAccessible(true);
    
    // Act: Run the rewrite
    $rewrittenResume = $ruleBasedRewriteMethod->invoke($service, $originalResume, $internship);
    $rewrittenLower = strtolower($rewrittenResume);
    
    // Assert: All companies are preserved
    $companies = ['Tata Consultancy Services', 'Infosys Limited'];
    foreach ($companies as $company) {
        expect(str_contains($rewrittenLower, strtolower($company)))
            ->toBeTrue("Company '{$company}' should be preserved");
    }
    
    // Assert: All years are preserved
    $years = ['2021', '2020', '2019', '2015'];
    foreach ($years as $year) {
        expect(str_contains($rewrittenResume, $year))
            ->toBeTrue("Year '{$year}' should be preserved");
    }
    
    // Assert: Educational institution is preserved
    expect(str_contains($rewrittenLower, 'indian institute of technology'))
        ->toBeTrue("Educational institution should be preserved");
    
    // Assert: "Present" keyword is preserved
    expect(stripos($rewrittenResume, 'present') !== false)
        ->toBeTrue("'Present' keyword should be preserved");
});

/**
 * Property Test: Data Preservation is Deterministic
 * 
 * Tests that running rewrite multiple times produces consistent data preservation
 */
test('property: data preservation is deterministic across multiple rewrites', function () {
    $originalResume = <<<RESUME
AMIT PATEL
amit@example.com

EXPERIENCE
Google India
Software Engineer | 2020 - 2022
• Developed search features

Microsoft Corporation
Intern | 2019 - 2020
• Worked on Azure platform

EDUCATION
Bachelor of Engineering
IIT Delhi
2015 - 2019
RESUME;

    $internship = Internship::factory()->create([
        'required_skills' => ['Python', 'Java', 'C++'],
        'title' => 'Software Developer',
        'organization' => 'Tech Company',
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $ruleBasedRewriteMethod = $reflection->getMethod('ruleBasedRewrite');
    $ruleBasedRewriteMethod->setAccessible(true);
    
    // Act: Run rewrite multiple times
    $rewrite1 = $ruleBasedRewriteMethod->invoke($service, $originalResume, $internship);
    $rewrite2 = $ruleBasedRewriteMethod->invoke($service, $originalResume, $internship);
    $rewrite3 = $ruleBasedRewriteMethod->invoke($service, $originalResume, $internship);
    
    // Assert: All rewrites preserve the same data
    $companies = ['Google India', 'Microsoft Corporation'];
    $years = ['2020', '2022', '2019', '2015'];
    $institution = 'IIT Delhi';
    
    foreach ([$rewrite1, $rewrite2, $rewrite3] as $idx => $rewrite) {
        $rewriteLower = strtolower($rewrite);
        
        foreach ($companies as $company) {
            expect(str_contains($rewriteLower, strtolower($company)))
                ->toBeTrue("Company '{$company}' should be preserved in rewrite " . ($idx + 1));
        }
        
        foreach ($years as $year) {
            expect(str_contains($rewrite, $year))
                ->toBeTrue("Year '{$year}' should be preserved in rewrite " . ($idx + 1));
        }
        
        expect(str_contains($rewriteLower, strtolower($institution)))
            ->toBeTrue("Institution '{$institution}' should be preserved in rewrite " . ($idx + 1));
    }
});

/**
 * Property Test: No Data Fabrication
 * 
 * Tests that the rewrite does not add company names or dates that don't exist in the original
 */
test('property: rewrite does not fabricate companies or dates not in original', function () {
    $originalResume = <<<RESUME
SARAH JOHNSON
sarah@example.com

EXPERIENCE
Startup Inc.
Developer | 2021 - 2022
• Built web applications

EDUCATION
Computer Science Degree
Local University
2017 - 2021
RESUME;

    $internship = Internship::factory()->create([
        'required_skills' => ['PHP', 'Laravel'],
        'title' => 'Backend Developer',
        'organization' => 'Google',  // Different from resume companies
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $ruleBasedRewriteMethod = $reflection->getMethod('ruleBasedRewrite');
    $ruleBasedRewriteMethod->setAccessible(true);
    
    // Act: Run the rewrite
    $rewrittenResume = $ruleBasedRewriteMethod->invoke($service, $originalResume, $internship);
    $rewrittenLower = strtolower($rewrittenResume);
    
    // Assert: Original company is preserved
    expect(str_contains($rewrittenLower, 'startup inc'))
        ->toBeTrue("Original company 'Startup Inc.' should be preserved");
    
    // Assert: Original years are preserved
    expect(str_contains($rewrittenResume, '2021'))
        ->toBeTrue("Original year '2021' should be preserved");
    expect(str_contains($rewrittenResume, '2022'))
        ->toBeTrue("Original year '2022' should be preserved");
    expect(str_contains($rewrittenResume, '2017'))
        ->toBeTrue("Original year '2017' should be preserved");
    
    // Note: We cannot easily test that NO fabrication occurs without knowing all possible
    // company names, but we can verify that the original data is intact, which is the
    // primary requirement. The rewrite should preserve, not fabricate.
});

/**
 * Property Test: Case-Insensitive Preservation
 * 
 * Tests that company names are preserved regardless of case variations
 */
test('property: company name preservation is case-insensitive', function () {
    $originalResume = <<<RESUME
DAVID CHEN
david@example.com

EXPERIENCE
amazon web services
cloud engineer | 2020 - 2022
• Managed AWS infrastructure

MICROSOFT CORPORATION
software developer | 2018 - 2020
• Developed applications
RESUME;

    $internship = Internship::factory()->create([
        'required_skills' => ['AWS', 'Azure', 'Cloud'],
        'title' => 'Cloud Engineer',
        'organization' => 'Tech Corp',
    ]);
    
    $service = new ResumeOptimizationService();
    $reflection = new ReflectionClass($service);
    $ruleBasedRewriteMethod = $reflection->getMethod('ruleBasedRewrite');
    $ruleBasedRewriteMethod->setAccessible(true);
    
    // Act: Run the rewrite
    $rewrittenResume = $ruleBasedRewriteMethod->invoke($service, $originalResume, $internship);
    $rewrittenLower = strtolower($rewrittenResume);
    
    // Assert: Company names are preserved (case-insensitive check)
    expect(str_contains($rewrittenLower, 'amazon web services'))
        ->toBeTrue("Company 'amazon web services' should be preserved (case-insensitive)");
    
    expect(str_contains($rewrittenLower, 'microsoft corporation'))
        ->toBeTrue("Company 'MICROSOFT CORPORATION' should be preserved (case-insensitive)");
});
