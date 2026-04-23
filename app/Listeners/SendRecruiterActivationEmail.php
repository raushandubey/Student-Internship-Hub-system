<?php

namespace App\Listeners;

use App\Events\RecruiterActivated;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * SendRecruiterActivationEmail Listener
 * 
 * Sends notification email when a suspended recruiter account is reactivated.
 * Implements ShouldQueue for async processing.
 */
class SendRecruiterActivationEmail implements ShouldQueue
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

    public function handle(RecruiterActivated $event): void
    {
        $recruiter = $event->recruiter;

        // Log the email
        Log::channel('email')->info('Recruiter Activation Email', [
            'to' => $recruiter->email,
            'subject' => 'Your Account Has Been Reactivated',
            'body' => "Dear {$recruiter->name}, your recruiter account has been reactivated. You can now log in and resume posting internships.",
            'user_id' => $recruiter->id,
            'timestamp' => now()->toISOString()
        ]);

        // Store in database for audit
        $emailLog = EmailLog::createIdempotent([
            'user_id' => $recruiter->id,
            'email_type' => 'recruiter_activated',
            'subject' => 'Your Account Has Been Reactivated',
            'recipient' => $recruiter->email,
            'body' => "Your recruiter account has been reactivated. You can now log in and resume posting internships.",
            'status' => 'sent',
            'metadata' => [
                'recruiter_id' => $recruiter->id,
            ],
        ]);

        if ($emailLog->wasRecentlyCreated) {
            Log::info('Recruiter activation email logged', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id
            ]);
        } else {
            Log::info('Duplicate recruiter activation email prevented', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(RecruiterActivated $event, \Throwable $exception): void
    {
        Log::error('Failed to send recruiter activation email', [
            'recruiter_id' => $event->recruiter->id,
            'error' => $exception->getMessage()
        ]);
    }
}
