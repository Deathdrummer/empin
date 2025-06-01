<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('timesheet_chat', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->foreignId('team_id')->constrained('timesheet_teams')->onDelete('cascade');
            $table->foreignId('timesheet_contract_id')->constrained('timesheet_contracts')->onDelete('cascade');
            $table->foreignId('from_id')->constrained('staff')->onDelete('cascade');
			$table->longText('message')->comment('Сообщение');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('timesheet_chat');
    }
};