<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Internship;
use App\Services\ResumePdfService;

$user = User::where('role', 'student')->whereHas('profile', fn($q) => $q->whereNotNull('resume_path'))->first();
$job = Internship::where('is_active', true)->latest()->first();

// This is exactly what the AI will output when your OPENAI_API_KEY is configured.
$aiRewrittenText = <<<EOF
SUDHANSHU SINGH
+91 7903391118 | sudhanshusingh39477@gmail.com | Bangalore

PROFESSIONAL SUMMARY
Results-driven Full Stack Developer seeking the Full Stack Developer Intern position at TechCorp Solutions. Proficient in modern web technologies including React, Node.js, and MongoDB. Demonstrated ability to build scalable architectures and deliver measurable impact through agile development workflows.

TECHNICAL SKILLS
Languages: JavaScript, TypeScript, Python, C++, Java, PHP, HTML/CSS | Frameworks: React, Node.js, Express.js, Laravel, Tailwind | Tools: Git, GitHub | Databases: MongoDB, MySQL

EXPERIENCE

Vizva Consultancy Services | Technical Support Engineer | Aug 2025 – Present | Remote
• Diagnosed and resolved complex software and connectivity issues within tight SLAs, utilizing Python scripts to automate root-cause analysis.
• Executed L1/L2 technical support workflows for US clients, achieving high resolution rates through rigorous object-oriented log analysis.
• Managed support tickets using Git-versioned diagnostic tools, escalating highly technical issues with comprehensive architectural documentation.

PROJECTS

Student Internship Hub System | Laravel, PHP 8.2, MySQL, Tailwind CSS
Full-stack hiring platform with AI integration and state-machine workflows.
• Engineered a service-layer architecture and state-machine pipeline to optimize the end-to-end recruitment process with secure audit logging.
• Developed a personalized AI chatbot to analyze user profiles and deliver intelligent career guidance, significantly boosting user engagement.

Adv Weather App | Flutter, Dart, C++, Swift
Cross-platform mobile application providing real-time forecasting.
• Implemented asynchronous API integrations to deliver real-time global weather data, ensuring robust error handling and zero downtime.
• Designed location-based forecasting modules using GPS and object-oriented architecture to instantly fetch hyper-local climate reports.

HealthCare Scheduler | MongoDB, MySQL, Git
Dual-database scheduling platform designed for medical staff and patients.
• Integrated real-time scheduling and automated notifications leveraging dual database synchronization across MongoDB and MySQL.
• Optimized booking efficiency and reduced manual data entry errors by implementing dynamic scheduling updates within an Agile/Scrum environment.

EDUCATION

Gopal Narayan Singh University | B.S. Computer Science Engineering | 2022 - Present
Coursework: Data Structures & Algorithms, Object-Oriented Programming, Database Management, Compiler Design

Bihar School Examination Board | 12th Grade (PCM) | 2020 - 2022
EOF;

$pdfService = app(ResumePdfService::class);
$reflection = new ReflectionClass($pdfService);
$method = $reflection->getMethod('buildPdfData');
$method->setAccessible(true);

$data = $method->invoke($pdfService, $user, $job, $aiRewrittenText);

$html = view('resume.pdf-template', $data)->render();
file_put_contents(__DIR__ . '/public/resume_faang_mock.html', $html);

// We need to bypass the database version check for this mock test
$pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('resume.pdf-template', $data)
    ->setPaper('a4', 'portrait')
    ->setWarnings(false);

file_put_contents(__DIR__ . '/public/resume_faang_mock.pdf', $pdf->output());

echo "MOCK AI PDF GENERATED AT: public/resume_faang_mock.pdf\n";
