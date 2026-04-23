<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Internship;
use Illuminate\Support\Facades\DB;

class RecruiterApplicationService
{
    /**
     * Get all applications for a recruiter's internships with data isolation
     */
    public function getRecruiterApplications($recruiterId, array $filters = [])
    {
        $query = Application::with(['user', 'user.profile', 'internship'])
            ->whereHas('internship', fn($q) => $q->where('recruiter_id', $recruiterId));

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['internship_id'])) {
            $query->where('internship_id', $filters['internship_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Update an application's status with ownership check and audit logging
     */
    public function updateApplicationStatus(Application $application, string $newStatusValue, $recruiterId)
    {
        // Verify the application belongs to the recruiter's internship
        if ($application->internship->recruiter_id !== $recruiterId) {
            abort(403, 'Unauthorized to update this application.');
        }

        $newStatus = ApplicationStatus::from($newStatusValue);
        $oldStatus = $application->status;

        // Validate status transition
        if (!$oldStatus->canTransitionTo($newStatus)) {
            abort(422, "Cannot transition from {$oldStatus->label()} to {$newStatus->label()}.");
        }

        DB::transaction(function () use ($application, $oldStatus, $newStatus, $recruiterId) {
            $application->update(['status' => $newStatus]);
            $this->recordStatusChange($application, $oldStatus, $newStatus, $recruiterId);
        });

        return $application->fresh();
    }

    /**
     * Record a status change in the audit log
     */
    public function recordStatusChange(Application $application, ApplicationStatus $oldStatus, ApplicationStatus $newStatus, $changedBy = null)
    {
        return $application->statusLogs()->create([
            'from_status' => $oldStatus->value,
            'to_status' => $newStatus->value,
            'changed_by' => $changedBy,
            'actor_type' => 'recruiter',
        ]);
    }

    /**
     * Bulk update status for multiple applications
     */
    public function bulkUpdateStatus(array $applicationIds, string $newStatusValue, $recruiterId)
    {
        $newStatus = ApplicationStatus::from($newStatusValue);

        // Fetch only applications belonging to this recruiter's internships
        $applications = Application::with('internship')
            ->whereIn('id', $applicationIds)
            ->whereHas('internship', fn($q) => $q->where('recruiter_id', $recruiterId))
            ->get();

        if ($applications->isEmpty()) {
            abort(403, 'No authorized applications found for bulk update.');
        }

        $updatedCount = 0;

        DB::transaction(function () use ($applications, $newStatus, $recruiterId, &$updatedCount) {
            foreach ($applications as $application) {
                $oldStatus = $application->status;

                if ($oldStatus->canTransitionTo($newStatus)) {
                    $application->update(['status' => $newStatus]);
                    $this->recordStatusChange($application, $oldStatus, $newStatus, $recruiterId);
                    $updatedCount++;
                }
            }
        });

        return $updatedCount;
    }
}
