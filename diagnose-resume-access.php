<?php

/**
 * RESUME FILE ACCESS DIAGNOSTIC TOOL
 * 
 * This script diagnoses why production cannot access uploaded resume files.
 * Run on production: php diagnose-resume-access.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESUME FILE ACCESS DIAGNOSTIC TOOL                                  ║\n";
echo "║  Diagnosing: Why production cannot access uploaded resumes           ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ============================================================================
// STEP 1: ENVIRONMENT CONFIGURATION
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 1: ENVIRONMENT CONFIGURATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$defaultDisk = config('filesystems.default');
echo sprintf("Default Disk: %s\n", $defaultDisk);
echo sprintf("APP_ENV: %s\n", env('APP_ENV'));
echo sprintf("APP_DEBUG: %s\n", env('APP_DEBUG') ? 'true' : 'false');

// ============================================================================
// STEP 2: DISK CONFIGURATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 2: DISK CONFIGURATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$diskConfig = config("filesystems.disks.{$defaultDisk}");

if ($defaultDisk === 's3' || $defaultDisk === 'r2') {
    echo "Cloud Storage Configuration:\n";
    echo sprintf("  Driver:       %s\n", $diskConfig['driver'] ?? 'NOT SET');
    echo sprintf("  Region:       %s\n", $diskConfig['region'] ?? 'NOT SET');
    echo sprintf("  Bucket:       %s\n", $diskConfig['bucket'] ?? 'NOT SET');
    echo sprintf("  URL:          %s\n", $diskConfig['url'] ?? 'NOT SET');
    echo sprintf("  Endpoint:     %s\n", $diskConfig['endpoint'] ?? 'NOT SET');
    echo sprintf("  Path Style:   %s\n", ($diskConfig['use_path_style_endpoint'] ?? false) ? 'true' : 'false');
    
    $hasKey = !empty($diskConfig['key']);
    $hasSecret = !empty($diskConfig['secret']);
    echo sprintf("  Has Key:      %s\n", $hasKey ? 'YES' : 'NO');
    echo sprintf("  Has Secret:   %s\n", $hasSecret ? 'YES' : 'NO');
    
    if (!$hasKey || !$hasSecret) {
        echo "\n  ⚠️  WARNING: Cloud storage credentials not configured!\n";
        echo "  This will cause 'file not found' errors.\n";
    }
} else {
    echo "Local Storage Configuration:\n";
    echo sprintf("  Root:         %s\n", $diskConfig['root'] ?? 'NOT SET');
    echo sprintf("  Visibility:   %s\n", $diskConfig['visibility'] ?? 'NOT SET');
}

// ============================================================================
// STEP 3: DATABASE RESUME PATHS
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 3: DATABASE RESUME PATHS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$profiles = DB::table('profiles')
    ->whereNotNull('resume_path')
    ->select('id', 'user_id', 'resume_path', 'updated_at')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();

if ($profiles->isEmpty()) {
    echo "  ℹ️  No resume files found in database\n";
} else {
    echo sprintf("Found %d resume(s) in database:\n\n", $profiles->count());
    
    foreach ($profiles as $profile) {
        echo sprintf("Profile ID: %d (User ID: %d)\n", $profile->id, $profile->user_id);
        echo sprintf("  Path:         %s\n", $profile->resume_path);
        echo sprintf("  Updated:      %s\n", $profile->updated_at);
        
        // Analyze path format
        $path = $profile->resume_path;
        $hasLeadingSlash = str_starts_with($path, '/');
        $hasResumesPrefix = str_contains($path, 'resumes/');
        
        echo sprintf("  Leading slash: %s\n", $hasLeadingSlash ? 'YES (may cause issues)' : 'NO (correct)');
        echo sprintf("  Has 'resumes/': %s\n", $hasResumesPrefix ? 'YES' : 'NO');
        echo "\n";
    }
}

// ============================================================================
// STEP 4: TEST FILE ACCESS
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 4: TEST FILE ACCESS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (!$profiles->isEmpty()) {
    $testProfile = $profiles->first();
    $path = $testProfile->resume_path;
    $normalizedPath = ltrim($path, '/');
    
    echo sprintf("Testing access to: %s\n", $path);
    echo sprintf("Normalized path:   %s\n", $normalizedPath);
    echo "\n";
    
    // Test 1: Check if file exists
    echo "Test 1: Storage::disk()->exists()\n";
    try {
        $exists = Storage::disk($defaultDisk)->exists($normalizedPath);
        echo sprintf("  Result: %s\n", $exists ? '✓ EXISTS' : '✗ NOT FOUND');
        
        if (!$exists) {
            echo "  ⚠️  This is the root cause! File not found in storage.\n";
            echo "  Possible reasons:\n";
            echo "    1. File was never uploaded to cloud storage\n";
            echo "    2. Wrong bucket configuration\n";
            echo "    3. Wrong credentials\n";
            echo "    4. Path format mismatch\n";
        }
    } catch (\Exception $e) {
        echo sprintf("  ✗ ERROR: %s\n", $e->getMessage());
        echo "  ⚠️  This indicates a configuration problem!\n";
    }
    echo "\n";
    
    // Test 2: Try to get file content
    if (isset($exists) && $exists) {
        echo "Test 2: Storage::disk()->get()\n";
        try {
            $content = Storage::disk($defaultDisk)->get($normalizedPath);
            $size = strlen($content);
            echo sprintf("  Result: ✓ SUCCESS (%d bytes)\n", $size);
            
            // Test 3: Validate PDF magic bytes
            echo "\nTest 3: PDF Magic Bytes Validation\n";
            $isPdf = str_starts_with($content, '%PDF');
            echo sprintf("  Result: %s\n", $isPdf ? '✓ VALID PDF' : '✗ INVALID PDF');
            
            if (!$isPdf) {
                echo sprintf("  First 20 bytes: %s\n", bin2hex(substr($content, 0, 20)));
            }
            
        } catch (\Exception $e) {
            echo sprintf("  ✗ ERROR: %s\n", $e->getMessage());
        }
    }
    
    // Test 4: Try alternative path formats
    echo "\nTest 4: Alternative Path Formats\n";
    $alternativePaths = [
        $path,                          // Original
        $normalizedPath,                // Without leading slash
        'resumes/' . basename($path),   // Just filename with resumes/
        basename($path),                // Just filename
    ];
    
    foreach ($alternativePaths as $altPath) {
        try {
            $exists = Storage::disk($defaultDisk)->exists($altPath);
            echo sprintf("  %-40s: %s\n", $altPath, $exists ? '✓ FOUND' : '✗ NOT FOUND');
        } catch (\Exception $e) {
            echo sprintf("  %-40s: ✗ ERROR\n", $altPath);
        }
    }
    
} else {
    echo "  ℹ️  No resume files to test\n";
}

// ============================================================================
// STEP 5: TEST STORAGE WRITE
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 5: TEST STORAGE WRITE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$testContent = "Test file created at " . now()->toDateTimeString();
$testPath = 'test/diagnostic_' . time() . '.txt';

echo "Attempting to write test file...\n";
echo sprintf("  Path: %s\n", $testPath);

try {
    $written = Storage::disk($defaultDisk)->put($testPath, $testContent);
    
    if ($written) {
        echo "  ✓ Write successful\n";
        
        // Try to read it back
        echo "\nAttempting to read test file...\n";
        $readContent = Storage::disk($defaultDisk)->get($testPath);
        echo sprintf("  ✓ Read successful (%d bytes)\n", strlen($readContent));
        
        // Cleanup
        Storage::disk($defaultDisk)->delete($testPath);
        echo "  ✓ Cleanup successful\n";
        
    } else {
        echo "  ✗ Write failed\n";
    }
    
} catch (\Exception $e) {
    echo sprintf("  ✗ ERROR: %s\n", $e->getMessage());
    echo "\n  ⚠️  Storage write test failed!\n";
    echo "  This indicates:\n";
    echo "    1. Wrong credentials\n";
    echo "    2. Insufficient permissions\n";
    echo "    3. Wrong bucket configuration\n";
    echo "    4. Network connectivity issues\n";
}

// ============================================================================
// STEP 6: LIST FILES IN BUCKET
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 6: LIST FILES IN BUCKET\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    echo "Listing files in 'resumes/' directory...\n\n";
    $files = Storage::disk($defaultDisk)->files('resumes');
    
    if (empty($files)) {
        echo "  ℹ️  No files found in 'resumes/' directory\n";
        echo "  This means:\n";
        echo "    1. Files were never uploaded to cloud storage\n";
        echo "    2. Files are in a different directory\n";
        echo "    3. Wrong bucket is being accessed\n";
    } else {
        echo sprintf("Found %d file(s):\n\n", count($files));
        foreach (array_slice($files, 0, 10) as $file) {
            $size = Storage::disk($defaultDisk)->size($file);
            $lastModified = Storage::disk($defaultDisk)->lastModified($file);
            echo sprintf("  %s\n", $file);
            echo sprintf("    Size: %d bytes (%.2f KB)\n", $size, $size / 1024);
            echo sprintf("    Modified: %s\n", date('Y-m-d H:i:s', $lastModified));
            echo "\n";
        }
    }
    
} catch (\Exception $e) {
    echo sprintf("  ✗ ERROR: %s\n", $e->getMessage());
    echo "\n  ⚠️  Cannot list files in bucket!\n";
    echo "  This indicates a serious configuration problem.\n";
}

// ============================================================================
// STEP 7: RECOMMENDATIONS
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 7: RECOMMENDATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$issues = [];

// Check credentials
if ($defaultDisk === 's3' || $defaultDisk === 'r2') {
    if (empty($diskConfig['key']) || empty($diskConfig['secret'])) {
        $issues[] = "✗ CRITICAL: Cloud storage credentials not configured";
        $issues[] = "  Fix: Set AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY in .env";
    }
    
    if (empty($diskConfig['bucket'])) {
        $issues[] = "✗ CRITICAL: Bucket name not configured";
        $issues[] = "  Fix: Set AWS_BUCKET in .env";
    }
    
    if (($diskConfig['region'] ?? '') !== 'auto' && $defaultDisk === 'r2') {
        $issues[] = "⚠️  WARNING: R2 region should be 'auto'";
        $issues[] = "  Fix: Set AWS_DEFAULT_REGION=auto in .env";
    }
}

// Check if files exist in database but not in storage
if (!$profiles->isEmpty() && isset($exists) && !$exists) {
    $issues[] = "✗ CRITICAL: Resume paths in database but files not in storage";
    $issues[] = "  This means uploads are saving to database but not to cloud storage";
    $issues[] = "  Fix: Verify ProfileController is using correct disk for upload";
}

if (empty($issues)) {
    echo "✓ No critical issues detected!\n\n";
    echo "If you're still experiencing issues:\n";
    echo "  1. Check production logs: storage/logs/laravel.log\n";
    echo "  2. Verify network connectivity to cloud storage\n";
    echo "  3. Check cloud storage dashboard for actual files\n";
} else {
    echo "Issues detected:\n\n";
    foreach ($issues as $issue) {
        echo "  {$issue}\n";
    }
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC COMPLETE                                                 ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
