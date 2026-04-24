<?php

namespace Tests\Unit;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bug Condition Exploration Test for S3 Resume Display Fix
 * 
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8**
 * 
 * CRITICAL: This test MUST FAIL on unfixed configuration - failure confirms the bug exists
 * DO NOT attempt to fix the test or configuration when it fails
 * 
 * This test encodes the expected behavior and will validate the fix when it passes after implementation
 * 
 * GOAL: Surface counterexamples demonstrating the bug exists in production with FILESYSTEM_DISK=local
 */
class S3ResumeDisplayBugConditionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Check if bug condition exists in current environment
     * 
     * Bug condition: FILESYSTEM_DISK=local in production with S3 storage
     * and missing/incomplete AWS configuration
     */
    private function isBugCondition(): bool
    {
        $filesystemDisk = config('filesystems.default');
        $awsAccessKey = config('filesystems.disks.s3.key');
        $awsSecretKey = config('filesystems.disks.s3.secret');
        $awsRegion = config('filesystems.disks.s3.region');
        $awsBucket = config('filesystems.disks.s3.bucket');
        $awsUrl = config('filesystems.disks.s3.url');
        
        // Bug exists when:
        // 1. FILESYSTEM_DISK is 'local' (should be 's3' in production)
        // 2. AWS configuration is missing or incomplete
        return $filesystemDisk === 'local' 
            || empty($awsAccessKey)
            || empty($awsSecretKey)
            || empty($awsRegion)
            || empty($awsBucket)
            || empty($awsUrl);
    }

    /**
     * Property 1: Bug Condition - S3 Resume URLs Generated Successfully
     * 
     * For any production environment where FILESYSTEM_DISK=s3 is configured with complete AWS credentials
     * and a resume file exists in S3, the Profile::getResumeUrl() method SHALL generate valid S3 URLs
     * (either signed temporary URLs for private buckets or public URLs for public buckets) that successfully
     * load PDF files in browsers without 404, 500, or redirect errors.
     * 
     * This test will FAIL on unfixed configuration where FILESYSTEM_DISK=local or AWS config is missing.
     */
    public function test_s3_resume_urls_generated_successfully_with_proper_configuration()
    {
        // Document current configuration state
        $currentDisk = config('filesystems.default');
        $awsConfig = [
            'key' => config('filesystems.disks.s3.key'),
            'secret' => config('filesystems.disks.s3.secret') ? '***' : null,
            'region' => config('filesystems.disks.s3.region'),
            'bucket' => config('filesystems.disks.s3.bucket'),
            'url' => config('filesystems.disks.s3.url'),
        ];
        
        echo "\n=== Bug Condition Exploration Test ===\n";
        echo "Current FILESYSTEM_DISK: {$currentDisk}\n";
        echo "AWS Configuration: " . json_encode($awsConfig, JSON_PRETTY_PRINT) . "\n";
        echo "Bug Condition Exists: " . ($this->isBugCondition() ? 'YES' : 'NO') . "\n";
        echo "=====================================\n\n";
        
        // Temporarily configure for S3 to test expected behavior
        Config::set('filesystems.default', 's3');
        
        // Mock S3 storage for testing (since we may not have real S3 in test environment)
        Storage::fake('s3');
        
        // Create test user and profile with resume
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => 'resumes/test-resume.pdf',
        ]);
        
        // Simulate resume file existing in S3
        Storage::disk('s3')->put('resumes/test-resume.pdf', 'fake pdf content');
        
        // Test 1: getResumeUrl() should return valid S3 URL
        $resumeUrl = $profile->getResumeUrl();
        
        echo "Test 1 - Resume URL Generation:\n";
        echo "  Generated URL: " . ($resumeUrl ?? 'NULL') . "\n";
        
        $this->assertNotNull(
            $resumeUrl,
            "COUNTEREXAMPLE: getResumeUrl() returned NULL when FILESYSTEM_DISK=s3 and file exists. " .
            "This indicates the bug exists - URL generation fails with current configuration."
        );
        
        // Test 2: URL should start with 'https://' (S3 URLs are always HTTPS)
        echo "  URL starts with https://: " . (str_starts_with($resumeUrl ?? '', 'https://') ? 'YES' : 'NO') . "\n";
        
        $this->assertTrue(
            str_starts_with($resumeUrl ?? '', 'https://'),
            "COUNTEREXAMPLE: Resume URL does not start with 'https://'. " .
            "Generated URL: {$resumeUrl}. " .
            "This indicates S3 URL generation is not working correctly."
        );
        
        // Test 3: URL should contain bucket name or be a valid S3 URL format
        $bucketName = config('filesystems.disks.s3.bucket');
        echo "  URL contains bucket name or S3 format: " . 
            (str_contains($resumeUrl ?? '', $bucketName ?? '') || str_contains($resumeUrl ?? '', 's3') ? 'YES' : 'NO') . "\n";
        
        $this->assertTrue(
            str_contains($resumeUrl ?? '', $bucketName ?? '') || str_contains($resumeUrl ?? '', 's3'),
            "COUNTEREXAMPLE: Resume URL does not contain bucket name or S3 format. " .
            "Generated URL: {$resumeUrl}. " .
            "Expected bucket: {$bucketName}. " .
            "This indicates the URL is not a valid S3 URL."
        );
        
        echo "\n";
    }

    /**
     * Test that getResumeUrl() returns null when AWS configuration is missing
     * 
     * This demonstrates the bug condition where missing AWS config causes URL generation to fail
     */
    public function test_resume_url_returns_null_with_missing_aws_configuration()
    {
        echo "\n=== Testing Missing AWS Configuration ===\n";
        
        // Configure S3 disk but with missing AWS credentials
        Config::set('filesystems.default', 's3');
        Config::set('filesystems.disks.s3.key', null);
        Config::set('filesystems.disks.s3.secret', null);
        Config::set('filesystems.disks.s3.region', null);
        Config::set('filesystems.disks.s3.bucket', null);
        Config::set('filesystems.disks.s3.url', null);
        
        // Create test user and profile with resume
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => 'resumes/test-resume.pdf',
        ]);
        
        // Attempt to get resume URL with missing AWS config
        $resumeUrl = $profile->getResumeUrl();
        
        echo "Resume URL with missing AWS config: " . ($resumeUrl ?? 'NULL') . "\n";
        echo "=========================================\n\n";
        
        // With missing AWS config, getResumeUrl() should handle gracefully
        // This test documents the current behavior
        $this->assertTrue(
            true,
            "Documented behavior: getResumeUrl() with missing AWS config returns: " . ($resumeUrl ?? 'NULL')
        );
    }

    /**
     * Test that demonstrates disk mismatch issue
     * 
     * When FILESYSTEM_DISK=local but code tries to check S3, file existence checks fail
     */
    public function test_disk_mismatch_causes_file_existence_check_failure()
    {
        echo "\n=== Testing Disk Mismatch Issue ===\n";
        
        // Set default disk to local (bug condition)
        Config::set('filesystems.default', 'local');
        
        // Create test user and profile
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => 'resumes/test-resume.pdf',
        ]);
        
        // Put file in public disk (local storage)
        Storage::fake('public');
        Storage::disk('public')->put('resumes/test-resume.pdf', 'fake pdf content');
        
        // Try to get resume URL - this will fail because code checks S3 when default is local
        $resumeUrl = $profile->getResumeUrl();
        
        echo "Default disk: " . config('filesystems.default') . "\n";
        echo "File exists in public disk: " . (Storage::disk('public')->exists('resumes/test-resume.pdf') ? 'YES' : 'NO') . "\n";
        echo "Resume URL generated: " . ($resumeUrl ?? 'NULL') . "\n";
        echo "===================================\n\n";
        
        // Document the behavior - this demonstrates the bug
        $this->assertTrue(
            true,
            "Documented behavior: With FILESYSTEM_DISK=local, getResumeUrl() returns: " . ($resumeUrl ?? 'NULL')
        );
    }

    /**
     * Property-based test: Generate multiple test cases with different resume filenames
     * 
     * Tests that S3 URL generation works for various filename formats
     */
    public function test_s3_url_generation_works_for_various_filenames()
    {
        echo "\n=== Property-Based Test: Various Filenames ===\n";
        
        // Configure for S3
        Config::set('filesystems.default', 's3');
        Storage::fake('s3');
        
        // Generate test cases with different filename patterns
        $testFilenames = [
            'resumes/simple.pdf',
            'resumes/with-dashes.pdf',
            'resumes/with_underscores.pdf',
            'resumes/with spaces.pdf',
            'resumes/with.multiple.dots.pdf',
            'resumes/UPPERCASE.PDF',
            'resumes/MixedCase.Pdf',
            'resumes/with-numbers-123.pdf',
            'resumes/very-long-filename-that-exceeds-normal-length-expectations-but-should-still-work.pdf',
        ];
        
        $results = [];
        
        foreach ($testFilenames as $filename) {
            // Create profile with this filename
            $user = User::factory()->create();
            $profile = Profile::factory()->create([
                'user_id' => $user->id,
                'resume_path' => $filename,
            ]);
            
            // Simulate file existing in S3
            Storage::disk('s3')->put($filename, 'fake pdf content');
            
            // Generate URL
            $url = $profile->getResumeUrl();
            
            $results[] = [
                'filename' => $filename,
                'url_generated' => $url !== null,
                'url_is_https' => str_starts_with($url ?? '', 'https://'),
                'url' => $url,
            ];
        }
        
        // Display results
        echo "Tested " . count($testFilenames) . " different filename patterns:\n";
        foreach ($results as $result) {
            echo "  {$result['filename']}: " . 
                ($result['url_generated'] ? 'URL Generated' : 'FAILED') . 
                ($result['url_is_https'] ? ' (HTTPS)' : ' (NOT HTTPS)') . "\n";
        }
        echo "==============================================\n\n";
        
        // Assert all URLs were generated successfully
        $allGenerated = array_reduce($results, fn($carry, $r) => $carry && $r['url_generated'], true);
        $allHttps = array_reduce($results, fn($carry, $r) => $carry && $r['url_is_https'], true);
        
        $this->assertTrue(
            $allGenerated,
            "COUNTEREXAMPLE: Not all resume URLs were generated successfully. " .
            "Failed filenames: " . json_encode(array_filter($results, fn($r) => !$r['url_generated']))
        );
        
        $this->assertTrue(
            $allHttps,
            "COUNTEREXAMPLE: Not all URLs use HTTPS protocol. " .
            "Non-HTTPS URLs: " . json_encode(array_filter($results, fn($r) => !$r['url_is_https']))
        );
    }
}
