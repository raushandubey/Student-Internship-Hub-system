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
     * PRODUCTION-SAFE: Handles both S3 and local storage with multiple fallback strategies
     */
    public function getResumeUrl(): ?string
    {
        if (!$this->resume_path) {
            return null;
        }

        try {
            $disk = config('filesystems.default');
            
            // Strategy 1: S3 Storage (Production)
            if ($disk === 's3') {
                if (\Illuminate\Support\Facades\Storage::disk('s3')->exists($this->resume_path)) {
                    // Try to generate a temporary signed URL with 1 hour expiration
                    // This provides better security and handles private buckets
                    try {
                        return \Illuminate\Support\Facades\Storage::disk('s3')
                            ->temporaryUrl($this->resume_path, now()->addHour());
                    } catch (\Exception $e) {
                        // If temporaryUrl fails (e.g., bucket is public), fall back to regular URL
                        \Log::debug('Falling back to regular S3 URL', [
                            'profile_id' => $this->id,
                            'error' => $e->getMessage()
                        ]);
                        return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->resume_path);
                    }
                }
            }
            
            // Strategy 2: Public disk (Local/Development)
            $normalizedPath = ltrim($this->resume_path, '/');
            
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedPath)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($normalizedPath);
            }
            
            // Strategy 3: Direct filesystem check
            $fullPath = storage_path('app/public/' . $normalizedPath);
            if (file_exists($fullPath)) {
                return asset('storage/' . $normalizedPath);
            }
            
            // Strategy 4: Route-based serving (fallback for missing symlink)
            return route('resume.serve', ['filename' => basename($normalizedPath)]);
            
        } catch (\Exception $e) {
            \Log::warning('Resume URL generation failed', [
                'profile_id' => $this->id,
                'resume_path' => $this->resume_path,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Check if resume file actually exists on disk
     * Handles both S3 and local storage
     */
    public function hasResumeFile(): bool
    {
        if (!$this->resume_path) {
            return false;
        }
        
        try {
            $disk = config('filesystems.default');
            
            // Check S3 first if configured
            if ($disk === 's3') {
                return \Illuminate\Support\Facades\Storage::disk('s3')->exists($this->resume_path);
            }
            
            // Check public disk
            $normalizedPath = ltrim($this->resume_path, '/');
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedPath)) {
                return true;
            }
            
            // Check direct file system
            $fullPath = storage_path('app/public/' . $normalizedPath);
            return file_exists($fullPath);
            
        } catch (\Exception $e) {
            \Log::warning('Resume file check failed', [
                'profile_id' => $this->id,
                'resume_path' => $this->resume_path,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}