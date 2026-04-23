<?php

use App\Models\User;
use App\Models\RecruiterProfile;
use App\Models\AdminAuditLog;
use App\Services\RecruiterManagementService;
use App\Events\RecruiterApproved;
use App\Events\RecruiterRejected;
use App\Events\RecruiterSuspended;
use App\Events\RecruiterActivated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RecruiterManagementService();
    Event::fake();
});

test('approves a pending recruiter successfully', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $profile = RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'pending',
    ]);

    // Act
    $result = $this->service->approveRecruiter($recruiter->id, $admin->id, '127.0.0.1');

    // Assert
    expect($result)->toBeTrue();
    
    $profile->refresh();
    expect($profile->approval_status)->toBe('approved')
        ->and($profile->approved_by)->toBe($admin->id)
        ->and($profile->approved_at)->not->toBeNull()
        ->and($profile->suspended_at)->toBeNull()
        ->and($profile->suspension_reason)->toBeNull()
        ->and($profile->rejection_reason)->toBeNull();
    
    // Check audit log was created
    $auditLog = AdminAuditLog::where('target_recruiter_id', $recruiter->id)->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->admin_user_id)->toBe($admin->id)
        ->and($auditLog->action_type)->toBe(AdminAuditLog::APPROVED)
        ->and($auditLog->ip_address)->toBe('127.0.0.1');
    
    // Check event was dispatched
    Event::assertDispatched(RecruiterApproved::class, function ($event) use ($recruiter) {
        return $event->recruiter->id === $recruiter->id;
    });
});

test('approves a rejected recruiter successfully', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $profile = RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'rejected',
        'rejection_reason' => 'Previous rejection reason',
    ]);

    // Act
    $result = $this->service->approveRecruiter($recruiter->id, $admin->id, '127.0.0.1');

    // Assert
    expect($result)->toBeTrue();
    
    $profile->refresh();
    expect($profile->approval_status)->toBe('approved')
        ->and($profile->rejection_reason)->toBeNull();
});

test('throws exception when approving non-recruiter user', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'student']);

    // Act & Assert
    expect(fn() => $this->service->approveRecruiter($student->id, $admin->id, '127.0.0.1'))
        ->toThrow(Exception::class, 'User is not a recruiter');
});

test('throws exception when approving recruiter from invalid status', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'suspended',
    ]);

    // Act & Assert
    expect(fn() => $this->service->approveRecruiter($recruiter->id, $admin->id, '127.0.0.1'))
        ->toThrow(Exception::class);
});

test('rejects a pending recruiter successfully', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $profile = RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'pending',
    ]);
    $reason = 'Incomplete organization information';

    // Act
    $result = $this->service->rejectRecruiter($recruiter->id, $admin->id, $reason, '127.0.0.1');

    // Assert
    expect($result)->toBeTrue();
    
    $profile->refresh();
    expect($profile->approval_status)->toBe('rejected')
        ->and($profile->rejection_reason)->toBe($reason)
        ->and($profile->approved_by)->toBeNull()
        ->and($profile->approved_at)->toBeNull();
    
    // Check audit log was created
    $auditLog = AdminAuditLog::where('target_recruiter_id', $recruiter->id)->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->admin_user_id)->toBe($admin->id)
        ->and($auditLog->action_type)->toBe(AdminAuditLog::REJECTED)
        ->and($auditLog->reason)->toBe($reason)
        ->and($auditLog->ip_address)->toBe('127.0.0.1');
    
    // Check event was dispatched
    Event::assertDispatched(RecruiterRejected::class, function ($event) use ($recruiter, $reason) {
        return $event->recruiter->id === $recruiter->id && $event->reason === $reason;
    });
});

test('throws exception when rejecting non-pending recruiter', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'approved',
    ]);

    // Act & Assert
    expect(fn() => $this->service->rejectRecruiter($recruiter->id, $admin->id, 'Some reason', '127.0.0.1'))
        ->toThrow(Exception::class, 'Only pending recruiters can be rejected');
});

test('suspends an approved recruiter successfully', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $profile = RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'approved',
    ]);
    $reason = 'Policy violation';

    // Act
    $result = $this->service->suspendRecruiter($recruiter->id, $admin->id, $reason, '127.0.0.1');

    // Assert
    expect($result)->toBeTrue();
    
    $profile->refresh();
    expect($profile->approval_status)->toBe('suspended')
        ->and($profile->suspended_at)->not->toBeNull()
        ->and($profile->suspension_reason)->toBe($reason);
    
    // Check audit log was created
    $auditLog = AdminAuditLog::where('target_recruiter_id', $recruiter->id)->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->admin_user_id)->toBe($admin->id)
        ->and($auditLog->action_type)->toBe(AdminAuditLog::SUSPENDED)
        ->and($auditLog->reason)->toBe($reason)
        ->and($auditLog->ip_address)->toBe('127.0.0.1');
    
    // Check event was dispatched
    Event::assertDispatched(RecruiterSuspended::class, function ($event) use ($recruiter, $reason) {
        return $event->recruiter->id === $recruiter->id && $event->reason === $reason;
    });
});

test('throws exception when suspending non-approved recruiter', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'pending',
    ]);

    // Act & Assert
    expect(fn() => $this->service->suspendRecruiter($recruiter->id, $admin->id, 'Some reason', '127.0.0.1'))
        ->toThrow(Exception::class, 'Only approved recruiters can be suspended');
});

test('activates a suspended recruiter successfully', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    $profile = RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'suspended',
        'suspended_at' => now(),
        'suspension_reason' => 'Previous violation',
    ]);

    // Act
    $result = $this->service->activateRecruiter($recruiter->id, $admin->id, '127.0.0.1');

    // Assert
    expect($result)->toBeTrue();
    
    $profile->refresh();
    expect($profile->approval_status)->toBe('approved')
        ->and($profile->suspended_at)->toBeNull()
        ->and($profile->suspension_reason)->toBeNull();
    
    // Check audit log was created
    $auditLog = AdminAuditLog::where('target_recruiter_id', $recruiter->id)->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->admin_user_id)->toBe($admin->id)
        ->and($auditLog->action_type)->toBe(AdminAuditLog::ACTIVATED)
        ->and($auditLog->ip_address)->toBe('127.0.0.1');
    
    // Check event was dispatched
    Event::assertDispatched(RecruiterActivated::class, function ($event) use ($recruiter) {
        return $event->recruiter->id === $recruiter->id;
    });
});

test('throws exception when activating non-suspended recruiter', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'approved',
    ]);

    // Act & Assert
    expect(fn() => $this->service->activateRecruiter($recruiter->id, $admin->id, '127.0.0.1'))
        ->toThrow(Exception::class, 'Only suspended recruiters can be activated');
});

test('all operations use database transactions', function () {
    // Arrange
    $admin = User::factory()->create(['role' => 'admin']);
    $recruiter = User::factory()->create(['role' => 'recruiter']);
    RecruiterProfile::factory()->create([
        'user_id' => $recruiter->id,
        'approval_status' => 'pending',
    ]);

    // Mock Event to throw exception after profile update
    Event::fake();
    Event::shouldReceive('dispatch')->andThrow(new Exception('Event dispatch failed'));

    // Act & Assert - Transaction should rollback
    try {
        $this->service->approveRecruiter($recruiter->id, $admin->id, '127.0.0.1');
    } catch (Exception $e) {
        // Expected to throw
    }

    // Profile should not be updated due to transaction rollback
    $profile = RecruiterProfile::where('user_id', $recruiter->id)->first();
    expect($profile->approval_status)->toBe('pending');
    
    // Audit log should not be created due to transaction rollback
    $auditLog = AdminAuditLog::where('target_recruiter_id', $recruiter->id)->first();
    expect($auditLog)->toBeNull();
});
