<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Пересланные сообщения: «Переслано от …» — автор оригинала (при пересылке пересланного — первый автор).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->json('forwarded_from')->nullable()->after('mentions');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->dropColumn('forwarded_from');
        });
    }
};
