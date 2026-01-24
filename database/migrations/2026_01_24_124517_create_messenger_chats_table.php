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
        Schema::create('messenger_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('participant_1_id');
            $table->unsignedBigInteger('participant_2_id');
            $table->timestamps();

            // Индексы
            $table->index('participant_1_id');
            $table->index('participant_2_id');
            $table->unique(['participant_1_id', 'participant_2_id'], 'unique_participants');

            // Foreign keys
            $table->foreign('participant_1_id')
                ->references('id')
                ->on('staff')
                ->onDelete('cascade');

            $table->foreign('participant_2_id')
                ->references('id')
                ->on('staff')
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
        Schema::dropIfExists('messenger_chats');
    }
};
