<?php

namespace App\Listeners;

use App\Events\ApplicationStatusChanged;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * SendStatusUpdateNotification Listener
 * 
 * Sends notification when application status changes.
 */
class SendStatusUpdateNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;

    public function handle(ApplicationStatusChanged $event): void
    {
        $application = $event->application;
        $user = $application->user;
        $internship = $application->internship;

        $subject = "Application Status Updated: {$event->newStatus->label()}";
        $body = "Dear {$user->name}, your application for {$internship->title} at {$internship->organization} has been updated from {$event->oldStatus->label()} to {$event->newStatus->label()}.";

        // Log the email
        Log::channel('email')->info('Status Update Email', [
            'to' => $user->email,
            'subject' => $subject,
            'body' => $body,
            'application_id' => $application->id,
            'old_status' => $event->oldStatus->value,
            'new_status' => $event->newStatus->value,
            'timestamp' => now()->toISOString()
        ]);

        // Store in database (with idempotency protection)
        $emailLog = EmailLog::createIdempotent([
            'user_id' => $user->id,
            'email_type' => 'status_updated',
            'subject' => $subject,
            'recipient' => $user->email,
            'body' => $body,
            'status' => 'sent',
            'metadata' => [
                'application_id' => $application->id,
                'old_status' => $event->oldStatus->value,
                'new_status' => $event->newStatus->value,
            ],
        ]);

        if ($emailLog->wasRecentlyCreated) {
            Log::info('Status update notification logged', [
                'email_log_id' => $emailLog->id,
                'application_id' => $application->id,
                'new_status' => $event->newStatus->value
            ]);
        } else {
            Log::info('Duplicate status update notification prevented', [
                'email_log_id' => $emailLog->id,
                'application_id' => $application->id,
                'new_status' => $event->newStatus->value
            ]);
        }
    }

    public function failed(ApplicationStatusChanged $event, \Throwable $exception): void
    {
        Log::error('Failed to send status update notification', [
            'application_id' => $event->application->id,
            'error' => $exception->getMessage()
        ]);
    }
}
