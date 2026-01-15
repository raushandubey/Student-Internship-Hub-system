<?php
// database/migrations/2025_09_15_000002_add_admin_fields_to_internships_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('internships', function (Blueprint $table) {
        if (!Schema::hasColumn('internships', 'is_active')) {
            $table->boolean('is_active')->default(true)->after('description');
        }
        if (!Schema::hasColumn('internships', 'is_featured')) {
            $table->boolean('is_featured')->default(false)->after('is_active');
        }
        if (!Schema::hasColumn('internships', 'application_deadline')) {
            $table->date('application_deadline')->nullable()->after('is_featured');
        }
        if (!Schema::hasColumn('internships', 'stipend')) {
            $table->string('stipend')->nullable()->after('application_deadline');
        }
        if (!Schema::hasColumn('internships', 'requirements')) {
            $table->text('requirements')->nullable()->after('stipend');
        }
        if (!Schema::hasColumn('internships', 'views_count')) {
            $table->integer('views_count')->default(0)->after('requirements');
        }
        if (!Schema::hasColumn('internships', 'applications_count')) {
            $table->integer('applications_count')->default(0)->after('views_count');
        }
        if (!Schema::hasColumn('internships', 'created_by')) {
            $table->unsignedBigInteger('created_by')->nullable()->after('applications_count');
        }
    });
}

    public function down()
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn([
                'is_active', 'is_featured', 'application_deadline', 
                'stipend', 'requirements', 'views_count', 
                'applications_count', 'created_by'
            ]);
        });
    }
};
// 