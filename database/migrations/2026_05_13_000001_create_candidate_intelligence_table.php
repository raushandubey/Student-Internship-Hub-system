<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ATS Intelligence Analytics Table
     *
     * Stores per-candidate ATS intelligence for:
     *   - Recruiter-facing evaluation indicators
     *   - Candidate ranking engine inputs
     *   - Analytics history for ML foundation
     *   - Optimization improvement tracking
     */
    public function up(): void
    {
        Schema::create('candidate_intelligence', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('internship_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained()->onDelete('set null');

            // ── Core ATS Scores ──────────────────────────────────────
            $table->unsignedTinyInteger('overall_ats_score')->default(0);
            $table->unsignedTinyInteger('skill_match_score')->default(0);
            $table->unsignedTinyInteger('keyword_score')->default(0);
            $table->unsignedTinyInteger('experience_score')->default(0);
            $table->unsignedTinyInteger('project_score')->default(0);
            $table->unsignedTinyInteger('technical_depth_score')->default(0);
            $table->unsignedTinyInteger('format_score')->default(0);

            // ── Recruiter Trust Indicators ────────────────────────────
            $table->string('role_category', 30)->nullable();           // backend, frontend, etc.
            $table->string('backend_match', 20)->default('Unknown');   // Strong/Medium/Weak/None
            $table->string('frontend_match', 20)->default('Unknown');
            $table->string('project_quality', 20)->default('Unknown');
            $table->string('technical_depth', 20)->default('Unknown');
            $table->string('recruiter_readability', 20)->default('Unknown');
            $table->string('experience_level', 20)->default('entry');  // entry/junior/mid/senior

            // ── Skill Analysis ────────────────────────────────────────
            $table->json('matching_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->json('critical_missing_skills')->nullable();
            $table->json('optional_missing_skills')->nullable();

            // ── Weakness Report ───────────────────────────────────────
            $table->json('weak_areas')->nullable();
            $table->json('recommendations')->nullable();
            $table->boolean('summary_weak')->default(false);
            $table->unsignedTinyInteger('weak_bullet_count')->default(0);

            // ── Ranking ───────────────────────────────────────────────
            $table->unsignedSmallInteger('rank_position')->nullable();   // rank among all applicants
            $table->unsignedSmallInteger('total_candidates')->nullable(); // total for this internship
            $table->string('rank_tier', 20)->default('unranked');        // top10/top25/average/below

            // ── Optimization History ──────────────────────────────────
            $table->unsignedTinyInteger('before_score')->nullable();
            $table->unsignedTinyInteger('after_score')->nullable();
            $table->boolean('ai_optimized')->default(false);

            // ── Analytics Flags ───────────────────────────────────────
            $table->boolean('recruiter_viewed')->default(false);
            $table->timestamp('recruiter_viewed_at')->nullable();
            $table->boolean('shortlisted')->default(false);
            $table->timestamp('shortlisted_at')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────
            $table->unique(['user_id', 'internship_id'], 'ci_user_internship_unique');
            $table->index('overall_ats_score');
            $table->index('role_category');
            $table->index('rank_position');
            $table->index(['internship_id', 'overall_ats_score']);
        });

        // ATS Analytics Events — track recruiter interactions for ML foundation
        Schema::create('ats_analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('internship_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type', 50);  // score_computed, rewrite_triggered, recruiter_viewed, shortlisted, etc.
            $table->json('event_data')->nullable();
            $table->string('actor_role', 20)->default('student'); // student, recruiter, system
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['user_id', 'event_type']);
            $table->index(['internship_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ats_analytics_events');
        Schema::dropIfExists('candidate_intelligence');
    }
};
