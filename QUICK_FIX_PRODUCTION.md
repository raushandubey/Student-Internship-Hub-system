# QUICK FIX: Production Resume Access Error

## 🚨 THE PROBLEM

**Error**: "Unable to access your resume file"  
**Root Cause**: **Missing cloud storage credentials on Laravel Cloud**

---

## ⚡ IMMEDIATE FIX (5 Minutes)

### Step 1: Get Your R2 Credentials

1. Go to https://dash.cloudflare.com/
2. Click "R2" → "Manage R2 API Tokens"
3. Click "Create API Token"
4. Copy the **Access Key ID** and **Secret Access Key**

### Step 2: Get Your R2 Public URL

1. Go to Cloudflare R2 → Your Bucket
2. Click "Settings" tab
3. Enable "Public access" if disabled
4. Copy the **Public bucket URL** (format: `https://pub-{hash}.r2.dev`)

### Step 3: Set Environment Variables on Laravel Cloud

**Go to Laravel Cloud Dashboard → Your Project → Settings → Environment Variables**

Add/Update these variables:

```env
AWS_ACCESS_KEY_ID=<paste_access_key_from_step_1>
AWS_SECRET_ACCESS_KEY=<paste_secret_key_from_step_1>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=<your_bucket_name>
AWS_URL=<paste_public_url_from_step_2>
AWS_ENDPOINT=https://<your_account_id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
FILESYSTEM_DISK=s3
```

### Step 4: Clear Caches

**On Laravel Cloud Dashboard → Deployments → Run Command:**

```bash
php artisan config:clear && php artisan cache:clear && php artisan config:cache
```

### Step 5: Test

**Run diagnostic:**

```bash
php diagnose-resume-access.php
```

**Expected output:**
```
✓ Has Key: YES
✓ Has Secret: YES
✓ Test Write: SUCCESS
✓ Test Read: SUCCESS
```

---

## 🔍 VERIFY IT'S FIXED

### Test 1: Check Configuration

```bash
php artisan tinker
config('filesystems.disks.s3.key');  // Should NOT be empty
exit
```

### Test 2: Upload a Resume

1. Log in as student
2. Upload resume
3. Check Cloudflare R2 dashboard
4. Confirm file exists in `resumes/` folder

### Test 3: Run Resume Optimizer

1. Go to any internship
2. Click "Resume Optimizer"
3. Click "Analyze Resume"
4. Should complete successfully

---

## ❌ IF STILL FAILING

Run full diagnostic:

```bash
php diagnose-resume-access.php
```

Look for:
- `✗ Has Key: NO` → AWS_ACCESS_KEY_ID not set
- `✗ Has Secret: NO` → AWS_SECRET_ACCESS_KEY not set
- `✗ Test Write: ERROR` → Wrong credentials
- `✗ Files in bucket: NOT FOUND` → Files never uploaded

---

## 📞 NEED HELP?

1. Run: `php diagnose-resume-access.php`
2. Share the output
3. Check `storage/logs/laravel.log` for errors

---

**This fix addresses the ROOT CAUSE: Missing cloud storage credentials on production.**
