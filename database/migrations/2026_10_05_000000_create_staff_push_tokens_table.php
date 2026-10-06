<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('installation_id', 100)->nullable();
            $table->string('token', 255)->unique();
            $table->string('platform', 20);
            $table->string('token_type', 20);
            $table->timestamps();

            $table->unique(
                ['staff_id', 'installation_id', 'token_type'],
                'staff_push_installation_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_push_tokens');
    }
};
