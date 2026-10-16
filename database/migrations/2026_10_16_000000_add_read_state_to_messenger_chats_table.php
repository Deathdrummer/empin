<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Последнее прочитанное сообщение каждого участника: из него считаются непрочитанные
     * и статус «прочитано» у отправленных сообщений.
     */
    public function up(): void
    {
        Schema::table('messenger_chats', function (Blueprint $table) {
            $table->unsignedBigInteger('participant_1_last_read_id')->nullable()->after('participant_2_id');
            $table->unsignedBigInteger('participant_2_last_read_id')->nullable()->after('participant_1_last_read_id');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_chats', function (Blueprint $table) {
            $table->dropColumn(['participant_1_last_read_id', 'participant_2_last_read_id']);
        });
    }
};
