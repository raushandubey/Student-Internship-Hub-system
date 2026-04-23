# Production 500 Error - Quick Fix Card

## 🚨 MOST LIKELY CAUSE

**Database Configuration Mismatch**
- Local uses MySQL
- Production uses PostgreSQL
- `.env` file has `DB_CONNECTION=mysql` instead of `DB_CONNECTION=pgsql`

---

## ⚡ 60-SECOND FIX

```bash
# 1. SSH to production
ssh user@your-server

# 2. Navigate to app
cd /path/to/laravel

# 3. Edit .env
nano .env

# 4. Change these lines:
DB_CONNECTION=pgsql  # Change from mysql
APP_ENV=production   # Change from local
APP_DEBUG=false      # Change from true

# 5. Clear caches
php artisan config:clear && php artisan config:cache

# 6. Test
curl https://your-domain.com
```

---

## 🔍 DIAGNOSTIC COMMANDS

```bash
# Run diagnostic script
bash diagnose-production.sh

# Or manually check:

# 1. Check database connection
php artisan tinker
>>> DB::connection()->getPdo()
>>> exit

# 2. Check logs
tail -n 50 storage/logs/laravel.log

# 3. Check .env
cat .env | grep -E "DB_CONNECTION|APP_ENV|APP_DEBUG"

# 4. Check permissions
ls -la storage/logs/
```

---

## 🎯 TOP 5 PRODUCTION ERRORS

### 1. Wrong Database Driver
```bash
# Fix
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=pgsql/' .env
php artisan config:cache
```

### 2. Missing APP_KEY
```bash
# Fix
php artisan key:generate --force
php artisan config:cache
```

### 3. Cached Config
```bash
# Fix
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### 4. Storage Not Writable
```bash
# Fix
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. Database Credentials Wrong
```bash
# Fix - Edit .env
DB_HOST=correct-host
DB_PORT=5432
DB_DATABASE=correct-database
DB_USERNAME=correct-user
DB_PASSWORD=correct-password

# Then
php artisan config:cache
```

---

## 📋 PRODUCTION .ENV TEMPLATE

```env
# Application
APP_NAME="Student Internship Hub"
APP_ENV=production
APP_KEY=base64:your-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database - CRITICAL: Use pgsql for PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Session
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

# Cache
CACHE_STORE=database

# Logging
LOG_LEVEL=error
```

---

## 🔧 AUTOMATED FIX

```bash
# Download and run fix script
bash fix-production-500.sh
```

---

## 🆘 EMERGENCY COMMANDS

```bash
# If everything is broken:

# 1. Restore .env backup
cp .env.backup .env

# 2. Clear everything
php artisan optimize:clear

# 3. Rebuild
php artisan optimize

# 4. Restart web server
sudo systemctl restart nginx
```

---

## ✅ VERIFICATION

```bash
# After fix, verify:

# 1. App responds
curl -I https://your-domain.com

# 2. Database works
php artisan tinker
>>> User::count()

# 3. No errors in log
tail -n 20 storage/logs/laravel.log

# 4. Routes work
php artisan route:list
```

---

## 📞 STILL BROKEN?

1. **Enable debug temporarily:**
   ```bash
   # Edit .env
   APP_DEBUG=true
   php artisan config:cache
   
   # Visit failing route in browser
   # Copy the error message
   
   # IMMEDIATELY disable debug
   APP_DEBUG=false
   php artisan config:cache
   ```

2. **Check specific error:**
   ```bash
   tail -f storage/logs/laravel.log
   # Visit failing route
   # See the error in real-time
   ```

3. **Provide these details:**
   - Output of: `tail -n 50 storage/logs/laravel.log`
   - Output of: `cat .env | grep -E "DB_|APP_"`
   - The specific URL that fails
   - The error message from browser

---

## 🎯 PREVENTION

Add to your deployment script:

```bash
#!/bin/bash
# deploy.sh

# Verify .env is correct
if ! grep -q "DB_CONNECTION=pgsql" .env; then
    echo "ERROR: DB_CONNECTION must be pgsql"
    exit 1
fi

if ! grep -q "APP_ENV=production" .env; then
    echo "ERROR: APP_ENV must be production"
    exit 1
fi

# Deploy
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
sudo systemctl restart nginx
```

---

**Quick Reference:** See `PRODUCTION_500_ERROR_FIX.md` for complete guide
