<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RecruiterProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization',
        'description',
        'website',
        'logo_path',
        'approval_status',
        'approved_by',
        'approved_at',
        'suspended_at',
        'suspension_reason',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    /**
     * Get the recruiter user this profile belongs to
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who approved this recruiter profile
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the public URL for the logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Scope a query to only include pending recruiters
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Scope a query to only include approved recruiters
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope a query to only include rejected recruiters
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    /**
     * Scope a query to only include suspended recruiters
     */
    public function scopeSuspended($query)
    {
        return $query->where('approval_status', 'suspended');
    }

    /**
     * Check if the recruiter profile is pending approval
     */
    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Check if the recruiter profile is approved
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if the recruiter profile is rejected
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Check if the recruiter profile is suspended
     */
    public function isSuspended(): bool
    {
        return $this->approval_status === 'suspended';
    }
}
