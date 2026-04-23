<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Enums\ApplicationStatus;
use App\Services\ApplicationService;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AdminApplicationController
 * 
 * Thin controller for admin application management.
 * Delegates business logic to ApplicationService.
 * 
 * Architecture: Controller → Service → Model → Events
 */
class AdminApplicationController extends Controller
{
    protected ApplicationService $applicationService;
    protected ProfileService $profileService;

    public function __construct(
        ApplicationService $applicationService,
        ProfileService $profileService
    ) {
        $this->applicationService = $applicationService;
        $this->profileService = $profileService;
    }

    /**
     * Display all applications with optional internship source filtering
     * Requirements: 7.1, 7.2, 7.3
     */
    public function index(Request $request)
    {
        $query = Application::with(['user', 'internship', 'internship.recruiter'])
            ->orderBy('created_at', 'desc');

        $source = $request->input('source', 'all');

        if ($source === 'recruiter-posted') {
            $query->whereHas('internship', function ($q) {
                $q->whereNotNull('recruiter_id');
            });
        } elseif ($source === 'admin-posted') {
            $query->whereHas('internship', function ($q) {
                $q->whereNull('recruiter_id');
            });
        }

        $applications = $query->paginate(20)->withQueryString();

        return view('admin.applications.index', compact('applications', 'source'));
    }

    /**
     * Show application detail with recruiter context
     * Requirements: 7.4, 7.5
     */
    public function show(Application $application)
    {
        $application->load([
            'user',
            'internship',
            'internship.recruiter',
            'internship.recruiter.recruiterProfile',
            'statusLogs',
        ]);

        $recruiter = $application->internship?->recruiter;

        return view('admin.applications.show', compact('application', 'recruiter'));
    }

    /**
     * Update application status via service layer
     * 
     * Phase 9: Exception handling delegated to global handler
     * 
     * Uses state machine to validate transitions.
     * Logs all changes to application_status_logs.
     * Fires ApplicationStatusChanged event for async processing.
     */
    public function updateStatus(Request $request, Application $application)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,under_review,shortlisted,interview_scheduled,approved,rejected',
        ]);

        try {
            // Convert string to enum
            $newStatus = ApplicationStatus::from($validated['status']);

            // Use service layer for business logic
            $result = $this->applicationService->updateStatus(
                $application,
                $newStatus,
                Auth::id(),
                'admin'
            );

            return redirect()->route('admin.applications.index')
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            // Global handler will log and format the error
            // InvalidStateTransitionException will show allowed transitions
            return redirect()->route('admin.applications.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * View application status history (JSON for modal)
     */
    public function history(Application $application)
    {
        $history = $application->statusLogs()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'from_status' => $log->from_status ? ucfirst(str_replace('_', ' ', $log->from_status)) : null,
                    'to_status' => ucfirst(str_replace('_', ' ', $log->to_status)),
                    'actor_type' => ucfirst($log->actor_type),
                    'notes' => $log->notes,
                    'created_at' => $log->created_at->format('M d, Y H:i'),
                ];
            });

        return response()->json($history);
    }

    /**
     * View email logs
     */
    public function emailLogs()
    {
        $emails = \App\Models\EmailLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.email-logs', compact('emails'));
    }

    /**
     * Get candidate profile data for modal display
     * 
     * Retrieves complete profile information for a candidate
     * associated with an application. Used by the admin profile
     * viewer modal to display candidate details.
     * 
     * @param Application $application
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile(Application $application)
    {
        try {
            $profileData = $this->profileService->getProfileForAdmin(
                $application->user_id
            );
            
            return response()->json([
                'success' => true,
                'data' => $profileData
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Profile fetch failed', [
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to load profile data'
            ], 500);
        }
    }
}
