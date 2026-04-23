@echo off
REM Laravel Cloud S3 Storage Fix Script for Windows
REM Installs AWS S3 package and configures storage

echo.
echo 🔧 Installing AWS S3 Storage Support...
echo.

REM Step 1: Install package
echo 1️⃣ Installing league/flysystem-aws-s3-v3...
call composer require league/flysystem-aws-s3-v3 "^3.0" --with-all-dependencies --no-interaction
if %errorlevel% equ 0 (
    echo    ✅ Package installed successfully
) else (
    echo    ❌ Package installation failed
    exit /b 1
)
echo.

REM Step 2: Clear caches
echo 2️⃣ Clearing caches...
call php artisan config:clear
call php artisan cache:clear
call composer dump-autoload
echo    ✅ Caches cleared
echo.

REM Step 3: Verify installation
echo 3️⃣ Verifying installation...
composer show | findstr /C:"league/flysystem-aws-s3-v3" >nul 2>&1
if %errorlevel% equ 0 (
    echo    ✅ Package verified:
    composer show | findstr "flysystem-aws"
) else (
    echo    ❌ Package not found in composer show
)
echo.

REM Step 4: Check configuration files
echo 4️⃣ Checking configuration files...
if exist ".cloud.yml" (
    findstr /C:"FILESYSTEM_DISK: s3" .cloud.yml >nul 2>&1
    if %errorlevel% equ 0 (
        echo    ✅ .cloud.yml configured for S3
    ) else (
        echo    ⚠️  .cloud.yml needs FILESYSTEM_DISK: s3
    )
) else (
    echo    ⚠️  .cloud.yml not found
)

if exist ".env.example" (
    findstr /C:"FILESYSTEM_DISK=s3" .env.example >nul 2>&1
    if %errorlevel% equ 0 (
        echo    ✅ .env.example configured for S3
    ) else (
        echo    ⚠️  .env.example needs FILESYSTEM_DISK=s3
    )
) else (
    echo    ⚠️  .env.example not found
)
echo.

REM Step 5: Git status
echo 5️⃣ Git status...
git status --short
echo.

echo ✅ S3 PACKAGE INSTALLED!
echo.
echo 📋 Files to commit:
echo    - composer.json (updated dependencies)
echo    - composer.lock (locked versions)
echo    - .env.example (S3 configuration)
echo    - .cloud.yml (Laravel Cloud config)
echo    - app\Models\Profile.php (S3 support)
echo    - app\Http\Controllers\ProfileController.php (S3 support)
echo.
echo 📤 Next Steps:
echo    1. Review changes: git diff
echo    2. Commit changes:
echo       git add composer.json composer.lock .env.example .cloud.yml
echo       git add app/Models/Profile.php app/Http/Controllers/ProfileController.php
echo       git commit -m "Add: AWS S3 storage support"
echo    3. Push to deploy: git push
echo    4. Verify Laravel Cloud auto-populates AWS credentials
echo    5. Test file upload in production
echo.
echo 🎯 Expected Result:
echo    - Deployment succeeds without S3 errors
echo    - Files upload to S3 bucket
echo    - Files persist across deployments
echo.
pause
