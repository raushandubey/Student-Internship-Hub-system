<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ApplicationStatusLog Model
 * 
 * Stores audit trail of all application status changes.
 * Every status transition is logged with timestamp and actor.
 */
class ApplicationStatusLog extends Model
{
    protected $fillable = [
        'application_id',
        'from_status',
        'to_status',
        'changed_by',
        'actor_type',
        'notes',
    ];

    /**
     * Get the application this log belongs to
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user who made the change
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
