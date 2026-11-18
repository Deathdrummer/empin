<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Переписка задания без связи (ИС-ЗД-16): телефон повторяет отправку, пока не получит ответ */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('timesheet_chat', 'client_uuid')) {
            Schema::table('timesheet_chat', function (Blueprint $table) {
                $table->string('client_uuid', 36)->nullable()->after('from_id');
                $table->unique(['from_id', 'client_uuid'], 'timesheet_chat_from_client_uuid_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('timesheet_chat', 'client_uuid')) {
            Schema::table('timesheet_chat', function (Blueprint $table) {
                $table->dropUnique('timesheet_chat_from_client_uuid_unique');
                $table->dropColumn('client_uuid');
            });
        }
    }
};
