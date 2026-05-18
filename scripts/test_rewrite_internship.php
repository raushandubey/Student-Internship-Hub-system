<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Internship;
use App\Services\ResumeOptimizationService;

$internshipId = (int) ($argv[1] ?? 16);
$user = User::where('role', 'student')->whereHas('profile', fn ($q) => $q->whereNotNull('resume_path'))->with('profile')->first();
$internship = Internship::find($internshipId);

if (!$user || !$internship) {
    fwrite(STDERR, "Missing user or internship {$internshipId}\n");
    exit(1);
}

$start = microtime(true);
$result = app(ResumeOptimizationService::class)->rewriteResume($user, $internship);
$elapsed = round(microtime(true) - $start, 2);

echo json_encode([
    'internship' => $internship->title,
    'elapsed_sec' => $elapsed,
    'success' => $result['success'],
    'stage_failed' => $result['stage_failed'] ?? null,
    'error' => $result['error'] ?? null,
    'quality_gate' => $result['quality_gate'] ?? null,
], JSON_PRETTY_PRINT) . PHP_EOL;

exit($result['success'] ? 0 : 1);
