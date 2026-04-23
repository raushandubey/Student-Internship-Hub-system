<?php

use App\Http\Controllers\Recruiter\RecruiterDashboardController;
use App\Http\Controllers\Recruiter\RecruiterInternshipController;
use App\Http\Controllers\Recruiter\RecruiterApplicationController;
use App\Http\Controllers\Recruiter\RecruiterProfileController;
use App\Http\Controllers\Recruiter\RecruiterAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'recruiter'])->prefix('recruiter')->name('recruiter.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [RecruiterDashboardController::class, 'index'])->name('dashboard');

    // Internship management
    Route::resource('internships', RecruiterInternshipController::class);

    // Application management
    Route::get('/applications', [RecruiterApplicationController::class, 'index'])->name('applications.index');

    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/applications/{application}/status', [RecruiterApplicationController::class, 'updateStatus'])
            ->name('applications.update-status');

        Route::post('/applications/bulk-update', [RecruiterApplicationController::class, 'bulkUpdateStatus'])
            ->name('applications.bulk-update');
    });

    // AJAX endpoints
    Route::get('/applications/{application}/profile', [RecruiterApplicationController::class, 'getProfile'])
        ->name('applications.profile');

    Route::get('/applications/{application}/history', [RecruiterApplicationController::class, 'history'])
        ->name('applications.history');

    // Analytics
    Route::get('/analytics', [RecruiterAnalyticsController::class, 'index'])->name('analytics');

    // Profile management
    Route::get('/profile', [RecruiterProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [RecruiterProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [RecruiterProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/logo', [RecruiterProfileController::class, 'updateLogo'])->name('profile.logo');
});
