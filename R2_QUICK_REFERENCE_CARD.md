# Cloudflare R2 - Quick Reference Card

## 🎯 The Problem
Resume URLs cause `ERR_TOO_MANY_REDIRECTS`

## 🔧 The Fix (ONE LINE)
```bash
# In production .env, change:
AWS_URL=https://pub-YOUR-HASH-HERE.r2.dev
```

## 📍 Get Your R2 Public URL
1. https://dash.cloudflare.com/
2. R2 → Your Bucket → Settings
3. Public access → Copy URL

## ✅ Required Settings
```bash
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=auto
AWS_URL=https://pub-{hash}.r2.dev
AWS_ENDPOINT=https://{account}.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

## 🚀 Deploy
```bash
# 1. Update .env
nano .env

# 2. Clear caches
php artisan config:clear
php artisan config:cache
php artisan cache:clear

# 3. Verify
php verify-r2-production.php

# 4. Test
# Open resume URL in browser
```

## ✅ Success
- Resume URL: `https://pub-{hash}.r2.dev/resumes/file.pdf`
- Opens directly (no redirects)
- Works for all users

## 📚 Full Docs
- `R2_PRODUCTION_DEPLOYMENT_COMPLETE.md`
- `EXECUTIVE_SUMMARY_R2_FIX.md`

---

**Time to Fix**: 10 minutes  
**Code Changes**: ZERO  
**Risk**: Low
