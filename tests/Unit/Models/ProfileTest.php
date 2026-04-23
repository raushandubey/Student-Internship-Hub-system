<?php

namespace Tests\Unit\Models;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user and profile for each test
        $this->user = User::factory()->create();
        $this->profile = Profile::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_get_resume_url_returns_s3_url_when_s3_disk_configured()
    {
        // Arrange
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        
        $resumePath = 'resumes/test_resume.pdf';
        Storage::disk('s3')->put($resumePath, 'test content');
        
        $this->profile->update(['resume_path' => $resumePath]);
        
        // Act
        $url = $this->profile->getResumeUrl();
        
        // Assert
        $this->assertNotNull($url);
        $this->assertStringContainsString($resumePath, $url);
    }

    /** @test */
    public function test_get_resume_url_returns_public_url_when_public_disk_configured()
    {
        // Arrange
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        
        $resumePath = 'resumes/test_resume.pdf';
        Storage::disk('public')->put($resumePath, 'test content');
        
        $this->profile->update(['resume_path' => $resumePath]);
        
        // Act
        $url = $this->profile->getResumeUrl();
        
        // Assert
        $this->assertNotNull($url);
        $this->assertStringContainsString('storage', $url);
    }

    /** @test */
    public function test_get_resume_url_returns_fallback_route_when_file_not_found_on_s3()
    {
        // Arrange
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        Storage::fake('public');
        
        $this->profile->update(['resume_path' => 'resumes/nonexistent.pdf']);
        
        // Act
        $url = $this->profile->getResumeUrl();
        
        // Assert - Should fall back to route-based serving
        $this->assertNotNull($url);
        $this->assertStringContainsString('resume/serve', $url);
        $this->assertStringContainsString('nonexistent.pdf', $url);
    }

    /** @test */
    public function test_get_resume_url_returns_null_when_resume_path_is_null()
    {
        // Arrange
        $this->profile->update(['resume_path' => null]);
        
        // Act
        $url = $this->profile->getResumeUrl();
        
        // Assert
        $this->assertNull($url);
    }

    /** @test */
    public function test_has_resume_file_returns_true_when_file_exists_on_s3()
    {
        // Arrange
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        
        $resumePath = 'resumes/test_resume.pdf';
        Storage::disk('s3')->put($resumePath, 'test content');
        
        $this->profile->update(['resume_path' => $resumePath]);
        
        // Act
        $result = $this->profile->hasResumeFile();
        
        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function test_has_resume_file_returns_true_when_file_exists_on_public_disk()
    {
        // Arrange
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        
        $resumePath = 'resumes/test_resume.pdf';
        Storage::disk('public')->put($resumePath, 'test content');
        
        $this->profile->update(['resume_path' => $resumePath]);
        
        // Act
        $result = $this->profile->hasResumeFile();
        
        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function test_has_resume_file_returns_false_when_file_missing()
    {
        // Arrange
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        
        $this->profile->update(['resume_path' => 'resumes/nonexistent.pdf']);
        
        // Act
        $result = $this->profile->hasResumeFile();
        
        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_has_resume_file_returns_false_when_resume_path_is_null()
    {
        // Arrange
        $this->profile->update(['resume_path' => null]);
        
        // Act
        $result = $this->profile->hasResumeFile();
        
        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_get_resume_url_handles_exception_gracefully()
    {
        // Arrange
        $this->profile->update(['resume_path' => 'resumes/test.pdf']);
        
        // Mock Storage to throw exception
        Storage::shouldReceive('disk')
            ->andThrow(new \Exception('Storage error'));
        
        // Act
        $url = $this->profile->getResumeUrl();
        
        // Assert
        $this->assertNull($url);
    }

    /** @test */
    public function test_has_resume_file_handles_exception_gracefully()
    {
        // Arrange
        $this->profile->update(['resume_path' => 'resumes/test.pdf']);
        
        // Mock Storage to throw exception
        Storage::shouldReceive('disk')
            ->andThrow(new \Exception('Storage error'));
        
        // Act
        $result = $this->profile->hasResumeFile();
        
        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_get_resume_url_with_leading_slash_in_path()
    {
        // Arrange
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        
        // Path with leading slash
        $resumePath = '/resumes/test_resume.pdf';
        Storage::disk('public')->put('resumes/test_resume.pdf', 'test content');
        
        $this->profile->update(['resume_path' => $resumePath]);
        
        // Act
        $url = $this->profile->getResumeUrl();
        
        // Assert - Should normalize path and find file
        $this->assertNotNull($url);
        $this->assertStringContainsString('storage', $url);
    }

    /** @test */
    public function test_has_resume_file_with_leading_slash_in_path()
    {
        // Arrange
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        
        // Path with leading slash
        $resumePath = '/resumes/test_resume.pdf';
        Storage::disk('public')->put('resumes/test_resume.pdf', 'test content');
        
        $this->profile->update(['resume_path' => $resumePath]);
        
        // Act
        $result = $this->profile->hasResumeFile();
        
        // Assert - Should normalize path and find file
        $this->assertTrue($result);
    }
}
