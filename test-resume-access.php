<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== Testing Resume Access ===\n\n";

// Get a profile with resume
$profile = App\Models\Profile::whereNotNull('resume_path')->first();

if (!$profile) {
    echo "No profiles with resumes found.\n";
    exit(1);
}

echo "Profile ID: {$profile->id}\n";
echo "User ID: {$profile->user_id}\n";
echo "Resume Path: {$profile->resume_path}\n\n";

// Test 1: Check if file exists
$normalizedPath = ltrim($profile->resume_path, '/');
$fileExists = Storage::disk('public')->exists($normalizedPath);
echo "1. File exists in storage: " . ($fileExists ? 'YES' : 'NO') . "\n";

// Test 2: Get URL
$url = $profile->getResumeUrl();
echo "2. Generated URL: " . ($url ?? 'NULL') . "\n";

// Test 3: Check physical file
$fullPath = storage_path('app/public/' . $normalizedPath);
$physicalExists = file_exists($fullPath);
echo "3. Physical file exists: " . ($physicalExists ? 'YES' : 'NO') . "\n";
echo "   Path: {$fullPath}\n";

// Test 4: Check symlink
$symlinkPath = public_path('storage/' . $normalizedPath);
$symlinkExists = file_exists($symlinkPath);
echo "4. Symlink accessible: " . ($symlinkExists ? 'YES' : 'NO') . "\n";
echo "   Path: {$symlinkPath}\n";

// Test 5: Check if URL is accessible (simulate HTTP request)
if ($url) {
    echo "\n5. Testing URL accessibility:\n";
    echo "   URL: {$url}\n";
    
    // Parse URL to get the path
    $parsedUrl = parse_url($url);
    $path = $parsedUrl['path'] ?? '';
    
    // Check if the file is accessible via the public path
    $publicPath = public_path(ltrim($path, '/'));
    $accessible = file_exists($publicPath);
    echo "   Accessible via public path: " . ($accessible ? 'YES' : 'NO') . "\n";
    
    if ($accessible) {
        $fileSize = filesize($publicPath);
        echo "   File size: " . number_format($fileSize / 1024, 2) . " KB\n";
    }
}

echo "\n=== Test Complete ===\n";
