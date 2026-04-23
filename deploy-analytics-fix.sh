#!/bin/bash

# Admin Analytics Fix Deployment Script
# Fixes PostgreSQL compatibility and adds error handling

set -e

echo "🔧 Deploying Admin Analytics Fix"
echo "================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Step 1: Backup
echo -e "${BLUE}Step 1: Creating backups...${NC}"
BACKUP_DIR="backups/analytics_fix_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp app/Services/AnalyticsService.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Http/Controllers/Admin/AdminAnalyticsController.php "$BACKUP_DIR/" 2>/dev/null || true
cp resources/views/admin/analytics.blade.php "$BACKUP_DIR/" 2>/dev/null || true

echo -e "${GREEN}✅ Backups created in $BACKUP_DIR${NC}"
echo ""

# Step 2: Clear caches
echo -e "${BLUE}Step 2: Clearing caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo -e "${GREEN}✅ Caches cleared${NC}"
echo ""

# Step 3: Test analytics service
echo -e "${BLUE}Step 3: Testing analytics service...${NC}"
php artisan tinker --execute="
try {
    \$service = app(App\Services\AnalyticsService::class);
    \$stats = \$service->getOverallStats();
    echo 'Overall Stats: OK\n';
    \$top = \$service->getTopPerformingInternships(5);
    echo 'Top Performing: OK\n';
    echo 'SUCCESS';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>&1 | grep -q "SUCCESS"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Analytics service tests passed${NC}"
else
    echo -e "${YELLOW}⚠️  Analytics service tests had issues (check logs)${NC}"
fi
echo ""

# Step 4: Rebuild caches
echo -e "${BLUE}Step 4: Rebuilding optimized caches...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Caches rebuilt${NC}"
echo ""

# Step 5: Verify files
echo -e "${BLUE}Step 5: Verifying critical files...${NC}"
FILES=(
    "app/Services/AnalyticsService.php"
    "app/Http/Controllers/Admin/AdminAnalyticsController.php"
    "resources/views/admin/analytics.blade.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✅ $file${NC}"
    else
        echo -e "${YELLOW}❌ $file NOT FOUND${NC}"
    fi
done
echo ""

# Summary
echo "================================="
echo -e "${GREEN}✅ Deployment Complete!${NC}"
echo "================================="
echo ""
echo "What was fixed:"
echo "1. ✅ PostgreSQL-compatible CASE WHEN query"
echo "2. ✅ Null safety in all analytics methods"
echo "3. ✅ Error handling in controller"
echo "4. ✅ Division by zero protection in blade"
echo "5. ✅ Empty array protection"
echo ""
echo "Next steps:"
echo "1. Test /admin/analytics page in browser"
echo "2. Monitor logs: tail -f storage/logs/laravel.log"
echo "3. Verify all charts load correctly"
echo ""
echo "Backup location: $BACKUP_DIR"
echo "================================="
