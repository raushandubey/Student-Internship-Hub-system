<?php

namespace App\Listeners;

use App\Events\RecruiterApproved;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * SendRecruiterApprovalEmail Listener
 * 
 * Sends welcome email when a recruiter account is approved.
 * Implements ShouldQueue for async processing.
 */
class SendRecruiterApprovalEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Number of times to retry
     */
    public int $tries = 3;

    /**
     * Retry after X seconds
     */
    public int $backoff = 60;

    public function handle(RecruiterApproved $event): void
    {
        $recruiter = $event->recruiter;

        // Log the email
        Log::channel('email')->info('Recruiter Approval Email', [
            'to' => $recruiter->email,
            'subject' => 'Your Recruiter Account Has Been Approved',
            'body' => "Dear {$recruiter->name}, your recruiter account has been approved! You can now log in and start posting internships. Visit " . url('/login') . " to get started.",
            'user_id' => $recruiter->id,
            'timestamp' => now()->toISOString()
        ]);

        // Store in database for audit
        $emailLog = EmailLog::createIdempotent([
            'user_id' => $recruiter->id,
            'email_type' => 'recruiter_approved',
            'subject' => 'Your Recruiter Account Has Been Approved',
            'recipient' => $recruiter->email,
            'body' => "Your recruiter account has been approved. You can now log in and start posting internships.",
            'status' => 'sent',
            'metadata' => [
                'recruiter_id' => $recruiter->id,
            ],
        ]);

        if ($emailLog->wasRecentlyCreated) {
            Log::info('Recruiter approval email logged', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id
            ]);
        } else {
            Log::info('Duplicate recruiter approval email prevented', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(RecruiterApproved $event, \Throwable $exception): void
    {
        Log::error('Failed to send recruiter approval email', [
            'recruiter_id' => $event->recruiter->id,
            'error' => $exception->getMessage()
        ]);
    }
}
