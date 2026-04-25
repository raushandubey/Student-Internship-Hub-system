<?php

namespace App\Http\Controllers;

use App\Services\ApplicationService;
use App\Services\MatchingService;
use App\Services\StudentAnalyticsService;
use Illuminate\Support\Facades\Auth;

/**
 * DashboardController
 * 
 * Thin controller that delegates to services for business logic.
 * Phase 8: Enhanced with Student Career Intelligence.
 * 
 * Uses MatchingService for recommendations (excludes applied internships).
 */
class DashboardController extends Controller
{
    protected ApplicationService $applicationService;
    protected MatchingService $matchingService;
    protected StudentAnalyticsService $studentAnalytics;

    public function __construct(
        ApplicationService $applicationService,
        MatchingService $matchingService,
        StudentAnalyticsService $studentAnalytics
    ) {
        $this->applicationService = $applicationService;
        $this->matchingService = $matchingService;
        $this->studentAnalytics = $studentAnalytics;
    }

    public function index()
    {
        $user = Auth::user();
        
        // Redirect admin to admin dashboard
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Redirect recruiter to recruiter dashboard
        if ($user->role === 'recruiter') {
            return redirect()->route('recruiter.dashboard');
        }
        
        // Student dashboard logic
        $profile = $user->profile;
        $recommendations = 0;
        $profileCompletion = 0;
        
        // Calculate profile completion
        if ($profile) {
            $profileCompletion = $this->calculateProfileCompletion($profile);
            
            // Get recommendations count (excludes applied internships)
            if (!empty($profile->skills)) {
                $recommendations = count($this->matchingService->getRecommendations($user));
            }
        }
        
        // Get application statistics from service (single source of truth)
        $stats = $this->applicationService->getUserStats(Auth::id());
        
        // Get career intelligence data (Phase 8)
        $analytics = $this->studentAnalytics->getDashboardAnalytics(Auth::id());
        
        // Calculate rates
        $applicationRate = $recommendations > 0 
            ? round(($stats['total'] / $recommendations) * 100) . '%'
            : '0%';
        
        $responseRate = $stats['total'] > 0
            ? round((($stats['approved'] + $stats['rejected']) / $stats['total']) * 100) . '%'
            : '0%';
        
        // Mobile detection — same regex as ProfileController
        $isMobile = request()->header('User-Agent') &&
                    preg_match('/Mobile|Android|iPhone|iPad/i', request()->header('User-Agent'));

        if ($isMobile) {
            $recentActivities = $this->getRecentActivities($user);

            return view('student.dashboard-mobile', [
                'profileCompletion' => $profileCompletion,
                'appliedJobs'       => $stats['total'],
                'interviews'        => $stats['interview_scheduled'] + $stats['approved'],
                'profileViews'      => 0,
                'recommendations'   => $recommendations,
                'recentActivities'  => $recentActivities,
            ]);
        }

        return view('student.dashboard', [
            'profileCompletion' => $profileCompletion,
            'recommendations' => $recommendations,
            'appliedJobs' => $stats['total'],
            'interviews' => $stats['interview_scheduled'] + $stats['approved'],
            'profileViews' => 0,
            'applicationRate' => $applicationRate,
            'responseRate' => $responseRate,
            // Phase 8: Career Intelligence
            'careerReadiness' => $analytics['readiness'],
            'skillStrengths' => $analytics['strengths'],
            'skillGaps' => $analytics['gaps'],
            'outcomes' => $analytics['outcomes'],
        ]);
    }
    
    /**
     * JSON endpoint: recent activity for polling (session-auth)
     * GET /api/recent-activity
     */
    public function recentActivity(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $activities = [];

        try {
            $apps = \App\Models\Application::where('user_id', $user->id)
                ->with('internship')
                ->latest()
                ->take(5)
                ->get();

            foreach ($apps as $app) {
                if (!$app->internship) continue;

                // Map status → icon/color/subtitle
                // $app->status is an ApplicationStatus enum — use ->value for string key lookup
                $statusValue = $app->status instanceof \BackedEnum
                    ? $app->status->value
                    : (string) $app->status;

                $statusMap = [
                    'approved'             => ['icon' => 'check-circle',   'color' => '#10b981', 'subtitle' => 'Congratulations! Application approved'],
                    'rejected'             => ['icon' => 'times-circle',   'color' => '#ef4444', 'subtitle' => 'Application was not selected'],
                    'interview_scheduled'  => ['icon' => 'calendar-check', 'color' => '#3b82f6', 'subtitle' => 'Interview has been scheduled'],
                    'shortlisted'          => ['icon' => 'star',           'color' => '#8b5cf6', 'subtitle' => 'You have been shortlisted!'],
                    'under_review'         => ['icon' => 'search',         'color' => '#f59e0b', 'subtitle' => 'Application is under review'],
                    'pending'              => ['icon' => 'paper-plane',    'color' => '#6366f1', 'subtitle' => 'Application submitted successfully'],
                ];

                $meta = $statusMap[$statusValue] ?? $statusMap['pending'];

                $activities[] = [
                    'icon'      => $meta['icon'],
                    'color'     => $meta['color'],
                    'title'     => 'Applied to ' . $app->internship->title,
                    'subtitle'  => $meta['subtitle'],
                    'time'      => $app->created_at->diffForHumans(),
                    'status'    => $statusValue,
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('recentActivity endpoint failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'activities'   => $activities,
            'last_updated' => now()->toIso8601String(),
            'count'        => count($activities),
        ]);
    }

    /**
     * Mobile-first dashboard view
     */
    public function indexMobile()
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        // Calculate profile completion
        $profileCompletion = $profile ? $this->calculateProfileCompletion($profile) : 0;
        
        // Get stats
        $stats = $this->applicationService->getUserStats(Auth::id());
        $appliedJobs = $stats['total'];
        $interviews = $stats['interview_scheduled'] + $stats['approved'];
        $profileViews = 0; // Implement view tracking if needed
        
        // Get recommendations count
        $recommendations = 0;
        if ($profile && !empty($profile->skills)) {
            $recommendations = count($this->matchingService->getRecommendations($user));
        }
        
        // Get recent activities (last 5)
        $recentActivities = $this->getRecentActivities($user);
        
        return view('student.dashboard-mobile', compact(
            'profileCompletion',
            'appliedJobs',
            'interviews',
            'profileViews',
            'recommendations',
            'recentActivities'
        ));
    }
    
    /**
     * Get recent user activities
     * Uses Application model directly — User model has no applications() relation.
     */
    private function getRecentActivities($user): array
    {
        $activities = [];

        try {
            $recentApps = \App\Models\Application::where('user_id', $user->id)
                ->with('internship')
                ->latest()
                ->take(3)
                ->get();

            foreach ($recentApps as $app) {
                if ($app->internship) {
                    $activities[] = [
                        'icon'  => 'paper-plane',
                        'title' => 'Applied to ' . $app->internship->title,
                        'time'  => $app->created_at->diffForHumans(),
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::warning('getRecentActivities failed', ['error' => $e->getMessage()]);
        }

        return $activities;
    }
    
    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion($profile): int
    {
        $fields = ['name', 'academic_background', 'skills', 'career_interests', 'resume_path', 'aadhaar_number'];
        $completed = 0;
        
        foreach ($fields as $field) {
            if (!empty($profile->$field)) {
                $completed++;
            }
        }
        
        return round(($completed / count($fields)) * 100);
    }
}