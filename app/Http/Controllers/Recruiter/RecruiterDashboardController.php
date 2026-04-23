<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Services\RecruiterAnalyticsService;
use Illuminate\Http\Request;

class RecruiterDashboardController extends Controller
{
    public function __construct(
        private RecruiterAnalyticsService $analyticsService
    ) {}

    public function index(Request $request)
    {
        $recruiterId = auth()->id();

        $stats = $this->analyticsService->getDashboardStats($recruiterId);
        $funnel = $this->analyticsService->getConversionFunnel($recruiterId);
        $avgTimeToHire = $this->analyticsService->getAverageTimeToHire($recruiterId);
        $applicationRate = $this->analyticsService->getApplicationRate($recruiterId);
        $topSkills = $this->analyticsService->getTopSkills($recruiterId);

        return view('recruiter.dashboard', compact(
            'stats',
            'funnel',
            'avgTimeToHire',
            'applicationRate',
            'topSkills'
        ));
    }
}
