<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_fields()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recruiter = User::factory()->create(['role' => 'recruiter']);

        $auditLog = AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action_type' => AdminAuditLog::APPROVED,
            'target_recruiter_id' => $recruiter->id,
            'reason' => 'Test reason',
            'ip_address' => '192.168.1.1',
        ]);

        expect($auditLog->admin_user_id)->toBe($admin->id)
            ->and($auditLog->action_type)->toBe(AdminAuditLog::APPROVED)
            ->and($auditLog->target_recruiter_id)->toBe($recruiter->id)
            ->and($auditLog->reason)->toBe('Test reason')
            ->and($auditLog->ip_address)->toBe('192.168.1.1');
    }

    /** @test */
    public function it_has_admin_relationship()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recruiter = User::factory()->create(['role' => 'recruiter']);

        $auditLog = AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action_type' => AdminAuditLog::SUSPENDED,
            'target_recruiter_id' => $recruiter->id,
            'reason' => 'Policy violation',
            'ip_address' => '127.0.0.1',
        ]);

        expect($auditLog->admin)->toBeInstanceOf(User::class)
            ->and($auditLog->admin->id)->toBe($admin->id)
            ->and($auditLog->admin->role)->toBe('admin');
    }

    /** @test */
    public function it_has_target_recruiter_relationship()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recruiter = User::factory()->create(['role' => 'recruiter']);

        $auditLog = AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action_type' => AdminAuditLog::REJECTED,
            'target_recruiter_id' => $recruiter->id,
            'reason' => 'Invalid organization',
            'ip_address' => '127.0.0.1',
        ]);

        expect($auditLog->targetRecruiter)->toBeInstanceOf(User::class)
            ->and($auditLog->targetRecruiter->id)->toBe($recruiter->id)
            ->and($auditLog->targetRecruiter->role)->toBe('recruiter');
    }

    /** @test */
    public function it_has_scope_by_admin()
    {
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);
        $recruiter = User::factory()->create(['role' => 'recruiter']);

        AdminAuditLog::create([
            'admin_user_id' => $admin1->id,
            'action_type' => AdminAuditLog::APPROVED,
            'target_recruiter_id' => $recruiter->id,
            'ip_address' => '127.0.0.1',
        ]);

        AdminAuditLog::create([
            'admin_user_id' => $admin2->id,
            'action_type' => AdminAuditLog::SUSPENDED,
            'target_recruiter_id' => $recruiter->id,
            'ip_address' => '127.0.0.1',
        ]);

        $admin1Logs = AdminAuditLog::byAdmin($admin1->id)->get();
        $admin2Logs = AdminAuditLog::byAdmin($admin2->id)->get();

        expect($admin1Logs)->toHaveCount(1)
            ->and($admin1Logs->first()->admin_user_id)->toBe($admin1->id)
            ->and($admin2Logs)->toHaveCount(1)
            ->and($admin2Logs->first()->admin_user_id)->toBe($admin2->id);
    }

    /** @test */
    public function it_has_scope_by_action_type()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recruiter1 = User::factory()->create(['role' => 'recruiter']);
        $recruiter2 = User::factory()->create(['role' => 'recruiter']);

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action_type' => AdminAuditLog::APPROVED,
            'target_recruiter_id' => $recruiter1->id,
            'ip_address' => '127.0.0.1',
        ]);

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action_type' => AdminAuditLog::SUSPENDED,
            'target_recruiter_id' => $recruiter2->id,
            'ip_address' => '127.0.0.1',
        ]);

        $approvedLogs = AdminAuditLog::byActionType(AdminAuditLog::APPROVED)->get();
        $suspendedLogs = AdminAuditLog::byActionType(AdminAuditLog::SUSPENDED)->get();

        expect($approvedLogs)->toHaveCount(1)
            ->and($approvedLogs->first()->action_type)->toBe(AdminAuditLog::APPROVED)
            ->and($suspendedLogs)->toHaveCount(1)
            ->and($suspendedLogs->first()->action_type)->toBe(AdminAuditLog::SUSPENDED);
    }

    /** @test */
    public function it_has_scope_by_recruiter()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recruiter1 = User::factory()->create(['role' => 'recruiter']);
        $recruiter2 = User::factory()->create(['role' => 'recruiter']);

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action_type' => AdminAuditLog::APPROVED,
            'target_recruiter_id' => $recruiter1->id,
            'ip_address' => '127.0.0.1',
        ]);

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action_type' => AdminAuditLog::SUSPENDED,
            'target_recruiter_id' => $recruiter1->id,
            'ip_address' => '127.0.0.1',
        ]);

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action_type' => AdminAuditLog::APPROVED,
            'target_recruiter_id' => $recruiter2->id,
            'ip_address' => '127.0.0.1',
        ]);

        $recruiter1Logs = AdminAuditLog::byRecruiter($recruiter1->id)->get();
        $recruiter2Logs = AdminAuditLog::byRecruiter($recruiter2->id)->get();

        expect($recruiter1Logs)->toHaveCount(2)
            ->and($recruiter2Logs)->toHaveCount(1)
            ->and($recruiter2Logs->first()->target_recruiter_id)->toBe($recruiter2->id);
    }

    /** @test */
    public function it_has_all_action_type_constants()
    {
        expect(AdminAuditLog::APPROVED)->toBe('approved')
            ->and(AdminAuditLog::REJECTED)->toBe('rejected')
            ->and(AdminAuditLog::SUSPENDED)->toBe('suspended')
            ->and(AdminAuditLog::ACTIVATED)->toBe('activated')
            ->and(AdminAuditLog::INTERNSHIP_DEACTIVATED)->toBe('internship_deactivated')
            ->and(AdminAuditLog::PROFILE_EDITED)->toBe('profile_edited');
    }
}
