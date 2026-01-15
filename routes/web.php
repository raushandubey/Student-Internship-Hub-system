<?php
// routes/web.php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InternshipController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\Admin\AdminController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Welcome/Landing page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public internships listing (for browsing without login)
Route::get('/internships', [InternshipController::class, 'publicIndex'])->name('internships.public');
Route::get('/internships/{internship}', [InternshipController::class, 'show'])->name('internships.show');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Student Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Logout route (for authenticated users)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Dashboard Route - Role-based Redirect
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Protected Student Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:student'])->group(function () {
    
    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar'])->name('avatar.delete');
    });
    
    // Recommendations
    Route::prefix('recommendations')->name('recommendations.')->group(function () {
        Route::post('/feedback', [RecommendationController::class, 'feedback'])->name('feedback');
        Route::get('/similar/{internship}', [RecommendationController::class, 'similar'])->name('similar');
        Route::post('/preferences', [RecommendationController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/export', [RecommendationController::class, 'export'])->name('export');
        Route::get('/', [RecommendationController::class, 'index'])->name('index');
        Route::post('/feedback', [RecommendationController::class, 'feedback'])->name('feedback');
        Route::get('/similar/{internship}', [RecommendationController::class, 'similar'])->name('similar');
        Route::post('/preferences', [RecommendationController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/export', [RecommendationController::class, 'export'])->name('export');
    });
    
    // Internship Applications (Student side)
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [ApplicationController::class, 'myApplications'])->name('index');
        Route::post('/apply/{internship}', [ApplicationController::class, 'apply'])->name('apply');
    
    // Saved/Bookmarked Internships
    Route::prefix('bookmarks')->name('bookmarks.')->group(function () {
        Route::get('/', [InternshipController::class, 'bookmarks'])->name('index');
        Route::post('/toggle/{internship}', [InternshipController::class, 'toggleBookmark'])->name('toggle');
    });
        Route::delete('/{application}', [ApplicationController::class, 'cancel'])->name('cancel');
    });
    
    // Alias route for application tracker
    Route::get('/my-applications', [ApplicationController::class, 'myApplications'])->name('my-applications');
    
    // Saved/Bookmarked Internships

/*
|--------------------------------------------------------------------------
| API Routes (for AJAX calls)
|--------------------------------------------------------------------------
*/

Route::prefix('api')->name('api.')->middleware('auth')->group(function () {
    // Recommendation API endpoints
    Route::get('/recommendations/filters', [RecommendationController::class, 'getFilters'])->name('recommendations.filters');
    Route::post('/recommendations/feedback', [RecommendationController::class, 'feedback'])->name('recommendations.feedback');
    
    // Search & Autocomplete
    Route::get('/search/skills', [InternshipController::class, 'searchSkills'])->name('search.skills');
    Route::get('/search/organizations', [InternshipController::class, 'searchOrganizations'])->name('search.organizations');
    Route::get('/search/locations', [InternshipController::class, 'searchLocations'])->name('search.locations');
    
    // Dashboard data
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
});
    Route::prefix('bookmarks')->name('bookmarks.')->group(function () {
        Route::get('/', [InternshipController::class, 'bookmarks'])->name('index');
        Route::post('/toggle/{internship}', [InternshipController::class, 'toggleBookmark'])->name('toggle');
    });
});

/*
|--------------------------------------------------------------------------
| Protected Admin Routes (handled in routes/admin.php)
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| API Routes (for AJAX calls)
|--------------------------------------------------------------------------
*/

Route::prefix('api')->name('api.')->middleware('auth')->group(function () {
    // Recommendation API endpoints
    Route::get('/recommendations/filters', [RecommendationController::class, 'getFilters'])->name('recommendations.filters');
    Route::post('/recommendations/feedback', [RecommendationController::class, 'feedback'])->name('recommendations.feedback');
    
    // Search & Autocomplete
    Route::get('/search/skills', [InternshipController::class, 'searchSkills'])->name('search.skills');
    Route::get('/search/organizations', [InternshipController::class, 'searchOrganizations'])->name('search.organizations');
    Route::get('/search/locations', [InternshipController::class, 'searchLocations'])->name('search.locations');
    
    // Dashboard data
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
});

/*
|--------------------------------------------------------------------------
| Email Verification Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return view('errors.404');
});
