<?php

namespace App\Listeners;

use App\Events\RecruiterInternshipDeactivated;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * SendInternshipDeactivationEmail Listener
 * 
 * Sends notification email when an admin deactivates a recruiter's internship.
 * Implements ShouldQueue for async processing.
 */
class SendInternshipDeactivationEmail implements ShouldQueue
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

    public function handle(RecruiterInternshipDeactivated $event): void
    {
        $internship = $event->internship;
        $recruiter = $event->recruiter;
        $reason = $event->reason;

        // Log the email
        Log::channel('email')->info('Internship Deactivation Email', [
            'to' => $recruiter->email,
            'subject' => 'Your Internship Has Been Deactivated',
            'body' => "Dear {$recruiter->name}, your internship '{$internship->title}' has been deactivated by an administrator. Reason: {$reason}",
            'user_id' => $recruiter->id,
            'internship_id' => $internship->id,
            'timestamp' => now()->toISOString()
        ]);

        // Store in database for audit
        $emailLog = EmailLog::createIdempotent([
            'user_id' => $recruiter->id,
            'email_type' => 'internship_deactivated',
            'subject' => 'Your Internship Has Been Deactivated',
            'recipient' => $recruiter->email,
            'body' => "Your internship '{$internship->title}' has been deactivated. Reason: {$reason}",
            'status' => 'sent',
            'metadata' => [
                'recruiter_id' => $recruiter->id,
                'internship_id' => $internship->id,
                'deactivation_reason' => $reason,
            ],
        ]);

        if ($emailLog->wasRecentlyCreated) {
            Log::info('Internship deactivation email logged', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id,
                'internship_id' => $internship->id
            ]);
        } else {
            Log::info('Duplicate internship deactivation email prevented', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id,
                'internship_id' => $internship->id
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(RecruiterInternshipDeactivated $event, \Throwable $exception): void
    {
        Log::error('Failed to send internship deactivation email', [
            'recruiter_id' => $event->recruiter->id,
            'internship_id' => $event->internship->id,
            'error' => $exception->getMessage()
        ]);
    }
}
