<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Internship;
use App\Models\Application;
use App\Enums\ApplicationStatus;
use App\Services\RecruiterAnalyticsService;

class AdminDashboardController extends Controller
{
    protected RecruiterAnalyticsService $recruiterAnalyticsService;

    public function __construct(RecruiterAnalyticsService $recruiterAnalyticsService)
    {
        $this->recruiterAnalyticsService = $recruiterAnalyticsService;
    }

    /**
     * Display admin dashboard
     */
    public function index()
    {
        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'total_internships' => Internship::count(),
            'active_internships' => Internship::where('is_active', true)->count(),
            'total_applications' => Application::count(),
            'pending_applications' => Application::where('status', ApplicationStatus::PENDING)->count(),
        ];

        $recruiterStats = $this->recruiterAnalyticsService->getSystemWideRecruiterStats();

        return view('admin.dashboard', array_merge(compact('stats'), $recruiterStats));
    }
}
