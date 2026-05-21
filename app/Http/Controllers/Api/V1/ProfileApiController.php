<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\RecruiterProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileApiController extends Controller
{
    /**
     * GET /api/v1/profile
     * Fetch authenticated user's profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $profile = $user->profile;
            if (!$profile) {
                $profile = Profile::create([
                    'user_id' => $user->id,
                    'name'    => $user->name,
                ]);
            }

            return response()->json([
                'success' => true,
                'role' => 'student',
                'profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'academic_background' => $profile->academic_background,
                    'skills' => $profile->skills ?? [],
                    'career_interests' => $profile->career_interests,
                    'aadhaar_number' => $profile->aadhaar_number,
                    'location' => $profile->location,
                    'profile_photo_url' => $profile->getPhotoUrl(),
                    'resume_url' => $profile->getResumeUrl(),
                    'has_resume' => $profile->hasResumeFile(),
                ]
            ]);
        }

        if ($user->isRecruiter()) {
            $profile = $user->recruiterProfile;
            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recruiter profile not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'role' => 'recruiter',
                'profile' => [
                    'id' => $profile->id,
                    'organization' => $profile->organization,
                    'description' => $profile->description,
                    'website' => $profile->website,
                    'logo_url' => $profile->logo_url_attribute ?? $profile->logo_path ? Storage::disk('public')->url($profile->logo_path) : null,
                    'approval_status' => $profile->approval_status,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Role profile not supported.'
        ], 422);
    }

    /**
     * POST /api/v1/profile
     * Update user profile text fields
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $request->validate([
                'name'                => 'required|string|max:255',
                'academic_background' => 'nullable|string|max:255',
                'skills'              => 'nullable|string', // Comma separated
                'career_interests'    => 'nullable|string',
                'aadhaar_number'      => 'nullable|string|max:12',
                'location'            => 'nullable|string|max:255',
            ]);

            $profile = $user->profile ?? new Profile(['user_id' => $user->id]);
            $profile->name                = $request->name;
            $profile->academic_background = $request->academic_background;
            $profile->skills              = $request->skills ? array_map('trim', explode(',', $request->skills)) : [];
            $profile->career_interests    = $request->career_interests;
            $profile->aadhaar_number      = $request->aadhaar_number;
            $profile->location            = $request->location;
            $profile->save();

            // Clear cache
            \App\Services\MatchingService::clearCache($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Student profile updated successfully.',
                'profile' => $profile
            ]);
        }

        if ($user->isRecruiter()) {
            $request->validate([
                'organization' => 'required|string|max:255',
                'description'  => 'nullable|string',
                'website'      => 'nullable|string|url|max:255',
            ]);

            $profile = $user->recruiterProfile;
            if (!$profile) {
                return response()->json(['success' => false, 'message' => 'Recruiter profile not found.'], 404);
            }

            $profile->organization = $request->organization;
            $profile->description  = $request->description;
            $profile->website      = $request->website;
            $profile->save();

            return response()->json([
                'success' => true,
                'message' => 'Recruiter profile updated successfully.',
                'profile' => $profile
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
    }

    /**
     * POST /api/v1/profile/upload-photo
     * Upload profile picture or recruiter logo
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        $disk = config('filesystems.default');

        if ($user->isStudent()) {
            $request->validate([
                'profile_photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            $profile = $user->profile;
            if ($profile->profile_photo) {
                try { Storage::disk($disk)->delete($profile->profile_photo); } catch (\Exception $e) {}
            }

            $photo    = $request->file('profile_photo');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $photo->getClientOriginalName());
            $path     = $photo->storeAs('profile_photos', $filename, ['disk' => $disk, 'visibility' => 'public']);

            if ($path) {
                $profile->profile_photo = $path;
                $profile->save();
                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo uploaded successfully.',
                    'url' => $profile->getPhotoUrl()
                ]);
            }
        }

        if ($user->isRecruiter()) {
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            $profile = $user->recruiterProfile;
            if ($profile->logo_path) {
                try { Storage::disk($disk)->delete($profile->logo_path); } catch (\Exception $e) {}
            }

            $logo     = $request->file('logo');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $logo->getClientOriginalName());
            $path     = $logo->storeAs('recruiter_logos', $filename, ['disk' => $disk, 'visibility' => 'public']);

            if ($path) {
                $profile->logo_path = $path;
                $profile->save();
                return response()->json([
                    'success' => true,
                    'message' => 'Logo uploaded successfully.',
                    'url' => Storage::disk('public')->url($profile->logo_path)
                ]);
            }
        }

        return response()->json(['success' => false, 'message' => 'File upload failed.'], 422);
    }

    /**
     * POST /api/v1/profile/upload-resume
     * Upload resume PDF (for students)
     */
    public function uploadResume(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isStudent()) {
            return response()->json(['success' => false, 'message' => 'Only students can upload resumes.'], 403);
        }

        $request->validate([
            'resume' => 'required|file|mimes:pdf|max:2048',
        ]);

        try {
            $profile = $user->profile;
            $disk = config('filesystems.default');

            if ($profile->resume_path) {
                try { Storage::disk($disk)->delete($profile->resume_path); } catch (\Exception $e) {}
            }

            $file     = $request->file('resume');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $path     = $file->storeAs('resumes', $filename, ['disk' => $disk, 'visibility' => 'public']);

            if ($path) {
                $profile->resume_path = $path;
                $profile->save();

                // Clear match cache
                \App\Services\MatchingService::clearCache($user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Resume uploaded successfully.',
                    'url' => $profile->getResumeUrl()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('API Resume upload failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'message' => 'Resume upload failed.'], 422);
    }
}
