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
     * Safe to run on existing databases - preserves all data.
     */
    public function up(): void
    {
        // Fix users.role column if it exists as ENUM
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('student')->change();
            });
        }

        // Fix applications.status column if it exists as ENUM
        if (Schema::hasTable('applications') && Schema::hasColumn('applications', 'status')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->string('status', 30)->default('pending')->change();
            });
            
            // Add index for performance if not exists
            if (!$this->indexExists('applications', 'applications_status_index')) {
                Schema::table('applications', function (Blueprint $table) {
                    $table->index('status');
                });
            }
        }

        // Fix recruiter_profiles.approval_status column if it exists as ENUM
        if (Schema::hasTable('recruiter_profiles') && Schema::hasColumn('recruiter_profiles', 'approval_status')) {
            Schema::table('recruiter_profiles', function (Blueprint $table) {
                $table->string('approval_status', 20)->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     * 
     * No rollback - string type is more flexible and database-agnostic than ENUM.
     */
    public function down(): void
    {
        // No rollback needed - string columns are compatible with all databases
        // and more flexible than ENUM columns
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();
        $indexes = $schemaManager->listTableIndexes($table);
        
        return isset($indexes[$index]);
    }
};
