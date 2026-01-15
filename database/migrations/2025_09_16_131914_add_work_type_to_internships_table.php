<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkTypeToInternshipsTable extends Migration
{
    public function up()
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->string('work_type')->default('On-site')->after('location');
            $table->string('category')->nullable()->after('description');
        });
    }

    public function down()
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn(['work_type', 'category']);
        });
    }
}
