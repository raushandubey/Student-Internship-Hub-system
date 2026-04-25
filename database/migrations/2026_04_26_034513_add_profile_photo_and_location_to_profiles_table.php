<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add profile_photo and location to profiles table.
     * Both nullable — safe for existing rows.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Profile photo path (stored on public disk)
            $table->string('profile_photo')->nullable()->after('name');
            // User's city / address for location-based recommendations
            $table->string('location', 255)->nullable()->after('profile_photo');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['profile_photo', 'location']);
        });
    }
};
