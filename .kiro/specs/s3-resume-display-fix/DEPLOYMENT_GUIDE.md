# S3 Resume Display Fix - Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the S3 resume display fix to production.

## Prerequisites

- Access to production server or Laravel Cloud dashboard
- AWS S3 bucket created and configured
- AWS IAM credentials with S3 read/write permissions
- SSH access to production server (if not using Laravel Cloud dashboard)

## Deployment Steps

### Step 1: Update Production Environment Variables

Update the `.env` file in production with the following configuration:

```bash
# Change FILESYSTEM_DISK from 'local' to 's3'
FILESYSTEM_DISK=s3

# Add AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_actual_access_key_here
AWS_SECRET_ACCESS_KEY=your_actual_secret_key_here
AWS_DEFAULT_REGION=us-east-1  # or your bucket's region
AWS_BUCKET=your_actual_bucket_name_here
AWS_URL=https://your_actual_bucket_name.s3.amazonaws.com
AWS_ENDPOINT=https://your_laravel_cloud_endpoint_here  # if using Laravel Cloud
AWS_USE_PATH_STYLE_ENDPOINT=false
```

**Important Notes:**
- Replace all placeholder values with actual AWS credentials
- Ensure the AWS IAM user has `s3:GetObject`, `s3:PutObject`, `s3:DeleteObject` permissions
- The `AWS_URL` should match your bucket's region and name
- For Laravel Cloud, use the provided S3-compatible endpoint

### Step 2: Clear Configuration Cache

SSH into production server and run:

```bash
php artisan config:clear
php artisan config:cache
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

If using Laravel Cloud, you can run these commands through the dashboard or trigger a deployment.

### Step 3: Test S3 Connection

Verify S3 connectivity:

```bash
php artisan storage:test-s3
```

Expected output:
```
Testing S3 connectivity...
1. Writing test file...
   ✓ Write successful
2. Checking file exists...
   ✓ File exists
3. Reading file contents...
   ✓ Read successful
4. Deleting test file...
   ✓ Delete successful

✅ S3 connectivity test PASSED
```

If the test fails, check:
- AWS credentials are correct
- S3 bucket exists and is accessible
- IAM permissions are properly configured
- Network connectivity to S3 endpoint

### Step 4: Test Resume URL Generation

Run in production tinker:

```bash
php artisan tinker
```

Then execute:

```php
// Verify configuration
config('filesystems.default'); // Should return 's3'
config('filesystems.disks.s3.bucket'); // Should return your bucket name

// Test URL generation
$profile = App\Models\Profile::whereNotNull('resume_path')->first();
$url = $profile->getResumeUrl();
echo $url; // Should output a valid S3 URL starting with 'https://'
```

Expected URL format:
- Signed URL (private bucket): `https://bucket-name.s3.region.amazonaws.com/resumes/filename.pdf?X-Amz-Algorithm=...`
- Public URL (public bucket): `https://bucket-name.s3.region.amazonaws.com/resumes/filename.pdf`

### Step 5: Manual Testing Across User Roles

#### Admin Panel Resume View

1. Login as admin user
2. Navigate to `/admin/users`
3. Select a user who has uploaded a resume
4. Click "View Resume" link
5. **Expected**: PDF opens successfully in browser without 404/500 errors

#### Recruiter Application Profile

1. Login as recruiter user
2. Navigate to recruiter dashboard
3. View an application with a resume
4. Click "View Profile" button
5. **Expected**: Modal displays with resume URL, clicking opens PDF successfully

#### Student Profile Page

1. Login as student user who has uploaded a resume
2. Navigate to `/profile`
3. Click "View Resume" button
4. **Expected**: PDF opens successfully without redirect loops or errors

### Step 6: Run Automated Tests

Run the bug condition exploration test (should now PASS):

```bash
php artisan test --filter=S3ResumeDisplayBugConditionTest
```

Expected output:
```
PASS  Tests\Unit\S3ResumeDisplayBugConditionTest
✓ s3 resume urls generated successfully with proper configuration
✓ resume url returns null with missing aws configuration
✓ disk mismatch causes file existence check failure
✓ s3 url generation works for various filenames

Tests:    4 passed (12 assertions)
Duration: 0.50s
```

Run the preservation tests (should still PASS):

```bash
php artisan test --filter=S3ResumeDisplayPreservationTest
```

Expected output:
```
PASS  Tests\Unit\S3ResumeDisplayPreservationTest
✓ local resume uploads store in public storage
✓ local resume url generation uses public storage url
✓ local development handles various filename formats
✓ resume paths stored as relative paths
✓ resume url returns null for nonexistent files
✓ resume url returns null for null resume path
✓ has resume file checks existence correctly
✓ local development handles various file sizes
✓ error handling returns gracefully

Tests:    9 passed (35 assertions)
Duration: 1.20s
```

### Step 7: Monitor Production Logs

After deployment, monitor application logs for any S3-related errors:

```bash
tail -f storage/logs/laravel.log
```

Look for:
- `Resume file not found on S3` warnings (indicates missing files)
- `Resume URL generation failed` errors (indicates configuration issues)
- AWS SDK exceptions (indicates credential or permission issues)

### Step 8: Migrate Existing Local Files to S3 (if needed)

If you have existing resume files in local storage that need to be migrated to S3:

```bash
php artisan migrate:files-to-s3
```

This command will:
1. Find all profiles with `resume_path` set
2. Check if files exist in local storage
3. Upload files to S3
4. Verify upload success
5. Optionally delete local files after successful migration

## Rollback Plan

If issues occur after deployment:

1. **Immediate Rollback**: Change `FILESYSTEM_DISK=s3` back to `FILESYSTEM_DISK=local`
2. **Clear Cache**: Run `php artisan config:clear && php artisan config:cache`
3. **Verify**: Test resume display works with local storage
4. **Investigate**: Review logs to identify the issue
5. **Fix and Redeploy**: Address the issue and follow deployment steps again

## Troubleshooting

### Issue: Resume URLs return NULL

**Possible Causes:**
- AWS credentials not configured correctly
- S3 bucket name incorrect
- Files don't exist in S3 bucket
- IAM permissions insufficient

**Solution:**
1. Verify `config('filesystems.disks.s3.bucket')` returns correct bucket name
2. Check AWS credentials are valid
3. Verify files exist in S3: `Storage::disk('s3')->files('resumes')`
4. Check IAM user has `s3:GetObject` permission

### Issue: 403 Forbidden errors when accessing resume URLs

**Possible Causes:**
- S3 bucket is private and signed URLs are not working
- IAM credentials don't have permission to generate signed URLs
- Signed URL has expired (1 hour expiration)

**Solution:**
1. Verify IAM user has `s3:GetObject` permission
2. Check bucket CORS configuration allows browser access
3. Ensure `AWS_SECRET_ACCESS_KEY` is correct
4. Refresh the page to generate a new signed URL

### Issue: ERR_TOO_MANY_REDIRECTS

**Possible Causes:**
- `AWS_URL` is incorrect or missing
- Bucket region mismatch
- Endpoint configuration issue

**Solution:**
1. Verify `AWS_URL` matches bucket region: `https://bucket-name.s3.region.amazonaws.com`
2. Check `AWS_DEFAULT_REGION` matches bucket's actual region
3. For Laravel Cloud, verify `AWS_ENDPOINT` is correct

### Issue: Local development broken after deployment

**Possible Causes:**
- Local `.env` file was accidentally updated with `FILESYSTEM_DISK=s3`
- Local environment is trying to use S3 configuration

**Solution:**
1. Verify local `.env` has `FILESYSTEM_DISK=local`
2. Clear local configuration cache: `php artisan config:clear`
3. Run preservation tests to verify: `php artisan test --filter=S3ResumeDisplayPreservationTest`

## Verification Checklist

After deployment, verify:

- [ ] `FILESYSTEM_DISK=s3` in production `.env`
- [ ] All AWS configuration variables are set with actual values
- [ ] Configuration cache cleared and rebuilt
- [ ] S3 connection test passes
- [ ] Resume URL generation returns valid HTTPS URLs
- [ ] Admin panel resume view works
- [ ] Recruiter application profile resume view works
- [ ] Student profile resume view works
- [ ] Bug condition exploration test passes
- [ ] Preservation tests still pass
- [ ] No errors in production logs
- [ ] Local development still works with `FILESYSTEM_DISK=local`

## Support

If you encounter issues not covered in this guide:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check AWS CloudTrail for S3 API errors
3. Review the bugfix design document: `.kiro/specs/s3-resume-display-fix/design.md`
4. Review the bugfix requirements: `.kiro/specs/s3-resume-display-fix/bugfix.md`

## References

- [Laravel File Storage Documentation](https://laravel.com/docs/filesystem)
- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [Laravel Cloud Documentation](https://cloud.laravel.com/docs)
