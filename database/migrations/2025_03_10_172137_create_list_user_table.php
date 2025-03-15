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
        Schema::create('list_user', function (Blueprint $table) {
			$table->foreignId('staff_id')->references('id')->on('staff')->onDelete('cascade');
			$table->unsignedBigInteger('list_id');
			$table->primary(['staff_id', 'list_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('list_user');
    }
};
