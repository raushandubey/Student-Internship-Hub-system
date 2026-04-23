<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * RecruiterProfileModified Event
 * 
 * Fired when an admin modifies a recruiter's profile.
 */
class RecruiterProfileModified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $recruiter;
    public User $admin;

    public function __construct(User $recruiter, User $admin)
    {
        $this->recruiter = $recruiter;
        $this->admin = $admin;
    }
}
