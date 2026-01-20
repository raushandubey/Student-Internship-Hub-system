# 🚀 RAILWAY DEPLOYMENT - FINAL CHECKLIST

## ✅ PRE-DEPLOYMENT VERIFICATION

### 1. Local Environment Check
- [x] Local app works perfectly
- [x] `.env` configured for local (APP_ENV=local)
- [x] `.env` is in `.gitignore`
- [x] All migrations run successfully locally
- [x] No Docker files present (removed for Railway)

### 2. Railway Configuration Files
- [x] `nixpacks.toml` - Build configuration with MySQL extensions
- [x] `Procfile` - Start command
- [x] `railway.json` - Deployment settings
- [x] `.railwayignore` - Files to exclude from deployment
- [x] `.env.example` - Template with Railway variable format

### 3. Code Quality Checks
- [x] `routes/console.php` - Jobs use deferred instantiation (no build-time errors)
- [x] `composer.json` - No problematic post-autoload scripts
- [x] `/health` endpoint implemented in `routes/web.php`
- [x] Database config uses `mysql` as default
- [x] Session/Cache drivers set to `file` for production

---

## 🎯 DEPLOYMENT STEPS

### STEP 1: Create Railway Project
1. Go to https://railway.app
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Connect your GitHub account
5. Select your repository: `Student-Internship-Hub-system`

### STEP 2: Add MySQL Database
1. In Railway project dashboard
2. Click "+ New"
3. Select "Database" → "MySQL"
4. Wait for provisioning (30-60 seconds)
5. Railway automatically creates these variables:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`

### STEP 3: Configure Environment Variables
1. Click on your web service (not database)
2. Go to "Variables" tab
3. Click "Raw Editor"
4. Paste the following (replace `APP_KEY` with your generated key):

```env
APP_NAME=Student Internship Hub
APP_KEY=base64:H7aEu5IOU0QAE7UIMSf78EHXdMLf1HKyLijhOGlO//I=
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

DB_CONNECTION=mysql
DB_HOST=${MYSQLHOST}
DB_PORT=${MYSQLPORT}
DB_DATABASE=${MYSQLDATABASE}
DB_USERNAME=${MYSQLUSER}
DB_PASSWORD=${MYSQLPASSWORD}

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

LOG_CHANNEL=stack
LOG_LEVEL=error

MAIL_MAILER=log
```

5. Click "Save"

**CRITICAL**: Use `${MYSQLHOST}` format (with curly braces) - Railway will replace these with actual values at runtime.

### STEP 4: Generate New APP_KEY (if needed)
Run locally:
```bash
php artisan key:generate --show
```

Copy the output (e.g., `base64:xxxxx...`) and update `APP_KEY` in Railway variables.

### STEP 5: Deploy
```bash
git add .
git commit -m "Configure for Railway deployment"
git push origin master
```

Railway will automatically:
1. Detect the push
2. Start building using Nixpacks
3. Install PHP 8.2 with MySQL extensions
4. Run `composer install --no-dev --optimize-autoloader`
5. Clear Laravel caches
6. Start the application

**Build time**: 2-5 minutes

### STEP 6: Monitor Deployment
1. Railway Dashboard → Your Service → "Deployments" tab
2. Click on the active deployment
3. Watch the build logs
4. Look for:
   - ✅ `composer install` completes
   - ✅ `php artisan config:clear` succeeds
   - ✅ Application starts on port $PORT

### STEP 7: Run Migrations
**Option A: Via Railway Dashboard**
1. Go to your service → "Settings" tab
2. Scroll to "Deploy" section
3. Add one-time command: `php artisan migrate --force`
4. Click "Deploy"

**Option B: Via Railway CLI**
```bash
railway run php artisan migrate --force
```

### STEP 8: Verify Deployment

#### 8.1 Check Health Endpoint
Visit: `https://your-app.up.railway.app/health`

Expected response:
```json
{
  "status": "healthy",
  "php_version": "8.2.x",
  "pdo_drivers": ["mysql", "sqlite"],
  "pdo_mysql_loaded": true,
  "mysqli_loaded": true,
  "database_connected": true,
  "database_error": null,
  "env_check": {
    "DB_CONNECTION": "mysql",
    "DB_HOST": "actual-mysql-host.railway.internal",
    "DB_PORT": "3306",
    "DB_DATABASE": "railway"
  },
  "timestamp": "2026-01-20T..."
}
```

**If `database_connected: false`**:
- Check environment variables are set correctly
- Verify MySQL service is running
- Check `database_error` field for details

#### 8.2 Test Homepage
Visit: `https://your-app.up.railway.app`

Expected: Welcome page loads without 500 error

#### 8.3 Test Login
Visit: `https://your-app.up.railway.app/login`

Expected: Login form displays correctly

---

## 🔍 TROUBLESHOOTING

### Issue: PDO MySQL Extension Not Found
**Symptoms**: `/health` shows `pdo_mysql_loaded: false`

**Solution**:
1. Verify `nixpacks.toml` includes:
   ```toml
   nixPkgs = ["php82", "php82Extensions.pdo_mysql", "php82Extensions.mysqli"]
   ```
2. Redeploy:
   ```bash
   git commit --allow-empty -m "Trigger rebuild"
   git push origin master
   ```

### Issue: Database Connection Failed
**Symptoms**: `/health` shows `database_connected: false`

**Solutions**:
1. **Check MySQL service is running**:
   - Railway Dashboard → MySQL service → Should show "Active"

2. **Verify environment variables**:
   - Railway Dashboard → Web Service → Variables
   - Ensure `DB_HOST=${MYSQLHOST}` (not literal string)
   - Click "Raw Editor" to see actual format

3. **Check database credentials**:
   - Railway Dashboard → MySQL service → "Variables" tab
   - Copy actual values and test connection

4. **View detailed error**:
   - Check `/health` endpoint → `database_error` field
   - Railway Dashboard → Logs → Look for PDO exceptions

### Issue: 500 Internal Server Error
**Symptoms**: All routes return 500

**Solutions**:
1. **Check APP_KEY is set**:
   ```bash
   railway run php artisan key:generate --show
   ```
   Update in Railway variables

2. **Clear Laravel caches**:
   ```bash
   railway run php artisan config:clear
   railway run php artisan cache:clear
   railway run php artisan route:clear
   railway run php artisan view:clear
   ```

3. **Check logs**:
   - Railway Dashboard → Your Service → "Logs" tab
   - Look for PHP errors, stack traces

4. **Enable debug temporarily**:
   - Set `APP_DEBUG=true` in Railway variables
   - Visit site to see detailed error
   - **IMPORTANT**: Set back to `false` after debugging

### Issue: CSRF Token Mismatch
**Symptoms**: Forms return 419 error

**Solutions**:
1. **Check APP_URL**:
   - Must match actual domain: `https://your-app.up.railway.app`
   - Use `${RAILWAY_PUBLIC_DOMAIN}` for automatic detection

2. **Check session driver**:
   - Must be `SESSION_DRIVER=file` (not database)
   - Verify in Railway variables

3. **Clear browser cookies**:
   - Delete all cookies for the domain
   - Try in incognito mode

### Issue: Build Fails During Composer Install
**Symptoms**: Build logs show class not found errors

**Solutions**:
1. **Check `routes/console.php`**:
   - Jobs must use deferred instantiation
   - Use `Schedule::call(function() { dispatch(new JobClass); })`
   - NOT `Schedule::job(new JobClass)`

2. **Check `composer.json` scripts**:
   - Remove any scripts that require database
   - Avoid `php artisan migrate` in post-install

3. **Verify autoload**:
   ```bash
   composer dump-autoload --optimize
   ```

---

## 📊 POST-DEPLOYMENT TASKS

### 1. Seed Demo Data (Optional)
```bash
railway run php artisan db:seed --class=DemoDataSeeder
```

### 2. Create Admin User
```bash
railway run php artisan db:seed --class=AdminSeeder
```

Default admin credentials:
- Email: `admin@sih.com`
- Password: `admin123`

**IMPORTANT**: Change password immediately after first login!

### 3. Test Core Features
- [ ] Student registration
- [ ] Student login
- [ ] View internships
- [ ] Apply to internship
- [ ] View application tracker
- [ ] Admin login
- [ ] Admin dashboard
- [ ] Admin view applications

### 4. Configure Custom Domain (Optional)
1. Railway Dashboard → Your Service → "Settings"
2. Scroll to "Domains"
3. Click "Add Domain"
4. Follow DNS configuration instructions

### 5. Set Up Monitoring
1. Railway Dashboard → Your Service → "Metrics"
2. Monitor:
   - CPU usage
   - Memory usage
   - Request count
   - Response times

### 6. Configure Scheduled Jobs (Optional)
Railway doesn't support cron natively. Options:

**Option A: External Cron Service**
- Use https://cron-job.org
- Schedule: `https://your-app.up.railway.app/api/cron/run`
- Frequency: Every hour

**Option B: Railway Cron Service**
- Create separate Railway service
- Use `nixpacks.toml` with cron configuration
- Point to main app's database

**Option C: Manual Triggers**
- Run manually via Railway CLI:
  ```bash
  railway run php artisan app:run-jobs-sync
  ```

---

## 🎉 SUCCESS CRITERIA

Your deployment is successful when:

- ✅ `/health` endpoint returns `database_connected: true`
- ✅ Homepage loads without errors
- ✅ Login page displays correctly
- ✅ Student can register and login
- ✅ Student can view internships
- ✅ Student can apply to internships
- ✅ Admin can login
- ✅ Admin dashboard shows analytics
- ✅ No 500 errors in logs
- ✅ No CSRF token errors
- ✅ Sessions persist across requests

---

## 📞 SUPPORT

### Railway Documentation
- https://docs.railway.app
- https://docs.railway.app/guides/nixpacks

### Laravel Documentation
- https://laravel.com/docs/12.x/deployment

### Common Railway Commands
```bash
# Install Railway CLI
npm i -g @railway/cli

# Login
railway login

# Link to project
railway link

# View logs
railway logs

# Run command
railway run php artisan migrate --force

# Open shell
railway shell

# View environment variables
railway variables
```

---

## 🔒 SECURITY CHECKLIST

Before going live:

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure
- [ ] Admin password changed from default
- [ ] HTTPS enabled (Railway provides by default)
- [ ] CSRF protection enabled (Laravel default)
- [ ] Rate limiting configured (already in routes)
- [ ] File upload validation (already implemented)
- [ ] SQL injection protection (Laravel Eloquent default)

---

## 📝 NOTES

- Railway provides **$5 free credit per month**
- MySQL database is included in free tier
- Automatic HTTPS with custom domains
- Zero-downtime deployments
- Automatic scaling (paid plans)
- Built-in metrics and logging

**Estimated monthly cost**: $0-5 (within free tier for small projects)

---

**Last Updated**: January 20, 2026
**Status**: Ready for deployment ✅
