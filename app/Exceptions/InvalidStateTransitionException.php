<?php

namespace App\Exceptions;

use Exception;

/**
 * InvalidStateTransitionException
 * 
 * Thrown when an application status transition violates the state machine rules.
 * 
 * Example: Trying to move from 'pending' directly to 'approved' without going through review stages.
 * 
 * WHY THIS EXISTS:
 * - Prevents data corruption from invalid state changes
 * - Provides clear error messages for debugging
 * - Allows centralized handling of state machine violations
 */
class InvalidStateTransitionException extends Exception
{
    protected $code = 422; // Unprocessable Entity

    public function __construct(string $from, string $to, array $allowed = [])
    {
        $allowedStr = !empty($allowed) ? implode(', ', $allowed) : 'none';
        $message = "Invalid state transition from '{$from}' to '{$to}'. Allowed transitions: {$allowedStr}";
        
        parent::__construct($message, $this->code);
    }
}
