# Apply Internship Feature - Implementation Guide

## Overview
This document explains the simple "Apply Internship" feature implemented for the Student Internship Hub (SIH) project.

## Feature Description
Students can apply to internships with a single click. The system prevents duplicate applications and tracks application status.

---

## Database Schema

### Applications Table
```sql
CREATE TABLE applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    internship_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_application (user_id, internship_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
);
```

**Key Points:**
- `user_id`: References the student who applied
- `internship_id`: References the internship
- `status`: Default is 'pending', can be changed by admin
- `UNIQUE constraint`: Prevents duplicate applications

---

## Application Flow

### 1. Student Applies
```
Student clicks "Apply Now" 
    ↓
POST /applications/apply/{internship_id}
    ↓
ApplicationController@apply()
    ↓
Checks:
- Is user authenticated?
- Is user a student?
- Is internship active?
- Has user already applied?
    ↓
If all checks pass:
- Create application record with status='pending'
- Show success message
    ↓
If any check fails:
- Show error message
- Redirect back
```

### 2. View Applications
```
Student navigates to "My Applications"
    ↓
GET /applications
    ↓
ApplicationController@myApplications()
    ↓
Fetch all applications for logged-in user
    ↓
Display in table with status
```

### 3. Cancel Application
```
Student clicks "Cancel" (only for pending applications)
    ↓
DELETE /applications/{application_id}
    ↓
ApplicationController@cancel()
    ↓
Checks:
- Does application belong to user?
- Is status still 'pending'?
    ↓
If yes: Delete application
If no: Show error
```

---

## Files Created/Modified

### 1. Migration
**File:** `database/migrations/2026_01_14_220948_create_applications_table.php`

```php
Schema::create('applications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('internship_id')->constrained()->onDelete('cascade');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->timestamps();
    $table->unique(['user_id', 'internship_id']);
});
```

### 2. Model
**File:** `app/Models/Application.php`

```php
class Application extends Model
{
    protected $fillable = ['user_id', 'internship_id', 'status'];
    
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function internship() {
        return $this->belongsTo(Internship::class);
    }
}
```

### 3. Controller
**File:** `app/Http/Controllers/ApplicationController.php`

**Methods:**
- `apply(Request $request, Internship $internship)` - Submit application
- `myApplications()` - View student's applications
- `cancel(Application $application)` - Cancel pending application

### 4. Routes
**File:** `routes/web.php`

```php
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [ApplicationController::class, 'myApplications'])->name('index');
        Route::post('/apply/{internship}', [ApplicationController::class, 'apply'])->name('apply');
        Route::delete('/{application}', [ApplicationController::class, 'cancel'])->name('cancel');
    });
});
```

### 5. Views

**File:** `resources/views/student/applications.blade.php`
- Displays all student applications in a table
- Shows status with color coding
- Allows cancellation of pending applications

**File:** `resources/views/components/apply-button.blade.php`
- Reusable component for Apply button
- Shows different states: Apply Now, Already Applied, Login to Apply

**Modified:** `resources/views/recommendations/index.blade.php`
- Replaced static "Apply Now" button with functional form
- Added authentication checks
- Shows "Already Applied" for duplicate applications

---

## Usage Examples

### For Students

#### 1. Apply to Internship
```
1. Browse internships at /recommendations
2. Click "Apply Now" button
3. System creates application with status='pending'
4. Success message: "Application submitted successfully!"
```

#### 2. View Applications
```
1. Navigate to "My Applications" (/applications)
2. See table with:
   - Internship title
   - Organization
   - Applied date
   - Status (Pending/Approved/Rejected)
   - Cancel button (for pending only)
```

#### 3. Cancel Application
```
1. Go to "My Applications"
2. Click "Cancel" on pending application
3. Confirm cancellation
4. Application is deleted
```

### For Admins

Admins can view and manage applications in the admin panel:
```
1. Login to admin panel
2. Go to "Applications" section
3. View all student applications
4. Change status using dropdown:
   - Pending → Approved
   - Pending → Rejected
```

---

## Security Features

### 1. Authentication Check
```php
if (!Auth::check()) {
    return redirect()->route('login');
}
```

### 2. Role Verification
```php
if ($user->role !== 'student') {
    return back()->with('error', 'Only students can apply');
}
```

### 3. Duplicate Prevention
```php
// Database level: UNIQUE constraint
$table->unique(['user_id', 'internship_id']);

// Application level: Check before insert
$existingApplication = Application::where('user_id', $user->id)
    ->where('internship_id', $internship->id)
    ->first();
```

### 4. CSRF Protection
```blade
<form action="{{ route('applications.apply', $internship) }}" method="POST">
    @csrf
    <button type="submit">Apply Now</button>
</form>
```

### 5. Authorization Check (Cancel)
```php
if ($application->user_id !== Auth::id()) {
    return back()->with('error', 'Unauthorized');
}
```

---

## Button States

### 1. Not Logged In
```html
<a href="/login">Login to Apply</a>
```

### 2. Logged In as Student (Not Applied)
```html
<form method="POST">
    <button>Apply Now</button>
</form>
```

### 3. Already Applied
```html
<button disabled>Already Applied</button>
```

### 4. Logged In as Admin
```html
<button disabled>Admin Account</button>
```

---

## Testing Checklist

### Student Tests
- [ ] Can apply to internship when logged in
- [ ] Cannot apply twice to same internship
- [ ] Can view all applications
- [ ] Can cancel pending applications
- [ ] Cannot cancel approved/rejected applications
- [ ] See "Login to Apply" when not logged in
- [ ] See "Already Applied" for duplicate attempts

### Admin Tests
- [ ] Can view all student applications
- [ ] Can change application status
- [ ] Cannot apply to internships (admin account)

### Security Tests
- [ ] CSRF token required for POST requests
- [ ] Only students can apply
- [ ] Only application owner can cancel
- [ ] Database prevents duplicate applications

---

## Error Messages

### Success Messages
- "Application submitted successfully! You will be notified once reviewed."
- "Application cancelled successfully."

### Error Messages
- "Please login to apply for internships."
- "Only students can apply for internships."
- "This internship is no longer accepting applications."
- "You have already applied to this internship."
- "Failed to submit application. Please try again."
- "Unauthorized action."
- "Cannot cancel an application that has been reviewed."

---

## Database Queries

### Check if Applied
```php
$hasApplied = Application::where('user_id', auth()->id())
    ->where('internship_id', $internship->id)
    ->exists();
```

### Get User Applications
```php
$applications = Application::with('internship')
    ->where('user_id', Auth::id())
    ->orderBy('created_at', 'desc')
    ->paginate(10);
```

### Create Application
```php
Application::create([
    'user_id' => $user->id,
    'internship_id' => $internship->id,
    'status' => 'pending',
]);
```

---

## Future Enhancements (Optional)

If you want to extend this feature later:

1. **Email Notifications**
   - Send email when application is submitted
   - Notify when status changes

2. **Application History**
   - Track status change history
   - Show who approved/rejected

3. **Application Deadline**
   - Add deadline field to internships
   - Prevent applications after deadline

4. **Application Limit**
   - Limit number of active applications per student
   - Prevent spam applications

5. **Application Withdrawal**
   - Allow withdrawal with reason
   - Track withdrawal reasons

---

## Troubleshooting

### Issue: "Already Applied" shows but no application in database
**Solution:** Clear browser cache and check database directly

### Issue: Cannot apply (button disabled)
**Check:**
1. Are you logged in?
2. Is your role 'student'?
3. Have you already applied?
4. Is the internship active?

### Issue: Application not showing in admin panel
**Solution:** Check if Application model is properly loaded in admin controller

### Issue: Duplicate application error
**Solution:** This is expected behavior - the unique constraint is working

---

## Code Snippets for Reference

### Check Application Status in Blade
```blade
@php
    $hasApplied = \App\Models\Application::where('user_id', auth()->id())
        ->where('internship_id', $internship->id)
        ->exists();
@endphp

@if($hasApplied)
    <button disabled>Already Applied</button>
@else
    <form action="{{ route('applications.apply', $internship) }}" method="POST">
        @csrf
        <button type="submit">Apply Now</button>
    </form>
@endif
```

### Display Application Status
```blade
<span class="badge 
    {{ $application->status === 'approved' ? 'bg-green' : '' }}
    {{ $application->status === 'rejected' ? 'bg-red' : '' }}
    {{ $application->status === 'pending' ? 'bg-yellow' : '' }}">
    {{ ucfirst($application->status) }}
</span>
```

---

## Summary

This implementation provides a **simple, secure, and functional** application system for your college project. It follows Laravel best practices and includes:

✅ Database migration with proper constraints
✅ Model with relationships
✅ Controller with validation and security checks
✅ Routes with middleware protection
✅ Blade views with authentication logic
✅ CSRF protection
✅ Duplicate prevention
✅ Status tracking
✅ User-friendly error messages

The feature is ready for demonstration and can be easily extended in the future if needed.

---

**Created for:** Student Internship Hub (SIH)
**Purpose:** College Major Project
**Focus:** Simplicity, Clarity, Functionality
