<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    use HasFactory;

    // Action type constants
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const SUSPENDED = 'suspended';
    const ACTIVATED = 'activated';
    const INTERNSHIP_DEACTIVATED = 'internship_deactivated';
    const PROFILE_EDITED = 'profile_edited';

    protected $fillable = [
        'admin_user_id',
        'action_type',
        'target_recruiter_id',
        'reason',
        'ip_address',
    ];

    /**
     * Get the admin user who performed the action
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Get the target recruiter user
     */
    public function targetRecruiter()
    {
        return $this->belongsTo(User::class, 'target_recruiter_id');
    }

    /**
     * Scope a query to filter by admin user
     */
    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_user_id', $adminId);
    }

    /**
     * Scope a query to filter by action type
     */
    public function scopeByActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope a query to filter by target recruiter
     */
    public function scopeByRecruiter($query, $recruiterId)
    {
        return $query->where('target_recruiter_id', $recruiterId);
    }
}
