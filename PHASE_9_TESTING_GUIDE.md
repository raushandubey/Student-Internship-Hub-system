# Phase 9: Testing Guide

## 🧪 How to Test Phase 9 Features

This guide provides step-by-step instructions to test all Phase 9 features before your viva.

---

## Prerequisites

```bash
# Ensure application is running
php artisan serve

# Ensure database is migrated
php artisan migrate

# Ensure queue worker is running (for emails)
php artisan queue:work

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Test 1: Rate Limiting on Login

### Objective
Verify that login attempts are rate-limited to 5 per minute.

### Steps

1. **Open browser in incognito mode** (to avoid session issues)

2. **Navigate to login page:**
   ```
   http://localhost:8000/login
   ```

3. **Attempt to login with wrong password 6 times:**
   - Email: `test@test.com`
   - Password: `wrongpassword`
   - Click "Login" 6 times rapidly

4. **Expected Results:**
   - First 5 attempts: "Invalid credentials" error
   - 6th attempt: "Too many requests" error (HTTP 429)

5. **Verify in logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   - Should NOT see rate limit logs (Laravel handles this internally)

### Success Criteria
✅ 6th login attempt blocked with "Too many requests"
✅ Can login again after 1 minute

---

## Test 2: Business Rule Violation (Duplicate Application)

### Objective
Verify that applying to the same internship twice throws BusinessRuleViolationException.

### Steps

1. **Login as student:**
   - Email: `student@test.com`
   - Password: `password`

2. **Navigate to recommendations:**
   ```
   http://localhost:8000/recommendations
   ```

3. **Apply to an internship:**
   - Click "Apply Now" on any internship
   - Should see success message

4. **Try applying to the same internship again:**
   - Click "Apply Now" on the same internship
   - Should see error: "You have already applied to this internship."

5. **Verify in logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   - Should see:
     ```
     [warning] Business rule violation
     {
       "actor_id": 1,
       "action": "business_rule_violation",
       "rule": "You have already applied to this internship.",
       ...
     }
     ```

### Success Criteria
✅ Second application blocked with error message
✅ Structured warning log created
✅ No stack trace shown to user

---

## Test 3: Invalid State Transition

### Objective
Verify that invalid status transitions throw InvalidStateTransitionException.

### Steps

1. **Login as admin:**
   - Email: `admin@sih.com`
   - Password: `admin123`

2. **Navigate to applications:**
   ```
   http://localhost:8000/admin/applications
   ```

3. **Find a "Pending" application**

4. **Try changing status to "Approved" directly:**
   - Select "Approved" from dropdown
   - Click "Update Status"
   - Should see error: "Invalid transition from Pending to Approved"

5. **Verify in logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   - Should see:
     ```
     [warning] Invalid state transition attempted
     {
       "actor_id": 1,
       "action": "invalid_state_transition",
       "from_status": "pending",
       "to_status": "approved",
       "allowed_transitions": ["under_review", "rejected"],
       ...
     }
     ```

6. **Test valid transition:**
   - Change status to "Under Review"
   - Should succeed

### Success Criteria
✅ Invalid transition blocked with error message
✅ Structured warning log created
✅ Valid transition works correctly

---

## Test 4: Unauthorized Action

### Objective
Verify that unauthorized actions throw UnauthorizedActionException.

### Steps

1. **Login as Student A:**
   - Email: `student@test.com`
   - Password: `password`

2. **Apply to an internship and note the application ID:**
   - Go to "My Applications"
   - Note the application ID from URL (e.g., `/applications/123`)

3. **Login as Student B (different account):**
   - Register a new account or use another student account

4. **Try to cancel Student A's application:**
   - Manually navigate to:
     ```
     http://localhost:8000/applications/{student_a_application_id}
     ```
   - Try to cancel via form submission or URL manipulation

5. **Expected Result:**
   - Error: "You are not authorized to perform this action."

6. **Verify in logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   - Should see:
     ```
     [warning] Unauthorized action attempted
     {
       "actor_id": 2,
       "actor_role": "student",
       "action": "unauthorized_action",
       "attempted_action": "cancel this application",
       "reason": "as non-owner",
       "ip": "127.0.0.1",
       ...
     }
     ```

### Success Criteria
✅ Unauthorized action blocked
✅ Security audit log created with IP and user agent
✅ User sees generic error message (no sensitive data)

---

## Test 5: Transaction Rollback

### Objective
Verify that database transactions rollback on failure.

### Steps

1. **Open Tinker:**
   ```bash
   php artisan tinker
   ```

2. **Test transaction rollback:**
   ```php
   use App\Models\Application;
   use Illuminate\Support\Facades\DB;
   
   // Count applications before
   $before = Application::count();
   echo "Applications before: $before\n";
   
   // Try to create application in transaction with forced failure
   try {
       DB::transaction(function () {
           $app = Application::create([
               'user_id' => 1,
               'internship_id' => 1,
               'status' => 'pending',
               'match_score' => 75
           ]);
           
           echo "Application created: " . $app->id . "\n";
           
           // Force failure
           throw new \Exception('Simulated failure');
       });
   } catch (\Exception $e) {
       echo "Exception caught: " . $e->getMessage() . "\n";
   }
   
   // Count applications after
   $after = Application::count();
   echo "Applications after: $after\n";
   
   // Should be the same (rollback)
   echo "Rollback successful: " . ($before === $after ? 'YES' : 'NO') . "\n";
   ```

3. **Expected Output:**
   ```
   Applications before: 5
   Application created: 6
   Exception caught: Simulated failure
   Applications after: 5
   Rollback successful: YES
   ```

### Success Criteria
✅ Application count unchanged after exception
✅ Transaction rolled back successfully
✅ No orphaned records in database

---

## Test 6: Structured Logging

### Objective
Verify that all actions are logged with structured data.

### Steps

1. **Clear log file:**
   ```bash
   echo "" > storage/logs/laravel.log
   ```

2. **Perform various actions:**
   - Login as student
   - Apply to internship
   - Try duplicate application
   - Cancel application
   - Login as admin
   - Update application status

3. **Check log file:**
   ```bash
   cat storage/logs/laravel.log
   ```

4. **Verify log structure:**
   - Each log should have:
     - `actor_id`
     - `action`
     - `timestamp` (ISO 8601 format)
     - Context-specific fields

5. **Example log entry:**
   ```json
   {
     "actor_id": 1,
     "actor_type": "student",
     "action": "application.submit",
     "target_entity": "application",
     "target_id": 123,
     "internship_id": 45,
     "match_score": 75,
     "timestamp": "2026-01-18T10:30:00Z"
   }
   ```

### Success Criteria
✅ All actions logged with structured data
✅ Consistent format across all logs
✅ ISO 8601 timestamps
✅ No plain text logs (all structured)

---

## Test 7: Rate Limiting on Applications

### Objective
Verify that application submissions are rate-limited to 10 per minute.

### Steps

1. **Login as student**

2. **Open browser console (F12)**

3. **Run this JavaScript to submit 11 applications rapidly:**
   ```javascript
   // Get all "Apply Now" buttons
   const buttons = document.querySelectorAll('form[action*="apply"] button');
   
   // Click first 11 buttons with 100ms delay
   buttons.forEach((btn, index) => {
       if (index < 11) {
           setTimeout(() => {
               btn.click();
               console.log(`Application ${index + 1} submitted`);
           }, index * 100);
       }
   });
   ```

4. **Expected Result:**
   - First 10 applications: Success
   - 11th application: "Too many requests" error

### Success Criteria
✅ 11th application blocked
✅ Rate limit enforced correctly
✅ Can apply again after 1 minute

---

## Test 8: Exception Handling in Controllers

### Objective
Verify that controllers handle exceptions gracefully.

### Steps

1. **Temporarily modify ApplicationService to throw exception:**
   ```php
   // In app/Services/ApplicationService.php
   public function submitApplication(User $user, Internship $internship): array
   {
       throw new \Exception('Test exception');
   }
   ```

2. **Try to apply to internship:**
   - Should see error message
   - Should NOT see stack trace
   - Should be redirected back

3. **Revert the change**

### Success Criteria
✅ Exception caught by controller
✅ User sees friendly error message
✅ No stack trace or sensitive data shown

---

## Test 9: Global Exception Handler

### Objective
Verify that global exception handler catches and logs all exceptions.

### Steps

1. **Test BusinessRuleViolationException:**
   - Apply to internship twice
   - Check logs for structured warning

2. **Test InvalidStateTransitionException:**
   - Try invalid status transition
   - Check logs for allowed transitions

3. **Test UnauthorizedActionException:**
   - Try to cancel other user's application
   - Check logs for security audit

4. **Verify log format:**
   ```bash
   grep -A 10 "warning" storage/logs/laravel.log
   ```

### Success Criteria
✅ All exceptions logged with context
✅ Consistent log format
✅ Security events include IP and user agent

---

## Test 10: API Error Responses

### Objective
Verify that API endpoints return proper JSON error responses.

### Steps

1. **Test with curl:**
   ```bash
   # Try to apply twice (should fail)
   curl -X POST http://localhost:8000/applications/apply/1 \
     -H "Accept: application/json" \
     -H "Authorization: Bearer {token}" \
     -d ""
   ```

2. **Expected JSON response:**
   ```json
   {
     "success": false,
     "message": "You have already applied to this internship.",
     "error_type": "business_rule_violation"
   }
   ```

3. **Verify HTTP status code:**
   - BusinessRuleViolationException: 422
   - InvalidStateTransitionException: 409
   - UnauthorizedActionException: 403

### Success Criteria
✅ JSON responses for API requests
✅ Correct HTTP status codes
✅ Consistent error format

---

## Quick Test Checklist

Before viva, run through this quick checklist:

- [ ] Rate limiting works on login (5/min)
- [ ] Rate limiting works on applications (10/min)
- [ ] Duplicate application blocked
- [ ] Invalid state transition blocked
- [ ] Unauthorized action blocked
- [ ] Transaction rollback works
- [ ] Logs are structured
- [ ] No stack traces shown to users
- [ ] Error messages are user-friendly
- [ ] Security events logged with IP

---

## Troubleshooting

### Issue: Rate limiting not working
**Solution:** Clear cache and restart server
```bash
php artisan cache:clear
php artisan config:clear
php artisan serve
```

### Issue: Logs not showing structured data
**Solution:** Check log level in `.env`
```
LOG_LEVEL=debug
```

### Issue: Exceptions not caught
**Solution:** Verify Handler.php is registered in bootstrap/app.php
```php
->withExceptions(function (Exceptions $exceptions) {
    //
})
```

### Issue: Transaction not rolling back
**Solution:** Ensure database supports transactions (InnoDB for MySQL)
```sql
SHOW TABLE STATUS WHERE Name = 'applications';
-- Engine should be InnoDB
```

---

## Performance Testing (Optional)

### Load Test Rate Limiting
```bash
# Install Apache Bench
# Windows: Download from Apache website
# Linux: sudo apt-get install apache2-utils

# Test login rate limiting (100 requests)
ab -n 100 -c 10 -p login.txt -T application/x-www-form-urlencoded http://localhost:8000/login

# login.txt content:
# email=test@test.com&password=wrong
```

### Expected Result
- First 50 requests: HTTP 200 (5 per second for 10 seconds)
- Remaining requests: HTTP 429 (rate limited)

---

## Final Verification

Before viva, ensure:

1. **All tests pass** ✅
2. **Logs are clean** ✅
3. **No errors in console** ✅
4. **Application runs smoothly** ✅
5. **Documentation is complete** ✅

---

**You're ready for the viva! Good luck! 🚀**
