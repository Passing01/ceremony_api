<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->integer('template_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreign('template_id')
                  ->references('id')
                  ->on('templates')
                  ->onDelete('set null');
        });
    }
};