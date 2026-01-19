<?php

namespace App\Listeners;

use App\Events\ApplicationSubmitted;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * SendApplicationConfirmation Listener
 * 
 * Sends confirmation email when application is submitted.
 * Implements ShouldQueue for async processing.
 * 
 * Why Queue?
 * - Email sending is slow (network I/O)
 * - User doesn't need to wait for email to be sent
 * - Retry mechanism for failed emails
 * - Interview: "I used queues to improve response time"
 */
class SendApplicationConfirmation implements ShouldQueue
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

    public function handle(ApplicationSubmitted $event): void
    {
        $application = $event->application;
        $user = $application->user;
        $internship = $application->internship;

        // Log the email (simulated - using log driver)
        Log::channel('email')->info('Application Confirmation Email', [
            'to' => $user->email,
            'subject' => 'Application Submitted Successfully',
            'body' => "Dear {$user->name}, your application for {$internship->title} at {$internship->organization} has been submitted successfully.",
            'application_id' => $application->id,
            'timestamp' => now()->toISOString()
        ]);

        // Store in database for audit (with idempotency protection)
        $emailLog = EmailLog::createIdempotent([
            'user_id' => $user->id,
            'email_type' => 'application_submitted',
            'subject' => 'Application Submitted Successfully',
            'recipient' => $user->email,
            'body' => "Your application for {$internship->title} has been submitted.",
            'status' => 'sent',
            'metadata' => [
                'application_id' => $application->id,
                'internship_id' => $internship->id,
            ],
        ]);

        if ($emailLog->wasRecentlyCreated) {
            Log::info('Application confirmation email logged', [
                'email_log_id' => $emailLog->id,
                'application_id' => $application->id,
                'user_id' => $user->id
            ]);
        } else {
            Log::info('Duplicate application confirmation prevented', [
                'email_log_id' => $emailLog->id,
                'application_id' => $application->id,
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(ApplicationSubmitted $event, \Throwable $exception): void
    {
        Log::error('Failed to send application confirmation', [
            'application_id' => $event->application->id,
            'error' => $exception->getMessage()
        ]);
    }
}
