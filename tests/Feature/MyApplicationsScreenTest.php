<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Internship;
use App\Models\Application;
use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @test MyApplicationsScreenTest
 * Tests for My Applications screen functionality
 * Requirements: 6.1, 6.2, 6.5, 6.7, 6.8
 */
class MyApplicationsScreenTest extends TestCase
{
    use RefreshDatabase;

    protected $student;
    protected $internship;

    protected function setUp(): void
    {
        parent::setUp();

        // Create student user with profile
        $this->student = User::factory()->create(['role' => 'student']);
        Profile::factory()->create([
            'user_id' => $this->student->id,
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
        ]);

        // Create internship
        $this->internship = Internship::factory()->create([
            'title' => 'Software Developer Intern',
            'organization' => 'Tech Corp',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_displays_application_cards_correctly()
    {
        // Create application
        $application = Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('my-applications'));

        $response->assertStatus(200);
        $response->assertSee('My Applications');
        $response->assertSee($this->internship->title);
        $response->assertSee($this->internship->organization);
        $response->assertSee('Pending');
    }

    /** @test */
    public function it_displays_filter_tabs()
    {
        $response = $this->actingAs($this->student)
            ->get(route('my-applications'));

        $response->assertStatus(200);
        $response->assertSee('All');
        $response->assertSee('Pending');
        $response->assertSee('Under Review');
        $response->assertSee('Interview');
        $response->assertSee('Approved');
        $response->assertSee('Rejected');
    }

    /** @test */
    public function it_includes_filter_javascript()
    {
        $response = $this->actingAs($this->student)
            ->get(route('my-applications'));

        $response->assertStatus(200);
        $response->assertSee('filterApplications', false);
        $response->assertSee('data-filter', false);
        $response->assertSee('filter-tab', false);
    }

    /** @test */
    public function it_displays_status_summary_cards()
    {
        // Create applications with different statuses
        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => Internship::factory()->create(['is_active' => true]),
            'status' => ApplicationStatus::UNDER_REVIEW,
        ]);

        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => Internship::factory()->create(['is_active' => true]),
            'status' => ApplicationStatus::INTERVIEW_SCHEDULED,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('my-applications'));

        $response->assertStatus(200);
        $response->assertSee('Pending');
        $response->assertSee('Under Review');
        $response->assertSee('Interview');
    }

    /** @test */
    public function it_displays_timeline_toggle_button()
    {
        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('my-applications'));

        $response->assertStatus(200);
        $response->assertSee('View Timeline');
        $response->assertSee('toggleTimeline', false);
    }

    /** @test */
    public function it_includes_timeline_javascript()
    {
        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('my-applications'));

        $response->assertStatus(200);
        $response->assertSee('function toggleTimeline', false);
        $response->assertSee('fa-chevron-down', false);
        $response->assertSee('fa-chevron-up', false);
    }

    /** @test */
    public function it_displays_empty_state_when_no_applications()
    {
        $response = $this->actingAs($this->student)
            ->get(route('my-applications'));

        $response->assertStatus(200);
        $response->assertSee('No Applications Yet');
        $response->assertSee('Browse Internships');
        $response->assertSee(route('recommendations.index'));
    }

    /** @test */
    public function it_displays_total_applications_count()
    {
        // Create 3 applications
        Application::factory()->count(3)->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        $response->assertSee('Total Applications');
        $response->assertSee('3');
    }

    /** @test */
    public function it_displays_application_status_badges()
    {
        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        $response->assertSee('Approved');
        $response->assertSee('badge-approved', false);
    }

    /** @test */
    public function it_displays_progress_pipeline_for_non_rejected_applications()
    {
        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::UNDER_REVIEW,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        $response->assertSee('progress-pipeline', false);
    }

    /** @test */
    public function it_does_not_display_progress_pipeline_for_rejected_applications()
    {
        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::REJECTED,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        // Should not see progress pipeline for rejected applications
        $html = $response->getContent();
        $this->assertStringNotContainsString('progress-pipeline', $html);
    }

    /** @test */
    public function it_requires_authentication_to_view_applications()
    {
        $response = $this->get(route('applications.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_only_shows_applications_for_authenticated_user()
    {
        // Create application for this student
        $myApplication = Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
        ]);

        // Create application for another student
        $otherStudent = User::factory()->create(['role' => 'student']);
        Profile::factory()->create(['user_id' => $otherStudent->id]);
        $otherApplication = Application::factory()->create([
            'user_id' => $otherStudent->id,
            'internship_id' => Internship::factory()->create(['is_active' => true]),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        $response->assertSee($myApplication->internship->title);
        $response->assertDontSee($otherApplication->internship->title);
    }

    /** @test */
    public function it_includes_data_application_status_attribute()
    {
        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        $response->assertSee('data-application-status="pending"', false);
    }

    /** @test */
    public function it_displays_filter_tab_counts()
    {
        // Create applications with different statuses
        Application::factory()->count(2)->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => Internship::factory()->create(['is_active' => true]),
            'status' => ApplicationStatus::UNDER_REVIEW,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        // Should see counts in filter tabs
        $response->assertSee('class="count"', false);
    }

    /** @test */
    public function it_includes_no_results_message_element()
    {
        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        $response->assertSee('no-results-message', false);
        $response->assertSee('No Applications Found');
    }

    /** @test */
    public function it_includes_responsive_styling()
    {
        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        $response->assertSee('md:grid-cols-3', false);
        $response->assertSee('pb-20 md:pb-8', false);
    }

    /** @test */
    public function it_includes_accessibility_attributes()
    {
        Application::factory()->create([
            'user_id' => $this->student->id,
            'internship_id' => $this->internship->id,
            'status' => ApplicationStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('applications.index'));

        $response->assertStatus(200);
        $response->assertSee('aria-label', false);
        $response->assertSee('aria-expanded', false);
        $response->assertSee('aria-controls', false);
    }
}
