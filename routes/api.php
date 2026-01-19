<?php

use App\Http\Controllers\Api\V1\ApplicationApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/**
 * API v1 Routes
 * 
 * Versioned API for future mobile app support.
 * Currently uses session auth for demonstration.
 * 
 * For production mobile app:
 * - Implement Laravel Sanctum token auth
 * - Add rate limiting
 * - Add API documentation (OpenAPI/Swagger)
 */
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Applications
    Route::get('/applications', [ApplicationApiController::class, 'index']);
    Route::get('/applications/stats', [ApplicationApiController::class, 'stats']);
    Route::get('/applications/{application}', [ApplicationApiController::class, 'show']);
    Route::get('/applications/{application}/history', [ApplicationApiController::class, 'history']);
});
