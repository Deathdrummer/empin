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
        Schema::create('admin_sections', function (Blueprint $table) {
            $table->id();
			$table->string('section', 100)->nullable();
			$table->longText('title')->nullable();
			$table->longText('page_title')->nullable();
			$table->integer('parent_id')->default(0);
			$table->boolean('nav')->default(false);
			$table->boolean('visible')->default(false);
			$table->bigInteger('_sort')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('admin_sections');
    }
};