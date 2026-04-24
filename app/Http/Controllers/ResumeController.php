<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * ResumeController
 * 
 * ARCHITECTURE: Direct S3 URL serving - NO file proxying through Laravel
 * 
 * Key Principles:
 * - Resumes load DIRECTLY from S3 (no Laravel routing)
 * - Use Storage::temporaryUrl() for signed URLs
 * - NO redirect loops
 * - Authorization at API level only
 */
class ResumeController extends Controller
{
    /**
     * Get resume URL for a profile (API endpoint)
     * 
     * Returns direct S3 URL - frontend loads file directly
     * NO file serving through Laravel
     * 
     * @param int $profileId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUrl(int $profileId)
    {
        try {
            $profile = Profile::findOrFail($profileId);
            
            // Authorization check
            $user = auth()->user();
            
            // Students can only access their own resumes
            if ($user->role === 'student' && $profile->user_id !== $user->id) {
                Log::warning('Unauthorized resume access attempt', [
                    'user_id' => $user->id,
                    'profile_id' => $profileId
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }
            
            // Get direct S3 URL
            $url = $profile->getResumeUrl();
            
            if (!$url) {
                return response()->json([
                    'success' => false,
                    'error' => 'Resume not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'filename' => basename($profile->resume_path)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Resume URL fetch failed', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch resume URL'
            ], 500);
        }
    }
    
    /**
     * Download resume file
     * 
     * PRODUCTION: Redirects to direct S3 public URL
     * NO file proxying, NO authentication on S3 files
     * 
     * @param int $profileId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function download(int $profileId)
    {
        try {
            $profile = Profile::findOrFail($profileId);
            
            // Authorization check
            $user = auth()->user();
            
            // Students can only download their own resumes
            if ($user->role === 'student' && $profile->user_id !== $user->id) {
                Log::warning('Unauthorized resume download attempt', [
                    'user_id' => $user->id,
                    'profile_id' => $profileId
                ]);
                
                abort(403, 'Unauthorized');
            }
            
            if (!$profile->resume_path) {
                abort(404, 'Resume not found');
            }
            
            $normalizedPath = ltrim($profile->resume_path, '/');
            $disk = config('filesystems.default');
            
            // S3 Storage - Redirect to direct public URL
            if ($disk === 's3') {
                // CRITICAL: Use direct public URL for download
                $url = Storage::disk('s3')->url($normalizedPath);
                
                Log::info('Resume download redirect to S3', [
                    'profile_id' => $profileId,
                    'url' => $url
                ]);
                
                return redirect($url);
            }
            
            // Local Storage - Direct download
            if (Storage::disk('public')->exists($normalizedPath)) {
                $filename = $profile->user->name . '_Resume.pdf';
                $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);
                return Storage::disk('public')->download($normalizedPath, $filename);
            }
            
            abort(404, 'Resume file not found');
            
        } catch (\Exception $e) {
            Log::error('Resume download failed', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            
            abort(500, 'Download failed');
        }
    }
    
    /**
     * Check if resume exists (API endpoint)
     * 
     * @param int $profileId
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(int $profileId)
    {
        try {
            $profile = Profile::findOrFail($profileId);
            
            // Authorization check
            $user = auth()->user();
            
            // Students can only check their own resumes
            if ($user->role === 'student' && $profile->user_id !== $user->id) {
                return response()->json([
                    'exists' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }
            
            $exists = $profile->hasResumeFile();
            $url = $exists ? $profile->getResumeUrl() : null;
            
            return response()->json([
                'exists' => $exists,
                'url' => $url,
                'filename' => $profile->resume_path ? basename($profile->resume_path) : null
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
                'error' => 'Profile not found'
            ], 404);
        }
    }
}
