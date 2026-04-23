<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * RecruiterApproved Event
 * 
 * Fired when an admin approves a recruiter account.
 */
class RecruiterApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $recruiter;

    public function __construct(User $recruiter)
    {
        $this->recruiter = $recruiter;
    }
}
