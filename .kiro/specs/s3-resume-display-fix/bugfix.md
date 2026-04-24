# Bugfix Requirements Document

## Introduction

This bugfix addresses a critical production issue where resume files uploaded to S3/Object Storage are not displaying in the Laravel application deployed on Laravel Cloud. While uploads succeed and files exist in the S3 bucket, the application fails to retrieve and display these resumes across all user roles (admin, recruiter, student), resulting in 404 errors, 500 errors, and redirect loops. The root cause is a misconfiguration where the application is set to use local filesystem storage (`FILESYSTEM_DISK=local`) instead of S3, combined with missing AWS configuration variables required for S3 URL generation.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN the application is deployed on Laravel Cloud with S3 storage AND `FILESYSTEM_DISK` is set to `local` in `.env` THEN resume uploads are stored to local disk which may not persist or be accessible in cloud environments

1.2 WHEN `FILESYSTEM_DISK=local` is configured AND the application attempts to generate resume URLs THEN the system uses `Storage::disk('public')->url()` which generates incorrect local filesystem URLs instead of S3 URLs

1.3 WHEN AWS configuration variables (`AWS_ENDPOINT`, `AWS_URL`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`) are missing from `.env` THEN S3 disk configuration is incomplete and cannot generate valid S3 URLs

1.4 WHEN admin users attempt to view student resumes in the admin panel (`admin/users/show.blade.php`) THEN the resume link calls `$user->profile->getResumeUrl()` which returns null or invalid URLs causing 404 errors

1.5 WHEN recruiters attempt to view applicant resumes via AJAX endpoint (`/api/recruiter/applications/{id}/profile`) THEN the `getProfile()` method returns invalid resume URLs causing frontend display failures

1.6 WHEN students attempt to view their own resumes in the profile page (`profile/show.blade.php`) THEN the resume preview fails with 404, 500, or ERR_TOO_MANY_REDIRECTS errors

1.7 WHEN `Profile::getResumeUrl()` checks for file existence using `Storage::disk('s3')->exists()` while `FILESYSTEM_DISK=local` THEN the existence check fails because it's looking in the wrong storage location

1.8 WHEN the system attempts to generate S3 URLs without proper `AWS_URL` configuration THEN `Storage::disk('s3')->url()` and `temporaryUrl()` methods fail or return malformed URLs

### Expected Behavior (Correct)

2.1 WHEN the application is deployed on Laravel Cloud with S3 storage THEN `FILESYSTEM_DISK` SHALL be set to `s3` in `.env` to ensure all file operations use S3

2.2 WHEN resume uploads are processed via `ProfileController::update()` THEN files SHALL be stored to S3 using `Storage::disk('s3')->storeAs()` and the relative path SHALL be saved in the database

2.3 WHEN AWS configuration is required for S3 operations THEN all necessary environment variables (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`, `AWS_ENDPOINT`) SHALL be present in `.env`

2.4 WHEN `Profile::getResumeUrl()` is called THEN it SHALL check file existence on S3 using `Storage::disk('s3')->exists()` and generate signed URLs using `Storage::disk('s3')->temporaryUrl()` for private buckets or `Storage::disk('s3')->url()` for public buckets

2.5 WHEN admin users view student profiles THEN resume links SHALL display valid S3 URLs that open directly in the browser without errors

2.6 WHEN recruiters fetch applicant profiles via AJAX THEN the `resume_url` field SHALL contain valid S3 URLs that can be loaded in iframes or opened in new tabs

2.7 WHEN students view their profile page THEN resume preview links SHALL work correctly, opening the PDF directly from S3 without redirect loops or 404 errors

2.8 WHEN S3 URLs are generated THEN they SHALL be either direct public URLs (if bucket is public) or signed temporary URLs with 1-hour expiration (if bucket is private)

### Unchanged Behavior (Regression Prevention)

3.1 WHEN the application runs in local development environment with `FILESYSTEM_DISK=local` THEN resume uploads and retrieval SHALL CONTINUE TO work using local storage with `Storage::disk('public')`

3.2 WHEN `ProfileController::update()` processes resume uploads THEN it SHALL CONTINUE TO delete old resume files before uploading new ones to prevent storage bloat

3.3 WHEN `ResumeController::getUrl()` is called THEN it SHALL CONTINUE TO enforce authorization checks ensuring students can only access their own resumes

3.4 WHEN `ResumeController::download()` is called THEN it SHALL CONTINUE TO generate download URLs with proper content disposition headers for file downloads

3.5 WHEN resume file paths are stored in the database THEN they SHALL CONTINUE TO be stored as relative paths (e.g., `resumes/filename.pdf`) not absolute URLs

3.6 WHEN `Profile::hasResumeFile()` is called THEN it SHALL CONTINUE TO return boolean indicating actual file existence on the configured storage disk

3.7 WHEN resume URLs are generated for non-existent files THEN the system SHALL CONTINUE TO return null and log warnings instead of throwing exceptions

3.8 WHEN file operations fail (upload, delete, URL generation) THEN the system SHALL CONTINUE TO log errors with context and return user-friendly error messages
