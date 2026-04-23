@echo off
REM Laravel Octane Deployment Fix Script for Windows
REM Fixes "laravel/octane package not found" error on Laravel Cloud

echo.
echo 🔧 Fixing Laravel Octane Deployment Error...
echo.

REM Step 1: Verify Octane is not in composer.json
echo 1️⃣ Checking composer.json...
findstr /C:"laravel/octane" composer.json >nul 2>&1
if %errorlevel% equ 0 (
    echo    ⚠️  Octane found in composer.json - removing...
    call composer remove laravel/octane --no-interaction
) else (
    echo    ✅ Octane not in composer.json (good)
)
echo.

REM Step 2: Remove Octane config if exists
echo 2️⃣ Removing Octane configuration...
if exist "config\octane.php" (
    del "config\octane.php"
    echo    ✅ Removed config\octane.php
) else (
    echo    ✅ No config\octane.php found (good)
)
echo.

REM Step 3: Clear all caches
echo 3️⃣ Clearing caches...
call php artisan config:clear
call php artisan route:clear
call php artisan view:clear
call php artisan cache:clear
echo    ✅ Caches cleared
echo.

REM Step 4: Verify .cloud.yml exists
echo 4️⃣ Verifying Laravel Cloud configuration...
if exist ".cloud.yml" (
    echo    ✅ .cloud.yml exists
) else (
    echo    ⚠️  .cloud.yml not found - creating...
    (
        echo # Laravel Cloud Configuration
        echo octane: false
        echo php: 8.2
    ) > .cloud.yml
    echo    ✅ Created .cloud.yml with Octane disabled
)
echo.

REM Step 5: Update composer.lock
echo 5️⃣ Updating composer.lock...
call composer update --lock --no-interaction
echo    ✅ composer.lock updated
echo.

REM Step 6: Verify environment variables
echo 6️⃣ Checking environment variables...
if exist ".env" (
    findstr /C:"OCTANE_ENABLED" .env >nul 2>&1
    if %errorlevel% neq 0 (
        echo    ⚠️  Adding OCTANE_ENABLED=false to .env
        echo OCTANE_ENABLED=false >> .env
    ) else (
        echo    ✅ OCTANE_ENABLED found in .env
    )
) else (
    echo    ⚠️  .env not found (will be created in production)
)
echo.

REM Step 7: Git status
echo 7️⃣ Git status...
git status --short
echo.
echo 📋 Files to commit:
echo    - .cloud.yml (Laravel Cloud config)
echo    - cloud.yaml (backup config)
echo    - .env.example (updated)
echo    - composer.lock (if changed)
echo.

echo ✅ FIX COMPLETE!
echo.
echo 📤 Next Steps:
echo    1. Review changes: git diff
echo    2. Commit changes: git add . ^&^& git commit -m "Fix: Disable Octane for Laravel Cloud"
echo    3. Push to deploy: git push
echo.
echo 🎯 Expected Result:
echo    Deployment should succeed without Octane errors
echo.
pause
