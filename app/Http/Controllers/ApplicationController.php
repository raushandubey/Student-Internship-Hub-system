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
     * Show the application form for a specific internship (GET)
     *
     * Redirects back if student has already applied.
     */
    public function applyForm(Internship $internship)
    {
        // Guard: already applied
        $hasApplied = \App\Models\Application::where('user_id', Auth::id())
            ->where('internship_id', $internship->id)
            ->exists();

        if ($hasApplied) {
            return redirect()->route('my-applications')
                ->with('error', 'You have already applied for this internship.');
        }

        // Mobile detection — same regex as ProfileController
        $isMobile = request()->header('User-Agent') &&
                    preg_match('/Mobile|Android|iPhone|iPad/i', request()->header('User-Agent'));

        return view($isMobile ? 'applications.apply-mobile' : 'applications.apply', compact('internship'));
    }

    /**
     * Show a student's own application detail (GET)
     *
     * Authorization: student may only view their own applications.
     */
    public function show(\App\Models\Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            abort(403, 'You do not have permission to view this application.');
        }

        $application->load('internship');

        // Add timeline if available
        try {
            $application->timeline = $this->timelineService->getApplicationTimeline($application);
        } catch (\Exception $e) {
            $application->timeline = ['prediction' => null];
        }

        return view('student.application-tracker', [
            'applications' => collect([$application]),
        ]);
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
     * Mobile: Detects User-Agent and serves mobile-optimised view
     */
    public function myApplications()
    {
        try {
            $applications = $this->applicationService->getUserApplications(Auth::id());

            // CRITICAL FIX: Filter out applications with null internships
            $validApplications = $applications->filter(function ($app) {
                return $app->internship !== null;
            });

            // Mobile detection — same regex as ProfileController
            $isMobile = request()->header('User-Agent') &&
                        preg_match('/Mobile|Android|iPhone|iPad/i', request()->header('User-Agent'));

            if ($isMobile) {
                $stats = $this->applicationService->getUserStats(Auth::id());

                return view('student.applications-mobile', [
                    'applications' => $validApplications,
                    'stats'        => $stats,
                ]);
            }

            // Desktop: Add timeline data to each application
            $applicationsWithTimeline = $validApplications->map(function ($app) {
                try {
                    $app->timeline = $this->timelineService->getApplicationTimeline($app);
                } catch (\Exception $e) {
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
     * Mobile-first applications view
     */
    public function myApplicationsMobile()
    {
        try {
            $applications = $this->applicationService->getUserApplications(Auth::id());
            
            // Filter out applications with null internships
            $validApplications = $applications->filter(function ($app) {
                return $app->internship !== null;
            });
            
            // Get stats
            $stats = $this->applicationService->getUserStats(Auth::id());
            
            return view('student.applications-mobile', [
                'applications' => $validApplications,
                'stats' => $stats,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('My Applications Mobile page error', [
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
