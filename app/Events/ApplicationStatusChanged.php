<?php

namespace App\Events;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ApplicationStatusChanged Event
 * 
 * Fired when an application status is updated.
 */
class ApplicationStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Application $application;
    public ApplicationStatus $oldStatus;
    public ApplicationStatus $newStatus;
    public int $changedBy;
    public string $actorType;

    public function __construct(
        Application $application,
        ApplicationStatus $oldStatus,
        ApplicationStatus $newStatus,
        int $changedBy,
        string $actorType = 'admin'
    ) {
        $this->application = $application;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
        $this->actorType = $actorType;
    }
}
