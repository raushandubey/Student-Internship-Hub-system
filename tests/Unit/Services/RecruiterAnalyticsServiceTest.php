<?php

namespace Tests\Unit\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Internship;
use App\Models\RecruiterProfile;
use App\Models\User;
use App\Services\RecruiterAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruiterAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecruiterAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecruiterAnalyticsService();
    }

    /** @test */
    public function it_gets_individual_recruiter_stats()
    {
        // Create a recruiter with profile
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $recruiter->id,
            'approval_status' => 'approved',
        ]);

        // Create internships
        $activeInternship = Internship::factory()->create([
            'recruiter_id' => $recruiter->id,
            'is_active' => true,
        ]);
        $inactiveInternship = Internship::factory()->create([
            'recruiter_id' => $recruiter->id,
            'is_active' => false,
        ]);

        // Create applications
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        Application::factory()->create([
            'internship_id' => $activeInternship->id,
            'user_id' => $student1->id,
            'status' => ApplicationStatus::APPROVED,
        ]);
        Application::factory()->create([
            'internship_id' => $activeInternship->id,
            'user_id' => $student2->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        $stats = $this->service->getRecruiterStats($recruiter->id);

        $this->assertEquals(2, $stats['total_internships']);
        $this->assertEquals(1, $stats['active_internships']);
        $this->assertEquals(2, $stats['total_applications']);
        $this->assertEquals(50.0, $stats['approval_rate']); // 1 approved out of 2
    }

    /** @test */
    public function it_gets_system_wide_recruiter_stats()
    {
        // Create recruiters with different statuses
        $approvedRecruiter = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $approvedRecruiter->id,
            'approval_status' => 'approved',
        ]);

        $pendingRecruiter = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $pendingRecruiter->id,
            'approval_status' => 'pending',
        ]);

        $suspendedRecruiter = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $suspendedRecruiter->id,
            'approval_status' => 'suspended',
        ]);

        // Create internships
        $activeInternship = Internship::factory()->create([
            'recruiter_id' => $approvedRecruiter->id,
            'is_active' => true,
        ]);
        $inactiveInternship = Internship::factory()->create([
            'recruiter_id' => $approvedRecruiter->id,
            'is_active' => false,
        ]);

        // Create applications
        $student = User::factory()->create(['role' => 'student']);
        Application::factory()->create([
            'internship_id' => $activeInternship->id,
            'user_id' => $student->id,
        ]);

        $stats = $this->service->getSystemWideRecruiterStats();

        $this->assertEquals(1, $stats['approved_recruiters']);
        $this->assertEquals(1, $stats['pending_recruiters']);
        $this->assertEquals(1, $stats['suspended_recruiters']);
        $this->assertEquals(2, $stats['total_recruiter_internships']);
        $this->assertEquals(1, $stats['active_recruiter_internships']);
        $this->assertEquals(1, $stats['applications_to_recruiter_internships']);
    }

    /** @test */
    public function it_gets_recruiter_performance_data()
    {
        // Create approved recruiter
        $recruiter = User::factory()->create(['role' => 'recruiter', 'name' => 'Test Recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $recruiter->id,
            'approval_status' => 'approved',
            'organization' => 'Test Org',
        ]);

        // Create internship
        $internship = Internship::factory()->create([
            'recruiter_id' => $recruiter->id,
            'is_active' => true,
        ]);

        // Create applications
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        Application::factory()->create([
            'internship_id' => $internship->id,
            'user_id' => $student1->id,
            'status' => ApplicationStatus::APPROVED,
        ]);
        Application::factory()->create([
            'internship_id' => $internship->id,
            'user_id' => $student2->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        $performanceData = $this->service->getRecruiterPerformanceData();

        $this->assertCount(1, $performanceData);
        $this->assertEquals('Test Recruiter', $performanceData[0]['recruiter_name']);
        $this->assertEquals('Test Org', $performanceData[0]['organization']);
        $this->assertEquals(1, $performanceData[0]['total_internships']);
        $this->assertEquals(2, $performanceData[0]['total_applications']);
        $this->assertEquals(50.0, $performanceData[0]['approval_rate']);
        $this->assertEquals(100.0, $performanceData[0]['fill_rate']); // 1 internship with approvals
    }

    /** @test */
    public function it_calculates_approval_rate_correctly()
    {
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $recruiter->id,
            'approval_status' => 'approved',
        ]);

        $internship = Internship::factory()->create([
            'recruiter_id' => $recruiter->id,
        ]);

        // Create 3 approved and 7 pending applications with different students
        for ($i = 0; $i < 3; $i++) {
            $student = User::factory()->create(['role' => 'student']);
            Application::factory()->create([
                'internship_id' => $internship->id,
                'user_id' => $student->id,
                'status' => ApplicationStatus::APPROVED,
            ]);
        }
        for ($i = 0; $i < 7; $i++) {
            $student = User::factory()->create(['role' => 'student']);
            Application::factory()->create([
                'internship_id' => $internship->id,
                'user_id' => $student->id,
                'status' => ApplicationStatus::PENDING,
            ]);
        }

        $stats = $this->service->getRecruiterStats($recruiter->id);

        $this->assertEquals(30.0, $stats['approval_rate']); // 3/10 = 30%
    }

    /** @test */
    public function it_handles_recruiter_with_no_data()
    {
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $recruiter->id,
            'approval_status' => 'approved',
        ]);

        $stats = $this->service->getRecruiterStats($recruiter->id);

        $this->assertEquals(0, $stats['total_internships']);
        $this->assertEquals(0, $stats['active_internships']);
        $this->assertEquals(0, $stats['total_applications']);
        $this->assertEquals(0, $stats['approval_rate']);
        $this->assertNull($stats['avg_response_time']);
    }
}
