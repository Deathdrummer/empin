<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «Удалить у меня» (как в Telegram): сообщение скрыто только у этого сотрудника, у остальных остаётся.
 * chat_id — потому что в чате объекта message_id — это id комментария табеля.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_hidden_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('message_id');
            $table->timestamp('created_at')->nullable();

            $table->unique(['staff_id', 'chat_id', 'message_id']);
            $table->index(['chat_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_hidden_messages');
    }
};
