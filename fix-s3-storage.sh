#!/bin/bash

# Laravel Cloud S3 Storage Fix Script
# Installs AWS S3 package and configures storage

echo "🔧 Installing AWS S3 Storage Support..."
echo ""

# Step 1: Install package
echo "1️⃣ Installing league/flysystem-aws-s3-v3..."
composer require league/flysystem-aws-s3-v3 "^3.0" --with-all-dependencies --no-interaction
if [ $? -eq 0 ]; then
    echo "   ✅ Package installed successfully"
else
    echo "   ❌ Package installation failed"
    exit 1
fi
echo ""

# Step 2: Clear caches
echo "2️⃣ Clearing caches..."
php artisan config:clear
php artisan cache:clear
composer dump-autoload
echo "   ✅ Caches cleared"
echo ""

# Step 3: Verify installation
echo "3️⃣ Verifying installation..."
if composer show | grep -q "league/flysystem-aws-s3-v3"; then
    echo "   ✅ Package verified:"
    composer show | grep flysystem-aws
else
    echo "   ❌ Package not found in composer show"
fi
echo ""

# Step 4: Check configuration files
echo "4️⃣ Checking configuration files..."
if [ -f ".cloud.yml" ]; then
    if grep -q "FILESYSTEM_DISK: s3" .cloud.yml; then
        echo "   ✅ .cloud.yml configured for S3"
    else
        echo "   ⚠️  .cloud.yml needs FILESYSTEM_DISK: s3"
    fi
else
    echo "   ⚠️  .cloud.yml not found"
fi

if [ -f ".env.example" ]; then
    if grep -q "FILESYSTEM_DISK=s3" .env.example; then
        echo "   ✅ .env.example configured for S3"
    else
        echo "   ⚠️  .env.example needs FILESYSTEM_DISK=s3"
    fi
else
    echo "   ⚠️  .env.example not found"
fi
echo ""

# Step 5: Git status
echo "5️⃣ Git status..."
git status --short
echo ""

echo "✅ S3 PACKAGE INSTALLED!"
echo ""
echo "📋 Files to commit:"
echo "   - composer.json (updated dependencies)"
echo "   - composer.lock (locked versions)"
echo "   - .env.example (S3 configuration)"
echo "   - .cloud.yml (Laravel Cloud config)"
echo "   - app/Models/Profile.php (S3 support)"
echo "   - app/Http/Controllers/ProfileController.php (S3 support)"
echo ""
echo "📤 Next Steps:"
echo "   1. Review changes: git diff"
echo "   2. Commit changes:"
echo "      git add composer.json composer.lock .env.example .cloud.yml"
echo "      git add app/Models/Profile.php app/Http/Controllers/ProfileController.php"
echo "      git commit -m 'Add: AWS S3 storage support'"
echo "   3. Push to deploy: git push"
echo "   4. Verify Laravel Cloud auto-populates AWS credentials"
echo "   5. Test file upload in production"
echo ""
echo "🎯 Expected Result:"
echo "   - Deployment succeeds without S3 errors"
echo "   - Files upload to S3 bucket"
echo "   - Files persist across deployments"
echo ""
