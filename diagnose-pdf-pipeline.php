<?php

/**
 * PRODUCTION PDF PIPELINE DIAGNOSTIC TOOL
 * 
 * This script performs a complete audit of the PDF upload and parsing pipeline
 * to identify the exact failure point in production environments.
 * 
 * Run: php diagnose-pdf-pipeline.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║  PRODUCTION PDF PIPELINE DIAGNOSTIC TOOL                             ║\n";
echo "║  Tracing: Upload → Storage → Parser → Extraction                     ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ============================================================================
// STEP 1: ENVIRONMENT DETECTION
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 1: ENVIRONMENT DETECTION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$env = [
    'APP_ENV' => env('APP_ENV'),
    'APP_DEBUG' => env('APP_DEBUG') ? 'true' : 'false',
    'FILESYSTEM_DISK' => config('filesystems.default'),
    'PHP_VERSION' => PHP_VERSION,
    'OS' => PHP_OS,
    'SAPI' => php_sapi_name(),
];

foreach ($env as $key => $value) {
    echo sprintf("%-20s: %s\n", $key, $value);
}

// ============================================================================
// STEP 2: STORAGE CONFIGURATION AUDIT
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 2: STORAGE CONFIGURATION AUDIT\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$disk = config('filesystems.default');
$diskConfig = config("filesystems.disks.{$disk}");

echo "Default Disk: {$disk}\n\n";

if ($disk === 's3' || $disk === 'r2') {
    echo "Cloud Storage Configuration:\n";
    echo sprintf("  Driver:       %s\n", $diskConfig['driver'] ?? 'NOT SET');
    echo sprintf("  Region:       %s\n", $diskConfig['region'] ?? 'NOT SET');
    echo sprintf("  Bucket:       %s\n", $diskConfig['bucket'] ?? 'NOT SET');
    echo sprintf("  URL:          %s\n", $diskConfig['url'] ?? 'NOT SET');
    echo sprintf("  Endpoint:     %s\n", $diskConfig['endpoint'] ?? 'NOT SET');
    echo sprintf("  Path Style:   %s\n", ($diskConfig['use_path_style_endpoint'] ?? false) ? 'true' : 'false');
    
    // Check credentials
    $hasKey = !empty($diskConfig['key']);
    $hasSecret = !empty($diskConfig['secret']);
    echo sprintf("  Credentials:  %s\n", ($hasKey && $hasSecret) ? '✓ CONFIGURED' : '✗ MISSING');
    
} else {
    echo "Local Storage Configuration:\n";
    echo sprintf("  Root:         %s\n", $diskConfig['root'] ?? 'NOT SET');
    echo sprintf("  Visibility:   %s\n", $diskConfig['visibility'] ?? 'NOT SET');
}

// ============================================================================
// STEP 3: DIRECTORY PERMISSIONS CHECK
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 3: DIRECTORY PERMISSIONS CHECK\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$directories = [
    'storage/app' => storage_path('app'),
    'storage/app/public' => storage_path('app/public'),
    'storage/app/private' => storage_path('app/private'),
    'storage/framework' => storage_path('framework'),
    'storage/framework/cache' => storage_path('framework/cache'),
    'storage/logs' => storage_path('logs'),
    'bootstrap/cache' => base_path('bootstrap/cache'),
];

foreach ($directories as $name => $path) {
    $exists = file_exists($path);
    $writable = $exists && is_writable($path);
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    
    $status = $exists ? ($writable ? '✓ WRITABLE' : '✗ READ-ONLY') : '✗ MISSING';
    echo sprintf("%-30s: %s (perms: %s)\n", $name, $status, $perms);
}

// ============================================================================
// STEP 4: PDF PARSER DEPENDENCIES CHECK
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 4: PDF PARSER DEPENDENCIES CHECK\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check smalot/pdfparser
$pdfParserInstalled = class_exists('Smalot\\PdfParser\\Parser');
echo sprintf("%-30s: %s\n", "smalot/pdfparser", $pdfParserInstalled ? '✓ INSTALLED' : '✗ MISSING');

// Check PHP extensions
$extensions = ['mbstring', 'zlib', 'gd', 'fileinfo'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo sprintf("%-30s: %s\n", "PHP Extension: {$ext}", $loaded ? '✓ LOADED' : '✗ MISSING');
}

// Check system commands
$commands = ['pdftotext', 'gs', 'convert'];
foreach ($commands as $cmd) {
    $available = false;
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec("where {$cmd} 2>nul", $output, $returnVar);
        $available = $returnVar === 0;
    } else {
        exec("which {$cmd} 2>/dev/null", $output, $returnVar);
        $available = $returnVar === 0;
    }
    echo sprintf("%-30s: %s\n", "System Command: {$cmd}", $available ? '✓ AVAILABLE' : '✗ NOT FOUND');
}

// ============================================================================
// STEP 5: TEST FILE UPLOAD SIMULATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 5: TEST FILE UPLOAD SIMULATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Create a minimal test PDF
$testPdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n4 0 obj\n<< /Length 44 >>\nstream\nBT\n/F1 12 Tf\n100 700 Td\n(Test Resume) Tj\nET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\n0000000317 00000 n\ntrailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n410\n%%EOF";

$testFilename = 'diagnostic_test_' . time() . '.pdf';

try {
    // Test storage write
    echo "Testing storage write...\n";
    $stored = Storage::disk($disk)->put("test/{$testFilename}", $testPdfContent);
    
    if ($stored) {
        echo "  ✓ File stored successfully\n";
        
        // Test storage read
        echo "Testing storage read...\n";
        $content = Storage::disk($disk)->get("test/{$testFilename}");
        echo sprintf("  ✓ File read successfully (%d bytes)\n", strlen($content));
        
        // Test file exists
        echo "Testing file exists check...\n";
        $exists = Storage::disk($disk)->exists("test/{$testFilename}");
        echo sprintf("  %s File exists check\n", $exists ? '✓' : '✗');
        
        // Test URL generation
        echo "Testing URL generation...\n";
        $url = Storage::disk($disk)->url("test/{$testFilename}");
        echo sprintf("  Generated URL: %s\n", $url);
        
        // Cleanup
        echo "Cleaning up test file...\n";
        Storage::disk($disk)->delete("test/{$testFilename}");
        echo "  ✓ Test file deleted\n";
        
    } else {
        echo "  ✗ FAILED to store file\n";
    }
    
} catch (\Exception $e) {
    echo "  ✗ ERROR: " . $e->getMessage() . "\n";
    echo "  Stack trace:\n";
    echo "  " . str_replace("\n", "\n  ", $e->getTraceAsString()) . "\n";
}

// ============================================================================
// STEP 6: TEST PDF PARSING
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 6: TEST PDF PARSING\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($pdfParserInstalled) {
    try {
        echo "Testing PDF parser with content...\n";
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseContent($testPdfContent);
        $text = $pdf->getText();
        echo sprintf("  ✓ PDF parsed successfully\n");
        echo sprintf("  Extracted text: '%s'\n", trim($text));
        
    } catch (\Exception $e) {
        echo "  ✗ PDF parsing FAILED: " . $e->getMessage() . "\n";
    }
} else {
    echo "  ✗ SKIPPED: smalot/pdfparser not installed\n";
}

// ============================================================================
// STEP 7: TEST ACTUAL RESUME FILE
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 7: TEST ACTUAL RESUME FILE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $profile = \App\Models\Profile::whereNotNull('resume_path')->first();
    
    if ($profile) {
        echo sprintf("Testing resume for user: %s\n", $profile->user->name ?? 'Unknown');
        echo sprintf("Resume path: %s\n", $profile->resume_path);
        
        // Test file exists
        $exists = Storage::disk($disk)->exists($profile->resume_path);
        echo sprintf("  File exists: %s\n", $exists ? '✓ YES' : '✗ NO');
        
        if ($exists) {
            // Test file size
            $size = Storage::disk($disk)->size($profile->resume_path);
            echo sprintf("  File size: %d bytes (%.2f KB)\n", $size, $size / 1024);
            
            // Test URL generation
            $url = $profile->getResumeUrl();
            echo sprintf("  Generated URL: %s\n", $url);
            
            // Test content retrieval
            echo "  Testing content retrieval...\n";
            $content = Storage::disk($disk)->get($profile->resume_path);
            echo sprintf("    ✓ Content retrieved (%d bytes)\n", strlen($content));
            
            // Test PDF parsing
            if ($pdfParserInstalled) {
                echo "  Testing PDF parsing...\n";
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseContent($content);
                    $text = $pdf->getText();
                    $textLength = strlen(trim($text));
                    echo sprintf("    ✓ PDF parsed successfully (%d chars extracted)\n", $textLength);
                    
                    if ($textLength < 50) {
                        echo "    ⚠ WARNING: Very little text extracted (< 50 chars)\n";
                        echo "    This may indicate:\n";
                        echo "      - Scanned PDF (image-based, needs OCR)\n";
                        echo "      - Encrypted PDF\n";
                        echo "      - Corrupted PDF\n";
                    }
                    
                } catch (\Exception $e) {
                    echo "    ✗ PDF parsing FAILED: " . $e->getMessage() . "\n";
                }
            }
        }
        
    } else {
        echo "  ℹ No resume files found in database\n";
    }
    
} catch (\Exception $e) {
    echo "  ✗ ERROR: " . $e->getMessage() . "\n";
}

// ============================================================================
// STEP 8: RECOMMENDATIONS
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 8: RECOMMENDATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$issues = [];

// Check storage configuration
if ($disk === 's3' || $disk === 'r2') {
    if (empty($diskConfig['key']) || empty($diskConfig['secret'])) {
        $issues[] = "✗ CRITICAL: Cloud storage credentials not configured";
    }
    if (empty($diskConfig['url'])) {
        $issues[] = "✗ CRITICAL: AWS_URL not configured (required for public file access)";
    }
    if (($diskConfig['region'] ?? '') !== 'auto' && $disk === 'r2') {
        $issues[] = "⚠ WARNING: R2 region should be 'auto', not '{$diskConfig['region']}'";
    }
}

// Check directories
foreach ($directories as $name => $path) {
    if (!file_exists($path)) {
        $issues[] = "✗ CRITICAL: Directory missing: {$name}";
    } elseif (!is_writable($path)) {
        $issues[] = "✗ CRITICAL: Directory not writable: {$name}";
    }
}

// Check PDF parser
if (!$pdfParserInstalled) {
    $issues[] = "✗ CRITICAL: smalot/pdfparser not installed (run: composer require smalot/pdfparser)";
}

// Check PHP extensions
foreach ($extensions as $ext) {
    if (!extension_loaded($ext)) {
        $issues[] = "⚠ WARNING: PHP extension '{$ext}' not loaded";
    }
}

if (empty($issues)) {
    echo "✓ No critical issues detected!\n";
    echo "\nYour PDF pipeline appears to be configured correctly.\n";
    echo "If you're still experiencing issues, check:\n";
    echo "  1. Production logs: storage/logs/laravel.log\n";
    echo "  2. Browser console for frontend errors\n";
    echo "  3. Network tab for failed requests\n";
} else {
    echo "Issues detected:\n\n";
    foreach ($issues as $issue) {
        echo "  {$issue}\n";
    }
    
    echo "\nRecommended actions:\n\n";
    
    if ($disk === 's3' || $disk === 'r2') {
        echo "  1. Verify cloud storage configuration:\n";
        echo "     - Check AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY\n";
        echo "     - Verify AWS_URL points to R2 public bucket URL (not Laravel domain)\n";
        echo "     - Confirm AWS_ENDPOINT is correct\n";
        echo "     - Ensure AWS_DEFAULT_REGION='auto' for R2\n\n";
    }
    
    echo "  2. Fix directory permissions:\n";
    echo "     chmod -R 775 storage bootstrap/cache\n";
    echo "     chown -R www-data:www-data storage bootstrap/cache\n\n";
    
    echo "  3. Install missing dependencies:\n";
    echo "     composer require smalot/pdfparser\n\n";
    
    echo "  4. Clear all caches:\n";
    echo "     php artisan config:clear\n";
    echo "     php artisan cache:clear\n";
    echo "     php artisan config:cache\n\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC COMPLETE                                                 ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
