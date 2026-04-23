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
     * This migration ensures applications are automatically deleted when their
     * associated internship is deleted, preventing orphaned records.
     */
    public function up(): void
    {
        // Clean up orphaned applications first (applications without internships)
        $orphanedCount = DB::table('applications')
            ->whereNotIn('internship_id', function($query) {
                $query->select('id')->from('internships');
            })
            ->count();
            
        if ($orphanedCount > 0) {
            \Log::warning("Cleaning up {$orphanedCount} orphaned applications");
            
            DB::table('applications')
                ->whereNotIn('internship_id', function($query) {
                    $query->select('id')->from('internships');
                })
                ->delete();
        }
        
        // Drop existing foreign key constraint
        Schema::table('applications', function (Blueprint $table) {
            // PostgreSQL syntax for dropping foreign key
            $table->dropForeign(['internship_id']);
        });
        
        // Re-add foreign key with CASCADE delete
        Schema::table('applications', function (Blueprint $table) {
            $table->foreign('internship_id')
                  ->references('id')
                  ->on('internships')
                  ->onDelete('cascade');
        });
        
        \Log::info('CASCADE delete constraint added to applications.internship_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop CASCADE constraint
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['internship_id']);
        });
        
        // Re-add without CASCADE (default behavior)
        Schema::table('applications', function (Blueprint $table) {
            $table->foreign('internship_id')
                  ->references('id')
                  ->on('internships');
        });
    }
};
