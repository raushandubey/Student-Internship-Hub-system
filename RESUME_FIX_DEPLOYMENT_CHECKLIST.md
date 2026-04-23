# Resume 404 Fix - Deployment Checklist

## Pre-Deployment Checklist

### Local Testing
- [ ] Code changes committed to Git
- [ ] All caches cleared locally
- [ ] Resume upload tested locally
- [ ] Resume viewing tested locally
- [ ] Resume download tested locally
- [ ] Error page tested (try accessing non-existent file)
- [ ] Routes verified: `php artisan route:list --name=resume`
- [ ] No syntax errors: `php artisan about`

### Code Review
- [ ] ProfileController updated with error handling
- [ ] Profile Model has getResumeUrl() and hasResumeFile()
- [ ] ProfileService uses model methods
- [ ] ResumeController created and working
- [ ] Routes added to web.php
- [ ] Error view created
- [ ] All files committed to version control

---

## Deployment Steps

### Step 1: Deploy Code
```bash
# Push to production
git push production main

# Or deploy via your platform
# Laravel Cloud: automatic on push
# Heroku: git push heroku main
# Railway: automatic on push
```

- [ ] Code deployed successfully
- [ ] No deployment errors
- [ ] Application accessible

### Step 2: Run Storage Fix Script
```bash
# SSH into production server
ssh user@your-server.com

# Navigate to project directory
cd /path/to/your/project

# Run fix script
bash fix-resume-storage.sh

# Or run commands manually:
php artisan storage:link
mkdir -p storage/app/public/resumes
chmod -R 775 storage
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

- [ ] Script executed without errors
- [ ] Symlink created
- [ ] Directories created
- [ ] Permissions set
- [ ] Caches cleared

### Step 3: Verify Setup
```bash
# Check symlink
ls -la public/storage
# Should show: storage -> ../storage/app/public

# Check directory
ls -la storage/app/public/resumes/
# Should exist and be writable

# Check routes
php artisan route:list --name=resume
# Should show 3 resume routes

# Check permissions
ls -la storage/app/public/
# Should show: drwxrwxr-x (775)
```

- [ ] Symlink exists and points correctly
- [ ] Resume directory exists
- [ ] Permissions are correct (775)
- [ ] Routes are registered

---

## Post-Deployment Testing

### Test 1: Upload Resume
1. [ ] Log in as a student
2. [ ] Go to `/profile/edit`
3. [ ] Upload a PDF resume (max 2MB)
4. [ ] Click "Update Profile"
5. [ ] See success message
6. [ ] No errors in browser console

### Test 2: View Resume
1. [ ] Go to `/profile/show`
2. [ ] See resume link/button
3. [ ] Click resume link
4. [ ] PDF opens in browser
5. [ ] URL is `/storage/resumes/filename.pdf` or `/resume/serve/filename.pdf`
6. [ ] No 404 error

### Test 3: Download Resume
1. [ ] Go to profile page
2. [ ] Click download button (if available)
3. [ ] File downloads successfully
4. [ ] Filename is correct
5. [ ] File opens correctly

### Test 4: Check File Existence
```bash
# On server
ls -la storage/app/public/resumes/
# Should show uploaded PDF file

# Check database
php artisan tinker
$profile = Profile::whereNotNull('resume_path')->first();
echo $profile->resume_path;
echo $profile->getResumeUrl();
echo $profile->hasResumeFile() ? 'EXISTS' : 'MISSING';
```

- [ ] File exists on disk
- [ ] Database path is correct
- [ ] getResumeUrl() returns valid URL
- [ ] hasResumeFile() returns true

### Test 5: Error Handling
1. [ ] Try accessing non-existent resume: `/storage/resumes/fake.pdf`
2. [ ] Should see custom 404 page
3. [ ] Page has "Upload New Resume" button
4. [ ] Page has "Go to Dashboard" button
5. [ ] No server error (500)

### Test 6: Check Logs
```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# Look for:
# ✅ "Resume uploaded successfully"
# ❌ "Resume file not found"
# ❌ "Resume serving failed"
```

- [ ] No errors in logs
- [ ] Upload events logged
- [ ] No 404 errors
- [ ] No permission errors

---

## Production Environment Checks

### For Ephemeral Storage (Laravel Cloud, Heroku, Railway)

#### Test File Persistence
1. [ ] Upload a resume
2. [ ] Note the filename
3. [ ] Trigger a redeploy (push dummy commit)
4. [ ] Wait for deployment to complete
5. [ ] Try accessing the resume
6. [ ] **Expected**: File is GONE (404 error)
7. [ ] **Action Required**: Configure S3

#### Configure S3 (If Files Disappear)
```bash
# Install AWS SDK
composer require league/flysystem-aws-s3-v3 "^3.0"

# Update .env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key-here
AWS_SECRET_ACCESS_KEY=your-secret-here
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.amazonaws.com

# Deploy changes
git add .
git commit -m "Configure S3 storage"
git push production main

# Clear config cache
php artisan config:clear
```

- [ ] S3 credentials configured
- [ ] Bucket created and accessible
- [ ] Files upload to S3
- [ ] Files persist after redeploy
- [ ] URLs work correctly

### For Persistent Storage (VPS, Dedicated Server)

#### Test File Persistence
1. [ ] Upload a resume
2. [ ] Note the filename
3. [ ] Redeploy application
4. [ ] Try accessing the resume
5. [ ] **Expected**: File still EXISTS
6. [ ] **Action**: No S3 needed (but recommended for backups)

#### Set Up Backups (Recommended)
```bash
# Create backup script
cat > backup-resumes.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf /backups/resumes_$DATE.tar.gz storage/app/public/resumes/
find /backups -name "resumes_*.tar.gz" -mtime +30 -delete
EOF

chmod +x backup-resumes.sh

# Add to crontab (daily at 2 AM)
crontab -e
# Add: 0 2 * * * /path/to/backup-resumes.sh
```

- [ ] Backup script created
- [ ] Cron job configured
- [ ] Test backup runs successfully
- [ ] Old backups are cleaned up

---

## Monitoring Setup

### Set Up Log Monitoring
```bash
# Create log monitoring script
cat > monitor-resume-errors.sh << 'EOF'
#!/bin/bash
tail -f storage/logs/laravel.log | grep -i "resume" | while read line; do
    if echo "$line" | grep -q "ERROR\|failed\|not found"; then
        echo "[ALERT] $line"
        # Optional: Send to Slack/email
    fi
done
EOF

chmod +x monitor-resume-errors.sh
```

- [ ] Log monitoring set up
- [ ] Alerts configured (optional)
- [ ] Team notified of monitoring

### Set Up Uptime Monitoring
Use a service like:
- [ ] UptimeRobot
- [ ] Pingdom
- [ ] StatusCake

Monitor these URLs:
- [ ] `/profile/edit` (upload page)
- [ ] `/storage/resumes/test.pdf` (file serving)
- [ ] `/resume/serve/test.pdf` (fallback route)

---

## Rollback Plan

### If Something Goes Wrong

#### Quick Rollback
```bash
# Revert to previous deployment
git revert HEAD
git push production main

# Or rollback via platform
# Laravel Cloud: Use dashboard
# Heroku: heroku rollback
# Railway: Use dashboard
```

#### Manual Fix
```bash
# If only storage is broken
php artisan storage:link
chmod -R 775 storage

# If routes are broken
php artisan route:clear
php artisan cache:clear

# If config is broken
php artisan config:clear
```

- [ ] Rollback plan tested
- [ ] Team knows rollback procedure
- [ ] Backup of previous version available

---

## Success Criteria

### All Must Pass
- [ ] ✅ Resume upload works
- [ ] ✅ Resume viewing works (no 404)
- [ ] ✅ Resume download works
- [ ] ✅ Error page shows for missing files
- [ ] ✅ No errors in logs
- [ ] ✅ Files persist (or S3 configured)
- [ ] ✅ Symlink exists
- [ ] ✅ Permissions correct
- [ ] ✅ Routes registered
- [ ] ✅ Security measures in place

### Performance Checks
- [ ] ✅ Upload completes in < 5 seconds
- [ ] ✅ File serving is fast (< 1 second)
- [ ] ✅ No memory issues
- [ ] ✅ No disk space issues

### Security Checks
- [ ] ✅ Only PDF files accepted
- [ ] ✅ File size limit enforced (2MB)
- [ ] ✅ Filename sanitization working
- [ ] ✅ Directory traversal prevented
- [ ] ✅ Authentication required

---

## Documentation Checklist

### Team Documentation
- [ ] Update internal wiki/docs
- [ ] Document S3 configuration (if used)
- [ ] Document backup procedures
- [ ] Document monitoring setup
- [ ] Share deployment checklist with team

### User Documentation
- [ ] Update user guide (if exists)
- [ ] Document resume upload process
- [ ] Document file size limits
- [ ] Document supported formats

---

## Final Sign-Off

### Deployment Lead
- [ ] All tests passed
- [ ] No critical errors
- [ ] Monitoring in place
- [ ] Team notified
- [ ] Documentation updated

**Signed**: ________________  
**Date**: ________________  
**Time**: ________________

### QA Approval
- [ ] Functional testing complete
- [ ] Security testing complete
- [ ] Performance testing complete
- [ ] User acceptance testing complete

**Signed**: ________________  
**Date**: ________________

---

## Post-Deployment Actions

### Week 1
- [ ] Monitor error logs daily
- [ ] Check 404 rate
- [ ] Verify file persistence
- [ ] Collect user feedback

### Week 2-4
- [ ] Review storage usage
- [ ] Optimize if needed
- [ ] Consider CDN (if high traffic)
- [ ] Plan S3 migration (if not done)

### Month 2+
- [ ] Review backup strategy
- [ ] Audit security
- [ ] Optimize performance
- [ ] Plan improvements

---

## Support Contacts

**Technical Lead**: ________________  
**DevOps**: ________________  
**On-Call**: ________________  
**Emergency**: ________________

---

**Checklist Version**: 1.0  
**Last Updated**: 2026-04-24  
**Next Review**: 2026-05-24
