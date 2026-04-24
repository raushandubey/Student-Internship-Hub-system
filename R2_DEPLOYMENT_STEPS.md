# Cloudflare R2 Production Deployment - Action Plan

## 🎯 Current Status

**Local Development**: ✅ Working correctly
- `FILESYSTEM_DISK=public`
- Resumes stored in `storage/app/public/resumes/`
- URLs: `http://localhost:8000/storage/resumes/filename.pdf`

**Production Issue**: ❌ ERR_TOO_MANY_REDIRECTS
- Files upload successfully to R2
- `AWS_URL` incorrectly points to Laravel Cloud domain
- Browser gets stuck in infinite redirect loop

## 🚀 Deployment Steps

### Step 1: Get Your R2 Public Bucket URL

This is the MOST CRITICAL step. You need to get the public bucket URL from Cloudflare.

1. **Login to Cloudflare Dashboard**
   - Go to: https://dash.cloudflare.com/

2. **Navigate to R2**
   - Click on **R2** in the left sidebar
   - You should see your bucket listed

3. **Open Your Bucket Settings**
   - Click on your bucket name
   - Click the **"Settings"** tab

4. **Enable Public Access** (if not already enabled)
   - Scroll down to **"Public access"** section
   - If it says "Public access disabled", click **"Allow Access"**
   - If already enabled, you'll see the public bucket URL

5. **Copy the Public Bucket URL**
   - Look for: **"Public bucket URL:"**
   - Format: `https://pub-{hash}.r2.dev`
   - Example: `https://pub-1234567890abcdef.r2.dev`
   - **COPY THIS URL** - you'll need it for Step 2

**Visual Reference:**
```
┌─────────────────────────────────────────────────┐
│ Public access                                   │
├─────────────────────────────────────────────────┤
│ ✓ Public access enabled                         │
│                                                 │
│ Public bucket URL:                              │
│ https://pub-1234567890abcdef.r2.dev            │  ← COPY THIS!
│                                                 │
│ [Disable Access]                                │
└─────────────────────────────────────────────────┘
```

### Step 2: Update Production .env File

**CRITICAL**: You need to update your production `.env` file with the correct R2 configuration.

**On your production server**, edit your `.env` file:

```bash
# Change filesystem disk to S3 (R2 uses S3 driver)
FILESYSTEM_DISK=s3

# R2 Credentials (you should already have these)
AWS_ACCESS_KEY_ID=your_existing_r2_access_key
AWS_SECRET_ACCESS_KEY=your_existing_r2_secret_key

# CRITICAL: Region MUST be 'auto' for R2 (not us-east-1)
AWS_DEFAULT_REGION=auto

# Your R2 bucket name (you should already have this)
AWS_BUCKET=your_existing_bucket_name

# CRITICAL FIX: Change AWS_URL to R2 public bucket URL
# BEFORE (WRONG): AWS_URL=https://your-laravel-cloud-domain.com
# AFTER (CORRECT): AWS_URL=https://pub-your-hash-here.r2.dev
AWS_URL=https://pub-YOUR-HASH-HERE.r2.dev

# R2 Endpoint (you should already have this)
# Format: https://{account_id}.r2.cloudflarestorage.com
AWS_ENDPOINT=https://your_existing_account_id.r2.cloudflarestorage.com

# CRITICAL: Must be true for R2 (not false)
AWS_USE_PATH_STYLE_ENDPOINT=true
```

**Key Changes:**
1. ✅ `FILESYSTEM_DISK=s3` (not `local`)
2. ✅ `AWS_DEFAULT_REGION=auto` (not `us-east-1`)
3. ✅ `AWS_URL=https://pub-{hash}.r2.dev` (NOT your Laravel domain!)
4. ✅ `AWS_USE_PATH_STYLE_ENDPOINT=true` (not `false`)

### Step 3: Clear All Caches

**On your production server**, run these commands:

```bash
php artisan config:clear
php artisan config:cache
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 4: Verify Configuration

**On your production server**, run the diagnostic script:

```bash
php diagnose-r2-config.php
```

**Expected Output:**
```
✓ FILESYSTEM_DISK is 's3'
✓ AWS_DEFAULT_REGION is 'auto' (correct for R2)
✓ AWS_USE_PATH_STYLE_ENDPOINT is true (correct for R2)
✓ AWS_URL appears to be R2 public bucket URL
✓ URL format is correct (pub-{hash}.r2.dev)
✓ AWS_ENDPOINT is R2 endpoint
✓ Generated URL points to R2
✓ Resume URLs working correctly

✓ CONFIGURATION LOOKS GOOD
```

### Step 5: Test Resume URL Generation

**On your production server**, test URL generation:

```bash
php artisan tinker
```

Then run these commands:

```php
// Test 1: Verify configuration
config('filesystems.default');
// Expected: "s3"

config('filesystems.disks.s3.region');
// Expected: "auto"

config('filesystems.disks.s3.url');
// Expected: "https://pub-{hash}.r2.dev"

// Test 2: Generate test URL
Storage::disk('s3')->url('resumes/test.pdf');
// Expected: "https://pub-{hash}.r2.dev/resumes/test.pdf"

// Test 3: Get actual resume URL
$profile = App\Models\Profile::whereNotNull('resume_path')->first();
if ($profile) {
    $url = $profile->getResumeUrl();
    echo $url;
    // Expected: "https://pub-{hash}.r2.dev/resumes/actual-file.pdf"
}

exit
```

### Step 6: Test in Browser

1. **Copy the resume URL** from Step 5
2. **Open in incognito/private window**
3. **Expected Result**: PDF opens directly
4. **Expected Result**: No redirects
5. **Expected Result**: No authentication required

### Step 7: Test All User Roles

**Admin Panel:**
1. Login as admin
2. Go to Users → Select user with resume
3. Click "View Resume"
4. **Expected**: PDF opens in new tab

**Recruiter Dashboard:**
1. Login as recruiter
2. View application
3. Click "View Profile"
4. Click resume link
5. **Expected**: PDF opens in new tab

**Student Profile:**
1. Login as student with resume
2. Go to Profile
3. Click "View Resume"
4. **Expected**: PDF opens in new tab

## 🔍 Troubleshooting

### Issue: Still Getting ERR_TOO_MANY_REDIRECTS

**Cause**: `AWS_URL` still points to Laravel domain

**Solution**:
```bash
# Check current AWS_URL
php artisan tinker
echo config('filesystems.disks.s3.url');

# If it shows your Laravel domain:
# 1. Update .env with correct R2 public URL
# 2. Clear caches again:
php artisan config:clear
php artisan config:cache
```

### Issue: "Access Denied" or 403 Error

**Cause**: Public access not enabled on R2 bucket

**Solution**:
1. Go to Cloudflare R2 Dashboard
2. Open bucket → Settings
3. Enable "Public access"
4. Copy the public bucket URL
5. Update `AWS_URL` in `.env`

### Issue: URL Returns 404

**Cause**: File doesn't exist in R2

**Solution**:
```bash
php artisan tinker

# List files in bucket
Storage::disk('s3')->files('resumes');

# Check specific file
Storage::disk('s3')->exists('resumes/filename.pdf');
```

### Issue: Configuration Not Taking Effect

**Cause**: Cached configuration

**Solution**:
```bash
# Clear ALL caches
php artisan config:clear
php artisan config:cache
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Restart PHP-FPM (if applicable)
sudo systemctl restart php8.2-fpm
```

## ✅ Success Checklist

- [ ] Got R2 public bucket URL from Cloudflare
- [ ] Updated production `.env` with correct `AWS_URL`
- [ ] Set `AWS_DEFAULT_REGION=auto`
- [ ] Set `AWS_USE_PATH_STYLE_ENDPOINT=true`
- [ ] Cleared all caches
- [ ] Ran diagnostic script - all checks pass
- [ ] Test URL generation returns R2 URL
- [ ] Resume opens in browser without redirects
- [ ] Admin panel shows resumes correctly
- [ ] Recruiter dashboard shows resumes correctly
- [ ] Student profile shows resumes correctly

## 📊 Before vs After

### Before Fix
```
❌ AWS_URL=https://your-laravel-cloud-domain.com
❌ Resume URL: https://your-laravel-cloud-domain.com/resumes/file.pdf
❌ Browser: ERR_TOO_MANY_REDIRECTS
❌ Infinite redirect loop
```

### After Fix
```
✅ AWS_URL=https://pub-1234567890abcdef.r2.dev
✅ Resume URL: https://pub-1234567890abcdef.r2.dev/resumes/file.pdf
✅ Browser: PDF opens directly
✅ Zero redirects, zero errors
```

## 🎯 Key Takeaway

**The ONLY thing you need to change is `AWS_URL` in your production `.env` file.**

Replace:
```bash
AWS_URL=https://your-laravel-cloud-domain.com
```

With:
```bash
AWS_URL=https://pub-your-hash-here.r2.dev
```

Then clear caches and test. That's it!

---

**Need Help?**
- Run: `php diagnose-r2-config.php` to identify issues
- Check: `CLOUDFLARE_R2_DEPLOYMENT_GUIDE.md` for detailed explanations
- Quick Fix: `R2_QUICK_FIX.md` for 5-minute solution
