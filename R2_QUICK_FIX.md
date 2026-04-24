# Cloudflare R2 - Quick Fix for ERR_TOO_MANY_REDIRECTS

## 🎯 Problem
Resume URLs cause `ERR_TOO_MANY_REDIRECTS` because `AWS_URL` points to Laravel domain instead of R2 public bucket URL.

## ⚡ 5-Minute Fix

### Step 1: Get R2 Public Bucket URL
1. Go to Cloudflare Dashboard → R2
2. Click your bucket → Settings
3. Scroll to "Public access"
4. Click "Allow Access" (if not already enabled)
5. **Copy the public bucket URL**
   - Format: `https://pub-{hash}.r2.dev`
   - Example: `https://pub-1234567890abcdef.r2.dev`

### Step 2: Update .env
```bash
# Open .env file
nano .env

# Update these values:
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=auto
AWS_BUCKET=your-bucket-name
AWS_URL=https://pub-your-hash-here.r2.dev  # ← CRITICAL: Use R2 public URL
AWS_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Step 3: Clear Caches
```bash
php artisan config:clear
php artisan config:cache
php artisan cache:clear
```

### Step 4: Test
```bash
php artisan tinker
echo Storage::disk('s3')->url('test.txt');
# Should output: https://pub-{hash}.r2.dev/test.txt
```

## ✅ Correct Configuration

```bash
# ✓ CORRECT
AWS_URL=https://pub-1234567890abcdef.r2.dev
AWS_ENDPOINT=https://abc123def456.r2.cloudflarestorage.com
AWS_DEFAULT_REGION=auto
AWS_USE_PATH_STYLE_ENDPOINT=true

Result:
- Resume URL: https://pub-1234567890abcdef.r2.dev/resumes/file.pdf
- Opens directly in browser
- No redirects
```

## ❌ Wrong Configuration

```bash
# ✗ WRONG (causes ERR_TOO_MANY_REDIRECTS)
AWS_URL=https://your-laravel-cloud-domain.com
AWS_ENDPOINT=https://abc123def456.r2.cloudflarestorage.com

Result:
- Resume URL: https://your-laravel-cloud-domain.com/resumes/file.pdf
- Redirects to Laravel app
- ERR_TOO_MANY_REDIRECTS
```

## 🔍 Diagnostic

Run this to identify the issue:
```bash
php diagnose-r2-config.php
```

## 📚 Full Guide

See `CLOUDFLARE_R2_DEPLOYMENT_GUIDE.md` for complete instructions.

## 🎯 Key Points

1. **AWS_URL** = R2 public bucket URL (`https://pub-{hash}.r2.dev`)
2. **AWS_ENDPOINT** = R2 API endpoint (`https://{account}.r2.cloudflarestorage.com`)
3. **AWS_DEFAULT_REGION** = `auto` (not AWS regions)
4. **AWS_USE_PATH_STYLE_ENDPOINT** = `true` (required for R2)

---

**Fix Time:** 5 minutes  
**Result:** Resume opens directly from R2 with zero redirects
