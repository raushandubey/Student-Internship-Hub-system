<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Resume\LatexTemplateEngine;
use Illuminate\Support\Facades\Log;

$engine = new LatexTemplateEngine();

$data = [
    'name' => 'John Richardson',
    'email' => 'john.richardson@email.com',
    'phone' => '(+44) 07912 345678',
    'location' => 'Manchester, UK',
    'links' => [
        'https://linkedin.com/in/johnrichardson',
        'https://github.com/jrichardson'
    ],
    'sections' => [
        'summary' => 'Software Developer | BSc Computer Science (First Class Honours)',
        'experience' => [
            [
                'title' => 'Software Engineer',
                'date' => 'Jan 2023 -- Present',
                'org' => 'TechFlow Solutions Ltd',
                'location' => 'Manchester, UK',
                'bullets' => [
                    'Developed full-stack web applications using React, Node.js, and PostgreSQL for enterprise clients.',
                    'Collaborated with cross-functional teams to deliver features aligned with business requirements.',
                    'Implemented CI/CD pipelines reducing deployment time by 40%.',
                    'Mentored junior developers and conducted code reviews to maintain code quality standards.'
                ]
            ],
            [
                'title' => 'Junior Developer',
                'date' => 'June 2021 -- Dec 2022',
                'org' => 'Digital Innovations Co',
                'location' => 'Leeds, UK',
                'bullets' => [
                    'Built RESTful APIs and microservices supporting e-commerce platforms.',
                    'Participated in agile development processes including sprint planning and retrospectives.',
                    'Optimized database queries improving application response times by 25%.',
                    'Contributed to technical documentation and user guides for internal tools.'
                ]
            ]
        ],
        'education' => [
            [
                'school' => 'University of Manchester',
                'date' => '2018 -- 2022',
                'degree' => 'BSc Computer Science, First Class Honours',
                'location' => 'Manchester, UK',
                'bullets' => [
                    'Final Project: Machine learning system for sentiment analysis of social media data using Python and TensorFlow, achieving 89% accuracy.',
                    'Recipient of Dean\'s List Award for academic excellence in 2020 and 2021.',
                    'Key Modules: Algorithms and Data Structures (85%), Database Systems (92%), Web Technologies (88%), Artificial Intelligence (90%).'
                ]
            ],
            [
                'school' => 'Secondary Education',
                'date' => '2011 -- 2018',
                'degree' => '',
                'location' => 'Leeds, UK',
                'bullets' => [
                    'A-Levels: Computer Science (A*), Mathematics (A), Physics (A).',
                    'GCSEs: 10 subjects including Mathematics (9), English Language (8), Computer Science (A*).'
                ]
            ]
        ],
        'skills' => [
            'Programming Languages' => 'JavaScript, Python, Java, TypeScript, SQL',
            'Frameworks & Libraries' => 'React, Node.js, Express, Django, Spring Boot',
            'Tools & Technologies' => 'Git, Docker, AWS, Jenkins, PostgreSQL, MongoDB'
        ],
        'certifications' => [
            'AWS Certified Solutions Architect -- Associate (2023)',
            'Professional Scrum Master I (PSM I) -- Scrum.org (2022)',
            'MongoDB Certified Developer -- MongoDB University (2021)'
        ]
    ]
];

$method = (new ReflectionClass($engine))->getMethod('buildTexContent');
$method->setAccessible(true);
$tex = $method->invokeArgs($engine, [$data]);
echo "Generated TeX:\n" . $tex . "\n\n";

$openBraces = substr_count($tex, '{');
$closeBraces = substr_count($tex, '}');
echo "Braces: Open ($openBraces), Close ($closeBraces)\n";

$openEnvs = substr_count($tex, '\begin{');
$closeEnvs = substr_count($tex, '\end{');
echo "Envs: Open ($openEnvs), Close ($closeEnvs)\n";

echo "Has begin{document}: " . (str_contains($tex, '\begin{document}') ? 'Yes' : 'No') . "\n";
echo "Has end{document}: " . (str_contains($tex, '\end{document}') ? 'Yes' : 'No') . "\n";

$pdfBinary = $engine->generatePdf($data);

if ($pdfBinary) {
    file_put_contents('test_resume.pdf', $pdfBinary);
    echo "Successfully generated PDF: test_resume.pdf (" . strlen($pdfBinary) . " bytes)\n";
} else {
    echo "Failed to generate PDF.\n";
}
