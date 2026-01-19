<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GenerateDailyAdminSummary Job
 * 
 * Generates daily statistics summary for admin review.
 * Logs to file for demonstration purposes.
 * 
 * Idempotency: Running multiple times on same day produces same result.
 */
class GenerateDailyAdminSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Gather statistics
        $stats = [
            'date' => $today,
            'generated_at' => now()->toISOString(),
            'applications' => [
                'total' => Application::count(),
                'today' => Application::whereDate('created_at', $today)->count(),
                'yesterday' => Application::whereDate('created_at', $yesterday)->count(),
                'pending' => Application::where('status', 'pending')->count(),
                'approved_today' => Application::where('status', 'approved')
                    ->whereDate('updated_at', $today)->count(),
                'rejected_today' => Application::where('status', 'rejected')
                    ->whereDate('updated_at', $today)->count(),
            ],
            'internships' => [
                'total' => Internship::count(),
                'active' => Internship::where('is_active', true)->count(),
            ],
            'users' => [
                'total_students' => User::where('role', 'student')->count(),
                'new_today' => User::whereDate('created_at', $today)->count(),
            ],
        ];

        // Log the summary
        Log::channel('daily')->info('Daily Admin Summary', $stats);

        Log::info('Daily admin summary generated', [
            'date' => $today,
            'total_applications' => $stats['applications']['total']
        ]);
    }
}
