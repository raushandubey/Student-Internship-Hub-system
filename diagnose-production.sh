#!/bin/bash

# Production Diagnostic Script
# Run this to identify the exact cause of 500 errors

echo "🔍 Laravel Production Diagnostic Tool"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 1. Environment Check
echo -e "${BLUE}1. Environment Configuration${NC}"
echo "----------------------------"
if [ -f .env ]; then
    echo -e "${GREEN}✅ .env file exists${NC}"
    echo "APP_ENV: $(grep "^APP_ENV=" .env | cut -d '=' -f2)"
    echo "APP_DEBUG: $(grep "^APP_DEBUG=" .env | cut -d '=' -f2)"
    echo "APP_KEY: $(grep "^APP_KEY=" .env | cut -d '=' -f2 | cut -c1-20)..."
    echo "DB_CONNECTION: $(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)"
    echo "DB_HOST: $(grep "^DB_HOST=" .env | cut -d '=' -f2)"
    echo "DB_DATABASE: $(grep "^DB_DATABASE=" .env | cut -d '=' -f2)"
else
    echo -e "${RED}❌ .env file not found${NC}"
fi
echo ""

# 2. Database Connection
echo -e "${BLUE}2. Database Connection${NC}"
echo "----------------------"
if php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'SUCCESS'; } catch (Exception \$e) { echo 'FAILED: ' . \$e->getMessage(); }" 2>&1 | grep -q "SUCCESS"; then
    echo -e "${GREEN}✅ Database connection successful${NC}"
    USER_COUNT=$(php artisan tinker --execute="echo DB::table('users')->count();" 2>/dev/null | tail -n 1)
    echo "Users in database: $USER_COUNT"
else
    echo -e "${RED}❌ Database connection failed${NC}"
    php artisan tinker --execute="try { DB::connection()->getPdo(); } catch (Exception \$e) { echo \$e->getMessage(); }" 2>&1 | tail -n 5
fi
echo ""

# 3. Storage Permissions
echo -e "${BLUE}3. Storage Permissions${NC}"
echo "----------------------"
DIRS=("storage/logs" "storage/framework/cache" "storage/framework/sessions" "storage/framework/views" "bootstrap/cache")
for dir in "${DIRS[@]}"; do
    if [ -d "$dir" ]; then
        if [ -w "$dir" ]; then
            echo -e "${GREEN}✅ $dir is writable${NC}"
        else
            echo -e "${RED}❌ $dir is NOT writable${NC}"
        fi
    else
        echo -e "${RED}❌ $dir does not exist${NC}"
    fi
done
echo ""

# 4. Cache Status
echo -e "${BLUE}4. Cache Status${NC}"
echo "---------------"
if [ -f "bootstrap/cache/config.php" ]; then
    echo -e "${YELLOW}⚠️  Config is cached${NC}"
    echo "Last modified: $(stat -c %y bootstrap/cache/config.php 2>/dev/null || stat -f %Sm bootstrap/cache/config.php)"
else
    echo -e "${GREEN}✅ Config is not cached${NC}"
fi

if [ -f "bootstrap/cache/routes-v7.php" ]; then
    echo -e "${YELLOW}⚠️  Routes are cached${NC}"
else
    echo -e "${GREEN}✅ Routes are not cached${NC}"
fi
echo ""

# 5. Recent Errors
echo -e "${BLUE}5. Recent Errors (Last 20 lines)${NC}"
echo "--------------------------------"
if [ -f "storage/logs/laravel.log" ]; then
    echo "Log file size: $(du -h storage/logs/laravel.log | cut -f1)"
    echo ""
    echo "Recent errors:"
    tail -n 20 storage/logs/laravel.log | grep -i "error\|exception\|fatal" || echo "No recent errors found"
else
    echo -e "${RED}❌ Log file not found${NC}"
fi
echo ""

# 6. PHP Version
echo -e "${BLUE}6. PHP Configuration${NC}"
echo "--------------------"
echo "PHP Version: $(php -v | head -n 1)"
echo "PHP Extensions:"
php -m | grep -E "pdo|pgsql|openssl|mbstring|tokenizer|xml|ctype|json" | sed 's/^/  - /'
echo ""

# 7. Composer Dependencies
echo -e "${BLUE}7. Composer Dependencies${NC}"
echo "------------------------"
if [ -f "composer.lock" ]; then
    echo -e "${GREEN}✅ composer.lock exists${NC}"
    echo "Laravel Version: $(grep '"laravel/framework"' composer.lock -A 3 | grep '"version"' | cut -d '"' -f4)"
else
    echo -e "${RED}❌ composer.lock not found${NC}"
fi
echo ""

# 8. Web Server
echo -e "${BLUE}8. Web Server${NC}"
echo "--------------"
if command -v systemctl &> /dev/null; then
    if systemctl is-active --quiet nginx; then
        echo -e "${GREEN}✅ Nginx is running${NC}"
    elif systemctl is-active --quiet apache2; then
        echo -e "${GREEN}✅ Apache is running${NC}"
    else
        echo -e "${YELLOW}⚠️  Could not detect running web server${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  systemctl not available${NC}"
fi
echo ""

# 9. Routes
echo -e "${BLUE}9. Registered Routes${NC}"
echo "--------------------"
ROUTE_COUNT=$(php artisan route:list 2>/dev/null | wc -l)
echo "Total routes: $ROUTE_COUNT"
echo ""

# 10. Disk Space
echo -e "${BLUE}10. Disk Space${NC}"
echo "--------------"
df -h . | tail -n 1
echo ""

# Summary
echo "======================================"
echo -e "${BLUE}Diagnostic Summary${NC}"
echo "======================================"
echo ""

# Check critical issues
CRITICAL=0

if ! [ -f .env ]; then
    echo -e "${RED}❌ CRITICAL: .env file missing${NC}"
    CRITICAL=$((CRITICAL + 1))
fi

if ! php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null; then
    echo -e "${RED}❌ CRITICAL: Database connection failed${NC}"
    CRITICAL=$((CRITICAL + 1))
fi

if ! [ -w "storage/logs" ]; then
    echo -e "${RED}❌ CRITICAL: storage/logs not writable${NC}"
    CRITICAL=$((CRITICAL + 1))
fi

APP_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo -e "${RED}❌ CRITICAL: APP_KEY is empty${NC}"
    CRITICAL=$((CRITICAL + 1))
fi

if [ $CRITICAL -eq 0 ]; then
    echo -e "${GREEN}✅ No critical issues found${NC}"
    echo ""
    echo "If you're still experiencing 500 errors:"
    echo "1. Check the specific route that's failing"
    echo "2. Enable APP_DEBUG=true temporarily to see the error"
    echo "3. Check storage/logs/laravel.log for details"
    echo "4. Run: tail -f storage/logs/laravel.log"
else
    echo -e "${RED}Found $CRITICAL critical issue(s)${NC}"
    echo ""
    echo "Run the fix script: bash fix-production-500.sh"
fi

echo ""
echo "For detailed fixes, see: PRODUCTION_500_ERROR_FIX.md"
echo "======================================"
