# Render Deployment Verification Guide

## 🎯 Current Status: 500 Error Fix Applied

### What Was Fixed
The **root cause** of the 500 error was **config cache poisoning**:
- Laravel config/routes were cached in Dockerfile BEFORE environment variables were available
- When Laravel booted on Render, it read poisoned cache (no APP_KEY, no DB credentials)
- Result: 500 error because Laravel couldn't decrypt sessions, connect to DB, etc.

### The Fix
1. ✅ Removed all `php artisan cache` commands from Dockerfile
2. ✅ Added cache clearing in `start.sh` (removes stale cache files)
3. ✅ Added APP_KEY verification in `start.sh` (exits if not set)
4. ✅ Added Laravel boot test in `start.sh` (`php artisan --version`)
5. ✅ Moved all caching to `start.sh` AFTER environment variables are loaded

---

## 📋 Pre-Deployment Checklist

### 1. Environment Variables on Render
Ensure these are set in Render Dashboard → Environment:

```bash
# Required (Application will NOT boot without these)
APP_KEY=base64:your-32-character-key-here
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

# Database (Required if using database)
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host.render.com
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Mail (Optional, for email features)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Generate APP_KEY
If you don't have an APP_KEY yet:

```bash
# Run locally
php artisan key:generate --show

# Copy the output (starts with base64:)
# Paste into Render environment variables
```

### 3. Render Service Settings
- **Build Command**: (leave empty, Docker handles it)
- **Start Command**: (leave empty, Dockerfile CMD handles it)
- **Port**: (leave empty, Render sets $PORT automatically)
- **Health Check Path**: `/health`

---

## 🚀 Deployment Steps

### Step 1: Push to GitHub
```bash
git add .
git commit -m "Fix: Resolve 500 error - move cache to runtime"
git push origin main
```

### Step 2: Deploy on Render
1. Go to Render Dashboard
2. Click "Manual Deploy" → "Deploy latest commit"
3. Watch the build logs

### Step 3: Monitor Build Logs
Look for these success indicators:

```
✓ Building Docker image...
✓ Successfully built image
✓ Starting container...
✓ Container started
```

### Step 4: Monitor Startup Logs
Click "Logs" tab and look for:

```
=========================================
Starting Laravel Application on Render
=========================================
Step 1: Configuring Nginx...
  → Port: 10000
  ✓ Nginx configured
Step 2: Verifying Laravel...
  ✓ Laravel verified
Step 3: Setting permissions...
  ✓ Permissions set
Step 4: Clearing stale cache...
  ✓ Stale cache cleared
Step 5: Verifying environment variables...
  ✓ APP_KEY is set
  ✓ Environment variables verified
Step 6: Caching Laravel configuration...
  ✓ Config cached
  ✓ Routes cached
  ✓ Views cached
Step 7: Testing Laravel boot...
  ✓ Laravel boots successfully
  Laravel Framework 10.x.x
Step 8: Configuration summary...
  → PHP Version: PHP 8.2.x
  → Laravel Version: Laravel Framework 10.x.x
  → Environment: production
  → Debug Mode: false
  → Port: 10000
  → Document Root: /var/www/html/public
  → APP_KEY: base64:xxxxxxxxxxxxxxxx... (set)
  → DB_HOST: your-db-host.render.com
Step 9: Starting services...
  → Starting PHP-FPM...
  → Starting Nginx...
=========================================
✓ Application ready on port 10000
=========================================
```

---

## ✅ Verification Tests

### Test 1: Health Check (Most Basic)
```bash
curl https://your-app.onrender.com/health
```

**Expected**: `healthy`

**If fails**: Container didn't start or Nginx isn't running

---

### Test 2: Laravel Welcome Page
```bash
curl -I https://your-app.onrender.com/
```

**Expected**: `HTTP/2 200`

**If 500**: Laravel boot failed (check logs for APP_KEY or DB errors)

**If 404**: Nginx routing issue (unlikely with our config)

---

### Test 3: Login Page
```bash
curl -I https://your-app.onrender.com/login
```

**Expected**: `HTTP/2 200`

**If 500**: Database connection issue or session driver problem

---

### Test 4: Full Application Test
1. Open browser: `https://your-app.onrender.com`
2. Click "Login"
3. Try to log in with test credentials
4. Check if dashboard loads

---

## 🔍 Troubleshooting Guide

### Issue: "✗ ERROR: APP_KEY not set!"

**Cause**: APP_KEY environment variable missing on Render

**Fix**:
1. Generate key locally: `php artisan key:generate --show`
2. Copy the output (e.g., `base64:abc123...`)
3. Add to Render → Environment → `APP_KEY`
4. Redeploy

---

### Issue: "✗ ERROR: Laravel failed to boot!"

**Cause**: Database connection failed or APP_KEY invalid

**Fix**:
1. Check Render logs for specific error
2. Verify DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
3. Test database connection from Render shell:
   ```bash
   php artisan tinker
   DB::connection()->getPdo();
   ```

---

### Issue: 500 Error on Homepage

**Cause**: Session driver or cache driver misconfigured

**Fix**:
1. Check `SESSION_DRIVER=database` (not file or redis)
2. Run migrations: `php artisan migrate --force`
3. Check storage permissions in logs

---

### Issue: "Connection refused" in logs

**Cause**: PHP-FPM not started or Nginx can't connect

**Fix**:
1. Check supervisor logs for PHP-FPM errors
2. Verify port 9000 is available
3. Check `supervisord.conf` is correct

---

### Issue: Static files (CSS/JS) not loading

**Cause**: Nginx document root misconfigured

**Fix**:
1. Verify `root /var/www/html/public;` in nginx.conf
2. Check file permissions: `ls -la /var/www/html/public`
3. Run: `php artisan storage:link`

---

## 🧪 Advanced Debugging

### Access Render Shell
```bash
# From Render Dashboard → Shell tab
cd /var/www/html

# Test Laravel boot
php artisan --version

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check environment variables
php artisan config:show app

# Check permissions
ls -la storage/
ls -la bootstrap/cache/

# Check Nginx config
cat /etc/nginx/nginx.conf | grep "listen"

# Check PHP-FPM status
ps aux | grep php-fpm

# Check Nginx status
ps aux | grep nginx
```

### Check Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Nginx access logs
tail -f /dev/stdout

# Nginx error logs
tail -f /dev/stderr

# PHP-FPM logs
tail -f /var/log/php-fpm.log
```

---

## 📊 Success Indicators

### ✅ Deployment Successful If:
1. Build completes without errors
2. Container starts and stays running
3. Startup script shows all ✓ checkmarks
4. `/health` endpoint returns `healthy`
5. Homepage loads without 500 error
6. Login page loads
7. Can log in and access dashboard

### ❌ Deployment Failed If:
1. Build fails (check Dockerfile syntax)
2. Container crashes immediately (check start.sh)
3. Startup script shows ✗ errors (check environment variables)
4. `/health` returns 502/503 (container not running)
5. Homepage returns 500 (Laravel boot failed)

---

## 🎓 Viva Questions & Answers

### Q: Why did the 500 error occur?
**A**: Config cache poisoning. Laravel cached config/routes in Dockerfile before environment variables were available. When Laravel booted on Render, it read poisoned cache with no APP_KEY or DB credentials, causing boot failure.

### Q: How did you fix it?
**A**: Moved all caching from Dockerfile to start.sh. Now cache is generated AFTER environment variables are loaded, ensuring Laravel has correct config.

### Q: Why use Nginx + PHP-FPM instead of Apache?
**A**: 
- Nginx: Lightweight, better for static files, lower memory usage
- PHP-FPM: Process manager, better resource handling than mod_php
- Separation of concerns: Nginx handles HTTP, PHP-FPM handles PHP
- Industry standard for modern Laravel deployments

### Q: Why use Supervisor?
**A**: Docker containers should run one main process. We need both Nginx and PHP-FPM. Supervisor manages multiple processes as one, automatically restarts crashed processes, and is production-grade.

### Q: How does the request flow work?
**A**:
1. Request arrives at Nginx on $PORT
2. If static file: Nginx serves directly
3. If PHP/route: Nginx proxies to PHP-FPM (port 9000)
4. PHP-FPM executes public/index.php
5. Laravel handles routing
6. Response flows back: Laravel → PHP-FPM → Nginx → Client

### Q: What happens if APP_KEY is not set?
**A**: start.sh checks for APP_KEY and exits with error if not set. This prevents Laravel from booting with invalid config, which would cause 500 errors.

### Q: How do you verify the deployment is working?
**A**: 
1. Check startup logs for ✓ checkmarks
2. Test /health endpoint (returns "healthy")
3. Test homepage (returns 200, not 500)
4. Test login functionality
5. Check Laravel logs for errors

---

## 📝 Next Steps After Successful Deployment

1. **Run Migrations**:
   ```bash
   php artisan migrate --force
   ```

2. **Seed Demo Data** (optional):
   ```bash
   php artisan db:seed --class=DemoDataSeeder
   ```

3. **Create Admin User**:
   ```bash
   php artisan db:seed --class=AdminSeeder
   ```

4. **Test All Features**:
   - Student registration
   - Student login
   - Browse internships
   - Apply to internships
   - Admin login
   - Admin dashboard
   - Application management

5. **Monitor Performance**:
   - Check response times
   - Monitor memory usage
   - Check error logs

---

## 🔗 Useful Links

- **Render Dashboard**: https://dashboard.render.com
- **Render Docs**: https://render.com/docs
- **Laravel Deployment**: https://laravel.com/docs/deployment
- **Nginx Docs**: https://nginx.org/en/docs/

---

## 📞 Support

If deployment still fails after following this guide:

1. Check Render logs for specific error messages
2. Verify all environment variables are set correctly
3. Test database connection separately
4. Check APP_KEY is valid (starts with `base64:`)
5. Ensure migrations have run successfully

**Common mistakes**:
- Forgetting to set APP_KEY
- Wrong database credentials
- SESSION_DRIVER=file (should be database)
- Not running migrations
- Invalid APP_URL

---

**Last Updated**: January 19, 2026
**Status**: Ready for deployment
**Confidence Level**: High (fix addresses root cause)
