# S3 Storage Setup Guide

## Overview

This guide explains how to configure, test, and troubleshoot AWS S3 storage for the Laravel Student Internship Hub. The application uses S3 for persistent file storage of student resume uploads, ensuring files survive across deployments on Laravel Cloud.

## Table of Contents

1. [Environment Configuration](#environment-configuration)
2. [Testing and Verification](#testing-and-verification)
3. [Migration Process](#migration-process)
4. [Troubleshooting](#troubleshooting)

---

## Environment Configuration

### Required Environment Variables

The following environment variables are **required** for S3 storage to function:

```env
# Storage Configuration
FILESYSTEM_DISK=s3

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

**Variable Descriptions:**

- `FILESYSTEM_DISK`: Sets the default storage disk for the application. Must be set to `s3` for S3 storage.
- `AWS_ACCESS_KEY_ID`: Your AWS IAM access key ID with S3 permissions.
- `AWS_SECRET_ACCESS_KEY`: Your AWS IAM secret access key.
- `AWS_DEFAULT_REGION`: The AWS region where your S3 bucket is located (e.g., `us-east-1`, `eu-west-1`).
- `AWS_BUCKET`: The name of your S3 bucket.

### Optional Configuration Variables

These variables are optional and only needed for specific configurations:

```env
# Optional S3 Configuration
AWS_URL=https://your-custom-domain.com
AWS_ENDPOINT=https://custom-s3-endpoint.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

**Optional Variable Descriptions:**

- `AWS_URL`: Custom URL for accessing S3 files (useful for CloudFront CDN integration).
- `AWS_ENDPOINT`: Custom S3-compatible endpoint (for services like MinIO or LocalStack).
- `AWS_USE_PATH_STYLE_ENDPOINT`: Set to `true` for path-style S3 URLs (required for some S3-compatible services).

### Laravel Cloud Auto-Configuration

When deploying to **Laravel Cloud**, AWS credentials are automatically configured:

1. **Attach S3 Bucket**: In the Laravel Cloud dashboard, attach an S3 bucket to your project.
2. **Auto-Population**: Laravel Cloud automatically populates `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, and `AWS_BUCKET`.
3. **Manual Configuration**: You only need to set `FILESYSTEM_DISK=s3` in your `.cloud.yml` file.

**Example `.cloud.yml` configuration:**

```yaml
storage:
  disk: s3

environment:
  FILESYSTEM_DISK: s3
```

### Local Development Configuration

For local development, you have two options:

**Option 1: Use Local Storage (Recommended for Development)**

```env
FILESYSTEM_DISK=public
```

Files will be stored in `storage/app/public/` directory.

**Option 2: Use S3 (For Testing S3 Integration)**

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-dev-access-key
AWS_SECRET_ACCESS_KEY=your-dev-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-dev-bucket
```

Create a separate S3 bucket for development to avoid mixing development and production data.

---

## Testing and Verification

### Automated S3 Connectivity Test

The application includes a built-in command to verify S3 connectivity:

```bash
php artisan storage:test-s3
```

**What This Command Does:**

1. Writes a test file to S3
2. Verifies the file exists on S3
3. Reads the file contents and verifies they match
4. Deletes the test file from S3
5. Reports success or failure

**Expected Output (Success):**

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

**Expected Output (Failure):**

```
Testing S3 connectivity...
1. Writing test file...

❌ S3 connectivity test FAILED
Error: Error executing "PutObject" on "https://s3.amazonaws.com/..."; AWS HTTP error: ...
```

### Manual Testing Steps

#### 1. Test File Upload

1. Log in as a student user
2. Navigate to your profile page
3. Click "Edit Profile" or similar
4. Upload a PDF resume file (max 2MB)
5. Click "Save" or "Update Profile"
6. Verify you see a success message: "Profile updated successfully!"

#### 2. Test File Retrieval

1. After uploading, view your profile page
2. Look for a "View Resume" or "Download Resume" link
3. Click the link
4. Verify the PDF opens or downloads correctly
5. Check that the URL contains your S3 bucket name or CloudFront domain

#### 3. Test File Update

1. Upload a new resume file
2. Verify the old file is replaced
3. Check that the new file is accessible
4. Verify the old file no longer appears in S3 (optional: check S3 console)

### Verify Persistence Across Deployments

To ensure files persist across deployments:

1. Upload a resume file
2. Note the file URL or path
3. Trigger a redeployment:
   ```bash
   git push origin main
   ```
4. Wait for deployment to complete
5. Log in and verify the resume is still accessible
6. Verify the URL hasn't changed

**Why This Works:**

- S3 storage is external to the application server
- Files are not stored in the application's filesystem
- Deployments create new application instances, but S3 data persists

### Checking Application Logs

Monitor logs for S3 operations:

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Filter for resume uploads
tail -f storage/logs/laravel.log | grep "Resume uploaded"

# Filter for S3 errors
tail -f storage/logs/laravel.log | grep -i "s3\|aws"
```

**Successful Upload Log Example:**

```
[2024-01-15 10:30:45] local.INFO: Resume uploaded successfully {"user_id":123,"path":"resumes/1705318245_resume.pdf","disk":"s3","filename":"1705318245_resume.pdf"}
```

---

## Migration Process

### When to Run Migration

Run the migration command when:

- Transitioning from local storage to S3 storage
- You have existing resume files in `storage/app/public/resumes/`
- You want to move files from development to production S3 bucket

**Do NOT run migration if:**

- This is a fresh installation with no existing files
- Files are already stored on S3
- You're using local storage for development

### Migration Command

```bash
php artisan storage:migrate-to-s3
```

### Dry-Run Mode (Recommended First Step)

Before performing the actual migration, run in dry-run mode to preview what will happen:

```bash
php artisan storage:migrate-to-s3 --dry-run
```

**Dry-Run Output Example:**

```
DRY RUN MODE - No files will be migrated

Found 15 profiles with resume files

 15/15 [============================] 100%

Migration Results:
  ✓ Successful: 12
  ✗ Failed: 0
  ⊘ Skipped: 3

Run without --dry-run to perform actual migration
```

**What Dry-Run Does:**

- Scans all profiles with resume files
- Checks if files exist locally
- Reports what would be migrated
- **Does NOT** copy any files to S3
- **Does NOT** modify any data

### Performing Actual Migration

After reviewing the dry-run results, perform the actual migration:

```bash
php artisan storage:migrate-to-s3
```

**Migration Output Example:**

```
Found 15 profiles with resume files

 15/15 [============================] 100%

Migration Results:
  ✓ Successful: 12
  ✗ Failed: 0
  ⊘ Skipped: 3
```

### Understanding Migration Statistics

**Successful**: Files that were successfully copied from local storage to S3.

**Failed**: Files that encountered errors during migration (e.g., S3 write failure, network error).

**Skipped**: Files that don't exist in local storage (e.g., already migrated, file deleted, or path is incorrect).

### Migration Logging

The migration command logs all operations:

**Successful Migration Log:**

```
[2024-01-15 11:00:00] local.INFO: File migrated to S3 {"profile_id":123,"path":"resumes/1705318245_resume.pdf"}
```

**Failed Migration Log:**

```
[2024-01-15 11:00:05] local.ERROR: File migration failed {"profile_id":456,"path":"resumes/1705318250_resume.pdf","error":"Error executing PutObject..."}
```

### Post-Migration Verification

After migration, verify files are accessible:

1. Check a few student profiles with resumes
2. Verify resume links work correctly
3. Check S3 bucket in AWS Console to confirm files exist
4. Monitor application logs for any errors

### Migration Best Practices

1. **Always run dry-run first** to preview the migration
2. **Backup your database** before migration (in case you need to rollback)
3. **Run during low-traffic periods** to minimize user impact
4. **Monitor logs** during and after migration
5. **Keep local files** for a few days after migration as backup
6. **Test thoroughly** before deleting local files

---

## Troubleshooting

### Common S3 Errors and Solutions

#### Error 1: "Failed to upload resume. Please try again."

**Symptoms:**
- Upload form submits but no file appears
- Generic error message shown to user

**Possible Causes:**
1. Missing or invalid AWS credentials
2. S3 bucket doesn't exist
3. Insufficient S3 permissions
4. Network connectivity issues

**Diagnosis Steps:**

```bash
# 1. Test S3 connectivity
php artisan storage:test-s3

# 2. Check configuration
php artisan tinker
>>> config('filesystems.default')
=> "s3"
>>> config('filesystems.disks.s3.bucket')
=> "your-bucket-name"

# 3. Check application logs
tail -f storage/logs/laravel.log | grep "Profile update failed"
```

**Solutions:**

1. **Verify AWS credentials:**
   ```bash
   # Check .env file
   cat .env | grep AWS_
   ```
   Ensure `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` are set.

2. **Verify S3 bucket exists:**
   - Log in to AWS Console
   - Navigate to S3 service
   - Confirm bucket name matches `AWS_BUCKET` value

3. **Check IAM permissions:**
   Ensure your IAM user/role has these permissions:
   ```json
   {
     "Effect": "Allow",
     "Action": [
       "s3:PutObject",
       "s3:GetObject",
       "s3:DeleteObject",
       "s3:ListBucket"
     ],
     "Resource": [
       "arn:aws:s3:::your-bucket-name/*",
       "arn:aws:s3:::your-bucket-name"
     ]
   }
   ```

4. **Test network connectivity:**
   ```bash
   curl -I https://s3.amazonaws.com
   ```

#### Error 2: Resume Link Returns 404 or Null

**Symptoms:**
- Resume link doesn't work
- Resume link is missing from profile page
- Clicking resume link shows 404 error

**Possible Causes:**
1. File doesn't exist on S3
2. Incorrect file path in database
3. S3 bucket permissions issue
4. Missing `AWS_URL` configuration

**Diagnosis Steps:**

```bash
# Check if file exists on S3
php artisan tinker
>>> $profile = App\Models\Profile::find(1);
>>> Storage::disk('s3')->exists($profile->resume_path);
=> false  # File doesn't exist

# Check URL generation
>>> $profile->getResumeUrl();
=> null  # URL generation failed

# Check logs
tail -f storage/logs/laravel.log | grep "Resume URL generation failed"
```

**Solutions:**

1. **Verify file exists on S3:**
   - Log in to AWS Console
   - Navigate to S3 bucket
   - Check if file exists at the path stored in database

2. **Check file path format:**
   ```bash
   php artisan tinker
   >>> $profile = App\Models\Profile::find(1);
   >>> $profile->resume_path;
   => "resumes/1705318245_resume.pdf"  # Should NOT start with /
   ```

3. **Check S3 bucket public access:**
   - S3 bucket should NOT be publicly accessible
   - Application uses signed URLs or IAM credentials

4. **Verify AWS_URL configuration:**
   ```env
   # If using CloudFront
   AWS_URL=https://d1234567890.cloudfront.net
   ```

#### Error 3: Migration Command Fails

**Symptoms:**
- Migration reports failures
- Files not appearing on S3
- Error messages in logs

**Possible Causes:**
1. Source files don't exist locally
2. S3 write permissions issue
3. Network connectivity problems
4. Insufficient disk space

**Diagnosis Steps:**

```bash
# Run in dry-run mode
php artisan storage:migrate-to-s3 --dry-run

# Check logs
tail -f storage/logs/laravel.log | grep "File migration failed"

# Check local files exist
ls -la storage/app/public/resumes/
```

**Solutions:**

1. **Verify source files exist:**
   ```bash
   # Check if files exist locally
   ls -la storage/app/public/resumes/
   ```

2. **Check S3 write permissions:**
   - Ensure IAM user has `s3:PutObject` permission
   - Test with `php artisan storage:test-s3`

3. **Run migration in smaller batches:**
   - Modify command to process fewer profiles at a time
   - Or manually migrate specific profiles

4. **Check network connectivity:**
   ```bash
   ping s3.amazonaws.com
   ```

#### Error 4: "Class 'League\Flysystem\AwsS3V3\AwsS3V3Adapter' not found"

**Symptoms:**
- Application crashes when trying to use S3
- Error about missing Flysystem adapter class

**Cause:**
- Missing `league/flysystem-aws-s3-v3` package

**Solution:**

```bash
# Install the package
composer require league/flysystem-aws-s3-v3 "^3.0"

# Verify installation
composer show | grep flysystem-aws
```

### Diagnosing Upload Failures

**Step-by-step diagnosis:**

1. **Check file validation:**
   - Ensure file is PDF format
   - Ensure file is under 2MB
   - Check browser console for validation errors

2. **Check S3 connectivity:**
   ```bash
   php artisan storage:test-s3
   ```

3. **Check application logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep "Profile update failed"
   ```

4. **Check Laravel logs for exceptions:**
   ```bash
   tail -f storage/logs/laravel.log | grep "ERROR"
   ```

5. **Test with Tinker:**
   ```bash
   php artisan tinker
   >>> Storage::disk('s3')->put('test.txt', 'test content');
   >>> Storage::disk('s3')->exists('test.txt');
   >>> Storage::disk('s3')->delete('test.txt');
   ```

### Diagnosing Retrieval Failures

**Step-by-step diagnosis:**

1. **Verify file exists:**
   ```bash
   php artisan tinker
   >>> $profile = App\Models\Profile::find(1);
   >>> Storage::disk('s3')->exists($profile->resume_path);
   ```

2. **Test URL generation:**
   ```bash
   php artisan tinker
   >>> $profile = App\Models\Profile::find(1);
   >>> $profile->getResumeUrl();
   ```

3. **Check S3 bucket configuration:**
   - Verify bucket exists
   - Check bucket region matches `AWS_DEFAULT_REGION`
   - Verify IAM permissions include `s3:GetObject`

4. **Test direct S3 access:**
   ```bash
   php artisan tinker
   >>> Storage::disk('s3')->url('resumes/test.pdf');
   ```

### Rollback Procedures

If S3 integration fails and you need to rollback:

#### Option 1: Switch to Local Storage (Quick Rollback)

1. **Update environment configuration:**
   ```env
   FILESYSTEM_DISK=public
   ```

2. **Restart application:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Verify local storage works:**
   - Test file upload
   - Files will be stored in `storage/app/public/`

**Note:** Files already on S3 will not be accessible until you switch back to S3.

#### Option 2: Restore from Backup

If you need to restore files from S3 to local storage:

1. **Download files from S3:**
   ```bash
   # Using AWS CLI
   aws s3 sync s3://your-bucket-name/resumes/ storage/app/public/resumes/
   ```

2. **Update environment:**
   ```env
   FILESYSTEM_DISK=public
   ```

3. **Verify files are accessible:**
   ```bash
   ls -la storage/app/public/resumes/
   ```

#### Option 3: Fix S3 Configuration

If the issue is configuration-related:

1. **Verify all environment variables:**
   ```bash
   php artisan tinker
   >>> config('filesystems.disks.s3');
   ```

2. **Update credentials if needed:**
   - Update `.env` file
   - Clear configuration cache:
     ```bash
     php artisan config:clear
     ```

3. **Test connectivity:**
   ```bash
   php artisan storage:test-s3
   ```

### Getting Help

If you're still experiencing issues:

1. **Check Laravel logs:**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **Check AWS CloudTrail logs** (if enabled) for S3 API calls

3. **Enable debug mode temporarily:**
   ```env
   APP_DEBUG=true
   LOG_LEVEL=debug
   ```

4. **Contact support** with:
   - Error messages from logs
   - Output of `php artisan storage:test-s3`
   - Configuration (without credentials)
   - Steps to reproduce the issue

---

## Additional Resources

- [Laravel File Storage Documentation](https://laravel.com/docs/filesystem)
- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [Flysystem AWS S3 Adapter](https://github.com/thephpleague/flysystem-aws-s3-v3)
- [Laravel Cloud Documentation](https://cloud.laravel.com/docs)

---

## Quick Reference

### Environment Variables Checklist

```env
# Required
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket

# Optional
AWS_URL=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Common Commands

```bash
# Test S3 connectivity
php artisan storage:test-s3

# Preview migration
php artisan storage:migrate-to-s3 --dry-run

# Perform migration
php artisan storage:migrate-to-s3

# Clear configuration cache
php artisan config:clear

# View logs
tail -f storage/logs/laravel.log
```

### IAM Permissions Required

```json
{
  "Effect": "Allow",
  "Action": [
    "s3:PutObject",
    "s3:GetObject",
    "s3:DeleteObject",
    "s3:ListBucket"
  ],
  "Resource": [
    "arn:aws:s3:::your-bucket-name/*",
    "arn:aws:s3:::your-bucket-name"
  ]
}
```
