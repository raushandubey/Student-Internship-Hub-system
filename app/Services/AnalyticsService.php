<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * AnalyticsService
 * 
 * Provides aggregated statistics for admin dashboard.
 * Uses database aggregates for efficiency (no N+1 queries).
 * Implements caching for frequently accessed statistics.
 * 
 * Performance Optimizations:
 * - Cache::remember() for expensive aggregations
 * - Explicit column selection (no SELECT *)
 * - MySQL ONLY_FULL_GROUP_BY compliant queries
 */
class AnalyticsService
{
    /** Cache TTL in seconds (5 minutes) */
    private const CACHE_TTL = 300;

    /** Cache key prefix */
    private const CACHE_PREFIX = 'analytics_';

    /**
     * Get overall application statistics (CACHED)
     * 
     * Why cache? These counts are expensive on large tables.
     * TTL of 5 minutes balances freshness vs performance.
     */
    public function getOverallStats(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'overall_stats', self::CACHE_TTL, function () {
            return [
                'total_applications' => Application::count(),
                'total_internships' => Internship::count(),
                'active_internships' => Internship::where('is_active', true)->count(),
                'total_students' => User::where('role', 'student')->count(),
                'avg_match_score' => round(Application::avg('match_score') ?? 0, 1),
            ];
        });
    }

    /**
     * Get application status breakdown
     */
    public function getStatusBreakdown(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'status_breakdown', self::CACHE_TTL, function () {
            return Application::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        });
    }

    /**
     * Get approval/rejection ratio (CACHED)
     */
    public function getApprovalRatio(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'approval_ratio', self::CACHE_TTL, function () {
            $approved = Application::where('status', 'approved')->count();
            $rejected = Application::where('status', 'rejected')->count();
            $total = $approved + $rejected;

            return [
                'approved' => $approved,
                'rejected' => $rejected,
                'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
                'rejection_rate' => $total > 0 ? round(($rejected / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get applications per internship (top N)
     * 
     * Uses withCount() - Laravel's optimized subquery approach.
     * No N+1: single query with COUNT subquery.
     */
    public function getApplicationsPerInternship(int $limit = 10): array
    {
        return Internship::withCount('applications')
            ->orderByDesc('applications_count')
            ->limit($limit)
            ->get(['id', 'title', 'organization']) // Explicit columns
            ->map(fn($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'organization' => $i->organization,
                'applications_count' => $i->applications_count,
            ])
            ->toArray();
    }

    /**
     * Get match score distribution (CACHED)
     */
    public function getMatchScoreDistribution(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'match_distribution', self::CACHE_TTL, function () {
            return [
                'excellent' => Application::where('match_score', '>=', 80)->count(),
                'good' => Application::whereBetween('match_score', [60, 79.99])->count(),
                'fair' => Application::whereBetween('match_score', [40, 59.99])->count(),
                'low' => Application::where('match_score', '<', 40)->count(),
            ];
        });
    }

    /**
     * Get recent application trends (last 7 days)
     * 
     * Not cached: needs real-time accuracy for trends.
     */
    public function getRecentTrends(): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $days[$date] = Application::whereDate('created_at', $date)->count();
        }
        return $days;
    }

    /**
     * Get top performing internships by approval count
     * 
     * FIXED: MySQL ONLY_FULL_GROUP_BY compliant
     * 
     * Problem: SELECT internships.* with GROUP BY internships.id fails
     * because MySQL strict mode requires ALL selected non-aggregated 
     * columns to appear in GROUP BY.
     * 
     * Solution: Explicitly select only needed columns and include them
     * all in GROUP BY clause.
     */
    public function getTopPerformingInternships(int $limit = 5): array
    {
        return Internship::select([
                'internships.id',
                'internships.title',
                'internships.organization'
            ])
            ->selectRaw('COUNT(applications.id) as total_apps')
            ->selectRaw('SUM(CASE WHEN applications.status = "approved" THEN 1 ELSE 0 END) as approved_count')
            ->selectRaw('AVG(applications.match_score) as avg_score')
            ->leftJoin('applications', 'internships.id', '=', 'applications.internship_id')
            ->groupBy('internships.id', 'internships.title', 'internships.organization')
            ->havingRaw('COUNT(applications.id) >= 1')
            ->orderByDesc('approved_count')
            ->limit($limit)
            ->get()
            ->map(fn($i) => [
                'title' => $i->title,
                'organization' => $i->organization,
                'total_apps' => $i->total_apps,
                'approved' => $i->approved_count,
                'avg_score' => round($i->avg_score ?? 0, 1),
            ])
            ->toArray();
    }

    /**
     * Clear all analytics cache
     * 
     * Called when data changes (application submit, status update)
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'overall_stats');
        Cache::forget(self::CACHE_PREFIX . 'status_breakdown');
        Cache::forget(self::CACHE_PREFIX . 'approval_ratio');
        Cache::forget(self::CACHE_PREFIX . 'match_distribution');
    }
}
