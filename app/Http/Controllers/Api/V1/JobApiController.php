<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InternshipResource;
use App\Models\Internship;
use App\Services\ApplicationService;
use App\Services\InternshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobApiController extends Controller
{
    protected ApplicationService $applicationService;
    protected InternshipService $internshipService;

    public function __construct(ApplicationService $applicationService, InternshipService $internshipService)
    {
        $this->applicationService = $applicationService;
        $this->internshipService = $internshipService;
    }

    /**
     * GET /api/v1/jobs
     * List active internships with optional search filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Internship::where('is_active', true);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('organization', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $internships = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => InternshipResource::collection($internships),
            'meta' => [
                'current_page' => $internships->currentPage(),
                'last_page' => $internships->lastPage(),
                'per_page' => $internships->perPage(),
                'total' => $internships->total(),
            ]
        ]);
    }

    /**
     * GET /api/v1/jobs/{internship}
     * Get internship details
     */
    public function show(Internship $internship): JsonResponse
    {
        if (!$internship->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This internship is no longer active.'
            ], 404);
        }

        $user = Auth::user();
        $hasApplied = false;

        if ($user && $user->isStudent()) {
            $hasApplied = $this->applicationService->hasApplied($user->id, $internship->id);
        }

        return response()->json([
            'success' => true,
            'data' => new InternshipResource($internship),
            'has_applied' => $hasApplied,
        ]);
    }

    /**
     * POST /api/v1/jobs/{internship}/apply
     * Apply for internship
     */
    public function apply(Request $request, Internship $internship): JsonResponse
    {
        try {
            $user = $request->user();
            $resumeVersionId = $request->input('resume_version_id');

            $result = $this->applicationService->submitApplication($user, $internship, $resumeVersionId);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'application_id' => $result['application']->id,
            ]);
        } catch (\App\Exceptions\BusinessRuleViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\App\Exceptions\UnauthorizedActionException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting your application.'
            ], 500);
        }
    }
}
