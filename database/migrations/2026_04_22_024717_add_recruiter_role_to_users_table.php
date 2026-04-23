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
        // PostgreSQL-compatible: No action needed since string already supports all values
        // This migration is a no-op for PostgreSQL - string columns don't need modification
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed - string column remains
    }
};
