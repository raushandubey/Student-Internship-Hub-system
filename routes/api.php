<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\ProfileApiController;
use App\Http\Controllers\Api\V1\JobApiController;
use App\Http\Controllers\Api\V1\RecommendationApiController;
use App\Http\Controllers\Api\V1\ResumeOptimizerApiController;
use App\Http\Controllers\Api\V1\RecruiterApiController;
use App\Http\Controllers\Api\V1\ApplicationApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Guest Auth Routes
    Route::post('/auth/register/student', [AuthApiController::class, 'registerStudent']);
    Route::post('/auth/register/recruiter', [AuthApiController::class, 'registerRecruiter']);
    Route::post('/auth/login', [AuthApiController::class, 'login']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth User / Logout
        Route::get('/user', [AuthApiController::class, 'user']);
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);

        // Profile Management
        Route::get('/profile', [ProfileApiController::class, 'show']);
        Route::put('/profile', [ProfileApiController::class, 'update']);
        Route::post('/profile/photo', [ProfileApiController::class, 'uploadPhoto']);
        Route::post('/profile/resume', [ProfileApiController::class, 'uploadResume']);

        // Job Listings & Applying
        Route::get('/jobs', [JobApiController::class, 'index']);
        Route::get('/jobs/{internship}', [JobApiController::class, 'show']);
        Route::post('/jobs/{internship}/apply', [JobApiController::class, 'apply']);

        // Applications Tracker
        Route::get('/applications', [ApplicationApiController::class, 'index']);
        Route::get('/applications/stats', [ApplicationApiController::class, 'stats']);
        Route::get('/applications/{application}', [ApplicationApiController::class, 'show']);
        Route::get('/applications/{application}/history', [ApplicationApiController::class, 'history']);
        Route::delete('/applications/{application}', [ApplicationApiController::class, 'destroy']);

        // Recommendations
        Route::get('/recommendations', [RecommendationApiController::class, 'index']);

        // Resume Optimizer
        Route::get('/resume-optimizer/jobs', [ResumeOptimizerApiController::class, 'internships']);
        Route::post('/resume-optimizer/score/{internship}', [ResumeOptimizerApiController::class, 'score']);
        Route::post('/resume-optimizer/rewrite/{internship}', [ResumeOptimizerApiController::class, 'rewrite']);
        Route::get('/resume-optimizer/download/{internship}', [ResumeOptimizerApiController::class, 'download']);

        // Recruiter Actions
        Route::get('/recruiter/dashboard', [RecruiterApiController::class, 'dashboardStats']);
        Route::get('/recruiter/jobs', [RecruiterApiController::class, 'jobs']);
        Route::post('/recruiter/jobs', [RecruiterApiController::class, 'storeJob']);
        Route::get('/recruiter/applications', [RecruiterApiController::class, 'applications']);
        Route::get('/recruiter/applications/{application}', [RecruiterApiController::class, 'applicationDetails']);
        Route::post('/recruiter/applications/{application}/status', [RecruiterApiController::class, 'updateStatus']);
    });
});
