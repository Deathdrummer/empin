<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Мессенджер: кроме личных чатов — группы (бригады) и чаты объектов (комментарии табеля).
 * Состояние каждого участника (прочитано, закреплён, без звука) — в messenger_chat_members.
 */
return new class extends Migration
{
    public function up(): void
    {
        // У группы и чата объекта нет пары участников
        DB::statement('ALTER TABLE messenger_chats MODIFY participant_1_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE messenger_chats MODIFY participant_2_id BIGINT UNSIGNED NULL');

        Schema::table('messenger_chats', function (Blueprint $table) {
            $table->string('type', 16)->default('direct')->after('id');
            $table->string('title')->nullable()->after('type');
            // Чат объекта: один на договор (contracts.id)
            $table->unsignedBigInteger('contract_id')->nullable()->unique()->after('title');
            $table->unsignedBigInteger('created_by')->nullable()->after('contract_id');
            $table->index('type');
        });

        Schema::create('messenger_chat_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('staff_id');
            $table->string('role', 16)->default('member');
            $table->unsignedBigInteger('last_read_id')->nullable();
            $table->timestamp('pinned_at')->nullable();
            $table->boolean('muted')->default(false);
            $table->timestamps();

            $table->unique(['chat_id', 'staff_id']);
            $table->index('staff_id');
            $table->foreign('chat_id')->references('id')->on('messenger_chats')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });

        // UNION убирает повтор участника в существующих чатах с самим собой.
        // Сами чаты и их история сохраняются.
        DB::statement("
            INSERT INTO messenger_chat_members (chat_id, staff_id, role, created_at, updated_at)
            SELECT id, participant_1_id, 'member', NOW(), NOW() FROM messenger_chats WHERE participant_1_id IS NOT NULL
            UNION
            SELECT id, participant_2_id, 'member', NOW(), NOW() FROM messenger_chats WHERE participant_2_id IS NOT NULL
        ");

        // Переписка до обновления считается прочитанной: иначе после обновления у всех появились бы
        // сотни «непрочитанных» старых сообщений
        DB::statement('
            UPDATE messenger_chat_members m
            SET last_read_id = (SELECT MAX(id) FROM messenger_messages WHERE chat_id = m.chat_id)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_chat_members');

        DB::table('messenger_chats')->where('type', '!=', 'direct')->delete();

        Schema::table('messenger_chats', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropUnique(['contract_id']);
            $table->dropColumn(['type', 'title', 'contract_id', 'created_by']);
        });

        DB::statement('ALTER TABLE messenger_chats MODIFY participant_1_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE messenger_chats MODIFY participant_2_id BIGINT UNSIGNED NOT NULL');
    }
};
