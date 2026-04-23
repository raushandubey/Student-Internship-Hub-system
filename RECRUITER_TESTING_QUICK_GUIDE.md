# Quick Testing Guide: AJAX Status Update Fix

## Prerequisites
- Recruiter account: `recruiter@test.com` / `password`
- At least one internship posted by this recruiter
- At least one application to that internship

## Test Scenarios

### Scenario 1: Valid Status Transition ✅
**Steps:**
1. Login as recruiter
2. Navigate to Applications page
3. Find an application with status "Pending"
4. Change status dropdown to "Under Review"

**Expected Result:**
- Green success notification appears at top-right: "Status updated successfully!"
- Status badge updates to "Under Review" with blue color
- Dropdown stays on "Under Review"
- Notification disappears after 3 seconds

### Scenario 2: Invalid Status Transition ❌
**Steps:**
1. Find an application with status "Pending"
2. Try to change status directly to "Approved" (skipping intermediate steps)

**Expected Result:**
- Alert dialog appears with error message: "Cannot transition from Pending to Approved"
- Dropdown reverts back to "Pending"
- Status badge remains unchanged

### Scenario 3: Terminal State Protection ❌
**Steps:**
1. Find an application with status "Approved" or "Rejected"
2. Try to change status to any other value

**Expected Result:**
- Alert dialog appears with error message about terminal state
- Dropdown reverts back to original status
- Status badge remains unchanged

### Scenario 4: Valid Status Progression ✅
**Steps:**
1. Start with application at "Pending"
2. Change to "Under Review" → Success
3. Change to "Shortlisted" → Success
4. Change to "Interview Scheduled" → Success
5. Change to "Approved" → Success

**Expected Result:**
- Each transition shows green success notification
- Status badge updates correctly at each step
- Final status is "Approved" with green color

### Scenario 5: Rejection Path ✅
**Steps:**
1. Start with application at any non-terminal status
2. Change status to "Rejected"

**Expected Result:**
- Green success notification appears
- Status badge updates to "Rejected" with red color
- Cannot change status anymore (terminal state)

## Valid Transition Rules

```
pending → under_review ✅
pending → rejected ✅

under_review → shortlisted ✅
under_review → rejected ✅

shortlisted → interview_scheduled ✅
shortlisted → rejected ✅

interview_scheduled → approved ✅
interview_scheduled → rejected ✅

approved → (no transitions) ❌
rejected → (no transitions) ❌
```

## Common Issues to Check

### Issue: "Failed to update status" with no details
**Cause:** Server error or network issue
**Check:** Browser console for detailed error messages

### Issue: Dropdown doesn't revert on error
**Cause:** JavaScript error in catch block
**Check:** Browser console for JavaScript errors

### Issue: Status badge doesn't update
**Cause:** CSS class mismatch or DOM selector issue
**Check:** Inspect element to verify class names

### Issue: Success notification doesn't appear
**Cause:** CSS animation not loading or z-index issue
**Check:** Browser console and inspect element

## Browser Console Commands

To test AJAX manually in browser console:

```javascript
// Test valid transition
fetch('/recruiter/applications/1/status', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ status: 'under_review' })
})
.then(r => r.json())
.then(console.log)
.catch(console.error);

// Test invalid transition
fetch('/recruiter/applications/1/status', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ status: 'approved' })
})
.then(r => r.json())
.then(console.log)
.catch(console.error);
```

## Verification Checklist

- [ ] Valid transitions show success notification
- [ ] Invalid transitions show error alert
- [ ] Dropdown reverts on error
- [ ] Status badge updates on success
- [ ] Success notification auto-dismisses after 3 seconds
- [ ] Error messages are clear and helpful
- [ ] Terminal states cannot be changed
- [ ] Network errors are handled gracefully
- [ ] Page doesn't reload on status update
- [ ] Multiple status updates work in sequence

## Next Steps After Testing

If all tests pass:
- ✅ Mark task as complete
- ✅ Document any edge cases found
- ✅ Consider adding automated tests

If tests fail:
- ❌ Check browser console for errors
- ❌ Verify route configuration
- ❌ Check middleware and authentication
- ❌ Review ApplicationStatus enum transitions
