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
    ];

    protected $casts = [
        'required_skills' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all applications for this internship
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Scope: Active internships only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: With application counts
     */
    public function scopeWithApplicationStats($query)
    {
        return $query->withCount('applications');
    }
}