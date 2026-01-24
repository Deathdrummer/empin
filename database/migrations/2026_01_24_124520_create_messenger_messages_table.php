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
        Schema::create('messenger_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('from_id');
            $table->longText('message')->nullable();
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->json('reactions')->nullable();
            $table->json('media')->nullable();
            $table->timestamps();

            // Индексы
            $table->index('chat_id');

            // Foreign keys
            $table->foreign('chat_id')
                ->references('id')
                ->on('messenger_chats')
                ->onDelete('cascade');

            $table->foreign('from_id')
                ->references('id')
                ->on('staff')
                ->onDelete('cascade');

            $table->foreign('reply_to_id')
                ->references('id')
                ->on('messenger_messages')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messenger_messages');
    }
};
