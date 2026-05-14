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

echo $result['rewritten_text'];
