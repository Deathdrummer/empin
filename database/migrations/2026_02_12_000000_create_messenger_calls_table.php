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

            // Участники звонка
            $table->unsignedBigInteger('caller_id')->comment('Кто звонит');
            $table->unsignedBigInteger('callee_id')->comment('Кому звонят');

            // Тип и статус звонка
            $table->enum('call_type', ['audio', 'video'])->default('audio')->comment('Тип звонка');
            $table->enum('status', [
                'initiated',    // Звонок инициирован
                'ringing',      // Звонок звонит
                'active',       // Активный разговор
                'completed',    // Завершён успешно
                'rejected',     // Отклонён
                'missed',       // Пропущенный
                'cancelled'     // Отменён звонящим
            ])->comment('Статус звонка');

            // Временные метки
            $table->timestamp('started_at')->nullable()->comment('Время начала разговора (когда принят)');
            $table->timestamp('ended_at')->nullable()->comment('Время завершения звонка');

            // Дополнительная информация
            $table->unsignedInteger('duration')->nullable()->comment('Длительность в секундах');
            $table->unsignedTinyInteger('quality_rating')->nullable()->comment('Оценка качества 1-5');
            $table->string('session_id', 255)->nullable()->comment('VideoSDK session ID');

            $table->timestamps();

            // Индексы для быстрого поиска
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
