<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Internship;
use Illuminate\Support\Facades\DB;

class RecruiterAnalyticsService
{
    /**
     * Get dashboard statistics for a recruiter
     */
    public function getDashboardStats($recruiterId): array
    {
        $internshipIds = Internship::forRecruiter($recruiterId)->pluck('id');

        $totalInternships = $internshipIds->count();
        $totalApplicants = Application::whereIn('internship_id', $internshipIds)->count();
        $pendingApplications = Application::whereIn('internship_id', $internshipIds)
            ->where('status', ApplicationStatus::PENDING->value)
            ->count();

        return [
            'total_internships' => $totalInternships,
            'total_applicants' => $totalApplicants,
            'pending_applications' => $pendingApplications,
        ];
    }

    /**
     * Get conversion funnel data (application counts at each status)
     */
    public function getConversionFunnel($recruiterId): array
    {
        $internshipIds = Internship::forRecruiter($recruiterId)->pluck('id');

        $counts = Application::whereIn('internship_id', $internshipIds)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Return in funnel order
        $funnel = [];
        foreach (ApplicationStatus::cases() as $status) {
            $funnel[$status->label()] = $counts[$status->value] ?? 0;
        }

        return $funnel;
    }

    /**
     * Get average time-to-hire (days from applied to approved)
     */
    public function getAverageTimeToHire($recruiterId): ?float
    {
        $internshipIds = Internship::forRecruiter($recruiterId)->pluck('id');

        $approvedApplicationIds = Application::whereIn('internship_id', $internshipIds)
            ->where('status', ApplicationStatus::APPROVED->value)
            ->pluck('id');

        if ($approvedApplicationIds->isEmpty()) {
            return null;
        }

        $avgDays = ApplicationStatusLog::whereIn('application_id', $approvedApplicationIds)
            ->where('to_status', ApplicationStatus::APPROVED->value)
            ->join('applications', 'application_status_logs.application_id', '=', 'applications.id')
            ->select(DB::raw('AVG(DATEDIFF(application_status_logs.created_at, applications.created_at)) as avg_days'))
            ->value('avg_days');

        return $avgDays ? round((float) $avgDays, 1) : null;
    }

    /**
     * Get application rate per internship
     */
    public function getApplicationRate($recruiterId): array
    {
        return Internship::forRecruiter($recruiterId)
            ->withCount('applications')
            ->get()
            ->map(fn($i) => [
                'title' => $i->title,
                'count' => $i->applications_count,
            ])
            ->toArray();
    }

    /**
     * Get top skills from approved candidates
     */
    public function getTopSkills($recruiterId, int $limit = 10): array
    {
        $internshipIds = Internship::forRecruiter($recruiterId)->pluck('id');

        $approvedUserIds = Application::whereIn('internship_id', $internshipIds)
            ->where('status', ApplicationStatus::APPROVED->value)
            ->pluck('user_id');

        if ($approvedUserIds->isEmpty()) {
            return [];
        }

        $profiles = DB::table('profiles')
            ->whereIn('user_id', $approvedUserIds)
            ->whereNotNull('skills')
            ->pluck('skills');

        $skillCounts = [];
        foreach ($profiles as $skillsJson) {
            $skills = is_array($skillsJson) ? $skillsJson : json_decode($skillsJson, true);
            if (is_array($skills)) {
                foreach ($skills as $skill) {
                    $skill = trim((string) $skill);
                    if ($skill) {
                        $skillCounts[$skill] = ($skillCounts[$skill] ?? 0) + 1;
                    }
                }
            }
        }

        arsort($skillCounts);
        return array_slice($skillCounts, 0, $limit, true);
    }

    /**
     * Get individual recruiter metrics for admin panel
     * 
     * @param int $recruiterId
     * @return array
     */
    public function getRecruiterStats($recruiterId): array
    {
        $internshipIds = Internship::forRecruiter($recruiterId)->pluck('id');

        $totalInternships = $internshipIds->count();
        $activeInternships = Internship::forRecruiter($recruiterId)->active()->count();
        $totalApplications = Application::whereIn('internship_id', $internshipIds)->count();

        // Calculate approval rate
        $approvedApplications = Application::whereIn('internship_id', $internshipIds)
            ->where('status', ApplicationStatus::APPROVED->value)
            ->count();
        $approvalRate = $totalApplications > 0 
            ? round(($approvedApplications / $totalApplications) * 100, 1) 
            : 0;

        // Calculate average response time (time from application to first status change)
        $avgResponseTime = $this->calculateAverageResponseTime($internshipIds);

        return [
            'total_internships' => $totalInternships,
            'active_internships' => $activeInternships,
            'total_applications' => $totalApplications,
            'approval_rate' => $approvalRate,
            'avg_response_time' => $avgResponseTime,
        ];
    }

    /**
     * Get system-wide recruiter statistics for admin dashboard
     * 
     * @return array
     */
    public function getSystemWideRecruiterStats(): array
    {
        // Count recruiters by approval status
        $approvedRecruiters = DB::table('recruiter_profiles')
            ->where('approval_status', 'approved')
            ->count();

        $pendingRecruiters = DB::table('recruiter_profiles')
            ->where('approval_status', 'pending')
            ->count();

        $suspendedRecruiters = DB::table('recruiter_profiles')
            ->where('approval_status', 'suspended')
            ->count();

        // Count recruiter-posted internships
        $totalRecruiterInternships = Internship::whereNotNull('recruiter_id')->count();
        $activeRecruiterInternships = Internship::whereNotNull('recruiter_id')
            ->active()
            ->count();

        // Count applications to recruiter-posted internships
        $recruiterInternshipIds = Internship::whereNotNull('recruiter_id')->pluck('id');
        $applicationsToRecruiterInternships = Application::whereIn('internship_id', $recruiterInternshipIds)
            ->count();

        return [
            'approved_recruiters' => $approvedRecruiters,
            'pending_recruiters' => $pendingRecruiters,
            'suspended_recruiters' => $suspendedRecruiters,
            'total_recruiter_internships' => $totalRecruiterInternships,
            'active_recruiter_internships' => $activeRecruiterInternships,
            'applications_to_recruiter_internships' => $applicationsToRecruiterInternships,
        ];
    }

    /**
     * Get recruiter performance data for analytics page
     * 
     * @param array|null $dateRange ['start' => Carbon, 'end' => Carbon]
     * @return array
     */
    public function getRecruiterPerformanceData($dateRange = null): array
    {
        $query = DB::table('users')
            ->join('recruiter_profiles', 'users.id', '=', 'recruiter_profiles.user_id')
            ->where('users.role', 'recruiter')
            ->where('recruiter_profiles.approval_status', 'approved')
            ->select(
                'users.id as recruiter_id',
                'users.name as recruiter_name',
                'recruiter_profiles.organization'
            );

        $recruiters = $query->get();

        $performanceData = [];

        foreach ($recruiters as $recruiter) {
            $internshipQuery = Internship::forRecruiter($recruiter->recruiter_id);
            
            // Apply date range filter if provided
            if ($dateRange && isset($dateRange['start']) && isset($dateRange['end'])) {
                $internshipQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }

            $internshipIds = $internshipQuery->pluck('id');
            $totalInternships = $internshipIds->count();

            // Get applications within date range
            $applicationQuery = Application::whereIn('internship_id', $internshipIds);
            if ($dateRange && isset($dateRange['start']) && isset($dateRange['end'])) {
                $applicationQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }

            $totalApplications = $applicationQuery->count();
            $approvedApplications = (clone $applicationQuery)
                ->where('status', ApplicationStatus::APPROVED->value)
                ->count();

            // Calculate approval rate
            $approvalRate = $totalApplications > 0 
                ? round(($approvedApplications / $totalApplications) * 100, 1) 
                : 0;

            // Calculate average response time
            $avgResponseTime = $this->calculateAverageResponseTime($internshipIds, $dateRange);

            // Calculate fill rate (internships with at least one approved application)
            $internshipsWithApprovals = Internship::whereIn('id', $internshipIds)
                ->whereHas('applications', function ($q) {
                    $q->where('status', ApplicationStatus::APPROVED->value);
                })
                ->count();
            $fillRate = $totalInternships > 0 
                ? round(($internshipsWithApprovals / $totalInternships) * 100, 1) 
                : 0;

            $performanceData[] = [
                'recruiter_id' => $recruiter->recruiter_id,
                'recruiter_name' => $recruiter->recruiter_name,
                'organization' => $recruiter->organization,
                'total_internships' => $totalInternships,
                'total_applications' => $totalApplications,
                'approval_rate' => $approvalRate,
                'avg_response_time' => $avgResponseTime,
                'fill_rate' => $fillRate,
                'exceeds_response_threshold' => $avgResponseTime !== null && $avgResponseTime > 7,
            ];
        }

        // Sort by total applications descending
        usort($performanceData, function ($a, $b) {
            return $b['total_applications'] <=> $a['total_applications'];
        });

        return $performanceData;
    }

    /**
     * Calculate average response time for given internship IDs
     * 
     * @param \Illuminate\Support\Collection $internshipIds
     * @param array|null $dateRange
     * @return float|null Average response time in days
     */
    private function calculateAverageResponseTime($internshipIds, $dateRange = null): ?float
    {
        if ($internshipIds->isEmpty()) {
            return null;
        }

        $applicationQuery = Application::whereIn('internship_id', $internshipIds);
        
        if ($dateRange && isset($dateRange['start']) && isset($dateRange['end'])) {
            $applicationQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        $applicationIds = $applicationQuery->pluck('id');

        if ($applicationIds->isEmpty()) {
            return null;
        }

        // Get first status change for each application
        $firstStatusChanges = ApplicationStatusLog::whereIn('application_id', $applicationIds)
            ->select('application_id', DB::raw('MIN(created_at) as first_change'))
            ->groupBy('application_id')
            ->get();

        if ($firstStatusChanges->isEmpty()) {
            return null;
        }

        $totalDays = 0;
        $count = 0;

        foreach ($firstStatusChanges as $change) {
            $application = Application::find($change->application_id);
            if ($application) {
                $days = $application->created_at->diffInDays($change->first_change);
                $totalDays += $days;
                $count++;
            }
        }

        return $count > 0 ? round($totalDays / $count, 1) : null;
    }
}
