<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        // Create profile if it doesn't exist
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'name' => $user->name,
            ]);
        }
        
        return view('profile.show', compact('profile'));
    }

    public function edit()
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        // Create profile if it doesn't exist
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'name' => $user->name,
            ]);
        }
        
        return view('profile.edit', compact('profile'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'academic_background' => 'nullable|string|max:255',
            'skills' => 'nullable|string',
            'career_interests' => 'nullable|string',
            'resume' => 'nullable|file|mimes:pdf|max:2048',
            'aadhaar_number' => 'nullable|string|max:12',
        ]);

        try {
            $user = Auth::user();
            $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

            $profile->name = $request->name;
            $profile->academic_background = $request->academic_background;
            $profile->skills = $request->skills ? explode(',', $request->skills) : [];
            $profile->career_interests = $request->career_interests;
            $profile->aadhaar_number = $request->aadhaar_number;

            // Handle resume upload with proper error handling
            if ($request->hasFile('resume')) {
                // Get configured disk (s3 in production, public in development)
                $disk = config('filesystems.default');
                
                // Delete old resume if exists
                if ($profile->resume_path) {
                    try {
                        Storage::disk($disk)->delete($profile->resume_path);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete old resume', [
                            'path' => $profile->resume_path,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Store new resume with sanitized filename
                $file = $request->file('resume');
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                $path = $file->storeAs('resumes', $filename, $disk);
                
                if ($path) {
                    $profile->resume_path = $path;
                    \Log::info('Resume uploaded successfully', [
                        'user_id' => $user->id,
                        'path' => $path,
                        'disk' => $disk,
                        'filename' => $filename,
                        'size' => $file->getSize()
                    ]);
                } else {
                    throw new \Exception('Failed to store resume file');
                }
            }

            $profile->save();

            return redirect()->route('profile.show')->with('success', 'Profile updated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Profile update failed', [
                'user_id' => Auth::id(),
                'operation' => 'upload',
                'disk' => config('filesystems.default'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update profile. Please try again.');
        }
    }
}