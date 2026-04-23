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
        // Change role from enum to string to avoid truncation warnings
        // Use database-agnostic approach for testing compatibility
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN, so we skip this for SQLite
            // The role column is already created as string in SQLite
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) DEFAULT 'student'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to enum
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN, so we skip this for SQLite
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'recruiter') DEFAULT 'student'");
        }
    }
};
