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
        // Ensure role column is string type (PostgreSQL-compatible)
        // This migration is idempotent - safe to run multiple times
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep as string - no reversion needed
        // String type is more flexible and database-agnostic
    }
};
