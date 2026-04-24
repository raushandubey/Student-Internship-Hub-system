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
     * Get the direct PUBLIC URL for the resume file
     * 
     * PRODUCTION ARCHITECTURE: Direct S3 public URLs - NO signed URLs, NO Laravel routing
     * - S3: Returns direct public URL from storage (only if file exists)
     * - Local: Returns public storage URL (only if file exists)
     * - Returns null if file doesn't exist
     * 
     * CRITICAL: Uses Storage::disk('s3')->url() for direct public access
     * NO temporaryUrl(), NO authentication, NO redirects
     */
    public function getResumeUrl(): ?string
    {
        if (!$this->resume_path) {
            return null;
        }

        try {
            $disk = config('filesystems.default');
            $normalizedPath = ltrim($this->resume_path, '/');
            
            // S3 Storage (Production) - Direct public URL
            if ($disk === 's3') {
                // Check if file exists on S3 first
                if (!\Illuminate\Support\Facades\Storage::disk('s3')->exists($normalizedPath)) {
                    \Log::warning('Resume file not found on S3', [
                        'profile_id' => $this->id,
                        'resume_path' => $normalizedPath
                    ]);
                    return null;
                }
                
                // CRITICAL: Use direct public URL - NO signed URLs, NO authentication
                // This returns: https://bucket-name.s3.region.amazonaws.com/resumes/filename.pdf
                $url = \Illuminate\Support\Facades\Storage::disk('s3')->url($normalizedPath);
                
                \Log::debug('S3 resume URL generated', [
                    'profile_id' => $this->id,
                    'path' => $normalizedPath,
                    'url' => $url
                ]);
                
                return $url;
            }
            
            // Local/Public Storage (Development) - Check existence then generate URL
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedPath)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($normalizedPath);
            }
            
            // Direct filesystem check (fallback for symlink issues)
            $fullPath = storage_path('app/public/' . $normalizedPath);
            if (file_exists($fullPath)) {
                return asset('storage/' . $normalizedPath);
            }
            
            // File not found - return null
            \Log::warning('Resume file not found', [
                'profile_id' => $this->id,
                'resume_path' => $this->resume_path
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Resume URL generation failed', [
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