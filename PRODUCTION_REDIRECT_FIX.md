# Production Resume Redirect - IMMEDIATE FIX

## 🚨 Your Current Problem

**Symptom**: When you click "View Resume" on your production website, it redirects to your website instead of showing the PDF.

**Root Cause**: Your production `.env` file has `AWS_URL` pointing to your Laravel domain instead of your Cloudflare R2 public bucket URL.

**Impact**: Infinite redirect loop (ERR_TOO_MANY_REDIRECTS)

## ⚡ THE FIX (One Line Change)

### What You Need to Change

In your **production `.env` file**, change this ONE line:

```bash
# WRONG (current - causes redirects):
AWS_URL=https://your-laravel-cloud-domain.com

# CORRECT (what it should be):
AWS_URL=https://pub-YOUR-HASH-HERE.r2.dev
```

**That's it!** Just this one line change will fix the redirect issue.

## 📍 How to Get Your R2 Public URL

1. Go to: https://dash.cloudflare.com/
2. Click **R2** in left sidebar
3. Click your **bucket name**
4. Click **Settings** tab
5. Scroll to **"Public access"** section
6. Copy the **"Public bucket URL"**
   - Format: `https://pub-{hash}.r2.dev`
   - Example: `https://pub-1234567890abcdef.r2.dev`

## 🔧 Complete Fix Steps

### Step 1: Get R2 URL (from above)
Copy your R2 public bucket URL from Cloudflare Dashboard

### Step 2: Update Production .env
```bash
# SSH to your production server
ssh your-server

# Edit .env
nano .env

# Find this line:
AWS_URL=https://your-laravel-domain.com

# Change it to (paste your R2 URL):
AWS_URL=https://pub-YOUR-HASH-HERE.r2.dev

# Also verify these are correct:
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=auto
AWS_USE_PATH_STYLE_ENDPOINT=true

# Save: Ctrl+X, Y, Enter
```

### Step 3: Clear Caches
```bash
php artisan config:clear
php artisan config:cache
php artisan cache:clear
```

### Step 4: Test
1. Go to your production website
2. Login and try to view a resume
3. **Expected**: PDF opens directly (no redirects!)

## ✅ How to Verify It's Fixed

### Test 1: Check Configuration
```bash
# On production server:
php artisan tinker
echo config('filesystems.disks.s3.url');
# Should output: https://pub-{hash}.r2.dev
# Should NOT output: your Laravel domain
exit
```

### Test 2: Test URL Generation
```bash
php artisan tinker
Storage::disk('s3')->url('resumes/test.pdf');
# Should output: https://pub-{hash}.r2.dev/resumes/test.pdf
exit
```

### Test 3: Test in Browser
1. Open your production website
2. Login as any user
3. Try to view a resume
4. **Expected**: PDF opens in new tab (no redirects)

## 📊 Before vs After

### BEFORE (Current - Broken)
```
AWS_URL=https://your-laravel-domain.com
    ↓
Resume URL: https://your-laravel-domain.com/resumes/file.pdf
    ↓
Browser requests Laravel → Redirect loop
    ↓
❌ ERR_TOO_MANY_REDIRECTS
```

### AFTER (Fixed - Working)
```
AWS_URL=https://pub-1234567890abcdef.r2.dev
    ↓
Resume URL: https://pub-1234567890abcdef.r2.dev/resumes/file.pdf
    ↓
Browser requests R2 directly → PDF served
    ↓
✅ PDF OPENS INSTANTLY
```

## 🎯 Key Points

1. **Only ONE line needs to change**: `AWS_URL` in production `.env`
2. **Get the URL from Cloudflare**: R2 → Your Bucket → Settings → Public access
3. **Must be R2 public URL**: Format `https://pub-{hash}.r2.dev`
4. **NOT your Laravel domain**: Don't use your website URL
5. **Clear caches after**: Run the artisan commands
6. **Test immediately**: Try viewing a resume

## 🚨 Common Mistakes

❌ Using Laravel domain: `AWS_URL=https://your-site.com`  
❌ Using R2 endpoint: `AWS_URL=https://account.r2.cloudflarestorage.com`  
❌ Wrong region: `AWS_DEFAULT_REGION=us-east-1` (should be `auto`)  
❌ Wrong path style: `AWS_USE_PATH_STYLE_ENDPOINT=false` (should be `true`)  

✅ Correct R2 public URL: `AWS_URL=https://pub-{hash}.r2.dev`  
✅ Correct region: `AWS_DEFAULT_REGION=auto`  
✅ Correct path style: `AWS_USE_PATH_STYLE_ENDPOINT=true`  

## 📚 Additional Resources

- **Quick Visual Guide**: `R2_REDIRECT_FIX_VISUAL.md`
- **Immediate Fix**: `fix-r2-redirect-now.md`
- **Step-by-Step**: `R2_DEPLOYMENT_STEPS.md`
- **Complete Guide**: `CLOUDFLARE_R2_DEPLOYMENT_GUIDE.md`
- **Diagnostic Tool**: `diagnose-r2-config.php` (upload to production server and run)

## 💡 Why This Happens

Laravel generates resume URLs using `Storage::disk('s3')->url($path)`, which returns:
```
AWS_URL + '/' + $path
```

If `AWS_URL` is your Laravel domain, the URL points back to your website, creating a redirect loop.

If `AWS_URL` is your R2 public URL, the URL points directly to R2, and the PDF loads instantly.

## ✅ Expected Result

After this fix:
- ✅ Resume URLs point to R2 (not Laravel)
- ✅ PDFs open directly in browser
- ✅ Zero redirects
- ✅ No ERR_TOO_MANY_REDIRECTS
- ✅ Works for admin, recruiter, and student
- ✅ All resume features working

---

**SUMMARY**: Change `AWS_URL` in production `.env` to your R2 public bucket URL, clear caches, and test. That's it!

---

**Need Help?** Read `fix-r2-redirect-now.md` for detailed instructions with screenshots.
