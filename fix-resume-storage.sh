#!/bin/bash

# Resume Storage Fix Script
# Fixes 404 errors for resume files in production

echo "🔧 Fixing Resume Storage Issues..."
echo ""

# Step 1: Create storage symlink
echo "1️⃣ Creating storage symlink..."
php artisan storage:link
if [ $? -eq 0 ]; then
    echo "   ✅ Symlink created: public/storage → storage/app/public"
else
    echo "   ⚠️  Symlink may already exist or failed to create"
fi
echo ""

# Step 2: Verify storage directories exist
echo "2️⃣ Verifying storage directories..."
mkdir -p storage/app/public/resumes
chmod -R 775 storage/app/public/resumes
echo "   ✅ Directory created: storage/app/public/resumes"
echo ""

# Step 3: Set proper permissions
echo "3️⃣ Setting storage permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
echo "   ✅ Permissions set"
echo ""

# Step 4: Clear all caches
echo "4️⃣ Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "   ✅ Caches cleared"
echo ""

# Step 5: Verify symlink
echo "5️⃣ Verifying symlink..."
if [ -L "public/storage" ]; then
    TARGET=$(readlink public/storage)
    echo "   ✅ Symlink exists: public/storage → $TARGET"
else
    echo "   ❌ Symlink does NOT exist!"
    echo "   Run manually: php artisan storage:link"
fi
echo ""

# Step 6: Check for existing resumes
echo "6️⃣ Checking for existing resume files..."
RESUME_COUNT=$(find storage/app/public/resumes -type f 2>/dev/null | wc -l)
echo "   Found $RESUME_COUNT resume file(s)"
echo ""

# Step 7: Test file serving
echo "7️⃣ Testing resume routes..."
php artisan route:list --name=resume | grep -E "resume\.(serve|download|check)"
echo ""

echo "✅ STORAGE FIX COMPLETE!"
echo ""
echo "📋 Next Steps:"
echo "   1. Test resume upload: /profile/edit"
echo "   2. Verify file appears in: storage/app/public/resumes/"
echo "   3. Check symlink: ls -la public/storage"
echo "   4. Test resume URL in browser"
echo ""
echo "⚠️  PRODUCTION WARNING:"
echo "   If using ephemeral storage (Laravel Cloud, Heroku, etc.):"
echo "   - Files will be deleted on each deploy"
echo "   - Consider using S3 or external storage"
echo "   - Run this script after every deployment"
echo ""
