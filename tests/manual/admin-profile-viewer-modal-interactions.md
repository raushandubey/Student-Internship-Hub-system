# Manual Testing Guide: Admin Profile Viewer Modal Interactions

## Task 6.4: Modal Interaction Handlers

This guide provides manual testing steps to verify the modal interaction handlers work correctly in a browser environment.

**Requirements Validated**: 3.6, 3.7, 3.8

---

## Test Setup

1. Log in as an admin user
2. Navigate to the Admin Applications page: `/admin/applications`
3. Ensure there are applications in the list with "View Profile" buttons

---

## Test Cases

### Test 1: Close Modal with Close Button (Requirement 3.6)

**Steps:**
1. Click "View Profile" on any application
2. Wait for the modal to load
3. Click the X (close) button in the top-right corner of the modal

**Expected Result:**
- ✅ Modal closes smoothly
- ✅ Background scrolling is restored
- ✅ Modal overlay disappears

---

### Test 2: Close Modal by Clicking Outside (Requirement 3.7)

**Steps:**
1. Click "View Profile" on any application
2. Wait for the modal to load
3. Click on the dark overlay area OUTSIDE the white modal container

**Expected Result:**
- ✅ Modal closes smoothly
- ✅ Background scrolling is restored
- ✅ Modal overlay disappears

**Note:** Clicking inside the white modal content should NOT close the modal.

---

### Test 3: Close Modal with Escape Key (Requirement 3.8)

**Steps:**
1. Click "View Profile" on any application
2. Wait for the modal to load
3. Press the `Escape` key on your keyboard

**Expected Result:**
- ✅ Modal closes smoothly
- ✅ Background scrolling is restored
- ✅ Modal overlay disappears

---

### Test 4: Prevent Multiple Modals on Rapid Clicks

**Steps:**
1. Rapidly click "View Profile" button 5-10 times in quick succession
2. Observe the behavior

**Expected Result:**
- ✅ Only ONE modal opens
- ✅ No duplicate modals appear
- ✅ No console errors
- ✅ Subsequent clicks after the first are ignored until the modal finishes loading

---

### Test 5: Close Modal While Loading

**Steps:**
1. Click "View Profile" on any application
2. IMMEDIATELY press Escape or click outside before the profile data loads
3. Try clicking "View Profile" again

**Expected Result:**
- ✅ Modal closes even during loading
- ✅ Loading flag is reset
- ✅ Can open the modal again successfully
- ✅ No stuck state

---

### Test 6: Multiple Close Triggers

**Steps:**
1. Click "View Profile" on any application
2. Wait for the modal to load
3. Simultaneously press Escape AND click the close button

**Expected Result:**
- ✅ Modal closes gracefully
- ✅ No JavaScript errors in console
- ✅ No visual glitches

---

### Test 7: Background Scroll Prevention

**Steps:**
1. Scroll down the applications page
2. Click "View Profile" on any application
3. Try to scroll the background page (not the modal content)
4. Close the modal
5. Try scrolling the page again

**Expected Result:**
- ✅ Background scrolling is disabled when modal is open
- ✅ Modal content can still be scrolled
- ✅ Background scrolling is restored after closing modal

---

## Browser Compatibility Testing

Test all scenarios above in the following browsers:

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

---

## Accessibility Testing

### Keyboard Navigation
1. Use Tab key to navigate to "View Profile" button
2. Press Enter to open modal
3. Press Escape to close modal

**Expected Result:**
- ✅ Can navigate to button with keyboard
- ✅ Can open modal with Enter key
- ✅ Can close modal with Escape key

---

## Performance Testing

### Rapid Interaction Test
1. Open and close the modal 10 times rapidly
2. Check browser console for errors
3. Monitor memory usage

**Expected Result:**
- ✅ No memory leaks
- ✅ No console errors
- ✅ Smooth performance throughout

---

## Edge Cases

### Test: Modal Already Open
1. Open a modal
2. Try to open another modal (if possible via direct function call in console)

**Expected Result:**
- ✅ Second modal is blocked
- ✅ First modal remains open

### Test: Network Failure During Load
1. Open browser DevTools
2. Go to Network tab
3. Set throttling to "Offline"
4. Click "View Profile"
5. Press Escape immediately

**Expected Result:**
- ✅ Modal closes
- ✅ Loading flag is reset
- ✅ Can try again after restoring network

---

## Sign-off

- [ ] All test cases pass
- [ ] No console errors observed
- [ ] All browsers tested
- [ ] Accessibility requirements met
- [ ] Performance is acceptable

**Tester Name:** _______________  
**Date:** _______________  
**Notes:** _______________
