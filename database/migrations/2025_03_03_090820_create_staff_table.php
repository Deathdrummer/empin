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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
			$table->string('fname')->nullable()->comment('Имя');
			$table->string('mname')->nullable()->comment('Отчество');
			$table->string('sname')->nullable()->comment('Фамилия');
			$table->string('work_post')->nullable()->comment('Должность');
            $table->string('passport_series')->nullable()->comment('Серия паспорта');
            $table->string('passport_number')->nullable()->comment('Номер паспорта');
            $table->string('passport_date')->nullable()->comment('Дата выдачи поспорта');
            $table->text('passport_from')->nullable()->comment('Кем выдан поспорт');
            $table->text('birth_place')->nullable()->comment('Место рождения');
            $table->string('post_index')->nullable()->comment('Почтовый индекс');
            $table->text('registration_address')->nullable()->comment('Адрес регистрации');
			$table->boolean('working')->default(true)->comment('Работает');
            $table->unsignedBigInteger('_sort')->default(0)->comment('Сортировка');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('staff');
    }
};
