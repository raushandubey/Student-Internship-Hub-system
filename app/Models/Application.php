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

    /**
     * Record a status change in the log
     */
    public function recordStatusChange(ApplicationStatus $oldStatus, ApplicationStatus $newStatus, $changedBy = null, $actorType = 'admin', $notes = null)
    {
        return $this->statusLogs()->create([
            'from_status' => $oldStatus->value,
            'to_status' => $newStatus->value,
            'changed_by' => $changedBy,
            'actor_type' => $actorType,
            'notes' => $notes,
        ]);
    }

    /**
     * Get progress percentage based on status
     */
    public function getProgressPercentage(): int
    {
        $progressMap = [
            'pending' => 25,
            'under_review' => 50,
            'shortlisted' => 65,
            'interview_scheduled' => 80,
            'approved' => 100,
            'rejected' => 100,
        ];

        return $progressMap[$this->status->value] ?? 0;
    }

    /**
     * Get next steps message based on current status
     */
    public function getNextSteps(): ?string
    {
        $nextStepsMap = [
            'pending' => 'Your application is being reviewed by the recruiter.',
            'under_review' => 'The recruiter is evaluating your profile. You may be contacted soon.',
            'shortlisted' => 'Congratulations! Prepare for a potential interview.',
            'interview_scheduled' => 'Check your email for interview details and prepare accordingly.',
            'approved' => 'Congratulations! Check your email for next steps.',
            'rejected' => null,
        ];

        return $nextStepsMap[$this->status->value] ?? null;
    }
}
