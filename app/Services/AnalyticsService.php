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
     * PRODUCTION FIX: Added null coalescing and error handling
     */
    public function getOverallStats(): array
    {
        try {
            return Cache::remember(self::CACHE_PREFIX . 'overall_stats', self::CACHE_TTL, function () {
                return [
                    'total_applications' => Application::count() ?? 0,
                    'total_internships' => Internship::count() ?? 0,
                    'active_internships' => Internship::where('is_active', true)->count() ?? 0,
                    'total_students' => User::where('role', 'student')->count() ?? 0,
                    'avg_match_score' => round((float) (Application::avg('match_score') ?? 0), 1),
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Overall stats query failed', ['error' => $e->getMessage()]);
            return [
                'total_applications' => 0,
                'total_internships' => 0,
                'active_internships' => 0,
                'total_students' => 0,
                'avg_match_score' => 0,
            ];
        }
    }

    /**
     * Get application status breakdown
     * PRODUCTION FIX: Added error handling and empty array fallback
     */
    public function getStatusBreakdown(): array
    {
        try {
            return Cache::remember(self::CACHE_PREFIX . 'status_breakdown', self::CACHE_TTL, function () {
                $breakdown = Application::select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray();
                
                return $breakdown ?: [];
            });
        } catch (\Exception $e) {
            \Log::error('Status breakdown query failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get approval/rejection ratio (CACHED)
     * PRODUCTION FIX: Added division by zero protection
     */
    public function getApprovalRatio(): array
    {
        try {
            return Cache::remember(self::CACHE_PREFIX . 'approval_ratio', self::CACHE_TTL, function () {
                $approved = Application::where('status', 'approved')->count() ?? 0;
                $rejected = Application::where('status', 'rejected')->count() ?? 0;
                $total = $approved + $rejected;

                return [
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
                    'rejection_rate' => $total > 0 ? round(($rejected / $total) * 100, 1) : 0,
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Approval ratio query failed', ['error' => $e->getMessage()]);
            return [
                'approved' => 0,
                'rejected' => 0,
                'approval_rate' => 0,
                'rejection_rate' => 0,
            ];
        }
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
     * PRODUCTION FIX: PostgreSQL-compatible query
     * - Uses CAST for enum comparison
     * - Handles null values safely
     * - Adds comprehensive error handling
     */
    public function getTopPerformingInternships(int $limit = 5): array
    {
        try {
            $results = Internship::select([
                    'internships.id',
                    'internships.title',
                    'internships.organization'
                ])
                ->selectRaw('COUNT(applications.id) as total_apps')
                // FIXED: PostgreSQL-compatible enum comparison using CAST
                ->selectRaw("SUM(CASE WHEN CAST(applications.status AS TEXT) = 'approved' THEN 1 ELSE 0 END) as approved_count")
                ->selectRaw('AVG(applications.match_score) as avg_score')
                ->leftJoin('applications', 'internships.id', '=', 'applications.internship_id')
                ->groupBy('internships.id', 'internships.title', 'internships.organization')
                ->havingRaw('COUNT(applications.id) >= 1')
                ->orderByDesc('approved_count')
                ->limit($limit)
                ->get();

            return $results->map(function($i) {
                return [
                    'title' => $i->title ?? 'Unknown',
                    'organization' => $i->organization ?? 'N/A',
                    'total_apps' => (int) ($i->total_apps ?? 0),
                    'approved' => (int) ($i->approved_count ?? 0),
                    'avg_score' => round((float) ($i->avg_score ?? 0), 1),
                ];
            })->toArray();
            
        } catch (\Exception $e) {
            \Log::error('Top performing internships query failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty array on failure
            return [];
        }
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
