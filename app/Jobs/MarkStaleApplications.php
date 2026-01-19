<?php

namespace App\Jobs;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Services\ApplicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * MarkStaleApplications Job
 * 
 * Automatically marks applications as "under_review" if they've been
 * pending for more than 7 days.
 * 
 * Why Background Jobs?
 * - Automated maintenance without manual intervention
 * - Runs during off-peak hours
 * - Idempotent: safe to run multiple times
 * - Interview: "I implemented scheduled jobs for automated workflows"
 */
class MarkStaleApplications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(ApplicationService $applicationService): void
    {
        $staleDays = 7;
        $cutoffDate = now()->subDays($staleDays);

        // Find pending applications older than cutoff
        $staleApplications = Application::where('status', ApplicationStatus::PENDING)
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($staleApplications as $application) {
            try {
                $result = $applicationService->updateStatus(
                    $application,
                    ApplicationStatus::UNDER_REVIEW,
                    0, // System user
                    'system',
                    'Auto-moved to review after ' . $staleDays . ' days'
                );

                if ($result['success']) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to mark stale application', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Stale applications job completed', [
            'total_found' => $staleApplications->count(),
            'processed' => $processed,
            'failed' => $failed
        ]);
    }
}
