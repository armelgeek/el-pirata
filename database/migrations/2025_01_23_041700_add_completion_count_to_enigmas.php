<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('enigmas', function (Blueprint $table) {
            $table->integer('completion_count')->default(0)->after('difficulty');
        });
    }

    public function down()
    {
        Schema::table('enigmas', function (Blueprint $table) {
            $table->dropColumn('completion_count');
        });
    }
};
