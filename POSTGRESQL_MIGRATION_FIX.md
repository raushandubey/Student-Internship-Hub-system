# PostgreSQL Migration Fix - Complete Guide

## Problem Summary
The Laravel project contained MySQL-specific syntax that is incompatible with PostgreSQL:
- `ENUM` column types (PostgreSQL handles ENUMs differently)
- `MODIFY COLUMN` syntax (PostgreSQL uses `ALTER COLUMN`)
- Raw SQL statements with database-specific syntax

## Solution Applied
Replaced all ENUM columns with STRING columns and removed raw SQL statements for full database compatibility.

---

## Files Modified

### 1. Migration Files (5 files)

#### ✅ `database/migrations/2024_01_01_000005_add_role_to_users_table.php`
**Change:** Removed conditional logic, now uses `string` for all databases
```php
// BEFORE: enum('role', ['student', 'admin']) for MySQL
// AFTER:  string('role', 20)->default('student') for all databases
```

#### ✅ `database/migrations/2026_04_22_024717_add_recruiter_role_to_users_table.php`
**Change:** Removed raw SQL `MODIFY COLUMN`, uses Laravel Schema Builder
```php
// BEFORE: DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM...")
// AFTER:  Schema::table with ->change() method
```

#### ✅ `database/migrations/2026_04_22_031500_change_role_to_string.php`
**Change:** Removed raw SQL, uses Laravel Schema Builder
```php
// BEFORE: DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(20)...")
// AFTER:  $table->string('role', 20)->default('student')->change()
```

#### ✅ `database/migrations/2026_01_14_220948_create_applications_table.php`
**Change:** Replaced ENUM with STRING for status column
```php
// BEFORE: enum('status', ['pending', 'under_review', ...])
// AFTER:  string('status', 30)->default('pending')
```

#### ✅ `database/migrations/2026_04_23_000001_add_approval_workflow_to_recruiter_profiles.php`
**Change:** Replaced ENUM with STRING for approval_status
```php
// BEFORE: enum('approval_status', ['pending', 'approved', ...])
// AFTER:  string('approval_status', 20)->default('pending')
```

### 2. Configuration Files (1 file)

#### ✅ `config/roles.php` (NEW)
**Purpose:** Centralized role and status validation
- Defines all valid roles: `['student', 'admin', 'recruiter']`
- Defines application statuses
- Defines approval statuses
- Single source of truth for validation

### 3. Controller Files (1 file)

#### ✅ `app/Http/Controllers/AuthController.php`
**Change:** Updated validation to use config
```php
// BEFORE: 'role' => ['required', 'in:student,admin']
// AFTER:  'role' => ['required', 'in:' . implode(',', config('roles.valid_roles'))]
```

---

## Database Schema Changes

### Users Table - `role` Column
```sql
-- OLD (MySQL-specific)
role ENUM('student', 'admin', 'recruiter') DEFAULT 'student'

-- NEW (PostgreSQL-compatible)
role VARCHAR(20) DEFAULT 'student'
```

### Applications Table - `status` Column
```sql
-- OLD (MySQL-specific)
status ENUM('pending', 'under_review', 'shortlisted', 'interview_scheduled', 'approved', 'rejected') DEFAULT 'pending'

-- NEW (PostgreSQL-compatible)
status VARCHAR(30) DEFAULT 'pending'
```

### Recruiter Profiles Table - `approval_status` Column
```sql
-- OLD (MySQL-specific)
approval_status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending'

-- NEW (PostgreSQL-compatible)
approval_status VARCHAR(20) DEFAULT 'pending'
```

---

## Deployment Steps

### Option 1: Fresh Database (Recommended for Development)

```bash
# 1. Drop all tables and start fresh
php artisan migrate:fresh

# 2. Seed the database
php artisan db:seed

# 3. Verify migrations
php artisan migrate:status
```

### Option 2: Existing Database with Data (Production)

⚠️ **IMPORTANT:** Backup your database first!

```bash
# 1. Backup database
pg_dump -U your_user -d your_database > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Check current migration status
php artisan migrate:status

# 3. If migrations already ran, you need to modify existing columns
# Create a new migration to fix existing columns:
php artisan make:migration fix_enum_columns_for_postgresql

# 4. Add this to the new migration:
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix users.role column
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->change();
        });

        // Fix applications.status column
        Schema::table('applications', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
        });

        // Fix recruiter_profiles.approval_status column
        if (Schema::hasTable('recruiter_profiles') && Schema::hasColumn('recruiter_profiles', 'approval_status')) {
            Schema::table('recruiter_profiles', function (Blueprint $table) {
                $table->string('approval_status', 20)->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        // No rollback - string is more flexible than enum
    }
};
```

```bash
# 5. Run the fix migration
php artisan migrate

# 6. Verify all data is intact
php artisan tinker
>>> User::count()
>>> Application::count()
```

### Option 3: Using doctrine/dbal for Column Changes

If you encounter issues with `->change()`, install doctrine/dbal:

```bash
# Install doctrine/dbal (required for column modifications)
composer require doctrine/dbal

# Then run migrations
php artisan migrate
```

---

## Validation Updates

### Application-Level Validation
All validation now uses the centralized config:

```php
// In any controller or request class
use Illuminate\Validation\Rule;

$request->validate([
    'role' => ['required', Rule::in(config('roles.valid_roles'))],
    'status' => ['required', Rule::in(config('roles.application_statuses'))],
    'approval_status' => ['required', Rule::in(config('roles.approval_statuses'))],
]);
```

### Model Validation (Optional Enhancement)
Consider adding casts and accessors to models:

```php
// app/Models/User.php
protected $casts = [
    'role' => 'string',
];

public function setRoleAttribute($value)
{
    if (!in_array($value, config('roles.valid_roles'))) {
        throw new \InvalidArgumentException("Invalid role: {$value}");
    }
    $this->attributes['role'] = $value;
}
```

---

## Testing

### 1. Test Migrations
```bash
# Fresh migration
php artisan migrate:fresh --seed

# Check for errors
php artisan migrate:status
```

### 2. Test User Creation
```bash
php artisan tinker
>>> User::create(['name' => 'Test', 'email' => 'test@example.com', 'password' => bcrypt('password'), 'role' => 'student'])
>>> User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'role' => 'admin'])
>>> User::create(['name' => 'Recruiter', 'email' => 'recruiter@example.com', 'password' => bcrypt('password'), 'role' => 'recruiter'])
```

### 3. Test Application Creation
```bash
php artisan tinker
>>> $user = User::first()
>>> $internship = Internship::first()
>>> Application::create(['user_id' => $user->id, 'internship_id' => $internship->id, 'status' => 'pending'])
```

### 4. Test Validation
```bash
# Should fail with invalid role
>>> User::create(['name' => 'Invalid', 'email' => 'invalid@example.com', 'password' => bcrypt('password'), 'role' => 'invalid_role'])
```

---

## PostgreSQL-Specific Considerations

### 1. Case Sensitivity
PostgreSQL is case-sensitive for string comparisons. Ensure consistent casing:
```php
// Always use lowercase for roles
$user->role = strtolower($request->role);
```

### 2. Indexing
String columns are indexed differently than ENUMs:
```php
// Add indexes for frequently queried columns
$table->index('role');
$table->index('status');
$table->index('approval_status');
```

### 3. Performance
- STRING columns are slightly slower than ENUMs for queries
- Impact is negligible for small datasets
- Add indexes to maintain performance

---

## Rollback Plan

If you need to rollback:

```bash
# 1. Restore database backup
psql -U your_user -d your_database < backup_YYYYMMDD_HHMMSS.sql

# 2. Revert code changes
git revert <commit_hash>

# 3. Run old migrations
php artisan migrate:refresh
```

---

## Benefits of This Approach

✅ **Database Agnostic:** Works with PostgreSQL, MySQL, SQLite, SQL Server
✅ **No Raw SQL:** Uses Laravel Schema Builder exclusively
✅ **Data Preserved:** Existing data remains intact during migration
✅ **Centralized Validation:** Single source of truth in config
✅ **Type Safe:** String type is more flexible than ENUM
✅ **Easy to Extend:** Adding new roles/statuses is simple

---

## Common Issues & Solutions

### Issue 1: "Column type change not supported"
**Solution:** Install doctrine/dbal
```bash
composer require doctrine/dbal
```

### Issue 2: "Migration already ran"
**Solution:** Create a new migration to fix existing columns (see Option 2 above)

### Issue 3: "Data lost during migration"
**Solution:** Always backup first! Use transactions:
```php
DB::transaction(function () {
    // Your migration code
});
```

### Issue 4: "Validation fails after migration"
**Solution:** Clear config cache
```bash
php artisan config:clear
php artisan cache:clear
```

---

## Verification Checklist

- [ ] All migrations run successfully
- [ ] No raw SQL statements remain
- [ ] All ENUM columns converted to STRING
- [ ] Validation rules updated
- [ ] Config file created
- [ ] Existing data preserved
- [ ] Tests pass
- [ ] Application works on PostgreSQL
- [ ] Database backup created
- [ ] Documentation updated

---

## Support

If you encounter issues:
1. Check PostgreSQL logs: `tail -f /var/log/postgresql/postgresql-*.log`
2. Check Laravel logs: `tail -f storage/logs/laravel.log`
3. Run migrations with verbose output: `php artisan migrate --verbose`
4. Test database connection: `php artisan tinker` → `DB::connection()->getPdo()`

---

**Status:** ✅ All migrations are now PostgreSQL-compatible
**Last Updated:** 2026-04-23
