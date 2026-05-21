<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Services\ResumeOptimizationService;
use App\Services\ResumePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ResumeOptimizerApiController extends Controller
{
    private ResumeOptimizationService $optimizer;
    private ResumePdfService $pdfService;

    public function __construct(ResumeOptimizationService $optimizer, ResumePdfService $pdfService)
    {
        $this->optimizer = $optimizer;
        $this->pdfService = $pdfService;
    }

    /**
     * GET /api/v1/resume-optimizer/score/{internship}
     * Get ATS analysis & score for a specific internship
     */
    public function score(Internship $internship): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->optimizer->analyseResume($user, $internship);

            if (isset($result['success']) && $result['success'] === false) {
                return response()->json([
                    'success'      => false,
                    'stage_failed' => $result['stage_failed'] ?? 'UNKNOWN',
                    'error'        => $result['issues'][0] ?? 'Unable to analyse resume.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('API ResumeOptimizer: Critical score failure', [
                'user_id'       => Auth::id(),
                'internship_id' => $internship->id,
                'error'         => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'stage_failed' => 'CONTROLLER_CRASH',
                'error'   => 'A critical error occurred while scoring the resume.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/resume-optimizer/rewrite/{internship}
     * Trigger AI optimization and return results
     */
    public function rewrite(Internship $internship): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->optimizer->rewriteResume($user, $internship);

            if (!$result['success']) {
                return response()->json([
                    'success'      => false,
                    'stage_failed' => $result['stage_failed'] ?? 'AI_ORCHESTRATION_FAILURE',
                    'error'        => $result['error'] ?? 'Rewrite failed.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('API ResumeOptimizer: Critical rewrite failure', [
                'user_id'       => Auth::id(),
                'internship_id' => $internship->id,
                'error'         => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'stage_failed' => 'CONTROLLER_CRASH',
                'error'   => 'A fatal error occurred during optimization.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/resume-optimizer/download/{internship}
     * Download the optimized resume PDF
     */
    public function download(Request $request, Internship $internship)
    {
        try {
            $user = Auth::user();
            $versionId = $request->integer('version_id') ?: null;
            return $this->pdfService->downloadPdf($user, $internship, $versionId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'No AI-rewritten resume found. Please optimize first.');
        } catch (\Throwable $e) {
            Log::error('API ResumeOptimizer: PDF download failed', [
                'user_id'       => Auth::id(),
                'internship_id' => $internship->id,
                'error'         => $e->getMessage(),
            ]);
            abort(500, 'PDF generation failed.');
        }
    }

    /**
     * GET /api/v1/resume-optimizer/internships
     * Return list of active internships for the resume job selector
     */
    public function internships(): JsonResponse
    {
        $internships = Internship::where('is_active', true)
            ->select('id', 'title', 'organization', 'location')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'internships' => $internships,
        ]);
    }
}
