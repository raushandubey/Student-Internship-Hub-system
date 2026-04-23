<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration fixes ENUM columns to be PostgreSQL-compatible by converting them to VARCHAR.
     * Uses PostgreSQL-safe approach: create new column, copy data, drop old, rename new.
     * Does NOT require Doctrine DBAL or ->change() method.
     */
    public function up(): void
    {
        // Fix users.role column if it exists
        if (Schema::hasColumn('users', 'role')) {
            $this->convertColumnToString('users', 'role', 20, 'student');
        }

        // Fix applications.status column if it exists
        if (Schema::hasTable('applications') && Schema::hasColumn('applications', 'status')) {
            $this->convertColumnToString('applications', 'status', 30, 'pending');
            
            // Add index for performance
            $this->addIndexIfNotExists('applications', 'status');
        }

        // Fix recruiter_profiles.approval_status column if it exists
        if (Schema::hasTable('recruiter_profiles') && Schema::hasColumn('recruiter_profiles', 'approval_status')) {
            $this->convertColumnToString('recruiter_profiles', 'approval_status', 20, 'pending');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback - string columns are more flexible and database-agnostic
    }

    /**
     * Convert a column to string type without using ->change() or Doctrine DBAL.
     * PostgreSQL-safe approach: create temp column, copy data, drop old, rename temp.
     */
    private function convertColumnToString(string $table, string $column, int $length, string $default): void
    {
        $tempColumn = $column . '_temp';
        
        // Step 1: Create new temporary string column
        Schema::table($table, function (Blueprint $table) use ($tempColumn, $length, $default) {
            $table->string($tempColumn, $length)->default($default)->nullable();
        });

        // Step 2: Copy data from old column to new column
        DB::statement("UPDATE {$table} SET {$tempColumn} = {$column}::text");

        // Step 3: Drop old column
        Schema::table($table, function (Blueprint $table) use ($column) {
            $table->dropColumn($column);
        });

        // Step 4: Rename temporary column to original name
        Schema::table($table, function (Blueprint $table) use ($tempColumn, $column) {
            $table->renameColumn($tempColumn, $column);
        });

        // Step 5: Set NOT NULL constraint and default value using raw SQL
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET NOT NULL");
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT '{$default}'");
    }

    /**
     * Add index if it doesn't exist (PostgreSQL-safe).
     */
    private function addIndexIfNotExists(string $table, string $column): void
    {
        $indexName = "{$table}_{$column}_index";
        
        // Check if index exists using PostgreSQL system catalog
        $exists = DB::select(
            "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?",
            [$table, $indexName]
        );

        if (empty($exists)) {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->index($column);
            });
        }
    }
};
