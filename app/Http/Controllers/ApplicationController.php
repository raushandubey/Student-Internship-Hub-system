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
     */
    public function myApplications()
    {
        $applications = $this->applicationService->getUserApplications(Auth::id());
        
        // Add timeline data to each application
        $applicationsWithTimeline = $applications->map(function ($app) {
            $app->timeline = $this->timelineService->getApplicationTimeline($app);
            return $app;
        });

        return view('student.application-tracker', [
            'applications' => $applicationsWithTimeline,
        ]);
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
