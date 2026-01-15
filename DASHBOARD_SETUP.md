# Dashboard Setup Guide

## Overview
Both Admin and Student dashboards are now properly configured with role-based routing.

## How It Works

### 1. **Unified Dashboard Route**
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
```

### 2. **DashboardController Logic**
The controller automatically redirects based on user role:

```php
public function index()
{
    $user = Auth::user();
    
    // Admin users → redirect to admin dashboard
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    
    // Student users → show student dashboard
    return view('student.dashboard', [...]);
}
```

### 3. **Admin Dashboard Route**
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});
```

## Access URLs

### For Students:
- Login: `http://localhost:8000/login`
- After login: Automatically redirected to `/dashboard` (student view)
- Direct URL: `http://localhost:8000/dashboard`

### For Admins:
- Login: `http://localhost:8000/login`
- After login: Automatically redirected to `/admin/dashboard`
- Direct URL: `http://localhost:8000/admin/dashboard`

## Testing

### Test Admin Dashboard:
```bash
# 1. Seed admin user (if not done)
php artisan db:seed --class=AdminSeeder

# 2. Login with:
Email: admin@sih.com
Password: admin123

# 3. You should see the admin dashboard with:
- 4 colorful stat cards (Students, Internships, Applications, Pending)
- Quick action buttons
- System status panel
- Quick stats panel
```

### Test Student Dashboard:
```bash
# 1. Register a new student or login with existing student account

# 2. You should see the student dashboard with:
- Welcome message with profile completion
- Stats grid (Applications, Interviews, Recommendations, Profile Views)
- Action cards (Complete Profile, Job Recommendations, Career Analytics)
- Recent activity timeline
```

## Dashboard Features

### Admin Dashboard (`resources/views/admin/dashboard.blade.php`)
✅ **Statistics Cards:**
- Total Students (Blue gradient)
- Total Internships (Green gradient)
- Total Applications (Purple gradient)
- Pending Applications (Yellow gradient)

✅ **Quick Actions:**
- Add New Internship
- Review Applications
- View Students

✅ **System Status:**
- Active/Inactive internships count
- Approval rate calculation
- Real-time statistics

✅ **Quick Stats:**
- Average applications per internship
- Students with profiles
- Pending reviews count

### Student Dashboard (`resources/views/student/dashboard.blade.php`)
✅ **Profile Completion:**
- Visual progress ring
- Percentage display
- Status indicator

✅ **Statistics:**
- Applications sent
- Interviews scheduled
- Job matches (recommendations)
- Profile views

✅ **Action Cards:**
- Complete Your Profile
- Job Recommendations
- Career Analytics

✅ **Recent Activity:**
- Application history
- Profile views
- New recommendations

## Navigation

### Admin Navigation Bar:
- Dashboard (with active state highlighting)
- Internships
- Applications
- Students
- User info display
- Logout button

### Student Navigation:
- Uses existing `layouts.app` layout
- Student-specific menu items
- Profile management links

## Styling

### Admin Panel:
- **Color Scheme:** Blue gradient navigation
- **Cards:** Gradient backgrounds (blue, green, purple, yellow)
- **Icons:** SVG icons for visual appeal
- **Layout:** Responsive grid system
- **Effects:** Hover transitions, shadows

### Student Panel:
- **Color Scheme:** Glass morphism effects
- **Cards:** Transparent with backdrop blur
- **Animations:** Floating elements, AOS animations
- **Layout:** Modern, responsive design
- **Effects:** Progress rings, stat counters

## Troubleshooting

### Issue: "403 Unauthorized" when accessing admin dashboard
**Solution:** 
```sql
-- Check user role in database
SELECT id, name, email, role FROM users WHERE email = 'admin@sih.com';

-- If role is not 'admin', update it:
UPDATE users SET role = 'admin' WHERE email = 'admin@sih.com';
```

### Issue: Student dashboard not showing recommendations
**Solution:**
1. Ensure student has completed profile with skills
2. Ensure internships exist and are active
3. Run debug command: `php artisan check:recommendations`

### Issue: Dashboard route not found
**Solution:**
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Issue: Admin dashboard shows no data
**Solution:**
```bash
# Seed sample data
php artisan db:seed --class=InternshipSeeder

# Create test applications (manually through student account)
```

## Database Requirements

### Required Tables:
- ✅ `users` (with `role` column)
- ✅ `profiles`
- ✅ `internships`
- ✅ `applications`

### Run Migrations:
```bash
php artisan migrate
```

## Quick Setup Checklist

- [ ] Run migrations: `php artisan migrate`
- [ ] Seed admin user: `php artisan db:seed --class=AdminSeeder`
- [ ] Seed internships: `php artisan db:seed --class=InternshipSeeder`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Test admin login: `admin@sih.com` / `admin123`
- [ ] Test student registration and login
- [ ] Verify admin dashboard displays correctly
- [ ] Verify student dashboard displays correctly
- [ ] Test navigation between sections

## Screenshots Description

### Admin Dashboard Should Show:
1. **Top Navigation:** Blue gradient bar with menu items
2. **Welcome Section:** Greeting with admin name
3. **4 Stat Cards:** Colorful gradient cards with icons
4. **Quick Actions:** 3 action buttons with icons
5. **Bottom Panels:** System status and quick stats

### Student Dashboard Should Show:
1. **Welcome Card:** Glass effect with profile completion ring
2. **Stats Grid:** 4 cards with application metrics
3. **Action Cards:** 3 cards for profile, recommendations, analytics
4. **Recent Activity:** Timeline of recent actions

## File Structure
```
app/
├── Http/
│   └── Controllers/
│       ├── DashboardController.php (handles routing)
│       └── Admin/
│           └── AdminDashboardController.php (admin stats)
resources/
└── views/
    ├── admin/
    │   ├── layout.blade.php (admin layout)
    │   └── dashboard.blade.php (admin dashboard)
    └── student/
        └── dashboard.blade.php (student dashboard)
routes/
├── web.php (main dashboard route)
└── admin.php (admin routes)
```

## Next Steps

1. **Test Both Dashboards:**
   - Login as admin and verify all stats display
   - Login as student and verify profile completion works

2. **Customize as Needed:**
   - Modify colors in Tailwind classes
   - Add more statistics if required
   - Adjust layout for your needs

3. **Add Real Data:**
   - Create student accounts
   - Add internships
   - Submit applications
   - Watch dashboards update in real-time

---

**Note:** Both dashboards are now fully functional and ready for demonstration. The admin panel shows system-wide statistics while the student dashboard shows personalized information.
