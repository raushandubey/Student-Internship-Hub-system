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
            $table->text('deactivation_reason')->nullable()->after('is_active');
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->after('deactivation_reason');
            $table->timestamp('deactivated_at')->nullable()->after('deactivated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropForeign(['deactivated_by']);
            $table->dropColumn(['deactivation_reason', 'deactivated_by', 'deactivated_at']);
        });
    }
};
