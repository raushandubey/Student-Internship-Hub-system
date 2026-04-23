<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Internship;
use App\Events\RecruiterInternshipDeactivated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminInternshipController extends Controller
{
    /**
     * Display all internships with optional recruiter filtering
     * Requirements: 6.1, 6.2, 6.3
     */
    public function index(Request $request)
    {
        $query = Internship::with('recruiter')->orderBy('created_at', 'desc');

        $postedBy = $request->input('posted_by', 'all');

        if ($postedBy === 'recruiter') {
            $query->whereNotNull('recruiter_id');
        } elseif ($postedBy === 'admin') {
            $query->whereNull('recruiter_id');
        }

        $internships = $query->paginate(20)->withQueryString();

        return view('admin.internships.index', compact('internships', 'postedBy'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.internships.create');
    }

    /**
     * Store new internship
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'description' => 'required|string',
            'required_skills' => 'required|string',
            'duration' => 'required|string|max:100',
            'location' => 'required|string|max:255',
            'work_type' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
        ]);

        // Convert comma-separated skills to array
        $validated['required_skills'] = array_map('trim', explode(',', $validated['required_skills']));
        $validated['is_active'] = true;

        Internship::create($validated);

        return redirect()->route('admin.internships.index')
            ->with('success', 'Internship created successfully!');
    }

    /**
     * Show internship details including deactivation info
     * Requirements: 20.5
     */
    public function show(Internship $internship)
    {
        $internship->load(['recruiter', 'deactivatedBy', 'applications.user']);

        return view('admin.internships.show', compact('internship'));
    }

    /**
     * Show edit form
     */
    public function edit(Internship $internship)
    {
        return view('admin.internships.edit', compact('internship'));
    }

    /**
     * Update internship
     */
    public function update(Request $request, Internship $internship)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'description' => 'required|string',
            'required_skills' => 'required|string',
            'duration' => 'required|string|max:100',
            'location' => 'required|string|max:255',
            'work_type' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
        ]);

        // Convert comma-separated skills to array
        $validated['required_skills'] = array_map('trim', explode(',', $validated['required_skills']));

        $internship->update($validated);

        return redirect()->route('admin.internships.index')
            ->with('success', 'Internship updated successfully!');
    }

    /**
     * Delete internship
     */
    public function destroy(Internship $internship)
    {
        $internship->delete();

        return redirect()->route('admin.internships.index')
            ->with('success', 'Internship deleted successfully!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Internship $internship)
    {
        $internship->update(['is_active' => !$internship->is_active]);

        $status = $internship->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.internships.index')
            ->with('success', "Internship {$status} successfully!");
    }

    /**
     * Deactivate a recruiter-posted internship with reason
     * Requirements: 6.4, 6.6, 20.1, 20.2, 20.3, 20.4
     */
    public function deactivateRecruiterInternship(Request $request, Internship $internship)
    {
        $request->validate([
            'deactivation_reason' => 'required|string|max:1000',
        ]);

        // Validate internship belongs to a recruiter
        if (is_null($internship->recruiter_id)) {
            return redirect()->back()
                ->with('error', 'This internship was not posted by a recruiter.');
        }

        $internship->update([
            'is_active' => false,
            'deactivation_reason' => $request->input('deactivation_reason'),
            'deactivated_by' => Auth::id(),
            'deactivated_at' => now(),
        ]);

        // Create audit log entry
        AdminAuditLog::create([
            'admin_user_id' => Auth::id(),
            'action_type' => AdminAuditLog::INTERNSHIP_DEACTIVATED,
            'target_recruiter_id' => $internship->recruiter_id,
            'reason' => $request->input('deactivation_reason'),
            'ip_address' => $request->ip(),
        ]);

        // Dispatch event to notify recruiter
        $internship->load('recruiter');
        event(new RecruiterInternshipDeactivated(
            $internship,
            $internship->recruiter,
            $request->input('deactivation_reason')
        ));

        return redirect()->route('admin.internships.show', $internship)
            ->with('success', 'Internship deactivated and recruiter notified.');
    }
}
