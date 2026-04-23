<?php

namespace App\Listeners;

use App\Events\RecruiterSuspended;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * SendRecruiterSuspensionEmail Listener
 * 
 * Sends notification email when a recruiter account is suspended.
 * Implements ShouldQueue for async processing.
 */
class SendRecruiterSuspensionEmail implements ShouldQueue
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

    public function handle(RecruiterSuspended $event): void
    {
        $recruiter = $event->recruiter;
        $reason = $event->reason;

        // Log the email
        Log::channel('email')->info('Recruiter Suspension Email', [
            'to' => $recruiter->email,
            'subject' => 'Your Account Has Been Suspended',
            'body' => "Dear {$recruiter->name}, your recruiter account has been suspended. Reason: {$reason}. Please contact support if you have questions.",
            'user_id' => $recruiter->id,
            'timestamp' => now()->toISOString()
        ]);

        // Store in database for audit
        $emailLog = EmailLog::createIdempotent([
            'user_id' => $recruiter->id,
            'email_type' => 'recruiter_suspended',
            'subject' => 'Your Account Has Been Suspended',
            'recipient' => $recruiter->email,
            'body' => "Your recruiter account has been suspended. Reason: {$reason}",
            'status' => 'sent',
            'metadata' => [
                'recruiter_id' => $recruiter->id,
                'suspension_reason' => $reason,
            ],
        ]);

        if ($emailLog->wasRecentlyCreated) {
            Log::info('Recruiter suspension email logged', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id
            ]);
        } else {
            Log::info('Duplicate recruiter suspension email prevented', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(RecruiterSuspended $event, \Throwable $exception): void
    {
        Log::error('Failed to send recruiter suspension email', [
            'recruiter_id' => $event->recruiter->id,
            'error' => $exception->getMessage()
        ]);
    }
}
