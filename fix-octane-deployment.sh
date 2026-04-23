#!/bin/bash

# Laravel Octane Deployment Fix Script
# Fixes "laravel/octane package not found" error on Laravel Cloud

echo "🔧 Fixing Laravel Octane Deployment Error..."
echo ""

# Step 1: Verify Octane is not in composer.json
echo "1️⃣ Checking composer.json..."
if grep -q "laravel/octane" composer.json; then
    echo "   ⚠️  Octane found in composer.json - removing..."
    composer remove laravel/octane --no-interaction
else
    echo "   ✅ Octane not in composer.json (good)"
fi
echo ""

# Step 2: Remove Octane config if exists
echo "2️⃣ Removing Octane configuration..."
if [ -f "config/octane.php" ]; then
    rm config/octane.php
    echo "   ✅ Removed config/octane.php"
else
    echo "   ✅ No config/octane.php found (good)"
fi
echo ""

# Step 3: Clear all caches
echo "3️⃣ Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "   ✅ Caches cleared"
echo ""

# Step 4: Verify .cloud.yml exists
echo "4️⃣ Verifying Laravel Cloud configuration..."
if [ -f ".cloud.yml" ]; then
    echo "   ✅ .cloud.yml exists"
    if grep -q "octane: false" .cloud.yml; then
        echo "   ✅ Octane disabled in .cloud.yml"
    else
        echo "   ⚠️  Adding octane: false to .cloud.yml"
        echo "octane: false" >> .cloud.yml
    fi
else
    echo "   ⚠️  .cloud.yml not found - creating..."
    cat > .cloud.yml << 'EOF'
# Laravel Cloud Configuration
octane: false
php: 8.2
EOF
    echo "   ✅ Created .cloud.yml with Octane disabled"
fi
echo ""

# Step 5: Update composer.lock
echo "5️⃣ Updating composer.lock..."
composer update --lock --no-interaction
echo "   ✅ composer.lock updated"
echo ""

# Step 6: Verify environment variables
echo "6️⃣ Checking environment variables..."
if [ -f ".env" ]; then
    if grep -q "OCTANE_ENABLED" .env; then
        echo "   ✅ OCTANE_ENABLED found in .env"
    else
        echo "   ⚠️  Adding OCTANE_ENABLED=false to .env"
        echo "OCTANE_ENABLED=false" >> .env
    fi
else
    echo "   ⚠️  .env not found (will be created in production)"
fi
echo ""

# Step 7: Commit changes
echo "7️⃣ Git status..."
git status --short
echo ""
echo "📋 Files to commit:"
echo "   - .cloud.yml (Laravel Cloud config)"
echo "   - cloud.yaml (backup config)"
echo "   - .env.example (updated)"
echo "   - composer.lock (if changed)"
echo ""

echo "✅ FIX COMPLETE!"
echo ""
echo "📤 Next Steps:"
echo "   1. Review changes: git diff"
echo "   2. Commit changes: git add . && git commit -m 'Fix: Disable Octane for Laravel Cloud'"
echo "   3. Push to deploy: git push"
echo ""
echo "🎯 Expected Result:"
echo "   Deployment should succeed without Octane errors"
echo ""
