# S3 Resume Display Fix - Bugfix Design

## Overview

This bugfix addresses a critical production issue where resume files stored in S3/Object Storage fail to display across all user roles (admin, recruiter, student) in the Laravel application deployed on Laravel Cloud. The root cause is a misconfiguration where `FILESYSTEM_DISK=local` is set instead of `s3`, combined with missing AWS configuration variables required for S3 URL generation. The fix involves updating environment configuration, ensuring proper S3 disk usage throughout the codebase, and verifying URL generation works correctly for both public and private S3 buckets.

## Glossary

- **Bug_Condition (C)**: The condition that triggers the bug - when `FILESYSTEM_DISK=local` is configured in production with S3 storage, causing resume URL generation to fail
- **Property (P)**: The desired behavior - resume URLs should be valid S3 URLs (signed or public) that successfully load PDF files in browsers
- **Preservation**: Existing local development behavior with `FILESYSTEM_DISK=local` must continue to work unchanged
- **getResumeUrl()**: The method in `Profile` model (`app/Models/Profile.php`) that generates resume URLs by checking file existence and creating S3 signed URLs or public URLs
- **FILESYSTEM_DISK**: The Laravel environment variable that determines which storage disk (local, public, s3) is used for file operations
- **Signed URL**: A temporary S3 URL with authentication parameters that expires after a set time (1 hour in this case), used for private buckets
- **Public URL**: A permanent S3 URL without authentication, used for public buckets

## Bug Details

### Bug Condition

The bug manifests when the application is deployed to Laravel Cloud with S3 storage configured, but `FILESYSTEM_DISK` is set to `local` instead of `s3`, and AWS configuration variables are missing or incomplete. The `Profile::getResumeUrl()` method attempts to check file existence on S3 using `Storage::disk('s3')->exists()` while the default disk is `local`, causing existence checks to fail and URL generation to return null or invalid URLs.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type EnvironmentConfiguration
  OUTPUT: boolean
  
  RETURN input.FILESYSTEM_DISK == 'local'
         AND input.deploymentEnvironment == 'production'
         AND input.storageBackend == 's3'
         AND (input.AWS_ACCESS_KEY_ID IS NULL 
              OR input.AWS_SECRET_ACCESS_KEY IS NULL
              OR input.AWS_DEFAULT_REGION IS NULL
              OR input.AWS_BUCKET IS NULL
              OR input.AWS_URL IS NULL)
         AND resumeFileExistsOnS3(input.resume_path)
         AND NOT validResumeUrlGenerated()
END FUNCTION
```

### Examples

- **Admin Panel Resume View**: Admin navigates to `/admin/users/{id}` and clicks "View Resume" link. The link calls `$user->profile->getResumeUrl()` which returns null because `Storage::disk('s3')->exists()` fails when default disk is `local`. Result: 404 error or broken link.

- **Recruiter Application Profile**: Recruiter clicks "View Profile" button which makes AJAX call to `/api/recruiter/applications/{id}/profile`. The response includes `resume_url: null` because `getResumeUrl()` cannot generate valid S3 URLs without proper AWS configuration. Result: "No resume uploaded" message despite file existing in S3.

- **Student Profile Page**: Student views their profile at `/profile` and clicks "View Resume" button. The link uses `$profile->getResumeUrl()` which attempts to generate S3 URL but fails due to missing `AWS_URL` configuration. Result: ERR_TOO_MANY_REDIRECTS or 500 error.

- **Edge Case - Local Development**: Developer runs application locally with `FILESYSTEM_DISK=local` and uploads resume. The file is stored in `storage/app/public/resumes/` and `getResumeUrl()` correctly generates `http://localhost:8000/storage/resumes/filename.pdf`. Expected behavior: This should continue to work unchanged.

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Local development with `FILESYSTEM_DISK=local` must continue to work exactly as before, storing files in `storage/app/public/` and generating URLs via `Storage::disk('public')->url()`
- Resume upload functionality in `ProfileController::update()` must continue to delete old files before uploading new ones
- Authorization checks in `ResumeController::getUrl()` must continue to enforce that students can only access their own resumes
- Database storage of resume paths must continue to use relative paths (e.g., `resumes/filename.pdf`) not absolute URLs
- Error handling and logging for failed file operations must continue to log context and return user-friendly messages

**Scope:**
All inputs that do NOT involve production S3 configuration should be completely unaffected by this fix. This includes:
- Local development environment with `FILESYSTEM_DISK=local`
- Resume upload and deletion operations
- Authorization and access control logic
- Database schema and data structure

## Hypothesized Root Cause

Based on the bug description and code analysis, the most likely issues are:

1. **Incorrect FILESYSTEM_DISK Configuration**: The `.env` file has `FILESYSTEM_DISK=local` instead of `FILESYSTEM_DISK=s3` in production, causing all `config('filesystems.default')` calls to return `local` instead of `s3`
   - `ProfileController::update()` uses `$disk = config('filesystems.default')` which returns `local`
   - Files may be uploaded to local storage which doesn't persist in cloud environments

2. **Missing AWS Configuration Variables**: The `.env` file is missing required AWS variables for S3 URL generation
   - `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` are required for S3 operations
   - `AWS_URL` is required for generating public S3 URLs
   - `AWS_ENDPOINT` may be required for Laravel Cloud's S3-compatible storage

3. **Disk Mismatch in getResumeUrl()**: The `Profile::getResumeUrl()` method checks `Storage::disk('s3')->exists()` when default disk is `local`, causing existence checks to fail
   - The method hardcodes `Storage::disk('s3')` instead of using `config('filesystems.default')`
   - This causes file existence checks to look in the wrong storage location

4. **Incomplete S3 Configuration in config/filesystems.php**: The S3 disk configuration may be incomplete or missing required parameters
   - Missing `throw` and `report` options for error handling
   - Missing `use_path_style_endpoint` for Laravel Cloud compatibility

## Correctness Properties

Property 1: Bug Condition - S3 Resume URLs Generated Successfully

_For any_ production environment where `FILESYSTEM_DISK=s3` is configured with complete AWS credentials and a resume file exists in S3, the `Profile::getResumeUrl()` method SHALL generate valid S3 URLs (either signed temporary URLs for private buckets or public URLs for public buckets) that successfully load PDF files in browsers without 404, 500, or redirect errors.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8**

Property 2: Preservation - Local Development Behavior

_For any_ local development environment where `FILESYSTEM_DISK=local` is configured, the fixed code SHALL produce exactly the same behavior as the original code, preserving local file storage in `storage/app/public/`, URL generation via `Storage::disk('public')->url()`, and all existing upload/delete/authorization functionality.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**

## Fix Implementation

### Changes Required

Assuming our root cause analysis is correct:

**File 1**: `.env` (Production Environment)

**Specific Changes**:
1. **Update FILESYSTEM_DISK**: Change from `FILESYSTEM_DISK=local` to `FILESYSTEM_DISK=s3`
   - This ensures all file operations use S3 in production
   - Affects `config('filesystems.default')` calls throughout the application

2. **Add AWS Configuration Variables**: Add all required AWS credentials and configuration
   ```
   AWS_ACCESS_KEY_ID=your_access_key_here
   AWS_SECRET_ACCESS_KEY=your_secret_key_here
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=your_bucket_name_here
   AWS_URL=https://your_bucket_name.s3.amazonaws.com
   AWS_ENDPOINT=https://your_endpoint_here (if using Laravel Cloud S3-compatible storage)
   AWS_USE_PATH_STYLE_ENDPOINT=false
   ```
   - `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY`: Required for S3 authentication
   - `AWS_DEFAULT_REGION`: Required for S3 bucket location (e.g., `us-east-1`, `eu-west-1`)
   - `AWS_BUCKET`: The S3 bucket name where resumes are stored
   - `AWS_URL`: The base URL for generating public S3 URLs (e.g., `https://bucket-name.s3.amazonaws.com`)
   - `AWS_ENDPOINT`: Custom endpoint for S3-compatible storage (Laravel Cloud, DigitalOcean Spaces, etc.)
   - `AWS_USE_PATH_STYLE_ENDPOINT`: Set to `false` for AWS S3, `true` for some S3-compatible services

3. **Verify Existing Variables**: Ensure these remain unchanged for local development
   ```
   FILESYSTEM_DISK=local (for local development only)
   ```

**File 2**: `config/filesystems.php`

**Specific Changes**:
1. **Verify S3 Disk Configuration**: Ensure the S3 disk has all required parameters
   ```php
   's3' => [
       'driver' => 's3',
       'key' => env('AWS_ACCESS_KEY_ID'),
       'secret' => env('AWS_SECRET_ACCESS_KEY'),
       'region' => env('AWS_DEFAULT_REGION'),
       'bucket' => env('AWS_BUCKET'),
       'url' => env('AWS_URL'),
       'endpoint' => env('AWS_ENDPOINT'),
       'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
       'throw' => false,
       'report' => false,
   ],
   ```
   - The configuration already exists and is correct
   - No changes needed unless `throw` or `report` options are missing

**File 3**: `app/Models/Profile.php`

**Specific Changes**:
1. **No Code Changes Required**: The `getResumeUrl()` method already correctly handles both S3 and local storage
   - Line 42-44: Checks `config('filesystems.default')` to determine disk
   - Line 47-49: For S3, checks file existence with `Storage::disk('s3')->exists()`
   - Line 54-56: Generates signed URL with `Storage::disk('s3')->temporaryUrl()` for private buckets
   - Line 58-63: Falls back to `Storage::disk('s3')->url()` for public buckets
   - Line 67-69: For local, checks existence with `Storage::disk('public')->exists()`
   - The method will automatically work correctly once `.env` is updated

2. **Verification Only**: Confirm the method logic is correct (already verified in code review)

**File 4**: `app/Http/Controllers/ProfileController.php`

**Specific Changes**:
1. **No Code Changes Required**: The `update()` method already correctly uses `config('filesystems.default')`
   - Line 51: `$disk = config('filesystems.default')` dynamically gets the configured disk
   - Line 54-61: Deletes old resume from the configured disk
   - Line 64-66: Stores new resume to the configured disk with `storeAs()`
   - The controller will automatically use S3 once `.env` is updated

2. **Verification Only**: Confirm upload logic is correct (already verified in code review)

**File 5**: `app/Http/Controllers/Recruiter/RecruiterApplicationController.php`

**Specific Changes**:
1. **No Code Changes Required**: The `getProfile()` method already correctly calls `getResumeUrl()`
   - Line 165: `'resume_url' => $profile?->getResumeUrl() ?? null` returns the generated URL
   - The method will automatically return valid S3 URLs once `.env` is updated

2. **Verification Only**: Confirm AJAX response includes resume_url (already verified in code review)

### Configuration Deployment Steps

**Step 1: Update Production .env File**
```bash
# SSH into production server or use Laravel Cloud dashboard
# Edit .env file and update these variables:

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name_here
AWS_URL=https://your_bucket_name.s3.amazonaws.com
AWS_ENDPOINT=https://your_endpoint_here
AWS_USE_PATH_STYLE_ENDPOINT=false
```

**Step 2: Clear Configuration Cache**
```bash
php artisan config:clear
php artisan config:cache
```

**Step 3: Verify S3 Connection**
```bash
php artisan tinker
# Run in tinker:
Storage::disk('s3')->exists('resumes/test.pdf')
# Should return true/false without errors
```

**Step 4: Test Resume URL Generation**
```bash
php artisan tinker
# Run in tinker:
$profile = App\Models\Profile::whereNotNull('resume_path')->first();
$url = $profile->getResumeUrl();
echo $url;
# Should output a valid S3 URL
```

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bug on unfixed configuration, then verify the fix works correctly and preserves existing local development behavior.

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE implementing the fix. Confirm or refute the root cause analysis. If we refute, we will need to re-hypothesize.

**Test Plan**: Manually test resume URL generation in production environment with `FILESYSTEM_DISK=local` configuration. Run these tests on the UNFIXED configuration to observe failures and understand the root cause.

**Test Cases**:
1. **Admin Panel Resume View Test**: Navigate to `/admin/users/{id}` with a user who has uploaded a resume. Click "View Resume" link. (will fail on unfixed config - returns null or 404)
2. **Recruiter AJAX Profile Test**: Open browser DevTools Network tab, click "View Profile" button on an application. Inspect AJAX response for `resume_url` field. (will fail on unfixed config - returns null)
3. **Student Profile Resume Test**: Login as student with uploaded resume, navigate to `/profile`, click "View Resume" button. (will fail on unfixed config - 404, 500, or redirect loop)
4. **S3 File Existence Test**: Run `Storage::disk('s3')->exists('resumes/filename.pdf')` in tinker with `FILESYSTEM_DISK=local`. (will fail on unfixed config - throws exception or returns false)

**Expected Counterexamples**:
- Resume URLs are null or invalid when `FILESYSTEM_DISK=local` in production
- Possible causes: disk mismatch, missing AWS configuration, incorrect environment variable

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds (production with S3), the fixed configuration produces the expected behavior (valid S3 URLs).

**Pseudocode:**
```
FOR ALL environment WHERE isBugCondition(environment) DO
  environment.FILESYSTEM_DISK := 's3'
  environment.AWS_ACCESS_KEY_ID := valid_key
  environment.AWS_SECRET_ACCESS_KEY := valid_secret
  environment.AWS_DEFAULT_REGION := valid_region
  environment.AWS_BUCKET := valid_bucket
  environment.AWS_URL := valid_url
  
  result := Profile::getResumeUrl()
  
  ASSERT result IS NOT NULL
  ASSERT result STARTS_WITH 'https://'
  ASSERT result CONTAINS bucket_name
  ASSERT httpGet(result).statusCode == 200
  ASSERT httpGet(result).contentType == 'application/pdf'
END FOR
```

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold (local development), the fixed configuration produces the same result as the original configuration.

**Pseudocode:**
```
FOR ALL environment WHERE NOT isBugCondition(environment) DO
  ASSERT Profile::getResumeUrl_original(environment) = Profile::getResumeUrl_fixed(environment)
  ASSERT ProfileController::update_original(environment) = ProfileController::update_fixed(environment)
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many test cases automatically across the input domain (different file types, sizes, names)
- It catches edge cases that manual unit tests might miss (special characters, long filenames, concurrent uploads)
- It provides strong guarantees that behavior is unchanged for all non-buggy inputs (local development scenarios)

**Test Plan**: Observe behavior on UNFIXED code first for local development, then write property-based tests capturing that behavior.

**Test Cases**:
1. **Local Upload Preservation**: Observe that resume uploads work correctly in local development with `FILESYSTEM_DISK=local`, then write test to verify this continues after fix
2. **Local URL Generation Preservation**: Observe that `getResumeUrl()` returns `http://localhost:8000/storage/resumes/filename.pdf` in local development, then write test to verify this continues after fix
3. **Authorization Preservation**: Observe that students cannot access other students' resumes, then write test to verify this continues after fix

### Unit Tests

- Test `Profile::getResumeUrl()` with S3 configuration returns valid signed URLs
- Test `Profile::getResumeUrl()` with local configuration returns public storage URLs
- Test `Profile::hasResumeFile()` correctly checks file existence on both S3 and local storage
- Test `ProfileController::update()` stores files to correct disk based on configuration
- Test edge cases: missing resume_path, non-existent files, invalid AWS credentials

### Property-Based Tests

- Generate random resume filenames and verify URL generation works for all valid filenames
- Generate random S3 configurations and verify connection succeeds with valid credentials
- Test that all resume URLs (S3 and local) return 200 status codes when accessed
- Generate random file sizes and verify uploads succeed within 2MB limit

### Integration Tests

- Test full upload flow: student uploads resume → file stored to S3 → URL generated → admin views resume
- Test recruiter application flow: student applies → recruiter views profile → resume URL loads in iframe
- Test profile update flow: student updates resume → old file deleted from S3 → new file uploaded → new URL generated
- Test environment switching: verify local development continues to work after production S3 configuration

### Manual Verification Steps

**After Deployment:**

1. **Verify Environment Configuration**
   ```bash
   php artisan tinker
   config('filesystems.default') // Should return 's3'
   config('filesystems.disks.s3.bucket') // Should return bucket name
   ```

2. **Test S3 Connection**
   ```bash
   php artisan tinker
   Storage::disk('s3')->files('resumes') // Should list resume files
   ```

3. **Test Resume URL Generation**
   ```bash
   php artisan tinker
   $profile = App\Models\Profile::whereNotNull('resume_path')->first();
   $url = $profile->getResumeUrl();
   echo $url; // Should output valid S3 URL
   ```

4. **Test Resume Access in Browser**
   - Login as admin, navigate to `/admin/users/{id}`, click "View Resume"
   - Login as recruiter, view application, click "View Profile", verify resume loads in modal
   - Login as student, navigate to `/profile`, click "View Resume"
   - All should successfully load PDF files without errors

5. **Verify Local Development Still Works**
   ```bash
   # In local environment with FILESYSTEM_DISK=local
   php artisan tinker
   $profile = App\Models\Profile::whereNotNull('resume_path')->first();
   $url = $profile->getResumeUrl();
   echo $url; // Should output http://localhost:8000/storage/resumes/filename.pdf
   ```

### Cache Reset Commands

**After Configuration Changes:**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers (if using queues)
php artisan queue:restart
```

**If Using Laravel Cloud:**
```bash
# Deploy configuration changes
php artisan cloud:deploy

# Or use Laravel Cloud dashboard to update environment variables
# Then restart application
```
