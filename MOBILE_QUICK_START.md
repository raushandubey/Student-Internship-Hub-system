# 🚀 Mobile Redesign - Quick Start Guide

## For Developers

This guide will get you up and running with the mobile redesign in 5 minutes.

---

## ⚡ Quick Setup (5 minutes)

### Step 1: Build Assets (1 minute)

```bash
npm run build
```

**Expected output:**
```
✓ 57 modules transformed.
✓ built in 2.40s
```

### Step 2: Clear Caches (30 seconds)

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Step 3: Start Server (30 seconds)

```bash
php artisan serve
```

### Step 4: Test Mobile Pages (3 minutes)

Open in browser:
- http://localhost:8000/dashboard-mobile
- http://localhost:8000/profile/edit-mobile
- http://localhost:8000/my-applications-mobile

**✅ If pages load, you're done!**

---

## 📱 Mobile Pages Overview

### Available Routes

| Route | Description | Auth Required |
|-------|-------------|---------------|
| `/dashboard-mobile` | Mobile dashboard | Yes (Student) |
| `/profile/edit-mobile` | Multi-step profile form | Yes (Student) |
| `/my-applications-mobile` | Applications tracker | Yes (Student) |
| `/recommendations` | Job recommendations | Yes (Student) |

### Auto-Detection

Some pages auto-detect mobile and show mobile view:
- `/profile` - Shows mobile view on mobile devices
- More pages can be added with user agent detection

---

## 🎨 Using Mobile Components

### Bottom Navigation

Already included in `app-mobile.blade.php` layout:

```blade
@extends('layouts.app-mobile')

@section('content')
    <!-- Your content here -->
@endsection
```

Bottom nav shows automatically for authenticated students.

### Internship Card

```blade
<x-internship-card 
    :internship="$internship"
    :matchScore="85"
    :matchingSkills="['PHP', 'Laravel']"
/>
```

### Application Card

```blade
<x-application-card :application="$application" />
```

### Multi-Step Form

```blade
<div class="form-wizard" data-total-steps="4" data-form-id="my-form">
    <!-- Progress stepper -->
    <div class="progress-stepper">
        <div class="step-item active" data-step="1">
            <div class="step-circle">1</div>
        </div>
        <div class="step-line"></div>
        <div class="step-item" data-step="2">
            <div class="step-circle">2</div>
        </div>
        <!-- More steps -->
    </div>

    <!-- Form steps -->
    <form>
        <div class="form-step active" data-step="1">
            <!-- Step 1 content -->
        </div>
        <div class="form-step hidden" data-step="2">
            <!-- Step 2 content -->
        </div>
        
        <!-- Navigation buttons -->
        <div class="flex gap-3 mt-6">
            <button type="button" id="prevBtn" class="btn btn-secondary flex-1">Back</button>
            <button type="button" id="nextBtn" class="btn btn-primary flex-1">Next</button>
            <button type="submit" id="submitBtn" class="btn btn-primary flex-1 hidden">Submit</button>
        </div>
    </form>
</div>
```

JavaScript automatically handles navigation and auto-save.

---

## 🎯 Common Tasks

### Add a New Mobile Page

1. **Create Blade file:**
```bash
touch resources/views/student/my-page-mobile.blade.php
```

2. **Use mobile layout:**
```blade
@extends('layouts.app-mobile')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <!-- Your content -->
</div>
@endsection
```

3. **Add route:**
```php
Route::get('/my-page-mobile', [MyController::class, 'indexMobile'])
    ->middleware(['auth'])
    ->name('my-page.mobile');
```

4. **Add controller method:**
```php
public function indexMobile()
{
    return view('student.my-page-mobile', [
        'data' => $this->getData(),
    ]);
}
```

### Add Bottom Nav Item

Edit `resources/views/components/bottom-nav.blade.php`:

```blade
<a href="{{ route('my.route') }}" 
   class="flex flex-col items-center justify-center flex-1 py-2 min-w-[60px] min-h-[44px] transition-all {{ request()->routeIs('my.route') ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-primary-600' }}">
    <i class="fas fa-icon text-xl mb-1"></i>
    <span class="text-xs">Label</span>
</a>
```

### Customize Colors

Edit `tailwind.config.js`:

```javascript
colors: {
    primary: {
        500: '#your-color',
        600: '#your-darker-color',
        700: '#your-darkest-color',
    },
}
```

Rebuild:
```bash
npm run build
```

### Add Custom Styles

Edit `resources/css/mobile-components.css`:

```css
.my-custom-class {
    /* Your styles */
}
```

Rebuild:
```bash
npm run build
```

---

## 🐛 Troubleshooting

### Issue: Pages show 404

**Solution:**
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: Styles not applying

**Solution:**
```bash
npm run build
php artisan view:clear
# Hard refresh browser (Ctrl+Shift+R)
```

### Issue: Bottom nav not showing

**Check:**
1. User is authenticated: `@auth`
2. User is student: `auth()->user()->isStudent()`
3. Using mobile layout: `@extends('layouts.app-mobile')`

### Issue: Form wizard not working

**Check:**
1. Form has class `form-wizard`
2. Form has `data-total-steps` attribute
3. Steps have `data-step` attribute
4. JavaScript is loaded: Check browser console

### Issue: Assets not loading

**Check:**
```bash
# Verify build output
ls -la public/build/

# Should see:
# - manifest.json
# - assets/mobile-components-*.css
# - assets/form-wizard-*.js
```

If missing:
```bash
npm run build
```

---

## 📚 File Structure

```
resources/
├── views/
│   ├── layouts/
│   │   └── app-mobile.blade.php          # Mobile layout
│   ├── components/
│   │   ├── bottom-nav.blade.php          # Bottom navigation
│   │   ├── internship-card.blade.php     # Internship card
│   │   └── application-card.blade.php    # Application card
│   └── student/
│       ├── dashboard-mobile.blade.php    # Mobile dashboard
│       ├── profile-edit-mobile.blade.php # Profile form
│       ├── profile-show-mobile.blade.php # Profile view
│       ├── applications-mobile.blade.php # Applications
│       └── recommendations-mobile.blade.php # Recommendations
├── css/
│   └── mobile-components.css             # Mobile styles
└── js/
    └── form-wizard.js                    # Form wizard logic

app/Http/Controllers/
├── DashboardController.php               # indexMobile()
├── ProfileController.php                 # editMobile()
└── ApplicationController.php             # myApplicationsMobile()

routes/
└── web.php                               # Mobile routes
```

---

## 🎓 Learning Resources

### Tailwind CSS
- Docs: https://tailwindcss.com/docs
- Cheat Sheet: https://nerdcave.com/tailwind-cheat-sheet

### Mobile Design
- Touch targets: 44x44px minimum
- Spacing: 8px grid system
- Typography: 16px minimum for body text
- Safe area: Use `pb-safe` class

### Form Wizard
- Auto-saves every 1 second
- Stores in localStorage
- Restores on page load
- Clears on submit

---

## ✅ Checklist for New Developers

- [ ] Clone repository
- [ ] Run `npm install`
- [ ] Run `npm run build`
- [ ] Run `php artisan serve`
- [ ] Visit `/dashboard-mobile`
- [ ] Test bottom navigation
- [ ] Test profile form
- [ ] Test applications page
- [ ] Read documentation files
- [ ] Review component code

---

## 🚀 Next Steps

1. **Test locally** - Visit all mobile pages
2. **Test on phone** - Use real device or emulator
3. **Read full docs** - See `MOBILE_FIRST_REDESIGN_SPEC.md`
4. **Review code** - Check component implementations
5. **Deploy** - Follow `MOBILE_REDESIGN_DEPLOYMENT.md`

---

## 📞 Need Help?

1. Check `MOBILE_TESTING_GUIDE.md` for testing procedures
2. Check `MOBILE_REDESIGN_DEPLOYMENT.md` for deployment steps
3. Check `MOBILE_FIRST_REDESIGN_SPEC.md` for design system
4. Check browser console for JavaScript errors
5. Check Laravel logs: `storage/logs/laravel.log`

---

**Quick Links:**
- [Full Specification](MOBILE_FIRST_REDESIGN_SPEC.md)
- [Deployment Guide](MOBILE_REDESIGN_DEPLOYMENT.md)
- [Testing Guide](MOBILE_TESTING_GUIDE.md)
- [Implementation Summary](MOBILE_REDESIGN_COMPLETE.md)

**Status:** ✅ Ready to Use  
**Version:** 1.0  
**Last Updated:** 2026-04-25
