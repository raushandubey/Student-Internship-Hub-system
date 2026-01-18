<?php

namespace App\Policies;

use App\Models\Internship;
use App\Models\User;

/**
 * InternshipPolicy
 * 
 * Defines authorization rules for internship management.
 * 
 * WHY POLICIES?
 * - Centralized authorization logic (not scattered in controllers)
 * - Reusable across web, API, and CLI
 * - Automatically integrated with Laravel's Gate system
 */
class InternshipPolicy
{
    /**
     * Determine if user can view any internships
     * Everyone can view active internships
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can view a specific internship
     */
    public function view(User $user, Internship $internship): bool
    {
        return true;
    }

    /**
     * Determine if user can create internships
     * Only admins can create
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if user can update internships
     * Only admins can update
     */
    public function update(User $user, Internship $internship): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if user can delete internships
     * Only admins can delete
     */
    public function delete(User $user, Internship $internship): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if user can toggle internship status
     * Only admins can activate/deactivate
     */
    public function toggleStatus(User $user, Internship $internship): bool
    {
        return $user->role === 'admin';
    }
}
