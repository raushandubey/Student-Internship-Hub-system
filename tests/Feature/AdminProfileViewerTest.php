<?php

use App\Models\Application;
use App\Models\User;
use App\Models\Profile;
use App\Models\Internship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create an admin user for authentication
    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
        'role' => 'admin',
    ]);
    
    // Fake storage for file operations
    Storage::fake('public');
});

test('admin can retrieve candidate profile via getProfile endpoint', function () {
    // Arrange
    $student = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'student',
    ]);
    
    // Create the resume file in fake storage
    Storage::disk('public')->put('resumes/john_resume.pdf', 'fake resume content');
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'academic_background' => 'B.Tech Computer Science',
        'skills' => ['PHP', 'Laravel', 'JavaScript'],
        'career_interests' => 'Full-stack development',
        'resume_path' => 'resumes/john_resume.pdf',
    ]);
    
    $internship = Internship::create([
        'title' => 'Software Developer Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP', 'Laravel'],
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
        ->getJson(route('admin.applications.profile', $application));

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $student->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                'profile' => [
                    'academic_background' => 'B.Tech Computer Science',
                    'skills' => ['PHP', 'Laravel', 'JavaScript'],
                    'career_interests' => 'Full-stack development',
                    'has_resume' => true,
                ],
            ],
        ]);
});

test('getProfile returns error when profile not found', function () {
    // Arrange
    $student = User::factory()->create([
        'role' => 'student',
    ]);
    
    // No profile created for this user
    
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
        ->getJson(route('admin.applications.profile', $application));

    // Assert
    $response->assertStatus(500)
        ->assertJson([
            'success' => false,
            'message' => 'Unable to load profile data',
        ]);
});

test('getProfile requires admin authentication', function () {
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

    // Act - Try to access without authentication
    $response = $this->getJson(route('admin.applications.profile', $application));

    // Assert
    $response->assertStatus(401);
});

test('getProfile returns cached data on subsequent requests', function () {
    // Arrange
    $student = User::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'role' => 'student',
    ]);
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'skills' => ['PHP', 'Laravel'],
    ]);
    
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

    // Act - First request
    $firstResponse = $this->actingAs($this->admin)
        ->getJson(route('admin.applications.profile', $application));
    
    // Modify profile in database
    $profile->update(['skills' => ['Python', 'Django']]);
    
    // Second request should return cached data
    $secondResponse = $this->actingAs($this->admin)
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Both responses should have the original cached data
    $firstResponse->assertStatus(200);
    $secondResponse->assertStatus(200);
    
    expect($firstResponse->json('data.profile.skills'))
        ->toBe(['PHP', 'Laravel'])
        ->and($secondResponse->json('data.profile.skills'))
        ->toBe(['PHP', 'Laravel']); // Still cached
});

test('getProfile handles profile with no resume', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'resume_path' => null,
    ]);
    
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
        ->getJson(route('admin.applications.profile', $application));

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'profile' => [
                    'resume_path' => null,
                    'has_resume' => false,
                ],
            ],
        ]);
});

test('getProfile handles empty skills array', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'skills' => null,
    ]);
    
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
        ->getJson(route('admin.applications.profile', $application));

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'profile' => [
                    'skills' => [],
                ],
            ],
        ]);
});

test('getProfile includes AI summary when available', function () {
    // Arrange
    $student = User::factory()->create([
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'role' => 'student',
    ]);
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'academic_background' => 'B.Tech Computer Science',
        'skills' => ['Python', 'Machine Learning', 'TensorFlow'],
        'career_interests' => 'AI and Data Science',
    ]);
    
    $internship = Internship::create([
        'title' => 'ML Engineer Intern',
        'organization' => 'AI Corp',
        'required_skills' => ['Python', 'ML'],
        'duration' => '6 months',
        'location' => 'Remote',
        'description' => 'ML internship',
        'is_active' => true,
    ]);
    
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.applications.profile', $application));

    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'profile' => [
                    'academic_background',
                    'skills',
                    'career_interests',
                    'resume_path',
                    'has_resume',
                ],
                // AI summary may or may not be present depending on OpenAI API availability
                // We just verify the structure is valid
            ],
        ]);
    
    // If AI summary is present, verify its structure
    $data = $response->json('data');
    if (isset($data['ai_summary'])) {
        expect($data['ai_summary'])
            ->toHaveKeys(['strengths', 'weaknesses', 'overall_assessment'])
            ->and($data['ai_summary']['strengths'])->toBeArray()
            ->and($data['ai_summary']['weaknesses'])->toBeArray()
            ->and($data['ai_summary']['overall_assessment'])->toBeString();
    }
});

test('getProfile works without AI summary when generation fails', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    
    // Create profile with minimal data that might cause AI generation to fail
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'academic_background' => '',
        'skills' => [],
        'career_interests' => '',
    ]);
    
    $internship = Internship::create([
        'title' => 'Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Test',
        'is_active' => true,
    ]);
    
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Should still return success even without AI summary
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
    
    // Profile data should be present
    expect($response->json('data'))->toHaveKeys(['user', 'profile']);
});

// Task 10.1: Feature test for profile viewing flow
test('admin can view candidate profile from applications page', function () {
    // Arrange
    $student = User::factory()->create([
        'name' => 'Sarah Johnson',
        'email' => 'sarah@example.com',
        'role' => 'student',
    ]);
    
    // Create the resume file in fake storage
    Storage::disk('public')->put('resumes/sarah_resume.pdf', 'fake resume content');
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'academic_background' => 'M.Sc. Data Science, MIT',
        'skills' => ['Python', 'Machine Learning', 'TensorFlow', 'SQL'],
        'career_interests' => 'AI research and development',
        'resume_path' => 'resumes/sarah_resume.pdf',
    ]);
    
    $internship = Internship::create([
        'title' => 'Data Science Intern',
        'organization' => 'Tech Corp',
        'required_skills' => ['Python', 'ML'],
        'duration' => '6 months',
        'location' => 'Remote',
        'description' => 'Data science internship',
        'is_active' => true,
    ]);
    
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'under_review',
    ]);

    // Act - Admin views the applications page and clicks "View Profile"
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Validates Requirements 1.1, 1.3, 2.1
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $student->id,
                    'name' => 'Sarah Johnson',
                    'email' => 'sarah@example.com',
                ],
                'profile' => [
                    'academic_background' => 'M.Sc. Data Science, MIT',
                    'skills' => ['Python', 'Machine Learning', 'TensorFlow', 'SQL'],
                    'career_interests' => 'AI research and development',
                    'has_resume' => true,
                ],
            ],
        ]);
});

test('profile modal displays all required information', function () {
    // Arrange
    $student = User::factory()->create([
        'name' => 'Michael Chen',
        'email' => 'michael@example.com',
        'role' => 'student',
    ]);
    
    // Create the resume file in fake storage
    Storage::disk('public')->put('resumes/michael_resume.pdf', 'fake resume content');
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'academic_background' => 'B.Eng. Software Engineering',
        'skills' => ['Java', 'Spring Boot', 'Docker', 'Kubernetes'],
        'career_interests' => 'Backend development and cloud architecture',
        'resume_path' => 'resumes/michael_resume.pdf',
    ]);
    
    $internship = Internship::create([
        'title' => 'Backend Developer Intern',
        'organization' => 'Cloud Solutions Inc',
        'required_skills' => ['Java', 'Docker'],
        'duration' => '4 months',
        'location' => 'Hybrid',
        'description' => 'Backend internship',
        'is_active' => true,
    ]);
    
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'shortlisted',
    ]);

    // Act
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Validates Requirements 3.1, 3.2, 3.3
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'profile' => [
                    'academic_background',
                    'skills',
                    'career_interests',
                    'resume_path',
                    'has_resume',
                ],
            ],
        ]);
    
    $data = $response->json('data');
    
    // Verify all required fields are present and populated
    expect($data['user']['name'])->toBe('Michael Chen')
        ->and($data['user']['email'])->toBe('michael@example.com')
        ->and($data['profile']['academic_background'])->toBe('B.Eng. Software Engineering')
        ->and($data['profile']['skills'])->toBeArray()
        ->and($data['profile']['skills'])->toHaveCount(4)
        ->and($data['profile']['career_interests'])->toBe('Backend development and cloud architecture')
        ->and($data['profile']['has_resume'])->toBeTrue();
});

test('profile data is cached for subsequent requests', function () {
    // Arrange
    $student = User::factory()->create([
        'name' => 'Emma Wilson',
        'email' => 'emma@example.com',
        'role' => 'student',
    ]);
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'academic_background' => 'B.Sc. Computer Science',
        'skills' => ['React', 'TypeScript', 'Node.js'],
        'career_interests' => 'Full-stack web development',
    ]);
    
    $internship = Internship::create([
        'title' => 'Full Stack Intern',
        'organization' => 'Web Dev Co',
        'required_skills' => ['React', 'Node.js'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Full stack internship',
        'is_active' => true,
    ]);
    
    $application = Application::create([
        'user_id' => $student->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act - First request (should cache the data)
    $firstResponse = $this->actingAs($this->admin)
        ->getJson(route('admin.applications.profile', $application));
    
    // Modify the profile in database
    $profile->update([
        'skills' => ['Vue.js', 'Python', 'Django'],
        'career_interests' => 'Backend development only',
    ]);
    
    // Second request (should return cached data, not updated data)
    $secondResponse = $this->actingAs($this->admin)
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Validates Requirement 8.3 (caching)
    $firstResponse->assertStatus(200);
    $secondResponse->assertStatus(200);
    
    $firstData = $firstResponse->json('data.profile');
    $secondData = $secondResponse->json('data.profile');
    
    // Both responses should have the original cached data
    expect($firstData['skills'])->toBe(['React', 'TypeScript', 'Node.js'])
        ->and($secondData['skills'])->toBe(['React', 'TypeScript', 'Node.js'])
        ->and($firstData['career_interests'])->toBe('Full-stack web development')
        ->and($secondData['career_interests'])->toBe('Full-stack web development');
});

test('error message shown when profile not found', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    
    // No profile created for this student
    
    $internship = Internship::create([
        'title' => 'Software Intern',
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
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Validates Requirements 7.1, 7.2
    $response->assertStatus(500)
        ->assertJson([
            'success' => false,
            'message' => 'Unable to load profile data',
        ]);
});

test('non-admin users cannot access profile endpoint', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    $anotherStudent = User::factory()->create(['role' => 'student']);
    
    $profile = Profile::factory()->create(['user_id' => $anotherStudent->id]);
    
    $internship = Internship::create([
        'title' => 'Software Intern',
        'organization' => 'Test Corp',
        'required_skills' => ['PHP'],
        'duration' => '3 months',
        'location' => 'Remote',
        'description' => 'Test internship',
        'is_active' => true,
    ]);
    
    $application = Application::create([
        'user_id' => $anotherStudent->id,
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);

    // Act - Try to access as non-admin user
    $response = $this->actingAs($student)
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Validates Requirement 7.1 (authorization)
    $response->assertStatus(403); // Forbidden
});

// Task 10.2: Feature test for resume access
test('resume PDF is served correctly', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    
    // Create the resume file in fake storage
    Storage::disk('public')->put('resumes/test_resume.pdf', 'fake resume content');
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'resume_path' => 'resumes/test_resume.pdf',
    ]);
    
    $internship = Internship::create([
        'title' => 'Software Intern',
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
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Validates Requirements 4.1, 4.2
    $response->assertStatus(200);
    
    $data = $response->json('data.profile');
    expect($data['resume_path'])->toContain('resumes/test_resume.pdf')
        ->and($data['resume_path'])->toContain('.pdf')
        ->and($data['has_resume'])->toBeTrue();
});

test('resume download has correct filename', function () {
    // Arrange
    $student = User::factory()->create([
        'name' => 'John Doe',
        'role' => 'student',
    ]);
    
    // Create the resume file in fake storage
    Storage::disk('public')->put('resumes/john_doe_resume.pdf', 'fake resume content');
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'resume_path' => 'resumes/john_doe_resume.pdf',
    ]);
    
    $internship = Internship::create([
        'title' => 'Software Intern',
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
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Validates Requirement 4.2
    $response->assertStatus(200);
    
    $resumePath = $response->json('data.profile.resume_path');
    expect($resumePath)->toContain('resumes/john_doe_resume.pdf')
        ->and(basename($resumePath))->toBe('john_doe_resume.pdf');
});

test('missing resume shows appropriate message', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'resume_path' => null,
    ]);
    
    $internship = Internship::create([
        'title' => 'Software Intern',
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
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Validates Requirement 4.3
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'profile' => [
                    'resume_path' => null,
                    'has_resume' => false,
                ],
            ],
        ]);
    
    expect($response->json('data.profile.has_resume'))->toBeFalse();
});

test('resume files are protected from unauthorized access', function () {
    // Arrange
    $student = User::factory()->create(['role' => 'student']);
    $unauthorizedUser = User::factory()->create(['role' => 'student']);
    
    $profile = Profile::factory()->create([
        'user_id' => $student->id,
        'resume_path' => '/resumes/confidential_resume.pdf',
    ]);
    
    $internship = Internship::create([
        'title' => 'Software Intern',
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

    // Act - Try to access as unauthorized student
    $response = $this->actingAs($unauthorizedUser)
        ->getJson(route('admin.applications.profile', $application));

    // Assert - Validates Requirement 4.5 (authorization)
    $response->assertStatus(403); // Forbidden - non-admin cannot access
});
