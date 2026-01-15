<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class AdminApplicationController extends Controller
{
    /**
     * Display all applications
     */
    public function index()
    {
        $applications = Application::with(['user', 'internship'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.applications.index', compact('applications'));
    }

    /**
     * Update application status
     */
    public function updateStatus(Request $request, Application $application)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $application->update($validated);

        return redirect()->route('admin.applications.index')
            ->with('success', 'Application status updated successfully!');
    }
}
