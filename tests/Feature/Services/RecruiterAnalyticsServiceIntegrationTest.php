<?php

namespace Tests\Feature\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Internship;
use App\Models\RecruiterProfile;
use App\Models\User;
use App\Services\RecruiterAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruiterAnalyticsServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private RecruiterAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecruiterAnalyticsService::class);
    }

    /** @test */
    public function it_provides_complete_recruiter_stats_for_admin_panel()
    {
        // Create a recruiter with complete data
        $recruiter = User::factory()->create(['role' => 'recruiter', 'name' => 'Tech Corp']);
        RecruiterProfile::factory()->create([
            'user_id' => $recruiter->id,
            'approval_status' => 'approved',
            'organization' => 'Tech Corp Inc',
        ]);

        // Create internships
        $activeInternship1 = Internship::factory()->create([
            'recruiter_id' => $recruiter->id,
            'is_active' => true,
            'title' => 'Software Engineer Intern',
        ]);
        $activeInternship2 = Internship::factory()->create([
            'recruiter_id' => $recruiter->id,
            'is_active' => true,
            'title' => 'Data Analyst Intern',
        ]);
        $inactiveInternship = Internship::factory()->create([
            'recruiter_id' => $recruiter->id,
            'is_active' => false,
            'title' => 'Closed Position',
        ]);

        // Create applications with status changes
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);

        $app1 = Application::factory()->create([
            'internship_id' => $activeInternship1->id,
            'user_id' => $student1->id,
            'status' => ApplicationStatus::APPROVED,
            'created_at' => now()->subDays(10),
        ]);
        ApplicationStatusLog::create([
            'application_id' => $app1->id,
            'from_status' => ApplicationStatus::PENDING->value,
            'to_status' => ApplicationStatus::APPROVED->value,
            'changed_by' => $recruiter->id,
            'actor_type' => 'recruiter',
            'created_at' => now()->subDays(8),
        ]);

        $app2 = Application::factory()->create([
            'internship_id' => $activeInternship1->id,
            'user_id' => $student2->id,
            'status' => ApplicationStatus::PENDING,
            'created_at' => now()->subDays(5),
        ]);

        $app3 = Application::factory()->create([
            'internship_id' => $activeInternship2->id,
            'user_id' => $student3->id,
            'status' => ApplicationStatus::REJECTED,
            'created_at' => now()->subDays(3),
        ]);
        ApplicationStatusLog::create([
            'application_id' => $app3->id,
            'from_status' => ApplicationStatus::PENDING->value,
            'to_status' => ApplicationStatus::REJECTED->value,
            'changed_by' => $recruiter->id,
            'actor_type' => 'recruiter',
            'created_at' => now()->subDays(2),
        ]);

        // Test getRecruiterStats
        $stats = $this->service->getRecruiterStats($recruiter->id);
        
        $this->assertEquals(3, $stats['total_internships']);
        $this->assertEquals(2, $stats['active_internships']);
        $this->assertEquals(3, $stats['total_applications']);
        $this->assertEquals(33.3, $stats['approval_rate']); // 1 approved out of 3
        $this->assertIsFloat($stats['avg_response_time']);
        $this->assertGreaterThan(0, $stats['avg_response_time']);
    }

    /** @test */
    public function it_provides_system_wide_stats_for_admin_dashboard()
    {
        // Create multiple recruiters with different statuses
        $approvedRecruiter1 = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $approvedRecruiter1->id,
            'approval_status' => 'approved',
        ]);

        $approvedRecruiter2 = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $approvedRecruiter2->id,
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

        // Create internships for approved recruiters
        $internship1 = Internship::factory()->create([
            'recruiter_id' => $approvedRecruiter1->id,
            'is_active' => true,
        ]);
        $internship2 = Internship::factory()->create([
            'recruiter_id' => $approvedRecruiter1->id,
            'is_active' => false,
        ]);
        $internship3 = Internship::factory()->create([
            'recruiter_id' => $approvedRecruiter2->id,
            'is_active' => true,
        ]);

        // Create applications
        $student = User::factory()->create(['role' => 'student']);
        Application::factory()->create([
            'internship_id' => $internship1->id,
            'user_id' => $student->id,
        ]);
        Application::factory()->create([
            'internship_id' => $internship3->id,
            'user_id' => $student->id,
        ]);

        // Test getSystemWideRecruiterStats
        $stats = $this->service->getSystemWideRecruiterStats();

        $this->assertEquals(2, $stats['approved_recruiters']);
        $this->assertEquals(1, $stats['pending_recruiters']);
        $this->assertEquals(1, $stats['suspended_recruiters']);
        $this->assertEquals(3, $stats['total_recruiter_internships']);
        $this->assertEquals(2, $stats['active_recruiter_internships']);
        $this->assertEquals(2, $stats['applications_to_recruiter_internships']);
    }

    /** @test */
    public function it_provides_performance_data_with_date_range_filtering()
    {
        // Create recruiter
        $recruiter = User::factory()->create(['role' => 'recruiter', 'name' => 'Performance Test']);
        RecruiterProfile::factory()->create([
            'user_id' => $recruiter->id,
            'approval_status' => 'approved',
            'organization' => 'Test Org',
        ]);

        // Create internships at different times
        $oldInternship = Internship::factory()->create([
            'recruiter_id' => $recruiter->id,
            'created_at' => now()->subMonths(3),
        ]);
        $recentInternship = Internship::factory()->create([
            'recruiter_id' => $recruiter->id,
            'created_at' => now()->subDays(15),
        ]);

        // Create applications at different times
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        
        Application::factory()->create([
            'internship_id' => $oldInternship->id,
            'user_id' => $student1->id,
            'status' => ApplicationStatus::APPROVED,
            'created_at' => now()->subMonths(2),
        ]);
        
        Application::factory()->create([
            'internship_id' => $recentInternship->id,
            'user_id' => $student2->id,
            'status' => ApplicationStatus::PENDING,
            'created_at' => now()->subDays(10),
        ]);

        // Test without date range
        $allData = $this->service->getRecruiterPerformanceData();
        $this->assertCount(1, $allData);
        $this->assertEquals(2, $allData[0]['total_internships']);
        $this->assertEquals(2, $allData[0]['total_applications']);

        // Test with date range (last 30 days)
        $dateRange = [
            'start' => now()->subDays(30),
            'end' => now(),
        ];
        $filteredData = $this->service->getRecruiterPerformanceData($dateRange);
        
        $this->assertCount(1, $filteredData);
        $this->assertEquals(1, $filteredData[0]['total_internships']); // Only recent internship
        $this->assertEquals(1, $filteredData[0]['total_applications']); // Only recent application
    }

    /** @test */
    public function it_identifies_recruiters_exceeding_response_threshold()
    {
        // Create recruiter with slow response time
        $slowRecruiter = User::factory()->create(['role' => 'recruiter', 'name' => 'Slow Responder']);
        RecruiterProfile::factory()->create([
            'user_id' => $slowRecruiter->id,
            'approval_status' => 'approved',
            'organization' => 'Slow Corp',
        ]);

        $internship = Internship::factory()->create([
            'recruiter_id' => $slowRecruiter->id,
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $app = Application::factory()->create([
            'internship_id' => $internship->id,
            'user_id' => $student->id,
            'status' => ApplicationStatus::APPROVED,
            'created_at' => now()->subDays(20),
        ]);

        // Create status log showing response after 10 days (exceeds 7-day threshold)
        ApplicationStatusLog::create([
            'application_id' => $app->id,
            'from_status' => ApplicationStatus::PENDING->value,
            'to_status' => ApplicationStatus::APPROVED->value,
            'changed_by' => $slowRecruiter->id,
            'actor_type' => 'recruiter',
            'created_at' => now()->subDays(10),
        ]);

        $performanceData = $this->service->getRecruiterPerformanceData();

        $this->assertCount(1, $performanceData);
        $this->assertTrue($performanceData[0]['exceeds_response_threshold']);
        $this->assertGreaterThan(7, $performanceData[0]['avg_response_time']);
    }

    /** @test */
    public function it_calculates_fill_rate_correctly()
    {
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        RecruiterProfile::factory()->create([
            'user_id' => $recruiter->id,
            'approval_status' => 'approved',
            'organization' => 'Fill Rate Test',
        ]);

        // Create 3 internships
        $internship1 = Internship::factory()->create(['recruiter_id' => $recruiter->id]);
        $internship2 = Internship::factory()->create(['recruiter_id' => $recruiter->id]);
        $internship3 = Internship::factory()->create(['recruiter_id' => $recruiter->id]);

        // Only internship1 and internship2 have approved applications
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);

        Application::factory()->create([
            'internship_id' => $internship1->id,
            'user_id' => $student1->id,
            'status' => ApplicationStatus::APPROVED,
        ]);

        Application::factory()->create([
            'internship_id' => $internship2->id,
            'user_id' => $student2->id,
            'status' => ApplicationStatus::APPROVED,
        ]);

        Application::factory()->create([
            'internship_id' => $internship3->id,
            'user_id' => $student3->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        $performanceData = $this->service->getRecruiterPerformanceData();

        $this->assertCount(1, $performanceData);
        $this->assertEquals(66.7, $performanceData[0]['fill_rate']); // 2 out of 3 internships filled
    }
}
