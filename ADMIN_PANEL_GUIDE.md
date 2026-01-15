# Admin Panel Implementation Guide

## Overview
This is a MINIMAL admin panel for the Student Internship Hub (SIH) project, designed for college-level demonstration and approval management.

## Architecture

### 1. **Middleware** (`app/Http/Middleware/AdminMiddleware.php`)
- Protects admin routes
- Checks if user is authenticated AND has `role = 'admin'`
- Returns 403 error if unauthorized

### 2. **Controllers** (`app/Http/Controllers/Admin/`)

#### AdminDashboardController
- **Purpose**: Display basic statistics
- **Method**: `index()` - Shows counts of students, internships, and applications

#### AdminInternshipController
- **Purpose**: Full CRUD for internships
- **Methods**:
  - `index()` - List all internships with pagination
  - `create()` - Show create form
  - `store()` - Save new internship
  - `edit()` - Show edit form
  - `update()` - Update internship
  - `destroy()` - Delete internship
  - `toggleStatus()` - Activate/deactivate internship

#### AdminApplicationController
- **Purpose**: Manage student applications
- **Methods**:
  - `index()` - List all applications with student and internship details
  - `updateStatus()` - Change application status (pending/approved/rejected)

#### AdminUserController
- **Purpose**: View student information
- **Methods**:
  - `index()` - List all students
  - `show()` - View student profile details

### 3. **Routes** (`routes/admin.php`)
All routes are:
- Prefixed with `/admin`
- Named with `admin.` prefix
- Protected by `auth` and `admin` middleware

```
GET  /admin/dashboard                          - Dashboard
GET  /admin/internships                        - List internships
GET  /admin/internships/create                 - Create form
POST /admin/internships                        - Store internship
GET  /admin/internships/{id}/edit              - Edit form
PUT  /admin/internships/{id}                   - Update internship
DELETE /admin/internships/{id}                 - Delete internship
POST /admin/internships/{id}/toggle-status     - Toggle active status
GET  /admin/applications                       - List applications
POST /admin/applications/{id}/status           - Update status
GET  /admin/users                              - List students
GET  /admin/users/{id}                         - View student details
```

### 4. **Models**

#### Application Model (`app/Models/Application.php`)
- **Fields**: `user_id`, `internship_id`, `status`
- **Relationships**:
  - `belongsTo(User)` - The student who applied
  - `belongsTo(Internship)` - The internship applied for

### 5. **Views** (`resources/views/admin/`)

#### Layout (`layout.blade.php`)
- Navigation bar with links to all admin sections
- Displays success/error messages
- Logout button
- Uses Tailwind CSS via CDN

#### Dashboard (`dashboard.blade.php`)
- Shows 3 stat cards: Students, Internships, Applications
- Quick action buttons

#### Internships
- `index.blade.php` - Table with edit/delete/toggle actions
- `create.blade.php` - Form to add new internship
- `edit.blade.php` - Form to update internship

#### Applications
- `index.blade.php` - Table with dropdown to change status

#### Users
- `index.blade.php` - Table listing all students
- `show.blade.php` - Detailed student profile view

## Database Schema

### applications table
```sql
id              - Primary key
user_id         - Foreign key to users
internship_id   - Foreign key to internships
status          - ENUM('pending', 'approved', 'rejected')
created_at      - Timestamp
updated_at      - Timestamp
```

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Admin User
```bash
php artisan db:seed --class=AdminSeeder
```

**Admin Credentials:**
- Email: `admin@sih.com`
- Password: `admin123`

### 3. Seed Sample Internships (if needed)
```bash
php artisan db:seed --class=InternshipSeeder
```

## Usage Flow

### Admin Login
1. Go to `/login`
2. Enter admin credentials
3. System checks `role = 'admin'`
4. Redirects to `/admin/dashboard`

### Managing Internships
1. Click "Internships" in navigation
2. View all internships in table format
3. Actions available:
   - **Edit**: Modify internship details
   - **Activate/Deactivate**: Toggle visibility to students
   - **Delete**: Remove internship permanently

### Managing Applications
1. Click "Applications" in navigation
2. View all student applications
3. See student name and internship title
4. Change status using dropdown (auto-submits)

### Viewing Students
1. Click "Students" in navigation
2. View list of all registered students
3. Click "View Details" to see full profile
4. View skills, academic background, resume, etc.

## Security Features

1. **Middleware Protection**: All admin routes require authentication + admin role
2. **CSRF Protection**: All forms include `@csrf` token
3. **Method Spoofing**: PUT/DELETE requests use `@method` directive
4. **Validation**: All form inputs are validated before processing

## Key Design Decisions

### Why Minimal?
- **College Project**: Focus on functionality over aesthetics
- **Demonstration**: Easy to understand and present
- **Maintainability**: Simple code is easier to debug and modify

### Why No API?
- Admin panel is internal tool, no need for API
- Direct Blade rendering is simpler and faster for this use case

### Why No Charts?
- Basic tables provide all necessary information
- Charts add complexity without significant value for this scope

### Why Tailwind CDN?
- No build process required
- Quick styling without configuration
- Sufficient for basic admin UI

## Testing the Admin Panel

### 1. Test Admin Login
```bash
# Visit: http://localhost:8000/login
# Login with: admin@sih.com / admin123
```

### 2. Test Internship CRUD
- Create a new internship
- Edit the internship
- Toggle its status
- Delete it

### 3. Test Application Management
- Have a student apply to an internship
- View the application in admin panel
- Change its status to "approved"
- Verify status updates

### 4. Test User Viewing
- View list of students
- Click on a student to see details
- Verify profile information displays correctly

## Common Issues & Solutions

### Issue: 403 Unauthorized
**Solution**: Ensure logged-in user has `role = 'admin'` in database

### Issue: Routes not found
**Solution**: Clear route cache with `php artisan route:clear`

### Issue: Middleware not working
**Solution**: Check `bootstrap/app.php` has admin middleware registered

### Issue: Skills not displaying
**Solution**: Ensure `required_skills` is cast to array in Internship model

## File Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       ├── AdminDashboardController.php
│   │       ├── AdminInternshipController.php
│   │       ├── AdminApplicationController.php
│   │       └── AdminUserController.php
│   └── Middleware/
│       └── AdminMiddleware.php
├── Models/
│   └── Application.php
database/
├── migrations/
│   └── xxxx_create_applications_table.php
└── seeders/
    └── AdminSeeder.php
resources/
└── views/
    └── admin/
        ├── layout.blade.php
        ├── dashboard.blade.php
        ├── internships/
        │   ├── index.blade.php
        │   ├── create.blade.php
        │   └── edit.blade.php
        ├── applications/
        │   └── index.blade.php
        └── users/
            ├── index.blade.php
            └── show.blade.php
routes/
└── admin.php
```

## Presentation Tips

1. **Start with Login**: Show admin login process
2. **Dashboard First**: Display statistics overview
3. **CRUD Demo**: Create, edit, and delete an internship
4. **Application Flow**: Show how applications are managed
5. **Student View**: Demonstrate viewing student profiles

## Future Enhancements (Optional)

If you want to extend this later:
- Add search and filtering
- Export data to Excel/PDF
- Email notifications on status changes
- Bulk actions for applications
- Activity logs

## Credits

**Project**: Student Internship Hub (SIH)
**Framework**: Laravel 12
**Purpose**: College Major Project
**Focus**: Simplicity, Clarity, Functionality

---

**Note**: This admin panel is intentionally kept simple for educational purposes. It demonstrates core Laravel concepts (MVC, middleware, authentication, CRUD) without unnecessary complexity.
