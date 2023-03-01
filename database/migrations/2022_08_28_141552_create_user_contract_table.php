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
        Schema::create('user_contract', function (Blueprint $table) {
			$table->unsignedBigInteger('account_id')->nullable();
			$table->foreign('account_id')->references('id')->on('users')->onDelete('cascade');
			
			$table->unsignedBigInteger('contract_id')->nullable();
			$table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
			
			$table->boolean('viewed')->default(false)->comment('Договор просмотрен');
			$table->boolean('pinned')->default(false)->comment('Договор закреплен');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_contract');
    }
};