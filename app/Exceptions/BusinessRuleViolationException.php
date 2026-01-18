<?php

namespace App\Exceptions;

use Exception;

/**
 * BusinessRuleViolationException
 * 
 * Thrown when a business rule is violated (not authorization or state machine).
 * 
 * Examples:
 * - Applying to the same internship twice
 * - Applying to inactive internship
 * - Cancelling an already-processed application
 * 
 * WHY THIS EXISTS:
 * - Distinguishes business logic errors from technical errors
 * - Provides user-friendly error messages
 * - Prevents invalid data from entering the system
 */
class BusinessRuleViolationException extends Exception
{
    protected $code = 422; // Unprocessable Entity

    public function __construct(string $message)
    {
        parent::__construct($message, $this->code);
    }
}
