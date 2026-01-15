# Student Internship Hub (SIH)

A comprehensive web application for managing student internships, built with Laravel 12 for college major project demonstration.

## 🎯 Project Overview

Student Internship Hub (SIH) is a platform that connects students with internship opportunities through a rule-based recommendation system. The platform features role-based access for students and administrators, with a focus on simplicity and functionality.

## ✨ Key Features

### For Students
- **Profile Management** - Complete profile with skills, academic background, and resume
- **Skill-Based Recommendations** - Database-driven internship matching based on skills and interests
- **One-Click Applications** - Apply to internships instantly
- **Application Tracker** - Monitor all applications and their status
- **Dashboard Analytics** - Track profile completion and application statistics

### For Administrators
- **Internship Management** - Full CRUD operations for internship postings
- **Application Review** - View and manage student applications
- **Status Management** - Approve or reject applications
- **Student Profiles** - View detailed student information
- **System Statistics** - Real-time dashboard with key metrics

## 🛠️ Technology Stack

- **Backend:** Laravel 12, PHP 8.2
- **Frontend:** Blade Templates, Tailwind CSS
- **Database:** MySQL
- **Authentication:** Laravel Auth with role-based access control
- **Architecture:** MVC Pattern

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Node.js & NPM (for asset compilation)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd student-internship-hub
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sih_database
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. Seed Database
```bash
# Seed admin user
php artisan db:seed --class=AdminSeeder

# Seed sample internships (optional)
php artisan db:seed --class=InternshipSeeder
```

### 7. Start Development Server
```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 🔐 Default Credentials

### Admin Account
- **Email:** admin@sih.com
- **Password:** admin123
- **Access:** http://localhost:8000/admin/dashboard

### Student Account
- Register at: http://localhost:8000/register
- Or create via database seeder

## 📁 Project Structure

```
student-internship-hub/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/              # Admin panel controllers
│   │   │   ├── ApplicationController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ProfileController.php
│   │   │   └── RecommendationController.php
│   │   └── Middleware/
│   │       └── AdminMiddleware.php  # Admin route protection
│   └── Models/
│       ├── User.php
│       ├── Profile.php
│       ├── Internship.php
│       └── Application.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/
│       ├── admin/                   # Admin panel views
│       ├── student/                 # Student dashboard views
│       └── recommendations/         # Recommendation system views
├── routes/
│   ├── web.php                      # Student routes
│   └── admin.php                    # Admin routes
└── public/
```

## 🎓 Feature Documentation

Comprehensive guides are available for each feature:

- **[QUICK_START.md](QUICK_START.md)** - Quick setup and testing guide
- **[ADMIN_PANEL_GUIDE.md](ADMIN_PANEL_GUIDE.md)** - Complete admin panel documentation
- **[APPLY_INTERNSHIP_GUIDE.md](APPLY_INTERNSHIP_GUIDE.md)** - Apply feature implementation
- **[APPLICATION_TRACKER_GUIDE.md](APPLICATION_TRACKER_GUIDE.md)** - Tracker feature details
- **[DASHBOARD_SETUP.md](DASHBOARD_SETUP.md)** - Dashboard architecture

## 🔄 Application Workflow

```
1. Student Registration
   ↓
2. Complete Profile (skills, academic background)
   ↓
3. View Personalized Recommendations
   ↓
4. Apply to Internships (one-click)
   ↓
5. Track Application Status
   ↓
6. Admin Reviews Application
   ↓
7. Status Updated (Approved/Rejected)
   ↓
8. Student Receives Notification
```

## 🎯 Recommendation System

The platform uses a **rule-based skill-matching algorithm**:

1. **Skill Matching**: Compares student skills with internship requirements
2. **Similarity Scoring**: Calculates match percentage based on overlapping skills
3. **Academic Background**: Matches keywords from student's academic field with internship titles
4. **Filtering**: Shows only active internships
5. **Ranking**: Orders results by similarity score

**Technical Implementation:**
- Database-driven queries using Laravel Eloquent
- String comparison and array intersection for skill matching
- No machine learning or predictive algorithms
- Transparent, rule-based logic that can be easily explained and audited

## 🛡️ Security Features

- **Authentication:** Laravel's built-in authentication system
- **Authorization:** Role-based middleware (admin, student)
- **CSRF Protection:** All forms include CSRF tokens
- **SQL Injection Prevention:** Eloquent ORM with parameter binding
- **XSS Protection:** Blade template escaping
- **Password Hashing:** Bcrypt algorithm
- **Duplicate Prevention:** Unique constraints on applications

## 📊 Database Schema

### Users Table
- id, name, email, password, role (admin/student)

### Profiles Table
- id, user_id, academic_background, skills, career_interests, resume_path

### Internships Table
- id, title, organization, description, required_skills, location, duration, is_active

### Applications Table
- id, user_id, internship_id, status (pending/approved/rejected)
- Unique constraint: (user_id, internship_id)

## 🧪 Testing

### Manual Testing
```bash
# Test admin login
1. Visit /login
2. Login with admin@sih.com / admin123
3. Verify redirect to /admin/dashboard

# Test student flow
1. Register new student account
2. Complete profile with skills
3. View recommendations
4. Apply to internship
5. Check application tracker
```

### Database Verification
```bash
# Check recommendations
php artisan check:recommendations

# View application data
php artisan tinker
>>> Application::with('user', 'internship')->get();
```

## 🎨 UI/UX Features

- **Responsive Design:** Works on desktop, tablet, and mobile
- **Modern UI:** Gradient cards, glass morphism effects
- **Color-Coded Status:** Visual feedback for application states
- **Empty States:** Helpful messages when no data exists
- **Loading States:** User feedback during operations
- **Success/Error Messages:** Flash messages for user actions

## 🔧 Troubleshooting

### Common Issues

**Issue:** Routes not found
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

**Issue:** No recommendations showing
```bash
# Check if internships exist
php artisan tinker
>>> Internship::where('is_active', true)->count();

# Verify profile has skills
>>> User::find(1)->profile->skills;
```

**Issue:** Admin cannot access admin panel
```sql
-- Verify admin role
SELECT * FROM users WHERE email = 'admin@sih.com';

-- Update role if needed
UPDATE users SET role = 'admin' WHERE email = 'admin@sih.com';
```

## 📈 Future Enhancements

Potential features for future development:

- Email notifications for application status changes
- Advanced search and filtering
- Export applications to PDF/Excel
- Real-time chat between students and organizations
- Interview scheduling system
- Document verification system
- Analytics dashboard with charts
- Mobile application

## 🤝 Contributing

This is a college major project. For educational purposes only.

## 📝 License

This project is created for educational purposes as part of a college major project.

## 👥 Team

- **Project Type:** College Major Project
- **Framework:** Laravel 12
- **Focus:** Simplicity, Clarity, Functionality

## 📞 Support

For issues or questions:
1. Check the feature-specific documentation in the root directory
2. Review the troubleshooting section above
3. Verify database and environment configuration

## 🎯 Project Goals

This project demonstrates:
- ✅ Full-stack web development with Laravel
- ✅ Role-based access control
- ✅ CRUD operations
- ✅ Database relationships
- ✅ Authentication & authorization
- ✅ Rule-based recommendation algorithm
- ✅ Clean MVC architecture
- ✅ Security best practices
- ✅ User-friendly interface
- ✅ Database-driven skill matching

---

**Status:** ✅ Ready for College Demonstration
**Last Updated:** January 15, 2026
**Version:** 1.0.0
**System Type:** Rule-Based Web Application
