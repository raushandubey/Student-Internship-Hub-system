<?php

namespace Tests\Unit;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Preservation Property Tests for S3 Resume Display Fix
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**
 * 
 * IMPORTANT: Follow observation-first methodology
 * - These tests observe and capture behavior on UNFIXED code for local development
 * - Tests should PASS on unfixed code (confirms baseline behavior to preserve)
 * - Tests verify that local development with FILESYSTEM_DISK=local continues to work unchanged
 * 
 * Property 2: Preservation - Local Development Behavior
 * 
 * For any local development environment where FILESYSTEM_DISK=local is configured,
 * the fixed code SHALL produce exactly the same behavior as the original code.
 */
class S3ResumeDisplayPreservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure for local development environment
        Config::set('filesystems.default', 'local');
        
        // Use fake storage for testing
        Storage::fake('public');
    }

    /**
     * Property: Resume uploads store files in storage/app/public/resumes/
     * 
     * Validates: Requirement 3.1
     * 
     * For all local development environments where FILESYSTEM_DISK=local,
     * resume uploads SHALL store files to storage/app/public/ using Storage::disk('public')
     */
    public function test_local_resume_uploads_store_in_public_storage()
    {
        echo "\n=== Preservation Test: Local Resume Upload Storage ===\n";
        
        // Create test user and profile
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => null,
        ]);
        
        // Simulate resume upload
        $resumeFile = UploadedFile::fake()->create('test-resume.pdf', 1024, 'application/pdf');
        $filename = time() . '_test-resume.pdf';
        $path = $resumeFile->storeAs('resumes', $filename, 'public');
        
        echo "  Uploaded file path: {$path}\n";
        echo "  File exists in public disk: " . (Storage::disk('public')->exists($path) ? 'YES' : 'NO') . "\n";
        
        // Verify file is stored in public disk
        $this->assertTrue(
            Storage::disk('public')->exists($path),
            "Resume file should be stored in public disk at path: {$path}"
        );
        
        // Verify path format is relative (resumes/filename.pdf)
        $this->assertTrue(
            str_starts_with($path, 'resumes/'),
            "Resume path should start with 'resumes/'. Got: {$path}"
        );
        
        echo "  ✓ Local uploads store in public disk\n";
        echo "======================================================\n\n";
    }

    /**
     * Property: getResumeUrl() returns http://localhost:8000/storage/resumes/filename.pdf
     * 
     * Validates: Requirement 3.1
     * 
     * For all local development environments, getResumeUrl() SHALL generate URLs
     * via Storage::disk('public')->url()
     */
    public function test_local_resume_url_generation_uses_public_storage_url()
    {
        echo "\n=== Preservation Test: Local Resume URL Generation ===\n";
        
        // Create test user and profile with resume
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => 'resumes/test-resume.pdf',
        ]);
        
        // Simulate file existing in public storage
        Storage::disk('public')->put('resumes/test-resume.pdf', 'fake pdf content');
        
        // Generate resume URL
        $resumeUrl = $profile->getResumeUrl();
        
        echo "  Generated URL: {$resumeUrl}\n";
        echo "  URL contains '/storage/': " . (str_contains($resumeUrl ?? '', '/storage/') ? 'YES' : 'NO') . "\n";
        echo "  URL contains filename: " . (str_contains($resumeUrl ?? '', 'test-resume.pdf') ? 'YES' : 'NO') . "\n";
        
        // Verify URL is generated
        $this->assertNotNull($resumeUrl, "Resume URL should not be null for local storage");
        
        // Verify URL uses public storage path
        $this->assertTrue(
            str_contains($resumeUrl, '/storage/'),
            "Local resume URL should contain '/storage/' path. Got: {$resumeUrl}"
        );
        
        // Verify URL contains the filename
        $this->assertTrue(
            str_contains($resumeUrl, 'test-resume.pdf'),
            "Resume URL should contain the filename. Got: {$resumeUrl}"
        );
        
        echo "  ✓ Local URLs use public storage path\n";
        echo "======================================================\n\n";
    }

    /**
     * Property-Based Test: Various filename formats work in local development
     * 
     * Validates: Requirements 3.1, 3.5
     * 
     * Tests that local development handles various filename formats correctly
     */
    public function test_local_development_handles_various_filename_formats()
    {
        echo "\n=== Property-Based Test: Various Filename Formats (Local) ===\n";
        
        // Generate test cases with different filename patterns
        $testFilenames = [
            'resumes/simple.pdf',
            'resumes/with-dashes.pdf',
            'resumes/with_underscores.pdf',
            'resumes/with.multiple.dots.pdf',
            'resumes/UPPERCASE.PDF',
            'resumes/MixedCase.Pdf',
            'resumes/with-numbers-123.pdf',
            'resumes/1234567890_resume.pdf',
        ];
        
        $results = [];
        
        foreach ($testFilenames as $filename) {
            // Create profile with this filename
            $user = User::factory()->create();
            $profile = Profile::factory()->create([
                'user_id' => $user->id,
                'resume_path' => $filename,
            ]);
            
            // Simulate file existing in public storage
            Storage::disk('public')->put($filename, 'fake pdf content');
            
            // Generate URL
            $url = $profile->getResumeUrl();
            
            $results[] = [
                'filename' => $filename,
                'url_generated' => $url !== null,
                'url_contains_storage' => str_contains($url ?? '', '/storage/'),
                'url' => $url,
            ];
        }
        
        // Display results
        echo "  Tested " . count($testFilenames) . " different filename patterns:\n";
        foreach ($results as $result) {
            echo "    {$result['filename']}: " . 
                ($result['url_generated'] ? 'URL Generated' : 'FAILED') . 
                ($result['url_contains_storage'] ? ' (Public Storage)' : '') . "\n";
        }
        
        // Assert all URLs were generated successfully
        $allGenerated = array_reduce($results, fn($carry, $r) => $carry && $r['url_generated'], true);
        $allUseStorage = array_reduce($results, fn($carry, $r) => $carry && $r['url_contains_storage'], true);
        
        $this->assertTrue(
            $allGenerated,
            "All resume URLs should be generated in local development. " .
            "Failed filenames: " . json_encode(array_filter($results, fn($r) => !$r['url_generated']))
        );
        
        $this->assertTrue(
            $allUseStorage,
            "All URLs should use public storage path. " .
            "Non-storage URLs: " . json_encode(array_filter($results, fn($r) => !$r['url_contains_storage']))
        );
        
        echo "  ✓ All filename formats work correctly\n";
        echo "=============================================================\n\n";
    }

    /**
     * Property: Resume paths are stored as relative paths in database
     * 
     * Validates: Requirement 3.5
     * 
     * Resume paths SHALL be stored as relative paths (e.g., resumes/filename.pdf)
     * not absolute URLs
     */
    public function test_resume_paths_stored_as_relative_paths()
    {
        echo "\n=== Preservation Test: Relative Path Storage ===\n";
        
        // Create profile with resume
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => 'resumes/test-resume.pdf',
        ]);
        
        echo "  Stored resume_path: {$profile->resume_path}\n";
        echo "  Is relative path: " . (!str_starts_with($profile->resume_path, 'http') ? 'YES' : 'NO') . "\n";
        echo "  Starts with 'resumes/': " . (str_starts_with($profile->resume_path, 'resumes/') ? 'YES' : 'NO') . "\n";
        
        // Verify path is relative (not absolute URL)
        $this->assertFalse(
            str_starts_with($profile->resume_path, 'http'),
            "Resume path should be relative, not absolute URL. Got: {$profile->resume_path}"
        );
        
        // Verify path format
        $this->assertTrue(
            str_starts_with($profile->resume_path, 'resumes/'),
            "Resume path should start with 'resumes/'. Got: {$profile->resume_path}"
        );
        
        echo "  ✓ Paths stored as relative paths\n";
        echo "================================================\n\n";
    }

    /**
     * Property: getResumeUrl() returns null for non-existent files
     * 
     * Validates: Requirement 3.7
     * 
     * When resume URLs are generated for non-existent files, the system SHALL
     * return null and log warnings instead of throwing exceptions
     */
    public function test_resume_url_returns_null_for_nonexistent_files()
    {
        echo "\n=== Preservation Test: Non-existent File Handling ===\n";
        
        // Create profile with resume path but no actual file
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => 'resumes/nonexistent-file.pdf',
        ]);
        
        // Do NOT create the file in storage
        
        // Attempt to get resume URL
        $resumeUrl = $profile->getResumeUrl();
        
        echo "  Resume path: {$profile->resume_path}\n";
        echo "  File exists: NO\n";
        echo "  Generated URL: " . ($resumeUrl ?? 'NULL') . "\n";
        
        // Verify URL is null for non-existent file
        $this->assertNull(
            $resumeUrl,
            "Resume URL should be null for non-existent files. Got: {$resumeUrl}"
        );
        
        echo "  ✓ Returns null for non-existent files\n";
        echo "=====================================================\n\n";
    }

    /**
     * Property: getResumeUrl() returns null for null resume_path
     * 
     * Validates: Requirement 3.7
     * 
     * Edge case: When resume_path is null, getResumeUrl() should return null
     */
    public function test_resume_url_returns_null_for_null_resume_path()
    {
        echo "\n=== Preservation Test: Null Resume Path Handling ===\n";
        
        // Create profile without resume
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => null,
        ]);
        
        // Attempt to get resume URL
        $resumeUrl = $profile->getResumeUrl();
        
        echo "  Resume path: NULL\n";
        echo "  Generated URL: " . ($resumeUrl ?? 'NULL') . "\n";
        
        // Verify URL is null
        $this->assertNull(
            $resumeUrl,
            "Resume URL should be null when resume_path is null. Got: {$resumeUrl}"
        );
        
        echo "  ✓ Returns null for null resume_path\n";
        echo "====================================================\n\n";
    }

    /**
     * Property: hasResumeFile() correctly checks file existence
     * 
     * Validates: Requirement 3.6
     * 
     * hasResumeFile() SHALL return boolean indicating actual file existence
     * on the configured storage disk
     */
    public function test_has_resume_file_checks_existence_correctly()
    {
        echo "\n=== Preservation Test: File Existence Check ===\n";
        
        // Test case 1: File exists
        $user1 = User::factory()->create();
        $profile1 = Profile::factory()->create([
            'user_id' => $user1->id,
            'resume_path' => 'resumes/existing-file.pdf',
        ]);
        Storage::disk('public')->put('resumes/existing-file.pdf', 'content');
        
        echo "  Test 1 - File exists:\n";
        echo "    hasResumeFile(): " . ($profile1->hasResumeFile() ? 'TRUE' : 'FALSE') . "\n";
        
        $this->assertTrue(
            $profile1->hasResumeFile(),
            "hasResumeFile() should return true when file exists"
        );
        
        // Test case 2: File does not exist
        $user2 = User::factory()->create();
        $profile2 = Profile::factory()->create([
            'user_id' => $user2->id,
            'resume_path' => 'resumes/nonexistent-file.pdf',
        ]);
        
        echo "  Test 2 - File does not exist:\n";
        echo "    hasResumeFile(): " . ($profile2->hasResumeFile() ? 'TRUE' : 'FALSE') . "\n";
        
        $this->assertFalse(
            $profile2->hasResumeFile(),
            "hasResumeFile() should return false when file does not exist"
        );
        
        // Test case 3: Null resume_path
        $user3 = User::factory()->create();
        $profile3 = Profile::factory()->create([
            'user_id' => $user3->id,
            'resume_path' => null,
        ]);
        
        echo "  Test 3 - Null resume_path:\n";
        echo "    hasResumeFile(): " . ($profile3->hasResumeFile() ? 'TRUE' : 'FALSE') . "\n";
        
        $this->assertFalse(
            $profile3->hasResumeFile(),
            "hasResumeFile() should return false when resume_path is null"
        );
        
        echo "  ✓ File existence checks work correctly\n";
        echo "===============================================\n\n";
    }

    /**
     * Property-Based Test: Different file sizes within 2MB limit
     * 
     * Validates: Requirement 3.1
     * 
     * Tests that local development handles various file sizes correctly
     */
    public function test_local_development_handles_various_file_sizes()
    {
        echo "\n=== Property-Based Test: Various File Sizes (Local) ===\n";
        
        // Test different file sizes (in KB)
        $fileSizes = [1, 10, 100, 500, 1024, 2048]; // 1KB to 2MB
        
        $results = [];
        
        foreach ($fileSizes as $sizeKB) {
            // Create fake file
            $resumeFile = UploadedFile::fake()->create("resume-{$sizeKB}kb.pdf", $sizeKB, 'application/pdf');
            $filename = time() . "_resume-{$sizeKB}kb.pdf";
            $path = $resumeFile->storeAs('resumes', $filename, 'public');
            
            // Create profile
            $user = User::factory()->create();
            $profile = Profile::factory()->create([
                'user_id' => $user->id,
                'resume_path' => $path,
            ]);
            
            // Check file exists and URL generation
            $fileExists = Storage::disk('public')->exists($path);
            $url = $profile->getResumeUrl();
            
            $results[] = [
                'size_kb' => $sizeKB,
                'file_exists' => $fileExists,
                'url_generated' => $url !== null,
            ];
        }
        
        // Display results
        echo "  Tested " . count($fileSizes) . " different file sizes:\n";
        foreach ($results as $result) {
            echo "    {$result['size_kb']}KB: " . 
                ($result['file_exists'] ? 'Stored' : 'FAILED') . ', ' .
                ($result['url_generated'] ? 'URL Generated' : 'NO URL') . "\n";
        }
        
        // Assert all files were stored and URLs generated
        $allStored = array_reduce($results, fn($carry, $r) => $carry && $r['file_exists'], true);
        $allUrls = array_reduce($results, fn($carry, $r) => $carry && $r['url_generated'], true);
        
        $this->assertTrue(
            $allStored,
            "All file sizes should be stored successfully in local development"
        );
        
        $this->assertTrue(
            $allUrls,
            "URLs should be generated for all file sizes"
        );
        
        echo "  ✓ All file sizes handled correctly\n";
        echo "========================================================\n\n";
    }

    /**
     * Property: Error handling logs context and returns gracefully
     * 
     * Validates: Requirement 3.8
     * 
     * When file operations fail, the system SHALL log errors with context
     * and return user-friendly error messages (not throw exceptions)
     */
    public function test_error_handling_returns_gracefully()
    {
        echo "\n=== Preservation Test: Error Handling ===\n";
        
        // Test with invalid path format
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => '../../etc/passwd', // Invalid path
        ]);
        
        // Attempt to get resume URL - should not throw exception
        $resumeUrl = null;
        $exceptionThrown = false;
        
        try {
            $resumeUrl = $profile->getResumeUrl();
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }
        
        echo "  Invalid path: {$profile->resume_path}\n";
        echo "  Exception thrown: " . ($exceptionThrown ? 'YES' : 'NO') . "\n";
        echo "  Resume URL: " . ($resumeUrl ?? 'NULL') . "\n";
        
        // Verify no exception is thrown
        $this->assertFalse(
            $exceptionThrown,
            "getResumeUrl() should not throw exceptions for invalid paths"
        );
        
        // Verify null is returned
        $this->assertNull(
            $resumeUrl,
            "getResumeUrl() should return null for invalid paths"
        );
        
        echo "  ✓ Error handling returns gracefully\n";
        echo "=========================================\n\n";
    }
}
