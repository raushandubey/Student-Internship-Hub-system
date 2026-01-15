# Quick Start Guide - SIH Dashboards

## ✅ Setup Complete!

Both Admin and Student dashboards are now fully functional and ready to use.

## 🔐 Login Credentials

### Admin Account:
- **URL**: `http://localhost:8000/login`
- **Email**: `admin@sih.com`
- **Password**: `admin123`

### Student Account:
- **Register**: `http://localhost:8000/register`
- Or use existing student account

## 🚀 How to Access Dashboards

### 1. Start the Server
```bash
php artisan serve
```

### 2. Login as Admin
1. Go to: `http://localhost:8000/login`
2. Enter admin credentials
3. You'll be automatically redirected to: `http://localhost:8000/admin/dashboard`

### 3. Login as Student
1. Go to: `http://localhost:8000/login`
2. Enter student credentials
3. You'll be automatically redirected to: `http://localhost:8000/dashboard`

## 📊 What You'll See

### Admin Dashboard Features:
✅ **4 Colorful Stat Cards:**
- Total Students (Blue)
- Total Internships (Green)
- Total Applications (Purple)
- Pending Applications (Yellow)

✅ **Quick Actions:**
- Add New Internship
- Review Applications
- View Students

✅ **System Status Panel:**
- Active/Inactive internships
- Approval rate
- Real-time stats

✅ **Navigation Menu:**
- Dashboard
- Internships (CRUD operations)
- Applications (Status management)
- Students (View profiles)

### Student Dashboard Features:
✅ **Profile Completion:**
- Visual progress ring
- Percentage display

✅ **Statistics Cards:**
- Applications Sent
- Interviews Scheduled
- Job Matches
- Profile Views

✅ **Action Cards:**
- Complete Your Profile
- Job Recommendations
- Career Analytics

✅ **Recent Activity Timeline**

## 🎯 Testing the System

### Test Admin Panel:
```bash
# 1. Login as admin
# 2. Click "Internships" → "Add New Internship"
# 3. Fill the form and submit
# 4. Go to "Applications" to see student applications
# 5. Change application status using dropdown
```

### Test Student Panel:
```bash
# 1. Register/Login as student
# 2. Complete your profile (Profile → Edit)
# 3. Add skills that match internships
# 4. View recommendations
# 5. Apply to internships
```

## 🔄 How Routing Works

```
User logs in → DashboardController checks role
    ↓
If role = 'admin' → Redirect to /admin/dashboard
If role = 'student' → Show /dashboard (student view)
```

## 📁 Key Files

### Controllers:
- `app/Http/Controllers/DashboardController.php` - Main dashboard router
- `app/Http/Controllers/Admin/AdminDashboardController.php` - Admin stats
- `app/Http/Controllers/Admin/AdminInternshipController.php` - Internship CRUD
- `app/Http/Controllers/Admin/AdminApplicationController.php` - Application management
- `app/Http/Controllers/Admin/AdminUserController.php` - Student viewing

### Views:
- `resources/views/admin/dashboard.blade.php` - Admin dashboard
- `resources/views/student/dashboard.blade.php` - Student dashboard
- `resources/views/admin/layout.blade.php` - Admin layout

### Routes:
- `routes/web.php` - Main routes + student routes
- `routes/admin.php` - Admin-only routes

### Middleware:
- `app/Http/Middleware/AdminMiddleware.php` - Protects admin routes

## 🛠️ Troubleshooting

### Issue: Can't access admin dashboard
**Solution:**
```sql
-- Check user role
SELECT * FROM users WHERE email = 'admin@sih.com';

-- If role is not 'admin', update it:
UPDATE users SET role = 'admin' WHERE email = 'admin@sih.com';
```

### Issue: No internships showing
**Solution:**
```bash
php artisan db:seed --class=InternshipSeeder
```

### Issue: Routes not working
**Solution:**
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Issue: Seeder fix needed
**Solution:**
```bash
# Fix the InternshipSeeder syntax first, then:
php artisan db:seed --class=InternshipSeeder
```

## 📸 Expected Screenshots

### Admin Dashboard:
- Blue gradient navigation bar
- 4 colorful gradient stat cards with icons
- Quick action buttons with icons
- System status and quick stats panels

### Student Dashboard:
- Glass morphism welcome card
- Profile completion ring
- 4 stat cards with trends
- 3 action cards
- Recent activity timeline

## ✨ Features Summary

### Admin Can:
- ✅ View system statistics
- ✅ Create/Edit/Delete internships
- ✅ Activate/Deactivate internships
- ✅ View all applications
- ✅ Change application status (pending/approved/rejected)
- ✅ View all students
- ✅ View student profiles

### Student Can:
- ✅ View personalized dashboard
- ✅ Track profile completion
- ✅ See recommendations count
- ✅ View application statistics
- ✅ Access profile management
- ✅ Browse internships
- ✅ Apply to internships

## 🎓 For College Presentation

### Demo Flow:
1. **Show Admin Login** → Display admin dashboard with stats
2. **Create Internship** → Add a new internship opportunity
3. **Show Student Login** → Display student dashboard
4. **Student Applies** → Student applies to the internship
5. **Admin Reviews** → Admin sees application and approves it
6. **Show Updated Stats** → Dashboard reflects the changes

### Key Points to Highlight:
- Role-based access control
- Real-time statistics
- Simple, clean UI
- Full CRUD operations
- Application workflow
- Responsive design

## 📞 Need Help?

Check these files for detailed information:
- `ADMIN_PANEL_GUIDE.md` - Complete admin panel documentation
- `DASHBOARD_SETUP.md` - Dashboard setup and architecture
- `README.md` - Full project documentation

---

**Status**: ✅ Ready for demonstration
**Last Updated**: January 14, 2026
