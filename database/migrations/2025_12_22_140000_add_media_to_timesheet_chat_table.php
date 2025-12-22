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
            // Добавляем поле для хранения информации о прикрепленном медиа
            // Формат JSON: {"path": "/uploads/timesheet/comments/file.jpg", "type": "image", "mime_type": "image/jpeg", "size": 123456, "filename": "photo.jpg"}
            // NULL означает, что медиа не прикреплено
            $table->json('media')->nullable()->after('reactions');
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
