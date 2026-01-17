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