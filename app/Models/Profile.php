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
     * LARAVEL CLOUD ARCHITECTURE: Manual R2 URL construction
     * - CRITICAL: Cannot use Storage::url() on Laravel Cloud (causes redirect loop)
     * - Laravel Cloud sets AWS_URL to Laravel domain (platform restriction)
     * - Solution: Manually construct R2 public bucket URL
     * - Format: https://pub-{hash}.r2.dev/{path}
     * 
     * NO Storage::url(), NO temporaryUrl(), NO Laravel routing
     */
    public function getResumeUrl(): ?string
    {
        if (!$this->resume_path) {
            return null;
        }

        try {
            $disk = config('filesystems.default');
            $normalizedPath = ltrim($this->resume_path, '/');
            
            // R2 Storage (Production on Laravel Cloud)
            if ($disk === 's3') {
                // Check if file exists on R2 first
                if (!\Illuminate\Support\Facades\Storage::disk('s3')->exists($normalizedPath)) {
                    \Log::warning('Resume file not found on R2', [
                        'profile_id' => $this->id,
                        'resume_path' => $normalizedPath
                    ]);
                    return null;
                }
                
                // CRITICAL: Manually construct R2 public URL
                // DO NOT use Storage::url() - it returns Laravel Cloud domain
                $r2PublicUrl = config('filesystems.disks.s3.r2_public_url');
                
                if (empty($r2PublicUrl)) {
                    \Log::error('R2_PUBLIC_URL not configured', [
                        'profile_id' => $this->id,
                        'resume_path' => $normalizedPath
                    ]);
                    return null;
                }
                
                // Construct direct R2 URL: https://pub-{hash}.r2.dev/resumes/filename.pdf
                $url = rtrim($r2PublicUrl, '/') . '/' . $normalizedPath;
                
                \Log::debug('R2 resume URL generated (manual construction)', [
                    'profile_id' => $this->id,
                    'path' => $normalizedPath,
                    'url' => $url,
                    'method' => 'manual_construction'
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