<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * RecruiterActivated Event
 * 
 * Fired when an admin activates a suspended recruiter account.
 */
class RecruiterActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $recruiter;

    public function __construct(User $recruiter)
    {
        $this->recruiter = $recruiter;
    }
}
