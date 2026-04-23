<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Services\RecruiterAnalyticsService;

class RecruiterAnalyticsController extends Controller
{
    public function __construct(
        private RecruiterAnalyticsService $analyticsService
    ) {}

    public function index()
    {
        $recruiterId = auth()->id();

        $funnel = $this->analyticsService->getConversionFunnel($recruiterId);
        $avgTimeToHire = $this->analyticsService->getAverageTimeToHire($recruiterId);
        $applicationRate = $this->analyticsService->getApplicationRate($recruiterId);
        $topSkills = $this->analyticsService->getTopSkills($recruiterId);

        return view('recruiter.analytics', compact(
            'funnel',
            'avgTimeToHire',
            'applicationRate',
            'topSkills'
        ));
    }
}
