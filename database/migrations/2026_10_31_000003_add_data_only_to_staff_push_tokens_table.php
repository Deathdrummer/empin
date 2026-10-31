<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Android-приложение, которое само показывает уведомление о сообщении (с кнопками «Ответить» / «Прочитано»):
 * ему push о сообщении уходит только данными. Иначе Firebase показывает ещё и своё уведомление — без кнопок,
 * второе на то же сообщение. Старые версии приложения флаг не присылают и получают push как раньше.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_push_tokens', function (Blueprint $table) {
            $table->boolean('data_only')->default(false)->after('token_type');
        });
    }

    public function down(): void
    {
        Schema::table('staff_push_tokens', function (Blueprint $table) {
            $table->dropColumn('data_only');
        });
    }
};
