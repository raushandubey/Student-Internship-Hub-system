<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Comprehensive integration tests for S3 Storage Completion feature
 * Task 8: Create comprehensive integration tests
 */
class S3StorageIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake storage for all tests
        Storage::fake('s3');
        Storage::fake('public');
    }

    // ========================================
    // Task 8.1: End-to-end file upload tests
    // ========================================

    /**
     * Test student can upload resume to S3
     * Requirements: 3.1, 3.2
     */
    public function test_student_can_upload_resume_to_s3()
    {
        // Configure S3 as default disk
        config(['filesystems.default' => 's3']);
        
        // Create student user with profile
        $user = User::factory()->create(['role' => 'student']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Student'
        ]);
        
        $this->actingAs($user);
        
        // Create a test PDF file
        $file = UploadedFile::fake()->create('my_resume.pdf', 500, 'application/pdf');
        
        // Submit profile update with resume
        $response = $this->put(route('profile.update'), [
            'name' => 'Test Student',
            'academic_background' => 'Computer Science',
            'skills' => 'PHP,Laravel,Testing',
            'resume' => $file
        ]);
        
        // Assert successful redirect
        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success', 'Profile updated successfully!');
        
        // Verify file was stored on S3
        $profile->refresh();
        $this->assertNotNull($profile->resume_path);
        $this->assertStringContainsString('resumes/', $profile->resume_path);
        $this->assertStringContainsString('.pdf', $profile->resume_path);
        
        // Verify file exists on S3 disk
        Storage::disk('s3')->assertExists($profile->resume_path);
    }

    /**
     * Test old resume is deleted when uploading new one
     * Requirements: 3.3
     */
    public function test_old_resume_is_deleted_when_uploading_new_one()
    {
        config(['filesystems.default' => 's3']);
        
        $user = User::factory()->create(['role' => 'student']);
        
        // Create profile with existing resume
        $oldPath = 'resumes/1234567890_old_resume.pdf';
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => $oldPath
        ]);
        
        // Store old file on S3
        Storage::disk('s3')->put($oldPath, 'old resume content');
        $this->assertTrue(Storage::disk('s3')->exists($oldPath));
        
        $this->actingAs($user);
        
        // Upload new resume
        $newFile = UploadedFile::fake()->create('new_resume.pdf', 500, 'application/pdf');
        
        $response = $this->put(route('profile.update'), [
            'name' => 'Test Student',
            'resume' => $newFile
        ]);
        
        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');
        
        // Verify old file was deleted from S3
        Storage::disk('s3')->assertMissing($oldPath);
        
        // Verify new file was stored
        $profile->refresh();
        $this->assertNotEquals($oldPath, $profile->resume_path);
        Storage::disk('s3')->assertExists($profile->resume_path);
        
        // Verify only one resume file exists
        $allFiles = Storage::disk('s3')->allFiles('resumes');
        $this->assertCount(1, $allFiles);
    }

    /**
     * Test upload with invalid file type fails gracefully
     * Requirements: 3.4, 9.1
     */
    public function test_upload_with_invalid_file_type_fails_gracefully()
    {
        config(['filesystems.default' => 's3']);
        
        $user = User::factory()->create(['role' => 'student']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => null // Start with no resume
        ]);
        
        $this->actingAs($user);
        
        // Try to upload a non-PDF file (text file)
        $invalidFile = UploadedFile::fake()->create('resume.txt', 100, 'text/plain');
        
        $response = $this->put(route('profile.update'), [
            'name' => 'Test Student',
            'resume' => $invalidFile
        ]);
        
        // Should have validation errors
        $response->assertSessionHasErrors('resume');
        
        // Verify no file was stored on S3
        $allFiles = Storage::disk('s3')->allFiles('resumes');
        $this->assertCount(0, $allFiles);
        
        // Verify profile resume_path was not updated
        $profile->refresh();
        $this->assertNull($profile->resume_path);
    }

    /**
     * Test upload with oversized file fails gracefully
     * Requirements: 3.4, 9.1
     */
    public function test_upload_with_oversized_file_fails_gracefully()
    {
        config(['filesystems.default' => 's3']);
        
        $user = User::factory()->create(['role' => 'student']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => null // Start with no resume
        ]);
        
        $this->actingAs($user);
        
        // Try to upload a file larger than 2MB (2048 KB)
        $oversizedFile = UploadedFile::fake()->create('huge_resume.pdf', 3000, 'application/pdf');
        
        $response = $this->put(route('profile.update'), [
            'name' => 'Test Student',
            'resume' => $oversizedFile
        ]);
        
        // Should have validation errors
        $response->assertSessionHasErrors('resume');
        
        // Verify no file was stored on S3
        $allFiles = Storage::disk('s3')->allFiles('resumes');
        $this->assertCount(0, $allFiles);
        
        // Verify profile resume_path was not updated
        $profile->refresh();
        $this->assertNull($profile->resume_path);
    }

    // ========================================
    // Task 8.2: File retrieval tests
    // ========================================

    /**
     * Test resume URL generation for S3 files
     * Requirements: 4.1, 4.2
     */
    public function test_resume_url_generation_for_s3_files()
    {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        
        $user = User::factory()->create(['role' => 'student']);
        
        // Create profile with resume on S3
        $resumePath = 'resumes/1234567890_test_resume.pdf';
        Storage::disk('s3')->put($resumePath, 'test resume content');
        
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => $resumePath
        ]);
        
        // Get resume URL
        $url = $profile->getResumeUrl();
        
        // Verify URL is generated
        $this->assertNotNull($url);
        $this->assertIsString($url);
        
        // Verify URL contains the resume path
        $this->assertStringContainsString($resumePath, $url);
        
        // Verify hasResumeFile returns true
        $this->assertTrue($profile->hasResumeFile());
    }

    /**
     * Test resume URL generation for local files
     * Requirements: 4.1, 4.3
     */
    public function test_resume_url_generation_for_local_files()
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        
        $user = User::factory()->create(['role' => 'student']);
        
        // Create profile with resume on public disk
        $resumePath = 'resumes/1234567890_test_resume.pdf';
        Storage::disk('public')->put($resumePath, 'test resume content');
        
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => $resumePath
        ]);
        
        // Get resume URL
        $url = $profile->getResumeUrl();
        
        // Verify URL is generated
        $this->assertNotNull($url);
        $this->assertIsString($url);
        
        // Verify URL contains 'storage' (public disk URL pattern)
        $this->assertStringContainsString('storage', $url);
        
        // Verify hasResumeFile returns true
        $this->assertTrue($profile->hasResumeFile());
    }

    /**
     * Test resume URL returns null for missing files
     * Requirements: 4.3, 4.4
     */
    public function test_resume_url_returns_null_for_missing_files()
    {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        
        $user = User::factory()->create(['role' => 'student']);
        
        // Create profile with non-existent resume path
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => 'resumes/nonexistent_file.pdf'
        ]);
        
        // Get resume URL - should return null for missing files
        $url = $profile->getResumeUrl();
        
        // New architecture: returns null for missing files (no fallback route)
        $this->assertNull($url);
        
        // Verify hasResumeFile returns false
        $this->assertFalse($profile->hasResumeFile());
    }

    /**
     * Test resume URL returns null when resume_path is null
     * Requirements: 4.3
     */
    public function test_resume_url_returns_null_when_no_resume_path()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        // Create profile without resume
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => null
        ]);
        
        // Get resume URL
        $url = $profile->getResumeUrl();
        
        // Verify URL is null
        $this->assertNull($url);
        
        // Verify hasResumeFile returns false
        $this->assertFalse($profile->hasResumeFile());
    }

    /**
     * Test resume download works correctly
     * Requirements: 4.4
     */
    public function test_resume_download_works_correctly()
    {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        
        $user = User::factory()->create(['role' => 'student']);
        
        // Upload a resume
        $this->actingAs($user);
        
        $file = UploadedFile::fake()->create('test_resume.pdf', 500, 'application/pdf');
        
        $response = $this->put(route('profile.update'), [
            'name' => 'Test Student',
            'resume' => $file
        ]);
        
        $response->assertRedirect(route('profile.show'));
        
        // Get the profile and verify file
        $profile = $user->fresh()->profile;
        $this->assertNotNull($profile->resume_path);
        
        // Verify file can be retrieved from storage
        $this->assertTrue(Storage::disk('s3')->exists($profile->resume_path));
        
        // Verify URL can be generated
        $url = $profile->getResumeUrl();
        $this->assertNotNull($url);
    }

    // ========================================
    // Task 8.3: Backward compatibility tests
    // ========================================

    /**
     * Test storage works with FILESYSTEM_DISK=s3
     * Requirements: 7.1, 7.5
     */
    public function test_storage_works_with_s3_disk()
    {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);
        
        // Upload resume
        $file = UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf');
        
        $response = $this->put(route('profile.update'), [
            'name' => 'Test Student',
            'resume' => $file
        ]);
        
        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');
        
        // Verify file is on S3 disk
        $profile = $user->fresh()->profile;
        Storage::disk('s3')->assertExists($profile->resume_path);
        
        // Verify file is NOT on public disk
        Storage::disk('public')->assertMissing($profile->resume_path);
    }

    /**
     * Test storage works with FILESYSTEM_DISK=public
     * Requirements: 7.2, 7.5
     */
    public function test_storage_works_with_public_disk()
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);
        
        // Upload resume
        $file = UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf');
        
        $response = $this->put(route('profile.update'), [
            'name' => 'Test Student',
            'resume' => $file
        ]);
        
        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');
        
        // Verify file is on public disk
        $profile = $user->fresh()->profile;
        Storage::disk('public')->assertExists($profile->resume_path);
    }

    /**
     * Test URL generation handles both disk types
     * Requirements: 7.3
     */
    public function test_url_generation_handles_both_disk_types()
    {
        $user = User::factory()->create(['role' => 'student']);
        $resumePath = 'resumes/test_resume.pdf';
        
        // Test with S3 disk
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put($resumePath, 'content');
        
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => $resumePath
        ]);
        
        $s3Url = $profile->getResumeUrl();
        $this->assertNotNull($s3Url);
        $this->assertStringContainsString($resumePath, $s3Url);
        
        // Test with public disk - need to recreate storage fakes
        config(['filesystems.default' => 'public']);
        Storage::fake('s3'); // Clear S3
        Storage::fake('public');
        Storage::disk('public')->put($resumePath, 'content');
        
        // Force profile to re-evaluate
        $profile = Profile::find($profile->id);
        $publicUrl = $profile->getResumeUrl();
        $this->assertNotNull($publicUrl);
        $this->assertStringContainsString('storage', $publicUrl);
    }

    /**
     * Test file existence checks handle both disk types
     * Requirements: 7.4
     */
    public function test_file_existence_checks_handle_both_disk_types()
    {
        $user = User::factory()->create(['role' => 'student']);
        $resumePath = 'resumes/test_resume.pdf';
        
        // Test with S3 disk
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put($resumePath, 'content');
        
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => $resumePath
        ]);
        
        $this->assertTrue($profile->hasResumeFile());
        
        // Test with public disk
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        Storage::disk('public')->put($resumePath, 'content');
        
        $profile->refresh();
        $this->assertTrue($profile->hasResumeFile());
        
        // Test with missing file on S3
        config(['filesystems.default' => 's3']);
        Storage::fake('s3'); // Reset S3 storage
        
        $profile->refresh();
        $this->assertFalse($profile->hasResumeFile());
    }

    /**
     * Test complete upload and retrieval flow with S3
     * Requirements: 3.1, 3.2, 4.1, 7.1
     */
    public function test_complete_upload_and_retrieval_flow_with_s3()
    {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
        
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);
        
        // Step 1: Upload resume
        $file = UploadedFile::fake()->create('my_resume.pdf', 1000, 'application/pdf');
        
        $uploadResponse = $this->put(route('profile.update'), [
            'name' => 'Test Student',
            'academic_background' => 'Computer Science',
            'skills' => 'PHP,Laravel',
            'resume' => $file
        ]);
        
        $uploadResponse->assertRedirect(route('profile.show'));
        $uploadResponse->assertSessionHas('success');
        
        // Step 2: Verify file stored
        $profile = $user->fresh()->profile;
        $this->assertNotNull($profile->resume_path);
        Storage::disk('s3')->assertExists($profile->resume_path);
        
        // Step 3: Verify URL generation
        $url = $profile->getResumeUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString($profile->resume_path, $url);
        
        // Step 4: Verify file existence check
        $this->assertTrue($profile->hasResumeFile());
    }

    /**
     * Test complete upload and retrieval flow with public disk
     * Requirements: 3.1, 3.2, 4.2, 7.2
     */
    public function test_complete_upload_and_retrieval_flow_with_public_disk()
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);
        
        // Step 1: Upload resume
        $file = UploadedFile::fake()->create('my_resume.pdf', 1000, 'application/pdf');
        
        $uploadResponse = $this->put(route('profile.update'), [
            'name' => 'Test Student',
            'resume' => $file
        ]);
        
        $uploadResponse->assertRedirect(route('profile.show'));
        $uploadResponse->assertSessionHas('success');
        
        // Step 2: Verify file stored
        $profile = $user->fresh()->profile;
        $this->assertNotNull($profile->resume_path);
        Storage::disk('public')->assertExists($profile->resume_path);
        
        // Step 3: Verify URL generation
        $url = $profile->getResumeUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString('storage', $url);
        
        // Step 4: Verify file existence check
        $this->assertTrue($profile->hasResumeFile());
    }
}
