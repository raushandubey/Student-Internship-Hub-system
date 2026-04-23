<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecruiterMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and has recruiter role
        if (!auth()->check() || auth()->user()->role !== 'recruiter') {
            abort(403, 'Unauthorized access. Recruiter only.');
        }

        return $next($request);
    }
}
