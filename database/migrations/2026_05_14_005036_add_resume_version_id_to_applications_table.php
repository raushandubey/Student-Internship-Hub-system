<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add resume_version_id to applications so recruiters see
     * the AI-optimized resume version submitted, not just the raw upload.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedBigInteger('resume_version_id')->nullable()->after('match_score');
            $table->foreign('resume_version_id')
                  ->references('id')->on('resume_versions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['resume_version_id']);
            $table->dropColumn('resume_version_id');
        });
    }
};
