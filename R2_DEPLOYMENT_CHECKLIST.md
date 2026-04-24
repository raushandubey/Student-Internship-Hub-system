# Cloudflare R2 Production Deployment Checklist

## 📋 Pre-Deployment Checklist

### Information Gathering

- [ ] **Cloudflare Account Access**
  - Can login to https://dash.cloudflare.com/
  - Have access to R2 section
  - Can view your bucket

- [ ] **Production Server Access**
  - Can SSH into production server
  - Can edit `.env` file
  - Can run artisan commands

- [ ] **Current Configuration Known**
  - Know your R2 bucket name
  - Have R2 API credentials (access key & secret)
  - Know your R2 account ID

## 🎯 Step 1: Get R2 Public Bucket URL

- [ ] Login to Cloudflare Dashboard
- [ ] Navigate to R2 section
- [ ] Click on your bucket name
- [ ] Click "Settings" tab
- [ ] Scroll to "Public access" section
- [ ] Verify public access is enabled (or enable it)
- [ ] Copy the public bucket URL
  - Format: `https://pub-{hash}.r2.dev`
  - Example: `https://pub-1234567890abcdef.r2.dev`
- [ ] Save this URL (you'll need it in Step 2)

**My R2 Public URL:**
```
https://pub-___________________________.r2.dev
```

## 🔧 Step 2: Update Production .env

- [ ] SSH into production server
- [ ] Backup current `.env` file
  ```bash
  cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
  ```
- [ ] Edit `.env` file
  ```bash
  nano .env
  # or
  vi .env
  ```
- [ ] Update these values:

### Required Changes

- [ ] `FILESYSTEM_DISK=s3`
  - Current value: _______________
  - New value: `s3`

- [ ] `AWS_DEFAULT_REGION=auto`
  - Current value: _______________
  - New value: `auto`

- [ ] `AWS_URL=https://pub-{your-hash}.r2.dev`
  - Current value: _______________
  - New value: (paste from Step 1)

- [ ] `AWS_USE_PATH_STYLE_ENDPOINT=true`
  - Current value: _______________
  - New value: `true`

### Verify These Are Correct

- [ ] `AWS_ACCESS_KEY_ID` is set (don't change)
- [ ] `AWS_SECRET_ACCESS_KEY` is set (don't change)
- [ ] `AWS_BUCKET` is set (don't change)
- [ ] `AWS_ENDPOINT` is set (don't change)
  - Should be: `https://{account_id}.r2.cloudflarestorage.com`

- [ ] Save and close the file

## 🧹 Step 3: Clear All Caches

Run these commands on production server:

- [ ] Clear config cache
  ```bash
  php artisan config:clear
  ```

- [ ] Rebuild config cache
  ```bash
  php artisan config:cache
  ```

- [ ] Clear application cache
  ```bash
  php artisan cache:clear
  ```

- [ ] Clear route cache
  ```bash
  php artisan route:clear
  ```

- [ ] Clear view cache
  ```bash
  php artisan view:clear
  ```

- [ ] Restart PHP-FPM (if applicable)
  ```bash
  sudo systemctl restart php8.2-fpm
  # or
  sudo systemctl restart php-fpm
  ```

## 🔍 Step 4: Run Diagnostic

- [ ] Run diagnostic script
  ```bash
  php diagnose-r2-config.php
  ```

- [ ] Verify all checks pass:
  - [ ] ✓ FILESYSTEM_DISK is 's3'
  - [ ] ✓ AWS_DEFAULT_REGION is 'auto'
  - [ ] ✓ AWS_USE_PATH_STYLE_ENDPOINT is true
  - [ ] ✓ AWS_URL appears to be R2 public bucket URL
  - [ ] ✓ URL format is correct (pub-{hash}.r2.dev)
  - [ ] ✓ AWS_ENDPOINT is R2 endpoint
  - [ ] ✓ Generated URL points to R2
  - [ ] ✓ Resume URLs working correctly

- [ ] If any checks fail, review and fix before continuing

## 🧪 Step 5: Test Configuration

### Test 1: Verify Configuration Values

- [ ] Open tinker
  ```bash
  php artisan tinker
  ```

- [ ] Check filesystem disk
  ```php
  config('filesystems.default');
  // Expected: "s3"
  ```
  - [ ] Result matches expected

- [ ] Check region
  ```php
  config('filesystems.disks.s3.region');
  // Expected: "auto"
  ```
  - [ ] Result matches expected

- [ ] Check AWS_URL
  ```php
  config('filesystems.disks.s3.url');
  // Expected: "https://pub-{hash}.r2.dev"
  ```
  - [ ] Result matches expected
  - [ ] Does NOT contain Laravel domain

- [ ] Exit tinker
  ```php
  exit
  ```

### Test 2: Test URL Generation

- [ ] Open tinker
  ```bash
  php artisan tinker
  ```

- [ ] Generate test URL
  ```php
  Storage::disk('s3')->url('resumes/test.pdf');
  ```
  - [ ] URL starts with `https://pub-`
  - [ ] URL contains `.r2.dev`
  - [ ] URL does NOT contain Laravel domain

- [ ] Get actual resume URL
  ```php
  $profile = App\Models\Profile::whereNotNull('resume_path')->first();
  if ($profile) {
      $url = $profile->getResumeUrl();
      echo $url . "\n";
  }
  ```
  - [ ] URL generated successfully
  - [ ] URL points to R2 (not Laravel domain)

- [ ] Copy the URL for browser test

- [ ] Exit tinker
  ```php
  exit
  ```

## 🌐 Step 6: Test in Browser

### Test 1: Direct URL Access

- [ ] Open incognito/private browser window
- [ ] Paste the resume URL from Step 5
- [ ] Press Enter
- [ ] Verify:
  - [ ] PDF opens successfully
  - [ ] No redirects
  - [ ] No authentication required
  - [ ] No ERR_TOO_MANY_REDIRECTS error
  - [ ] No 404 error
  - [ ] No 403 error

### Test 2: Admin Panel

- [ ] Login as admin
- [ ] Navigate to Users section
- [ ] Select a user with a resume
- [ ] Click "View Resume" button
- [ ] Verify:
  - [ ] PDF opens in new tab
  - [ ] No errors
  - [ ] No redirects

### Test 3: Recruiter Dashboard

- [ ] Login as recruiter
- [ ] View an application
- [ ] Click "View Profile"
- [ ] Click resume link
- [ ] Verify:
  - [ ] PDF opens successfully
  - [ ] No errors
  - [ ] No redirects

### Test 4: Student Profile

- [ ] Login as student (with uploaded resume)
- [ ] Navigate to Profile page
- [ ] Click "View Resume"
- [ ] Verify:
  - [ ] PDF opens successfully
  - [ ] No errors
  - [ ] No redirects

## ✅ Step 7: Final Verification

- [ ] All diagnostic checks pass
- [ ] Configuration values are correct
- [ ] URL generation returns R2 URLs
- [ ] Direct URL access works
- [ ] Admin panel resume display works
- [ ] Recruiter dashboard resume display works
- [ ] Student profile resume display works
- [ ] No ERR_TOO_MANY_REDIRECTS errors
- [ ] No 404 or 403 errors

## 📝 Step 8: Documentation

- [ ] Document the R2 public URL for future reference
- [ ] Update team documentation with new configuration
- [ ] Note the deployment date and time
- [ ] Keep `.env.backup` file for rollback if needed

## 🎉 Deployment Complete!

If all checkboxes are checked, your R2 configuration is complete and working!

---

## 🚨 Troubleshooting

### If Diagnostic Fails

**Issue**: AWS_URL still shows Laravel domain

- [ ] Re-edit `.env` file
- [ ] Verify AWS_URL is set to R2 public URL
- [ ] Clear caches again
- [ ] Re-run diagnostic

**Issue**: Region is not 'auto'

- [ ] Edit `.env` file
- [ ] Set `AWS_DEFAULT_REGION=auto`
- [ ] Clear caches
- [ ] Re-run diagnostic

**Issue**: Path style endpoint is false

- [ ] Edit `.env` file
- [ ] Set `AWS_USE_PATH_STYLE_ENDPOINT=true`
- [ ] Clear caches
- [ ] Re-run diagnostic

### If Browser Test Fails

**Issue**: Still getting redirects

- [ ] Verify `AWS_URL` in `.env` is R2 public URL
- [ ] Clear browser cache
- [ ] Try incognito mode
- [ ] Clear server caches again

**Issue**: 403 Access Denied

- [ ] Go to Cloudflare R2 Dashboard
- [ ] Open bucket → Settings
- [ ] Enable "Public access"
- [ ] Verify public URL is shown
- [ ] Update `AWS_URL` if needed

**Issue**: 404 Not Found

- [ ] Verify file exists in R2 bucket
  ```bash
  php artisan tinker
  Storage::disk('s3')->files('resumes');
  ```
- [ ] Check file path is correct

### If Configuration Not Taking Effect

- [ ] Clear ALL caches again
  ```bash
  php artisan config:clear
  php artisan config:cache
  php artisan cache:clear
  php artisan route:clear
  php artisan view:clear
  ```
- [ ] Restart PHP-FPM
  ```bash
  sudo systemctl restart php8.2-fpm
  ```
- [ ] Restart web server (if needed)
  ```bash
  sudo systemctl restart nginx
  # or
  sudo systemctl restart apache2
  ```

## 🔄 Rollback Procedure

If you need to rollback:

- [ ] Restore backup `.env` file
  ```bash
  cp .env.backup.YYYYMMDD_HHMMSS .env
  ```
- [ ] Clear caches
  ```bash
  php artisan config:clear
  php artisan config:cache
  php artisan cache:clear
  ```
- [ ] Verify rollback successful

---

## 📊 Deployment Summary

**Date**: _______________  
**Time**: _______________  
**Deployed By**: _______________  

**R2 Public URL**: _______________  
**Bucket Name**: _______________  
**Account ID**: _______________  

**Status**: ⬜ Success / ⬜ Failed / ⬜ Rolled Back

**Notes**:
```
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________
```

---

**Need Help?**
- Review: `R2_FIX_SUMMARY.md`
- Detailed Guide: `CLOUDFLARE_R2_DEPLOYMENT_GUIDE.md`
- Visual Guide: `R2_CONFIGURATION_VISUAL.md`
- Quick Fix: `R2_QUICK_FIX.md`
