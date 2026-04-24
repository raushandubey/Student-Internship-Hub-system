# Cloudflare R2 Configuration - Visual Diagram

## 🎯 The Problem: ERR_TOO_MANY_REDIRECTS

```
┌─────────────────────────────────────────────────────────────────────┐
│                    WRONG CONFIGURATION                              │
└─────────────────────────────────────────────────────────────────────┘

.env Configuration:
┌──────────────────────────────────────────────────────────────┐
│ AWS_URL=https://your-laravel-cloud-domain.com               │  ← WRONG!
│ AWS_ENDPOINT=https://account-id.r2.cloudflarestorage.com    │
└──────────────────────────────────────────────────────────────┘

What Happens:

1. Laravel generates URL:
   ┌────────────────────────────────────────────────────────────┐
   │ Storage::disk('s3')->url('resumes/file.pdf')              │
   │ Returns: https://your-laravel-cloud-domain.com/resumes/... │
   └────────────────────────────────────────────────────────────┘

2. Browser requests file:
   ┌────────────────────────────────────────────────────────────┐
   │ GET https://your-laravel-cloud-domain.com/resumes/file.pdf│
   └────────────────────────────────────────────────────────────┘
                              ↓
   ┌────────────────────────────────────────────────────────────┐
   │              Laravel Application                           │
   │  - No route for /resumes/file.pdf                         │
   │  - Redirects to home or 404 handler                       │
   └────────────────────────────────────────────────────────────┘
                              ↓
   ┌────────────────────────────────────────────────────────────┐
   │  Redirect: https://your-laravel-cloud-domain.com/resumes/...│
   └────────────────────────────────────────────────────────────┘
                              ↓
                    ♻️  INFINITE LOOP  ♻️
                              ↓
   ┌────────────────────────────────────────────────────────────┐
   │           ERR_TOO_MANY_REDIRECTS                           │
   └────────────────────────────────────────────────────────────┘
```

## ✅ The Solution: Direct R2 Public URL

```
┌─────────────────────────────────────────────────────────────────────┐
│                    CORRECT CONFIGURATION                            │
└─────────────────────────────────────────────────────────────────────┘

.env Configuration:
┌──────────────────────────────────────────────────────────────┐
│ AWS_URL=https://pub-1234567890abcdef.r2.dev                 │  ← CORRECT!
│ AWS_ENDPOINT=https://account-id.r2.cloudflarestorage.com    │
└──────────────────────────────────────────────────────────────┘

What Happens:

1. Laravel generates URL:
   ┌────────────────────────────────────────────────────────────┐
   │ Storage::disk('s3')->url('resumes/file.pdf')              │
   │ Returns: https://pub-1234567890abcdef.r2.dev/resumes/...  │
   └────────────────────────────────────────────────────────────┘

2. Browser requests file:
   ┌────────────────────────────────────────────────────────────┐
   │ GET https://pub-1234567890abcdef.r2.dev/resumes/file.pdf  │
   └────────────────────────────────────────────────────────────┘
                              ↓
   ┌────────────────────────────────────────────────────────────┐
   │              Cloudflare R2 Storage                         │
   │  - Receives request directly                              │
   │  - Serves file from bucket                                │
   │  - No authentication required                             │
   └────────────────────────────────────────────────────────────┘
                              ↓
   ┌────────────────────────────────────────────────────────────┐
   │  Response: 200 OK                                          │
   │  Content-Type: application/pdf                             │
   │  [PDF file content]                                        │
   └────────────────────────────────────────────────────────────┘
                              ↓
   ┌────────────────────────────────────────────────────────────┐
   │           ✅ PDF OPENS IN BROWSER                          │
   │           ✅ NO REDIRECTS                                  │
   │           ✅ NO ERRORS                                     │
   └────────────────────────────────────────────────────────────┘
```

## 🔄 Two URLs in R2: Understanding the Difference

```
┌─────────────────────────────────────────────────────────────────────┐
│                    R2 HAS TWO DIFFERENT URLS                        │
└─────────────────────────────────────────────────────────────────────┘

1. R2 API ENDPOINT (AWS_ENDPOINT)
   ┌──────────────────────────────────────────────────────────────┐
   │ https://account-id.r2.cloudflarestorage.com                  │
   └──────────────────────────────────────────────────────────────┘
   
   Purpose:     API operations (upload, delete, list)
   Used by:     Laravel Storage facade
   Access:      Requires authentication (API token)
   Example:     Upload file, delete file, list files
   
   Laravel Code:
   ┌──────────────────────────────────────────────────────────────┐
   │ Storage::disk('s3')->put('file.pdf', $content);             │
   │ Storage::disk('s3')->delete('file.pdf');                    │
   │ Storage::disk('s3')->files('resumes');                      │
   └──────────────────────────────────────────────────────────────┘
   
   Uses: AWS_ENDPOINT for these operations

2. R2 PUBLIC BUCKET URL (AWS_URL)
   ┌──────────────────────────────────────────────────────────────┐
   │ https://pub-1234567890abcdef.r2.dev                         │
   └──────────────────────────────────────────────────────────────┘
   
   Purpose:     Public file access (view, download)
   Used by:     Browser to load files
   Access:      No authentication required
   Example:     View PDF in browser, download file
   
   Laravel Code:
   ┌──────────────────────────────────────────────────────────────┐
   │ $url = Storage::disk('s3')->url('file.pdf');                │
   │ // Returns: https://pub-hash.r2.dev/file.pdf                │
   └──────────────────────────────────────────────────────────────┘
   
   Uses: AWS_URL for URL generation

⚠️  CRITICAL: These are DIFFERENT URLs for DIFFERENT purposes!
```

## 📊 Configuration Comparison

```
┌─────────────────────────────────────────────────────────────────────┐
│              AWS S3 vs Cloudflare R2 Configuration                  │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────┬─────────────────────┬──────────────────────┐
│ Setting              │ AWS S3              │ Cloudflare R2        │
├──────────────────────┼─────────────────────┼──────────────────────┤
│ FILESYSTEM_DISK      │ s3                  │ s3                   │
├──────────────────────┼─────────────────────┼──────────────────────┤
│ AWS_DEFAULT_REGION   │ us-east-1           │ auto                 │
│                      │ eu-west-1           │ (MUST be 'auto')     │
│                      │ ap-southeast-1      │                      │
├──────────────────────┼─────────────────────┼──────────────────────┤
│ AWS_USE_PATH_STYLE   │ false               │ true                 │
│                      │ (virtual-hosted)    │ (MUST be true)       │
├──────────────────────┼─────────────────────┼──────────────────────┤
│ AWS_URL              │ https://bucket.s3.  │ https://pub-hash.    │
│                      │ region.amazonaws.com│ r2.dev               │
├──────────────────────┼─────────────────────┼──────────────────────┤
│ AWS_ENDPOINT         │ (not needed)        │ https://account-id.  │
│                      │                     │ r2.cloudflarestorage │
│                      │                     │ .com                 │
└──────────────────────┴─────────────────────┴──────────────────────┘
```

## 🎯 Quick Reference: What Goes Where

```
┌─────────────────────────────────────────────────────────────────────┐
│                    CLOUDFLARE R2 CONFIGURATION                      │
└─────────────────────────────────────────────────────────────────────┘

FILESYSTEM_DISK=s3
├─ Use S3 driver for R2 compatibility
└─ Value: s3

AWS_DEFAULT_REGION=auto
├─ R2 doesn't use AWS regions
└─ Value: auto (MUST be 'auto', not us-east-1, etc.)

AWS_USE_PATH_STYLE_ENDPOINT=true
├─ R2 requires path-style endpoints
└─ Value: true (MUST be true)

AWS_BUCKET=your-bucket-name
├─ Your R2 bucket name
└─ Example: my-app-resumes

AWS_ACCESS_KEY_ID=your_key
├─ From R2 API Token
└─ Get from: R2 → Manage R2 API Tokens

AWS_SECRET_ACCESS_KEY=your_secret
├─ From R2 API Token
└─ Get from: R2 → Manage R2 API Tokens

AWS_URL=https://pub-hash.r2.dev
├─ R2 PUBLIC BUCKET URL (for browser access)
├─ Get from: R2 → Bucket → Settings → Public access
├─ Format: https://pub-{hash}.r2.dev
└─ ⚠️  CRITICAL: NOT your Laravel domain!

AWS_ENDPOINT=https://account-id.r2.cloudflarestorage.com
├─ R2 API ENDPOINT (for file operations)
├─ Get account_id from Cloudflare dashboard URL
└─ Format: https://{account_id}.r2.cloudflarestorage.com
```

## 🔍 How to Get Each Value

```
┌─────────────────────────────────────────────────────────────────────┐
│                    STEP-BY-STEP VALUE COLLECTION                    │
└─────────────────────────────────────────────────────────────────────┘

1. AWS_BUCKET (Bucket Name)
   ┌──────────────────────────────────────────────────────────────┐
   │ Cloudflare Dashboard → R2 → Create bucket                    │
   │ Enter name: my-app-resumes                                   │
   │ Copy: my-app-resumes                                         │
   └──────────────────────────────────────────────────────────────┘

2. AWS_URL (Public Bucket URL) ⚠️  CRITICAL
   ┌──────────────────────────────────────────────────────────────┐
   │ Cloudflare Dashboard → R2 → Your Bucket → Settings          │
   │ Scroll to "Public access"                                    │
   │ Click "Allow Access"                                         │
   │ Copy URL: https://pub-1234567890abcdef.r2.dev              │
   └──────────────────────────────────────────────────────────────┘

3. AWS_ACCESS_KEY_ID & AWS_SECRET_ACCESS_KEY
   ┌──────────────────────────────────────────────────────────────┐
   │ Cloudflare Dashboard → R2 → Manage R2 API Tokens            │
   │ Click "Create API Token"                                     │
   │ Name: Laravel Production                                     │
   │ Permissions: Object Read & Write                             │
   │ Copy Access Key ID and Secret Access Key                     │
   └──────────────────────────────────────────────────────────────┘

4. AWS_ENDPOINT (Account Endpoint)
   ┌──────────────────────────────────────────────────────────────┐
   │ Look at Cloudflare dashboard URL:                            │
   │ https://dash.cloudflare.com/abc123def456/r2                 │
   │                              ^^^^^^^^^^^^                    │
   │                              account_id                      │
   │                                                              │
   │ Build endpoint:                                              │
   │ https://abc123def456.r2.cloudflarestorage.com               │
   └──────────────────────────────────────────────────────────────┘
```

## ✅ Verification Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    VERIFY YOUR CONFIGURATION                        │
└─────────────────────────────────────────────────────────────────────┘

Step 1: Run Diagnostic
┌──────────────────────────────────────────────────────────────┐
│ $ php diagnose-r2-config.php                                 │
│                                                              │
│ Expected output:                                             │
│ ✓ CONFIGURATION LOOKS GOOD                                  │
└──────────────────────────────────────────────────────────────┘

Step 2: Test URL Generation
┌──────────────────────────────────────────────────────────────┐
│ $ php artisan tinker                                         │
│ >>> echo Storage::disk('s3')->url('test.pdf');              │
│                                                              │
│ Expected output:                                             │
│ https://pub-1234567890abcdef.r2.dev/test.pdf               │
│                                                              │
│ ❌ WRONG if output is:                                       │
│ https://your-laravel-cloud-domain.com/test.pdf             │
└──────────────────────────────────────────────────────────────┘

Step 3: Test in Browser
┌──────────────────────────────────────────────────────────────┐
│ 1. Copy URL from Step 2                                      │
│ 2. Open in incognito/private mode                           │
│ 3. Expected: File opens directly                            │
│ 4. Expected: No login required                              │
│ 5. Expected: No redirects                                   │
└──────────────────────────────────────────────────────────────┘
```

---

## 📝 Summary

**Problem:** `AWS_URL` pointed to Laravel domain → ERR_TOO_MANY_REDIRECTS

**Solution:** `AWS_URL` points to R2 public bucket URL → Direct file access

**Key Point:** R2 has TWO URLs - use the right one for the right purpose!

---

**Quick Fix:** See `R2_QUICK_FIX.md`  
**Full Guide:** See `CLOUDFLARE_R2_DEPLOYMENT_GUIDE.md`  
**Diagnostic:** Run `php diagnose-r2-config.php`
