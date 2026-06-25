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
            // Добавляем поле для хранения реакций (эмодзи) в формате JSON
            // Формат: [{"user_id": 1, "emoji": "👍"}, {"user_id": 2, "emoji": "❤️"}]
            $table->json('reactions')->nullable()->after('message');

            // Добавляем поле для ответов на комментарии (только один уровень вложенности)
            // NULL означает, что это обычный комментарий, не ответ
            $table->bigInteger('reply_to_id')->unsigned()->nullable()->after('reactions');

            // Внешний ключ на саму таблицу для организации ответов
            $table->foreign('reply_to_id')
                  ->references('id')
                  ->on('timesheet_chat')
                  ->onDelete('cascade');
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
            // Удаляем внешний ключ
            $table->dropForeign(['reply_to_id']);

            // Удаляем поля
            $table->dropColumn(['reactions', 'reply_to_id']);
        });
    }
};
