# Resume Storage Architecture - Visual Guide

## File Upload Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER UPLOADS RESUME                      │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│              ProfileController::update()                         │
│  • Validates file (PDF, max 2MB)                                │
│  • Sanitizes filename                                            │
│  • Stores: $file->storeAs('resumes', $filename, 'public')       │
│  • Saves path to database: "resumes/filename.pdf"               │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    FILE STORED ON DISK                           │
│  Location: storage/app/public/resumes/filename.pdf              │
│  Database: profiles.resume_path = "resumes/filename.pdf"        │
└─────────────────────────────────────────────────────────────────┘
```

## File Access Flow (3 Strategies)

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER REQUESTS RESUME                          │
│              Profile::getResumeUrl()                             │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                      STRATEGY 1: SYMLINK                         │
│  Check: Storage::disk('public')->exists('resumes/file.pdf')     │
│  URL: /storage/resumes/filename.pdf                             │
│  Via: public/storage → storage/app/public (symlink)             │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  │ If fails ▼
┌─────────────────────────────────────────────────────────────────┐
│                   STRATEGY 2: DIRECT CHECK                       │
│  Check: file_exists(storage_path('app/public/resumes/file.pdf'))│
│  URL: asset('storage/resumes/filename.pdf')                     │
│  Via: Direct filesystem check                                   │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  │ If fails ▼
┌─────────────────────────────────────────────────────────────────┐
│                   STRATEGY 3: ROUTE FALLBACK                     │
│  URL: route('resume.serve', ['filename' => 'file.pdf'])         │
│  Via: /resume/serve/filename.pdf                                │
│  Controller: ResumeController::serve()                          │
│  Reads: Storage::disk('public')->get('resumes/file.pdf')        │
│  Returns: File response with proper headers                     │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  │ If fails ▼
┌─────────────────────────────────────────────────────────────────┐
│                      STRATEGY 4: NULL                            │
│  Return: null                                                    │
│  UI Shows: "No resume uploaded"                                 │
│  Error Page: resources/views/errors/resume-not-found.blade.php  │
└─────────────────────────────────────────────────────────────────┘
```

## Directory Structure

```
project-root/
│
├── public/
│   ├── index.php
│   ├── storage/  ← SYMLINK to ../storage/app/public
│   │   └── resumes/
│   │       └── filename.pdf  (accessible via symlink)
│   └── ...
│
├── storage/
│   ├── app/
│   │   ├── private/  (not web accessible)
│   │   └── public/   ← ACTUAL FILE LOCATION
│   │       └── resumes/
│   │           └── 1234567890_resume.pdf  (actual file)
│   └── logs/
│       └── laravel.log
│
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── ProfileController.php  (handles upload)
│   │       └── ResumeController.php   (serves files)
│   ├── Models/
│   │   └── Profile.php  (getResumeUrl(), hasResumeFile())
│   └── Services/
│       └── ProfileService.php  (formats data)
│
└── database/
    └── migrations/
        └── create_profiles_table.php  (resume_path column)
```

## URL Resolution

### Scenario 1: Symlink Exists (Normal)
```
User Request: http://domain.com/storage/resumes/file.pdf
              │
              ▼
Web Server:   public/storage/resumes/file.pdf
              │ (follows symlink)
              ▼
Filesystem:   storage/app/public/resumes/file.pdf
              │
              ▼
Response:     PDF file served
```

### Scenario 2: Symlink Missing (Fallback)
```
User Request: http://domain.com/storage/resumes/file.pdf
              │
              ▼
Web Server:   404 Not Found (symlink missing)
              │
              ▼
Profile Model: getResumeUrl() detects missing symlink
              │
              ▼
Returns:      http://domain.com/resume/serve/file.pdf
              │
              ▼
Route:        GET /resume/serve/{filename}
              │
              ▼
Controller:   ResumeController::serve()
              │
              ▼
Reads:        Storage::disk('public')->get('resumes/file.pdf')
              │
              ▼
Response:     PDF file served directly
```

## Database Schema

```sql
CREATE TABLE profiles (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255),
    academic_background VARCHAR(255),
    skills JSON,
    career_interests TEXT,
    resume_path VARCHAR(255),  ← Stores: "resumes/filename.pdf"
    aadhaar_number VARCHAR(12),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Example Data
```sql
INSERT INTO profiles (user_id, name, resume_path) VALUES
(1, 'John Doe', 'resumes/1234567890_john_resume.pdf'),
(2, 'Jane Smith', 'resumes/1234567891_jane_resume.pdf'),
(3, 'Bob Wilson', NULL);  -- No resume uploaded
```

## Security Layers

```
┌─────────────────────────────────────────────────────────────────┐
│                         UPLOAD SECURITY                          │
├─────────────────────────────────────────────────────────────────┤
│  1. File Type Validation: mimes:pdf                             │
│  2. File Size Limit: max:2048 (2MB)                             │
│  3. MIME Type Check: application/pdf                            │
│  4. Filename Sanitization: preg_replace('/[^A-Za-z0-9_\-\.]/')  │
│  5. Timestamp Prefix: time() . '_' . filename                   │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                        STORAGE SECURITY                          │
├─────────────────────────────────────────────────────────────────┤
│  1. Non-Executable Directory: storage/app/public/               │
│  2. No PHP Execution: .htaccess prevents script execution       │
│  3. Proper Permissions: 775 (owner/group write, others read)    │
│  4. Outside Web Root: storage/ not in public/                   │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                        SERVING SECURITY                          │
├─────────────────────────────────────────────────────────────────┤
│  1. Authentication Required: auth middleware                     │
│  2. Basename Extraction: basename($filename)                     │
│  3. Directory Traversal Prevention: No ../ allowed               │
│  4. Proper MIME Headers: Content-Type: application/pdf          │
│  5. Logging: All access attempts logged                         │
└─────────────────────────────────────────────────────────────────┘
```

## Error Handling Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    FILE ACCESS ATTEMPT                           │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
                    ┌─────────────────────────┐
                    │   File Exists?          │
                    └─────────────────────────┘
                              │
                ┌─────────────┴─────────────┐
                │                           │
               YES                         NO
                │                           │
                ▼                           ▼
    ┌───────────────────────┐   ┌───────────────────────┐
    │  Serve File           │   │  Log Warning          │
    │  Return 200 OK        │   │  Return 404 Page      │
    │  Cache-Control: 3600  │   │  Show Upload Button   │
    └───────────────────────┘   └───────────────────────┘
```

## Production Deployment Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                      DEPLOY TO PRODUCTION                        │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│  1. Git Push Code                                               │
│     • ProfileController changes                                 │
│     • Profile Model changes                                     │
│     • ResumeController (new)                                    │
│     • Routes updated                                            │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. Run Deployment Script                                       │
│     bash fix-resume-storage.sh                                  │
│     • Creates symlink                                           │
│     • Creates directories                                       │
│     • Sets permissions                                          │
│     • Clears caches                                             │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. Verify Setup                                                │
│     • Check symlink: ls -la public/storage                      │
│     • Check routes: php artisan route:list --name=resume        │
│     • Check logs: tail -f storage/logs/laravel.log              │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│  4. Test Functionality                                          │
│     • Upload test resume                                        │
│     • Verify file in storage/app/public/resumes/               │
│     • Test URL: /storage/resumes/filename.pdf                   │
│     • Test fallback: /resume/serve/filename.pdf                 │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│  5. Monitor Production                                          │
│     • Watch logs for errors                                     │
│     • Check 404 rate                                            │
│     • Verify file persistence                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Ephemeral vs Persistent Storage

### Ephemeral Storage (Laravel Cloud, Heroku, Railway)
```
Deploy 1:
  Upload resume → storage/app/public/resumes/file.pdf
  ✅ Works

Deploy 2:
  Code updated → Container rebuilt
  ❌ storage/app/public/ WIPED
  ❌ Resume files DELETED
  ❌ 404 errors

Solution: Use S3
  Upload resume → S3 bucket
  ✅ Persists across deploys
  ✅ No 404 errors
```

### Persistent Storage (VPS, Dedicated Server)
```
Deploy 1:
  Upload resume → storage/app/public/resumes/file.pdf
  ✅ Works

Deploy 2:
  Code updated → Files remain
  ✅ storage/app/public/ PERSISTS
  ✅ Resume files INTACT
  ✅ No 404 errors

Benefit: No S3 needed (but recommended for backups)
```

## S3 Migration Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      BEFORE (Local Storage)                      │
├─────────────────────────────────────────────────────────────────┤
│  Upload → storage/app/public/resumes/file.pdf                   │
│  URL → /storage/resumes/file.pdf                                │
│  Issue → Files deleted on redeploy                              │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  │ MIGRATE
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                       AFTER (S3 Storage)                         │
├─────────────────────────────────────────────────────────────────┤
│  Upload → S3 bucket: your-bucket/resumes/file.pdf               │
│  URL → https://your-bucket.s3.amazonaws.com/resumes/file.pdf    │
│  Benefit → Files persist forever                                │
│  Benefit → CDN-ready                                            │
│  Benefit → Automatic backups                                    │
│  Benefit → Scalable                                             │
└─────────────────────────────────────────────────────────────────┘
```

---

**Legend**:
- ✅ = Working correctly
- ❌ = Error/Issue
- ▼ = Flow direction
- │ = Connection
- ← = Points to / References
