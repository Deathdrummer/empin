<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Удержание звонка каждой стороной: звонок по сотовой связи или из другого мессенджера, второй звонок ЭМПИН.
 * Собеседник видит «на удержании» и не завершает разговор, когда удержавший выходит из голосового канала.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('messenger_calls', function (Blueprint $table) {
            $table->boolean('caller_on_hold')->default(false);
            $table->boolean('callee_on_hold')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('messenger_calls', function (Blueprint $table) {
            $table->dropColumn(['caller_on_hold', 'callee_on_hold']);
        });
    }
};
