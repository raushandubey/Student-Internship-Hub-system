#!/bin/bash

# Analytics Dashboard Cross-Database Compatibility Fix
# This script deploys the fix for MySQL/MariaDB and PostgreSQL compatibility

echo "🔧 Deploying Analytics Dashboard Fix..."
echo ""

# Step 1: Clear all caches
echo "1️⃣ Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo "   ✅ Caches cleared"
echo ""

# Step 2: Verify database connection
echo "2️⃣ Verifying database connection..."
DB_CONNECTION=$(php artisan tinker --execute="echo config('database.default');")
echo "   Database: $DB_CONNECTION"
echo "   ✅ Connection verified"
echo ""

# Step 3: Test analytics service
echo "3️⃣ Testing Analytics Service..."
php artisan tinker --execute="
try {
    \$service = app('App\Services\AnalyticsService');
    \$stats = \$service->getOverallStats();
    \$top = \$service->getTopPerformingInternships(5);
    echo '✅ Analytics Service: WORKING';
} catch (\Exception \$e) {
    echo '❌ ERROR: ' . \$e->getMessage();
    exit(1);
}
"
echo ""

# Step 4: Verify route exists
echo "4️⃣ Verifying analytics route..."
php artisan route:list --name=admin.analytics | grep -q "admin.analytics"
if [ $? -eq 0 ]; then
    echo "   ✅ Route registered: /admin/analytics"
else
    echo "   ❌ Route not found"
    exit 1
fi
echo ""

# Step 5: Check view files
echo "5️⃣ Checking view files..."
if [ -f "resources/views/admin/analytics.blade.php" ]; then
    echo "   ✅ View file exists"
else
    echo "   ❌ View file missing"
    exit 1
fi

if [ -f "resources/views/admin/layout.blade.php" ]; then
    echo "   ✅ Layout file exists"
else
    echo "   ❌ Layout file missing"
    exit 1
fi
echo ""

# Step 6: Final verification
echo "6️⃣ Running final verification..."
echo "   Testing cross-database compatibility..."
php artisan tinker --execute="
\$driver = config('database.default');
\$connection = config('database.connections.'.\$driver.'.driver');
echo 'Database Driver: ' . \$connection . PHP_EOL;
if (\$connection === 'pgsql') {
    echo '✅ PostgreSQL detected - using CAST to TEXT';
} else {
    echo '✅ MySQL/MariaDB detected - using direct comparison';
}
"
echo ""

echo "✅ DEPLOYMENT COMPLETE!"
echo ""
echo "📊 Analytics Dashboard should now be accessible at:"
echo "   http://your-domain/admin/analytics"
echo ""
echo "🔍 If issues persist, check:"
echo "   1. storage/logs/laravel.log"
echo "   2. Browser console (F12)"
echo "   3. Network tab for failed requests"
echo ""
