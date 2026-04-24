<?php

/**
 * S3 Production Configuration Verification Script
 * 
 * Run this script to verify S3 configuration is correct
 * Usage: php verify-s3-production.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     S3 Production Configuration Verification                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$errors = [];
$warnings = [];
$success = [];

// Test 1: Check FILESYSTEM_DISK
echo "1. Checking FILESYSTEM_DISK configuration...\n";
$disk = config('filesystems.default');
if ($disk === 's3') {
    echo "   ✓ FILESYSTEM_DISK is set to 's3'\n";
    $success[] = "FILESYSTEM_DISK configured correctly";
} else {
    echo "   ✗ FILESYSTEM_DISK is '{$disk}' (should be 's3' for production)\n";
    $errors[] = "FILESYSTEM_DISK must be 's3' in production";
}
echo "\n";

// Test 2: Check AWS Credentials
echo "2. Checking AWS credentials...\n";
$awsKey = config('filesystems.disks.s3.key');
$awsSecret = config('filesystems.disks.s3.secret');
$awsRegion = config('filesystems.disks.s3.region');
$awsBucket = config('filesystems.disks.s3.bucket');
$awsUrl = config('filesystems.disks.s3.url');

if (empty($awsKey)) {
    echo "   ✗ AWS_ACCESS_KEY_ID is not set\n";
    $errors[] = "AWS_ACCESS_KEY_ID is required";
} else {
    echo "   ✓ AWS_ACCESS_KEY_ID is set\n";
    $success[] = "AWS_ACCESS_KEY_ID configured";
}

if (empty($awsSecret)) {
    echo "   ✗ AWS_SECRET_ACCESS_KEY is not set\n";
    $errors[] = "AWS_SECRET_ACCESS_KEY is required";
} else {
    echo "   ✓ AWS_SECRET_ACCESS_KEY is set\n";
    $success[] = "AWS_SECRET_ACCESS_KEY configured";
}

if (empty($awsRegion)) {
    echo "   ✗ AWS_DEFAULT_REGION is not set\n";
    $errors[] = "AWS_DEFAULT_REGION is required";
} else {
    echo "   ✓ AWS_DEFAULT_REGION: {$awsRegion}\n";
    $success[] = "AWS_DEFAULT_REGION configured";
}

if (empty($awsBucket)) {
    echo "   ✗ AWS_BUCKET is not set\n";
    $errors[] = "AWS_BUCKET is required";
} else {
    echo "   ✓ AWS_BUCKET: {$awsBucket}\n";
    $success[] = "AWS_BUCKET configured";
}

if (empty($awsUrl)) {
    echo "   ✗ AWS_URL is not set\n";
    $errors[] = "AWS_URL is required";
} else {
    echo "   ✓ AWS_URL: {$awsUrl}\n";
    
    // Validate AWS_URL format
    $expectedUrl = "https://{$awsBucket}.s3.{$awsRegion}.amazonaws.com";
    if ($awsUrl === $expectedUrl) {
        echo "   ✓ AWS_URL format is correct\n";
        $success[] = "AWS_URL format correct";
    } else {
        echo "   ⚠ AWS_URL format may be incorrect\n";
        echo "     Expected: {$expectedUrl}\n";
        echo "     Actual:   {$awsUrl}\n";
        $warnings[] = "AWS_URL format should be verified";
    }
}
echo "\n";

// Test 3: Check S3 Disk Configuration
echo "3. Checking S3 disk configuration...\n";
$s3Config = config('filesystems.disks.s3');

if (isset($s3Config['visibility']) && $s3Config['visibility'] === 'public') {
    echo "   ✓ Default visibility is 'public'\n";
    $success[] = "S3 visibility configured";
} else {
    echo "   ✗ Default visibility is not set to 'public'\n";
    $errors[] = "S3 disk must have 'visibility' => 'public'";
}

if (isset($s3Config['options']['ACL']) && $s3Config['options']['ACL'] === 'public-read') {
    echo "   ✓ Default ACL is 'public-read'\n";
    $success[] = "S3 ACL configured";
} else {
    echo "   ✗ Default ACL is not set to 'public-read'\n";
    $errors[] = "S3 disk must have 'ACL' => 'public-read' in options";
}
echo "\n";

// Test 4: Test S3 Connection (if credentials are set)
if (!empty($awsKey) && !empty($awsSecret) && !empty($awsBucket)) {
    echo "4. Testing S3 connection...\n";
    try {
        // Try to list files
        $files = Storage::disk('s3')->files('resumes');
        echo "   ✓ Successfully connected to S3\n";
        echo "   ✓ Found " . count($files) . " files in resumes folder\n";
        $success[] = "S3 connection successful";
        
        // Test URL generation
        if (count($files) > 0) {
            $testFile = $files[0];
            $url = Storage::disk('s3')->url($testFile);
            echo "   ✓ URL generation works\n";
            echo "     Sample URL: {$url}\n";
            
            // Check URL format
            if (strpos($url, 'X-Amz-') === false) {
                echo "   ✓ URL is direct public URL (no signed parameters)\n";
                $success[] = "Direct public URLs working";
            } else {
                echo "   ✗ URL contains signed parameters (should be direct public URL)\n";
                $errors[] = "URLs should be direct public, not signed";
            }
        }
    } catch (\Exception $e) {
        echo "   ✗ S3 connection failed\n";
        echo "     Error: " . $e->getMessage() . "\n";
        $errors[] = "S3 connection failed: " . $e->getMessage();
    }
    echo "\n";
}

// Test 5: Check Profile Model
echo "5. Checking Profile model URL generation...\n";
try {
    $profile = App\Models\Profile::whereNotNull('resume_path')->first();
    
    if ($profile) {
        $url = $profile->getResumeUrl();
        
        if ($url) {
            echo "   ✓ Profile::getResumeUrl() returns URL\n";
            echo "     URL: {$url}\n";
            
            // Check if it's a direct URL
            if (strpos($url, 'X-Amz-') === false) {
                echo "   ✓ URL is direct public URL (correct)\n";
                $success[] = "Profile model using direct URLs";
            } else {
                echo "   ✗ URL is signed URL (should be direct public URL)\n";
                $errors[] = "Profile model should use Storage::disk('s3')->url() not temporaryUrl()";
            }
            
            // Check URL format
            if (strpos($url, 'https://') === 0) {
                echo "   ✓ URL uses HTTPS\n";
                $success[] = "URLs use HTTPS";
            } else {
                echo "   ⚠ URL does not use HTTPS\n";
                $warnings[] = "URLs should use HTTPS";
            }
        } else {
            echo "   ⚠ Profile::getResumeUrl() returned null\n";
            $warnings[] = "Resume URL is null (file may not exist)";
        }
    } else {
        echo "   ⚠ No profiles with resumes found in database\n";
        $warnings[] = "No test data available";
    }
} catch (\Exception $e) {
    echo "   ✗ Error checking Profile model\n";
    echo "     Error: " . $e->getMessage() . "\n";
    $errors[] = "Profile model error: " . $e->getMessage();
}
echo "\n";

// Summary
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "✓ Success: " . count($success) . "\n";
foreach ($success as $item) {
    echo "  - {$item}\n";
}
echo "\n";

if (count($warnings) > 0) {
    echo "⚠ Warnings: " . count($warnings) . "\n";
    foreach ($warnings as $item) {
        echo "  - {$item}\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "✗ Errors: " . count($errors) . "\n";
    foreach ($errors as $item) {
        echo "  - {$item}\n";
    }
    echo "\n";
    
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                    CONFIGURATION FAILED                        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Please fix the errors above before deploying to production.\n";
    echo "See S3_PRODUCTION_DEPLOYMENT_GUIDE.md for detailed instructions.\n";
    echo "\n";
    exit(1);
} else {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║              ✓ CONFIGURATION VERIFIED                         ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "S3 configuration is correct and ready for production!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Configure S3 bucket policy (see S3_PRODUCTION_DEPLOYMENT_GUIDE.md)\n";
    echo "2. Deploy code to production\n";
    echo "3. Run: php artisan resumes:fix-s3-permissions\n";
    echo "4. Test resume access in browser\n";
    echo "\n";
    exit(0);
}
