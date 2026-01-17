<?php

namespace App\Exceptions;

use App\Exceptions\BusinessRuleViolationException;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\UnauthorizedActionException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Global Exception Handler
 * 
 * Phase 9: Production-Grade Error Handling
 * 
 * Responsibilities:
 * - Catch custom business exceptions
 * - Log security violations
 * - Return user-friendly error messages
 * - Prevent sensitive data leakage
 * 
 * Architecture: All exceptions bubble up here for centralized handling
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        /**
         * Handle Business Rule Violations
         * 
         * Why separate handler?
         * - Business rules are expected failures (not bugs)
         * - Should return 422 (Unprocessable Entity)
         * - User-friendly message without stack trace
         */
        $this->renderable(function (BusinessRuleViolationException $e, $request) {
            // Structured audit log
            Log::warning('Business rule violation', [
                'actor_id' => auth()->id(),
                'action' => 'business_rule_violation',
                'rule' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_type' => 'business_rule_violation'
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        });

        /**
         * Handle Invalid State Transitions
         * 
         * Why separate handler?
         * - State machine violations are critical
         * - Should return 409 (Conflict)
         * - Include allowed transitions for debugging
         */
        $this->renderable(function (InvalidStateTransitionException $e, $request) {
            // Structured audit log
            Log::warning('Invalid state transition attempted', [
                'actor_id' => auth()->id(),
                'action' => 'invalid_state_transition',
                'from_status' => $e->getFromStatus()->value,
                'to_status' => $e->getToStatus()->value,
                'allowed_transitions' => array_map(fn($s) => $s->value, $e->getAllowedTransitions()),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_type' => 'invalid_state_transition',
                    'from_status' => $e->getFromStatus()->value,
                    'to_status' => $e->getToStatus()->value,
                    'allowed_transitions' => array_map(fn($s) => $s->value, $e->getAllowedTransitions())
                ], 409);
            }

            return back()->with('error', $e->getMessage());
        });

        /**
         * Handle Unauthorized Actions
         * 
         * Why separate handler?
         * - Security violations must be logged
         * - Should return 403 (Forbidden)
         * - No sensitive data in response
         */
        $this->renderable(function (UnauthorizedActionException $e, $request) {
            // SECURITY AUDIT LOG (critical for compliance)
            Log::warning('Unauthorized action attempted', [
                'actor_id' => auth()->id(),
                'actor_role' => auth()->user()?->role,
                'action' => 'unauthorized_action',
                'attempted_action' => $e->getAction(),
                'reason' => $e->getReason(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to perform this action.',
                    'error_type' => 'unauthorized_action'
                ], 403);
            }

            return back()->with('error', 'You are not authorized to perform this action.');
        });
    }
}
