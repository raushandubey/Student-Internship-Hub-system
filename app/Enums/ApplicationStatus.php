<?php

namespace App\Enums;

/**
 * ApplicationStatus Enum
 * 
 * Defines all valid application states and their allowed transitions.
 * 
 * State Machine Design:
 * - pending → under_review, rejected
 * - under_review → shortlisted, rejected
 * - shortlisted → interview_scheduled, rejected
 * - interview_scheduled → approved, rejected
 * - approved → (terminal state)
 * - rejected → (terminal state)
 * 
 * Why State Machine?
 * - Prevents invalid transitions (e.g., pending → approved directly)
 * - Documents business rules in code
 * - Easy to audit and explain in interviews
 */
enum ApplicationStatus: string
{
    case PENDING = 'pending';
    case UNDER_REVIEW = 'under_review';
    case SHORTLISTED = 'shortlisted';
    case INTERVIEW_SCHEDULED = 'interview_scheduled';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Get allowed transitions from current status
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::UNDER_REVIEW, self::REJECTED],
            self::UNDER_REVIEW => [self::SHORTLISTED, self::REJECTED],
            self::SHORTLISTED => [self::INTERVIEW_SCHEDULED, self::REJECTED],
            self::INTERVIEW_SCHEDULED => [self::APPROVED, self::REJECTED],
            self::APPROVED => [], // Terminal state
            self::REJECTED => [], // Terminal state
        };
    }

    /**
     * Check if transition to target status is allowed
     */
    public function canTransitionTo(ApplicationStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::UNDER_REVIEW => 'Under Review',
            self::SHORTLISTED => 'Shortlisted',
            self::INTERVIEW_SCHEDULED => 'Interview Scheduled',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Get CSS color class for UI
     */
    public function colorClass(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::UNDER_REVIEW => 'blue',
            self::SHORTLISTED => 'purple',
            self::INTERVIEW_SCHEDULED => 'indigo',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
        };
    }

    /**
     * Check if this is a terminal state
     */
    public function isTerminal(): bool
    {
        return empty($this->allowedTransitions());
    }

    /**
     * Get all statuses as array for dropdowns
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
