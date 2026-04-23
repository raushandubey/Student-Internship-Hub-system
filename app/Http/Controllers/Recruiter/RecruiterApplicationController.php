<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Internship;
use App\Services\RecruiterApplicationService;
use Illuminate\Http\Request;

class RecruiterApplicationController extends Controller
{
    public function __construct(
        private RecruiterApplicationService $applicationService
    ) {}

    public function index(Request $request)
    {
        $recruiterId = auth()->id();

        $filters = $request->only(['status', 'internship_id', 'date_from', 'date_to']);
        $applications = $this->applicationService->getRecruiterApplications($recruiterId, $filters);

        $internships = Internship::forRecruiter($recruiterId)->get(['id', 'title']);

        // Group by internship title
        $grouped = $applications->groupBy(fn($a) => $a->internship->title ?? 'Unknown');

        return view('recruiter.applications.index', compact('grouped', 'internships', 'filters'));
    }

    public function updateStatus(Request $request, Application $application)
    {
        try {
            // Ownership check first
            if ($application->internship->recruiter_id !== auth()->id()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to update this application.',
                    ], 403);
                }
                abort(403, 'Unauthorized to update this application.');
            }

            // Validate request
            $validated = $request->validate(['status' => 'required|string']);

            // Ensure JSON response for AJAX requests
            if (!$request->expectsJson() && $request->ajax()) {
                $request->headers->set('Accept', 'application/json');
            }

            $updated = $this->applicationService->updateApplicationStatus(
                $application,
                $validated['status'],
                auth()->id()
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'status' => $updated->status->value,
                    'status_label' => $updated->status->label(),
                ]);
            }

            return back()->with('success', 'Application status updated.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
                ], 422);
            }
            return back()->withErrors($e->validator)->withInput();
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Status update failed', [
                'application_id' => $application->id,
                'requested_status' => $request->input('status'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'application_ids'   => 'required|array|min:1',
            'application_ids.*' => 'integer|exists:applications,id',
            'status'            => 'required|string',
        ]);

        $count = $this->applicationService->bulkUpdateStatus(
            $request->application_ids,
            $request->status,
            auth()->id()
        );

        return back()->with('success', "{$count} application(s) updated successfully.");
    }

    public function history(Application $application)
    {
        // Ownership check
        if ($application->internship->recruiter_id !== auth()->id()) {
            abort(403);
        }

        $logs = $application->statusLogs()->with('changedBy')->get();

        if (request()->expectsJson()) {
            return response()->json(['logs' => $logs]);
        }

        return view('recruiter.applications.history', compact('application', 'logs'));
    }

    public function getProfile(Application $application)
    {
        // Ownership check
        if ($application->internship->recruiter_id !== auth()->id()) {
            abort(403);
        }

        $profile = $application->user->profile;
        $user = $application->user;

        return response()->json([
            'name'              => $user->name,
            'email'             => $user->email,
            'skills'            => $profile?->skills ?? [],
            'academic_background' => $profile?->academic_background ?? null,
            'career_interests'  => $profile?->career_interests ?? null,
            'resume_url'        => $profile?->getResumeUrl() ?? null,
        ]);
    }
}
