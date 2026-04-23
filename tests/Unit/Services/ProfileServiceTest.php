<?php

use App\Models\Profile;
use App\Models\User;
use App\Services\CandidateSummaryService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock CandidateSummaryService
    $this->summaryService = Mockery::mock(CandidateSummaryService::class);
    $this->service = new ProfileService($this->summaryService);
});

test('retrieves profile data for valid user', function () {
    // Arrange
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'academic_background' => 'B.Tech Computer Science',
        'skills' => ['PHP', 'Laravel', 'JavaScript'],
        'career_interests' => 'Full-stack development',
        'resume_path' => '/resumes/john_resume.pdf',
    ]);

    // Mock AI summary generation
    $this->summaryService->shouldReceive('generateSummary')
        ->once()
        ->with(Mockery::type(Profile::class))
        ->andReturn([
            'strengths' => ['Strong technical skills'],
            'weaknesses' => ['Limited experience'],
            'overall_assessment' => 'Good candidate'
        ]);

    // Act
    $result = $this->service->getProfileForAdmin($user->id);

    // Assert
    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['user', 'profile', 'ai_summary'])
        ->and($result['user']['id'])->toBe($user->id)
        ->and($result['user']['name'])->toBe('John Doe')
        ->and($result['user']['email'])->toBe('john@example.com')
        ->and($result['profile']['academic_background'])->toBe('B.Tech Computer Science')
        ->and($result['profile']['skills'])->toBe(['PHP', 'Laravel', 'JavaScript'])
        ->and($result['profile']['career_interests'])->toBe('Full-stack development')
        ->and($result['profile']['resume_path'])->toBe('/resumes/john_resume.pdf')
        ->and($result['profile']['has_resume'])->toBeTrue()
        ->and($result['ai_summary'])->toHaveKeys(['strengths', 'weaknesses', 'overall_assessment']);
});

test('returns cached data on second request', function () {
    // Arrange
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'skills' => ['PHP', 'Laravel'],
    ]);

    // Mock AI summary generation (should only be called once)
    $this->summaryService->shouldReceive('generateSummary')
        ->once()
        ->andReturn([
            'strengths' => ['Strong skills'],
            'weaknesses' => [],
            'overall_assessment' => 'Good'
        ]);

    // Act - First call should cache the data
    $firstResult = $this->service->getProfileForAdmin($user->id);
    
    // Modify the profile in database
    $profile->update(['skills' => ['Python', 'Django']]);
    
    // Second call should return cached data (not the updated data)
    $secondResult = $this->service->getProfileForAdmin($user->id);

    // Assert - Second result should match first result (cached)
    expect($secondResult)->toBe($firstResult)
        ->and($secondResult['profile']['skills'])->toBe(['PHP', 'Laravel']);
});

test('formats profile data correctly for JSON response', function () {
    // Arrange
    $user = User::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);
    
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'academic_background' => 'M.Tech AI',
        'skills' => ['Python', 'TensorFlow'],
        'career_interests' => 'Machine Learning',
        'resume_path' => null, // No resume
    ]);

    // Mock AI summary generation
    $this->summaryService->shouldReceive('generateSummary')
        ->once()
        ->andReturn([
            'strengths' => ['AI expertise'],
            'weaknesses' => [],
            'overall_assessment' => 'Excellent'
        ]);

    // Act
    $result = $this->service->getProfileForAdmin($user->id);

    // Assert
    expect($result)->toHaveKeys(['user', 'profile', 'ai_summary'])
        ->and($result['user'])->toHaveKeys(['id', 'name', 'email'])
        ->and($result['profile'])->toHaveKeys([
            'academic_background',
            'skills',
            'career_interests',
            'resume_path',
            'has_resume'
        ])
        ->and($result['profile']['has_resume'])->toBeFalse();
});

test('throws exception when profile not found', function () {
    // Arrange
    $nonExistentUserId = 99999;

    // Act & Assert
    expect(fn() => $this->service->getProfileForAdmin($nonExistentUserId))
        ->toThrow(Exception::class, 'Profile not found');
});

test('handles empty skills array correctly', function () {
    // Arrange
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'skills' => null, // No skills
    ]);

    // Mock AI summary generation
    $this->summaryService->shouldReceive('generateSummary')
        ->once()
        ->andReturn(null); // Simulate AI failure

    // Act
    $result = $this->service->getProfileForAdmin($user->id);

    // Assert
    expect($result['profile']['skills'])->toBe([])
        ->and($result)->not->toHaveKey('ai_summary'); // No AI summary when it fails
});

test('cache key is unique per user', function () {
    // Arrange
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    Profile::factory()->create([
        'user_id' => $user1->id,
        'skills' => ['PHP'],
    ]);
    
    Profile::factory()->create([
        'user_id' => $user2->id,
        'skills' => ['Python'],
    ]);

    // Mock AI summary generation for both users
    $this->summaryService->shouldReceive('generateSummary')
        ->twice()
        ->andReturn([
            'strengths' => ['Good skills'],
            'weaknesses' => [],
            'overall_assessment' => 'Good'
        ]);

    // Act
    $result1 = $this->service->getProfileForAdmin($user1->id);
    $result2 = $this->service->getProfileForAdmin($user2->id);

    // Assert - Each user should have their own cached data
    expect($result1['profile']['skills'])->toBe(['PHP'])
        ->and($result2['profile']['skills'])->toBe(['Python']);
});

test('clears cache and fetches fresh data after cache expiry', function () {
    // Arrange
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'skills' => ['PHP'],
    ]);

    // Mock AI summary generation (will be called twice)
    $this->summaryService->shouldReceive('generateSummary')
        ->twice()
        ->andReturn([
            'strengths' => ['Good'],
            'weaknesses' => [],
            'overall_assessment' => 'Good'
        ]);

    // Act - First call caches data
    $firstResult = $this->service->getProfileForAdmin($user->id);
    
    // Clear cache manually (simulating expiry)
    Cache::forget("admin_profile_{$user->id}");
    
    // Update profile
    $profile->update(['skills' => ['Python']]);
    
    // Second call should fetch fresh data
    $secondResult = $this->service->getProfileForAdmin($user->id);

    // Assert
    expect($firstResult['profile']['skills'])->toBe(['PHP'])
        ->and($secondResult['profile']['skills'])->toBe(['Python']);
});

test('logs error and throws exception on database failure', function () {
    // Arrange - Clear cache to force database query
    Cache::flush();
    
    // Use a non-existent user ID to simulate a scenario where profile lookup fails
    $nonExistentUserId = 99999;
    
    // Mock Log facade to verify error logging
    Log::shouldReceive('info')
        ->withArgs(function ($message, $context) use ($nonExistentUserId) {
            return $message === 'Fetching profile for admin view' 
                && $context['user_id'] === $nonExistentUserId;
        });
    
    Log::shouldReceive('info')
        ->withArgs(function ($message, $context) use ($nonExistentUserId) {
            return $message === 'Profile cache miss, fetching from database' 
                && $context['user_id'] === $nonExistentUserId;
        });
    
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message, $context) use ($nonExistentUserId) {
            return $message === 'Profile not found' 
                && $context['user_id'] === $nonExistentUserId;
        });
    
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) use ($nonExistentUserId) {
            return $message === 'Profile fetch failed' 
                && $context['user_id'] === $nonExistentUserId
                && isset($context['error'])
                && isset($context['trace']);
        });

    // Act & Assert - Should throw exception and log error
    expect(fn() => $this->service->getProfileForAdmin($nonExistentUserId))
        ->toThrow(\Exception::class, 'Profile not found');
});

test('handles null AI summary gracefully', function () {
    // Arrange
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'skills' => ['PHP', 'Laravel'],
    ]);

    // Mock AI summary generation returning null (failure case)
    $this->summaryService->shouldReceive('generateSummary')
        ->once()
        ->andReturn(null);

    // Act
    $result = $this->service->getProfileForAdmin($user->id);

    // Assert - Profile data should be present but no ai_summary key
    expect($result)->toHaveKeys(['user', 'profile'])
        ->and($result)->not->toHaveKey('ai_summary')
        ->and($result['profile']['skills'])->toBe(['PHP', 'Laravel']);
});

test('includes AI summary when generation succeeds', function () {
    // Arrange
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
    ]);

    $mockSummary = [
        'strengths' => ['Strong technical background', 'Good communication'],
        'weaknesses' => ['Limited leadership experience'],
        'overall_assessment' => 'Solid candidate with growth potential'
    ];

    // Mock AI summary generation returning valid data
    $this->summaryService->shouldReceive('generateSummary')
        ->once()
        ->andReturn($mockSummary);

    // Act
    $result = $this->service->getProfileForAdmin($user->id);

    // Assert - AI summary should be included
    expect($result)->toHaveKey('ai_summary')
        ->and($result['ai_summary'])->toBe($mockSummary)
        ->and($result['ai_summary']['strengths'])->toHaveCount(2)
        ->and($result['ai_summary']['weaknesses'])->toHaveCount(1);
});
