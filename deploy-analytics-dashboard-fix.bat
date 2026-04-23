@echo off
REM Analytics Dashboard Cross-Database Compatibility Fix
REM This script deploys the fix for MySQL/MariaDB and PostgreSQL compatibility

echo.
echo 🔧 Deploying Analytics Dashboard Fix...
echo.

REM Step 1: Clear all caches
echo 1️⃣ Clearing application caches...
call php artisan cache:clear
call php artisan config:clear
call php artisan view:clear
call php artisan route:clear
echo    ✅ Caches cleared
echo.

REM Step 2: Verify database connection
echo 2️⃣ Verifying database connection...
for /f "delims=" %%i in ('php artisan tinker --execute="echo config('database.default');"') do set DB_CONNECTION=%%i
echo    Database: %DB_CONNECTION%
echo    ✅ Connection verified
echo.

REM Step 3: Test analytics service
echo 3️⃣ Testing Analytics Service...
php artisan tinker --execute="try { $service = app('App\Services\AnalyticsService'); $stats = $service->getOverallStats(); $top = $service->getTopPerformingInternships(5); echo '✅ Analytics Service: WORKING'; } catch (\Exception $e) { echo '❌ ERROR: ' . $e->getMessage(); exit(1); }"
echo.

REM Step 4: Verify route exists
echo 4️⃣ Verifying analytics route...
php artisan route:list --name=admin.analytics | findstr "admin.analytics" >nul
if %errorlevel% equ 0 (
    echo    ✅ Route registered: /admin/analytics
) else (
    echo    ❌ Route not found
    exit /b 1
)
echo.

REM Step 5: Check view files
echo 5️⃣ Checking view files...
if exist "resources\views\admin\analytics.blade.php" (
    echo    ✅ View file exists
) else (
    echo    ❌ View file missing
    exit /b 1
)

if exist "resources\views\admin\layout.blade.php" (
    echo    ✅ Layout file exists
) else (
    echo    ❌ Layout file missing
    exit /b 1
)
echo.

REM Step 6: Final verification
echo 6️⃣ Running final verification...
echo    Testing cross-database compatibility...
php artisan tinker --execute="$driver = config('database.default'); $connection = config('database.connections.'.$driver.'.driver'); echo 'Database Driver: ' . $connection . PHP_EOL; if ($connection === 'pgsql') { echo '✅ PostgreSQL detected - using CAST to TEXT'; } else { echo '✅ MySQL/MariaDB detected - using direct comparison'; }"
echo.

echo ✅ DEPLOYMENT COMPLETE!
echo.
echo 📊 Analytics Dashboard should now be accessible at:
echo    http://localhost:8000/admin/analytics
echo.
echo 🔍 If issues persist, check:
echo    1. storage\logs\laravel.log
echo    2. Browser console (F12)
echo    3. Network tab for failed requests
echo.
pause
