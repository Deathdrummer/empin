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
        Schema::create('parsed_sites', function (Blueprint $table) {
            $table->id();
			$table->longText('company')->nullable()->default(null)->comment('Сайт');
			$table->longText('site')->nullable()->default(null)->comment('Сайт');
			$table->bigInteger('subject_id')->nullable()->default(null)->comment('ID Тематики');
			$table->string('whatsapp')->nullable()->default(null)->comment('Whatsapp');
			$table->string('telegram')->nullable()->default(null)->comment('Telegram');
			$table->string('phone')->nullable()->default(null)->comment('Телефон');
			$table->string('email')->nullable()->default(null)->comment('E-mail');
			
			$table->string('preview')->nullable()->default(null)->comment('Превью скриншот сайта');
			
			$table->boolean('valid')->nullable()->default(null)->comment('Валидный контакт');
			$table->boolean('banned')->nullable()->default(null)->comment('Невалидный контакт');
        });
		
		Schema::create('parsed_sites_subjects', function (Blueprint $table) {
            $table->id();
			$table->string('subject')->nullable()->default(null)->comment('Тематика');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('parsed_sites');
        Schema::dropIfExists('parsed_sites_subjects');
    }
};