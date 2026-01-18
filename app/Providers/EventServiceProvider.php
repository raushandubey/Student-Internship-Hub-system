<?php

namespace App\Providers;

use App\Events\ApplicationSubmitted;
use App\Events\ApplicationStatusChanged;
use App\Listeners\SendApplicationConfirmation;
use App\Listeners\SendStatusUpdateNotification;
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
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
