<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\AtsAnalyticsEvent;
use App\Models\Internship;
use App\Services\RecruitmentIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * RecruitmentIntelligenceController — Recruiter Intelligence Layer
 *
 * Endpoints:
 *   GET  /recruiter/intelligence/{internship}    → Ranked candidates dashboard
 *   GET  /recruiter/intelligence/{internship}/api → JSON data for AJAX refresh
 *   POST /recruiter/intelligence/track-view       → Track profile view analytics
 */
class RecruitmentIntelligenceController extends Controller
{
    public function __construct(
        private readonly RecruitmentIntelligenceService $intelligenceService,
    ) {}

    /**
     * Recruiter Candidate Intelligence Dashboard.
     */
    public function index(Internship $internship)
    {
        $recruiterId = Auth::id();

        // Authorization check
        if ($internship->recruiter_id !== $recruiterId) {
            abort(403, 'You do not have access to this internship.');
        }

        try {
            $data = $this->intelligenceService->getRankedCandidates($internship->id, $recruiterId);

            return view('recruiter.candidate-intelligence', array_merge($data, [
                'pageTitle' => 'Candidate Intelligence — ' . $internship->title,
            ]));

        } catch (\Exception $e) {
            Log::error('RecruitmentIntelligence: dashboard failed', [
                'internship_id' => $internship->id,
                'recruiter_id'  => $recruiterId,
                'error'         => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to load candidate intelligence. Please try again.');
        }
    }

    /**
     * AJAX: Get fresh candidate intelligence JSON.
     */
    public function apiData(Internship $internship): JsonResponse
    {
        $recruiterId = Auth::id();
        if ($internship->recruiter_id !== $recruiterId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->intelligenceService->getRankedCandidates($internship->id, $recruiterId);

            return response()->json([
                'success'    => true,
                'candidates' => array_map(fn($c) => [
                    'user_id'          => $c['user_id'],
                    'name'             => $c['name'],
                    'email'            => $c['email'],
                    'overall_score'    => $c['overall_score'],
                    'rank'             => $c['rank'],
                    'rank_tier'        => $c['rank_tier'],
                    'status'           => $c['application']?->status?->value ?? 'pending',
                    'status_label'     => $c['application']?->status?->label() ?? 'Pending',
                    'backend_match'    => $c['intelligence']?->backend_match ?? '—',
                    'project_quality'  => $c['intelligence']?->project_quality ?? '—',
                    'tech_depth'       => $c['intelligence']?->technical_depth ?? '—',
                    'missing_critical' => count($c['intelligence']?->critical_missing_skills ?? []),
                ], $data['candidates']),
                'stats'      => $data['stats'],
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Track recruiter profile view for analytics.
     */
    public function trackView(Request $request): JsonResponse
    {
        $request->validate(['application_id' => 'required|integer|exists:applications,id']);

        try {
            $this->intelligenceService->trackRecruiterView(
                $request->application_id,
                Auth::id()
            );
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * List all internships with their intelligence status.
     */
    public function internshipsList()
    {
        $recruiterId = Auth::id();

        $internships = Internship::forRecruiter($recruiterId)
            ->withCount('applications')
            ->orderByDesc('created_at')
            ->get();

        return view('recruiter.intelligence-list', compact('internships'));
    }
}
