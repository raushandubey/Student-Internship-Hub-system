<?php

namespace App\Models;

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
            $normalizedPath = ltrim($this->resume_path, '/');
            
            // R2/S3 Storage (Production on Laravel Cloud)
            if ($this->isCloudDisk($disk)) {
                if (!Storage::disk($disk)->exists($normalizedPath)) {
                    Log::warning('Resume file not found on cloud storage', [
                        'profile_id' => $this->id,
                        'resume_path' => $normalizedPath,
                        'disk' => $disk,
                    ]);
                    return null;
                }

                $url = $this->buildCloudFileUrl($disk, $normalizedPath);

                if (!$url) {
                    Log::error('Cloud public URL not configured for resume', [
                        'profile_id' => $this->id,
                        'resume_path' => $normalizedPath,
                        'disk' => $disk,
                    ]);
                    return null;
                }

                Log::debug('Cloud resume URL generated', [
                    'profile_id' => $this->id,
                    'path' => $normalizedPath,
                    'url' => $url,
                    'disk' => $disk,
                ]);

                return $url;
            }
            
            // Local/Public Storage (Development) - Check existence then generate URL
            if (Storage::disk('public')->exists($normalizedPath)) {
                return Storage::disk('public')->url($normalizedPath);
            }
            
            // Direct filesystem check (fallback for symlink issues)
            $fullPath = storage_path('app/public/' . $normalizedPath);
            if (file_exists($fullPath)) {
                return asset('storage/' . $normalizedPath);
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
            $normalizedPath = ltrim($this->resume_path, '/');

            if ($this->isCloudDisk($disk)) {
                return Storage::disk($disk)->exists($normalizedPath);
            }
            
            // Check public disk
            if (Storage::disk('public')->exists($normalizedPath)) {
                return true;
            }

            if ($disk === 'local' && Storage::disk('local')->exists($normalizedPath)) {
                return true;
            }
            
            // Check direct file system
            $fullPath = storage_path('app/public/' . $normalizedPath);
            $legacyLocalPath = storage_path('app/' . $normalizedPath);

            return file_exists($fullPath) || file_exists($legacyLocalPath);
            
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
        return in_array($disk, ['s3', 'r2'], true);
    }

    private function buildCloudFileUrl(string $disk, string $path): ?string
    {
        $baseUrl = config("filesystems.disks.{$disk}.r2_public_url")
            ?: config("filesystems.disks.{$disk}.url");

        if ($baseUrl) {
            $baseHost = parse_url($baseUrl, PHP_URL_HOST);
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);

            if (!$baseHost || !$appHost || strcasecmp($baseHost, $appHost) !== 0) {
                return rtrim($baseUrl, '/') . '/' . $this->encodeStoragePath($path);
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

    private function encodeStoragePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
    }
}
