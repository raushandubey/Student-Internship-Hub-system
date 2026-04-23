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
        // Modify the role enum to include 'recruiter'
        // Use database-agnostic approach for testing compatibility
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN or ENUM
            // For SQLite, we'll use a string column which is already created
            // No action needed as SQLite treats ENUM as TEXT
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'recruiter') DEFAULT 'student'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN or ENUM
            // No action needed
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin') DEFAULT 'student'");
        }
    }
};
