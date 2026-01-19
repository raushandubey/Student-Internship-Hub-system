<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Application Status Logs Migration
 * 
 * Purpose: Audit trail for all application status changes
 * 
 * Why this matters:
 * - Compliance: Track who changed what and when
 * - Debugging: Understand application lifecycle
 * - Analytics: Measure processing times
 * - Interview: Demonstrates understanding of audit logging
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->string('from_status')->nullable(); // null for initial creation
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('actor_type')->default('admin'); // admin, system, student
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index for efficient queries
            $table->index(['application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_logs');
    }
};
