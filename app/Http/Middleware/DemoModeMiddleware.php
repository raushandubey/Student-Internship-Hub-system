<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DemoModeMiddleware
 * 
 * Phase 10: Demo Readiness
 * 
 * Prevents write operations when demo mode is enabled.
 * Protects data during viva/interview demonstrations.
 * 
 * Usage: Apply to routes that modify data
 */
class DemoModeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if demo mode is enabled
        if (config('features.demo_mode', false)) {
            // Block all write operations (POST, PUT, PATCH, DELETE)
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                // Return user-friendly message
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => config('features.demo_mode_message'),
                        'demo_mode' => true
                    ], 403);
                }

                return back()->with('warning', config('features.demo_mode_message'));
            }
        }

        return $next($request);
    }
}
