<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            // Add recruiter_id foreign key column
            $table->foreignId('recruiter_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Add index for query performance
            $table->index('recruiter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropForeign(['recruiter_id']);
            $table->dropIndex(['recruiter_id']);
            $table->dropColumn('recruiter_id');
        });
    }
};
