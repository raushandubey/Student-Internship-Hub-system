<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInternshipController;
use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminUserController;
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
    
    // Email Logs
    Route::get('email-logs', [AdminApplicationController::class, 'emailLogs'])->name('email-logs');
    
    // User Management
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
});
