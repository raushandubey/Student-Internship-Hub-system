<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // For SQLite compatibility, we'll use string instead of enum
            // SQLite will treat this as TEXT without CHECK constraint
            if (DB::getDriverName() === 'sqlite') {
                $table->string('role', 20)->default('student');
            } else {
                $table->enum('role', ['student', 'admin'])->default('student');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};