<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Services\ResumeOptimizationService;
use App\Services\ResumePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ResumeOptimizerController
 *
 * Thin API controller — all heavy logic in service layer.
 *
 * Endpoints:
 *   GET  /resume-optimizer/score/{internship}     → full ATS analysis
 *   POST /resume-optimizer/rewrite/{internship}   → AI rewrite + PDF ready
 *   GET  /resume-optimizer/download/{internship}  → PDF download
 *   GET  /resume-optimizer/chatbot/analyse        → chatbot integration
 *   GET  /resume-optimizer/internships            → active internships list
 */
class ResumeOptimizerController extends Controller
{
    public function __construct(
        private readonly ResumeOptimizationService $optimizer,
        private readonly ResumePdfService          $pdfService,
    ) {}

    /* ------------------------------------------------------------------ */
    /*  Score Endpoint                                                      */
    /* ------------------------------------------------------------------ */

    public function score(Internship $internship): JsonResponse
    {
        try {
            $user   = Auth::user();
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
                'data'    => [
                    'score'             => $result['score'],
                    'skill_match'       => $result['skill_match'],
                    'keyword_score'     => $result['keyword_score'],
                    'format_score'      => $result['format_score'],
                    'exp_score'         => $result['exp_score'] ?? 0,
                    'proj_score'        => $result['proj_score'] ?? 0,
                    'intrinsic_score'   => $result['intrinsic_score'] ?? 0,
                    'tier'              => $result['tier'],
                    'gate_message'      => $result['gate_message'],
                    'badge_class'       => $result['badge_class'],
                    'matching_skills'   => $result['matching_skills'],
                    'missing_skills'    => $result['missing_skills'],
                    'issues'            => $result['issues'],
                    'strengths'         => $result['strengths'],
                    'weak_areas'        => $result['weak_areas'] ?? [],
                    'recommendations'   => $result['recommendations'] ?? [],
                    'role_category'     => $result['role_category'] ?? 'general',
                    'quality_tier'      => $result['quality_tier'] ?? 'average',
                    'quality_score'     => $result['quality_score'] ?? 0,
                    'locked_sections'   => $result['locked_sections'] ?? [],
                    'preservation_mode' => $result['preservation_mode'] ?? false,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ResumeOptimizer: Critical score failure', [
                'user_id'       => Auth::id(),
                'internship_id' => $internship->id,
                'error'         => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'stage_failed' => 'CONTROLLER_CRASH',
                'error'   => 'A critical error occurred while processing your request. Our engineers have been notified.',
            ], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Rewrite Endpoint                                                    */
    /* ------------------------------------------------------------------ */

    public function rewrite(Internship $internship): JsonResponse
    {
        try {
            $user   = Auth::user();
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
                'data'    => [
                    'before_score'      => $result['before_score'],
                    'after_score'       => $result['after_score'],
                    'improvements'      => $result['improvements'],
                    'version_id'        => $result['version_id'],
                    'rewritten_text'    => $result['rewritten_text'],
                    'ai_disabled'       => $result['ai_disabled'] ?? false,
                    'role_category'     => $result['role_category'] ?? 'general',
                    'quality_tier'      => $result['quality_tier'] ?? 'unknown',
                    'preservation_mode' => $result['preservation_mode'] ?? false,
                    'rewrite_mode'      => $result['rewrite_mode'] ?? 'unknown',
                    'locked_sections'   => $result['locked_sections'] ?? [],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ResumeOptimizer: Critical rewrite failure', [
                'user_id'       => Auth::id(),
                'internship_id' => $internship->id,
                'error'         => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'stage_failed' => 'CONTROLLER_CRASH',
                'error'   => 'A fatal error occurred during optimization. Please try again later.',
            ], 500);
        }
    }

    public function health(): JsonResponse
    {
        try {
            $health = $this->optimizer->checkPipelineHealth();
            return response()->json($health);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'critical',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storageDebug(): JsonResponse
    {
        return response()->json(
            $this->optimizer->debugResumeAccess(Auth::user())
        );
    }

    /* ------------------------------------------------------------------ */
    /*  PDF Download Endpoint                                               */
    /* ------------------------------------------------------------------ */

    public function downloadPdf(Request $request, Internship $internship): Response
    {
        try {
            $user      = Auth::user();
            $versionId = $request->integer('version_id') ?: null;
            return $this->pdfService->downloadPdf($user, $internship, $versionId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'No AI-rewritten resume found. Please click "Improve Resume" first.');

        } catch (\Exception $e) {
            Log::error('ResumeOptimizer: PDF download failed', [
                'user_id'       => Auth::id(),
                'internship_id' => $internship->id,
                'error'         => $e->getMessage(),
            ]);
            abort(500, 'PDF generation failed. Please try again.');
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Chatbot Integration Endpoint                                        */
    /* ------------------------------------------------------------------ */

    public function chatbotAnalyse(Request $request): JsonResponse
    {
        $request->validate([
            'internship_id' => 'required|integer|exists:internships,id',
        ]);

        try {
            $user       = Auth::user();
            $internship = Internship::findOrFail($request->internship_id);
            $result     = $this->optimizer->analyseResume($user, $internship);

            $roleLabel = ucfirst($result['role_category'] ?? 'General');
            $summary   = "📊 **ATS Resume Analysis** for **{$internship->title}**\n\n"
                       . "• Role Category: {$roleLabel} Developer\n"
                       . "• Overall Match: {$result['score']}%\n"
                       . "• Skill Match: {$result['skill_match']}%\n"
                       . "• Keyword Coverage: {$result['keyword_score']}%\n\n";

            if (!empty($result['missing_skills'])) {
                $summary .= "❌ **Missing Skills:** " . implode(', ', array_slice($result['missing_skills'], 0, 4)) . "\n\n";
            }

            if (!empty($result['weak_areas'])) {
                $summary .= "⚠️ **Weak Areas:**\n";
                foreach (array_slice($result['weak_areas'], 0, 3) as $area) {
                    $summary .= "  • {$area}\n";
                }
                $summary .= "\n";
            }

            $summary .= $result['gate_message'];

            return response()->json([
                'success'         => true,
                'chatbot_summary' => $summary,
                'score'           => $result['score'],
                'tier'            => $result['tier'],
                'role_category'   => $result['role_category'] ?? 'general',
                'internship_id'   => $internship->id,
                'internship_name' => $internship->title,
            ]);

        } catch (\Exception $e) {
            Log::error('ResumeOptimizer: chatbot endpoint failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Unable to analyse resume at this time.',
            ], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Active Internships List (chatbot job picker)                        */
    /* ------------------------------------------------------------------ */

    public function internshipsList(): JsonResponse
    {
        $internships = Internship::where('is_active', true)
            ->select('id', 'title', 'organization', 'location')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success'      => true,
            'internships'  => $internships,
        ]);
    }
}
