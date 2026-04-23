<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruiter_profiles', function (Blueprint $table) {
            // Approval workflow columns - using string instead of enum for PostgreSQL compatibility
            $table->string('approval_status', 20)
                  ->default('pending')
                  ->after('logo_path');
            
            $table->foreignId('approved_by')
                  ->nullable()
                  ->after('approval_status')
                  ->constrained('users')
                  ->onDelete('set null');
            
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('suspended_at')->nullable()->after('approved_at');
            $table->text('suspension_reason')->nullable()->after('suspended_at');
            $table->text('rejection_reason')->nullable()->after('suspension_reason');
            
            // Index for query performance
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('recruiter_profiles', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['approval_status']);
            $table->dropColumn([
                'approval_status',
                'approved_by',
                'approved_at',
                'suspended_at',
                'suspension_reason',
                'rejection_reason'
            ]);
        });
    }
};
