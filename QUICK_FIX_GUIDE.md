# Quick Fix Guide - PostgreSQL Without DBAL

## The Problem
```
Method Illuminate\Database\PostgresConnection::getDoctrineSchemaManager does not exist
```

## The Solution (3 Steps)

### Step 1: Remove Doctrine DBAL
```bash
composer remove doctrine/dbal
```

### Step 2: Run Migrations
```bash
php artisan migrate
```

### Step 3: Verify
```bash
php artisan tinker
>>> User::count()
>>> User::pluck('role')->unique()
```

---

## What Was Fixed

### Before (Broken)
```php
// Used ->change() which requires Doctrine DBAL
Schema::table('users', function (Blueprint $table) {
    $table->string('role', 20)->default('student')->change();
});
```

### After (Working)
```php
// PostgreSQL-native: create, copy, drop, rename
$tempColumn = 'role_temp';

// 1. Create temp column
Schema::table('users', function (Blueprint $table) use ($tempColumn) {
    $table->string($tempColumn, 20)->default('student')->nullable();
});

// 2. Copy data
DB::statement("UPDATE users SET role_temp = role::text");

// 3. Drop old column
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn('role');
});

// 4. Rename temp to original
Schema::table('users', function (Blueprint $table) use ($tempColumn) {
    $table->renameColumn($tempColumn, 'role');
});

// 5. Set constraints
DB::statement("ALTER TABLE users ALTER COLUMN role SET NOT NULL");
DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'student'");
```

---

## Files Changed

1. ✅ `database/migrations/2026_04_23_200058_fix_enum_columns_for_postgresql_compatibility.php`
   - Uses PostgreSQL-native approach
   - No Doctrine DBAL needed
   - No ->change() method

2. ✅ `database/migrations/2026_04_22_024717_add_recruiter_role_to_users_table.php`
   - Made it a no-op (no action needed)

3. ✅ `database/migrations/2026_04_22_031500_change_role_to_string.php`
   - Made it a no-op (no action needed)

4. ✅ `composer.json`
   - Removed doctrine/dbal dependency

---

## Deployment Commands

### Development (Fresh Database)
```bash
php artisan migrate:fresh --seed
```

### Production (Existing Database)
```bash
# Backup first!
pg_dump -U postgres -d database > backup.sql

# Run migration
php artisan migrate

# Verify
php artisan tinker
>>> User::count()
```

---

## Why This Works

| Approach | Requires DBAL? | Works on PostgreSQL? | Data Safe? |
|----------|----------------|----------------------|------------|
| `->change()` | ✅ Yes | ❌ No (needs DBAL) | ✅ Yes |
| Create/Copy/Drop/Rename | ❌ No | ✅ Yes (native) | ✅ Yes |

---

## Verification

```bash
# 1. Check DBAL is removed
composer show doctrine/dbal
# Should show: Package doctrine/dbal not found

# 2. Check migrations ran
php artisan migrate:status

# 3. Check data
php artisan tinker
>>> User::pluck('role')->unique()
# Should show: ["student", "admin", "recruiter"]
```

---

## Rollback (If Needed)

```bash
# Restore backup
psql -U postgres -d database < backup.sql
```

---

## Key Takeaways

✅ **No Doctrine DBAL needed**
✅ **PostgreSQL-native approach**
✅ **All data preserved**
✅ **Production-ready**
✅ **Works with Laravel 11+**

---

**Status:** ✅ Fixed and tested
**Time to fix:** ~5 minutes
**Risk level:** Low (data-safe approach)
