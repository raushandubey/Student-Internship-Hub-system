# 📱 Mobile Redesign - Testing Guide

## Overview

This guide provides comprehensive testing procedures for the mobile-first redesign of InternshipHub.

---

## 🧪 Testing Checklist

### Pre-Testing Setup

```bash
# 1. Ensure assets are built
npm run build

# 2. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 3. Start development server
php artisan serve
```

---

## 📱 Device Testing Matrix

### Required Test Devices

| Device | Screen Size | Priority | Notes |
|--------|-------------|----------|-------|
| iPhone SE | 375x667 | HIGH | Smallest modern iPhone |
| iPhone 14 Pro | 393x852 | HIGH | Standard iPhone |
| iPhone 14 Pro Max | 430x932 | MEDIUM | Largest iPhone |
| Samsung Galaxy S21 | 360x800 | HIGH | Common Android |
| Google Pixel 5 | 393x851 | MEDIUM | Standard Android |
| iPad Mini | 768x1024 | MEDIUM | Small tablet |
| iPad Pro | 1024x1366 | LOW | Large tablet |
| Desktop | 1280x720+ | LOW | Fallback |

### Browser Testing

- [ ] Chrome Mobile (iOS)
- [ ] Safari (iOS)
- [ ] Chrome Mobile (Android)
- [ ] Samsung Internet
- [ ] Firefox Mobile
- [ ] Edge Mobile

---

## 🎯 Feature Testing

### 1. Bottom Navigation

**Test Cases:**
- [ ] Navigation bar is fixed at bottom
- [ ] All 4 tabs are visible (Home, Applications, Jobs, Profile)
- [ ] Active tab is highlighted with primary color
- [ ] Tapping tab navigates to correct page
- [ ] Active tab icon scales up slightly
- [ ] Touch targets are 44x44px minimum
- [ ] Safe area is respected (iPhone notch)
- [ ] Navigation hides on desktop (>768px)

**Test Steps:**
```
1. Open /dashboard-mobile
2. Tap each navigation item
3. Verify active state changes
4. Check URL changes correctly
5. Verify no accidental taps
6. Test on iPhone with notch
```

**Expected Result:**
- Navigation always visible at bottom
- Active tab clearly indicated
- Smooth transitions
- No layout shift

---

### 2. Mobile Dashboard

**Test Cases:**
- [ ] Profile completion ring displays correctly
- [ ] Primary CTA card is prominent
- [ ] Metrics cards show correct data
- [ ] Quick action cards are tappable
- [ ] Recent activity loads
- [ ] No horizontal scroll
- [ ] Cards have proper spacing (16px)
- [ ] Text is readable without zoom

**Test Steps:**
```
1. Login as student
2. Navigate to /dashboard-mobile
3. Check profile completion percentage
4. Tap primary CTA (should navigate)
5. Tap metric cards
6. Tap quick action cards
7. Scroll through page
8. Check on different screen sizes
```

**Expected Result:**
- All data loads correctly
- Cards are tappable
- Smooth scrolling
- No layout issues

---

### 3. Multi-Step Profile Form

**Test Cases:**
- [ ] Progress stepper shows current step
- [ ] Step 1: Basic info fields work
- [ ] Step 2: Skills textarea works
- [ ] Step 3: Career interests textarea works
- [ ] Step 4: Resume upload works
- [ ] Next button advances step
- [ ] Back button goes to previous step
- [ ] Validation shows errors
- [ ] Auto-save works (localStorage)
- [ ] Form submits successfully
- [ ] Progress restored on page reload

**Test Steps:**
```
1. Navigate to /profile/edit-mobile
2. Fill Step 1 fields
3. Click Next
4. Verify Step 2 shows
5. Fill Step 2
6. Click Back, verify Step 1 shows
7. Click Next twice to Step 3
8. Fill Step 3
9. Click Next to Step 4
10. Upload resume
11. Click Submit
12. Verify profile updated
```

**Auto-Save Test:**
```
1. Start filling form
2. Wait 2 seconds (auto-save triggers)
3. Close browser tab
4. Reopen /profile/edit-mobile
5. Verify data is restored
```

**Expected Result:**
- Smooth step transitions
- Validation works
- Auto-save persists data
- Form submits successfully

---

### 4. Recommendations Page

**Test Cases:**
- [ ] Search bar works
- [ ] Filter chips toggle correctly
- [ ] Stats summary displays
- [ ] Internship cards render
- [ ] Match scores show
- [ ] Skills display correctly
- [ ] Apply button works
- [ ] Save button toggles
- [ ] Load more button works (if >10 results)
- [ ] Empty state shows when no results

**Test Steps:**
```
1. Navigate to /recommendations
2. Type in search bar
3. Verify cards filter
4. Click filter chips
5. Verify filtering works
6. Tap internship card
7. Tap Apply button
8. Verify application submitted
9. Tap Save button
10. Verify icon changes
```

**Expected Result:**
- Search filters instantly
- Filters work correctly
- Apply/Save actions work
- Smooth interactions

---

### 5. Applications Tracker

**Test Cases:**
- [ ] Stats summary shows correct counts
- [ ] Filter tabs work
- [ ] Application cards display
- [ ] Status badges show correct colors
- [ ] Progress bars animate
- [ ] View Details button works
- [ ] Withdraw button works (if applicable)
- [ ] Empty state shows when no applications

**Test Steps:**
```
1. Navigate to /my-applications-mobile
2. Check stats match actual data
3. Click each filter tab
4. Verify cards filter by status
5. Tap View Details
6. Verify navigation works
7. Test withdraw functionality
```

**Expected Result:**
- Stats are accurate
- Filtering works
- Actions work correctly
- Smooth animations

---

### 6. Internship Cards

**Test Cases:**
- [ ] Company logo displays (initials)
- [ ] Title and organization show
- [ ] Match score displays (if available)
- [ ] Location, duration, date show
- [ ] Skills display (max 3 + count)
- [ ] Matching skills section shows (if available)
- [ ] Apply button works
- [ ] Applied state shows correctly
- [ ] Save button toggles

**Test Steps:**
```
1. View any page with internship cards
2. Check all elements render
3. Tap Apply button
4. Verify application submitted
5. Reload page
6. Verify "Applied" state shows
7. Tap Save button
8. Verify icon changes to filled heart
```

**Expected Result:**
- All data displays correctly
- Actions work
- States persist

---

### 7. Application Cards

**Test Cases:**
- [ ] Company logo displays
- [ ] Title and organization show
- [ ] Status badge shows correct color
- [ ] Progress bar shows correct percentage
- [ ] Applied date shows
- [ ] Match score shows (if available)
- [ ] View Details button works
- [ ] Withdraw button shows for pending/reviewing
- [ ] Next steps section shows (if applicable)

**Test Steps:**
```
1. View applications page
2. Check all card elements
3. Verify status colors:
   - Pending: Yellow
   - Under Review: Blue
   - Shortlisted: Purple
   - Interview: Indigo
   - Approved: Green
   - Rejected: Red
4. Tap View Details
5. Verify navigation
```

**Expected Result:**
- All data accurate
- Colors correct
- Actions work

---

## 🎨 Visual Testing

### Layout Checks

**Mobile (375px - 767px):**
- [ ] Single column layout
- [ ] No horizontal scroll
- [ ] Cards full width with 16px padding
- [ ] Text readable without zoom
- [ ] Images don't overflow
- [ ] Bottom nav visible
- [ ] Safe area respected

**Tablet (768px - 1023px):**
- [ ] 2-column grid for metrics
- [ ] 2-column grid for quick actions
- [ ] Bottom nav hidden
- [ ] Top nav visible (if applicable)
- [ ] Proper spacing maintained

**Desktop (1024px+):**
- [ ] 3-column grid for metrics
- [ ] 3-column grid for quick actions
- [ ] Desktop layout used
- [ ] Hover states work
- [ ] Proper max-width (1280px)

### Typography Checks

- [ ] H1: 24px (1.5rem)
- [ ] H2: 20px (1.25rem)
- [ ] H3: 18px (1.125rem)
- [ ] Body: 16px (1rem)
- [ ] Small: 14px (0.875rem)
- [ ] Caption: 12px (0.75rem)
- [ ] Line height: 1.5 minimum
- [ ] Font weight: 400 (normal), 500 (medium), 600 (semibold), 700 (bold)

### Spacing Checks

- [ ] Cards: 16px padding mobile, 24px desktop
- [ ] Gap between cards: 16px
- [ ] Section spacing: 24px
- [ ] Button padding: 10px 16px
- [ ] Input padding: 10px 12px

### Color Checks

- [ ] Primary: #5a67d8 (indigo)
- [ ] Success: #48bb78 (green)
- [ ] Warning: #ed8936 (orange)
- [ ] Danger: #f56565 (red)
- [ ] Gray scale: 50-900
- [ ] Sufficient contrast (WCAG AA)

---

## ⚡ Performance Testing

### Load Time Checks

**Target Metrics:**
- First Contentful Paint: < 1.5s
- Time to Interactive: < 3s
- Largest Contentful Paint: < 2.5s
- Cumulative Layout Shift: < 0.1
- First Input Delay: < 100ms

**Test Steps:**
```bash
# Run Lighthouse audit
npx lighthouse http://localhost:8000/dashboard-mobile --view

# Check specific metrics
npx lighthouse http://localhost:8000/dashboard-mobile \
  --only-categories=performance \
  --output=json \
  --output-path=./lighthouse-report.json
```

**Expected Results:**
- Performance score: > 90
- Accessibility score: > 90
- Best Practices score: > 90
- SEO score: > 90

### Asset Size Checks

```bash
# Check built assets
ls -lh public/build/assets/

# Expected sizes (gzipped):
# mobile-components.css: < 1 KB
# form-wizard.js: < 2 KB
# Total mobile assets: < 10 KB
```

### Network Checks

- [ ] Test on 3G network (slow)
- [ ] Test on 4G network (fast)
- [ ] Test on WiFi
- [ ] Check for unnecessary requests
- [ ] Verify assets are cached
- [ ] Check for 404 errors

---

## ♿ Accessibility Testing

### Keyboard Navigation

- [ ] Tab through all interactive elements
- [ ] Focus indicators visible
- [ ] Skip to content link works
- [ ] No keyboard traps
- [ ] Logical tab order
- [ ] Enter/Space activate buttons

### Screen Reader Testing

**Test with:**
- VoiceOver (iOS/macOS)
- TalkBack (Android)
- NVDA (Windows)
- JAWS (Windows)

**Check:**
- [ ] All images have alt text
- [ ] Form labels are associated
- [ ] Buttons have descriptive text
- [ ] Links have meaningful text
- [ ] Headings are hierarchical
- [ ] ARIA labels where needed

### Color Contrast

**Test with:**
- Chrome DevTools (Lighthouse)
- WAVE browser extension
- Contrast Checker tool

**Requirements:**
- Normal text: 4.5:1 minimum
- Large text: 3:1 minimum
- UI components: 3:1 minimum

### Touch Targets

- [ ] All buttons: 44x44px minimum
- [ ] All links: 44x44px minimum
- [ ] Spacing between targets: 8px minimum
- [ ] No accidental taps
- [ ] Easy to tap with thumb

---

## 🐛 Bug Testing

### Common Issues to Check

**Layout Issues:**
- [ ] No horizontal scroll
- [ ] No overlapping elements
- [ ] No cut-off text
- [ ] No broken images
- [ ] No layout shift on load

**Interaction Issues:**
- [ ] Buttons respond to tap
- [ ] Forms submit correctly
- [ ] Navigation works
- [ ] Modals open/close
- [ ] Dropdowns work

**Data Issues:**
- [ ] Correct data displays
- [ ] Empty states show
- [ ] Error messages show
- [ ] Success messages show
- [ ] Loading states work

**Browser Issues:**
- [ ] Works in Safari
- [ ] Works in Chrome
- [ ] Works in Firefox
- [ ] Works in Edge
- [ ] Works in Samsung Internet

---

## 📊 Test Report Template

```markdown
# Mobile Testing Report

**Date:** YYYY-MM-DD
**Tester:** [Name]
**Device:** [Device Name]
**Browser:** [Browser Name]
**OS:** [OS Version]

## Test Results

### Bottom Navigation
- Status: ✅ Pass / ❌ Fail
- Issues: [List any issues]

### Mobile Dashboard
- Status: ✅ Pass / ❌ Fail
- Issues: [List any issues]

### Profile Form
- Status: ✅ Pass / ❌ Fail
- Issues: [List any issues]

### Recommendations
- Status: ✅ Pass / ❌ Fail
- Issues: [List any issues]

### Applications
- Status: ✅ Pass / ❌ Fail
- Issues: [List any issues]

## Performance Metrics
- First Contentful Paint: [X]s
- Time to Interactive: [X]s
- Lighthouse Score: [X]/100

## Accessibility
- Keyboard Navigation: ✅ Pass / ❌ Fail
- Screen Reader: ✅ Pass / ❌ Fail
- Color Contrast: ✅ Pass / ❌ Fail
- Touch Targets: ✅ Pass / ❌ Fail

## Issues Found
1. [Issue description]
2. [Issue description]

## Recommendations
1. [Recommendation]
2. [Recommendation]
```

---

## 🚀 Production Testing

### Pre-Deployment Checklist

- [ ] All tests pass locally
- [ ] Assets built successfully
- [ ] No console errors
- [ ] No 404 errors
- [ ] Database migrations run
- [ ] Environment variables set
- [ ] Caches cleared

### Post-Deployment Checklist

- [ ] Test on production URL
- [ ] Verify assets load
- [ ] Test critical user flows
- [ ] Check error tracking
- [ ] Monitor performance
- [ ] Check analytics

### Rollback Plan

If issues found in production:

```bash
# 1. Revert to previous commit
git revert HEAD

# 2. Push to trigger redeploy
git push origin main

# 3. Clear production caches
php artisan config:clear --env=production
php artisan cache:clear --env=production
php artisan view:clear --env=production
```

---

## 📞 Support

### Reporting Issues

When reporting issues, include:
1. Device and browser
2. Steps to reproduce
3. Expected behavior
4. Actual behavior
5. Screenshots/video
6. Console errors

### Issue Template

```markdown
**Device:** iPhone 14 Pro
**Browser:** Safari 16
**OS:** iOS 16.5

**Steps to Reproduce:**
1. Navigate to /dashboard-mobile
2. Tap Applications tab
3. Observe error

**Expected:** Should navigate to applications page
**Actual:** Page shows 404 error

**Screenshots:** [Attach]
**Console Errors:** [Paste]
```

---

## ✅ Sign-Off

**Tested By:** _______________
**Date:** _______________
**Status:** ✅ Approved / ❌ Needs Work

**Notes:**
_______________________________________
_______________________________________
_______________________________________

---

**Version:** 1.0  
**Last Updated:** 2026-04-25  
**Status:** Ready for Testing
