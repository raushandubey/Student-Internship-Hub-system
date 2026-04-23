# Implementation Plan: S3 Storage Completion

## Overview

This implementation completes the S3 storage integration for the Laravel Student Internship Hub by installing the missing AWS S3 Flysystem adapter package and implementing comprehensive file storage functionality. The solution enables persistent, scalable resume file storage on AWS S3 while maintaining backward compatibility with local development environments.

## Tasks

- [x] 1. Install AWS S3 Flysystem package and configure environment
  - Add `league/flysystem-aws-s3-v3` version ^3.0 to composer.json
  - Run `composer update` to generate composer.lock
  - Update `.env.example` with all required S3 configuration variables (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET, AWS_URL, AWS_ENDPOINT, AWS_USE_PATH_STYLE_ENDPOINT)
  - Set `FILESYSTEM_DISK=s3` in `.env.example`
  - Update `.cloud.yml` to configure S3 as storage disk and set FILESYSTEM_DISK environment variable
  - Verify package installation with `composer show | grep flysystem-aws`
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4_

- [x] 2. Update ProfileController for S3 file operations
  - [x] 2.1 Implement dynamic disk selection using config('filesystems.default')
    - Replace hardcoded disk references with configuration-based selection
    - Ensure upload logic works with both 's3' and 'public' disks
    - _Requirements: 3.1, 7.5_
  
  - [x] 2.2 Add comprehensive error handling for file operations
    - Wrap file deletion in try-catch with warning-level logging
    - Wrap file upload in try-catch with error-level logging
    - Return user-friendly error messages on failure
    - _Requirements: 3.4, 9.1, 9.2, 9.5_
  
  - [x] 2.3 Implement logging for upload and deletion operations
    - Log successful uploads with user_id, path, disk, filename, and size
    - Log deletion failures with path and error message
    - Log upload failures with user_id, operation, disk, and error details
    - _Requirements: 3.5_
  
  - [x] 2.4 Write unit tests for ProfileController upload logic
    - Test upload with S3 disk (mocked)
    - Test upload with public disk (mocked)
    - Test old file deletion during upload
    - Test error handling for failed uploads
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 3. Update Profile model for S3 URL generation and file checks
  - [x] 3.1 Enhance getResumeUrl() method with S3 support
    - Implement S3 URL generation using Storage::disk('s3')->url()
    - Maintain existing fallback strategies for local storage
    - Add error handling with warning-level logging
    - Return null on failure for graceful degradation
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 7.3_
  
  - [x] 3.2 Enhance hasResumeFile() method with S3 support
    - Check S3 disk when configured as default
    - Maintain existing local storage checks as fallback
    - Add error handling with warning-level logging
    - Return false on failure
    - _Requirements: 4.5, 7.4_
  
  - [x] 3.3 Write unit tests for Profile model methods
    - Test getResumeUrl() with S3 disk (returns S3 URL)
    - Test getResumeUrl() with public disk (returns public URL)
    - Test getResumeUrl() with missing file (returns null)
    - Test hasResumeFile() with S3 disk (returns true when exists)
    - Test hasResumeFile() with public disk (returns true when exists)
    - Test hasResumeFile() with missing file (returns false)
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 4. Checkpoint - Verify core functionality
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Create S3 connectivity testing command
  - [x] 5.1 Create TestS3Connection Artisan command
    - Create command class at `app/Console/Commands/TestS3Connection.php`
    - Set signature to `storage:test-s3`
    - Set description to "Test S3 storage connectivity"
    - _Requirements: 5.1_
  
  - [x] 5.2 Implement S3 test operations
    - Write test file to S3 with unique filename
    - Verify file exists on S3
    - Read file contents and verify they match
    - Delete test file from S3
    - Report success or failure with detailed output
    - _Requirements: 5.2, 5.3, 5.4, 5.5_
  
  - [x] 5.3 Add error handling and cleanup
    - Wrap all operations in try-catch
    - Report which operation failed and why
    - Ensure test file is deleted even on failure
    - _Requirements: 5.5_
  
  - [x] 5.4 Write integration test for TestS3Connection command
    - Test command with mocked S3 disk
    - Verify success output when all operations pass
    - Verify failure output when operations fail
    - Verify cleanup occurs on failure
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 6. Create file migration command for existing local files
  - [x] 6.1 Create MigrateFilesToS3 Artisan command
    - Create command class at `app/Console/Commands/MigrateFilesToS3.php`
    - Set signature to `storage:migrate-to-s3 {--dry-run : Preview migration without making changes}`
    - Set description to "Migrate existing local resume files to S3"
    - _Requirements: 6.1_
  
  - [x] 6.2 Implement file enumeration and migration logic
    - Query all Profile records with non-null resume_path
    - Check if each file exists on local storage
    - Copy file contents from local to S3 at same path
    - Track success, failure, and skipped counts
    - _Requirements: 6.2, 6.3_
  
  - [x] 6.3 Add progress reporting and logging
    - Display progress bar during migration
    - Log each successful migration with profile_id and path
    - Log each failed migration with profile_id, path, and error
    - Report final statistics (successful, failed, skipped)
    - _Requirements: 6.4, 6.5, 6.6_
  
  - [x] 6.4 Implement dry-run mode
    - Skip actual file operations when --dry-run flag is set
    - Display warning message in dry-run mode
    - Show what would be migrated without making changes
    - _Requirements: 6.1_
  
  - [x] 6.5 Write integration test for MigrateFilesToS3 command
    - Test migration with sample local files
    - Test dry-run mode (no files actually migrated)
    - Test error handling for missing files
    - Verify statistics reporting
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [x] 7. Checkpoint - Verify migration and testing tools
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Create comprehensive integration tests
  - [x] 8.1 Write end-to-end file upload tests
    - Test student can upload resume to S3
    - Test old resume is deleted when uploading new one
    - Test upload with invalid file type fails gracefully
    - Test upload with oversized file fails gracefully
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 9.1_
  
  - [x] 8.2 Write file retrieval tests
    - Test resume URL generation for S3 files
    - Test resume URL generation for local files
    - Test resume URL returns null for missing files
    - Test resume download works correctly
    - _Requirements: 4.1, 4.2, 4.3, 4.4_
  
  - [x] 8.3 Write backward compatibility tests
    - Test storage works with FILESYSTEM_DISK=s3
    - Test storage works with FILESYSTEM_DISK=public
    - Test URL generation handles both disk types
    - Test file existence checks handle both disk types
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 9. Create documentation
  - [x] 9.1 Create S3_SETUP.md documentation file
    - Document all required environment variables
    - Explain Laravel Cloud auto-configuration
    - Document optional configuration variables
    - _Requirements: 10.1, 10.2_
  
  - [x] 9.2 Document testing and verification procedures
    - Explain how to run `php artisan storage:test-s3`
    - Document manual testing steps for upload and retrieval
    - Explain how to verify persistence across deployments
    - _Requirements: 10.3_
  
  - [x] 9.3 Document migration process
    - Explain when to run migration command
    - Document dry-run mode usage
    - Explain migration statistics and logging
    - _Requirements: 10.5_
  
  - [x] 9.4 Add troubleshooting guide
    - Document common S3 errors and solutions
    - Explain how to diagnose upload failures
    - Explain how to diagnose retrieval failures
    - Document rollback procedures
    - _Requirements: 10.4_

- [-] 10. Validate deployment configuration
  - [~] 10.1 Verify .cloud.yml configuration
    - Confirm storage disk is set to "s3"
    - Confirm FILESYSTEM_DISK environment variable is set to "s3"
    - Confirm "php artisan storage:link" is in deploy commands
    - _Requirements: 8.1, 8.2, 8.3_
  
  - [~] 10.2 Verify .env.example completeness
    - Confirm all AWS S3 variables are documented
    - Confirm FILESYSTEM_DISK is set to "s3"
    - Add comments explaining each variable
    - _Requirements: 2.4_
  
  - [~] 10.3 Test deployment configuration locally
    - Set FILESYSTEM_DISK=s3 in local .env
    - Configure local AWS credentials (or use LocalStack)
    - Run full upload/retrieval flow
    - Verify all operations work correctly
    - _Requirements: 8.4, 8.5_

- [ ] 11. Final checkpoint - Complete validation
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation throughout implementation
- The design document explicitly states property-based testing is not applicable for this IaC feature
- All code examples use PHP (Laravel framework)
- Backward compatibility with local storage is maintained throughout
- Comprehensive error handling ensures graceful degradation
