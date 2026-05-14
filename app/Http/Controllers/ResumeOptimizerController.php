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
 * Thin JSON API controller for the Resume Optimizer feature.
 * All heavy logic lives in ResumeOptimizationService.
 *
 * Endpoints:
 *   GET  /resume-optimizer/score/{internship}     → Run scoring
 *   POST /resume-optimizer/rewrite/{internship}   → AI rewrite
 *   GET  /resume-optimizer/chatbot/analyse        → Chatbot integration
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

    /**
     * Analyse the user's resume against a specific internship.
     * Returns a JSON score breakdown used by the Apply modal.
     */
    public function score(Internship $internship): JsonResponse
    {
        try {
            $user   = Auth::user();
            $result = $this->optimizer->analyseResume($user, $internship);

            return response()->json([
                'success'  => true,
                'data'     => [
                    'score'           => $result['score'],
                    'skill_match'     => $result['skill_match'],
                    'keyword_score'   => $result['keyword_score'],
                    'format_score'    => $result['format_score'],
                    'tier'            => $result['tier'],
                    'gate_message'    => $result['gate_message'],
                    'badge_class'     => $result['badge_class'],
                    'matching_skills' => $result['matching_skills'],
                    'missing_skills'  => $result['missing_skills'],
                    'issues'          => $result['issues'],
                    'strengths'       => $result['strengths'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ResumeOptimizer: score endpoint failed', [
                'user_id'       => Auth::id(),
                'internship_id' => $internship->id,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Unable to analyse resume. Please try again.',
            ], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Rewrite Endpoint                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * AI-rewrite the user's resume for the given internship.
     * Returns before/after scores and the rewritten text.
     */
    public function rewrite(Internship $internship): JsonResponse
    {
        try {
            $user   = Auth::user();
            $result = $this->optimizer->rewriteResume($user, $internship);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error'   => $result['error'] ?? 'Rewrite failed.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'before_score'    => $result['before_score'],
                    'after_score'     => $result['after_score'],
                    'improvements'    => $result['improvements'],
                    'version_id'      => $result['version_id'],
                    'rewritten_text'  => $result['rewritten_text'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ResumeOptimizer: rewrite endpoint failed', [
                'user_id'       => Auth::id(),
                'internship_id' => $internship->id,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'AI rewrite failed. Please try again later.',
            ], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  PDF Download Endpoint                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Generate and download a professionally formatted PDF of the optimised resume.
     *
     * GET /resume-optimizer/download/{internship}?version_id={id}
     *
     * Uses the latest AI-rewritten ResumeVersion for this user+job.
     * Returns a PDF file download response.
     */
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

    /**
     * Called by the chatbot when user says "improve resume" or "optimize resume".
     * Requires internship_id as query param.
     */
    public function chatbotAnalyse(Request $request): JsonResponse
    {
        $request->validate([
            'internship_id' => 'required|integer|exists:internships,id',
        ]);

        try {
            $user       = Auth::user();
            $internship = Internship::findOrFail($request->internship_id);
            $result     = $this->optimizer->analyseResume($user, $internship);

            // Chatbot-friendly summary
            $summary = "📊 Resume Score for **{$internship->title}**:\n\n"
                . "• Overall Match: {$result['score']}%\n"
                . "• Skill Match: {$result['skill_match']}%\n"
                . "• Keyword Coverage: {$result['keyword_score']}%\n\n";

            if (!empty($result['missing_skills'])) {
                $summary .= "❌ Missing Skills: " . implode(', ', array_slice($result['missing_skills'], 0, 4)) . "\n\n";
            }

            if (!empty($result['issues'])) {
                $summary .= "⚠️ Issues Found:\n";
                foreach (array_slice($result['issues'], 0, 3) as $issue) {
                    $summary .= "  • {$issue}\n";
                }
                $summary .= "\n";
            }

            $summary .= $result['gate_message'];

            return response()->json([
                'success'         => true,
                'chatbot_summary' => $summary,
                'score'           => $result['score'],
                'tier'            => $result['tier'],
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
    /*  Active Internships List (for chatbot job picker)                   */
    /* ------------------------------------------------------------------ */

    /**
     * Return active internships for the chatbot's job-selection dropdown.
     */
    public function internshipsList(): JsonResponse
    {
        $internships = Internship::where('is_active', true)
            ->select('id', 'title', 'organization', 'location')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success'     => true,
            'internships' => $internships,
        ]);
    }
}
