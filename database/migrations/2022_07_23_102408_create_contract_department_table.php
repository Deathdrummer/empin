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
        Schema::create('contract_department', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable();
			$table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
			
			$table->unsignedBigInteger('department_id')->nullable();
			$table->longText('steps')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('contract_department');
    }
};