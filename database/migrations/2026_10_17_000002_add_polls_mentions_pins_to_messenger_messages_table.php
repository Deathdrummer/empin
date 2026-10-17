<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Сообщения мессенджера: опросы, упоминания, системные сообщения группы, закреплённые сообщения.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_messages', function (Blueprint $table) {
            // text | poll | system
            $table->string('type', 16)->default('text')->after('from_id');
            $table->json('poll')->nullable()->after('media');
            $table->json('mentions')->nullable()->after('poll');
            $table->timestamp('pinned_at')->nullable()->after('mentions');
            $table->unsignedBigInteger('pinned_by')->nullable()->after('pinned_at');
            $table->index(['chat_id', 'pinned_at']);
        });

        Schema::create('messenger_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedTinyInteger('option_index');
            $table->timestamps();

            $table->unique(['message_id', 'staff_id', 'option_index']);
            $table->foreign('message_id')->references('id')->on('messenger_messages')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });

        // Закреплённые комментарии в чате объекта
        if (Schema::hasTable('timesheet_chat')) {
            Schema::table('timesheet_chat', function (Blueprint $table) {
                $table->timestamp('pinned_at')->nullable();
                $table->unsignedBigInteger('pinned_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('timesheet_chat')) {
            Schema::table('timesheet_chat', function (Blueprint $table) {
                $table->dropColumn(['pinned_at', 'pinned_by']);
            });
        }

        Schema::dropIfExists('messenger_poll_votes');

        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->dropIndex(['chat_id', 'pinned_at']);
            $table->dropColumn(['type', 'poll', 'mentions', 'pinned_at', 'pinned_by']);
        });
    }
};
