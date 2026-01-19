<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add match_score to applications table
 * 
 * Stores the skill match percentage at time of application.
 * This is calculated by MatchingService and stored for:
 * - Historical accuracy (skills may change later)
 * - Analytics and reporting
 * - Sorting applications by relevance
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->decimal('match_score', 5, 2)->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('match_score');
        });
    }
};
