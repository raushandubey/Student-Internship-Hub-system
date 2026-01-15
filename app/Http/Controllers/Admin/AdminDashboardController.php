<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Internship;
use App\Models\Application;

class AdminDashboardController extends Controller
{
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
            'pending_applications' => Application::where('status', 'pending')->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
