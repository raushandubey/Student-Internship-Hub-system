# Requirements Document: S3 Storage Completion

## Introduction

The Laravel Student Internship Hub is deployed on Laravel Cloud with an attached S3 bucket for file storage. The application currently has partial S3 support implemented in the code (Profile model and ProfileController), but is missing the critical AWS S3 Flysystem adapter package, causing deployment failures. This feature completes the S3 storage implementation to enable persistent, scalable file storage for student resume uploads.

## Glossary

- **Application**: The Laravel Student Internship Hub web application
- **S3_Storage**: Amazon S3 cloud storage service for file persistence
- **Flysystem_Adapter**: The league/flysystem-aws-s3-v3 PHP package that enables Laravel to communicate with S3
- **Resume_File**: PDF files uploaded by students as part of their profile
- **Storage_Disk**: Laravel's abstraction for file storage backends (s3, public, local)
- **Laravel_Cloud**: The hosting platform where the application is deployed
- **Profile_Model**: The Eloquent model representing student profile data
- **ProfileController**: The controller handling profile updates and file uploads
- **Deployment**: The process of pushing code changes to Laravel Cloud production environment

## Requirements

### Requirement 1: Install AWS S3 Flysystem Package

**User Story:** As a developer, I want the AWS S3 Flysystem adapter installed, so that Laravel can communicate with the attached S3 bucket.

#### Acceptance Criteria

1. THE Application SHALL include league/flysystem-aws-s3-v3 version ^3.0 in composer.json dependencies
2. WHEN composer install runs, THE Flysystem_Adapter SHALL be installed successfully
3. THE Application SHALL include the updated composer.lock file in version control
4. WHEN deployed to Laravel_Cloud, THE Application SHALL not produce "missing package" errors

### Requirement 2: Configure S3 as Default Storage Disk

**User Story:** As a system administrator, I want S3 configured as the default storage disk, so that all file uploads persist across deployments.

#### Acceptance Criteria

1. THE Application SHALL set FILESYSTEM_DISK to "s3" in .env.example
2. THE Application SHALL set FILESYSTEM_DISK to "s3" in .cloud.yml environment section
3. WHEN config('filesystems.default') is called, THE Application SHALL return "s3"
4. THE Application SHALL include all required AWS S3 configuration variables in .env.example (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET, AWS_URL, AWS_ENDPOINT, AWS_USE_PATH_STYLE_ENDPOINT)

### Requirement 3: Store Resume Files to S3

**User Story:** As a student, I want my resume uploads to be stored in S3, so that my files persist across application deployments.

#### Acceptance Criteria

1. WHEN a student uploads a resume file, THE ProfileController SHALL store the file to the s3 disk
2. WHEN a resume file is stored, THE ProfileController SHALL save the S3 path to the Profile_Model resume_path field
3. WHEN an old resume exists, THE ProfileController SHALL delete the old file from S3 before storing the new file
4. IF file upload fails, THEN THE ProfileController SHALL log the error and return a user-friendly error message
5. WHEN a resume is successfully uploaded, THE ProfileController SHALL log the upload details (user_id, path, disk, filename)

### Requirement 4: Retrieve Resume Files from S3

**User Story:** As a student, I want to view and download my uploaded resume, so that I can verify my profile information.

#### Acceptance Criteria

1. WHEN getResumeUrl() is called on a Profile_Model with a resume_path, THE Profile_Model SHALL return a valid S3 URL
2. WHEN the Storage_Disk is "s3", THE Profile_Model SHALL use Storage::disk('s3')->url() to generate the URL
3. WHEN the Storage_Disk is not "s3", THE Profile_Model SHALL fall back to public disk URL generation
4. IF URL generation fails, THEN THE Profile_Model SHALL log a warning and return null
5. WHEN hasResumeFile() is called, THE Profile_Model SHALL check if the file exists on the configured Storage_Disk

### Requirement 5: Verify S3 Configuration

**User Story:** As a developer, I want to verify S3 is configured correctly, so that I can confirm file operations work before deploying.

#### Acceptance Criteria

1. THE Application SHALL provide a test command or script to verify S3 connectivity
2. WHEN the test is run, THE Application SHALL attempt to write a test file to S3
3. WHEN the test is run, THE Application SHALL verify the test file exists on S3
4. WHEN the test is run, THE Application SHALL delete the test file from S3
5. IF any test operation fails, THEN THE Application SHALL report which operation failed and why

### Requirement 6: Handle Existing Local Files

**User Story:** As a system administrator, I want existing local resume files migrated to S3, so that no student data is lost during the transition.

#### Acceptance Criteria

1. THE Application SHALL provide a migration command or script to transfer existing files from local storage to S3
2. WHEN the migration runs, THE Application SHALL identify all Profile_Model records with resume_path values
3. FOR ALL profiles with local resume files, THE Application SHALL copy the file contents to S3 at the same path
4. WHEN a file is migrated, THE Application SHALL log the migration (profile_id, old_path, new_path)
5. IF a file migration fails, THEN THE Application SHALL log the error and continue with remaining files
6. WHEN all migrations complete, THE Application SHALL report the total count of successful and failed migrations

### Requirement 7: Maintain Backward Compatibility

**User Story:** As a developer, I want the storage implementation to work in both local and S3 environments, so that development and production use the same codebase.

#### Acceptance Criteria

1. WHEN FILESYSTEM_DISK is "s3", THE Application SHALL use S3 for all file operations
2. WHEN FILESYSTEM_DISK is "public" or "local", THE Application SHALL use local storage for all file operations
3. THE Profile_Model getResumeUrl() method SHALL handle both S3 and local storage paths
4. THE Profile_Model hasResumeFile() method SHALL check the correct Storage_Disk based on configuration
5. THE ProfileController SHALL use config('filesystems.default') to determine which disk to use for uploads

### Requirement 8: Validate Deployment Configuration

**User Story:** As a developer, I want deployment configuration validated, so that S3 storage works correctly in production.

#### Acceptance Criteria

1. THE .cloud.yml file SHALL specify storage disk as "s3"
2. THE .cloud.yml file SHALL include "php artisan storage:link" in the deploy commands
3. THE .cloud.yml environment section SHALL set FILESYSTEM_DISK to "s3"
4. WHEN deployed to Laravel_Cloud, THE Application SHALL have AWS credentials auto-populated by the platform
5. THE Application SHALL log successful S3 configuration on startup

### Requirement 9: Handle S3 Errors Gracefully

**User Story:** As a student, I want clear error messages when file operations fail, so that I understand what went wrong.

#### Acceptance Criteria

1. IF S3 connection fails during upload, THEN THE ProfileController SHALL return "Failed to upload resume. Please try again."
2. IF S3 file deletion fails, THEN THE ProfileController SHALL log a warning but continue with the upload
3. IF S3 URL generation fails, THEN THE Profile_Model SHALL log the error and return null
4. IF S3 file existence check fails, THEN THE Profile_Model SHALL log the error and return false
5. WHEN any S3 operation fails, THE Application SHALL log the error with context (operation, path, error message)

### Requirement 10: Document S3 Setup Process

**User Story:** As a developer, I want clear documentation on S3 setup, so that I can configure and troubleshoot the storage system.

#### Acceptance Criteria

1. THE Application SHALL include a README or documentation file explaining S3 configuration
2. THE documentation SHALL list all required environment variables for S3
3. THE documentation SHALL explain how to test S3 connectivity
4. THE documentation SHALL provide troubleshooting steps for common S3 errors
5. THE documentation SHALL explain how to migrate existing local files to S3
