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
        Schema::create('templates_to_export', function (Blueprint $table) {
            $table->id();
			$table->string('title')->nullable()->default(null)->comment('Название шаблона');
			$table->string('import_filename')->nullable()->default(null)->comment('Название файла, импортируемоего через адмику (генерируется системой)');
			$table->string('export_filename')->nullable()->default(null)->comment('Название экспортируемоего файла (с интерполяцией)');
			$table->enum('format', ['docx','pdf', 'txt', 'xlsx'])->nullable()->default(null)->comment('Формат загружаемого шаблона');
			$table->string('section')->nullable()->default(null)->comment('Раздел списка шаблонов');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('templates_to_export');
    }
};