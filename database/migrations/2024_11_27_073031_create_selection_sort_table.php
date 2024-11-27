<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('selection_sort', function (Blueprint $table) {
            $table->foreignId('account_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade')->comment('ID пользователя');
			$table->foreignId('selection_id')->references('id')->on('contracts_selections')->onUpdate('cascade')->onDelete('cascade');
			$table->unsignedBigInteger('sort');
			
			$table->unique(['account_id', 'selection_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('selection_sort');
    }
};
