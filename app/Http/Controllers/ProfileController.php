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
        $user    = Auth::user();
        $profile = $user->profile;

        // Create profile if it doesn't exist
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'name'    => $user->name,
            ]);
        }

        // Detect mobile and use mobile view
        $isMobile = request()->header('User-Agent') &&
                    preg_match('/Mobile|Android|iPhone/i', request()->header('User-Agent'));

        if ($isMobile) {
            return view('student.profile-show-mobile', compact('profile'));
        }

        return view('profile.show', compact('profile'));
    }

    public function edit()
    {
        $user    = Auth::user();
        $profile = $user->profile;

        // Create profile if it doesn't exist
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'name'    => $user->name,
            ]);
        }

        // Mobile detection — same regex as show()
        $isMobile = request()->header('User-Agent') &&
                    preg_match('/Mobile|Android|iPhone|iPad/i', request()->header('User-Agent'));

        if ($isMobile) {
            return view('student.profile-edit-mobile', compact('profile'));
        }

        return view('profile.edit', compact('profile'));
    }

    public function editMobile()
    {
        $user    = Auth::user();
        $profile = $user->profile;

        // Create profile if it doesn't exist
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'name'    => $user->name,
            ]);
        }

        return view('student.profile-edit-mobile', compact('profile'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:255',
            'academic_background' => 'nullable|string|max:255',
            'skills'              => 'nullable|string',
            'career_interests'    => 'nullable|string',
            'resume'              => 'nullable|file|mimes:pdf|max:2048',
            'aadhaar_number'      => 'nullable|string|max:12',
            // Extended fields
            'profile_photo'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'location'            => 'nullable|string|max:255',
        ]);

        try {
            $user    = Auth::user();
            $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

            $profile->name                = $request->name;
            $profile->academic_background = $request->academic_background;
            $profile->skills              = $request->skills ? explode(',', $request->skills) : [];
            $profile->career_interests    = $request->career_interests;
            $profile->aadhaar_number      = $request->aadhaar_number;
            $profile->location            = $request->location;

            // ── Profile Photo Upload ──────────────────────────────────────────
            if ($request->hasFile('profile_photo')) {
                $disk = config('filesystems.default');

                // Delete old photo
                if ($profile->profile_photo) {
                    try {
                        Storage::disk($disk)->delete($profile->profile_photo);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete old profile photo', [
                            'path'  => $profile->profile_photo,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $photo    = $request->file('profile_photo');
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $photo->getClientOriginalName());

                $path = $photo->storeAs('profile_photos', $filename, [
                    'disk'       => $disk,
                    'visibility' => 'public',
                ]);

                if ($path) {
                    $profile->profile_photo = $path;
                    \Log::info('Profile photo uploaded', ['user_id' => $user->id, 'path' => $path]);
                }
            }

            // ── Resume Upload (original logic preserved) ──────────────────────
            if ($request->hasFile('resume')) {
                $disk = config('filesystems.default');

                // Delete old resume if exists
                if ($profile->resume_path) {
                    try {
                        Storage::disk($disk)->delete($profile->resume_path);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete old resume', [
                            'path'  => $profile->resume_path,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $file     = $request->file('resume');
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());

                $path = $file->storeAs('resumes', $filename, [
                    'disk'       => $disk,
                    'visibility' => 'public',
                ]);

                if ($path) {
                    $profile->resume_path = $path;

                    if ($disk === 's3') {
                        $url = Storage::disk('s3')->url($path);
                        \Log::info('Resume uploaded to S3 with public access', [
                            'user_id'  => $user->id,
                            'path'     => $path,
                            'url'      => $url,
                            'filename' => $filename,
                            'size'     => $file->getSize(),
                        ]);
                    } else {
                        \Log::info('Resume uploaded successfully', [
                            'user_id'  => $user->id,
                            'path'     => $path,
                            'disk'     => $disk,
                            'filename' => $filename,
                            'size'     => $file->getSize(),
                        ]);
                    }
                } else {
                    throw new \Exception('Failed to store resume file');
                }
            }

            $profile->save();

            // Clear recommendations cache — location change should recalculate immediately
            \App\Services\MatchingService::clearCache($user->id);

            return redirect()->route('profile.show')->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Profile update failed', [
                'user_id'   => Auth::id(),
                'operation' => 'upload',
                'disk'      => config('filesystems.default'),
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update profile. Please try again.');
        }
    }
}