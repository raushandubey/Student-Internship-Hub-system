<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InternshipsExport;
use App\Imports\InternshipsImport;

class InternshipController extends Controller
{
    /**
     * Display a listing of internships with search, filter, and sort
     */
    public function index(Request $request)
    {
        $query = Internship::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('organization', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereJsonContains('required_skills', $search);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        // Filter by organization
        if ($request->filled('organization')) {
            $query->where('organization', 'LIKE', "%{$request->organization}%");
                ->exists();
        }
        
        return view('internships.show', compact('internship', 'hasApplied'));
    }
}
