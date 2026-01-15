<?php
// app/Http/Controllers/Admin/AdminController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\User;
use App\Models\AdminUser;
use App\Models\RecommendationFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Admin Dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_internships' => Internship::count(),
            'active_internships' => Internship::where('is_active', true)->count(),
            'total_users' => User::count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'featured_internships' => Internship::where('is_featured', true)->count(),
            'total_applications' => Internship::sum('applications_count'),
        ];

        // Recent activities
        $recentInternships = Internship::with('creator')
            ->latest()
            ->limit(5)
            ->get();

        $recentUsers = User::latest()
            ->limit(5)
            ->get();

        // Chart data
        $internshipsChart = $this->getInternshipsChartData();
        $usersChart = $this->getUsersChartData();
        $popularLocations = $this->getPopularLocations();
        $popularSkills = $this->getPopularSkills();

        return view('admin.dashboard', compact(
            'stats',
            'recentInternships',
            'recentUsers',
            'internshipsChart',
            'usersChart',
            'popularLocations',
            'popularSkills'
        ));
    }

    /**
     * Internships Management
     */
    public function internships(Request $request)
    {
        $query = Internship::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('organization', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'featured') {
                $query->where('is_featured', true);
            }
        }

        // Location filter
        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        $internships = $query->with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->query());

        $locations = Internship::distinct()->pluck('location')->filter()->sort();

        return view('admin.internships.index', compact('internships', 'locations'));
    }

    /**
     * Users Management
     */
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->whereHas('profile', function($q) use ($request) {
                if ($request->status === 'complete') {
                    $q->whereNotNull('skills');
                } else {
                    $q->whereNull('skills');
                }
            });
        }

        $users = $query->with('profile')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->query());

        return view('admin.users.index', compact('users'));
    }

    /**
     * Analytics Dashboard
     */
    public function analytics()
    {
        $analytics = [
            'internship_trends' => $this->getInternshipTrends(),
            'user_growth' => $this->getUserGrowth(),
            'top_organizations' => $this->getTopOrganizations(),
            'skill_demand' => $this->getSkillDemand(),
            'location_stats' => $this->getLocationStats(),
            'recommendation_stats' => $this->getRecommendationStats(),
        ];

        return view('admin.analytics', compact('analytics'));
    }

    /**
     * Settings
     */
    public function settings()
    {
        return view('admin.settings');
    }

    /**
     * Update Profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admin_users,email,' . auth('admin')->id(),
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $admin = auth('admin')->user();
        
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('admin/avatars', 'public');
            $admin->avatar = $avatarPath;
        }

        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Change Password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $admin = auth('admin')->user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $admin->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->back()->with('success', 'Password changed successfully!');
    }

    /**
     * Toggle Internship Status
     */
    public function toggleInternshipStatus(Internship $internship)
    {
        $internship->update(['is_active' => !$internship->is_active]);
        
        $status = $internship->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Internship {$status} successfully!");
    }

    /**
     * Toggle Featured Status
     */
    public function toggleFeatured(Internship $internship)
    {
        $internship->update(['is_featured' => !$internship->is_featured]);
        
        $status = $internship->is_featured ? 'featured' : 'unfeatured';
        return redirect()->back()->with('success', "Internship {$status} successfully!");
    }

    /**
     * Delete Internship
     */
    public function deleteInternship(Internship $internship)
    {
        $internship->delete();
        return redirect()->back()->with('success', 'Internship deleted successfully!');
    }

    /**
     * Bulk Actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,feature,unfeature,delete',
            'selected_items' => 'required|array|min:1',
        ]);

        $internships = Internship::whereIn('id', $request->selected_items);
        $count = $internships->count();

        switch ($request->action) {
            case 'activate':
                $internships->update(['is_active' => true]);
                $message = "Successfully activated {$count} internship(s).";
                break;
            case 'deactivate':
                $internships->update(['is_active' => false]);
                $message = "Successfully deactivated {$count} internship(s).";
                break;
            case 'feature':
                $internships->update(['is_featured' => true]);
                $message = "Successfully featured {$count} internship(s).";
                break;
            case 'unfeature':
                $internships->update(['is_featured' => false]);
                $message = "Successfully unfeatured {$count} internship(s).";
                break;
            case 'delete':
                $internships->delete();
                $message = "Successfully deleted {$count} internship(s).";
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    // Helper methods for chart data
    private function getInternshipsChartData()
    {
        return Internship::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getUsersChartData()
    {
        return User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getPopularLocations()
    {
        return Internship::select('location', DB::raw('count(*) as count'))
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();
    }

    private function getPopularSkills()
    {
        $internships = Internship::whereNotNull('required_skills')->get();
        $skillCounts = [];

        foreach ($internships as $internship) {
            $skills = is_array($internship->required_skills) 
                ? $internship->required_skills 
                : explode(',', $internship->required_skills);

            foreach ($skills as $skill) {
                $skill = trim(strtolower($skill));
                $skillCounts[$skill] = ($skillCounts[$skill] ?? 0) + 1;
            }
        }

        arsort($skillCounts);
        return array_slice($skillCounts, 0, 10, true);
    }

    private function getInternshipTrends()
    {
        return Internship::selectRaw('
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as count
        ')
        ->where('created_at', '>=', now()->subMonths(12))
        ->groupBy('year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get();
    }

    private function getUserGrowth()
    {
        return User::selectRaw('
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as count
        ')
        ->where('created_at', '>=', now()->subMonths(12))
        ->groupBy('year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get();
    }

    private function getTopOrganizations()
    {
        return Internship::select('organization', DB::raw('count(*) as count'))
            ->groupBy('organization')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    private function getSkillDemand()
    {
        return $this->getPopularSkills();
    }

    private function getLocationStats()
    {
        return $this->getPopularLocations();
    }

    private function getRecommendationStats()
    {
        return [
            'total_recommendations' => DB::table('recommendation_analytics')->count(),
            'avg_recommendations' => DB::table('recommendation_analytics')->avg('recommendations_shown'),
            'feedback_stats' => RecommendationFeedback::select('feedback_type', DB::raw('count(*) as count'))
                ->groupBy('feedback_type')
                ->get(),
        ];
    }
}
