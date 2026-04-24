# Cloudflare R2 Configuration - Visual Guide

## 🎯 The Problem: ERR_TOO_MANY_REDIRECTS

### Current (WRONG) Configuration Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ WRONG: AWS_URL points to Laravel domain                        │
└─────────────────────────────────────────────────────────────────┘

User clicks "View Resume"
    ↓
Laravel generates URL: https://your-laravel-cloud-domain.com/resumes/file.pdf
    ↓
Browser requests: https://your-laravel-cloud-domain.com/resumes/file.pdf
    ↓
Laravel receives request → No route matches → Redirects to home
    ↓
Browser follows redirect → Laravel redirects again
    ↓
Browser follows redirect → Laravel redirects again
    ↓
Browser follows redirect → Laravel redirects again
    ↓
❌ ERR_TOO_MANY_REDIRECTS (infinite loop)
```

### Correct Configuration Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ CORRECT: AWS_URL points to R2 public bucket URL                │
└─────────────────────────────────────────────────────────────────┘

User clicks "View Resume"
    ↓
Laravel generates URL: https://pub-1234567890abcdef.r2.dev/resumes/file.pdf
    ↓
Browser requests: https://pub-1234567890abcdef.r2.dev/resumes/file.pdf
    ↓
Cloudflare R2 receives request → Serves PDF file directly
    ↓
✅ PDF opens in browser (zero redirects, zero errors)
```

## 🔧 Configuration Comparison

### ❌ WRONG Configuration (Causes Redirects)

```env
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=us-east-1          ← WRONG: Should be 'auto'
AWS_BUCKET=my-bucket
AWS_URL=https://your-laravel-cloud-domain.com  ← WRONG: Laravel domain
AWS_ENDPOINT=https://abc123.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false     ← WRONG: Should be true
```

**Result:**
- ❌ URLs point to Laravel app
- ❌ Infinite redirect loop
- ❌ ERR_TOO_MANY_REDIRECTS

### ✅ CORRECT Configuration (Works Perfectly)

```env
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=auto               ← CORRECT: R2 requires 'auto'
AWS_BUCKET=my-bucket
AWS_URL=https://pub-1234567890abcdef.r2.dev  ← CORRECT: R2 public URL
AWS_ENDPOINT=https://abc123.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true      ← CORRECT: R2 requires true
```

**Result:**
- ✅ URLs point directly to R2
- ✅ Zero redirects
- ✅ PDF opens instantly

## 🌐 Understanding R2 URLs

### Two Different URLs in R2

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. R2 ENDPOINT (AWS_ENDPOINT)                                   │
│    https://{account_id}.r2.cloudflarestorage.com                │
│                                                                 │
│    Purpose: API operations (upload, delete, list)               │
│    Used by: Laravel Storage facade                              │
│    Public: NO - requires authentication                         │
│    Example: https://abc123def456.r2.cloudflarestorage.com       │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ 2. R2 PUBLIC BUCKET URL (AWS_URL)                               │
│    https://pub-{hash}.r2.dev                                    │
│                                                                 │
│    Purpose: Public file access (view, download)                 │
│    Used by: Browser to load files                               │
│    Public: YES - no authentication required                     │
│    Example: https://pub-1234567890abcdef.r2.dev                 │
└─────────────────────────────────────────────────────────────────┘
```

### How They Work Together

```
┌─────────────────────────────────────────────────────────────────┐
│ FILE UPLOAD (Uses AWS_ENDPOINT)                                 │
└─────────────────────────────────────────────────────────────────┘

Student uploads resume
    ↓
Laravel ProfileController receives file
    ↓
Storage::disk('s3')->put('resumes/file.pdf', $file)
    ↓
Laravel uses AWS_ENDPOINT for API call
    ↓
https://abc123.r2.cloudflarestorage.com/my-bucket/resumes/file.pdf
    ↓
✅ File uploaded to R2 (requires authentication)


┌─────────────────────────────────────────────────────────────────┐
│ FILE ACCESS (Uses AWS_URL)                                      │
└─────────────────────────────────────────────────────────────────┘

User clicks "View Resume"
    ↓
Laravel Profile model generates URL
    ↓
Storage::disk('s3')->url('resumes/file.pdf')
    ↓
Returns: AWS_URL + path
    ↓
https://pub-1234567890abcdef.r2.dev/resumes/file.pdf
    ↓
Browser requests this URL directly
    ↓
✅ R2 serves file (no authentication required)
```

## 📍 Where to Find Your R2 Public URL

### Cloudflare Dashboard Navigation

```
1. Login to Cloudflare
   https://dash.cloudflare.com/
   
2. Click "R2" in left sidebar
   
3. Click your bucket name
   
4. Click "Settings" tab
   
5. Scroll to "Public access" section
   
6. Look for "Public bucket URL:"
   
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
   
7. Copy this URL and use it as AWS_URL in .env
```

## 🔄 URL Generation Flow

### How Laravel Generates Resume URLs

```php
// In Profile model: getResumeUrl()

$disk = config('filesystems.default');  // Returns: 's3'
    ↓
Storage::disk('s3')->url('resumes/file.pdf')
    ↓
Laravel reads config('filesystems.disks.s3.url')  // This is AWS_URL
    ↓
Concatenates: AWS_URL + '/' + path
    ↓
Returns: https://pub-{hash}.r2.dev/resumes/file.pdf
```

### Configuration Impact

```
┌─────────────────────────────────────────────────────────────────┐
│ IF AWS_URL = https://your-laravel-cloud-domain.com             │
└─────────────────────────────────────────────────────────────────┘

Storage::disk('s3')->url('resumes/file.pdf')
    ↓
Returns: https://your-laravel-cloud-domain.com/resumes/file.pdf
    ↓
❌ Points to Laravel app → Redirect loop


┌─────────────────────────────────────────────────────────────────┐
│ IF AWS_URL = https://pub-1234567890abcdef.r2.dev               │
└─────────────────────────────────────────────────────────────────┘

Storage::disk('s3')->url('resumes/file.pdf')
    ↓
Returns: https://pub-1234567890abcdef.r2.dev/resumes/file.pdf
    ↓
✅ Points to R2 → File served directly
```

## 🎯 The Fix (One Line Change)

### In Your Production .env File

```diff
# BEFORE (WRONG)
- AWS_URL=https://your-laravel-cloud-domain.com

# AFTER (CORRECT)
+ AWS_URL=https://pub-1234567890abcdef.r2.dev
```

**That's literally the only change needed!**

(Plus ensuring `AWS_DEFAULT_REGION=auto` and `AWS_USE_PATH_STYLE_ENDPOINT=true`)

## 📊 Complete Configuration Matrix

| Setting | Local Dev | Production (R2) | Notes |
|---------|-----------|-----------------|-------|
| `FILESYSTEM_DISK` | `public` | `s3` | Use S3 driver for R2 |
| `AWS_DEFAULT_REGION` | N/A | `auto` | MUST be 'auto' for R2 |
| `AWS_BUCKET` | N/A | `your-bucket` | Your R2 bucket name |
| `AWS_URL` | N/A | `https://pub-{hash}.r2.dev` | R2 PUBLIC URL |
| `AWS_ENDPOINT` | N/A | `https://{account}.r2.cloudflarestorage.com` | R2 API endpoint |
| `AWS_USE_PATH_STYLE_ENDPOINT` | N/A | `true` | MUST be true for R2 |

## 🚀 Quick Reference

### Get R2 Public URL
```
Cloudflare Dashboard → R2 → Your Bucket → Settings → Public access
Copy: https://pub-{hash}.r2.dev
```

### Update .env
```bash
AWS_URL=https://pub-your-hash-here.r2.dev
AWS_DEFAULT_REGION=auto
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Clear Caches
```bash
php artisan config:clear && php artisan config:cache && php artisan cache:clear
```

### Test
```bash
php artisan tinker
Storage::disk('s3')->url('resumes/test.pdf');
# Should return: https://pub-{hash}.r2.dev/resumes/test.pdf
```

---

**Remember**: The key is making sure `AWS_URL` points to your R2 public bucket URL, NOT your Laravel domain!
