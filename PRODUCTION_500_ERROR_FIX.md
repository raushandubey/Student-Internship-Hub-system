# Production 500 Error - Diagnostic & Fix Guide

## 🔴 CRITICAL ISSUE IDENTIFIED

**Root Cause:** Database configuration mismatch
- **Local:** MySQL (`DB_CONNECTION=mysql`)
- **Production:** PostgreSQL (as mentioned in your deployment)

This causes the application to crash in production when trying to use MySQL-specific queries or syntax on PostgreSQL.

---

## 🎯 IMMEDIATE FIX - Production .env Configuration

### Step 1: Update Production .env File

SSH into your production server and update the `.env` file:

```bash
# SSH to production server
ssh user@your-production-server

# Navigate to application directory
cd /path/to/your/laravel/app

# Edit .env file
nano .env  # or vim .env
```

### Step 2: Set These EXACT Values

```env
# Application
APP_NAME="Student Internship Hub"
APP_ENV=production
APP_KEY=base64:H7aEu5IOU0QAE7UIMSf78EHXdMLf1HKyLijhOGlO//I=
APP_DEBUG=false
APP_URL=https://your-production-domain.com

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error

# Database - CRITICAL: Change to PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_postgres_user
DB_PASSWORD=your_postgres_password

# Session - CRITICAL for production
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=your-production-domain.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Cache - Use Redis or database in production
CACHE_STORE=database
CACHE_PREFIX=sih_

# Queue - Use database or Redis in production
QUEUE_CONNECTION=database

# Filesystem
FILESYSTEM_DISK=public

# Mail - Configure real mail service
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 3: Clear All Caches

```bash
# Clear configuration cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Clear application cache
php artisan cache:clear

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 4: Fix Storage Permissions

```bash
# Set correct permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Or if using different user
chown -R your-web-user:your-web-group storage bootstrap/cache
```

### Step 5: Run Migrations (if not done)

```bash
# Run migrations on PostgreSQL
php artisan migrate --force

# Verify database connection
php artisan tinker
>>> DB::connection()->getPdo()
>>> exit
```

---

## 🔍 DIAGNOSTIC CHECKLIST

Run these commands on production to identify the exact issue:

### 1. Check Laravel Logs
```bash
# View last 50 lines of Laravel log
tail -n 50 storage/logs/laravel.log

# Watch logs in real-time
tail -f storage/logs/laravel.log
```

### 2. Check Web Server Logs
```bash
# For Nginx
tail -n 50 /var/log/nginx/error.log

# For Apache
tail -n 50 /var/log/apache2/error.log
```

### 3. Test Database Connection
```bash
php artisan tinker
>>> DB::connection()->getPdo()
>>> DB::table('users')->count()
>>> exit
```

### 4. Verify APP_KEY
```bash
# Check if APP_KEY exists
php artisan tinker
>>> config('app.key')
# Should show: base64:...

# If empty, generate new key
php artisan key:generate --force
```

### 5. Check File Permissions
```bash
# Check storage permissions
ls -la storage/
ls -la storage/logs/
ls -la bootstrap/cache/

# Should show writable permissions (775 or 755)
```

### 6. Test Specific Route
```bash
# Enable debug mode temporarily
php artisan tinker
>>> config(['app.debug' => true])

# Or edit .env temporarily
APP_DEBUG=true

# Visit the failing route and check error
# Then IMMEDIATELY set back to false
APP_DEBUG=false
```

---

## 🚨 COMMON PRODUCTION-ONLY ERRORS

### Error 1: Database Connection Failed
**Symptom:** 500 error on all routes
**Cause:** Wrong DB_CONNECTION or credentials
**Fix:**
```bash
# Verify PostgreSQL is running
sudo systemctl status postgresql

# Test connection
psql -U your_user -d your_database -h your_host

# Update .env with correct credentials
DB_CONNECTION=pgsql
DB_HOST=correct-host
DB_PORT=5432
DB_DATABASE=correct-database
DB_USERNAME=correct-user
DB_PASSWORD=correct-password

# Clear config cache
php artisan config:clear
php artisan config:cache
```

### Error 2: Missing APP_KEY
**Symptom:** "No application encryption key has been specified"
**Fix:**
```bash
php artisan key:generate --force
php artisan config:cache
```

### Error 3: Cached Config with Wrong Values
**Symptom:** Changes to .env don't take effect
**Fix:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### Error 4: Storage Not Writable
**Symptom:** "file_put_contents(): failed to open stream"
**Fix:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Error 5: Missing Session Table
**Symptom:** 500 error after login or form submission
**Fix:**
```bash
# Create sessions table
php artisan session:table
php artisan migrate --force

# Or use file driver
SESSION_DRIVER=file
php artisan config:cache
```

### Error 6: View Not Found
**Symptom:** "View [name] not found"
**Fix:**
```bash
# Clear view cache
php artisan view:clear

# Verify view exists
ls -la resources/views/

# Rebuild view cache
php artisan view:cache
```

### Error 7: Route Not Found
**Symptom:** 404 or 500 on specific routes
**Fix:**
```bash
# Clear route cache
php artisan route:clear

# List all routes
php artisan route:list

# Rebuild route cache
php artisan route:cache
```

### Error 8: OpenAI API Key Missing
**Symptom:** 500 error on chatbot or AI features
**Fix:**
```bash
# Add to .env
OPENAI_API_KEY=your-actual-api-key

# Clear config
php artisan config:clear
php artisan config:cache
```

---

## 🔧 PRODUCTION DEPLOYMENT CHECKLIST

Use this checklist for every deployment:

### Pre-Deployment
- [ ] Backup database: `pg_dump -U user -d database > backup.sql`
- [ ] Backup .env file: `cp .env .env.backup`
- [ ] Test locally with `APP_ENV=production` and `APP_DEBUG=false`

### Deployment
- [ ] Pull latest code: `git pull origin main`
- [ ] Install dependencies: `composer install --no-dev --optimize-autoloader`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Clear all caches: `php artisan optimize:clear`
- [ ] Rebuild caches: `php artisan optimize`

### Post-Deployment
- [ ] Verify .env settings (especially DB_CONNECTION=pgsql)
- [ ] Check storage permissions: `chmod -R 775 storage bootstrap/cache`
- [ ] Test database connection: `php artisan tinker >>> DB::connection()->getPdo()`
- [ ] Check logs: `tail -f storage/logs/laravel.log`
- [ ] Test critical routes (login, dashboard, etc.)
- [ ] Monitor for 5 minutes

---

## 🎯 EXACT COMMANDS TO RUN NOW

Run these commands in order on your production server:

```bash
# 1. Navigate to app directory
cd /path/to/your/laravel/app

# 2. Backup current .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# 3. Edit .env and change DB_CONNECTION to pgsql
nano .env
# Change: DB_CONNECTION=mysql
# To:     DB_CONNECTION=pgsql
# Update: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# Change: APP_ENV=production
# Change: APP_DEBUG=false

# 4. Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 5. Fix permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 6. Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
>>> DB::table('users')->count()
>>> exit

# 7. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Restart web server
sudo systemctl restart nginx  # or apache2

# 9. Check logs
tail -f storage/logs/laravel.log
```

---

## 🔍 DEBUGGING SPECIFIC ROUTE

If a specific route is failing:

```bash
# 1. Find the route
php artisan route:list | grep "your-route-path"

# 2. Check the controller
cat app/Http/Controllers/YourController.php

# 3. Enable debug temporarily
# Edit .env: APP_DEBUG=true
php artisan config:cache

# 4. Visit the route and see the error

# 5. Check for:
# - Missing database columns
# - Wrong relationships
# - Missing environment variables
# - File permission issues

# 6. IMMEDIATELY disable debug
# Edit .env: APP_DEBUG=false
php artisan config:cache
```

---

## 🛡️ PREVENT FUTURE PRODUCTION CRASHES

### 1. Use Environment-Specific Config

Create `config/database.php` check:
```php
'default' => env('DB_CONNECTION', 'pgsql'), // Default to pgsql, not mysql
```

### 2. Add Health Check Route

Add to `routes/web.php`:
```php
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});
```

### 3. Add Logging for Errors

In `app/Exceptions/Handler.php`:
```php
public function register(): void
{
    $this->reportable(function (Throwable $e) {
        if (app()->environment('production')) {
            Log::error('Production Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    });
}
```

### 4. Use Deployment Script

Create `deploy.sh`:
```bash
#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Fix permissions
chmod -R 775 storage bootstrap/cache

# Restart services
sudo systemctl restart nginx

echo "✅ Deployment complete!"
```

### 5. Monitor Logs

Set up log monitoring:
```bash
# Install logrotate for Laravel logs
sudo nano /etc/logrotate.d/laravel

# Add:
/path/to/your/app/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0640 www-data www-data
}
```

---

## 📊 VERIFICATION

After applying fixes, verify:

```bash
# 1. Check app is running
curl -I https://your-domain.com

# 2. Check health endpoint
curl https://your-domain.com/health

# 3. Check database connection
php artisan tinker
>>> User::count()

# 4. Check logs are clean
tail -n 20 storage/logs/laravel.log

# 5. Test the failing route
curl https://your-domain.com/your-failing-route
```

---

## 🆘 EMERGENCY ROLLBACK

If fixes don't work:

```bash
# 1. Restore .env backup
cp .env.backup.YYYYMMDD_HHMMSS .env

# 2. Restore database backup
psql -U user -d database < backup.sql

# 3. Clear caches
php artisan config:clear
php artisan cache:clear

# 4. Restart web server
sudo systemctl restart nginx
```

---

## 📞 SUPPORT CHECKLIST

If still failing, provide:
1. Output of: `tail -n 50 storage/logs/laravel.log`
2. Output of: `php artisan route:list | grep failing-route`
3. Output of: `php artisan config:show database`
4. The specific URL that's failing
5. Any error messages from browser console

---

**Status:** Ready to fix production error
**Priority:** CRITICAL - Database configuration mismatch
**Estimated Fix Time:** 5-10 minutes
