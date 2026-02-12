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
        Schema::create('messenger_calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caller_id');
            $table->unsignedBigInteger('callee_id');
            $table->enum('call_type', ['audio', 'video'])->default('audio');
            $table->enum('status', ['initiated', 'ringing', 'active', 'completed', 'rejected', 'missed', 'cancelled']);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->tinyInteger('quality_rating')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->timestamps();

            // Индексы
            $table->index('caller_id');
            $table->index('callee_id');
            $table->index('status');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('caller_id')
                ->references('id')
                ->on('staff')
                ->onDelete('cascade');
            $table->foreign('callee_id')
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
        Schema::dropIfExists('messenger_calls');
    }
};
