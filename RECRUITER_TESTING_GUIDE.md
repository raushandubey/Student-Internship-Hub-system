# Recruiter Panel Testing Guide

## ✅ Issue Fixed

The database migration warning has been resolved by changing the `role` column from ENUM to VARCHAR(20). All migrations have been successfully applied.

## Test Accounts

### Recruiter Account
- **Email:** recruiter@test.com
- **Password:** password
- **Organization:** Tech Corp

### How to Create More Recruiters
1. Visit: `http://127.0.0.1:8000/recruiter/register`
2. Fill in the form:
   - Name
   - Email
   - Organization
   - Password
   - Confirm Password
3. Click "Create Recruiter Account"

## Testing Checklist

### 1. Registration & Login ✓
- [ ] Visit `/recruiter/register`
- [ ] Register a new recruiter account
- [ ] Verify redirect to recruiter dashboard
- [ ] Logout and login again
- [ ] Verify login redirects to `/recruiter/dashboard`

### 2. Dashboard ✓
- [ ] View dashboard statistics
- [ ] Check conversion funnel chart
- [ ] Verify all metrics display correctly
- [ ] Check responsive design on mobile

### 3. Internship Management ✓
- [ ] Click "Internships" in navigation
- [ ] Click "Create New" button
- [ ] Fill in internship form:
  - Title: "Software Engineering Intern"
  - Organization: "Your Company"
  - Location: "Remote"
  - Duration: "3 months"
  - Skills: Add multiple skills (press Enter after each)
  - Description: Add job description
- [ ] Submit form
- [ ] Verify internship appears in list
- [ ] Click "Edit" on an internship
- [ ] Modify details and save
- [ ] Toggle status (Active/Inactive)
- [ ] Delete an internship (with confirmation)

### 4. Application Management ✓
**Prerequisites:** Have students apply to your internships first

- [ ] Click "Applications" in navigation
- [ ] View list of applications grouped by internship
- [ ] Test filters:
  - [ ] Filter by status
  - [ ] Filter by internship
  - [ ] Filter by date range
  - [ ] Clear filters
- [ ] Click "View Profile" button
  - [ ] Verify modal opens
  - [ ] Check student details display
  - [ ] View resume preview (if uploaded)
  - [ ] Click download resume button
  - [ ] Close modal
- [ ] Update application status via dropdown
  - [ ] Verify AJAX update (no page reload)
  - [ ] Check status badge updates
- [ ] Test bulk actions:
  - [ ] Select multiple applications
  - [ ] Choose new status from bulk dropdown
  - [ ] Click "Apply Bulk Update"
  - [ ] Verify all selected applications updated
- [ ] Click history icon
  - [ ] View status change timeline
  - [ ] Verify all changes logged with timestamps

### 5. Analytics ✓
- [ ] Click "Analytics" in navigation
- [ ] View conversion funnel chart
- [ ] Check time-to-hire metric
- [ ] Review applications per internship
- [ ] View top skills from approved candidates

### 6. Profile Management ✓
- [ ] Click "Profile" in navigation
- [ ] View current profile details
- [ ] Click "Edit Profile"
- [ ] Update organization details
- [ ] Upload company logo (max 2MB)
- [ ] Update website URL
- [ ] Save changes
- [ ] Verify changes persist

### 7. Data Isolation Testing ✓
**Important Security Test**

1. Create two recruiter accounts
2. Login as Recruiter A:
   - Create internships
   - Note the internship IDs
3. Login as Recruiter B:
   - Try to access Recruiter A's internship URL
   - Expected: 403 Forbidden error
   - Verify you only see your own internships
   - Verify you only see applications for your internships
4. Verify analytics only show your own data

### 8. Email Notifications ✓
- [ ] Update an application status
- [ ] Check logs: `tail -f storage/logs/laravel.log`
- [ ] Verify email logged
- [ ] Check student receives notification (if mail is configured)

### 9. Navigation & UI ✓
- [ ] Verify recruiter link appears in main navigation
- [ ] Check all navigation links work
- [ ] Test mobile responsive design
- [ ] Verify logout works correctly
- [ ] Check flash messages display properly

### 10. Error Handling ✓
- [ ] Try to submit empty forms (verify validation)
- [ ] Upload invalid file types (verify rejection)
- [ ] Upload oversized logo (verify 2MB limit)
- [ ] Try invalid status transitions (verify blocked)
- [ ] Test rate limiting on status updates

## Common URLs

- **Recruiter Registration:** `/recruiter/register`
- **Recruiter Login:** `/login` (then enter recruiter credentials)
- **Recruiter Dashboard:** `/recruiter/dashboard`
- **Internships:** `/recruiter/internships`
- **Applications:** `/recruiter/applications`
- **Analytics:** `/recruiter/analytics`
- **Profile:** `/recruiter/profile`

## Expected Behavior

### After Registration
- Redirects to `/recruiter/dashboard`
- Shows welcome message
- Dashboard displays 0 internships, 0 applicants

### After Creating Internship
- Redirects to `/recruiter/internships`
- Shows success message
- Internship appears in list with "Active" badge

### After Status Update
- No page reload (AJAX)
- Status badge updates instantly
- Success message appears
- Email notification sent to student

### Data Isolation
- Recruiters only see their own:
  - Internships
  - Applications
  - Analytics data
- Attempting to access other recruiter's data returns 403

## Troubleshooting

### Issue: 403 Forbidden on recruiter routes
**Solution:** Ensure you're logged in as a recruiter (not student/admin)

### Issue: Internships not showing
**Solution:** Check that `recruiter_id` is set correctly in database

### Issue: Applications not appearing
**Solution:** Ensure students have applied to your internships

### Issue: Charts not displaying
**Solution:** Clear browser cache and ensure Chart.js is loaded

### Issue: Resume not previewing
**Solution:** Check that resume file exists in storage/app/public/resumes

### Issue: Logo upload fails
**Solution:** 
1. Run: `php artisan storage:link`
2. Check file size is under 2MB
3. Verify file type is image (jpeg, png, jpg, gif, svg)

## Database Check

To verify data in database:

```sql
-- Check recruiters
SELECT id, name, email, role FROM users WHERE role = 'recruiter';

-- Check recruiter profiles
SELECT * FROM recruiter_profiles;

-- Check internships with recruiter
SELECT id, title, organization, recruiter_id FROM internships WHERE recruiter_id IS NOT NULL;

-- Check application status logs
SELECT * FROM application_status_logs ORDER BY created_at DESC LIMIT 10;
```

## Performance Notes

- Status updates use AJAX (no page reload)
- Bulk updates process in single transaction
- Email notifications are queued (async)
- Analytics queries are optimized with indexes
- Rate limiting: 60 requests/minute on status updates

## Security Features Implemented

✅ Role-based middleware protection
✅ Data isolation at query level
✅ Ownership checks before updates
✅ Input validation on all forms
✅ XSS protection via Blade escaping
✅ SQL injection prevention via Eloquent
✅ CSRF protection on all forms
✅ Rate limiting on sensitive endpoints
✅ File upload validation
✅ Audit logging for status changes

## Next Steps After Testing

1. **Configure Email:** Set up mail driver in `.env` for real email notifications
2. **Queue Workers:** Run `php artisan queue:work` for async email processing
3. **Production Setup:** 
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Configure proper mail driver
   - Set up queue workers
   - Enable caching
4. **Monitoring:** Set up logging and error tracking
5. **Backups:** Configure database backups

## Support

If you encounter any issues:
1. Check `storage/logs/laravel.log` for errors
2. Run `php artisan optimize:clear` to clear caches
3. Verify database migrations: `php artisan migrate:status`
4. Check routes: `php artisan route:list --name=recruiter`

---

**Status:** ✅ All systems operational
**Last Updated:** 2026-04-22
