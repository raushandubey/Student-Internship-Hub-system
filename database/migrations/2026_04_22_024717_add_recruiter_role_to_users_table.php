<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update role column to support 'recruiter' value
        // PostgreSQL-compatible: Use Schema::table with change()
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->change();
        });
        
        // No schema change needed - string already supports all values
        // This migration ensures the column is properly typed as string
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed - string column remains
        // Data integrity maintained through application-level validation
    }
};
