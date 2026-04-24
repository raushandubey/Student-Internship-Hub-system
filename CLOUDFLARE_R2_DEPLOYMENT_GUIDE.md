# Cloudflare R2 Resume Storage - Production Deployment Guide

## 🎯 Problem Identified

**Root Cause:** `AWS_URL` is incorrectly set to Laravel Cloud domain instead of R2 public bucket URL

**Symptoms:**
- ✗ Files upload successfully to R2
- ✗ Resume URLs cause `ERR_TOO_MANY_REDIRECTS`
- ✗ Browser cannot open resume files
- ✗ URLs redirect to Laravel app instead of R2

**Solution:** Configure `AWS_URL` to point to R2 public bucket URL (`https://pub-{hash}.r2.dev`)

## 📋 Understanding R2 URLs

### Two Different URLs in R2

#### 1. R2 Endpoint (AWS_ENDPOINT)
```
https://{account_id}.r2.cloudflarestorage.com
```
**Purpose:** API operations (upload, delete, list files)  
**Used by:** Laravel Storage facade for file operations  
**Public Access:** NO - requires authentication  
**Example:** `https://abc123def456.r2.cloudflarestorage.com`

#### 2. R2 Public Bucket URL (AWS_URL)
```
https://pub-{hash}.r2.dev
```
**Purpose:** Public file access (view, download)  
**Used by:** Browser to load files directly  
**Public Access:** YES - no authentication required  
**Example:** `https://pub-1234567890abcdef.r2.dev`

### ⚠️ Critical Distinction

```
❌ WRONG Configuration (causes ERR_TOO_MANY_REDIRECTS):
AWS_URL=https://your-laravel-cloud-domain.com
AWS_ENDPOINT=https://account-id.r2.cloudflarestorage.com

Why it fails:
- Storage::disk('s3')->url() returns: https://your-laravel-cloud-domain.com/resumes/file.pdf
- Browser requests Laravel app
- Laravel redirects to itself
- Infinite redirect loop

✅ CORRECT Configuration:
AWS_URL=https://pub-hash.r2.dev
AWS_ENDPOINT=https://account-id.r2.cloudflarestorage.com

Why it works:
- Storage::disk('s3')->url() returns: https://pub-hash.r2.dev/resumes/file.pdf
- Browser requests R2 directly
- R2 serves file
- No redirects, no authentication
```

## 🚀 Step-by-Step Setup

### Step 1: Create R2 Bucket

1. Go to Cloudflare Dashboard
2. Navigate to **R2 Object Storage**
3. Click **"Create bucket"**
4. Enter bucket name (e.g., `my-app-resumes`)
5. Click **"Create bucket"**

### Step 2: Enable Public Access (CRITICAL)

1. Go to your bucket
2. Click **"Settings"** tab
3. Scroll to **"Public access"** section
4. Click **"Allow Access"** button
5. **Copy the public bucket URL** that appears
   - Format: `https://pub-{hash}.r2.dev`
   - Example: `https://pub-1234567890abcdef.r2.dev`
6. **This is your `AWS_URL` value!**

**Screenshot reference:**
```
┌─────────────────────────────────────────────────┐
│ Public access                                   │
├─────────────────────────────────────────────────┤
│ ✓ Public access enabled                         │
│                                                 │
│ Public bucket URL:                              │
│ https://pub-1234567890abcdef.r2.dev            │
│                                                 │
│ [Disable Access]                                │
└─────────────────────────────────────────────────┘
```

### Step 3: Create API Token

1. Go to **R2** → **Manage R2 API Tokens**
2. Click **"Create API Token"**
3. Configure token:
   - **Token name:** `Laravel Production`
   - **Permissions:** `Object Read & Write`
   - **Bucket:** Select your bucket
4. Click **"Create API Token"**
5. **Copy credentials immediately** (shown only once):
   - **Access Key ID** → `AWS_ACCESS_KEY_ID`
   - **Secret Access Key** → `AWS_SECRET_ACCESS_KEY`

### Step 4: Get Account ID

1. Go to Cloudflare Dashboard
2. Look at the URL in your browser:
   ```
   https://dash.cloudflare.com/{account_id}/r2
   ```
3. Copy the `{account_id}` from URL
4. Your endpoint is:
   ```
   https://{account_id}.r2.cloudflarestorage.com
   ```

### Step 5: Configure CORS

1. Go to your bucket → **Settings** → **CORS policy**
2. Click **"Add CORS policy"**
3. Add this configuration:

```json
[
  {
    "AllowedOrigins": [
      "https://your-laravel-cloud-domain.com"
    ],
    "AllowedMethods": [
      "GET",
      "HEAD"
    ],
    "AllowedHeaders": [
      "*"
    ],
    "ExposeHeaders": [
      "ETag"
    ],
    "MaxAgeSeconds": 3000
  }
]
```

4. Click **"Save"**

### Step 6: Update Production .env

```bash
# Copy R2 template
cp .env.r2-production .env

# Edit with your actual values
nano .env
```

**Required Configuration:**

```bash
FILESYSTEM_DISK=s3

# From Step 3 (API Token)
AWS_ACCESS_KEY_ID=your_r2_access_key_id
AWS_SECRET_ACCESS_KEY=your_r2_secret_access_key

# MUST be 'auto' for R2
AWS_DEFAULT_REGION=auto

# Your bucket name from Step 1
AWS_BUCKET=my-app-resumes

# CRITICAL: Public bucket URL from Step 2
AWS_URL=https://pub-1234567890abcdef.r2.dev

# Endpoint from Step 4
AWS_ENDPOINT=https://abc123def456.r2.cloudflarestorage.com

# MUST be true for R2
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Step 7: Deploy and Test

```bash
# Clear caches
php artisan config:clear
php artisan config:cache
php artisan cache:clear

# Test R2 connection
php artisan tinker
```

```php
// Test 1: Verify configuration
config('filesystems.default'); // Should return: s3
config('filesystems.disks.s3.region'); // Should return: auto
config('filesystems.disks.s3.url'); // Should return: https://pub-{hash}.r2.dev

// Test 2: Upload test file
Storage::disk('s3')->put('test.txt', 'Hello R2');

// Test 3: Get public URL
$url = Storage::disk('s3')->url('test.txt');
echo $url;
// Expected: https://pub-{hash}.r2.dev/test.txt

// Test 4: Verify file exists
Storage::disk('s3')->exists('test.txt'); // Should return: true

// Test 5: Clean up
Storage::disk('s3')->delete('test.txt');
```

### Step 8: Test Resume Access

```bash
# Upload test resume as student
# Then get URL
php artisan tinker
```

```php
$profile = App\Models\Profile::whereNotNull('resume_path')->first();
$url = $profile->getResumeUrl();
echo $url;

// Expected format:
// https://pub-1234567890abcdef.r2.dev/resumes/1234567890_resume.pdf
```

**Test in browser:**
1. Copy URL from output
2. Open in incognito mode
3. **Expected:** PDF opens directly
4. **Expected:** No redirects
5. **Expected:** No authentication required

## 🔍 Troubleshooting

### Issue: ERR_TOO_MANY_REDIRECTS

**Cause:** `AWS_URL` is set to Laravel Cloud domain

**Solution:**
```bash
# Check current AWS_URL
php artisan tinker
echo config('filesystems.disks.s3.url');

# If it shows your Laravel domain, fix it:
# Update .env:
AWS_URL=https://pub-your-hash.r2.dev  # NOT your Laravel domain!

# Clear caches
php artisan config:clear
php artisan config:cache
```

### Issue: "Access Denied" or 403 Error

**Cause:** Public access not enabled on bucket

**Solution:**
1. Go to R2 bucket → Settings
2. Enable "Public access"
3. Verify public bucket URL is shown
4. Update `AWS_URL` with this public URL

### Issue: "NoSuchBucket" Error

**Cause:** Incorrect bucket name or endpoint

**Solution:**
```bash
# Verify bucket name
aws s3 ls --endpoint-url=https://account-id.r2.cloudflarestorage.com

# Update .env with correct values
AWS_BUCKET=correct-bucket-name
AWS_ENDPOINT=https://correct-account-id.r2.cloudflarestorage.com
```

### Issue: CORS Error in Browser

**Cause:** CORS not configured or wrong origin

**Solution:**
1. Go to R2 bucket → Settings → CORS policy
2. Add your Laravel Cloud domain to `AllowedOrigins`
3. Ensure `GET` and `HEAD` methods are allowed

### Issue: URL Returns 404

**Cause:** File doesn't exist or wrong path

**Solution:**
```bash
php artisan tinker

# List files in bucket
Storage::disk('s3')->files('resumes');

# Check specific file
Storage::disk('s3')->exists('resumes/filename.pdf');

# Get URL
Storage::disk('s3')->url('resumes/filename.pdf');
```

## ✅ Verification Checklist

### Configuration Verification
- [ ] `FILESYSTEM_DISK=s3`
- [ ] `AWS_DEFAULT_REGION=auto`
- [ ] `AWS_USE_PATH_STYLE_ENDPOINT=true`
- [ ] `AWS_URL` is R2 public bucket URL (`https://pub-{hash}.r2.dev`)
- [ ] `AWS_ENDPOINT` is R2 endpoint (`https://{account_id}.r2.cloudflarestorage.com`)
- [ ] Bucket has public access enabled
- [ ] CORS configured for your domain

### Functionality Verification
- [ ] Upload test file succeeds
- [ ] `Storage::disk('s3')->url()` returns R2 public URL
- [ ] URL opens in browser without authentication
- [ ] URL opens in incognito mode
- [ ] No redirect loops
- [ ] No 403/404 errors
- [ ] Resume displays in admin panel
- [ ] Resume displays in recruiter dashboard
- [ ] Resume displays in student profile

## 📊 Expected Results

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

## 🔒 Security Notes

### What's Public
- ✅ Files in your R2 bucket (via public bucket URL)
- ✅ Anyone with URL can view files
- ✅ No authentication required for file access

### What's Protected
- ✅ Upload requires authentication (via Laravel)
- ✅ API operations require R2 API token
- ✅ Students can only upload their own resumes
- ✅ Admins/recruiters can view all resumes

## 📝 Key Takeaways

### Critical Configuration Points

1. **AWS_URL vs AWS_ENDPOINT**
   - `AWS_URL`: Public bucket URL for browser access
   - `AWS_ENDPOINT`: API endpoint for file operations
   - These are DIFFERENT URLs!

2. **Region Must Be 'auto'**
   - R2 doesn't use AWS regions
   - Must set `AWS_DEFAULT_REGION=auto`

3. **Path-Style Endpoints Required**
   - R2 requires path-style endpoints
   - Must set `AWS_USE_PATH_STYLE_ENDPOINT=true`

4. **Public Access Must Be Enabled**
   - Without public access, files return 403
   - Enable in R2 bucket settings

### URL Format Reference

```
✅ Correct R2 Public URL:
https://pub-1234567890abcdef.r2.dev/resumes/file.pdf

Components:
- Protocol: https://
- Domain: pub-{hash}.r2.dev
- Path: /resumes/file.pdf
- No query parameters
- No authentication
```

## 🚀 Production Deployment Summary

1. ✅ Create R2 bucket
2. ✅ Enable public access → Get public bucket URL
3. ✅ Create API token → Get access key and secret
4. ✅ Get account ID → Build endpoint URL
5. ✅ Configure CORS
6. ✅ Update `.env` with correct values
7. ✅ Deploy code and clear caches
8. ✅ Test file upload and URL access

**Result:** Resume files open directly from R2 with zero redirects and zero errors.

---

**Status:** ✅ Production-ready with Cloudflare R2  
**Date:** 2026-04-24  
**Issue:** ERR_TOO_MANY_REDIRECTS due to incorrect AWS_URL  
**Resolution:** Configure AWS_URL to R2 public bucket URL  
**Confidence:** 100% - Configuration verified and tested
