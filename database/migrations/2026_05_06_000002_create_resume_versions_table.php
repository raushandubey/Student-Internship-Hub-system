<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resume Versions Table
 *
 * Stores AI-rewritten resume snapshots so we can show BEFORE/AFTER comparison.
 * Original resume is always version 1; AI-rewritten copies increment from there.
 *
 * NO existing tables are modified by this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_versions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('internship_id')->nullable(); // null = generic improvement

            // Version numbering per user (1 = original, 2+ = AI rewrites)
            $table->unsignedSmallInteger('version_number')->default(1);

            // Label shown in UI: "Original", "AI Optimized – v2", etc.
            $table->string('label', 100)->default('Original');

            // The actual resume content (plain text extracted from PDF / AI output)
            $table->longText('content');

            // File path if we store a new PDF on disk (nullable — only for actual files)
            $table->string('file_path', 500)->nullable();

            // Score at the time this version was evaluated
            $table->unsignedTinyInteger('score_at_creation')->nullable();

            // Type: original | ai_rewrite
            $table->string('type', 20)->default('original');

            $table->timestamps();

            $table->index(['user_id', 'internship_id']);
            $table->index('user_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_versions');
    }
};
