<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInternshipController;
use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminRecruiterAnalyticsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminRecruiterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| All routes here are protected by 'auth' and 'admin' middleware
| Only users with role='admin' can access these routes
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Analytics Dashboard
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics');
    
    // Internship Management
    Route::resource('internships', AdminInternshipController::class);
    Route::post('internships/{internship}/toggle-status', [AdminInternshipController::class, 'toggleStatus'])
        ->name('internships.toggle-status');
    
    // Application Management
    // Phase 9: Rate limiting to prevent accidental bulk status updates
    Route::get('applications', [AdminApplicationController::class, 'index'])->name('applications.index');
    Route::post('applications/{application}/status', [AdminApplicationController::class, 'updateStatus'])
        ->middleware('throttle:60,1') // 60 status updates per minute (reasonable for admin)
        ->name('applications.update-status');
    Route::get('applications/{application}/history', [AdminApplicationController::class, 'history'])
        ->name('applications.history');
    Route::get('applications/{application}/profile', [AdminApplicationController::class, 'getProfile'])
        ->middleware('throttle:60,1')
        ->name('applications.profile');
    
    // Email Logs
    Route::get('email-logs', [AdminApplicationController::class, 'emailLogs'])->name('email-logs');
    
    // User Management
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');

    // Recruiter Management (Task 14.1)
    Route::get('recruiters', [AdminRecruiterController::class, 'index'])->name('recruiters.index');
    Route::get('recruiters/{user}', [AdminRecruiterController::class, 'show'])->name('recruiters.show');
    Route::post('recruiters/{user}/approve', [AdminRecruiterController::class, 'approve'])->name('recruiters.approve');
    Route::post('recruiters/{user}/reject', [AdminRecruiterController::class, 'reject'])->name('recruiters.reject');
    Route::post('recruiters/{user}/suspend', [AdminRecruiterController::class, 'suspend'])->name('recruiters.suspend');
    Route::post('recruiters/{user}/activate', [AdminRecruiterController::class, 'activate'])->name('recruiters.activate');
    Route::post('recruiters/bulk-action', [AdminRecruiterController::class, 'bulkAction'])->name('recruiters.bulk-action');
    Route::get('recruiters/{user}/edit-profile', [AdminRecruiterController::class, 'editProfile'])->name('recruiters.edit-profile');
    Route::put('recruiters/{user}/profile', [AdminRecruiterController::class, 'updateProfile'])->name('recruiters.update-profile');
    Route::get('recruiters/{user}/activity-timeline', [AdminRecruiterController::class, 'activityTimeline'])->name('recruiters.activity-timeline');

    // Recruiter Analytics (Task 14.2)
    Route::get('recruiter-analytics', [AdminRecruiterAnalyticsController::class, 'index'])->name('recruiter-analytics.index');
    Route::get('recruiter-analytics/report', [AdminRecruiterAnalyticsController::class, 'generateReport'])->name('recruiter-analytics.report');

    // Audit Logs (Task 14.3)
    Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');

    // Internship deactivation (Task 14.4)
    Route::post('internships/{internship}/deactivate', [AdminInternshipController::class, 'deactivateRecruiterInternship'])->name('internships.deactivate');

    // Application show route
    Route::get('applications/{application}', [AdminApplicationController::class, 'show'])->name('applications.show');
});
