<?php

namespace App\Services;

use App\Models\User;
use App\Models\RecruiterProfile;
use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Events\RecruiterProfileModified;

/**
 * RecruiterProfileModerationService
 * 
 * Handles admin moderation of recruiter profiles including profile updates
 * and validation. Uses database transactions for atomic operations and
 * dispatches events for notifications.
 */
class RecruiterProfileModerationService
{
    /**
     * Update a recruiter's profile as an admin
     * 
     * @param int $recruiterId The recruiter user ID
     * @param int $adminId The admin user ID performing the action
     * @param array $profileData The profile data to update
     * @param string $ipAddress The IP address of the admin
     * @return bool Success status
     * @throws ValidationException If profile data is invalid
     * @throws \Exception If recruiter not found or invalid state
     */
    public function updateRecruiterProfile(int $recruiterId, int $adminId, array $profileData, string $ipAddress): bool
    {
        // Validate profile data
        $validator = Validator::make($profileData, [
            'organization' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'website' => 'sometimes|nullable|url|max:255',
            'logo_path' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return DB::transaction(function () use ($recruiterId, $adminId, $profileData, $ipAddress) {
            // Load recruiter with profile
            $recruiter = User::with('recruiterProfile')->findOrFail($recruiterId);
            
            if (!$recruiter->isRecruiter()) {
                throw new \Exception('User is not a recruiter');
            }
            
            $profile = $recruiter->recruiterProfile;
            
            if (!$profile) {
                throw new \Exception('Recruiter profile not found');
            }
            
            // Store original data for logging
            $originalData = $profile->only(['organization', 'description', 'website', 'logo_path']);
            
            // Update recruiter profile with validated data
            $profile->update($profileData);
            
            // Load admin user
            $admin = User::findOrFail($adminId);
            
            // Create audit log
            AdminAuditLog::create([
                'admin_user_id' => $adminId,
                'action_type' => AdminAuditLog::PROFILE_EDITED,
                'target_recruiter_id' => $recruiterId,
                'reason' => 'Profile modified by admin: ' . json_encode([
                    'original' => $originalData,
                    'updated' => $profileData,
                ]),
                'ip_address' => $ipAddress,
            ]);
            
            // Dispatch event for email notification
            event(new RecruiterProfileModified($recruiter, $admin));
            
            Log::info('Recruiter profile modified by admin', [
                'recruiter_id' => $recruiterId,
                'admin_id' => $adminId,
                'updated_fields' => array_keys($profileData),
                'ip_address' => $ipAddress,
            ]);
            
            return true;
        });
    }
}
