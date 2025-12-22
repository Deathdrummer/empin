<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('timesheet_chat', function (Blueprint $table) {
            $table->json('media')->nullable()->after('message')->comment('Медиа вложение (изображение или видео)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('timesheet_chat', function (Blueprint $table) {
            $table->dropColumn('media');
        });
    }
};
