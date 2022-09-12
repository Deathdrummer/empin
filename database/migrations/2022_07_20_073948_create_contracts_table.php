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
            $table->id();
			$table->string('object_number', 6)->unsigned()->unique()->default(null)->comment('Номер объекта');
            $table->string('title')->nullable()->comment('Название/заявитель');
            $table->text('titul')->nullable()->comment('Титул');
            $table->string('contract', 30)->nullable()->comment('Номер договора');
            $table->integer('subcontracting')->unsigned()->default(0)->comment('Субподряд');
            $table->integer('customer')->nullable()->comment('Заказчик');
            $table->string('locality')->nullable()->comment('Населенный пункт');
            $table->decimal('price', 10, 2)->nullable()->default(0)->comment('Стоимость договора');
			$table->timestamp('date_start')->nullable()->comment('Дата начала договора');
			$table->timestamp('date_end')->nullable()->comment('Дата окончания договора');
            $table->boolean('hoz_method')->default(false)->comment('Хоз способ');
            $table->integer('type')->nullable()->comment('Тип договора');
            $table->integer('contractor')->nullable()->comment('Исполнтель');
            $table->integer('archive')->nullable()->comment('В архиве');
            $table->unsignedInteger('deadline_color_key')->nullable()->default(null)->comment('Принудительное присвоение цвета дедлайна (передается позиция цвета из настроек)');
            $table->unsignedInteger('is_new')->default(1)->comment('Пометка договора как нового');
			$table->bigInteger('_sort')->default(0);
			$table->unsignedInteger('last_id')->default(null)->comment('Запоминание последнего ID для формирования номера объкта нового договора');
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