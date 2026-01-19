<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * EmailLog Model
 * 
 * Stores all email notifications for audit trail.
 * 
 * Idempotency Strategy:
 * - Uses event_hash (SHA256 of event payload) to prevent duplicates
 * - Composite unique index: (user_id, email_type, event_hash)
 * - createIdempotent() method handles duplicate key violations gracefully
 */
class EmailLog extends Model
{
    protected $fillable = [
        'user_id',
        'email_type',
        'subject',
        'recipient',
        'body',
        'status',
        'metadata',
        'event_hash',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create email log with idempotency protection
     * 
     * Uses event_hash to prevent duplicate logs.
     * If duplicate exists, returns existing log instead of throwing exception.
     * 
     * @param array $data
     * @return EmailLog|null
     */
    public static function createIdempotent(array $data): ?EmailLog
    {
        // Generate event hash if not provided
        if (!isset($data['event_hash'])) {
            $data['event_hash'] = self::generateEventHash($data);
        }

        try {
            // Attempt to create
            return self::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if it's a duplicate key violation (error code 23000)
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'email_logs_idempotency_unique')) {
                // Duplicate detected - return existing log
                return self::where('user_id', $data['user_id'])
                    ->where('email_type', $data['email_type'])
                    ->where('event_hash', $data['event_hash'])
                    ->first();
            }
            
            // Re-throw if it's a different error
            throw $e;
        }
    }

    /**
     * Generate deterministic hash for event
     * 
     * Hash includes:
     * - user_id
     * - email_type
     * - metadata (application_id, status, etc.)
     * - timestamp rounded to nearest minute
     * 
     * This ensures same event within 1 minute produces same hash.
     */
    public static function generateEventHash(array $data): string
    {
        $payload = [
            'user_id' => $data['user_id'],
            'email_type' => $data['email_type'],
            'metadata' => $data['metadata'] ?? [],
            // Round timestamp to nearest minute for idempotency window
            'timestamp' => now()->format('Y-m-d H:i'),
        ];

        return hash('sha256', json_encode($payload));
    }
}
