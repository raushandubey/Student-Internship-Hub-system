<?php
/**
 * Chatbot Asset Verification Script
 * 
 * Run this to verify chatbot assets are correctly built and accessible
 * Usage: php verify-chatbot-assets.php
 */

echo "🔍 Chatbot Asset Verification\n";
echo str_repeat("=", 60) . "\n\n";

// Check if manifest exists
$manifestPath = __DIR__ . '/public/build/manifest.json';
echo "1. Checking Vite Manifest...\n";
if (!file_exists($manifestPath)) {
    echo "   ❌ MISSING: public/build/manifest.json\n";
    echo "   → Run: npm run build\n\n";
    exit(1);
}
echo "   ✅ Found: public/build/manifest.json\n\n";

// Read manifest
$manifest = json_decode(file_get_contents($manifestPath), true);
if (!$manifest) {
    echo "   ❌ ERROR: Invalid JSON in manifest\n\n";
    exit(1);
}

// Check chatbot JS entry
echo "2. Checking Chatbot JavaScript...\n";
if (!isset($manifest['public/js/chatbot.js'])) {
    echo "   ❌ MISSING: public/js/chatbot.js entry in manifest\n";
    echo "   → Check vite.config.js input array\n\n";
    exit(1);
}

$jsEntry = $manifest['public/js/chatbot.js'];
$jsFile = $jsEntry['file'] ?? null;
if (!$jsFile) {
    echo "   ❌ ERROR: No 'file' property in chatbot.js entry\n\n";
    exit(1);
}

$jsPath = __DIR__ . '/public/build/' . $jsFile;
if (!file_exists($jsPath)) {
    echo "   ❌ MISSING: public/build/{$jsFile}\n";
    echo "   → Run: npm run build\n\n";
    exit(1);
}

$jsSize = filesize($jsPath);
echo "   ✅ Found: public/build/{$jsFile}\n";
echo "   📦 Size: " . number_format($jsSize) . " bytes\n";
echo "   🔗 URL: /build/{$jsFile}\n\n";

// Check chatbot CSS entry
echo "3. Checking Chatbot CSS...\n";
if (!isset($manifest['public/css/chatbot.css'])) {
    echo "   ❌ MISSING: public/css/chatbot.css entry in manifest\n";
    echo "   → Check vite.config.js input array\n\n";
    exit(1);
}

$cssEntry = $manifest['public/css/chatbot.css'];
$cssFile = $cssEntry['file'] ?? null;
if (!$cssFile) {
    echo "   ❌ ERROR: No 'file' property in chatbot.css entry\n\n";
    exit(1);
}

$cssPath = __DIR__ . '/public/build/' . $cssFile;
if (!file_exists($cssPath)) {
    echo "   ❌ MISSING: public/build/{$cssFile}\n";
    echo "   → Run: npm run build\n\n";
    exit(1);
}

$cssSize = filesize($cssPath);
echo "   ✅ Found: public/build/{$cssFile}\n";
echo "   📦 Size: " . number_format($cssSize) . " bytes\n";
echo "   🔗 URL: /build/{$cssFile}\n\n";

// Check source files
echo "4. Checking Source Files...\n";
$sourceJs = __DIR__ . '/public/js/chatbot.js';
$sourceCss = __DIR__ . '/public/css/chatbot.css';

if (!file_exists($sourceJs)) {
    echo "   ❌ MISSING: public/js/chatbot.js\n";
} else {
    echo "   ✅ Found: public/js/chatbot.js (" . number_format(filesize($sourceJs)) . " bytes)\n";
}

if (!file_exists($sourceCss)) {
    echo "   ❌ MISSING: public/css/chatbot.css\n";
} else {
    echo "   ✅ Found: public/css/chatbot.css (" . number_format(filesize($sourceCss)) . " bytes)\n";
}
echo "\n";

// Check Blade component
echo "5. Checking Blade Component...\n";
$bladePath = __DIR__ . '/resources/views/components/chatbot.blade.php';
if (!file_exists($bladePath)) {
    echo "   ❌ MISSING: resources/views/components/chatbot.blade.php\n\n";
    exit(1);
}

$bladeContent = file_get_contents($bladePath);
if (strpos($bladeContent, '@vite') !== false) {
    echo "   ✅ Using @vite() directive (CORRECT)\n";
} elseif (strpos($bladeContent, "asset('build/js/chatbot.min.js')") !== false) {
    echo "   ⚠️  Using hardcoded asset paths (NEEDS FIX)\n";
    echo "   → Update to use @vite(['public/css/chatbot.css', 'public/js/chatbot.js'])\n";
} else {
    echo "   ⚠️  Unknown asset loading method\n";
}
echo "\n";

// Summary
echo str_repeat("=", 60) . "\n";
echo "✅ VERIFICATION COMPLETE\n\n";

echo "📋 Manifest Entries:\n";
echo "   JS:  public/js/chatbot.js → {$jsFile}\n";
echo "   CSS: public/css/chatbot.css → {$cssFile}\n\n";

echo "🚀 Next Steps:\n";
echo "   1. Commit changes: git add -A && git commit -m 'Fix chatbot assets'\n";
echo "   2. Push to repository: git push\n";
echo "   3. Deploy to Laravel Cloud\n";
echo "   4. Test in production browser console (should see no 404 errors)\n\n";

echo "🧪 Local Testing:\n";
echo "   php artisan serve\n";
echo "   Visit: http://localhost:8000\n";
echo "   Open browser console and check for chatbot asset loading\n\n";
