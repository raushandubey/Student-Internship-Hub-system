<?php

use App\Models\Application;
use App\Models\User;
use App\Models\Profile;
use App\Models\Internship;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create an admin user for authentication
    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
        'role' => 'admin',
    ]);
});

test('applications index page contains profile modal structure', function () {
    // Arrange - Create a sample application
    $student = User::factory()->create(['role' => 'student']);
    $profile = Profile::factory()->create(['user_id' => $student->id]);
    $internship = Internship::create([
        'title' => 'Software Developer Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Test internship',
        'is_active' => true,
    ]);
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->get(route('admin.applications.index'));

    // Assert - Verify modal structure exists
    $response->assertStatus(200)
        ->assertSee('id="profileModal"', false)
        ->assertSee('id="profileLoading"', false)
        ->assertSee('id="profileContent"', false)
        ->assertSee('id="profileError"', false);
});

test('profile modal contains all required sections', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    $profile = Profile::factory()->create(['user_id' => $student->id]);
    $internship = Internship::create([
        'title' => 'Software Developer Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Test internship',
        'is_active' => true,
    ]);
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->get(route('admin.applications.index'));

    // Assert - Verify all required sections exist
    $response->assertStatus(200)
        // Profile info section
        ->assertSee('profile-info', false)
        ->assertSee('id="profileName"', false)
        ->assertSee('id="profileEmail"', false)
        ->assertSee('id="profileSkills"', false)
        ->assertSee('id="profileAcademic"', false)
        // Profile about section
        ->assertSee('profile-about', false)
        ->assertSee('id="profileCareerInterests"', false)
        // Profile projects section
        ->assertSee('profile-projects', false)
        ->assertSee('id="profileProjects"', false)
        // AI summary section
        ->assertSee('ai-summary', false)
        ->assertSee('AI-Generated Candidate Summary')
        ->assertSee('id="aiStrengths"', false)
        ->assertSee('id="aiWeaknesses"', false)
        ->assertSee('id="aiAssessment"', false)
        // Resume viewer section
        ->assertSee('resume-viewer', false)
        ->assertSee('id="resumeContent"', false)
        ->assertSee('id="resumePreview"', false)
        ->assertSee('id="resumeIframe"', false)
        ->assertSee('id="resumeDownload"', false)
        ->assertSee('id="resumeNotFound"', false);
});

test('profile modal has close button and modal interactions', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    $profile = Profile::factory()->create(['user_id' => $student->id]);
    $internship = Internship::create([
        'title' => 'Software Developer Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Test internship',
        'is_active' => true,
    ]);
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->get(route('admin.applications.index'));

    // Assert - Verify modal has close functionality
    $response->assertStatus(200)
        ->assertSee('closeProfileModal()', false)
        ->assertSee('Candidate Profile')
        ->assertSee('function closeProfileModal', false);
});

test('applications table has view profile button', function () {
    // Arrange
    $student = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'student',
    ]);
    $profile = Profile::factory()->create(['user_id' => $student->id]);
    $internship = Internship::create([
        'title' => 'Software Developer Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Test internship',
        'is_active' => true,
    ]);
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->get(route('admin.applications.index'));

    // Assert - Verify View Profile button exists
    $response->assertStatus(200)
        ->assertSee('View Profile')
        ->assertSee('openProfileModal', false);
});

test('profile modal is hidden by default', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    $profile = Profile::factory()->create(['user_id' => $student->id]);
    $internship = Internship::create([
        'title' => 'Software Developer Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Test internship',
        'is_active' => true,
    ]);
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->get(route('admin.applications.index'));

    // Assert - Verify modal has hidden class
    $response->assertStatus(200)
        ->assertSee('id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden', false);
});

test('profile content section is hidden by default', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    $profile = Profile::factory()->create(['user_id' => $student->id]);
    $internship = Internship::create([
        'title' => 'Software Developer Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Test internship',
        'is_active' => true,
    ]);
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->get(route('admin.applications.index'));

    // Assert - Verify profile content is hidden by default
    $response->assertStatus(200)
        ->assertSee('id="profileContent" class="hidden', false);
});

test('profile error section is hidden by default', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    $profile = Profile::factory()->create(['user_id' => $student->id]);
    $internship = Internship::create([
        'title' => 'Software Developer Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Test internship',
        'is_active' => true,
    ]);
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->get(route('admin.applications.index'));

    // Assert - Verify error section is hidden by default
    $response->assertStatus(200)
        ->assertSee('id="profileError" class="hidden', false);
});
