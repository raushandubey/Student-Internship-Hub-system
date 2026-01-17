<?php

namespace App\Events;

use App\Models\Application;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ApplicationSubmitted Event
 * 
 * Fired when a student submits a new application.
 * 
 * Why Events?
 * - Decouples application logic from side effects (emails, notifications)
 * - Allows async processing via queues
 * - Easy to add new listeners without modifying core logic
 * - Interview: "I used event-driven architecture for loose coupling"
 */
class ApplicationSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }
}
