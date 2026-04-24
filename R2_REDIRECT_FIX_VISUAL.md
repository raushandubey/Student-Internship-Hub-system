# R2 Redirect Fix - Visual Guide

## 🎯 The Problem You're Experiencing

```
┌─────────────────────────────────────────────────────────────────┐
│ CURRENT SITUATION (WRONG)                                       │
└─────────────────────────────────────────────────────────────────┘

Production .env file:
┌──────────────────────────────────────────────────────────────┐
│ AWS_URL=https://your-laravel-domain.com                     │  ← WRONG!
└──────────────────────────────────────────────────────────────┘

What happens:
1. User clicks "View Resume"
2. Laravel generates: https://your-laravel-domain.com/resumes/file.pdf
3. Browser requests your Laravel website
4. Laravel has no route for /resumes/file.pdf
5. Laravel redirects to home page
6. Browser follows redirect
7. Laravel redirects again
8. Browser follows redirect
9. Laravel redirects again
10. ❌ ERR_TOO_MANY_REDIRECTS (infinite loop)
```

## ✅ The Solution

```
┌─────────────────────────────────────────────────────────────────┐
│ CORRECT CONFIGURATION                                           │
└─────────────────────────────────────────────────────────────────┘

Production .env file:
┌──────────────────────────────────────────────────────────────┐
│ AWS_URL=https://pub-1234567890abcdef.r2.dev                 │  ← CORRECT!
└──────────────────────────────────────────────────────────────┘

What happens:
1. User clicks "View Resume"
2. Laravel generates: https://pub-1234567890abcdef.r2.dev/resumes/file.pdf
3. Browser requests Cloudflare R2 directly
4. R2 serves the PDF file
5. ✅ PDF opens instantly (zero redirects)
```

## 🔧 Where to Find Your R2 Public URL

```
┌─────────────────────────────────────────────────────────────────┐
│ Cloudflare Dashboard Navigation                                 │
└─────────────────────────────────────────────────────────────────┘

Step 1: Login to Cloudflare
https://dash.cloudflare.com/

Step 2: Click "R2" in left sidebar
┌────────────────────┐
│ ☰ Dashboard        │
│ 🌐 Websites        │
│ 📦 R2              │  ← Click here
│ 🔒 Zero Trust      │
└────────────────────┘

Step 3: Click your bucket name
┌─────────────────────────────────────────────────────────────┐
│ R2 Buckets                                                  │
├─────────────────────────────────────────────────────────────┤
│ my-app-resumes                    Created: Jan 15, 2026    │  ← Click
│ 245 objects • 12.5 MB                                       │
└─────────────────────────────────────────────────────────────┘

Step 4: Click "Settings" tab
┌─────────────────────────────────────────────────────────────┐
│ [Objects]  [Settings]  [Metrics]                            │
│            ^^^^^^^^^^                                        │
│            Click here                                        │
└─────────────────────────────────────────────────────────────┘

Step 5: Scroll to "Public access" section
┌─────────────────────────────────────────────────────────────┐
│ Public access                                               │
├─────────────────────────────────────────────────────────────┤
│ ✓ Public access enabled                                     │
│                                                             │
│ Public bucket URL:                                          │
│ https://pub-1234567890abcdef.r2.dev                        │  ← COPY THIS!
│                                                             │
│ [Disable Access]                                            │
└─────────────────────────────────────────────────────────────┘

Step 6: COPY the URL
https://pub-1234567890abcdef.r2.dev  ← This is what you need!
```

## 📝 Exact Changes Needed

### Your Production .env File

```bash
# ============================================================================
# FIND THESE LINES IN YOUR PRODUCTION .env FILE
# ============================================================================

# BEFORE (causes redirects):
AWS_URL=https://your-laravel-domain.com

# AFTER (fixes redirects):
AWS_URL=https://pub-YOUR-HASH-HERE.r2.dev


# ============================================================================
# ALSO VERIFY THESE ARE CORRECT
# ============================================================================

FILESYSTEM_DISK=s3                    # Must be 's3' (not 'local' or 'public')
AWS_DEFAULT_REGION=auto               # Must be 'auto' (not 'us-east-1')
AWS_USE_PATH_STYLE_ENDPOINT=true      # Must be 'true' (not 'false')
```

## 🎯 Before vs After Comparison

### BEFORE FIX (Current - Broken)

```
┌─────────────────────────────────────────────────────────────────┐
│ .env Configuration                                              │
├─────────────────────────────────────────────────────────────────┤
│ AWS_URL=https://your-laravel-domain.com                        │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ Generated Resume URL                                            │
├─────────────────────────────────────────────────────────────────┤
│ https://your-laravel-domain.com/resumes/file.pdf               │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ Browser Behavior                                                │
├─────────────────────────────────────────────────────────────────┤
│ Requests Laravel app → Redirect → Redirect → Redirect          │
│ ❌ ERR_TOO_MANY_REDIRECTS                                       │
└─────────────────────────────────────────────────────────────────┘
```

### AFTER FIX (Correct - Working)

```
┌─────────────────────────────────────────────────────────────────┐
│ .env Configuration                                              │
├─────────────────────────────────────────────────────────────────┤
│ AWS_URL=https://pub-1234567890abcdef.r2.dev                    │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ Generated Resume URL                                            │
├─────────────────────────────────────────────────────────────────┤
│ https://pub-1234567890abcdef.r2.dev/resumes/file.pdf           │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ Browser Behavior                                                │
├─────────────────────────────────────────────────────────────────┤
│ Requests R2 directly → PDF served instantly                     │
│ ✅ PDF OPENS (zero redirects)                                   │
└─────────────────────────────────────────────────────────────────┘
```

## 🔄 Step-by-Step Fix Process

```
┌─────────────────────────────────────────────────────────────────┐
│ Step 1: Get R2 Public URL                                       │
├─────────────────────────────────────────────────────────────────┤
│ Cloudflare Dashboard → R2 → Your Bucket → Settings             │
│ Copy: https://pub-{hash}.r2.dev                                │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ Step 2: SSH to Production Server                                │
├─────────────────────────────────────────────────────────────────┤
│ ssh your-production-server                                      │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ Step 3: Edit .env File                                          │
├─────────────────────────────────────────────────────────────────┤
│ nano .env                                                       │
│ Find: AWS_URL=https://your-laravel-domain.com                  │
│ Change to: AWS_URL=https://pub-{your-hash}.r2.dev              │
│ Save: Ctrl+X, Y, Enter                                          │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ Step 4: Clear Caches                                            │
├─────────────────────────────────────────────────────────────────┤
│ php artisan config:clear                                        │
│ php artisan config:cache                                        │
│ php artisan cache:clear                                         │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ Step 5: Test                                                    │
├─────────────────────────────────────────────────────────────────┤
│ Open your website → Try to view resume                          │
│ ✅ PDF should open directly (no redirects!)                     │
└─────────────────────────────────────────────────────────────────┘
```

## ✅ Success Indicators

After the fix, you should see:

```
✅ Resume URL format:
   https://pub-1234567890abcdef.r2.dev/resumes/filename.pdf
   (NOT your Laravel domain!)

✅ Browser behavior:
   Click "View Resume" → PDF opens instantly
   (NO redirects, NO errors)

✅ All user roles work:
   Admin can view resumes ✓
   Recruiter can view resumes ✓
   Student can view resumes ✓

✅ No errors:
   No ERR_TOO_MANY_REDIRECTS ✓
   No 404 errors ✓
   No 403 errors ✓
```

## 🚨 If Still Not Working

### Check 1: Verify AWS_URL

```bash
# On production server:
php artisan tinker
echo config('filesystems.disks.s3.url');

# Should output: https://pub-{hash}.r2.dev
# Should NOT output: your Laravel domain
```

### Check 2: Verify Caches Cleared

```bash
# Clear again:
php artisan config:clear
php artisan config:cache
php artisan cache:clear

# Restart PHP-FPM (if applicable):
sudo systemctl restart php8.2-fpm
```

### Check 3: Clear Browser Cache

```
1. Open browser
2. Press Ctrl+Shift+Delete
3. Clear cached images and files
4. Try again in incognito mode
```

---

**The fix is simple: Just change AWS_URL to your R2 public bucket URL!**

That's literally the only change needed to fix the redirect issue.
