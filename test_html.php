<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Internship;
use App\Services\ResumeOptimizationService;
use App\Services\ResumePdfService;

$user = User::where('role', 'student')
    ->whereHas('profile', fn($q) => $q->whereNotNull('resume_path'))
    ->with('profile')->first();
$job = Internship::where('is_active', true)->latest()->first();

$result = app(ResumeOptimizationService::class)->rewriteResume($user, $job);
if (!$result['success']) { echo "FAIL: " . $result['error']; exit(1); }

// Now use Reflection to call buildPdfData to get the exact data passed to the view
$pdfService = app(ResumePdfService::class);
$reflection = new ReflectionClass($pdfService);
$method = $reflection->getMethod('buildPdfData');
$method->setAccessible(true);
$data = $method->invoke($pdfService, $user, $job, $result['rewritten_text']);

$html = view('resume.pdf-template', $data)->render();
file_put_contents(__DIR__ . '/public/resume_faang.html', $html);
echo "Wrote HTML to public/resume_faang.html\n";

// Generate PDF
$resp = $pdfService->downloadPdf($user, $job, $result['version_id']);
file_put_contents(__DIR__ . '/public/resume_faang.pdf', $resp->getContent());
echo "Wrote PDF to public/resume_faang.pdf\n";
