<?php

namespace App\Listeners;

use App\Events\RecruiterRejected;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * SendRecruiterRejectionEmail Listener
 * 
 * Sends notification email when a recruiter account is rejected.
 * Implements ShouldQueue for async processing.
 */
class SendRecruiterRejectionEmail implements ShouldQueue
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

    public function handle(RecruiterRejected $event): void
    {
        $recruiter = $event->recruiter;
        $reason = $event->reason;

        // Log the email
        Log::channel('email')->info('Recruiter Rejection Email', [
            'to' => $recruiter->email,
            'subject' => 'Your Recruiter Application Status',
            'body' => "Dear {$recruiter->name}, we regret to inform you that your recruiter application has not been approved. Reason: {$reason}",
            'user_id' => $recruiter->id,
            'timestamp' => now()->toISOString()
        ]);

        // Store in database for audit
        $emailLog = EmailLog::createIdempotent([
            'user_id' => $recruiter->id,
            'email_type' => 'recruiter_rejected',
            'subject' => 'Your Recruiter Application Status',
            'recipient' => $recruiter->email,
            'body' => "Your recruiter application has not been approved. Reason: {$reason}",
            'status' => 'sent',
            'metadata' => [
                'recruiter_id' => $recruiter->id,
                'rejection_reason' => $reason,
            ],
        ]);

        if ($emailLog->wasRecentlyCreated) {
            Log::info('Recruiter rejection email logged', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id
            ]);
        } else {
            Log::info('Duplicate recruiter rejection email prevented', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(RecruiterRejected $event, \Throwable $exception): void
    {
        Log::error('Failed to send recruiter rejection email', [
            'recruiter_id' => $event->recruiter->id,
            'error' => $exception->getMessage()
        ]);
    }
}
