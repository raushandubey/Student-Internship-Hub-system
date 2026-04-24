# Laravel Cloud + R2 - Quick Fix Guide

## 🎯 The Problem
**Laravel Cloud sets `AWS_URL` to your app domain** (platform restriction)  
**Result**: `Storage::url()` causes `ERR_TOO_MANY_REDIRECTS`

## ✅ The Solution
**Bypass `Storage::url()`** with manual R2 URL construction

## ⚡ 3-Step Fix

### Step 1: Get R2 Public URL (2 minutes)
1. https://dash.cloudflare.com/
2. R2 → Your Bucket → Settings
3. Public access → Copy URL
   ```
   Format: https://pub-{hash}.r2.dev
   Example: https://pub-1234567890abcdef.r2.dev
   ```

### Step 2: Add to Laravel Cloud (2 minutes)
**In Laravel Cloud Dashboard**:
1. Your App → Environment Variables
2. Add new variable:
   ```
   Key: R2_PUBLIC_URL
   Value: https://pub-YOUR-HASH-HERE.r2.dev
   ```
3. Save and redeploy

### Step 3: Verify (1 minute)
```bash
php artisan tinker
$profile = App\Models\Profile::whereNotNull('resume_path')->first();
echo $profile->getResumeUrl();
# Expected: https://pub-{hash}.r2.dev/resumes/filename.pdf
```

## ✅ Success
- Resume URL: `https://pub-{hash}.r2.dev/resumes/file.pdf`
- Opens directly (no redirects)
- Works on Laravel Cloud free tier

## 📚 Full Documentation
- `LARAVEL_CLOUD_R2_SOLUTION.md` - Complete guide
- `.env.laravel-cloud-r2` - Configuration template
- `verify-laravel-cloud-r2.php` - Verification script

---

**Time**: 5 minutes  
**Code Changes**: Already done  
**Platform**: Laravel Cloud (Free Tier)  
**Storage**: Cloudflare R2
