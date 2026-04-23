<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    /**
     * Display paginated list of all admin audit logs with filtering
     * Requirements: 9.4
     */
    public function index(Request $request)
    {
        $query = AdminAuditLog::with(['admin', 'targetRecruiter'])
            ->orderBy('created_at', 'desc');

        // Filter by admin user
        if ($adminId = $request->input('admin_id')) {
            $query->byAdmin($adminId);
        }

        // Filter by action type
        if ($actionType = $request->input('action_type')) {
            $query->byActionType($actionType);
        }

        // Filter by date range
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $auditLogs = $query->paginate(25)->withQueryString();

        // Load admin users for filter dropdown
        $admins = User::where('role', 'admin')->orderBy('name')->get();

        $actionTypes = [
            AdminAuditLog::APPROVED,
            AdminAuditLog::REJECTED,
            AdminAuditLog::SUSPENDED,
            AdminAuditLog::ACTIVATED,
            AdminAuditLog::INTERNSHIP_DEACTIVATED,
            AdminAuditLog::PROFILE_EDITED,
        ];

        $filters = $request->only(['admin_id', 'action_type', 'start_date', 'end_date']);

        return view('admin.audit-logs.index', compact('auditLogs', 'admins', 'actionTypes', 'filters'));
    }
}
