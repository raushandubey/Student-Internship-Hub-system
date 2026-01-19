<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Application Model
 * 
 * Represents a student's application to an internship.
 * Uses ApplicationStatus enum for state machine validation.
 */
class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'internship_id',
        'status',
        'match_score',
    ];

    /**
     * Cast status to enum
     */
    protected $casts = [
        'status' => ApplicationStatus::class,
        'match_score' => 'float',
    ];

    /**
     * Get the user who applied
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the internship applied for
     */
    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }

    /**
     * Get status change history
     */
    public function statusLogs()
    {
        return $this->hasMany(ApplicationStatusLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if transition to new status is allowed
     */
    public function canTransitionTo(ApplicationStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    /**
     * Get allowed next statuses
     */
    public function allowedTransitions(): array
    {
        return $this->status->allowedTransitions();
    }

    /**
     * Check if application is in terminal state
     */
    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * Get status color class for UI
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status->colorClass();
    }
}
