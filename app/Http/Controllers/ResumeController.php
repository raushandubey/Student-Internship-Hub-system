<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

/**
 * ResumeController
 * 
 * Handles resume file serving with proper security and fallback logic.
 * Solves production 404 issues by serving files directly when symlink is missing.
 */
class ResumeController extends Controller
{
    /**
     * Serve resume file directly
     * 
     * This route serves as a fallback when the storage symlink is missing
     * or when files are stored in a non-standard location.
     * 
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function serve(string $filename)
    {
        try {
            // Sanitize filename to prevent directory traversal
            $filename = basename($filename);
            $path = 'resumes/' . $filename;
            
            // Check if file exists on public disk
            if (!Storage::disk('public')->exists($path)) {
                Log::warning('Resume file not found', [
                    'filename' => $filename,
                    'path' => $path
                ]);
                
                return $this->resumeNotFoundResponse();
            }
            
            // Get file contents
            $file = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path);
            
            // Return file response with proper headers
            return Response::make($file, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control' => 'public, max-age=3600',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Resume serving failed', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->resumeNotFoundResponse();
        }
    }
    
    /**
     * Download resume file
     * 
     * Forces download instead of inline display
     * 
     * @param int $profileId
     * @return \Illuminate\Http\Response
     */
    public function download(int $profileId)
    {
        try {
            $profile = Profile::findOrFail($profileId);
            
            if (!$profile->resume_path) {
                return $this->resumeNotFoundResponse();
            }
            
            $normalizedPath = ltrim($profile->resume_path, '/');
            
            if (!Storage::disk('public')->exists($normalizedPath)) {
                Log::warning('Resume file not found for download', [
                    'profile_id' => $profileId,
                    'path' => $normalizedPath
                ]);
                
                return $this->resumeNotFoundResponse();
            }
            
            // Generate download filename
            $filename = $profile->user->name . '_Resume.pdf';
            $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);
            
            return Storage::disk('public')->download($normalizedPath, $filename);
            
        } catch (\Exception $e) {
            Log::error('Resume download failed', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            
            return $this->resumeNotFoundResponse();
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
    
    /**
     * Return a user-friendly 404 response for missing resumes
     * 
     * @return \Illuminate\Http\Response
     */
    protected function resumeNotFoundResponse()
    {
        return response()->view('errors.resume-not-found', [], 404);
    }
}
