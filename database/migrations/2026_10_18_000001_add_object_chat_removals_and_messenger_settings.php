<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Чаты объектов: участников добавляет табель (мастера бригад, которым поставлен объект),
 * а убранный вручную не возвращается, пока его не назначат на объект снова.
 * Настройки мессенджера сотрудника: «чаты объектов без звука».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_chat_removals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('messenger_chats')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedBigInteger('removed_by')->nullable();
            $table->timestamps();
            $table->unique(['chat_id', 'staff_id']);
        });

        Schema::create('messenger_settings', function (Blueprint $table) {
            $table->foreignId('staff_id')->primary()->constrained('staff')->cascadeOnDelete();
            $table->boolean('mute_objects')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_settings');
        Schema::dropIfExists('messenger_chat_removals');
    }
};
