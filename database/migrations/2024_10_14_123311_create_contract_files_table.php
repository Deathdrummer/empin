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
        Schema::create('contract_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename_orig')->nullable()->default(null)->comment('Имя файла оригинальное');
            $table->string('filename_sys')->nullable()->default(null)->comment('Имя файла в директории');
			$table->boolean('is_image')->default(false)->comment('Тип файла картинка');
			$table->bigInteger('contract_id')->nullable()->default(null)->comment('ID договора');
			$table->bigInteger('size')->nullable()->default(null)->comment('Размер файла');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('contract_files');
    }
};