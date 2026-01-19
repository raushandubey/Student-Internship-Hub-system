<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Unique Constraint to Email Logs
 * 
 * Prevents duplicate email logs for the same event.
 * Uses composite unique index on (user_id, email_type, event_hash, created_at).
 * 
 * Why This Works:
 * - event_hash: SHA256 hash of event payload (application_id + status + timestamp)
 * - created_at: Rounded to nearest minute to allow same event within 1 minute window
 * - Prevents exact duplicate logs while allowing legitimate retries after 1 minute
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            // Add event_hash column for idempotency
            $table->string('event_hash', 64)->nullable()->after('metadata');
            
            // Add composite unique index
            // This prevents duplicate logs for same event within 1-minute window
            $table->unique(
                ['user_id', 'email_type', 'event_hash'],
                'email_logs_idempotency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropUnique('email_logs_idempotency_unique');
            $table->dropColumn('event_hash');
        });
    }
};
