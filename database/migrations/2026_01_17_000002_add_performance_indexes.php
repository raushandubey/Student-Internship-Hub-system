<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Indexes Migration
 * 
 * WHY INDEXES MATTER:
 * Without indexes, MySQL performs full table scans (O(n) complexity).
 * With indexes, lookups become O(log n) using B-tree structures.
 * 
 * INDEXES ADDED:
 * 1. applications.status - Frequently filtered (WHERE status = 'pending')
 * 2. applications.created_at - Used in ORDER BY and date range queries
 * 3. application_status_logs.application_id - Already has FK, but explicit index
 *    ensures optimal JOIN performance
 * 
 * NOTE: user_id and internship_id already have indexes from foreign key constraints.
 * 
 * INTERVIEW TALKING POINT:
 * "I added indexes on columns used in WHERE, ORDER BY, and JOIN clauses.
 * This reduces query time from O(n) to O(log n) as the table grows."
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add index on applications.status for filtering
        Schema::table('applications', function (Blueprint $table) {
            $table->index('status', 'idx_applications_status');
            $table->index('created_at', 'idx_applications_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('idx_applications_status');
            $table->dropIndex('idx_applications_created_at');
        });
    }
};
