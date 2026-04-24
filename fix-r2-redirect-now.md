# FIX R2 REDIRECT ISSUE - IMMEDIATE ACTION REQUIRED

## 🚨 Problem Confirmed

**Issue**: Resume links redirect to your Laravel website instead of showing the PDF  
**Cause**: `AWS_URL` in production `.env` points to Laravel domain instead of R2 public URL  
**Result**: Infinite redirect loop (ERR_TOO_MANY_REDIRECTS)

## ⚡ IMMEDIATE FIX (5 Minutes)

### Step 1: Get Your R2 Public Bucket URL

1. **Login to Cloudflare Dashboard**: https://dash.cloudflare.com/
2. **Go to R2** (left sidebar)
3. **Click your bucket name**
4. **Click "Settings" tab**
5. **Scroll to "Public access" section**
6. **Look for "Public bucket URL"**
   - Format: `https://pub-{hash}.r2.dev`
   - Example: `https://pub-1234567890abcdef.r2.dev`
7. **COPY THIS URL** ← You need this!

**If you don't see a public URL**:
- Click "Allow Access" button to enable public access
- The public URL will appear after enabling

### Step 2: Update Production .env File

**On your production server**, edit your `.env` file:

```bash
# SSH into your production server
ssh your-server

# Edit .env file
nano .env
# or
vi .env
```

**Find and change these lines**:

```bash
# BEFORE (WRONG - causes redirects):
AWS_URL=https://your-laravel-domain.com

# AFTER (CORRECT - paste your R2 public URL):
AWS_URL=https://pub-YOUR-HASH-HERE.r2.dev
```

**Also verify these settings**:

```bash
# Must be exactly these values for R2:
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=auto
AWS_USE_PATH_STYLE_ENDPOINT=true
```

**Save the file** (Ctrl+X, then Y, then Enter in nano)

### Step 3: Clear All Caches

**On your production server**, run:

```bash
php artisan config:clear
php artisan config:cache
php artisan cache:clear
```

### Step 4: Test Immediately

1. **Go to your production website**
2. **Login and try to view a resume**
3. **Expected**: PDF opens directly (no redirects!)

## 🎯 What This Fixes

### Before Fix
```
User clicks "View Resume"
    ↓
URL: https://your-laravel-domain.com/resumes/file.pdf
    ↓
Browser requests Laravel app
    ↓
Laravel redirects → Browser redirects → Infinite loop
    ↓
❌ ERR_TOO_MANY_REDIRECTS
```

### After Fix
```
User clicks "View Resume"
    ↓
URL: https://pub-{hash}.r2.dev/resumes/file.pdf
    ↓
Browser requests R2 directly
    ↓
R2 serves PDF file
    ↓
✅ PDF opens instantly (zero redirects)
```

## 📋 Quick Checklist

- [ ] Got R2 public bucket URL from Cloudflare
- [ ] Updated `AWS_URL` in production `.env`
- [ ] Verified `AWS_DEFAULT_REGION=auto`
- [ ] Verified `AWS_USE_PATH_STYLE_ENDPOINT=true`
- [ ] Cleared all caches
- [ ] Tested resume access
- [ ] PDF opens without redirects

## 🔍 Verify the Fix

**Run this on your production server**:

```bash
php artisan tinker
```

Then:

```php
// Check AWS_URL
echo config('filesystems.disks.s3.url');
// Should output: https://pub-{hash}.r2.dev
// Should NOT output: your Laravel domain

// Test URL generation
Storage::disk('s3')->url('resumes/test.pdf');
// Should output: https://pub-{hash}.r2.dev/resumes/test.pdf

exit
```

## 🚨 Common Mistakes

❌ **WRONG**: `AWS_URL=https://your-laravel-domain.com`  
❌ **WRONG**: `AWS_URL=https://account-id.r2.cloudflarestorage.com`  
❌ **WRONG**: `AWS_DEFAULT_REGION=us-east-1`  
❌ **WRONG**: `AWS_USE_PATH_STYLE_ENDPOINT=false`  

✅ **CORRECT**: `AWS_URL=https://pub-1234567890abcdef.r2.dev`  
✅ **CORRECT**: `AWS_DEFAULT_REGION=auto`  
✅ **CORRECT**: `AWS_USE_PATH_STYLE_ENDPOINT=true`  

## 📞 Need Help?

### Can't Find R2 Public URL?

**Screenshot of where to find it**:
```
Cloudflare Dashboard
    → R2 (left sidebar)
    → Click your bucket
    → Settings tab
    → Scroll down to "Public access"
    → Look for "Public bucket URL: https://pub-..."
```

### Still Getting Redirects After Fix?

1. **Clear browser cache** (Ctrl+Shift+Delete)
2. **Try incognito mode**
3. **Verify caches cleared on server**:
   ```bash
   php artisan config:clear
   php artisan config:cache
   php artisan cache:clear
   ```
4. **Check AWS_URL again**:
   ```bash
   php artisan tinker
   echo config('filesystems.disks.s3.url');
   ```

### Public Access Not Enabled?

If you can't enable public access on your R2 bucket:
1. Go to Cloudflare Dashboard → R2
2. Click your bucket → Settings
3. Find "Public access" section
4. Click "Allow Access" button
5. Confirm the action
6. Copy the public URL that appears

## 🎯 Expected Result

After this fix:
- ✅ Resume URLs point to R2 (not Laravel)
- ✅ PDFs open directly in browser
- ✅ Zero redirects
- ✅ No ERR_TOO_MANY_REDIRECTS
- ✅ Works for all users (admin, recruiter, student)

---

**This is the ONLY change needed to fix the redirect issue!**

Just update `AWS_URL` to your R2 public bucket URL and clear caches.

---

## 📚 Detailed Guides

For more information:
- **Quick Guide**: `R2_QUICK_FIX.md`
- **Step-by-Step**: `R2_DEPLOYMENT_STEPS.md`
- **Visual Guide**: `R2_CONFIGURATION_VISUAL.md`
- **Complete Guide**: `CLOUDFLARE_R2_DEPLOYMENT_GUIDE.md`
- **Diagnostic Tool**: Run `php diagnose-r2-config.php` on production server
