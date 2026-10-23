<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Аватарка сотрудника (фото из приложения) и настройка «При открытии приложения показывать чаты».
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('staff', 'avatar')) {
            Schema::table('staff', function (Blueprint $table) {
                // Путь вида /storage/avatars/… (как у вложений сообщений)
                $table->string('avatar')->nullable();
            });
        }

        Schema::table('messenger_settings', function (Blueprint $table) {
            $table->boolean('open_chats_on_start')->default(false)->after('mute_objects');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            $table->dropColumn('open_chats_on_start');
        });

        if (Schema::hasColumn('staff', 'avatar')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('avatar');
            });
        }
    }
};
