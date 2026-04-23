<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;

/**
 * AdminAnalyticsController
 * 
 * Provides analytics dashboard for admin users.
 * Uses AnalyticsService for all data aggregation.
 */
class AdminAnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index()
    {
        try {
            $overallStats = $this->analyticsService->getOverallStats();
            $statusBreakdown = $this->analyticsService->getStatusBreakdown();
            $approvalRatio = $this->analyticsService->getApprovalRatio();
            $topInternships = $this->analyticsService->getApplicationsPerInternship(10);
            $matchDistribution = $this->analyticsService->getMatchScoreDistribution();
            $recentTrends = $this->analyticsService->getRecentTrends();
            $topPerforming = $this->analyticsService->getTopPerformingInternships(5);

            return view('admin.analytics', compact(
                'overallStats',
                'statusBreakdown',
                'approvalRatio',
                'topInternships',
                'matchDistribution',
                'recentTrends',
                'topPerforming'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Admin analytics page error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load analytics. Please try again or contact support.');
        }
    }
}
