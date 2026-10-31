<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Архив чатов, как в Telegram: чат убран из основного списка у этого сотрудника. Чат со звуком возвращается
 * из архива при новом сообщении, без звука — остаётся в архиве.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_chat_members', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('pinned_at');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_chat_members', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
