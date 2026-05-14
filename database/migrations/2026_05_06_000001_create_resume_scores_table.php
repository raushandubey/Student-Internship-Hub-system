<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resume Scores Table
 *
 * Stores AI-computed match scores between a user's resume and a specific job.
 * Keeps both the raw score breakdown and aggregate percentage for gate logic.
 *
 * NO existing tables are modified by this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_scores', function (Blueprint $table) {
            $table->id();

            // Foreign keys — profiles and internships already exist
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('internship_id');

            // Aggregate scores (0–100)
            $table->unsignedTinyInteger('overall_score')->default(0);   // 0-100
            $table->unsignedTinyInteger('skill_match_score')->default(0);
            $table->unsignedTinyInteger('keyword_score')->default(0);
            $table->unsignedTinyInteger('format_score')->default(0);

            // Detailed JSON breakdown
            $table->json('matching_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->json('issues')->nullable();          // e.g. ["No measurable impact","Weak keywords"]
            $table->json('strengths')->nullable();

            // Gate classification: low | medium | high
            $table->string('match_tier', 10)->default('low');

            // Version tracking — links to resume_versions
            $table->unsignedBigInteger('resume_version_id')->nullable();

            $table->timestamps();

            // Allow quick look-ups for a user+job combo
            $table->index(['user_id', 'internship_id']);
            $table->index('user_id');

            // Soft FK constraints (no cascade needed — jobs can be deactivated)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('internship_id')->references('id')->on('internships')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_scores');
    }
};
