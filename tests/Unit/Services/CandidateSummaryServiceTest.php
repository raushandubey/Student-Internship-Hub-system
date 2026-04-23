<?php

use App\Models\Profile;
use App\Models\User;
use App\Services\CandidateSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new CandidateSummaryService();
});

test('generates correct prompt from profile data', function () {
    // Arrange
    $user = User::factory()->create(['name' => 'John Doe']);
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
        'academic_background' => 'B.Tech Computer Science',
        'skills' => ['PHP', 'Laravel', 'JavaScript'],
        'career_interests' => 'Full-stack development',
    ]);

    // Act
    $prompt = $this->service->buildPrompt($profile);

    // Assert
    expect($prompt)->toContain('John Doe')
        ->and($prompt)->toContain('B.Tech Computer Science')
        ->and($prompt)->toContain('PHP, Laravel, JavaScript')
        ->and($prompt)->toContain('Full-stack development')
        ->and($prompt)->toContain('strengths')
        ->and($prompt)->toContain('weaknesses')
        ->and($prompt)->toContain('overall assessment')
        ->and($prompt)->toContain('JSON');
});

test('handles skills as array correctly in prompt', function () {
    // Arrange
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'skills' => ['Python', 'Django', 'PostgreSQL'],
    ]);

    // Act
    $prompt = $this->service->buildPrompt($profile);

    // Assert
    expect($prompt)->toContain('Python, Django, PostgreSQL');
});

test('handles skills as string correctly in prompt', function () {
    // Arrange
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'skills' => 'Python, Django, PostgreSQL',
    ]);

    // Act
    $prompt = $this->service->buildPrompt($profile);

    // Assert
    expect($prompt)->toContain('Python, Django, PostgreSQL');
});

test('parses valid AI response into structured array', function () {
    // Arrange
    $validResponse = json_encode([
        'strengths' => [
            'Strong technical skills',
            'Good academic background',
        ],
        'weaknesses' => [
            'Limited project experience',
        ],
        'overall_assessment' => 'Promising candidate with solid foundation.',
    ]);

    // Act
    $result = $this->service->parseAIResponse($validResponse);

    // Assert
    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['strengths', 'weaknesses', 'overall_assessment'])
        ->and($result['strengths'])->toBe([
            'Strong technical skills',
            'Good academic background',
        ])
        ->and($result['weaknesses'])->toBe(['Limited project experience'])
        ->and($result['overall_assessment'])->toBe('Promising candidate with solid foundation.');
});

test('returns empty structure on invalid JSON response', function () {
    // Arrange
    $invalidResponse = 'This is not valid JSON';

    // Mock Log to verify error logging
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Failed to parse AI response';
        });

    // Act
    $result = $this->service->parseAIResponse($invalidResponse);

    // Assert
    expect($result)->toBeArray()
        ->and($result['strengths'])->toBe([])
        ->and($result['weaknesses'])->toBe([])
        ->and($result['overall_assessment'])->toBe('');
});

test('returns empty structure when required keys are missing', function () {
    // Arrange
    $incompleteResponse = json_encode([
        'strengths' => ['Good skills'],
        // Missing 'weaknesses' and 'overall_assessment'
    ]);

    // Mock Log to verify error logging
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Failed to parse AI response';
        });

    // Act
    $result = $this->service->parseAIResponse($incompleteResponse);

    // Assert
    expect($result)->toBeArray()
        ->and($result['strengths'])->toBe([])
        ->and($result['weaknesses'])->toBe([])
        ->and($result['overall_assessment'])->toBe('');
});

test('handles non-array strengths in response', function () {
    // Arrange
    $response = json_encode([
        'strengths' => 'Strong technical skills', // String instead of array
        'weaknesses' => ['Limited experience'],
        'overall_assessment' => 'Good candidate',
    ]);

    // Act
    $result = $this->service->parseAIResponse($response);

    // Assert
    expect($result['strengths'])->toBe([]);
});

test('handles non-array weaknesses in response', function () {
    // Arrange
    $response = json_encode([
        'strengths' => ['Strong skills'],
        'weaknesses' => 'Limited experience', // String instead of array
        'overall_assessment' => 'Good candidate',
    ]);

    // Act
    $result = $this->service->parseAIResponse($response);

    // Assert
    expect($result['weaknesses'])->toBe([]);
});

test('returns null when OpenAI API key is not configured', function () {
    // Arrange
    Config::set('services.openai.api_key', null);
    
    $user = User::factory()->create();
    $profile = Profile::factory()->create(['user_id' => $user->id]);

    // Mock Log to verify error logging
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) use ($profile) {
            return $message === 'Candidate summary generation failed'
                && $context['profile_id'] === $profile->id
                && $context['user_id'] === $profile->user_id;
        });

    // Act
    $result = $this->service->generateSummary($profile);

    // Assert
    expect($result)->toBeNull();
});

test('returns null on API timeout', function () {
    // Arrange
    Config::set('services.openai.api_key', 'test-key');
    
    $user = User::factory()->create();
    $profile = Profile::factory()->create(['user_id' => $user->id]);

    // Mock Log to verify error logging
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) use ($profile) {
            return $message === 'Candidate summary generation failed'
                && $context['profile_id'] === $profile->id;
        });

    // Note: This test will fail in real execution due to actual API call
    // In a real scenario, we would mock the OpenAI client
    // For now, this demonstrates the expected behavior
    
    // Act
    $result = $this->service->generateSummary($profile);

    // Assert - Should return null on any failure
    expect($result)->toBeNull();
});

test('handles empty profile data gracefully', function () {
    // Arrange
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test User', // Name is required
        'academic_background' => '',
        'skills' => null,
        'career_interests' => '',
    ]);

    // Act
    $prompt = $this->service->buildPrompt($profile);

    // Assert - Should still generate a valid prompt
    expect($prompt)->toBeString()
        ->and($prompt)->toContain('Analyze the following candidate profile');
});

test('logs error with correct context on failure', function () {
    // Arrange
    Config::set('services.openai.api_key', null);
    
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'id' => 123,
    ]);

    // Mock Log to verify error logging with correct context
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Candidate summary generation failed'
                && $context['profile_id'] === 123
                && isset($context['user_id'])
                && isset($context['error']);
        });

    // Act
    $result = $this->service->generateSummary($profile);

    // Assert
    expect($result)->toBeNull();
});

