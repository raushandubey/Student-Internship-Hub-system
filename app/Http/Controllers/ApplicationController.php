<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    /**
     * Apply to an internship
     * 
     * Single source of truth: Creates record in applications table
     */
    public function apply(Request $request, Internship $internship)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to apply for internships.');
        }

        $user = Auth::user();

        // Check if user is a student
        if ($user->role !== 'student') {
            return back()->with('error', 'Only students can apply for internships.');
        }

        // Check if internship is active
        if (!$internship->is_active) {
            return back()->with('error', 'This internship is no longer accepting applications.');
        }

        // Check if already applied (prevent duplicates)
        $existingApplication = Application::where('user_id', $user->id)
            ->where('internship_id', $internship->id)
            ->first();

        if ($existingApplication) {
            return back()->with('error', 'You have already applied to this internship.');
        }

        // Create application with correct user_id and internship_id
        try {
            Application::create([
                'user_id' => $user->id,
                'internship_id' => $internship->id,
                'status' => 'pending',
            ]);

            return back()->with('success', 'Application submitted successfully! You will be notified once reviewed.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit application. Please try again.');
        }
    }

    /**
     * View student's own applications (Application Tracker)
     * 
     * Single source of truth: Fetches from applications table by auth()->id()
     */
    public function myApplications()
    {
        // Fetch all applications for the authenticated user only
        // Using Eloquent relationship to get internship details
        $applications = Application::with('internship')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.application-tracker', compact('applications'));
    }

    /**
     * Cancel application
     */
    public function cancel(Application $application)
    {
        // Check if the application belongs to the authenticated user
        if ($application->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized action.');
        }

        // Only allow cancellation if status is pending
        if ($application->status !== 'pending') {
            return back()->with('error', 'Cannot cancel an application that has been reviewed.');
        }

        $application->delete();

        return back()->with('success', 'Application cancelled successfully.');
    }
}
