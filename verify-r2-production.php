<?php

/**
 * Cloudflare R2 Production Configuration Verification Script
 * 
 * Purpose: Verify R2 configuration is correct and diagnose ERR_TOO_MANY_REDIRECTS
 * Usage: php verify-r2-production.php
 * 
 * This script checks:
 * 1. Filesystem configuration
 * 2. R2-specific settings (region, path-style, URLs)
 * 3. URL generation
 * 4. File accessibility
 * 5. Common misconfigurations
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// ANSI color codes for terminal output
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
    'bold' => "\033[1m",
];

function colorize($text, $color, $bold = false) {
    global $colors;
    $output = $bold ? $colors['bold'] : '';
    $output .= $colors[$color] . $text . $colors['reset'];
    return $output;
}

function printHeader($text) {
    echo "\n";
    echo colorize("╔" . str_repeat("═", 78) . "╗", 'cyan', true) . "\n";
    echo colorize("║ " . str_pad($text, 77) . "║", 'cyan', true) . "\n";
    echo colorize("╚" . str_repeat("═", 78) . "╝", 'cyan', true) . "\n";
    echo "\n";
}

function printSuccess($text) {
    echo colorize("✓ ", 'green', true) . $text . "\n";
}

function printError($text) {
    echo colorize("✗ ", 'red', true) . $text . "\n";
}

function printWarning($text) {
    echo colorize("⚠ ", 'yellow', true) . $text . "\n";
}

function printInfo($text) {
    echo colorize("ℹ ", 'blue') . $text . "\n";
}

$errors = [];
$warnings = [];
$success = [];

printHeader("Cloudflare R2 Production Configuration Verification");

// ============================================================================
// TEST 1: Check Filesystem Disk Configuration
// ============================================================================
echo colorize("TEST 1: Filesystem Disk Configuration", 'cyan', true) . "\n";
echo str_repeat("-", 80) . "\n";

$disk = config('filesystems.default');
printInfo("Current FILESYSTEM_DISK: " . colorize($disk, 'yellow', true));

if ($disk === 's3') {
    printSuccess("FILESYSTEM_DISK is correctly set to 's3' for R2");
    $success[] = "Filesystem disk configured correctly";
} else {
    printError("FILESYSTEM_DISK is '{$disk}' but should be 's3' for R2");
    $errors[] = "FILESYSTEM_DISK must be 's3' for Cloudflare R2";
}

// ============================================================================
// TEST 2: Check R2-Specific Configuration
// ============================================================================
echo "\n" . colorize("TEST 2: R2-Specific Settings", 'cyan', true) . "\n";
echo str_repeat("-", 80) . "\n";

// Check region
$region = config('filesystems.disks.s3.region');
printInfo("AWS_DEFAULT_REGION: " . colorize($region, 'yellow', true));

if ($region === 'auto') {
    printSuccess("Region is correctly set to 'auto' for R2");
    $success[] = "Region configured correctly for R2";
} else {
    printError("Region is '{$region}' but should be 'auto' for R2");
    $errors[] = "AWS_DEFAULT_REGION must be 'auto' for Cloudflare R2 (not AWS regions)";
}

// Check path-style endpoint
$pathStyle = config('filesystems.disks.s3.use_path_style_endpoint');
printInfo("AWS_USE_PATH_STYLE_ENDPOINT: " . colorize($pathStyle ? 'true' : 'false', 'yellow', true));

if ($pathStyle === true) {
    printSuccess("Path-style endpoints correctly enabled for R2");
    $success[] = "Path-style endpoints configured correctly";
} else {
    printError("Path-style endpoints are disabled but must be enabled for R2");
    $errors[] = "AWS_USE_PATH_STYLE_ENDPOINT must be true for R2";
}

// ============================================================================
// TEST 3: Check AWS_URL (CRITICAL for ERR_TOO_MANY_REDIRECTS)
// ============================================================================
echo "\n" . colorize("TEST 3: AWS_URL Configuration (CRITICAL)", 'cyan', true) . "\n";
echo str_repeat("-", 80) . "\n";

$awsUrl = config('filesystems.disks.s3.url');
$appUrl = config('app.url');

printInfo("Current AWS_URL: " . colorize($awsUrl ?? 'NOT SET', 'yellow', true));
printInfo("Current APP_URL: " . colorize($appUrl, 'yellow', true));

if (empty($awsUrl)) {
    printError("AWS_URL is not set!");
    $errors[] = "AWS_URL is required for R2 public access";
} else {
    // Check if AWS_URL matches APP_URL (common mistake causing redirects)
    if ($awsUrl === $appUrl) {
        printError("CRITICAL ERROR: AWS_URL matches APP_URL!");
        printError("This causes ERR_TOO_MANY_REDIRECTS");
        printInfo("AWS_URL should be your R2 public bucket URL");
        printInfo("Format: https://pub-{hash}.r2.dev");
        $errors[] = "AWS_URL must be R2 public bucket URL, not Laravel domain";
    } elseif (strpos($awsUrl, '.r2.dev') !== false) {
        printSuccess("AWS_URL appears to be R2 public bucket URL");
        $success[] = "AWS_URL configured correctly";
        
        // Check if it's the public URL format
        if (strpos($awsUrl, 'pub-') !== false) {
            printSuccess("URL format is correct (pub-{hash}.r2.dev)");
            $success[] = "R2 public URL format correct";
        } else {
            printWarning("URL doesn't contain 'pub-' prefix");
            printInfo("Expected format: https://pub-{hash}.r2.dev");
            $warnings[] = "R2 URL format should be verified";
        }
    } elseif (strpos($awsUrl, '.r2.cloudflarestorage.com') !== false) {
        printError("CRITICAL ERROR: AWS_URL is set to R2 endpoint!");
        printError("This is the API endpoint, not the public URL");
        printInfo("AWS_URL should be: https://pub-{hash}.r2.dev");
        printInfo("AWS_ENDPOINT should be: https://{account}.r2.cloudflarestorage.com");
        $errors[] = "AWS_URL must be public bucket URL, not endpoint";
    } else {
        printWarning("AWS_URL doesn't appear to be R2 URL");
        printInfo("Expected format: https://pub-{hash}.r2.dev");
        $warnings[] = "AWS_URL format should be verified";
    }
}

// ============================================================================
// TEST 4: Check AWS_ENDPOINT
// ============================================================================
echo "\n" . colorize("TEST 4: AWS_ENDPOINT Configuration", 'cyan', true) . "\n";
echo str_repeat("-", 80) . "\n";

$endpoint = config('filesystems.disks.s3.endpoint');
printInfo("AWS_ENDPOINT: " . colorize($endpoint ?? 'NOT SET', 'yellow', true));

if (empty($endpoint)) {
    printError("AWS_ENDPOINT is not set");
    $errors[] = "AWS_ENDPOINT is required for R2 API operations";
} else {
    if (strpos($endpoint, '.r2.cloudflarestorage.com') !== false) {
        printSuccess("AWS_ENDPOINT is R2 endpoint");
        $success[] = "AWS_ENDPOINT configured correctly";
    } else {
        printError("AWS_ENDPOINT doesn't appear to be R2 endpoint");
        printInfo("Expected format: https://{account_id}.r2.cloudflarestorage.com");
        $errors[] = "AWS_ENDPOINT must be R2 endpoint";
    }
}

// ============================================================================
// TEST 5: Check Credentials
// ============================================================================
echo "\n" . colorize("TEST 5: R2 Credentials", 'cyan', true) . "\n";
echo str_repeat("-", 80) . "\n";

$accessKey = config('filesystems.disks.s3.key');
$secretKey = config('filesystems.disks.s3.secret');
$bucket = config('filesystems.disks.s3.bucket');

if (empty($accessKey)) {
    printError("AWS_ACCESS_KEY_ID is not set");
    $errors[] = "AWS_ACCESS_KEY_ID is required";
} else {
    printSuccess("AWS_ACCESS_KEY_ID is set");
    $success[] = "Access key configured";
}

if (empty($secretKey)) {
    printError("AWS_SECRET_ACCESS_KEY is not set");
    $errors[] = "AWS_SECRET_ACCESS_KEY is required";
} else {
    printSuccess("AWS_SECRET_ACCESS_KEY is set");
    $success[] = "Secret key configured";
}

if (empty($bucket)) {
    printError("AWS_BUCKET is not set");
    $errors[] = "AWS_BUCKET is required";
} else {
    printInfo("AWS_BUCKET: " . colorize($bucket, 'yellow', true));
    printSuccess("Bucket name configured");
    $success[] = "Bucket name configured";
}

// ============================================================================
// TEST 6: Test URL Generation
// ============================================================================
echo "\n" . colorize("TEST 6: URL Generation Test", 'cyan', true) . "\n";
echo str_repeat("-", 80) . "\n";

try {
    $testPath = 'resumes/test.pdf';
    $generatedUrl = Storage::disk('s3')->url($testPath);
    
    printInfo("Test path: " . colorize($testPath, 'yellow'));
    printInfo("Generated URL: " . colorize($generatedUrl, 'yellow', true));
    
    // Analyze generated URL
    if (strpos($generatedUrl, $appUrl) !== false) {
        printError("CRITICAL: Generated URL contains Laravel domain!");
        printError("This will cause ERR_TOO_MANY_REDIRECTS");
        printInfo("Fix: Set AWS_URL to R2 public bucket URL");
        $errors[] = "Generated URLs point to Laravel domain instead of R2";
    } elseif (strpos($generatedUrl, '.r2.dev') !== false) {
        printSuccess("Generated URL points to R2");
        $success[] = "URL generation working correctly";
    } else {
        printWarning("Generated URL format unexpected");
        $warnings[] = "URL generation should be verified";
    }
} catch (\Exception $e) {
    printError("URL generation failed");
    printInfo("Error: " . $e->getMessage());
    $errors[] = "URL generation error: " . $e->getMessage();
}

// ============================================================================
// TEST 7: Check Existing Resume Files
// ============================================================================
echo "\n" . colorize("TEST 7: Existing Resume Files", 'cyan', true) . "\n";
echo str_repeat("-", 80) . "\n";

try {
    $profile = App\Models\Profile::whereNotNull('resume_path')->first();
    
    if ($profile) {
        printInfo("Found profile with resume");
        printInfo("Profile ID: " . colorize($profile->id, 'yellow'));
        printInfo("Resume path: " . colorize($profile->resume_path, 'yellow'));
        
        $url = $profile->getResumeUrl();
        printInfo("Generated URL: " . colorize($url ?? 'NULL', 'yellow', true));
        
        if ($url) {
            if (strpos($url, $appUrl) !== false) {
                printError("CRITICAL: Resume URL points to Laravel domain!");
                printError("This causes ERR_TOO_MANY_REDIRECTS");
                $errors[] = "Resume URLs redirect to Laravel domain";
            } elseif (strpos($url, '.r2.dev') !== false) {
                printSuccess("Resume URL points to R2");
                $success[] = "Resume URLs working correctly";
            }
        } else {
            printWarning("Resume URL is NULL");
            $warnings[] = "Resume URL generation returned NULL";
        }
    } else {
        printWarning("No profiles with resumes found");
        $warnings[] = "No test data available";
    }
} catch (\Exception $e) {
    printError("Error checking profiles");
    printInfo("Error: " . $e->getMessage());
}

// ============================================================================
// SUMMARY
// ============================================================================
printHeader("Verification Summary");

if (count($errors) > 0) {
    echo colorize("✗ ERRORS FOUND: " . count($errors), 'red', true) . "\n";
    foreach ($errors as $error) {
        echo colorize("  • ", 'red') . $error . "\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo colorize("⚠ WARNINGS: " . count($warnings), 'yellow', true) . "\n";
    foreach ($warnings as $warning) {
        echo colorize("  • ", 'yellow') . $warning . "\n";
    }
    echo "\n";
}

if (count($success) > 0) {
    echo colorize("✓ SUCCESS: " . count($success), 'green', true) . "\n";
    foreach ($success as $item) {
        echo colorize("  • ", 'green') . $item . "\n";
    }
    echo "\n";
}

// ============================================================================
// RECOMMENDATIONS
// ============================================================================
printHeader("Recommendations");

if (count($errors) > 0) {
    echo colorize("CRITICAL ISSUES DETECTED", 'red', true) . "\n\n";
    
    // Check for the main issue
    $hasUrlIssue = false;
    foreach ($errors as $error) {
        if (strpos($error, 'AWS_URL') !== false) {
            $hasUrlIssue = true;
            break;
        }
    }
    
    if ($hasUrlIssue) {
        echo colorize("PRIMARY ISSUE: AWS_URL Configuration", 'red', true) . "\n\n";
        echo "Your AWS_URL is causing ERR_TOO_MANY_REDIRECTS.\n\n";
        echo colorize("SOLUTION:", 'green', true) . "\n";
        echo "1. Go to Cloudflare R2 Dashboard\n";
        echo "2. Open your bucket → Settings\n";
        echo "3. Enable 'Public access'\n";
        echo "4. Copy the public bucket URL (format: https://pub-{hash}.r2.dev)\n";
        echo "5. Update your .env:\n\n";
        echo colorize("   AWS_URL=https://pub-your-hash-here.r2.dev", 'yellow', true) . "\n\n";
        echo "6. Clear caches:\n";
        echo "   php artisan config:clear\n";
        echo "   php artisan config:cache\n";
        echo "   php artisan cache:clear\n\n";
        echo "See .env.r2-production-CORRECT for complete configuration.\n";
    }
    
    echo "\n";
    exit(1);
} else {
    echo colorize("✓ CONFIGURATION LOOKS GOOD", 'green', true) . "\n\n";
    echo "Your R2 configuration appears to be correct.\n\n";
    echo "If you're still experiencing issues:\n";
    echo "1. Verify public access is enabled on your R2 bucket\n";
    echo "2. Check CORS configuration\n";
    echo "3. Test URL in browser: " . (isset($generatedUrl) ? $generatedUrl : 'N/A') . "\n\n";
    exit(0);
}
