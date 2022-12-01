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
		Schema::table('contracts', function (Blueprint $table) {
			$table->timestamp('date_close')->nullable()->comment('Дата закрытия договора');
			$table->decimal('price_nds', 10, 2)->nullable()->default(0)->comment('Стоимость договора с НДС');
			$table->timestamp('date_buy')->nullable()->comment('Дата закупки');
			$table->string('buy_number', 30)->unique()->default(null)->comment('Номер закупки');
			$table->string('archive_dir', 255)->unique()->default(null)->comment('Архивная папка');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('contracts', function (Blueprint $table) {
			$table->dropColumn('date_close');
			$table->dropColumn('price_nds');
			$table->dropColumn('date_buy');
			$table->dropColumn('buy_number');
			$table->dropColumn('archive_dir');
		});
	}
};