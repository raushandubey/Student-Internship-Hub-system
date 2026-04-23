<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RecruiterProfileModerationService;
use App\Models\User;
use App\Models\RecruiterProfile;
use App\Models\AdminAuditLog;
use App\Events\RecruiterProfileModified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

class RecruiterProfileModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecruiterProfileModerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecruiterProfileModerationService();
    }

    public function test_updates_recruiter_profile_successfully()
    {
        Event::fake();

        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);

        // Create recruiter user with profile
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        $profile = RecruiterProfile::factory()->create([
            'user_id' => $recruiter->id,
            'organization' => 'Old Organization',
            'description' => 'Old description',
            'website' => 'https://old-website.com',
        ]);

        // Update profile data
        $profileData = [
            'organization' => 'New Organization',
            'description' => 'New description',
            'website' => 'https://new-website.com',
        ];

        $result = $this->service->updateRecruiterProfile(
            $recruiter->id,
            $admin->id,
            $profileData,
            '127.0.0.1'
        );

        $this->assertTrue($result);

        // Verify profile was updated
        $profile->refresh();
        $this->assertEquals('New Organization', $profile->organization);
        $this->assertEquals('New description', $profile->description);
        $this->assertEquals('https://new-website.com', $profile->website);

        // Verify audit log was created
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $admin->id,
            'action_type' => AdminAuditLog::PROFILE_EDITED,
            'target_recruiter_id' => $recruiter->id,
            'ip_address' => '127.0.0.1',
        ]);

        // Verify event was dispatched
        Event::assertDispatched(RecruiterProfileModified::class, function ($event) use ($recruiter, $admin) {
            return $event->recruiter->id === $recruiter->id
                && $event->admin->id === $admin->id;
        });
    }

    public function test_validates_profile_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create(['user_id' => $recruiter->id]);

        // Invalid website URL
        $profileData = [
            'website' => 'not-a-valid-url',
        ];

        $this->expectException(ValidationException::class);

        $this->service->updateRecruiterProfile(
            $recruiter->id,
            $admin->id,
            $profileData,
            '127.0.0.1'
        );
    }

    public function test_throws_exception_for_non_recruiter()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User is not a recruiter');

        $this->service->updateRecruiterProfile(
            $student->id,
            $admin->id,
            ['organization' => 'Test'],
            '127.0.0.1'
        );
    }
}
