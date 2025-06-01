<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('timesheet_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('timesheet_teams')->onDelete('cascade');
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->boolean('done')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('timesheet_contracts');
    }
};