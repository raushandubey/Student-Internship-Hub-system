<?php

namespace App\Policies;

use App\Models\User;

class RecruiterManagementPolicy
{
    /**
     * Determine if the user can view any recruiters.
     * Requirements: 19.1, 19.2
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view a specific recruiter.
     * Requirements: 19.1, 19.2
     */
    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can approve a recruiter.
     * Requirements: 19.3
     */
    public function approve(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can reject a recruiter.
     * Requirements: 19.3
     */
    public function reject(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can suspend a recruiter.
     * Requirements: 19.3
     */
    public function suspend(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can activate a recruiter.
     * Requirements: 19.3
     */
    public function activate(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update a recruiter's profile.
     * Requirements: 19.3, 19.5
     */
    public function updateProfile(User $user): bool
    {
        return $user->isAdmin();
    }
}
