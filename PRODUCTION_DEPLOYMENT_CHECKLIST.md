# PRODUCTION DEPLOYMENT CHECKLIST - RESUME UPLOAD FIX

## 🚨 CRITICAL: The Real Problem

**Error**: "Unable to access your resume file"  
**Root Cause**: Production environment missing cloud storage credentials  
**Impact**: Files upload to database but NOT to cloud storage

---

## ✅ PRE-DEPLOYMENT CHECKLIST

### 1. Verify Cloud Storage Credentials

**On Laravel Cloud Dashboard:**

1. Go to your project settings
2. Navigate to "Environment Variables"
3. **VERIFY these variables are set:**

```env
AWS_ACCESS_KEY_ID=your_r2_access_key_here
AWS_SECRET_ACCESS_KEY=your_r2_secret_key_here
AWS_DEFAULT_REGION=auto
AWS_BUCKET=your-bucket-name
AWS_URL=https://pub-YOUR-HASH.r2.dev
AWS_ENDPOINT=https://YOUR-ACCOUNT-ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
FILESYSTEM_DISK=s3
```

**⚠️ CRITICAL**: If ANY of these are missing, file uploads will FAIL silently!

### 2. Get Your R2 Credentials

**Step-by-Step:**

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Click "R2" in left sidebar
3. Click "Manage R2 API Tokens"
4. Click "Create API Token"
5. Settings:
   - Name: "Laravel Production"
   - Permissions: "Object Read & Write"
   - Select your bucket
6. Click "Create API Token"
7. **COPY IMMEDIATELY** (shown only once):
   - Access Key ID → `AWS_ACCESS_KEY_ID`
   - Secret Access Key → `AWS_SECRET_ACCESS_KEY`

### 3. Get Your R2 Public Bucket URL

**Step-by-Step:**

1. Go to Cloudflare Dashboard → R2
2. Click your bucket name
3. Click "Settings" tab
4. Scroll to "Public access" section
5. If disabled, click "Allow Access" button
6. **COPY** the "Public bucket URL"
   - Format: `https://pub-{hash}.r2.dev`
   - This goes in `AWS_URL`

### 4. Get Your R2 Endpoint

**Step-by-Step:**

1. Look at your Cloudflare Dashboard URL
2. Format: `https://dash.cloudflare.com/{account_id}/r2`
3. Copy the `{account_id}` from URL
4. Your endpoint: `https://{account_id}.r2.cloudflarestorage.com`
5. This goes in `AWS_ENDPOINT`

---

## 🔧 DEPLOYMENT STEPS

### Step 1: Update Environment Variables on Laravel Cloud

```bash
# On Laravel Cloud Dashboard:
# Settings → Environment Variables → Add/Update:

AWS_ACCESS_KEY_ID=<from_step_2>
AWS_SECRET_ACCESS_KEY=<from_step_2>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=<your_bucket_name>
AWS_URL=<from_step_3>
AWS_ENDPOINT=<from_step_4>
AWS_USE_PATH_STYLE_ENDPOINT=true
FILESYSTEM_DISK=s3
```

### Step 2: Deploy Code Changes

```bash
git add -A
git commit -m "fix: add R2 disk configuration and diagnostic tools"
git push origin master
```

### Step 3: Clear Caches on Production

**Via Laravel Cloud Dashboard:**
1. Go to your project
2. Click "Deployments"
3. Click "..." menu
4. Select "Run Command"
5. Run these commands:

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### Step 4: Run Diagnostic Tool

**Via Laravel Cloud SSH or Tinker:**

```bash
php diagnose-resume-access.php
```

**Expected Output:**
```
✓ Cloud Storage Configuration: VALID
✓ Credentials: CONFIGURED
✓ Test Write: SUCCESS
✓ Test Read: SUCCESS
✓ Files in bucket: FOUND
```

**If you see errors:**
- `✗ Has Key: NO` → AWS_ACCESS_KEY_ID not set
- `✗ Has Secret: NO` → AWS_SECRET_ACCESS_KEY not set
- `✗ Write failed` → Wrong credentials or permissions
- `✗ No files found` → Files never uploaded to cloud

### Step 5: Test Resume Upload

1. Log in as a student
2. Go to Profile → Edit Profile
3. Upload a test resume (any PDF)
4. Click "Save"
5. **VERIFY**: Check Cloudflare R2 dashboard
   - Go to your bucket
   - Look for `resumes/` folder
   - Confirm file exists

### Step 6: Test Resume Optimizer

1. Go to any internship
2. Click "Resume Optimizer"
3. Click "Analyze Resume"
4. **Expected**: Analysis completes successfully
5. **If fails**: Run diagnostic tool again

---

## 🔍 TROUBLESHOOTING

### Issue: "Unable to access your resume file"

**Diagnosis:**
```bash
php diagnose-resume-access.php
```

**Common Causes:**

1. **Missing Credentials**
   - Symptom: `Has Key: NO` or `Has Secret: NO`
   - Fix: Set AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY

2. **Wrong Bucket**
   - Symptom: `Test Write: ERROR` or `Files in bucket: NOT FOUND`
   - Fix: Verify AWS_BUCKET matches your R2 bucket name

3. **Wrong Endpoint**
   - Symptom: `Test Write: ERROR (connection failed)`
   - Fix: Verify AWS_ENDPOINT format

4. **Wrong Region**
   - Symptom: `Test Write: ERROR (region mismatch)`
   - Fix: Set AWS_DEFAULT_REGION=auto for R2

5. **Files in Database but Not in Storage**
   - Symptom: Database has resume_path but Storage::exists() returns false
   - Fix: Re-upload resumes after fixing credentials

### Issue: Files Upload but Can't Be Read

**Diagnosis:**
```bash
php artisan tinker
$profile = App\Models\Profile::whereNotNull('resume_path')->first();
Storage::disk('s3')->exists($profile->resume_path);  // Should return: true
exit
```

**If returns false:**
1. Check if file exists in R2 dashboard
2. Verify path format in database (should be `resumes/filename.pdf`)
3. Check for leading slashes (should NOT have `/` at start)

### Issue: ERR_TOO_MANY_REDIRECTS

**Cause**: AWS_URL points to Laravel domain instead of R2 public URL

**Fix**:
```env
# ❌ WRONG
AWS_URL=https://your-app.laravel.cloud

# ✅ CORRECT
AWS_URL=https://pub-{hash}.r2.dev
```

---

## 📊 VERIFICATION COMMANDS

### Check Configuration

```bash
php artisan tinker
config('filesystems.default');  // Should return: "s3"
config('filesystems.disks.s3.key');  // Should return: your key (not empty)
config('filesystems.disks.s3.bucket');  // Should return: your bucket name
exit
```

### Check File Exists

```bash
php artisan tinker
$profile = App\Models\Profile::whereNotNull('resume_path')->first();
echo $profile->resume_path;  // Should show: resumes/filename.pdf
Storage::disk('s3')->exists($profile->resume_path);  // Should return: true
exit
```

### Check File Content

```bash
php artisan tinker
$profile = App\Models\Profile::whereNotNull('resume_path')->first();
$content = Storage::disk('s3')->get($profile->resume_path);
strlen($content);  // Should return: file size in bytes
substr($content, 0, 4);  // Should return: "%PDF"
exit
```

### List Files in Bucket

```bash
php artisan tinker
Storage::disk('s3')->files('resumes');  // Should return: array of files
exit
```

---

## 🎯 SUCCESS CRITERIA

After deployment, verify:

✅ **Environment Variables**: All AWS_* variables set on Laravel Cloud  
✅ **Diagnostic Tool**: All tests pass  
✅ **File Upload**: Files appear in R2 dashboard  
✅ **File Access**: Storage::exists() returns true  
✅ **Resume Optimizer**: Analysis completes successfully  
✅ **No Errors**: No "Unable to access" errors in logs  

---

## 📝 COMMON MISTAKES TO AVOID

❌ **Setting FILESYSTEM_DISK=r2 without adding r2 disk config**  
✅ Use FILESYSTEM_DISK=s3 (works for both S3 and R2)

❌ **Using AWS_URL=https://your-app.laravel.cloud**  
✅ Use AWS_URL=https://pub-{hash}.r2.dev

❌ **Forgetting to set AWS_USE_PATH_STYLE_ENDPOINT=true**  
✅ R2 requires path-style endpoints

❌ **Using AWS_DEFAULT_REGION=us-east-1 for R2**  
✅ R2 requires AWS_DEFAULT_REGION=auto

❌ **Not enabling public access on R2 bucket**  
✅ Enable in Cloudflare R2 → Bucket → Settings → Public access

---

## 🚀 POST-DEPLOYMENT MONITORING

### Monitor Upload Success Rate

```sql
SELECT 
  COUNT(*) as total_profiles,
  COUNT(CASE WHEN resume_path IS NOT NULL THEN 1 END) as with_resume,
  COUNT(CASE WHEN resume_path IS NOT NULL THEN 1 END) * 100.0 / COUNT(*) as percentage
FROM profiles
WHERE updated_at > NOW() - INTERVAL 24 HOUR;
```

### Monitor Analysis Success Rate

```sql
SELECT 
  COUNT(*) as total_analyses,
  COUNT(CASE WHEN overall_score > 0 THEN 1 END) as successful,
  COUNT(CASE WHEN overall_score > 0 THEN 1 END) * 100.0 / COUNT(*) as success_rate
FROM resume_scores
WHERE created_at > NOW() - INTERVAL 24 HOUR;
```

### Check Error Logs

```bash
tail -f storage/logs/laravel.log | grep -E "(Pipeline|RESUME_DOWNLOAD_FAILED|file not found)"
```

---

## 📞 EMERGENCY ROLLBACK

If deployment causes issues:

```bash
# 1. Revert code
git revert HEAD
git push origin master

# 2. Clear caches
php artisan config:clear
php artisan cache:clear

# 3. Restore previous environment variables
# (Keep backup of working configuration)
```

---

**Last Updated**: 2026-05-15  
**Version**: 2.0.0  
**Status**: Production-Critical ⚠️
