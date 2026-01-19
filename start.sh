#!/bin/bash
# ============================================================================
# Startup Script for Laravel on Render - PRODUCTION FIXED VERSION
# ============================================================================

set -e  # Exit on any error

echo "========================================="
echo "Starting Laravel Application on Render"
echo "========================================="

# ============================================================================
# Step 1: Configure Nginx Port
# ============================================================================
echo "Step 1: Configuring Nginx..."

PORT=${PORT:-10000}
echo "  → Port: $PORT"

sed -i "s/\${PORT}/$PORT/g" /etc/nginx/nginx.conf

echo "  ✓ Nginx configured"

# ============================================================================
# Step 2: Verify Laravel Installation
# ============================================================================
echo "Step 2: Verifying Laravel..."

if [ ! -d "/var/www/html/vendor" ]; then
    echo "  ✗ Error: vendor/ directory not found"
    exit 1
fi

echo "  ✓ Laravel verified"

# ============================================================================
# Step 3: Set Permissions (Critical for Laravel boot)
# ============================================================================
echo "Step 3: Setting permissions..."

# Ensure www-data owns everything
chown -R www-data:www-data /var/www/html

# Ensure storage and cache are writable
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "  ✓ Permissions set"

# ============================================================================
# Step 4: Clear Stale Cache (CRITICAL FIX)
# ============================================================================
echo "Step 4: Clearing stale cache..."

# Remove any cache created in Dockerfile (without env vars)
rm -f /var/www/html/bootstrap/cache/config.php
rm -f /var/www/html/bootstrap/cache/routes-v7.php
rm -f /var/www/html/bootstrap/cache/routes-v8.php
rm -rf /var/www/html/bootstrap/cache/packages.php
rm -rf /var/www/html/bootstrap/cache/services.php

echo "  ✓ Stale cache cleared"

# ============================================================================
# Step 5: Verify Environment Variables (CRITICAL)
# ============================================================================
echo "Step 5: Verifying environment variables..."

if [ -z "$APP_KEY" ]; then
    echo "  ✗ ERROR: APP_KEY not set!"
    echo "  → Set APP_KEY in Render environment variables"
    echo "  → Generate with: php artisan key:generate --show"
    exit 1
fi

echo "  ✓ APP_KEY is set"

if [ -z "$DB_HOST" ]; then
    echo "  ⚠ WARNING: DB_HOST not set"
    echo "  → Database connections will fail"
fi

echo "  ✓ Environment variables verified"

# ============================================================================
# Step 5.5: Test Database Connection (NON-BLOCKING)
# ============================================================================
echo "Step 5.5: Testing database connection..."

if [ -n "$DB_HOST" ]; then
    # Try to connect to database (non-blocking, timeout 5 seconds)
    if timeout 5 php artisan db:show 2>/dev/null; then
        echo "  ✓ Database connection successful"
        DB_AVAILABLE=true
    else
        echo "  ⚠ WARNING: Database connection failed or timed out"
        echo "  → Application will start WITHOUT database"
        echo "  → Homepage will work, but database features will fail"
        echo "  → Verify DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in Render"
        DB_AVAILABLE=false
    fi
else
    echo "  ⚠ WARNING: DB_HOST not set, skipping database test"
    DB_AVAILABLE=false
fi

# ============================================================================
# Step 6: Cache Laravel Configuration (WITH environment variables)
# ============================================================================
echo "Step 6: Caching Laravel configuration..."

# Force clear any existing cache to ensure env vars take effect
php artisan config:clear 2>/dev/null || true

# Now cache with actual environment variables
php artisan config:cache
echo "  ✓ Config cached"

# ============================================================================
# Step 6.5: Run Package Discovery (WITH environment variables)
# ============================================================================
echo "Step 6.5: Running package discovery..."

# Run package discovery now that env vars are available
php artisan package:discover --ansi
echo "  ✓ Packages discovered"

# ============================================================================
# Step 7: Cache Routes and Views
# ============================================================================
echo "Step 7: Caching routes and views..."

php artisan route:cache
echo "  ✓ Routes cached"

php artisan view:cache
echo "  ✓ Views cached"

# ============================================================================
# Step 8: Test Laravel Boot
# ============================================================================
echo "Step 8: Testing Laravel boot..."

# Try to run a simple artisan command to verify Laravel can boot
if php artisan --version > /dev/null 2>&1; then
    echo "  ✓ Laravel boots successfully"
    php artisan --version
else
    echo "  ✗ ERROR: Laravel failed to boot!"
    echo "  → Check APP_KEY and database credentials"
    exit 1
fi

# ============================================================================
# Step 9: Display Configuration
# ============================================================================
echo "Step 9: Configuration summary..."
echo "  → PHP Version: $(php -v | head -n 1)"
echo "  → Laravel Version: $(php artisan --version)"
echo "  → Environment: ${APP_ENV:-production}"
echo "  → Debug Mode: ${APP_DEBUG:-false}"
echo "  → Port: $PORT"
echo "  → Document Root: /var/www/html/public"
echo "  → APP_KEY: ${APP_KEY:0:20}... (set)"
echo "  → DB_HOST: ${DB_HOST:-not set}"

# ============================================================================
# Step 10: Start Supervisor (Nginx + PHP-FPM)
# ============================================================================
echo "Step 10: Starting services..."
echo "  → Starting PHP-FPM..."
echo "  → Starting Nginx..."
echo "========================================="
echo "✓ Application ready on port $PORT"
echo "========================================="

# Start supervisor
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
