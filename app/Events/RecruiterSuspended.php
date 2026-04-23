<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * RecruiterSuspended Event
 * 
 * Fired when an admin suspends a recruiter account.
 */
class RecruiterSuspended
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $recruiter;
    public string $reason;

    public function __construct(User $recruiter, string $reason)
    {
        $this->recruiter = $recruiter;
        $this->reason = $reason;
    }
}
