<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Internship;
use App\Services\RecruiterAnalyticsService;
use App\Services\RecruiterApplicationService;
use App\Services\RecruiterInternshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RecruiterApiController extends Controller
{
    public function __construct(
        private RecruiterAnalyticsService $analyticsService,
        private RecruiterApplicationService $applicationService,
        private RecruiterInternshipService $internshipService
    ) {}

    /**
     * GET /api/v1/recruiter/dashboard
     * Get dashboard metrics and charts
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $recruiterId = $request->user()->id;

        $stats = $this->analyticsService->getDashboardStats($recruiterId);
        $funnel = $this->analyticsService->getConversionFunnel($recruiterId);
        $avgTimeToHire = $this->analyticsService->getAverageTimeToHire($recruiterId);
        $applicationRate = $this->analyticsService->getApplicationRate($recruiterId);
        $topSkills = $this->analyticsService->getTopSkills($recruiterId);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'funnel' => $funnel,
                'avg_time_to_hire' => $avgTimeToHire,
                'application_rate' => $applicationRate,
                'top_skills' => $topSkills,
            ]
        ]);
    }

    /**
     * GET /api/v1/recruiter/jobs
     * List internships posted by this recruiter
     */
    public function jobs(Request $request): JsonResponse
    {
        $recruiterId = $request->user()->id;
        $jobs = $this->internshipService->getRecruiterInternships($recruiterId, true);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * POST /api/v1/recruiter/jobs
     * Create a new internship posting
     */
    public function storeJob(Request $request): JsonResponse
    {
        try {
            $recruiterId = $request->user()->id;
            $internship = $this->internshipService->createInternship($request->all(), $recruiterId);

            return response()->json([
                'success' => true,
                'message' => 'Internship created successfully.',
                'data' => $internship,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/recruiter/applications
     * List candidates applying to recruiter's jobs
     */
    public function applications(Request $request): JsonResponse
    {
        $recruiterId = $request->user()->id;
        $filters = $request->only(['status', 'internship_id', 'date_from', 'date_to']);

        $applications = $this->applicationService->getRecruiterApplications($recruiterId, $filters);

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }

    /**
     * GET /api/v1/recruiter/applications/{application}
     * Get candidate profile and application details
     */
    public function applicationDetails(Application $application): JsonResponse
    {
        // Ownership check
        if ($application->internship->recruiter_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this application.',
            ], 403);
        }

        $user = $application->user;
        $profile = $user->profile;
        $logs = $application->statusLogs()->with('changedBy')->get();

        $resumeUrl = $profile?->getResumeUrl() ?? null;

        // Check if there is an AI rewritten resume
        $aiVersion = \App\Models\ResumeVersion::where('user_id', $user->id)
            ->where('internship_id', $application->internship_id)
            ->where('type', 'ai_rewrite')
            ->latest()
            ->first();

        if ($aiVersion) {
            $resumeUrl = route('recruiter.applications.resume', $application->id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'application' => $application,
                'candidate' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'skills' => $profile?->skills ?? [],
                    'academic_background' => $profile?->academic_background ?? null,
                    'career_interests' => $profile?->career_interests ?? null,
                    'resume_url' => $resumeUrl,
                    'ai_version_id' => $aiVersion?->id,
                ],
                'logs' => $logs,
                'allowed_transitions' => $application->allowedTransitions(),
            ]
        ]);
    }

    /**
     * POST /api/v1/recruiter/applications/{application}/status
     * Transition application to a new state in the state machine
     */
    public function updateStatus(Request $request, Application $application): JsonResponse
    {
        try {
            $recruiterId = $request->user()->id;

            $request->validate([
                'status' => 'required|string',
            ]);

            $updated = $this->applicationService->updateApplicationStatus(
                $application,
                $request->input('status'),
                $recruiterId
            );

            return response()->json([
                'success' => true,
                'message' => 'Application status updated successfully.',
                'data' => $updated,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
