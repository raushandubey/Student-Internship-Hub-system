<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Internship;
use App\Services\ApplicationService;
use App\Services\ApplicationTimelineService;
use App\Services\StudentAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ApplicationController
 * 
 * Thin controller that handles HTTP requests and delegates to services.
 * Phase 8: Enhanced with timeline predictions.
 */
class ApplicationController extends Controller
{
    protected ApplicationService $applicationService;
    protected ApplicationTimelineService $timelineService;

    public function __construct(
        ApplicationService $applicationService,
        ApplicationTimelineService $timelineService
    ) {
        $this->applicationService = $applicationService;
        $this->timelineService = $timelineService;
    }

    /**
     * Apply to an internship
     * 
     * Phase 9: Exception handling delegated to global handler
     * Controller stays thin - just catches and redirects
     */
    public function apply(Request $request, Internship $internship)
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Please login to apply for internships.');
        }

        try {
            $result = $this->applicationService->submitApplication(
                Auth::user(),
                $internship
            );

            // Clear student analytics cache on new application
            StudentAnalyticsService::clearCache(Auth::id());

            return back()->with('success', $result['message']);

        } catch (\Exception $e) {
            // Global handler will log and format the error
            // Controller just catches and redirects with error message
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * View student's own applications (Application Tracker)
     * Phase 8: Enhanced with timeline predictions
     * Production Fix: Filter out applications with deleted internships
     */
    public function myApplications()
    {
        try {
            $applications = $this->applicationService->getUserApplications(Auth::id());
            
            // CRITICAL FIX: Filter out applications with null internships
            $validApplications = $applications->filter(function ($app) {
                return $app->internship !== null;
            });
            
            // Add timeline data to each application
            $applicationsWithTimeline = $validApplications->map(function ($app) {
                try {
                    $app->timeline = $this->timelineService->getApplicationTimeline($app);
                } catch (\Exception $e) {
                    // If timeline fails, set empty timeline
                    \Log::warning('Timeline generation failed', [
                        'application_id' => $app->id,
                        'error' => $e->getMessage()
                    ]);
                    $app->timeline = ['prediction' => null];
                }
                return $app;
            });

            return view('student.application-tracker', [
                'applications' => $applicationsWithTimeline,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('My Applications page error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Unable to load applications. Please try again or contact support.');
        }
    }

    /**
     * Cancel application
     * 
     * Phase 9: Exception handling delegated to global handler
     */
    public function cancel(Application $application)
    {
        try {
            $result = $this->applicationService->cancelApplication(
                $application,
                Auth::id()
            );

            // Clear student analytics cache on cancellation
            StudentAnalyticsService::clearCache(Auth::id());

            return back()->with('success', $result['message']);

        } catch (\Exception $e) {
            // Global handler will log and format the error
            return back()->with('error', $e->getMessage());
        }
    }
}
