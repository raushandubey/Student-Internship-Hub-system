<?php

namespace App\Http\Middleware;

use App\Models\AdminAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and has admin role
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            // Log unauthorized access attempt (Requirement 19.4)
            $userId = auth()->check() ? auth()->id() : null;
            $route = $request->path();
            $ipAddress = $request->ip();

            Log::warning('Unauthorized admin access attempt', [
                'user_id'   => $userId,
                'route'     => $route,
                'ip'        => $ipAddress,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Store in admin_audit_logs if user is authenticated
            if ($userId) {
                try {
                    AdminAuditLog::create([
                        'admin_user_id'      => $userId,
                        'action_type'        => 'unauthorized_access_attempt',
                        'target_recruiter_id' => null,
                        'reason'             => "Attempted to access admin route: /{$route}",
                        'ip_address'         => $ipAddress,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to store unauthorized access audit log', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            abort(403, 'Unauthorized access. Admin only.');
        }

        return $next($request);
    }
}
