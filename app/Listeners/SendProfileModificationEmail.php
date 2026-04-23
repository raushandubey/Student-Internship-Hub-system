<?php

namespace App\Listeners;

use App\Events\RecruiterProfileModified;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * SendProfileModificationEmail Listener
 * 
 * Sends notification email when an admin modifies a recruiter's profile.
 * Implements ShouldQueue for async processing.
 */
class SendProfileModificationEmail implements ShouldQueue
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

    public function handle(RecruiterProfileModified $event): void
    {
        $recruiter = $event->recruiter;
        $admin = $event->admin;

        // Log the email
        Log::channel('email')->info('Profile Modification Email', [
            'to' => $recruiter->email,
            'subject' => 'Your Profile Has Been Updated',
            'body' => "Dear {$recruiter->name}, your recruiter profile has been updated by an administrator. Please review your profile to see the changes.",
            'user_id' => $recruiter->id,
            'admin_id' => $admin->id,
            'timestamp' => now()->toISOString()
        ]);

        // Store in database for audit
        $emailLog = EmailLog::createIdempotent([
            'user_id' => $recruiter->id,
            'email_type' => 'profile_modified',
            'subject' => 'Your Profile Has Been Updated',
            'recipient' => $recruiter->email,
            'body' => "Your recruiter profile has been updated by an administrator. Please review your profile to see the changes.",
            'status' => 'sent',
            'metadata' => [
                'recruiter_id' => $recruiter->id,
                'admin_id' => $admin->id,
            ],
        ]);

        if ($emailLog->wasRecentlyCreated) {
            Log::info('Profile modification email logged', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id,
                'admin_id' => $admin->id
            ]);
        } else {
            Log::info('Duplicate profile modification email prevented', [
                'email_log_id' => $emailLog->id,
                'recruiter_id' => $recruiter->id,
                'admin_id' => $admin->id
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(RecruiterProfileModified $event, \Throwable $exception): void
    {
        Log::error('Failed to send profile modification email', [
            'recruiter_id' => $event->recruiter->id,
            'admin_id' => $event->admin->id,
            'error' => $exception->getMessage()
        ]);
    }
}
