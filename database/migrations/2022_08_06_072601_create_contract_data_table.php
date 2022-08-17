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
        Schema::create('contract_data', function (Blueprint $table) {
			$table->id();
			
			$table->unsignedBigInteger('contract_id')->nullable();
			$table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
			
			$table->unsignedBigInteger('department_id')->nullable();
			$table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
			
			$table->unsignedBigInteger('step_id')->nullable();
			$table->foreign('step_id')->references('id')->on('steps')->onDelete('cascade');
			
			$table->unsignedBigInteger('type')->comment('Тип данных. 1: чекбокс, 2: текст, 3: выпадающий список');

			$table->longText('data')->nullable()->comment('Данные');
			
			$table->integer('from_id')->nullable()->comment('Кто внес данные. Польжительное значение - сотрудник, отрицательное - админ');
			
			$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('contract_data');
    }
};