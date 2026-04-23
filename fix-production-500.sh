#!/bin/bash

# Production 500 Error Fix Script
# Run this on your production server

set -e

echo "🔧 Laravel Production 500 Error Fix Script"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -eq 0 ]; then 
    echo -e "${RED}❌ Do not run this script as root${NC}"
    exit 1
fi

# Get app directory
APP_DIR=$(pwd)
echo -e "${GREEN}📁 Application Directory: $APP_DIR${NC}"
echo ""

# Step 1: Backup .env
echo "📦 Step 1: Backing up .env file..."
BACKUP_FILE=".env.backup.$(date +%Y%m%d_%H%M%S)"
cp .env "$BACKUP_FILE"
echo -e "${GREEN}✅ Backup created: $BACKUP_FILE${NC}"
echo ""

# Step 2: Check current DB_CONNECTION
echo "🔍 Step 2: Checking current database configuration..."
CURRENT_DB=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)
echo "Current DB_CONNECTION: $CURRENT_DB"

if [ "$CURRENT_DB" != "pgsql" ]; then
    echo -e "${YELLOW}⚠️  WARNING: DB_CONNECTION is set to '$CURRENT_DB' but should be 'pgsql' for PostgreSQL${NC}"
    echo ""
    read -p "Do you want to update DB_CONNECTION to pgsql? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
        echo -e "${GREEN}✅ Updated DB_CONNECTION to pgsql${NC}"
    fi
else
    echo -e "${GREEN}✅ DB_CONNECTION is correctly set to pgsql${NC}"
fi
echo ""

# Step 3: Check APP_ENV and APP_DEBUG
echo "🔍 Step 3: Checking APP_ENV and APP_DEBUG..."
CURRENT_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
CURRENT_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d '=' -f2)

echo "Current APP_ENV: $CURRENT_ENV"
echo "Current APP_DEBUG: $CURRENT_DEBUG"

if [ "$CURRENT_ENV" != "production" ]; then
    echo -e "${YELLOW}⚠️  WARNING: APP_ENV should be 'production'${NC}"
    read -p "Update APP_ENV to production? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
        echo -e "${GREEN}✅ Updated APP_ENV to production${NC}"
    fi
fi

if [ "$CURRENT_DEBUG" != "false" ]; then
    echo -e "${YELLOW}⚠️  WARNING: APP_DEBUG should be 'false' in production${NC}"
    read -p "Update APP_DEBUG to false? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
        echo -e "${GREEN}✅ Updated APP_DEBUG to false${NC}"
    fi
fi
echo ""

# Step 4: Clear all caches
echo "🧹 Step 4: Clearing all caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo -e "${GREEN}✅ All caches cleared${NC}"
echo ""

# Step 5: Test database connection
echo "🔌 Step 5: Testing database connection..."
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected';" 2>/dev/null | grep -q "Connected"; then
    echo -e "${GREEN}✅ Database connection successful${NC}"
else
    echo -e "${RED}❌ Database connection failed${NC}"
    echo "Please check your database credentials in .env:"
    echo "  - DB_HOST"
    echo "  - DB_PORT"
    echo "  - DB_DATABASE"
    echo "  - DB_USERNAME"
    echo "  - DB_PASSWORD"
    exit 1
fi
echo ""

# Step 6: Check storage permissions
echo "🔐 Step 6: Checking storage permissions..."
if [ -w "storage/logs" ]; then
    echo -e "${GREEN}✅ storage/logs is writable${NC}"
else
    echo -e "${RED}❌ storage/logs is not writable${NC}"
    read -p "Fix storage permissions? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        chmod -R 775 storage bootstrap/cache
        echo -e "${GREEN}✅ Permissions fixed${NC}"
    fi
fi
echo ""

# Step 7: Rebuild caches
echo "🔨 Step 7: Rebuilding optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Caches rebuilt${NC}"
echo ""

# Step 8: Check for recent errors in log
echo "📋 Step 8: Checking recent errors..."
if [ -f "storage/logs/laravel.log" ]; then
    RECENT_ERRORS=$(tail -n 20 storage/logs/laravel.log | grep -i "error" | wc -l)
    if [ "$RECENT_ERRORS" -gt 0 ]; then
        echo -e "${YELLOW}⚠️  Found $RECENT_ERRORS recent errors in log${NC}"
        echo "Last 10 lines of log:"
        tail -n 10 storage/logs/laravel.log
    else
        echo -e "${GREEN}✅ No recent errors in log${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Log file not found${NC}"
fi
echo ""

# Step 9: Restart web server (optional)
echo "🔄 Step 9: Restart web server..."
read -p "Do you want to restart the web server? (y/n) " -n 1 -r
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
    else
        echo -e "${YELLOW}⚠️  systemctl not available${NC}"
    fi
fi
echo ""

# Summary
echo "=========================================="
echo -e "${GREEN}✅ Fix script completed!${NC}"
echo ""
echo "Next steps:"
echo "1. Test your application in a browser"
echo "2. Monitor logs: tail -f storage/logs/laravel.log"
echo "3. If still failing, check PRODUCTION_500_ERROR_FIX.md"
echo ""
echo "Backup file: $BACKUP_FILE"
echo "To restore: cp $BACKUP_FILE .env"
echo "=========================================="
