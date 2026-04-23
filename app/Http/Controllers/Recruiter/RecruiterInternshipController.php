<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Services\RecruiterInternshipService;
use Illuminate\Http\Request;

class RecruiterInternshipController extends Controller
{
    public function __construct(
        private RecruiterInternshipService $internshipService
    ) {}

    public function index()
    {
        $internships = $this->internshipService->getRecruiterInternships(auth()->id());
        return view('recruiter.internships.index', compact('internships'));
    }

    public function create()
    {
        return view('recruiter.internships.form', ['internship' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'organization'    => 'required|string|max:255',
            'required_skills' => 'required|array|min:1',
            'required_skills.*' => 'string|max:100',
            'duration'        => 'required|string|max:255',
            'location'        => 'required|string|max:255',
            'description'     => 'nullable|string|max:5000',
        ]);

        $this->internshipService->createInternship($data, auth()->id());

        return redirect()->route('recruiter.internships.index')
            ->with('success', 'Internship created successfully.');
    }

    public function edit(Internship $internship)
    {
        // Ownership check
        if ($internship->recruiter_id !== auth()->id()) {
            abort(403);
        }

        return view('recruiter.internships.form', compact('internship'));
    }

    public function update(Request $request, Internship $internship)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'organization'    => 'required|string|max:255',
            'required_skills' => 'required|array|min:1',
            'required_skills.*' => 'string|max:100',
            'duration'        => 'required|string|max:255',
            'location'        => 'required|string|max:255',
            'description'     => 'nullable|string|max:5000',
            'is_active'       => 'boolean',
        ]);

        $this->internshipService->updateInternship($internship, $data, auth()->id());

        return redirect()->route('recruiter.internships.index')
            ->with('success', 'Internship updated successfully.');
    }

    public function destroy(Internship $internship)
    {
        $this->internshipService->deleteInternship($internship, auth()->id());

        return redirect()->route('recruiter.internships.index')
            ->with('success', 'Internship deleted successfully.');
    }
}
