<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('contracts_info', function (Blueprint $table) {
			$table->unsignedBigInteger('contract_id')->nullable();
			$table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
			
			$table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('contracts_info');
    }
};