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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
			$table->string('name')->nullable();
			$table->unsignedBigInteger('assigned_primary')->default(0)->comment('Назначение ответственного: 1 - админ, 2 - сайт');
			$table->unsignedBigInteger('sort')->default(0);
			$table->unsignedBigInteger('_sort')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('departments');
    }
};