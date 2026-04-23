@echo off
REM Resume Storage Fix Script for Windows
REM Fixes 404 errors for resume files in production

echo.
echo 🔧 Fixing Resume Storage Issues...
echo.

REM Step 1: Create storage symlink
echo 1️⃣ Creating storage symlink...
call php artisan storage:link
if %errorlevel% equ 0 (
    echo    ✅ Symlink created: public\storage -^> storage\app\public
) else (
    echo    ⚠️  Symlink may already exist or failed to create
)
echo.

REM Step 2: Verify storage directories exist
echo 2️⃣ Verifying storage directories...
if not exist "storage\app\public\resumes" mkdir "storage\app\public\resumes"
echo    ✅ Directory created: storage\app\public\resumes
echo.

REM Step 3: Clear all caches
echo 3️⃣ Clearing caches...
call php artisan cache:clear
call php artisan config:clear
call php artisan route:clear
call php artisan view:clear
echo    ✅ Caches cleared
echo.

REM Step 4: Verify symlink
echo 4️⃣ Verifying symlink...
if exist "public\storage" (
    echo    ✅ Symlink exists: public\storage
) else (
    echo    ❌ Symlink does NOT exist!
    echo    Run manually: php artisan storage:link
)
echo.

REM Step 5: Check for existing resumes
echo 5️⃣ Checking for existing resume files...
dir /b /s "storage\app\public\resumes\*.pdf" 2>nul | find /c /v "" > temp_count.txt
set /p RESUME_COUNT=<temp_count.txt
del temp_count.txt
echo    Found %RESUME_COUNT% resume file(s)
echo.

REM Step 6: Test file serving
echo 6️⃣ Testing resume routes...
call php artisan route:list --name=resume
echo.

echo ✅ STORAGE FIX COMPLETE!
echo.
echo 📋 Next Steps:
echo    1. Test resume upload: /profile/edit
echo    2. Verify file appears in: storage\app\public\resumes\
echo    3. Check symlink: dir public\storage
echo    4. Test resume URL in browser
echo.
echo ⚠️  PRODUCTION WARNING:
echo    If using ephemeral storage (Laravel Cloud, Heroku, etc.):
echo    - Files will be deleted on each deploy
echo    - Consider using S3 or external storage
echo    - Run this script after every deployment
echo.
pause
