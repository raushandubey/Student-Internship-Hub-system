#!/bin/bash
# ============================================================================
# Startup Script for Laravel on Render
# ============================================================================
# 
# WHY this script?
# - Render provides $PORT environment variable at runtime
# - Can't hardcode port in nginx.conf
# - Need to configure Nginx dynamically before starting
# - Ensures Laravel is ready before accepting requests
# ============================================================================

set -e  # Exit on any error

echo "========================================="
echo "Starting Laravel Application on Render"
echo "========================================="

# ============================================================================
# Step 1: Configure Nginx Port
# ============================================================================
echo "Step 1: Configuring Nginx..."

# Get port from environment variable (Render provides this)
# Default to 10000 if not set
PORT=${PORT:-10000}
echo "  → Port: $PORT"

# Replace ${PORT} placeholder in nginx.conf with actual port
# WHY? Nginx config needs actual port number, not variable
sed -i "s/\${PORT}/$PORT/g" /etc/nginx/nginx.conf

echo "  ✓ Nginx configured"

# ============================================================================
# Step 2: Verify Laravel Installation
# ============================================================================
echo "Step 2: Verifying Laravel..."

# Check if vendor directory exists
if [ ! -d "/var/www/html/vendor" ]; then
    echo "  ✗ Error: vendor/ directory not found"
    echo "  → Run 'composer install' in Dockerfile"
    exit 1
fi

# Check if .env exists (should come from Render environment variables)
# Note: Laravel can work without .env if all vars are in environment
if [ ! -f "/var/www/html/.env" ]; then
    echo "  ⚠ Warning: .env file not found"
    echo "  → Using environment variables from Render"
fi

echo "  ✓ Laravel verified"

# ============================================================================
# Step 3: Set Permissions
# ============================================================================
echo "Step 3: Setting permissions..."

# Ensure www-data owns Laravel files
# WHY? Nginx and PHP-FPM run as www-data
chown -R www-data:www-data /var/www/html

# Ensure storage and cache are writable
# WHY? Laravel needs to write logs, cache, sessions
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "  ✓ Permissions set"

# ============================================================================
# Step 4: Run Laravel Optimizations (if not already done)
# ============================================================================
echo "Step 4: Optimizing Laravel..."

# These should already be done in Dockerfile, but verify
if [ ! -f "/var/www/html/bootstrap/cache/config.php" ]; then
    echo "  → Running config:cache..."
    php artisan config:cache
fi

if [ ! -f "/var/www/html/bootstrap/cache/routes-v7.php" ]; then
    echo "  → Running route:cache..."
    php artisan route:cache
fi

echo "  ✓ Laravel optimized"

# ============================================================================
# Step 5: Display Configuration
# ============================================================================
echo "Step 5: Configuration summary..."
echo "  → PHP Version: $(php -v | head -n 1)"
echo "  → Laravel Version: $(php artisan --version)"
echo "  → Environment: ${APP_ENV:-production}"
echo "  → Port: $PORT"
echo "  → Document Root: /var/www/html/public"

# ============================================================================
# Step 6: Start Supervisor (Nginx + PHP-FPM)
# ============================================================================
echo "Step 6: Starting services..."
echo "  → Starting PHP-FPM..."
echo "  → Starting Nginx..."
echo "========================================="
echo "Application ready on port $PORT"
echo "========================================="

# Start supervisor (manages Nginx + PHP-FPM)
# WHY -n? Run in foreground (keeps container alive)
# WHY -c? Specify config file
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf

# ============================================================================
# SCRIPT SUMMARY
# ============================================================================
# 
# What this script does:
# 1. Gets $PORT from environment (Render provides)
# 2. Configures Nginx with correct port
# 3. Verifies Laravel installation
# 4. Sets correct file permissions
# 5. Runs Laravel optimizations (if needed)
# 6. Displays configuration summary
# 7. Starts supervisor (which starts Nginx + PHP-FPM)
#
# Execution Flow:
# 1. Docker starts container
# 2. Container runs CMD ["/start.sh"]
# 3. This script executes
# 4. Script configures and starts services
# 5. Supervisor keeps services running
# 6. Container stays alive (supervisor in foreground)
#
# Error Handling:
# - set -e: Exit on any error
# - Checks for vendor/ directory
# - Warns if .env missing (uses env vars)
# - Verifies permissions
#
# Why this approach?
# - Dynamic port configuration (Render requirement)
# - Verification before starting
# - Clear logging for debugging
# - Graceful error handling
# - Production-ready startup sequence
# ============================================================================
