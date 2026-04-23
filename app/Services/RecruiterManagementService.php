<?php

namespace App\Services;

use App\Models\User;
use App\Models\RecruiterProfile;
use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\RecruiterApproved;
use App\Events\RecruiterRejected;
use App\Events\RecruiterSuspended;
use App\Events\RecruiterActivated;

/**
 * RecruiterManagementService
 * 
 * Handles all recruiter account management operations including approval,
 * rejection, suspension, and activation. Uses database transactions for
 * atomic operations and dispatches events for notifications.
 */
class RecruiterManagementService
{
    /**
     * Approve a recruiter account
     * 
     * @param int $recruiterId The recruiter user ID
     * @param int $adminId The admin user ID performing the action
     * @param string $ipAddress The IP address of the admin
     * @return bool Success status
     * @throws \Exception If recruiter not found or invalid state
     */
    public function approveRecruiter(int $recruiterId, int $adminId, string $ipAddress): bool
    {
        return DB::transaction(function () use ($recruiterId, $adminId, $ipAddress) {
            // Load recruiter with profile
            $recruiter = User::with('recruiterProfile')->findOrFail($recruiterId);
            
            if (!$recruiter->isRecruiter()) {
                throw new \Exception('User is not a recruiter');
            }
            
            $profile = $recruiter->recruiterProfile;
            
            if (!$profile) {
                throw new \Exception('Recruiter profile not found');
            }
            
            // Validate state transition
            if (!in_array($profile->approval_status, ['pending', 'rejected'])) {
                throw new \Exception('Recruiter cannot be approved from current status: ' . $profile->approval_status);
            }
            
            // Update recruiter profile
            $profile->update([
                'approval_status' => 'approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'suspended_at' => null,
                'suspension_reason' => null,
                'rejection_reason' => null,
            ]);
            
            // Create audit log
            AdminAuditLog::create([
                'admin_user_id' => $adminId,
                'action_type' => AdminAuditLog::APPROVED,
                'target_recruiter_id' => $recruiterId,
                'reason' => null,
                'ip_address' => $ipAddress,
            ]);
            
            // Dispatch event for email notification
            event(new RecruiterApproved($recruiter));
            
            Log::info('Recruiter approved', [
                'recruiter_id' => $recruiterId,
                'admin_id' => $adminId,
                'ip_address' => $ipAddress,
            ]);
            
            return true;
        });
    }
    
    /**
     * Reject a recruiter account
     * 
     * @param int $recruiterId The recruiter user ID
     * @param int $adminId The admin user ID performing the action
     * @param string $reason The reason for rejection
     * @param string $ipAddress The IP address of the admin
     * @return bool Success status
     * @throws \Exception If recruiter not found or invalid state
     */
    public function rejectRecruiter(int $recruiterId, int $adminId, string $reason, string $ipAddress): bool
    {
        return DB::transaction(function () use ($recruiterId, $adminId, $reason, $ipAddress) {
            // Load recruiter with profile
            $recruiter = User::with('recruiterProfile')->findOrFail($recruiterId);
            
            if (!$recruiter->isRecruiter()) {
                throw new \Exception('User is not a recruiter');
            }
            
            $profile = $recruiter->recruiterProfile;
            
            if (!$profile) {
                throw new \Exception('Recruiter profile not found');
            }
            
            // Validate state transition
            if ($profile->approval_status !== 'pending') {
                throw new \Exception('Only pending recruiters can be rejected');
            }
            
            // Update recruiter profile
            $profile->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by' => null,
                'approved_at' => null,
            ]);
            
            // Create audit log
            AdminAuditLog::create([
                'admin_user_id' => $adminId,
                'action_type' => AdminAuditLog::REJECTED,
                'target_recruiter_id' => $recruiterId,
                'reason' => $reason,
                'ip_address' => $ipAddress,
            ]);
            
            // Dispatch event for email notification
            event(new RecruiterRejected($recruiter, $reason));
            
            Log::info('Recruiter rejected', [
                'recruiter_id' => $recruiterId,
                'admin_id' => $adminId,
                'reason' => $reason,
                'ip_address' => $ipAddress,
            ]);
            
            return true;
        });
    }
    
    /**
     * Suspend a recruiter account
     * 
     * @param int $recruiterId The recruiter user ID
     * @param int $adminId The admin user ID performing the action
     * @param string $reason The reason for suspension
     * @param string $ipAddress The IP address of the admin
     * @return bool Success status
     * @throws \Exception If recruiter not found or invalid state
     */
    public function suspendRecruiter(int $recruiterId, int $adminId, string $reason, string $ipAddress): bool
    {
        return DB::transaction(function () use ($recruiterId, $adminId, $reason, $ipAddress) {
            // Load recruiter with profile
            $recruiter = User::with('recruiterProfile')->findOrFail($recruiterId);
            
            if (!$recruiter->isRecruiter()) {
                throw new \Exception('User is not a recruiter');
            }
            
            $profile = $recruiter->recruiterProfile;
            
            if (!$profile) {
                throw new \Exception('Recruiter profile not found');
            }
            
            // Validate state transition
            if ($profile->approval_status !== 'approved') {
                throw new \Exception('Only approved recruiters can be suspended');
            }
            
            // Update recruiter profile
            $profile->update([
                'approval_status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);
            
            // Create audit log
            AdminAuditLog::create([
                'admin_user_id' => $adminId,
                'action_type' => AdminAuditLog::SUSPENDED,
                'target_recruiter_id' => $recruiterId,
                'reason' => $reason,
                'ip_address' => $ipAddress,
            ]);
            
            // Dispatch event for email notification
            event(new RecruiterSuspended($recruiter, $reason));
            
            Log::info('Recruiter suspended', [
                'recruiter_id' => $recruiterId,
                'admin_id' => $adminId,
                'reason' => $reason,
                'ip_address' => $ipAddress,
            ]);
            
            return true;
        });
    }
    
    /**
     * Activate a suspended recruiter account
     * 
     * @param int $recruiterId The recruiter user ID
     * @param int $adminId The admin user ID performing the action
     * @param string $ipAddress The IP address of the admin
     * @return bool Success status
     * @throws \Exception If recruiter not found or invalid state
     */
    public function activateRecruiter(int $recruiterId, int $adminId, string $ipAddress): bool
    {
        return DB::transaction(function () use ($recruiterId, $adminId, $ipAddress) {
            // Load recruiter with profile
            $recruiter = User::with('recruiterProfile')->findOrFail($recruiterId);
            
            if (!$recruiter->isRecruiter()) {
                throw new \Exception('User is not a recruiter');
            }
            
            $profile = $recruiter->recruiterProfile;
            
            if (!$profile) {
                throw new \Exception('Recruiter profile not found');
            }
            
            // Validate state transition
            if ($profile->approval_status !== 'suspended') {
                throw new \Exception('Only suspended recruiters can be activated');
            }
            
            // Update recruiter profile
            $profile->update([
                'approval_status' => 'approved',
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
            
            // Create audit log
            AdminAuditLog::create([
                'admin_user_id' => $adminId,
                'action_type' => AdminAuditLog::ACTIVATED,
                'target_recruiter_id' => $recruiterId,
                'reason' => null,
                'ip_address' => $ipAddress,
            ]);
            
            // Dispatch event for email notification
            event(new RecruiterActivated($recruiter));
            
            Log::info('Recruiter activated', [
                'recruiter_id' => $recruiterId,
                'admin_id' => $adminId,
                'ip_address' => $ipAddress,
            ]);
            
            return true;
        });
    }
}
