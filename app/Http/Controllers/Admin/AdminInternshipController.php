<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use Illuminate\Http\Request;

class AdminInternshipController extends Controller
{
    /**
     * Display all internships
     */
    public function index()
    {
        $internships = Internship::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.internships.index', compact('internships'));
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
}
