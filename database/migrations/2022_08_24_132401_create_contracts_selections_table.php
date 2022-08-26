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
        Schema::create('contracts_selections', function (Blueprint $table) {
            $table->id();
			
			$table->unsignedBigInteger('account_id')->comment('ID пользователя');
			$table->foreign('account_id')->references('id')->on('users')->onDelete('cascade');
			
			$table->string('title');
			$table->unsignedBigInteger('_sort')->default(0);
        });
		
		
		Schema::create('contract_selection_contract', function (Blueprint $table) {
            $table->unsignedBigInteger('selection_id')->nullable();
			$table->foreign('selection_id')->references('id')->on('contracts_selections')->onDelete('cascade');
			
			$table->unsignedBigInteger('contract_id')->nullable();
			$table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
        });
    }




    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('contracts_selections');
        Schema::dropIfExists('contract_selection_contract');
    }
};