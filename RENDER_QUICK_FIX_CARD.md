# 🚀 Render Deployment Quick Fix Card

## ✅ What Was Fixed

**Problem**: 500 Server Error on Render
**Root Cause**: Config cache poisoning (caching before env vars loaded)
**Solution**: Move all caching from Dockerfile to start.sh

---

## 📋 Pre-Deployment Checklist

### 1. Required Environment Variables on Render
```bash
APP_KEY=base64:your-key-here          # ⚠️ CRITICAL - Generate with: php artisan key:generate --show
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

DB_CONNECTION=pgsql
DB_HOST=your-db-host.render.com       # ⚠️ CRITICAL
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

SESSION_DRIVER=database               # ⚠️ NOT file
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### 2. Render Service Settings
- **Environment**: Docker
- **Build Command**: (empty)
- **Start Command**: (empty)
- **Health Check Path**: `/health`

---

## 🔍 Verification Steps

### Step 1: Check Startup Logs
Look for these ✓ checkmarks:
```
✓ Nginx configured
✓ Laravel verified
✓ Permissions set
✓ Stale cache cleared
✓ APP_KEY is set
✓ Config cached
✓ Routes cached
✓ Views cached
✓ Laravel boots successfully
✓ Application ready on port 10000
```

### Step 2: Test Health Endpoint
```bash
curl https://your-app.onrender.com/health
# Expected: "healthy"
```

### Step 3: Test Homepage
```bash
curl -I https://your-app.onrender.com/
# Expected: HTTP/2 200
```

### Step 4: Test Login Page
```bash
curl -I https://your-app.onrender.com/login
# Expected: HTTP/2 200
```

---

## 🔧 Common Issues & Quick Fixes

### Issue: "✗ ERROR: APP_KEY not set!"
**Fix**: 
1. Run locally: `php artisan key:generate --show`
2. Copy output to Render → Environment → APP_KEY
3. Redeploy

### Issue: 500 Error on Homepage
**Fix**:
1. Check Render logs for specific error
2. Verify DB credentials are correct
3. Ensure SESSION_DRIVER=database (not file)
4. Run: `php artisan migrate --force`

### Issue: Container Crashes Immediately
**Fix**:
1. Check startup logs for ✗ errors
2. Verify all required env vars are set
3. Check APP_KEY format (must start with base64:)

### Issue: "Connection refused"
**Fix**:
1. Check supervisor logs
2. Verify PHP-FPM is running: `ps aux | grep php-fpm`
3. Verify Nginx is running: `ps aux | grep nginx`

---

## 🎓 Viva Quick Answers

**Q: Why did 500 error occur?**
A: Config cache poisoning - Laravel cached config before env vars were available.

**Q: How did you fix it?**
A: Moved caching from Dockerfile to start.sh, ensuring cache is generated AFTER env vars are loaded.

**Q: Why Nginx + PHP-FPM?**
A: Industry standard, better performance, separation of concerns, production-grade.

**Q: Request flow?**
A: User → Nginx → PHP-FPM → Laravel → Database → Response back

**Q: Why Supervisor?**
A: Manages multiple processes (Nginx + PHP-FPM) as one, auto-restarts on failure.

---

## 📞 Emergency Debugging

### Access Render Shell
```bash
# Test Laravel boot
php artisan --version

# Test database
php artisan tinker
>>> DB::connection()->getPdo();

# Check logs
tail -f storage/logs/laravel.log

# Check permissions
ls -la storage/ bootstrap/cache/
```

---

## ✅ Success Indicators
- ✓ All startup checkmarks present
- ✓ /health returns "healthy"
- ✓ Homepage loads (200, not 500)
- ✓ Can log in
- ✓ Dashboard loads

---

**Status**: Ready for deployment
**Confidence**: High (root cause fixed)
**Last Updated**: January 19, 2026
