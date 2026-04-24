# S3 Resume Storage - Quick Reference Card

## 🎯 Goal
Resume opens directly via S3 URL with **ZERO redirects** and **ZERO errors**.

## ⚡ Quick Setup (5 Minutes)

### 1. S3 Bucket Policy
```json
{
    "Version": "2012-10-17",
    "Statement": [{
        "Effect": "Allow",
        "Principal": "*",
        "Action": "s3:GetObject",
        "Resource": "arn:aws:s3:::YOUR-BUCKET/resumes/*"
    }]
}
```
**Apply:** S3 Console → Bucket → Permissions → Bucket Policy

### 2. Production .env
```bash
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket
AWS_URL=https://your_bucket.s3.us-east-1.amazonaws.com
```

### 3. Deploy
```bash
composer install --no-dev --optimize-autoloader
php artisan config:clear && php artisan config:cache
php artisan cache:clear
php artisan resumes:fix-s3-permissions  # Fix existing files
```

### 4. Test
```bash
php artisan tinker
$url = App\Models\Profile::first()->getResumeUrl();
echo $url;  # Open in browser - should work without login
```

## ✅ Expected URL Format
```
https://your-bucket.s3.us-east-1.amazonaws.com/resumes/1234567890_resume.pdf
```

**Characteristics:**
- ✅ Direct S3 URL
- ✅ No query parameters
- ✅ No expiration
- ✅ No authentication
- ✅ Works in iframe
- ✅ Opens in browser

## ❌ Wrong URL Formats
```
❌ https://bucket.s3.region.amazonaws.com/resumes/file.pdf?X-Amz-Algorithm=...
   (Signed URL - causes issues)

❌ https://your-app.com/resume/download/1
   (Laravel route - causes redirects)

❌ https://s3.region.amazonaws.com/bucket/resumes/file.pdf
   (Path-style - may not work)
```

## 🔧 Common Issues

### Issue: 403 Access Denied
```bash
# Fix: Apply bucket policy (see above)
# Or run: php artisan resumes:fix-s3-permissions
```

### Issue: Redirect Loop
```bash
# Fix: Clear caches
php artisan config:clear
php artisan cache:clear
```

### Issue: "No resume uploaded"
```bash
# Fix: Clear application cache
php artisan cache:clear
```

### Issue: CORS Error
```json
// Add to S3 → Permissions → CORS:
[{
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedOrigins": ["https://your-domain.com"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3000
}]
```

## 📋 Verification Checklist
- [ ] Bucket policy allows public read
- [ ] AWS_URL format correct (no trailing slash)
- [ ] FILESYSTEM_DISK=s3 in .env
- [ ] Upload test resume
- [ ] URL opens in incognito mode
- [ ] No redirect loops
- [ ] No 403 errors
- [ ] Works in admin/recruiter/student views

## 🚨 Critical Points
1. **Bucket Policy:** MUST allow public read on `/resumes/*`
2. **AWS_URL:** MUST be `https://bucket.s3.region.amazonaws.com`
3. **Visibility:** Files MUST be uploaded with `public` visibility
4. **URL Method:** MUST use `Storage::disk('s3')->url()` NOT `temporaryUrl()`

## 📞 Support
- Full Guide: `S3_PRODUCTION_DEPLOYMENT_GUIDE.md`
- Summary: `S3_PRODUCTION_FIX_SUMMARY.md`
- Template: `.env.production`
- Fix Command: `php artisan resumes:fix-s3-permissions`

---

**Status:** ✅ Production-ready with direct S3 public access
