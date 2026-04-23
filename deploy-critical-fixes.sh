#!/bin/bash

# Critical Production Fixes Deployment Script
# Fixes: /my-applications 500 error and chatbot issues

set -e

echo "🚀 Deploying Critical Production Fixes"
echo "======================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Step 1: Backup
echo -e "${BLUE}Step 1: Creating backups...${NC}"
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp app/Http/Controllers/ApplicationController.php "$BACKUP_DIR/" 2>/dev/null || true
cp resources/views/student/application-tracker.blade.php "$BACKUP_DIR/" 2>/dev/null || true
cp public/js/chatbot.js "$BACKUP_DIR/" 2>/dev/null || true

echo -e "${GREEN}✅ Backups created in $BACKUP_DIR${NC}"
echo ""

# Step 2: Run migration
echo -e "${BLUE}Step 2: Running database migration...${NC}"
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migration completed successfully${NC}"
else
    echo -e "${RED}❌ Migration failed${NC}"
    exit 1
fi
echo ""

# Step 3: Clear caches
echo -e "${BLUE}Step 3: Clearing caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo -e "${GREEN}✅ Caches cleared${NC}"
echo ""

# Step 4: Rebuild caches
echo -e "${BLUE}Step 4: Rebuilding optimized caches...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Caches rebuilt${NC}"
echo ""

# Step 5: Check for orphaned applications
echo -e "${BLUE}Step 5: Checking for orphaned applications...${NC}"
ORPHANED=$(php artisan tinker --execute="echo App\Models\Application::whereDoesntHave('internship')->count();" 2>/dev/null | tail -n 1)
echo "Orphaned applications: $ORPHANED"

if [ "$ORPHANED" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  Warning: Found $ORPHANED orphaned applications${NC}"
    echo "These will be handled gracefully by the new code."
else
    echo -e "${GREEN}✅ No orphaned applications found${NC}"
fi
echo ""

# Step 6: Test database connection
echo -e "${BLUE}Step 6: Testing database connection...${NC}"
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected';" 2>&1 | grep -q "Connected"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Database connection successful${NC}"
else
    echo -e "${RED}❌ Database connection failed${NC}"
    exit 1
fi
echo ""

# Step 7: Verify files exist
echo -e "${BLUE}Step 7: Verifying critical files...${NC}"
FILES=(
    "app/Http/Controllers/ApplicationController.php"
    "resources/views/student/application-tracker.blade.php"
    "public/js/chatbot.js"
    "database/migrations/2026_04_23_212241_add_cascade_delete_to_applications.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✅ $file${NC}"
    else
        echo -e "${RED}❌ $file NOT FOUND${NC}"
        exit 1
    fi
done
echo ""

# Step 8: Restart web server (optional)
echo -e "${BLUE}Step 8: Restart web server?${NC}"
read -p "Restart web server? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if command -v systemctl &> /dev/null; then
        if systemctl is-active --quiet nginx; then
            sudo systemctl restart nginx
            echo -e "${GREEN}✅ Nginx restarted${NC}"
        elif systemctl is-active --quiet apache2; then
            sudo systemctl restart apache2
            echo -e "${GREEN}✅ Apache restarted${NC}"
        else
            echo -e "${YELLOW}⚠️  Could not detect web server${NC}"
        fi
    fi
fi
echo ""

# Summary
echo "======================================="
echo -e "${GREEN}✅ Deployment Complete!${NC}"
echo "======================================="
echo ""
echo "What was fixed:"
echo "1. ✅ /my-applications page - null safety added"
echo "2. ✅ Blade template - null-safe operators"
echo "3. ✅ Database - CASCADE delete constraint"
echo "4. ✅ Chatbot - error handling improved"
echo "5. ✅ Chatbot - profile data fallbacks"
echo ""
echo "Next steps:"
echo "1. Test /my-applications page in browser"
echo "2. Test chatbot functionality"
echo "3. Monitor logs: tail -f storage/logs/laravel.log"
echo ""
echo "Backup location: $BACKUP_DIR"
echo "To rollback: cp $BACKUP_DIR/* back to original locations"
echo "======================================="
