# 🚀 Render Deployment Checklist

## ✅ Pre-Deployment Verification (LOCAL)

### 1. Local Environment Status
- [x] Dashboard renders once (no duplication)
- [x] Login works consistently (no 419 errors)
- [x] Session stable (no random logout)
- [x] All migrations applied successfully
- [x] Email logs idempotency working
- [x] Timezone set to Asia/Kolkata

### 2. Code Quality
- [x] No duplicate routes
- [x] No duplicate blade rendering
- [x] Proper use of @extends/@section
- [x] Controllers return views once
- [x] Middleware order correct

### 3. Production Fixes Applied
- [x] `Dockerfile`: Removed `package:discover` from build
- [x] `start.sh`: Added `package:discover` at runtime
- [x] `routes/console.php`: Using closures (not `new ClassName`)
- [x] Email idempotency: Unique constraint + `createIdempotent()`
- [x] Timezone: Asia/Kolkata in `config/app.php`

---

## 🔧 Render Environment Variables

### Required Variables (Set in Render Dashboard)

```bash
# Application
APP_KEY=base64:dt5cVqAJ4XEBCBlOE/IDoCSnDtbAU4/7UCcQy2nhBjU=
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

# Database (Get from Render MySQL Internal URL)
DB_CONNECTION=mysql
DB_HOST=<your-mysql-internal-host>
DB_PORT=3306
DB_DATABASE=<your-database-name>
DB_USERNAME=<your-database-user>
DB_PASSWORD=<your-database-password>

# Session & Cache (File-based for Render)
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file

# Queue (Sync for simple deployment)
QUEUE_CONNECTION=sync

# Timezone
APP_TIMEZONE=Asia/Kolkata
```

### How to Set Variables in Render:
1. Go to your Render service dashboard
2. Click "Environment" tab
3. Add each variable above
4. Click "Save Changes"

---

## 📦 Deployment Steps

### Step 1: Push to Git
```bash
git status
git log --oneline -3
git push origin master
```

### Step 2: Monitor Render Build
1. Go to Render dashboard
2. Watch "Logs" tab
3. Build should complete without errors

**Expected Build Output:**
```
✓ Composer install completed
✓ Autoloader generated
✓ Docker image built
✓ Container started
```

### Step 3: Monitor Runtime Logs
Watch for these steps in runtime logs:

```
Step 1: Configuring Nginx... ✓
Step 2: Verifying Laravel... ✓
Step 3: Setting permissions... ✓
Step 4: Clearing stale cache... ✓
Step 5: Verifying environment variables... ✓
Step 5.5: Testing database connection... ✓
Step 6: Caching Laravel configuration... ✓
Step 6.5: Running package discovery... ✓
Step 7: Caching routes and views... ✓
Step 8: Testing Laravel boot... ✓
Step 9: Configuration summary...
Step 10: Starting services... ✓
✓ Application ready on port 10000
```

### Step 4: Run Migrations (First Deploy Only)
```bash
# In Render Shell (Dashboard → Shell tab)
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
php artisan db:seed --class=InternshipSeeder --force
```

### Step 5: Verify Application
1. Visit your Render URL
2. Test login
3. Test dashboard
4. Test application submission
5. Check email logs (no duplicates)

---

## 🐛 Troubleshooting

### Build Fails: "Class not found"
**Cause:** Job class instantiated during build
**Fix:** Already fixed in `routes/console.php` (using closures)

### Build Fails: "getaddrinfo failed"
**Cause:** Database connection attempted during build
**Fix:** Already fixed - `package:discover` moved to runtime

### Runtime: 419 Page Expired
**Cause:** APP_KEY not set or session driver misconfigured
**Fix:** 
- Verify APP_KEY in Render environment variables
- Verify SESSION_DRIVER=file

### Runtime: Database Connection Failed
**Cause:** Wrong DB credentials
**Fix:**
- Use Render MySQL **Internal URL** (not external)
- Verify DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

### Runtime: Duplicate Email Logs
**Cause:** Migration not applied
**Fix:**
```bash
php artisan migrate --force
```

---

## ✅ Post-Deployment Verification

### 1. Application Health
- [ ] Homepage loads
- [ ] Login works
- [ ] Dashboard renders once
- [ ] No 419 errors
- [ ] Session persists

### 2. Database
- [ ] Migrations applied
- [ ] Seeders run successfully
- [ ] Email logs table has unique constraint

### 3. Email Logs
- [ ] Submit application → Check email_logs table
- [ ] Update status → Check email_logs table
- [ ] No duplicate logs for same event

### 4. Performance
- [ ] Config cached
- [ ] Routes cached
- [ ] Views cached
- [ ] Response time < 500ms

---

## 📊 Monitoring

### Check Logs
```bash
# In Render Shell
tail -f storage/logs/laravel.log
```

### Check Email Logs
```bash
# In Render Shell
php artisan tinker
>>> DB::table('email_logs')->latest()->take(10)->get();
```

### Check Scheduled Jobs
```bash
php artisan schedule:list
```

---

## 🎯 Success Criteria

Your deployment is successful when:

1. ✅ Build completes without errors
2. ✅ Application boots successfully
3. ✅ Database connection works
4. ✅ Login/logout works consistently
5. ✅ Dashboard renders once (no duplication)
6. ✅ Email logs have no duplicates
7. ✅ Timezone shows Asia/Kolkata
8. ✅ No 419 errors
9. ✅ Session persists across requests
10. ✅ All features work as expected

---

## 📝 Notes

- **Local vs Production:** Local uses `.env`, Production uses Render environment variables
- **Database:** Render MySQL is external service, not in Docker container
- **Sessions:** File-based (stored in `storage/framework/sessions`)
- **Cache:** File-based (stored in `storage/framework/cache`)
- **Queue:** Sync (no queue worker needed for simple deployment)
- **Timezone:** Asia/Kolkata (not UTC)

---

## 🆘 Need Help?

If deployment fails:
1. Check Render build logs
2. Check Render runtime logs
3. Check `storage/logs/laravel.log`
4. Verify all environment variables are set
5. Verify database credentials (use Internal URL)

---

**Last Updated:** January 20, 2026
**Status:** Ready for Deployment ✅
