<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'academic_background',
        'skills',
        'career_interests',
        'resume_path',
        'aadhaar_number',
    ];

    protected $casts = [
        'skills' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the public URL for the resume file
     */
    public function getResumeUrl(): ?string
    {
        if (!$this->resume_path) {
            return null;
        }

        // Try public disk first
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->resume_path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->resume_path);
        }

        // Fallback to default disk
        if (\Illuminate\Support\Facades\Storage::exists($this->resume_path)) {
            return \Illuminate\Support\Facades\Storage::url($this->resume_path);
        }

        return null;
    }
}