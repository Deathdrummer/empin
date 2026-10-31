<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «Прочитали» и галочки: граница прочитанного, с которой участник появился в чате. Добавленному в группу
 * (или мастеру, которому табель поставил объект) граница ставится на последнее сообщение, чтобы история не
 * стала непрочитанной, — эти сообщения он не читал. null — участник с самого начала (или запись старше колонки).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_chat_members', function (Blueprint $table) {
            $table->unsignedBigInteger('joined_read_id')->nullable()->after('last_read_id');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_chat_members', function (Blueprint $table) {
            $table->dropColumn('joined_read_id');
        });
    }
};
