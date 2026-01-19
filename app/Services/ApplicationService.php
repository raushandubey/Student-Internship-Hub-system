<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationSubmitted;
use App\Events\ApplicationStatusChanged;
use App\Exceptions\BusinessRuleViolationException;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\UnauthorizedActionException;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ApplicationService
 * 
 * Handles all business logic related to internship applications.
 * 
 * Phase 9 Enhancements:
 * - Custom exceptions for business rule violations
 * - Explicit transaction boundaries with comments
 * - Structured audit logging
 * - Authorization checks before operations
 * 
 * Architecture: Controller → Service → Model → Events
 */
class ApplicationService
{
    protected MatchingService $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Submit a new application
     * 
     * Phase 10: Feature flag support
     * 
     * TRANSACTION BOUNDARY:
     * Why wrapped in transaction?
     * - Application creation + status log must be atomic
     * - If status log fails, application shouldn't exist
     * - Prevents orphaned applications without audit trail
     */
    public function submitApplication(User $user, Internship $internship): array
    {
        // Feature flag check: Recommendations (applications are part of recommendation flow)
        if (!config('features.recommendations_enabled', true)) {
            throw new BusinessRuleViolationException('Application submissions are currently disabled.');
        }

        // Authorization: Only students can apply
        if ($user->role !== 'student') {
            throw new UnauthorizedActionException('apply to internships', 'as non-student');
        }

        // Business Rule: Internship must be active
        if (!$internship->is_active) {
            throw new BusinessRuleViolationException('This internship is no longer accepting applications.');
        }

        // Business Rule: Prevent duplicate applications
        if ($this->hasApplied($user->id, $internship->id)) {
            throw new BusinessRuleViolationException('You have already applied to this internship.');
        }

        try {
            // Calculate match score using MatchingService
            $matchScore = $this->matchingService->calculateApplicationScore($user, $internship);

            /**
             * CRITICAL TRANSACTION:
             * Ensures application + initial status log are created atomically.
             * If either fails, both rollback to maintain data integrity.
             */
            $application = DB::transaction(function () use ($user, $internship, $matchScore) {
                $application = Application::create([
                    'user_id' => $user->id,
                    'internship_id' => $internship->id,
                    'status' => ApplicationStatus::PENDING,
                    'match_score' => $matchScore,
                ]);

                // Log initial status (audit trail starts here)
                $this->logStatusChange(
                    $application,
                    null,
                    ApplicationStatus::PENDING,
                    $user->id,
                    'student',
                    'Application submitted'
                );

                return $application;
            });

            // Invalidate all caches (new application affects recommendations + analytics)
            AnalyticsService::clearCache();
            StudentAnalyticsService::clearCache($user->id);
            MatchingService::clearCache($user->id); // Recommendations should exclude this internship now

            // Fire event for async processing (emails, notifications)
            // Feature flag check: Email notifications
            if (config('features.email_notifications_enabled', true)) {
                event(new ApplicationSubmitted($application));
            }

            // Structured audit log
            Log::info('Application submitted successfully', [
                'actor_id' => $user->id,
                'actor_type' => 'student',
                'action' => 'application.submit',
                'target_entity' => 'application',
                'target_id' => $application->id,
                'internship_id' => $internship->id,
                'match_score' => $matchScore,
                'timestamp' => now()->toIso8601String(),
            ]);

            return [
                'success' => true,
                'message' => 'Application submitted successfully!',
                'application' => $application
            ];

        } catch (\Exception $e) {
            // Structured error log
            Log::error('Application submission failed', [
                'actor_id' => $user->id,
                'action' => 'application.submit',
                'internship_id' => $internship->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String(),
            ]);

            throw $e; // Re-throw for global handler
        }
    }

    /**
     * Check if user has already applied to an internship
     */
    public function hasApplied(int $userId, int $internshipId): bool
    {
        return Application::where('user_id', $userId)
            ->where('internship_id', $internshipId)
            ->exists();
    }

    /**
     * Get all applications for a user with status history
     */
    public function getUserApplications(int $userId)
    {
        return Application::with(['internship', 'statusLogs'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get application statistics for a user
     */
    public function getUserStats(int $userId): array
    {
        $applications = Application::where('user_id', $userId)->get();

        return [
            'total' => $applications->count(),
            'pending' => $applications->where('status', ApplicationStatus::PENDING)->count(),
            'under_review' => $applications->where('status', ApplicationStatus::UNDER_REVIEW)->count(),
            'shortlisted' => $applications->where('status', ApplicationStatus::SHORTLISTED)->count(),
            'interview_scheduled' => $applications->where('status', ApplicationStatus::INTERVIEW_SCHEDULED)->count(),
            'approved' => $applications->where('status', ApplicationStatus::APPROVED)->count(),
            'rejected' => $applications->where('status', ApplicationStatus::REJECTED)->count(),
        ];
    }

    /**
     * Cancel an application (student action)
     * 
     * TRANSACTION BOUNDARY:
     * Why wrapped in transaction?
     * - Application deletion + status log must be atomic
     * - Audit trail must record cancellation before deletion
     * - Prevents data inconsistency if logging fails
     */
    public function cancelApplication(Application $application, int $userId): array
    {
        // Authorization: Only application owner can cancel
        if ($application->user_id !== $userId) {
            throw new UnauthorizedActionException('cancel this application', 'as non-owner');
        }

        // Business Rule: Can only cancel pending applications
        if ($application->status !== ApplicationStatus::PENDING) {
            throw new BusinessRuleViolationException('Cannot cancel an application that has been reviewed.');
        }

        try {
            /**
             * CRITICAL TRANSACTION:
             * Log cancellation before deletion for audit trail.
             * If logging fails, deletion is rolled back.
             */
            DB::transaction(function () use ($application, $userId) {
                // Log cancellation action (audit trail)
                $this->logStatusChange(
                    $application,
                    ApplicationStatus::PENDING,
                    ApplicationStatus::REJECTED, // Treat cancellation as rejection
                    $userId,
                    'student',
                    'Application cancelled by student'
                );

                $application->delete();
            });

            // Invalidate all caches (cancelled application should reappear in recommendations)
            AnalyticsService::clearCache();
            MatchingService::clearCache($userId); // Internship should reappear in recommendations

            // Structured audit log
            Log::info('Application cancelled successfully', [
                'actor_id' => $userId,
                'actor_type' => 'student',
                'action' => 'application.cancel',
                'target_entity' => 'application',
                'target_id' => $application->id,
                'internship_id' => $application->internship_id,
                'timestamp' => now()->toIso8601String(),
            ]);

            return [
                'success' => true,
                'message' => 'Application cancelled successfully.'
            ];

        } catch (\Exception $e) {
            // Structured error log
            Log::error('Application cancellation failed', [
                'actor_id' => $userId,
                'action' => 'application.cancel',
                'application_id' => $application->id,
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ]);

            throw $e; // Re-throw for global handler
        }
    }

    /**
     * Update application status with state machine validation
     * 
     * TRANSACTION BOUNDARY:
     * Why wrapped in transaction?
     * - Status update + audit log must be atomic
     * - If audit log fails, status shouldn't change
     * - Maintains consistency between application state and history
     */
    public function updateStatus(
        Application $application,
        ApplicationStatus $newStatus,
        int $actorId,
        string $actorType = 'admin',
        ?string $notes = null
    ): array {
        $oldStatus = $application->status;

        // State Machine Validation: Check if transition is allowed
        if (!$application->canTransitionTo($newStatus)) {
            throw new InvalidStateTransitionException(
                $oldStatus,
                $newStatus,
                $application->allowedTransitions()
            );
        }

        try {
            /**
             * CRITICAL TRANSACTION:
             * Status update + audit log must be atomic.
             * If either fails, both rollback to maintain data integrity.
             */
            DB::transaction(function () use ($application, $oldStatus, $newStatus, $actorId, $actorType, $notes) {
                $application->update(['status' => $newStatus]);

                // Log status change (audit trail)
                $this->logStatusChange(
                    $application,
                    $oldStatus,
                    $newStatus,
                    $actorId,
                    $actorType,
                    $notes
                );
            });

            // Fire event for async processing (emails, notifications)
            event(new ApplicationStatusChanged(
                $application,
                $oldStatus,
                $newStatus,
                $actorId,
                $actorType
            ));

            // Invalidate analytics cache (status change affects breakdown)
            AnalyticsService::clearCache();
            StudentAnalyticsService::clearCache($application->user_id);

            // Structured audit log
            Log::info('Application status updated successfully', [
                'actor_id' => $actorId,
                'actor_type' => $actorType,
                'action' => 'application.status_update',
                'target_entity' => 'application',
                'target_id' => $application->id,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'notes' => $notes,
                'timestamp' => now()->toIso8601String(),
            ]);

            return [
                'success' => true,
                'message' => 'Application status updated successfully.',
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value
            ];

        } catch (\Exception $e) {
            // Structured error log
            Log::error('Application status update failed', [
                'actor_id' => $actorId,
                'actor_type' => $actorType,
                'action' => 'application.status_update',
                'application_id' => $application->id,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ]);

            throw $e; // Re-throw for global handler
        }
    }

    /**
     * Log status change for audit trail
     */
    private function logStatusChange(
        Application $application,
        ?ApplicationStatus $fromStatus,
        ApplicationStatus $toStatus,
        int $changedBy,
        string $actorType,
        ?string $notes = null
    ): void {
        ApplicationStatusLog::create([
            'application_id' => $application->id,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
            'changed_by' => $changedBy,
            'actor_type' => $actorType,
            'notes' => $notes,
        ]);
    }

    /**
     * Get status history for an application
     */
    public function getStatusHistory(Application $application)
    {
        return $application->statusLogs()->with('changedBy')->get();
    }
}
