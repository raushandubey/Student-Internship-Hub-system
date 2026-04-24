<?php

/**
 * Laravel Cloud + Cloudflare R2 Configuration Verification Script
 * 
 * Purpose: Verify R2 configuration works on Laravel Cloud free tier
 * Platform Constraint: AWS_URL cannot be modified (set by Laravel Cloud)
 * Solution: Manual R2 URL construction via R2_PUBLIC_URL
 * 
 * Usage: php verify-laravel-cloud-r2.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   Laravel Cloud + Cloudflare R2 Configuration Verification    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$errors = [];
$warnings = [];
$success = [];

// ============================================================================
// TEST 1: Check Platform
// ============================================================================
echo "TEST 1: Platform Detection\n";
echo str_repeat("-", 80) . "\n";

$appUrl = config('app.url');
$isLaravelCloud = strpos($appUrl, '.laravel.cloud') !== false;

echo "APP_URL: {$appUrl}\n";
echo "Platform: " . ($isLaravelCloud ? "Laravel Cloud" : "Other") . "\n";

if ($isLaravelCloud) {
    echo "✓ Laravel Cloud detected\n";
    echo "ℹ AWS_URL is managed by platform (cannot be modified)\n";
    $success[] = "Laravel Cloud platform detected";
} else {
    echo "⚠ Not Laravel Cloud - this solution is for Laravel Cloud free tier\n";
    $warnings[] = "Platform is not Laravel Cloud";
}
echo "\n";

// ============================================================================
// TEST 2: Check Filesystem Configuration
// ============================================================================
echo "TEST 2: Filesystem Configuration\n";
echo str_repeat("-", 80) . "\n";

$disk = config('filesystems.default');
echo "FILESYSTEM_DISK: {$disk}\n";

if ($disk === 's3') {
    echo "✓ FILESYSTEM_DISK is 's3'\n";
    $success[] = "Filesystem disk configured correctly";
} else {
    echo "✗ FILESYSTEM_DISK is '{$disk}' but should be 's3' for R2\n";
    $errors[] = "FILESYSTEM_DISK must be 's3' for R2";
}
echo "\n";

// ============================================================================
// TEST 3: Check AWS_URL (Platform Managed)
// ============================================================================
echo "TEST 3: AWS_URL (Platform Managed)\n";
echo str_repeat("-", 80) . "\n";

$awsUrl = config('filesystems.disks.s3.url');
echo "AWS_URL: " . ($awsUrl ?? 'NOT SET') . "\n";

if ($awsUrl === $appUrl) {
    echo "ℹ AWS_URL matches APP_URL (expected on Laravel Cloud)\n";
    echo "ℹ This is why Storage::url() causes redirects\n";
    echo "✓ Platform behavior confirmed\n";
    $success[] = "AWS_URL platform behavior confirmed";
} elseif (strpos($awsUrl, '.r2.dev') !== false) {
    echo "⚠ AWS_URL appears to be R2 URL\n";
    echo "ℹ This might work, but Laravel Cloud typically overrides this\n";
    $warnings[] = "AWS_URL configuration unexpected for Laravel Cloud";
} else {
    echo "⚠ AWS_URL configuration unexpected\n";
    $warnings[] = "AWS_URL value unexpected";
}
echo "\n";

// ============================================================================
// TEST 4: Check R2_PUBLIC_URL (CRITICAL)
// ============================================================================
echo "TEST 4: R2_PUBLIC_URL Configuration (CRITICAL)\n";
echo str_repeat("-", 80) . "\n";

$r2PublicUrl = config('filesystems.disks.s3.r2_public_url');
echo "R2_PUBLIC_URL: " . ($r2PublicUrl ?? 'NOT SET') . "\n";

if (empty($r2PublicUrl)) {
    echo "✗ CRITICAL: R2_PUBLIC_URL is not set!\n";
    echo "ℹ This is required for manual URL construction\n";
    echo "ℹ Add to Laravel Cloud environment variables:\n";
    echo "   Key: R2_PUBLIC_URL\n";
    echo "   Value: https://pub-{hash}.r2.dev\n";
    $errors[] = "R2_PUBLIC_URL is required for Laravel Cloud solution";
} else {
    if (strpos($r2PublicUrl, '.r2.dev') !== false) {
        echo "✓ R2_PUBLIC_URL appears to be R2 public bucket URL\n";
        $success[] = "R2_PUBLIC_URL configured correctly";
        
        if (strpos($r2PublicUrl, 'pub-') !== false) {
            echo "✓ URL format is correct (pub-{hash}.r2.dev)\n";
            $success[] = "R2 public URL format correct";
        } else {
            echo "⚠ URL doesn't contain 'pub-' prefix\n";
            echo "ℹ Expected format: https://pub-{hash}.r2.dev\n";
            $warnings[] = "R2 URL format should be verified";
        }
    } else {
        echo "✗ R2_PUBLIC_URL doesn't appear to be R2 URL\n";
        echo "ℹ Expected format: https://pub-{hash}.r2.dev\n";
        $errors[] = "R2_PUBLIC_URL must be R2 public bucket URL";
    }
}
echo "\n";

// ============================================================================
// TEST 5: Check R2 Configuration
// ============================================================================
echo "TEST 5: R2 Configuration\n";
echo str_repeat("-", 80) . "\n";

$region = config('filesystems.disks.s3.region');
$pathStyle = config('filesystems.disks.s3.use_path_style_endpoint');
$endpoint = config('filesystems.disks.s3.endpoint');

echo "AWS_DEFAULT_REGION: {$region}\n";
if ($region === 'auto') {
    echo "✓ Region is 'auto' (correct for R2)\n";
    $success[] = "Region configured correctly";
} else {
    echo "✗ Region is '{$region}' but should be 'auto' for R2\n";
    $errors[] = "AWS_DEFAULT_REGION must be 'auto' for R2";
}

echo "AWS_USE_PATH_STYLE_ENDPOINT: " . ($pathStyle ? 'true' : 'false') . "\n";
if ($pathStyle === true) {
    echo "✓ Path-style endpoints enabled (correct for R2)\n";
    $success[] = "Path-style endpoints configured correctly";
} else {
    echo "✗ Path-style endpoints disabled but must be enabled for R2\n";
    $errors[] = "AWS_USE_PATH_STYLE_ENDPOINT must be true for R2";
}

echo "AWS_ENDPOINT: " . ($endpoint ?? 'NOT SET') . "\n";
if (!empty($endpoint) && strpos($endpoint, '.r2.cloudflarestorage.com') !== false) {
    echo "✓ AWS_ENDPOINT is R2 endpoint\n";
    $success[] = "AWS_ENDPOINT configured correctly";
} else {
    echo "✗ AWS_ENDPOINT doesn't appear to be R2 endpoint\n";
    $errors[] = "AWS_ENDPOINT must be R2 endpoint";
}
echo "\n";

// ============================================================================
// TEST 6: Test URL Generation (Manual Construction)
// ============================================================================
echo "TEST 6: URL Generation Test (Manual Construction)\n";
echo str_repeat("-", 80) . "\n";

if (!empty($r2PublicUrl)) {
    $testPath = 'resumes/test.pdf';
    $manualUrl = rtrim($r2PublicUrl, '/') . '/' . $testPath;
    
    echo "Test path: {$testPath}\n";
    echo "Manual URL: {$manualUrl}\n";
    
    if (strpos($manualUrl, '.r2.dev') !== false) {
        echo "✓ Manual URL points to R2\n";
        $success[] = "Manual URL construction working correctly";
    } else {
        echo "✗ Manual URL doesn't point to R2\n";
        $errors[] = "Manual URL construction failed";
    }
    
    // Compare with Storage::url() (should be different)
    try {
        $storageUrl = Storage::disk('s3')->url($testPath);
        echo "Storage::url(): {$storageUrl}\n";
        
        if ($storageUrl !== $manualUrl) {
            echo "✓ Manual URL differs from Storage::url() (expected)\n";
            echo "ℹ This confirms we're bypassing Laravel Cloud's AWS_URL\n";
            $success[] = "Manual URL construction bypasses platform restriction";
        } else {
            echo "⚠ Manual URL matches Storage::url()\n";
            $warnings[] = "URL construction behavior unexpected";
        }
    } catch (\Exception $e) {
        echo "⚠ Storage::url() failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Cannot test URL generation - R2_PUBLIC_URL not set\n";
    $errors[] = "URL generation test skipped - R2_PUBLIC_URL required";
}
echo "\n";

// ============================================================================
// TEST 7: Test Actual Resume
// ============================================================================
echo "TEST 7: Actual Resume URL Test\n";
echo str_repeat("-", 80) . "\n";

try {
    $profile = App\Models\Profile::whereNotNull('resume_path')->first();
    
    if ($profile) {
        echo "Found profile with resume\n";
        echo "Profile ID: {$profile->id}\n";
        echo "Resume path: {$profile->resume_path}\n";
        
        $url = $profile->getResumeUrl();
        echo "Generated URL: " . ($url ?? 'NULL') . "\n";
        
        if ($url) {
            if (strpos($url, $appUrl) !== false) {
                echo "✗ CRITICAL: Resume URL points to Laravel domain!\n";
                echo "ℹ This will cause ERR_TOO_MANY_REDIRECTS\n";
                $errors[] = "Resume URLs redirect to Laravel domain";
            } elseif (strpos($url, '.r2.dev') !== false) {
                echo "✓ Resume URL points to R2\n";
                $success[] = "Resume URLs working correctly";
            } else {
                echo "⚠ Resume URL format unexpected\n";
                $warnings[] = "Resume URL should be verified";
            }
        } else {
            echo "✗ Resume URL is NULL\n";
            $errors[] = "Resume URL generation returned NULL";
        }
    } else {
        echo "⚠ No profiles with resumes found\n";
        $warnings[] = "No test data available";
    }
} catch (\Exception $e) {
    echo "✗ Error checking profiles: " . $e->getMessage() . "\n";
}
echo "\n";

// ============================================================================
// SUMMARY
// ============================================================================
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

if (count($errors) > 0) {
    echo "✗ ERRORS FOUND: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "  • {$error}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠ WARNINGS: " . count($warnings) . "\n";
    foreach ($warnings as $warning) {
        echo "  • {$warning}\n";
    }
    echo "\n";
}

if (count($success) > 0) {
    echo "✓ SUCCESS: " . count($success) . "\n";
    foreach ($success as $item) {
        echo "  • {$item}\n";
    }
    echo "\n";
}

// ============================================================================
// RECOMMENDATIONS
// ============================================================================
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                     RECOMMENDATIONS                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

if (count($errors) > 0) {
    echo "CRITICAL ISSUES DETECTED\n\n";
    
    // Check for R2_PUBLIC_URL issue
    if (empty($r2PublicUrl)) {
        echo "PRIMARY ISSUE: R2_PUBLIC_URL Not Configured\n\n";
        echo "SOLUTION:\n";
        echo "1. Go to Cloudflare R2 Dashboard\n";
        echo "2. Open your bucket → Settings → Public access\n";
        echo "3. Enable public access if not already enabled\n";
        echo "4. Copy the public bucket URL (format: https://pub-{hash}.r2.dev)\n";
        echo "5. Add to Laravel Cloud environment variables:\n\n";
        echo "   Key: R2_PUBLIC_URL\n";
        echo "   Value: https://pub-your-hash-here.r2.dev\n\n";
        echo "6. Redeploy your application\n\n";
    }
    
    exit(1);
} else {
    echo "✓ CONFIGURATION LOOKS GOOD\n\n";
    echo "Your Laravel Cloud + R2 configuration appears to be correct.\n\n";
    echo "If you're still experiencing issues:\n";
    echo "1. Verify public access is enabled on your R2 bucket\n";
    echo "2. Test URL in browser (should open without redirects)\n";
    echo "3. Check Laravel logs for any errors\n\n";
    exit(0);
}
