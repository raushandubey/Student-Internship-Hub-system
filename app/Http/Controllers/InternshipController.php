<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * InternshipController
 * 
 * Handles public internship browsing and student interactions.
 */
class InternshipController extends Controller
{
    /**
     * Display public listing of internships with search and filters
     */
    public function publicIndex(Request $request)
    {
        $query = Internship::where('is_active', true);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('organization', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        // Filter by organization
        if ($request->filled('organization')) {
            $query->where('organization', 'LIKE', "%{$request->organization}%");
        }

        $internships = $query->orderBy('created_at', 'desc')->paginate(12);
        
        // Get unique locations for filter dropdown
        $locations = Internship::where('is_active', true)
            ->distinct()
            ->pluck('location')
            ->filter()
            ->values();

        return view('internships.index', compact('internships', 'locations'));
    }

    /**
     * Display a single internship
     */
    public function show(Internship $internship)
    {
        $hasApplied = false;
        
        if (Auth::check()) {
            $hasApplied = Application::where('user_id', Auth::id())
                ->where('internship_id', $internship->id)
                ->exists();
        }
        
        return view('internships.show', compact('internship', 'hasApplied'));
    }

    /**
     * Get user's bookmarked internships
     */
    public function bookmarks()
    {
        return view('student.bookmarks', ['bookmarks' => collect()]);
    }

    /**
     * Toggle bookmark for an internship
     */
    public function toggleBookmark(Internship $internship)
    {
        return back()->with('info', 'Bookmarks feature coming soon.');
    }

    /**
     * Search skills for autocomplete
     */
    public function searchSkills(Request $request)
    {
        $query = $request->get('q', '');
        
        $skills = Internship::where('is_active', true)
            ->get()
            ->pluck('required_skills')
            ->flatten()
            ->unique()
            ->filter(function($skill) use ($query) {
                return stripos($skill, $query) !== false;
            })
            ->values()
            ->take(10);

        return response()->json($skills);
    }

    /**
     * Search organizations for autocomplete
     */
    public function searchOrganizations(Request $request)
    {
        $query = $request->get('q', '');
        
        $organizations = Internship::where('is_active', true)
            ->where('organization', 'LIKE', "%{$query}%")
            ->distinct()
            ->pluck('organization')
            ->take(10);

        return response()->json($organizations);
    }

    /**
     * Search locations for autocomplete
     */
    public function searchLocations(Request $request)
    {
        $query = $request->get('q', '');
        
        $locations = Internship::where('is_active', true)
            ->where('location', 'LIKE', "%{$query}%")
            ->distinct()
            ->pluck('location')
            ->take(10);

        return response()->json($locations);
    }
}
