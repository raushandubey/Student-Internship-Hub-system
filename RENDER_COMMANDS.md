# Render Deployment Commands Reference

## 🚀 Deployment Commands

### Initial Deployment
```bash
# 1. Commit and push changes
git add .
git commit -m "Fix: Resolve 500 error - move cache to runtime"
git push origin main

# 2. Deploy on Render (automatic or manual trigger)
# Go to Render Dashboard → Manual Deploy → Deploy latest commit
```

---

## 🔧 Post-Deployment Commands

### Run Migrations
```bash
# From Render Shell
php artisan migrate --force
```

### Seed Demo Data
```bash
# Seed demo data (optional)
php artisan db:seed --class=DemoDataSeeder

# Create admin user
php artisan db:seed --class=AdminSeeder
```

### Clear Cache (if needed)
```bash
# Clear all cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Create Storage Link
```bash
php artisan storage:link
```

---

## 🔍 Debugging Commands

### Check Laravel Status
```bash
# Check Laravel version
php artisan --version

# Check environment
php artisan env

# List all routes
php artisan route:list

# Check config
php artisan config:show app
php artisan config:show database
```

### Test Database Connection
```bash
# Using tinker
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::table('users')->count();
>>> exit

# Using artisan
php artisan db:show
```

### Check Permissions
```bash
# Check storage permissions
ls -la storage/
ls -la storage/logs/
ls -la storage/framework/

# Check bootstrap cache permissions
ls -la bootstrap/cache/

# Fix permissions if needed
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

### Check Processes
```bash
# Check PHP-FPM
ps aux | grep php-fpm

# Check Nginx
ps aux | grep nginx

# Check Supervisor
ps aux | grep supervisor

# Check all processes
ps aux
```

### Check Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log
cat storage/logs/laravel.log | grep ERROR

# Nginx access logs
tail -f /var/log/nginx/access.log

# Nginx error logs
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php-fpm.log

# Supervisor logs
tail -f /var/log/supervisor/supervisord.log
```

### Check Configuration
```bash
# Check Nginx config
cat /etc/nginx/nginx.conf
nginx -t  # Test config syntax

# Check PHP-FPM config
cat /usr/local/etc/php-fpm.conf
php-fpm -t  # Test config syntax

# Check Supervisor config
cat /etc/supervisor/conf.d/supervisord.conf
```

---

## 🧪 Testing Commands

### Test Endpoints
```bash
# Test health endpoint
curl http://localhost:10000/health

# Test homepage
curl -I http://localhost:10000/

# Test login page
curl -I http://localhost:10000/login

# Test API endpoint (if applicable)
curl -I http://localhost:10000/api/v1/applications
```

### Test from Outside
```bash
# Test from your local machine
curl https://your-app.onrender.com/health
curl -I https://your-app.onrender.com/
curl -I https://your-app.onrender.com/login
```

---

## 🔄 Restart Commands

### Restart Services
```bash
# Restart PHP-FPM
supervisorctl restart php-fpm

# Restart Nginx
supervisorctl restart nginx

# Restart all services
supervisorctl restart all

# Check service status
supervisorctl status
```

### Full Container Restart
```bash
# From Render Dashboard
# Go to your service → Settings → Manual Deploy → Restart Service
```

---

## 📊 Monitoring Commands

### Check Resource Usage
```bash
# Check memory usage
free -h

# Check disk usage
df -h

# Check CPU usage
top

# Check process memory
ps aux --sort=-%mem | head -10
```

### Check Application Metrics
```bash
# Count users
php artisan tinker
>>> DB::table('users')->count();

# Count applications
>>> DB::table('applications')->count();

# Count internships
>>> DB::table('internships')->count();

# Check recent applications
>>> DB::table('applications')->latest()->take(5)->get();
```

---

## 🛠️ Maintenance Commands

### Optimize Application
```bash
# Optimize autoloader
composer dump-autoload --optimize

# Cache everything
php artisan optimize

# Clear and cache
php artisan optimize:clear
php artisan optimize
```

### Queue Management (if using queues)
```bash
# Process queue jobs
php artisan queue:work --once

# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Schedule Management (if using scheduler)
```bash
# Run scheduled tasks manually
php artisan schedule:run

# List scheduled tasks
php artisan schedule:list
```

---

## 🔐 Security Commands

### Generate New APP_KEY
```bash
# Generate new key (show only, don't update .env)
php artisan key:generate --show

# Copy output and update Render environment variables
```

### Check Security
```bash
# Check for security vulnerabilities
composer audit

# Update dependencies
composer update --with-all-dependencies
```

---

## 📝 Database Commands

### Run Migrations
```bash
# Run all pending migrations
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback --step=1

# Reset database (⚠️ DESTRUCTIVE)
php artisan migrate:fresh --force

# Reset and seed
php artisan migrate:fresh --seed --force
```

### Seed Database
```bash
# Run all seeders
php artisan db:seed --force

# Run specific seeder
php artisan db:seed --class=AdminSeeder --force
php artisan db:seed --class=DemoDataSeeder --force
php artisan db:seed --class=InternshipSeeder --force
```

### Backup Database
```bash
# Export database (if pg_dump available)
pg_dump -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE > backup.sql

# Import database
psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE < backup.sql
```

---

## 🎯 Quick Troubleshooting

### If 500 Error Persists
```bash
# 1. Check Laravel logs
tail -f storage/logs/laravel.log

# 2. Check APP_KEY
php artisan tinker
>>> config('app.key');

# 3. Test database connection
>>> DB::connection()->getPdo();

# 4. Clear all cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Check permissions
ls -la storage/ bootstrap/cache/

# 7. Test Laravel boot
php artisan --version
```

### If Container Crashes
```bash
# 1. Check startup logs in Render Dashboard

# 2. Check environment variables
echo $APP_KEY
echo $DB_HOST

# 3. Test start script manually
bash /start.sh

# 4. Check supervisor status
supervisorctl status
```

---

## 📞 Emergency Recovery

### If Everything Fails
```bash
# 1. Clear all cache files manually
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# 2. Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Fix permissions
chown -R www-data:www-data /var/www/html
chmod -R 775 storage/ bootstrap/cache/

# 4. Restart services
supervisorctl restart all

# 5. Test Laravel boot
php artisan --version
```

---

## 🔗 Useful Render CLI Commands

### Install Render CLI (optional)
```bash
# Install
npm install -g @render-com/cli

# Login
render login

# List services
render services list

# View logs
render logs <service-id>

# Deploy
render deploy <service-id>
```

---

**Last Updated**: January 19, 2026
**Status**: Production-ready
