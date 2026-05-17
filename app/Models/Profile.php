<?php

namespace App\Models;

use App\Support\ResumeStoragePaths;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'profile_photo',
        'location',
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
     * Get the public URL for the profile photo.
     * Returns null if no photo set (views should show initials fallback).
     */
    public function getPhotoUrl(): ?string
    {
        if (!$this->profile_photo) {
            return null;
        }

        $normalized = ltrim($this->profile_photo, '/');
        $disk = config('filesystems.default');

        if ($this->isCloudDisk($disk)) {
            return $this->buildCloudFileUrl($disk, $normalized);
        }

        // Local public disk
        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->url($normalized);
        }

        $fullPath = storage_path('app/public/' . $normalized);
        return file_exists($fullPath) ? asset('storage/' . $normalized) : null;
    }

    /** True if a profile photo has been uploaded */
    public function hasPhoto(): bool
    {
        return !empty($this->profile_photo);
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
            $pathCandidates = ResumeStoragePaths::candidates($this->resume_path);
            
            // R2/S3 Storage (Production on Laravel Cloud)
            if ($this->isCloudDisk($disk)) {
                foreach ($pathCandidates as $pathCandidate) {
                    if (!Storage::disk($disk)->exists($pathCandidate)) {
                        continue;
                    }

                    $url = $this->buildCloudFileUrl($disk, $pathCandidate);

                    if (!$url) {
                        Log::error('Cloud public URL not configured for resume', [
                            'profile_id' => $this->id,
                            'resume_path' => $pathCandidate,
                            'disk' => $disk,
                        ]);
                        return null;
                    }

                    Log::debug('Cloud resume URL generated', [
                        'profile_id' => $this->id,
                        'path' => $pathCandidate,
                        'url' => $url,
                        'disk' => $disk,
                    ]);

                    return $url;
                }

                if (!empty($pathCandidates)) {
                    Log::warning('Resume file not found on cloud storage', [
                        'profile_id' => $this->id,
                        'resume_path' => $this->resume_path,
                        'path_candidates' => $pathCandidates,
                        'disk' => $disk,
                    ]);
                }
                return null;
            }
            
            // Local/Public Storage (Development) - Check existence then generate URL
            foreach ($pathCandidates as $pathCandidate) {
                if (Storage::disk('public')->exists($pathCandidate)) {
                    return Storage::disk('public')->url($pathCandidate);
                }
            }
            
            // Direct filesystem check (fallback for symlink issues)
            foreach ($pathCandidates as $pathCandidate) {
                $fullPath = storage_path('app/public/' . $pathCandidate);
                if (file_exists($fullPath)) {
                    return asset('storage/' . $pathCandidate);
                }
            }
            
            // File not found - return null
            Log::warning('Resume file not found', [
                'profile_id' => $this->id,
                'resume_path' => $this->resume_path
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Resume URL generation failed', [
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
            $pathCandidates = ResumeStoragePaths::candidates($this->resume_path);

            if ($this->isCloudDisk($disk)) {
                foreach ($pathCandidates as $pathCandidate) {
                    if (Storage::disk($disk)->exists($pathCandidate)) {
                        return true;
                    }
                }

                return false;
            }
            
            // Check public disk
            foreach ($pathCandidates as $pathCandidate) {
                if (Storage::disk('public')->exists($pathCandidate)) {
                    return true;
                }

                if ($disk === 'local' && Storage::disk('local')->exists($pathCandidate)) {
                    return true;
                }
            }
            
            // Check direct file system
            foreach ($pathCandidates as $pathCandidate) {
                $fullPath = storage_path('app/public/' . $pathCandidate);
                $legacyLocalPath = storage_path('app/' . $pathCandidate);

                if (file_exists($fullPath) || file_exists($legacyLocalPath)) {
                    return true;
                }
            }

            return false;
            
        } catch (\Exception $e) {
            Log::warning('Resume file check failed', [
                'profile_id' => $this->id,
                'resume_path' => $this->resume_path,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    private function isCloudDisk(string $disk): bool
    {
        return in_array($disk, ['s3', 'r2'], true)
            || config("filesystems.disks.{$disk}.driver") === 's3';
    }

    private function buildCloudFileUrl(string $disk, string $path): ?string
    {
        $baseUrl = config("filesystems.disks.{$disk}.r2_public_url")
            ?: config("filesystems.disks.{$disk}.url");

        if ($baseUrl) {
            $baseHost = parse_url($baseUrl, PHP_URL_HOST);
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);

            if (!$baseHost || !$appHost || strcasecmp($baseHost, $appHost) !== 0) {
                return rtrim($baseUrl, '/') . '/' . ResumeStoragePaths::encode($path);
            }
        }

        try {
            $url = Storage::disk($disk)->url($path);
            $urlHost = parse_url($url, PHP_URL_HOST);
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);

            return ($urlHost && $appHost && strcasecmp($urlHost, $appHost) === 0) ? null : $url;
        } catch (\Throwable $e) {
            Log::warning('Cloud file URL generation failed', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

}
