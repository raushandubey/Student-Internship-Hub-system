<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Services\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ApplicationApiController (API v1)
 * 
 * RESTful API for applications.
 * 
 * Why API Versioning?
 * - Backward compatibility
 * - Gradual migration for clients
 * - Interview: "I implemented versioned APIs for future mobile app support"
 * 
 * Note: This is for internal use / demonstration only.
 * No external authentication (OAuth, etc.) is implemented.
 */
class ApplicationApiController extends Controller
{
    protected ApplicationService $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    /**
     * GET /api/v1/applications
     * List user's applications
     */
    public function index(Request $request)
    {
        $applications = $this->applicationService->getUserApplications(Auth::id());

        return ApplicationResource::collection($applications);
    }

    /**
     * GET /api/v1/applications/{id}
     * Get single application
     */
    public function show(Application $application)
    {
        $this->authorize('view', $application);

        return new ApplicationResource($application->load(['internship', 'user']));
    }

    /**
     * GET /api/v1/applications/{id}/history
     * Get application status history
     */
    public function history(Application $application)
    {
        $this->authorize('view', $application);

        $history = $this->applicationService->getStatusHistory($application);

        return response()->json([
            'application_id' => $application->id,
            'history' => $history->map(fn($log) => [
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'changed_by' => $log->changedBy?->name ?? 'System',
                'actor_type' => $log->actor_type,
                'notes' => $log->notes,
                'timestamp' => $log->created_at->toISOString(),
            ])
        ]);
    }

    /**
     * GET /api/v1/applications/stats
     * Get user's application statistics
     */
    public function stats()
    {
        $stats = $this->applicationService->getUserStats(Auth::id());

        return response()->json([
            'stats' => $stats
        ]);
    }
}
