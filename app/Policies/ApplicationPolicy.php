<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

/**
 * ApplicationPolicy
 * 
 * Defines authorization rules for application management.
 * 
 * KEY RULES:
 * - Students can only view/cancel their own applications
 * - Only admins can update application status
 * - Status updates must follow state machine rules (enforced in service)
 */
class ApplicationPolicy
{
    /**
     * Determine if user can view any applications
     * Students see their own, admins see all
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtering happens in controller/service
    }

    /**
     * Determine if user can view a specific application
     */
    public function view(User $user, Application $application): bool
    {
        // Students can view their own applications
        if ($user->role === 'student') {
            return $application->user_id === $user->id;
        }

        // Admins can view all applications
        return $user->role === 'admin';
    }

    /**
     * Determine if user can create applications
     * Only students can apply
     */
    public function create(User $user): bool
    {
        return $user->role === 'student';
    }

    /**
     * Determine if user can update application status
     * Only admins can change status
     */
    public function updateStatus(User $user, Application $application): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if user can cancel an application
     * Students can cancel their own pending applications
     */
    public function cancel(User $user, Application $application): bool
    {
        if ($user->role !== 'student') {
            return false;
        }

        // Can only cancel own applications
        if ($application->user_id !== $user->id) {
            return false;
        }

        // Can only cancel pending applications
        return $application->status->value === 'pending';
    }

    /**
     * Determine if user can view application history
     */
    public function viewHistory(User $user, Application $application): bool
    {
        // Students can view their own history
        if ($user->role === 'student') {
            return $application->user_id === $user->id;
        }

        // Admins can view all history
        return $user->role === 'admin';
    }
}
