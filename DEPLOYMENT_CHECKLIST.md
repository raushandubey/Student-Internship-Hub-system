# PostgreSQL Deployment Checklist

## Pre-Deployment

### 1. Backup Current Database
```bash
# If migrating from MySQL
mysqldump -u user -p database > backup_mysql_$(date +%Y%m%d_%H%M%S).sql

# If already on PostgreSQL
pg_dump -U user -d database > backup_postgres_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Update Environment Configuration
```bash
# Edit .env file
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Verify Dependencies
```bash
# Check composer.json includes doctrine/dbal
composer show doctrine/dbal

# If not installed
composer require doctrine/dbal
```

## Deployment Steps

### Option A: Fresh Database (Recommended for New Deployments)

```bash
# 1. Clear config cache
php artisan config:clear

# 2. Run fresh migrations
php artisan migrate:fresh

# 3. Seed database
php artisan db:seed

# 4. Verify
php artisan migrate:status
```

### Option B: Existing Database with Data

```bash
# 1. Clear config cache
php artisan config:clear

# 2. Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
>>> exit

# 3. Run new fix migration
php artisan migrate

# 4. Verify all migrations ran
php artisan migrate:status

# 5. Check data integrity
php artisan tinker
>>> User::count()
>>> Application::count()
>>> Internship::count()
```

## Post-Deployment Verification

### 1. Test User Operations
```bash
php artisan tinker

# Test user creation
>>> User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password'), 'role' => 'student'])

# Test role validation
>>> User::where('role', 'student')->count()
>>> User::where('role', 'admin')->count()
>>> User::where('role', 'recruiter')->count()
```

### 2. Test Application Operations
```bash
php artisan tinker

# Test application creation
>>> $user = User::first()
>>> $internship = Internship::first()
>>> Application::create(['user_id' => $user->id, 'internship_id' => $internship->id, 'status' => 'pending'])

# Test status queries
>>> Application::where('status', 'pending')->count()
```

### 3. Test Recruiter Profile Operations
```bash
php artisan tinker

# Test recruiter profile
>>> $recruiter = User::where('role', 'recruiter')->first()
>>> $recruiter->recruiterProfile
>>> $recruiter->recruiterProfile->approval_status
```

### 4. Test Web Application
- [ ] Login as student
- [ ] Login as admin
- [ ] Login as recruiter
- [ ] Create application
- [ ] View applications
- [ ] Update application status
- [ ] Approve/reject recruiter

## Performance Optimization

```bash
# 1. Optimize autoloader
composer install --optimize-autoloader --no-dev

# 2. Cache configuration
php artisan config:cache

# 3. Cache routes
php artisan route:cache

# 4. Cache views
php artisan view:cache

# 5. Optimize database
php artisan optimize
```

## Monitoring

### Check Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# PostgreSQL logs (location varies by system)
tail -f /var/log/postgresql/postgresql-*.log
```

### Database Performance
```sql
-- Check table sizes
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

-- Check indexes
SELECT 
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;
```

## Rollback Plan

If deployment fails:

```bash
# 1. Restore database backup
pg_dump -U user -d database < backup_postgres_YYYYMMDD_HHMMSS.sql

# 2. Revert code
git revert <commit_hash>

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Common Issues

### Issue: "SQLSTATE[42P01]: Undefined table"
**Solution:** Run migrations
```bash
php artisan migrate
```

### Issue: "SQLSTATE[42703]: Undefined column"
**Solution:** Run fix migration
```bash
php artisan migrate --path=database/migrations/2026_04_23_200058_fix_enum_columns_for_postgresql_compatibility.php
```

### Issue: "Class 'Doctrine\DBAL\Driver\PDO\PgSQL\Driver' not found"
**Solution:** Install doctrine/dbal
```bash
composer require doctrine/dbal
```

### Issue: "Column type change not supported"
**Solution:** Ensure doctrine/dbal is installed and clear cache
```bash
composer require doctrine/dbal
php artisan config:clear
php artisan migrate
```

## Success Criteria

- [ ] All migrations run successfully
- [ ] No errors in logs
- [ ] Users can login
- [ ] Applications can be created
- [ ] Recruiters can be approved
- [ ] All roles work correctly
- [ ] Performance is acceptable
- [ ] Backup created and verified

## Support Contacts

- Database Admin: [contact]
- DevOps Team: [contact]
- Development Team: [contact]

---

**Deployment Date:** _____________
**Deployed By:** _____________
**Sign-off:** _____________
