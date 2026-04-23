# Resume 403 Forbidden Error - Root Cause & Fix

## Problem
The admin profile viewer modal showed "403 | FORBIDDEN" when trying to display resume PDFs in the iframe.

## Root Cause Analysis

### Issue 1: Wrong Storage Disk
Resume files were being stored on Laravel's `local` disk (`storage/app/resumes/`) instead of the `public` disk (`storage/app/public/resumes/`).

**Why this caused 403**:
- Files on the `local` disk are NOT publicly accessible via web URLs
- The iframe tried to load `/storage/resumes/file.pdf` which doesn't exist in the public directory
- Laravel's web server blocked access with a 403 Forbidden error

### Issue 2: Incorrect URL Generation
`ProfileService` was using `Storage::url()` without specifying the disk, which defaulted to the `local` disk and generated incorrect URLs.

### Issue 3: Existing Files in Wrong Location
The database had 1 profile with a resume file stored in the old `local` disk location that needed to be migrated.

## Fixes Applied

### 1. Fixed ProfileController.php
**Changed resume upload to use public disk**:
```php
// Before:
$profile->resume_path = $request->file('resume')->store('resumes');

// After:
$profile->resume_path = $request->file('resume')->store('resumes', 'public');
```

**Changed deletion to use public disk**:
```php
// Before:
Storage::delete($profile->resume_path);

// After:
Storage::disk('public')->delete($profile->resume_path);
```

### 2. Fixed ProfileService.php
**Changed URL generation to use public disk**:
```php
// Before:
'resume_path' => $profile->resume_path
    ? \Illuminate\Support\Facades\Storage::url(ltrim($profile->resume_path, '/'))
    : null,

// After:
'resume_path' => $profile->resume_path
    ? \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($profile->resume_path, '/'))
    : null,
```

### 3. Created Migration
**Created `2026_04_20_000001_migrate_resumes_to_public_disk.php`**:
- Migrates existing resume files from `storage/app/resumes/` to `storage/app/public/resumes/`
- Copies files to the public disk
- Deletes files from the old local disk location
- Includes rollback functionality

### 4. Updated Tests
Updated test assertions to expect full URLs (e.g., `http://localhost:8000/storage/resumes/file.pdf`) instead of relative paths.

## Verification

### Migration Results
```
✓ Migrated 1 resume file successfully
✓ File now accessible at: storage/app/public/resumes/IUmbvuybgRUTCILbSeMl52h8T1wdur7JlEbWVvYy.pdf
```

### Test Results
```
✓ All 17 AdminProfileViewerTest tests pass
✓ Resume URLs are correctly generated
✓ Resume files are accessible via public URLs
✓ Authorization is enforced
```

## How It Works Now

### Upload Flow
1. User uploads resume via profile form
2. File is stored in `storage/app/public/resumes/` (public disk)
3. Path is saved in database as `resumes/filename.pdf`

### Display Flow
1. Admin clicks "View Profile"
2. ProfileService generates URL: `http://localhost:8000/storage/resumes/filename.pdf`
3. Iframe loads the PDF from the public URL
4. File is accessible because:
   - It's in `storage/app/public/resumes/`
   - Symlink exists: `public/storage` → `storage/app/public`
   - URL resolves to the actual file

## File Structure

```
storage/
├── app/
│   ├── private/          # Local disk (NOT publicly accessible)
│   └── public/           # Public disk (publicly accessible via /storage)
│       └── resumes/      # ✓ Resume files stored here now
│           └── file.pdf
public/
└── storage/              # Symlink to storage/app/public
    └── resumes/          # Accessible via /storage/resumes/file.pdf
        └── file.pdf
```

## Future Uploads
All new resume uploads will automatically use the `public` disk and be immediately accessible.

## Security Note
Resume files are now publicly accessible via direct URL, but the admin profile viewer endpoint still requires admin authentication. Consider adding additional authorization checks if resume URLs should not be directly accessible without authentication.

## Related Files Changed
- `app/Http/Controllers/ProfileController.php` - Upload logic
- `app/Services/ProfileService.php` - URL generation
- `database/migrations/2026_04_20_000001_migrate_resumes_to_public_disk.php` - Migration
- `tests/Feature/AdminProfileViewerTest.php` - Test assertions
