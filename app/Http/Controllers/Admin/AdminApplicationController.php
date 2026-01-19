<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Enums\ApplicationStatus;
use App\Services\ApplicationService;
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

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    /**
     * Display all applications
     */
    public function index()
    {
        $applications = Application::with(['user', 'internship'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.applications.index', compact('applications'));
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
            'status' => 'required|in:pending,approved,rejected,under_review,shortlisted,interview_scheduled',
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
}
