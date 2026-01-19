# RENDER BUILD FIX - Class Not Found Error

## ROOT CAUSE (EXACT)

The Dockerfile had an incorrect build sequence that caused `composer run-script post-autoload-dump` to fail on Linux with error: **"Class 'App\Jobs\MarkStaleApplications' not found"**

### Why It Failed

**Original Dockerfile sequence (BROKEN)**:
```dockerfile
1. COPY composer.json composer.lock
2. RUN composer install --optimize-autoloader --no-scripts
   → Generates autoloader WITHOUT application code
3. COPY . .
   → Copies app/Jobs/MarkStaleApplications.php
4. RUN composer run-script post-autoload-dump
   → Runs php artisan package:discover
   → Bootstraps Laravel
   → Loads routes/console.php
   → Imports "use App\Jobs\MarkStaleApplications"
   → FAILS: Autoloader doesn't know about this class (generated before code was copied)
```

**Why it worked locally**:
- Local autoloader was generated WITH all files present
- Windows filesystem is case-insensitive and more forgiving
- Not running exact Docker build sequence

**Why it fails on Render (Linux)**:
- Linux filesystem is case-sensitive and strict
- Autoloader generated before application code exists
- `composer install --optimize-autoloader` creates classmap WITHOUT `App\Jobs\MarkStaleApplications`
- When Laravel bootstraps during `post-autoload-dump`, it can't find the class

---

## EXACT FIX APPLIED

**File Modified**: `Dockerfile`

**Changed build sequence**:
```dockerfile
1. COPY composer.json composer.lock
2. RUN composer install --no-scripts (WITHOUT --optimize-autoloader)
   → Installs dependencies only
3. COPY . .
   → Copies ALL application code including Jobs
4. RUN composer dump-autoload --optimize --no-dev
   → Generates optimized autoloader WITH all application classes
5. RUN composer run-script post-autoload-dump
   → Now autoloader knows about App\Jobs\MarkStaleApplications
   → Laravel can bootstrap successfully
```

**Key changes**:
- Removed `--optimize-autoloader` from initial `composer install`
- Added explicit `composer dump-autoload --optimize --no-dev` AFTER copying code
- Ensures autoloader is generated with complete application structure

---

## GIT CHANGES REQUIRED

```bash
# 1. Verify the fix
git diff Dockerfile

# 2. Commit the fix
git add Dockerfile
git commit -m "fix: Correct Dockerfile build sequence for autoloader generation

- Move autoloader optimization after COPY . .
- Ensures autoloader knows about all application classes
- Fixes 'Class App\Jobs\MarkStaleApplications not found' on Render
- Linux filesystem requires strict autoload sequence"

# 3. Push to trigger Render deployment
git push origin main
```

---

## CONFIRMATION CHECKLIST

Before pushing to Render, verify:

### ✅ Code Verification
- [x] `app/Jobs/MarkStaleApplications.php` exists
- [x] Namespace is `namespace App\Jobs;`
- [x] Class name matches filename exactly (case-sensitive)
- [x] `routes/console.php` imports `use App\Jobs\MarkStaleApplications;`
- [x] Dockerfile build sequence corrected

### ✅ Dockerfile Sequence (CRITICAL)
```dockerfile
Line 57-59: COPY composer.json composer.lock ./
Line 61-66: RUN composer install --no-scripts (NO --optimize-autoloader)
Line 68-69: COPY . .
Line 71-72: RUN composer dump-autoload --optimize --no-dev
Line 74-75: RUN composer run-script post-autoload-dump
```

### ✅ Build Will Succeed When
1. `composer install` completes without errors
2. Application code is copied
3. `composer dump-autoload` generates classmap with `App\Jobs\MarkStaleApplications`
4. `composer run-script post-autoload-dump` runs successfully
5. `php artisan package:discover` can bootstrap Laravel
6. `routes/console.php` can import `App\Jobs\MarkStaleApplications`
7. Build exits with status 0 (success)

### ✅ Render Environment Variables (Already Set)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY=base64:...`
- Database credentials
- Session/cache drivers

---

## TECHNICAL EXPLANATION

### Composer Autoloader Generation

**PSR-4 Autoloading**:
```json
"autoload": {
    "psr-4": {
        "App\\": "app/"
    }
}
```

**When `composer dump-autoload --optimize` runs**:
1. Scans `app/` directory for all PHP files
2. Builds classmap: `App\Jobs\MarkStaleApplications` → `app/Jobs/MarkStaleApplications.php`
3. Generates `vendor/composer/autoload_classmap.php`
4. Optimized autoloader uses classmap (faster than file scanning)

**The Problem**:
- If autoloader is generated BEFORE `app/Jobs/MarkStaleApplications.php` exists
- Classmap doesn't include `App\Jobs\MarkStaleApplications`
- When Laravel tries to load the class, autoloader returns "not found"

**The Solution**:
- Generate autoloader AFTER all application code is present
- Classmap includes ALL application classes
- Laravel can load any class successfully

### Linux vs Windows Filesystem

**Linux (Render)**:
- Case-sensitive: `MarkStaleApplications.php` ≠ `markstaleapplications.php`
- Strict autoloading: Exact filename match required
- No fallback mechanisms

**Windows (Local)**:
- Case-insensitive: `MarkStaleApplications.php` = `markstaleapplications.php`
- More forgiving: Can find files with case mismatches
- Autoloader more lenient

**This is why it worked locally but failed on Render.**

---

## VERIFICATION AFTER DEPLOYMENT

Once Render build completes:

### 1. Check Build Logs
```
✓ composer install completes
✓ Application code copied
✓ composer dump-autoload completes
✓ composer run-script post-autoload-dump completes
✓ php artisan package:discover completes
✓ Build exits with status 0
```

### 2. Check Application
```bash
# SSH into Render container (if available)
php artisan list | grep app:

# Should show:
# app:mark-stale
# app:daily-summary
# app:run-jobs-sync
```

### 3. Verify Autoloader
```bash
# Check classmap includes the job
grep -r "MarkStaleApplications" vendor/composer/autoload_classmap.php

# Should show:
# 'App\\Jobs\\MarkStaleApplications' => $baseDir . '/app/Jobs/MarkStaleApplications.php',
```

---

## IF BUILD STILL FAILS

### Check These (In Order)

1. **Verify Dockerfile changes committed**:
   ```bash
   git log -1 --name-only
   # Should show: Dockerfile
   ```

2. **Verify exact build sequence in Dockerfile**:
   ```bash
   grep -A 20 "Copy composer files" Dockerfile
   ```

3. **Check Render build logs for exact error**:
   - Look for line where it fails
   - Check if it's still during `post-autoload-dump`
   - Check if error message changed

4. **Verify file exists in repository**:
   ```bash
   git ls-files | grep MarkStaleApplications
   # Should show: app/Jobs/MarkStaleApplications.php
   ```

5. **Check for .gitignore issues**:
   ```bash
   cat .gitignore | grep -i jobs
   # Should NOT show: app/Jobs/
   ```

---

## SUMMARY

**Problem**: Autoloader generated before application code existed
**Solution**: Generate autoloader AFTER copying application code
**File Changed**: `Dockerfile` (build sequence corrected)
**Expected Result**: Build succeeds, application deploys successfully

**Next Step**: Commit and push the Dockerfile change to trigger Render deployment.
