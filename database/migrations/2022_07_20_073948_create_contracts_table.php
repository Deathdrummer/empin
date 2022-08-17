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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id()->comment('Номер объекта');
            $table->string('title')->nullable()->comment('Название/заявитель');
            $table->string('titul')->nullable()->comment('Титул');
            $table->string('contract', 30)->nullable()->comment('Номер договора');
            $table->integer('subcontracting')->unsigned()->default(0)->comment('Субподряд');
            $table->integer('customer')->nullable()->comment('Заказчик');
            $table->integer('locality')->nullable()->comment('Населенный пункт');
            $table->decimal('price', 10, 2)->default(0)->comment('Стоимость договора');
			$table->timestamp('date_start')->nullable()->comment('Дата начала договора');
			$table->timestamp('date_end')->nullable()->comment('Дата окончания договора');
            $table->boolean('hoz_method')->default(false)->comment('Хоз способ');
            $table->integer('type')->nullable()->comment('Тип договора');
            $table->integer('contractor')->nullable()->comment('Исполнтель');
            $table->integer('archive')->nullable()->comment('В архиве');
			$table->bigInteger('_sort')->default(0);
			$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('contracts');
    }
};