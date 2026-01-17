<?php

namespace App\Exceptions;

use Exception;

/**
 * UnauthorizedActionException
 * 
 * Thrown when a user attempts an action they don't have permission for.
 * 
 * Example: Student trying to access admin analytics, or modifying another user's application.
 * 
 * WHY THIS EXISTS:
 * - Separates authorization failures from authentication failures
 * - Provides audit trail for security incidents
 * - Returns consistent 403 Forbidden responses
 */
class UnauthorizedActionException extends Exception
{
    protected $code = 403; // Forbidden

    public function __construct(string $action, string $resource = '')
    {
        $message = "Unauthorized: You do not have permission to {$action}";
        if ($resource) {
            $message .= " on {$resource}";
        }
        
        parent::__construct($message, $this->code);
    }
}
