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
        Schema::create('steps', function (Blueprint $table) {
            $table->id();
			$table->unsignedBigInteger('department_id')->nullable();
			$table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
			
			$table->string('name');
            $table->unsignedBigInteger('type')->nullable();
            $table->unsignedBigInteger('deadline')->nullable();
			$table->bigInteger('sort')->default(0);
			$table->bigInteger('_sort')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('steps');
    }
};