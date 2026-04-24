<?php

/**
 * Cloudflare R2 Configuration Diagnostic Script
 * 
 * Run this to identify configuration issues causing ERR_TOO_MANY_REDIRECTS
 * Usage: php diagnose-r2-config.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     Cloudflare R2 Configuration Diagnostic                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$errors = [];
$warnings = [];
$success = [];

// Test 1: Check FILESYSTEM_DISK
echo "1. Checking FILESYSTEM_DISK...\n";
$disk = config('filesystems.default');
if ($disk === 's3') {
    echo "   ✓ FILESYSTEM_DISK is 's3'\n";
    $success[] = "FILESYSTEM_DISK configured correctly";
} else {
    echo "   ✗ FILESYSTEM_DISK is '{$disk}' (should be 's3' for R2)\n";
    $errors[] = "FILESYSTEM_DISK must be 's3' for R2";
}
echo "\n";

// Test 2: Check R2-specific configuration
echo "2. Checking R2-specific settings...\n";

$region = config('filesystems.disks.s3.region');
if ($region === 'auto') {
    echo "   ✓ AWS_DEFAULT_REGION is 'auto' (correct for R2)\n";
    $success[] = "Region configured correctly for R2";
} else {
    echo "   ✗ AWS_DEFAULT_REGION is '{$region}' (should be 'auto' for R2)\n";
    $errors[] = "AWS_DEFAULT_REGION must be 'auto' for Cloudflare R2";
}

$pathStyle = config('filesystems.disks.s3.use_path_style_endpoint');
if ($pathStyle === true) {
    echo "   ✓ AWS_USE_PATH_STYLE_ENDPOINT is true (correct for R2)\n";
    $success[] = "Path-style endpoints configured correctly";
} else {
    echo "   ✗ AWS_USE_PATH_STYLE_ENDPOINT is " . ($pathStyle ? 'true' : 'false') . " (should be true for R2)\n";
    $errors[] = "AWS_USE_PATH_STYLE_ENDPOINT must be true for R2";
}
echo "\n";

// Test 3: Check AWS_URL (CRITICAL)
echo "3. Checking AWS_URL (CRITICAL for redirect issue)...\n";
$awsUrl = config('filesystems.disks.s3.url');
$appUrl = config('app.url');

if (empty($awsUrl)) {
    echo "   ✗ AWS_URL is not set\n";
    $errors[] = "AWS_URL is required";
} else {
    echo "   Current AWS_URL: {$awsUrl}\n";
    echo "   Current APP_URL: {$appUrl}\n";
    echo "\n";
    
    // Check if AWS_URL matches APP_URL (common mistake)
    if ($awsUrl === $appUrl) {
        echo "   ✗ CRITICAL ERROR: AWS_URL matches APP_URL!\n";
        echo "     This causes ERR_TOO_MANY_REDIRECTS\n";
        echo "     AWS_URL should be your R2 public bucket URL\n";
        echo "     Format: https://pub-{hash}.r2.dev\n";
        $errors[] = "AWS_URL must be R2 public bucket URL, not Laravel domain";
    } elseif (strpos($awsUrl, '.r2.dev') !== false) {
        echo "   ✓ AWS_URL appears to be R2 public bucket URL\n";
        $success[] = "AWS_URL configured correctly";
        
        // Check if it's the public URL format
        if (strpos($awsUrl, 'pub-') !== false) {
            echo "   ✓ URL format is correct (pub-{hash}.r2.dev)\n";
            $success[] = "R2 public URL format correct";
        } else {
            echo "   ⚠ URL doesn't contain 'pub-' prefix\n";
            echo "     Expected format: https://pub-{hash}.r2.dev\n";
            $warnings[] = "R2 URL format should be verified";
        }
    } elseif (strpos($awsUrl, '.r2.cloudflarestorage.com') !== false) {
        echo "   ✗ CRITICAL ERROR: AWS_URL is set to R2 endpoint!\n";
        echo "     This is the API endpoint, not the public URL\n";
        echo "     AWS_URL should be: https://pub-{hash}.r2.dev\n";
        echo "     AWS_ENDPOINT should be: https://{account}.r2.cloudflarestorage.com\n";
        $errors[] = "AWS_URL must be public bucket URL, not endpoint";
    } else {
        echo "   ⚠ AWS_URL doesn't appear to be R2 URL\n";
        echo "     Expected format: https://pub-{hash}.r2.dev\n";
        $warnings[] = "AWS_URL format should be verified";
    }
}
echo "\n";

// Test 4: Check AWS_ENDPOINT
echo "4. Checking AWS_ENDPOINT...\n";
$endpoint = config('filesystems.disks.s3.endpoint');

if (empty($endpoint)) {
    echo "   ✗ AWS_ENDPOINT is not set\n";
    $errors[] = "AWS_ENDPOINT is required for R2";
} else {
    echo "   AWS_ENDPOINT: {$endpoint}\n";
    
    if (strpos($endpoint, '.r2.cloudflarestorage.com') !== false) {
        echo "   ✓ AWS_ENDPOINT is R2 endpoint\n";
        $success[] = "AWS_ENDPOINT configured correctly";
    } else {
        echo "   ✗ AWS_ENDPOINT doesn't appear to be R2 endpoint\n";
        echo "     Expected format: https://{account_id}.r2.cloudflarestorage.com\n";
        $errors[] = "AWS_ENDPOINT must be R2 endpoint";
    }
}
echo "\n";

// Test 5: Check credentials
echo "5. Checking R2 credentials...\n";
$accessKey = config('filesystems.disks.s3.key');
$secretKey = config('filesystems.disks.s3.secret');
$bucket = config('filesystems.disks.s3.bucket');

if (empty($accessKey)) {
    echo "   ✗ AWS_ACCESS_KEY_ID is not set\n";
    $errors[] = "AWS_ACCESS_KEY_ID is required";
} else {
    echo "   ✓ AWS_ACCESS_KEY_ID is set\n";
    $success[] = "Access key configured";
}

if (empty($secretKey)) {
    echo "   ✗ AWS_SECRET_ACCESS_KEY is not set\n";
    $errors[] = "AWS_SECRET_ACCESS_KEY is required";
} else {
    echo "   ✓ AWS_SECRET_ACCESS_KEY is set\n";
    $success[] = "Secret key configured";
}

if (empty($bucket)) {
    echo "   ✗ AWS_BUCKET is not set\n";
    $errors[] = "AWS_BUCKET is required";
} else {
    echo "   ✓ AWS_BUCKET: {$bucket}\n";
    $success[] = "Bucket name configured";
}
echo "\n";

// Test 6: Test URL generation
echo "6. Testing URL generation...\n";
try {
    $testPath = 'resumes/test.pdf';
    $generatedUrl = Storage::disk('s3')->url($testPath);
    
    echo "   Test path: {$testPath}\n";
    echo "   Generated URL: {$generatedUrl}\n";
    echo "\n";
    
    // Analyze generated URL
    if (strpos($generatedUrl, $appUrl) !== false) {
        echo "   ✗ CRITICAL: Generated URL contains Laravel domain!\n";
        echo "     This will cause ERR_TOO_MANY_REDIRECTS\n";
        echo "     Fix: Set AWS_URL to R2 public bucket URL\n";
        $errors[] = "Generated URLs point to Laravel domain instead of R2";
    } elseif (strpos($generatedUrl, '.r2.dev') !== false) {
        echo "   ✓ Generated URL points to R2\n";
        $success[] = "URL generation working correctly";
    } else {
        echo "   ⚠ Generated URL format unexpected\n";
        $warnings[] = "URL generation should be verified";
    }
} catch (\Exception $e) {
    echo "   ✗ URL generation failed\n";
    echo "     Error: " . $e->getMessage() . "\n";
    $errors[] = "URL generation error: " . $e->getMessage();
}
echo "\n";

// Test 7: Check if files exist
echo "7. Checking for existing resume files...\n";
try {
    $profile = App\Models\Profile::whereNotNull('resume_path')->first();
    
    if ($profile) {
        echo "   Found profile with resume\n";
        echo "   Resume path: {$profile->resume_path}\n";
        
        $url = $profile->getResumeUrl();
        echo "   Generated URL: " . ($url ?? 'NULL') . "\n";
        
        if ($url) {
            if (strpos($url, $appUrl) !== false) {
                echo "   ✗ CRITICAL: Resume URL points to Laravel domain!\n";
                echo "     This causes ERR_TOO_MANY_REDIRECTS\n";
                $errors[] = "Resume URLs redirect to Laravel domain";
            } elseif (strpos($url, '.r2.dev') !== false) {
                echo "   ✓ Resume URL points to R2\n";
                $success[] = "Resume URLs working correctly";
            }
        }
    } else {
        echo "   ⚠ No profiles with resumes found\n";
        $warnings[] = "No test data available";
    }
} catch (\Exception $e) {
    echo "   ✗ Error checking profiles\n";
    echo "     Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                         DIAGNOSIS                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

if (count($errors) > 0) {
    echo "✗ ERRORS FOUND: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠ WARNINGS: " . count($warnings) . "\n";
    foreach ($warnings as $warning) {
        echo "  - {$warning}\n";
    }
    echo "\n";
}

if (count($success) > 0) {
    echo "✓ SUCCESS: " . count($success) . "\n";
    foreach ($success as $item) {
        echo "  - {$item}\n";
    }
    echo "\n";
}

// Recommendations
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                     RECOMMENDATIONS                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

if (count($errors) > 0) {
    echo "CRITICAL ISSUES DETECTED\n";
    echo "\n";
    
    // Check for the main issue
    $hasUrlIssue = false;
    foreach ($errors as $error) {
        if (strpos($error, 'AWS_URL') !== false) {
            $hasUrlIssue = true;
            break;
        }
    }
    
    if ($hasUrlIssue) {
        echo "PRIMARY ISSUE: AWS_URL Configuration\n";
        echo "\n";
        echo "Your AWS_URL is causing ERR_TOO_MANY_REDIRECTS.\n";
        echo "\n";
        echo "SOLUTION:\n";
        echo "1. Go to Cloudflare R2 Dashboard\n";
        echo "2. Open your bucket → Settings\n";
        echo "3. Enable 'Public access'\n";
        echo "4. Copy the public bucket URL (format: https://pub-{hash}.r2.dev)\n";
        echo "5. Update your .env:\n";
        echo "\n";
        echo "   AWS_URL=https://pub-your-hash-here.r2.dev\n";
        echo "\n";
        echo "6. Clear caches:\n";
        echo "   php artisan config:clear\n";
        echo "   php artisan config:cache\n";
        echo "\n";
        echo "See CLOUDFLARE_R2_DEPLOYMENT_GUIDE.md for detailed instructions.\n";
    }
    
    echo "\n";
    exit(1);
} else {
    echo "✓ CONFIGURATION LOOKS GOOD\n";
    echo "\n";
    echo "Your R2 configuration appears to be correct.\n";
    echo "\n";
    echo "If you're still experiencing issues:\n";
    echo "1. Verify public access is enabled on your R2 bucket\n";
    echo "2. Check CORS configuration\n";
    echo "3. Test URL in browser: " . (isset($generatedUrl) ? $generatedUrl : 'N/A') . "\n";
    echo "\n";
    exit(0);
}
