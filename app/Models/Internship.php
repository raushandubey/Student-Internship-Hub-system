<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'organization',
        'required_skills',
        'duration',
        'location',
        'description',
        'is_active',
        'recruiter_id',
        'deactivation_reason',
        'deactivated_by',
        'deactivated_at',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    /**
     * Get all applications for this internship
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get the recruiter who posted this internship
     */
    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    /**
     * Get the admin who deactivated this internship
     */
    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    /**
     * Check if the internship is deactivated
     */
    public function isDeactivated()
    {
        return !$this->is_active && $this->deactivated_at !== null;
    }

    /**
     * Scope: Active internships only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter internships for a specific recruiter
     */
    public function scopeForRecruiter($query, $recruiterId)
    {
        return $query->where('recruiter_id', $recruiterId);
    }

    /**
     * Scope: With application counts
     */
    public function scopeWithApplicationStats($query)
    {
        return $query->withCount('applications');
    }
}