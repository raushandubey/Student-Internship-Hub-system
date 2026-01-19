<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email Logs Migration
 * 
 * Stores all email notifications for audit and debugging.
 * Since we use log driver (no real SMTP), this provides visibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('email_type'); // application_submitted, status_updated, etc.
            $table->string('subject');
            $table->string('recipient');
            $table->text('body');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'email_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
