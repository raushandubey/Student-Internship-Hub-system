<?php

namespace App\Providers;

use App\Events\ApplicationSubmitted;
use App\Events\ApplicationStatusChanged;
use App\Events\RecruiterApproved;
use App\Events\RecruiterRejected;
use App\Events\RecruiterSuspended;
use App\Events\RecruiterActivated;
use App\Events\RecruiterInternshipDeactivated;
use App\Events\RecruiterProfileModified;
use App\Listeners\SendApplicationConfirmation;
use App\Listeners\SendStatusUpdateNotification;
use App\Listeners\SendRecruiterApprovalEmail;
use App\Listeners\SendRecruiterRejectionEmail;
use App\Listeners\SendRecruiterSuspensionEmail;
use App\Listeners\SendRecruiterActivationEmail;
use App\Listeners\SendInternshipDeactivationEmail;
use App\Listeners\SendProfileModificationEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * EventServiceProvider
 * 
 * Registers event-listener mappings.
 * 
 * Event-Driven Architecture Benefits:
 * - Loose coupling between components
 * - Easy to add new side effects
 * - Async processing via queues
 * - Better testability
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Event to listener mappings
     */
    protected $listen = [
        ApplicationSubmitted::class => [
            SendApplicationConfirmation::class,
        ],
        ApplicationStatusChanged::class => [
            SendStatusUpdateNotification::class,
        ],
        RecruiterApproved::class => [
            SendRecruiterApprovalEmail::class,
        ],
        RecruiterRejected::class => [
            SendRecruiterRejectionEmail::class,
        ],
        RecruiterSuspended::class => [
            SendRecruiterSuspensionEmail::class,
        ],
        RecruiterActivated::class => [
            SendRecruiterActivationEmail::class,
        ],
        RecruiterInternshipDeactivated::class => [
            SendInternshipDeactivationEmail::class,
        ],
        RecruiterProfileModified::class => [
            SendProfileModificationEmail::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
