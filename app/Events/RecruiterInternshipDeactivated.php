<?php

namespace App\Events;

use App\Models\Internship;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * RecruiterInternshipDeactivated Event
 * 
 * Fired when an admin deactivates a recruiter's internship.
 */
class RecruiterInternshipDeactivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Internship $internship;
    public User $recruiter;
    public string $reason;

    public function __construct(Internship $internship, User $recruiter, string $reason)
    {
        $this->internship = $internship;
        $this->recruiter = $recruiter;
        $this->reason = $reason;
    }
}
