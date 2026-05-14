<?php

namespace App\Http\Controllers;

use App\Services\RecruitmentIntelligenceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * CandidateInsightController — Student Intelligence Dashboard
 */
class CandidateInsightController extends Controller
{
    public function __construct(
        private readonly RecruitmentIntelligenceService $intelligenceService,
    ) {}

    /**
     * Candidate Profile Intelligence Dashboard.
     */
    public function dashboard()
    {
        try {
            $user = Auth::user();
            $data = $this->intelligenceService->getCandidateInsightDashboard($user);
            return view('student.profile-intelligence', $data);
        } catch (\Exception $e) {
            Log::error('CandidateInsight: dashboard failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);
            return back()->with('error', 'Unable to load your intelligence dashboard.');
        }
    }
}
