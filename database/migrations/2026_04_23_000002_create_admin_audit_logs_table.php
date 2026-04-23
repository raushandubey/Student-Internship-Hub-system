<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Audit Logs Migration
 * 
 * Purpose: Track all admin actions on recruiter accounts for accountability
 * 
 * Why this matters:
 * - Compliance: Complete audit trail of admin moderation actions
 * - Accountability: Track which admin performed which action
 * - Security: Log IP addresses for security monitoring
 * - Debugging: Understand recruiter account lifecycle
 * - Interview: Demonstrates understanding of audit logging and security
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users')->onDelete('cascade');
            $table->string('action_type'); // approved, rejected, suspended, activated, internship_deactivated, profile_edited, unauthorized_access_attempt
            $table->foreignId('target_recruiter_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            // Indexes for efficient queries
            $table->index('target_recruiter_id'); // Filter by recruiter
            $table->index('admin_user_id'); // Filter by admin
            $table->index('action_type'); // Filter by action
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
