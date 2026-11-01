<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messenger_calls', function (Blueprint $table) {
            $table->unsignedBigInteger('answered_access_token_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('messenger_calls', function (Blueprint $table) {
            $table->dropColumn('answered_access_token_id');
        });
    }
};
