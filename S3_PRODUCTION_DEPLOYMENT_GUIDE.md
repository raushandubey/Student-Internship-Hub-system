# S3 Resume Storage - Production Deployment Guide

## 🎯 Goal
Resume files must open directly via S3 URL with **ZERO redirects** and **ZERO errors**.

## 📋 Pre-Deployment Checklist

### 1. AWS S3 Bucket Setup

#### Create Bucket
```bash
# Via AWS CLI
aws s3 mb s3://your-bucket-name --region us-east-1

# Or use AWS Console:
# 1. Go to S3 Console
# 2. Click "Create bucket"
# 3. Enter unique bucket name
# 4. Select region closest to users
# 5. UNCHECK "Block all public access" ⚠️ CRITICAL
# 6. Create bucket
```

#### Configure Bucket Policy (CRITICAL)
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::YOUR-BUCKET-NAME/resumes/*"
        }
    ]
}
```

**How to apply:**
1. Go to S3 Console → Your Bucket
2. Click "Permissions" tab
3. Scroll to "Bucket policy"
4. Click "Edit"
5. Paste policy above (replace YOUR-BUCKET-NAME)
6. Click "Save changes"

#### Configure CORS
```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "HEAD"],
        "AllowedOrigins": ["https://your-production-domain.com"],
        "ExposeHeaders": ["ETag"],
        "MaxAgeSeconds": 3000
    }
]
```

**How to apply:**
1. Go to S3 Console → Your Bucket
2. Click "Permissions" tab
3. Scroll to "Cross-origin resource sharing (CORS)"
4. Click "Edit"
5. Paste CORS configuration above
6. Click "Save changes"

### 2. IAM User Setup

#### Create IAM User
```bash
# Via AWS CLI
aws iam create-user --user-name laravel-s3-user

# Create access key
aws iam create-access-key --user-name laravel-s3-user
```

#### Attach Policy
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:PutObject",
                "s3:PutObjectAcl",
                "s3:GetObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::YOUR-BUCKET-NAME",
                "arn:aws:s3:::YOUR-BUCKET-NAME/*"
            ]
        }
    ]
}
```

**How to apply:**
1. Go to IAM Console → Users
2. Click your user (laravel-s3-user)
3. Click "Add permissions" → "Attach policies directly"
4. Click "Create policy"
5. Click "JSON" tab
6. Paste policy above (replace YOUR-BUCKET-NAME)
7. Click "Next" → Name it "LaravelS3Access"
8. Click "Create policy"
9. Go back and attach the policy to your user

### 3. Environment Configuration

#### Update Production .env
```bash
# Copy template
cp .env.production .env

# Edit with actual values
nano .env
```

**Critical Variables:**
```bash
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket-name.s3.us-east-1.amazonaws.com
```

**⚠️ AWS_URL Format Rules:**
- ✅ CORRECT: `https://my-bucket.s3.us-east-1.amazonaws.com`
- ❌ WRONG: `https://my-bucket.s3.us-east-1.amazonaws.com/`
- ❌ WRONG: `https://s3.us-east-1.amazonaws.com/my-bucket`
- ❌ WRONG: `https://my-bucket.s3.amazonaws.com` (missing region)

## 🚀 Deployment Steps

### Step 1: Deploy Code
```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear and cache config
php artisan config:clear
php artisan config:cache
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 2: Verify S3 Configuration
```bash
php artisan tinker
```

```php
// Test 1: Check configuration
config('filesystems.default'); // Should return: s3
config('filesystems.disks.s3.bucket'); // Should return: your-bucket-name
config('filesystems.disks.s3.region'); // Should return: us-east-1

// Test 2: Upload test file
Storage::disk('s3')->put('test.txt', 'Hello S3');

// Test 3: Get public URL
$url = Storage::disk('s3')->url('test.txt');
echo $url; // Should return: https://your-bucket.s3.region.amazonaws.com/test.txt

// Test 4: Verify file exists
Storage::disk('s3')->exists('test.txt'); // Should return: true

// Test 5: Clean up
Storage::disk('s3')->delete('test.txt');
```

### Step 3: Test Resume Upload
```bash
# 1. Login to application as student
# 2. Go to Profile page
# 3. Upload a test resume PDF
# 4. Check logs for upload confirmation
tail -f storage/logs/laravel.log
```

**Expected log output:**
```
Resume uploaded to S3 with public access
user_id: 1
path: resumes/1234567890_test_resume.pdf
url: https://your-bucket.s3.us-east-1.amazonaws.com/resumes/1234567890_test_resume.pdf
```

### Step 4: Verify Direct URL Access
```bash
# Get resume URL from database
php artisan tinker
```

```php
$profile = App\Models\Profile::whereNotNull('resume_path')->first();
$url = $profile->getResumeUrl();
echo $url;
```

**Test the URL:**
1. Copy the URL from output
2. Open in browser (incognito mode)
3. **Expected:** PDF opens directly, no login, no redirect
4. **Expected:** URL format: `https://bucket.s3.region.amazonaws.com/resumes/file.pdf`

### Step 5: Test All User Roles

#### Admin Panel
```bash
# 1. Login as admin
# 2. Go to /admin/applications
# 3. Click "View Profile" on any application
# Expected: Resume displays in iframe
# Expected: No "No resume uploaded" message
```

#### Recruiter Dashboard
```bash
# 1. Login as recruiter
# 2. Go to /recruiter/applications
# 3. Click "View Profile" on any application
# Expected: Resume displays in modal
# Expected: No authentication errors
```

#### Student Profile
```bash
# 1. Login as student with resume
# 2. Go to /profile
# 3. Click "View Resume"
# Expected: PDF opens in new tab
# Expected: Direct S3 URL, no redirects
```

## 🔍 Troubleshooting

### Issue 1: "Access Denied" or 403 Error

**Cause:** Bucket policy not configured or files not public

**Solution:**
```bash
# 1. Verify bucket policy is applied
aws s3api get-bucket-policy --bucket your-bucket-name

# 2. Check file ACL
aws s3api get-object-acl --bucket your-bucket-name --key resumes/file.pdf

# 3. Make existing files public
aws s3api put-object-acl --bucket your-bucket-name --key resumes/file.pdf --acl public-read

# 4. Make all resume files public
aws s3api put-object-acl --bucket your-bucket-name --key resumes/ --acl public-read --recursive
```

### Issue 2: "NoSuchBucket" Error

**Cause:** Bucket name or region incorrect

**Solution:**
```bash
# Verify bucket exists
aws s3 ls s3://your-bucket-name

# Check region
aws s3api get-bucket-location --bucket your-bucket-name

# Update .env with correct values
AWS_BUCKET=correct-bucket-name
AWS_DEFAULT_REGION=correct-region
AWS_URL=https://correct-bucket-name.s3.correct-region.amazonaws.com
```

### Issue 3: Redirect Loop or ERR_TOO_MANY_REDIRECTS

**Cause:** Using signed URLs or Laravel routing instead of direct S3 URLs

**Solution:**
```bash
# 1. Verify Profile model uses Storage::disk('s3')->url()
grep -n "temporaryUrl" app/Models/Profile.php
# Should return: nothing (temporaryUrl should NOT be used)

# 2. Verify direct URL format
php artisan tinker
$url = Storage::disk('s3')->url('resumes/test.pdf');
echo $url;
# Should return: https://bucket.s3.region.amazonaws.com/resumes/test.pdf
# Should NOT return: signed URL with query parameters

# 3. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Issue 4: CORS Error in Browser Console

**Cause:** CORS not configured or wrong origin

**Solution:**
```bash
# 1. Check CORS configuration
aws s3api get-bucket-cors --bucket your-bucket-name

# 2. Update CORS with your domain
# See "Configure CORS" section above

# 3. Test from browser console
fetch('https://your-bucket.s3.region.amazonaws.com/resumes/test.pdf')
  .then(r => console.log('CORS OK:', r.status))
  .catch(e => console.error('CORS Error:', e));
```

### Issue 5: "InvalidAccessKeyId" Error

**Cause:** AWS credentials incorrect or not set

**Solution:**
```bash
# 1. Verify credentials in .env
cat .env | grep AWS_

# 2. Test credentials
aws s3 ls s3://your-bucket-name --profile default

# 3. Regenerate access key if needed
aws iam create-access-key --user-name laravel-s3-user

# 4. Update .env and clear cache
php artisan config:clear
php artisan config:cache
```

### Issue 6: Resume Shows "No resume uploaded"

**Cause:** Cache contains old data or file doesn't exist

**Solution:**
```bash
# 1. Clear application cache
php artisan cache:clear

# 2. Verify file exists in S3
php artisan tinker
Storage::disk('s3')->exists('resumes/filename.pdf');

# 3. Check database
DB::table('profiles')->whereNotNull('resume_path')->get(['id', 'resume_path']);

# 4. Regenerate URL
$profile = App\Models\Profile::find(1);
$url = $profile->getResumeUrl();
echo $url;
```

## 🧪 Testing Checklist

### Pre-Production Testing
- [ ] S3 bucket created and configured
- [ ] Bucket policy allows public read access
- [ ] CORS configured for your domain
- [ ] IAM user has correct permissions
- [ ] .env configured with correct AWS credentials
- [ ] AWS_URL format is correct
- [ ] Test file upload works
- [ ] Test file URL is publicly accessible
- [ ] Direct URL opens in browser without login

### Post-Deployment Testing
- [ ] Resume upload works (check logs)
- [ ] Resume URL is direct S3 URL (no signed URL)
- [ ] URL opens in browser without authentication
- [ ] URL opens in incognito mode
- [ ] Admin panel displays resumes
- [ ] Recruiter dashboard displays resumes
- [ ] Student profile displays resumes
- [ ] No redirect loops
- [ ] No 403/404 errors
- [ ] No CORS errors in browser console

## 📊 Monitoring

### Check S3 Access Logs
```bash
# Enable S3 access logging
aws s3api put-bucket-logging --bucket your-bucket-name --bucket-logging-status file://logging.json

# logging.json:
{
    "LoggingEnabled": {
        "TargetBucket": "your-logs-bucket",
        "TargetPrefix": "s3-access-logs/"
    }
}

# View recent access logs
aws s3 ls s3://your-logs-bucket/s3-access-logs/ --recursive | tail -20
```

### Monitor Application Logs
```bash
# Watch Laravel logs
tail -f storage/logs/laravel.log | grep -i resume

# Check for errors
grep -i "error\|failed" storage/logs/laravel.log | grep -i resume
```

## 🔒 Security Best Practices

### 1. Bucket Security
- ✅ Only make `/resumes/*` publicly readable
- ✅ Keep bucket root private
- ✅ Enable versioning for backup
- ✅ Enable encryption at rest

### 2. IAM Security
- ✅ Use dedicated IAM user for Laravel
- ✅ Rotate access keys every 90 days
- ✅ Use least privilege principle
- ✅ Enable MFA for IAM user

### 3. Application Security
- ✅ Validate file types (PDF only)
- ✅ Limit file size (2MB max)
- ✅ Sanitize filenames
- ✅ Check authorization before upload
- ✅ Log all file operations

## 📝 Summary

### What Changed
1. **config/filesystems.php**: Added `visibility: public` and `ACL: public-read`
2. **ProfileController**: Upload with public visibility
3. **Profile Model**: Use `Storage::disk('s3')->url()` instead of `temporaryUrl()`
4. **ResumeController**: Direct S3 URL redirects

### Key Points
- ✅ All resume files are publicly readable via S3
- ✅ No authentication required to access resume URLs
- ✅ No signed URLs (no expiration, no query parameters)
- ✅ Direct S3 URLs: `https://bucket.s3.region.amazonaws.com/resumes/file.pdf`
- ✅ Zero redirects, zero authentication, zero errors

### Production URL Format
```
https://your-bucket-name.s3.us-east-1.amazonaws.com/resumes/1234567890_resume.pdf
```

**This URL:**
- Opens directly in browser
- No login required
- No redirects
- No expiration
- Works in iframes
- Works across all user roles

---

**Status:** ✅ Production-ready with direct S3 public access
**Last Updated:** 2026-04-24
