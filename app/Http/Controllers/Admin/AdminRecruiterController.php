<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use App\Services\RecruiterManagementService;
use App\Services\RecruiterProfileModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRecruiterController extends Controller
{
    public function __construct(
        private RecruiterManagementService $recruiterManagementService,
        private RecruiterProfileModerationService $profileModerationService,
    ) {}

    /**
     * 5.1 - Display paginated list of all recruiters with profile data.
     * Supports filtering, search, and sorting.
     * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 14.1, 14.2, 14.3
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'recruiter')
            ->with('recruiterProfile')
            ->withCount([
                'internships',
                'internships as applications_count' => function ($q) {
                    $q->join('applications', 'applications.internship_id', '=', 'internships.id');
                },
            ]);

        // Filter by approval_status
        if ($request->filled('status')) {
            $query->whereHas('recruiterProfile', function ($q) use ($request) {
                $q->where('approval_status', $request->status);
            });
        }

        // Search by name, email, or organization
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhereHas('recruiterProfile', function ($rq) use ($search) {
                        $rq->where('organization', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');

        $allowedSorts = ['created_at', 'internships_count', 'applications_count'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $recruiters = $query->paginate(15)->appends($request->query());

        $pendingCount = User::where('role', 'recruiter')
            ->whereHas('recruiterProfile', fn($q) => $q->where('approval_status', 'pending'))
            ->count();

        return view('admin.recruiters.index', compact('recruiters', 'pendingCount'));
    }

    /**
     * 5.2 - Display comprehensive recruiter details.
     * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 9.5
     */
    public function show(User $user)
    {
        abort_unless($user->isRecruiter(), 404);

        $user->load([
            'recruiterProfile.approvedBy',
            'internships' => fn($q) => $q->withCount('applications'),
        ]);

        // Activity metrics
        $totalInternships = $user->internships->count();
        $activeInternships = $user->internships->where('is_active', true)->count();

        $totalApplications = Application::whereIn(
            'internship_id',
            $user->internships->pluck('id')
        )->count();

        $approvedApplications = Application::whereIn(
            'internship_id',
            $user->internships->pluck('id')
        )->where('status', 'approved')->count();

        $metrics = compact('totalInternships', 'activeInternships', 'totalApplications', 'approvedApplications');

        // Recent applications (last 10) to this recruiter's internships
        $recentApplications = Application::with(['user', 'internship'])
            ->whereIn('internship_id', $user->internships->pluck('id'))
            ->latest()
            ->limit(10)
            ->get();

        // Audit log history for this recruiter
        $auditLogs = AdminAuditLog::with('admin')
            ->byRecruiter($user->id)
            ->latest()
            ->get();

        $recruiter = $user;

        return view('admin.recruiters.show', compact('recruiter', 'metrics', 'recentApplications', 'auditLogs'));
    }

    /**
     * 5.3 - Approve a recruiter.
     * Requirements: 1.4, 1.6, 3.6
     */
    public function approve(Request $request, User $user)
    {
        abort_unless($user->isRecruiter(), 404);

        $profile = $user->recruiterProfile;

        if (!$profile || !in_array($profile->approval_status, ['pending', 'rejected'])) {
            $message = 'Recruiter cannot be approved from current status.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->withErrors($message);
        }

        try {
            $this->recruiterManagementService->approveRecruiter(
                $user->id,
                auth()->id(),
                $request->ip()
            );

            $message = "Recruiter {$user->name} has been approved.";

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('admin.recruiters.show', $user)
                ->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    /**
     * 5.4 - Reject a recruiter.
     * Requirements: 1.5, 1.6, 18.1, 18.3
     */
    public function reject(Request $request, User $user)
    {
        abort_unless($user->isRecruiter(), 404);

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $profile = $user->recruiterProfile;

        if (!$profile || $profile->approval_status !== 'pending') {
            $message = 'Only pending recruiters can be rejected.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->withErrors($message);
        }

        try {
            $this->recruiterManagementService->rejectRecruiter(
                $user->id,
                auth()->id(),
                $request->reason,
                $request->ip()
            );

            $message = "Recruiter {$user->name} has been rejected.";

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('admin.recruiters.show', $user)
                ->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    /**
     * 5.5 - Suspend a recruiter.
     * Requirements: 4.1, 4.5, 18.2, 18.4
     */
    public function suspend(Request $request, User $user)
    {
        abort_unless($user->isRecruiter(), 404);

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $profile = $user->recruiterProfile;

        if (!$profile || $profile->approval_status !== 'approved') {
            $message = 'Only approved recruiters can be suspended.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->withErrors($message);
        }

        try {
            $this->recruiterManagementService->suspendRecruiter(
                $user->id,
                auth()->id(),
                $request->reason,
                $request->ip()
            );

            $message = "Recruiter {$user->name} has been suspended.";

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('admin.recruiters.show', $user)
                ->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    /**
     * 5.6 - Activate a suspended recruiter.
     * Requirements: 4.4
     */
    public function activate(Request $request, User $user)
    {
        abort_unless($user->isRecruiter(), 404);

        $profile = $user->recruiterProfile;

        if (!$profile || $profile->approval_status !== 'suspended') {
            $message = 'Only suspended recruiters can be activated.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->withErrors($message);
        }

        try {
            $this->recruiterManagementService->activateRecruiter(
                $user->id,
                auth()->id(),
                $request->ip()
            );

            $message = "Recruiter {$user->name} has been activated.";

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('admin.recruiters.show', $user)
                ->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    /**
     * 5.7 - Perform bulk actions on multiple recruiters.
     * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action'        => 'required|in:approve,reject,suspend',
            'recruiter_ids' => 'required|array|min:1',
            'recruiter_ids.*' => 'integer|exists:users,id',
            'reason'        => 'required_if:action,reject,suspend|nullable|string|max:1000',
        ]);

        $action = $request->action;
        $recruiterIds = $request->recruiter_ids;
        $reason = $request->reason ?? '';
        $adminId = auth()->id();
        $ipAddress = $request->ip();

        $affected = 0;
        $errors = [];

        foreach ($recruiterIds as $recruiterId) {
            $recruiter = User::with('recruiterProfile')->find($recruiterId);

            if (!$recruiter || !$recruiter->isRecruiter()) {
                continue;
            }

            $profile = $recruiter->recruiterProfile;
            if (!$profile) {
                continue;
            }

            try {
                switch ($action) {
                    case 'approve':
                        if (in_array($profile->approval_status, ['pending', 'rejected'])) {
                            $this->recruiterManagementService->approveRecruiter($recruiterId, $adminId, $ipAddress);
                            $affected++;
                        }
                        break;

                    case 'reject':
                        if ($profile->approval_status === 'pending') {
                            $this->recruiterManagementService->rejectRecruiter($recruiterId, $adminId, $reason, $ipAddress);
                            $affected++;
                        }
                        break;

                    case 'suspend':
                        if ($profile->approval_status === 'approved') {
                            $this->recruiterManagementService->suspendRecruiter($recruiterId, $adminId, $reason, $ipAddress);
                            $affected++;
                        }
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = "Recruiter #{$recruiterId}: {$e->getMessage()}";
            }
        }

        $message = "Bulk action '{$action}' applied to {$affected} recruiter(s).";

        return response()->json([
            'success'  => true,
            'message'  => $message,
            'affected' => $affected,
            'errors'   => $errors,
        ]);
    }

    /**
     * 5.8 - Show edit profile form for a recruiter.
     * Requirements: 12.1, 12.2
     */
    public function editProfile(User $user)
    {
        abort_unless($user->isRecruiter(), 404);

        $user->load('recruiterProfile');

        $recruiter = $user;

        return view('admin.recruiters.edit-profile', compact('recruiter'));
    }

    /**
     * 5.9 - Update a recruiter's profile.
     * Requirements: 12.3, 12.4
     */
    public function updateProfile(Request $request, User $user)
    {
        abort_unless($user->isRecruiter(), 404);

        $validated = $request->validate([
            'organization' => 'required|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'website'      => 'nullable|url|max:255',
            'logo'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $profileData = [
            'organization' => $validated['organization'],
            'description'  => $validated['description'] ?? null,
            'website'      => $validated['website'] ?? null,
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('recruiter-logos', 'public');
            $profileData['logo_path'] = $logoPath;
        }

        try {
            $this->profileModerationService->updateRecruiterProfile(
                $user->id,
                auth()->id(),
                $profileData,
                $request->ip()
            );

            return redirect()->route('admin.recruiters.show', $user)
                ->with('success', "Profile for {$user->name} has been updated.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->getMessage());
        }
    }

    /**
     * 5.10 - Return activity timeline for a recruiter as JSON.
     * Requirements: 15.1, 15.2, 15.3, 15.4, 15.5
     */
    public function activityTimeline(Request $request, User $user)
    {
        abort_unless($user->isRecruiter(), 404);

        $events = collect();

        // Registration event
        $events->push([
            'type'      => 'registration',
            'label'     => 'Recruiter Registered',
            'detail'    => "Account created for {$user->name}",
            'timestamp' => $user->created_at,
        ]);

        // Approval/rejection/suspension events from audit log
        $auditLogs = AdminAuditLog::with('admin')
            ->byRecruiter($user->id)
            ->get();

        foreach ($auditLogs as $log) {
            $events->push([
                'type'      => $log->action_type,
                'label'     => ucfirst($log->action_type),
                'detail'    => $log->reason ?? "Action by {$log->admin?->name}",
                'timestamp' => $log->created_at,
            ]);
        }

        // Internship posting events
        $internships = Internship::where('recruiter_id', $user->id)->get();

        foreach ($internships as $internship) {
            $events->push([
                'type'      => 'internship_posted',
                'label'     => 'Internship Posted',
                'detail'    => $internship->title,
                'timestamp' => $internship->created_at,
            ]);

            if ($internship->isDeactivated()) {
                $events->push([
                    'type'      => 'internship_deactivated',
                    'label'     => 'Internship Deactivated',
                    'detail'    => $internship->title . ($internship->deactivation_reason ? ': ' . $internship->deactivation_reason : ''),
                    'timestamp' => $internship->deactivated_at,
                ]);
            }
        }

        // Application status change events
        $internshipIds = $internships->pluck('id');

        $statusChanges = DB::table('application_status_logs')
            ->join('applications', 'applications.id', '=', 'application_status_logs.application_id')
            ->join('users as students', 'students.id', '=', 'applications.user_id')
            ->join('internships', 'internships.id', '=', 'applications.internship_id')
            ->whereIn('applications.internship_id', $internshipIds)
            ->select(
                'application_status_logs.to_status',
                'application_status_logs.created_at',
                'students.name as student_name',
                'internships.title as internship_title'
            )
            ->get();

        foreach ($statusChanges as $change) {
            $events->push([
                'type'      => 'application_status_change',
                'label'     => 'Application Status Changed',
                'detail'    => "Application for '{$change->internship_title}' by {$change->student_name} → {$change->to_status}",
                'timestamp' => $change->created_at,
            ]);
        }

        // Sort descending by timestamp, paginate 50 per page
        $sorted = $events
            ->sortByDesc('timestamp')
            ->values();

        $page = (int) $request->get('page', 1);
        $perPage = 50;
        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data'         => $items,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }
}
