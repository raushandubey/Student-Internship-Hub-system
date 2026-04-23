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
     * PRODUCTION-SAFE: Multiple fallback strategies
     */
    public function getResumeUrl(): ?string
    {
        if (!$this->resume_path) {
            return null;
        }

        try {
            // Normalize path (remove leading slash if present)
            $normalizedPath = ltrim($this->resume_path, '/');
            
            // Strategy 1: Check public disk (primary storage)
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedPath)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($normalizedPath);
            }
            
            // Strategy 2: Check if file exists in storage/app/public directly
            $fullPath = storage_path('app/public/' . $normalizedPath);
            if (file_exists($fullPath)) {
                return asset('storage/' . $normalizedPath);
            }
            
            // Strategy 3: Use route-based serving (fallback for missing symlink)
            return route('resume.serve', ['filename' => basename($normalizedPath)]);
            
        } catch (\Exception $e) {
            \Log::warning('Resume URL generation failed', [
                'profile_id' => $this->id,
                'resume_path' => $this->resume_path,
                'error' => $e->getMessage()
            ]);
            
            // Final fallback: return null (will show "No resume" in UI)
            return null;
        }
    }
    
    /**
     * Check if resume file actually exists on disk
     */
    public function hasResumeFile(): bool
    {
        if (!$this->resume_path) {
            return false;
        }
        
        $normalizedPath = ltrim($this->resume_path, '/');
        
        // Check public disk
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedPath)) {
            return true;
        }
        
        // Check direct file system
        $fullPath = storage_path('app/public/' . $normalizedPath);
        return file_exists($fullPath);
    }
}