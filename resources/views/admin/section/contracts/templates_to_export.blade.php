<section>
	<x-settings>
		<x-card
			loading
			ready
			title="Шаблоны"
			desc="Список шаблонов для выгрузки"
			>
			<x-simplelist
				group="normal"
				id="templatesToExportList"
				setting="templates-to-export"
				fieldset="ID:w7rem|number|id|1,Название шаблона:w30rem|text|name,Шаблон:w30rem|file|file,По-умолчанию:w12rem|radio|default,Активен:w12rem|checkbox|show"
				{{-- options="name_type;foo:fooval,bar:barval|name_radio;foo:rool,bar:tool" --}}
				onRemove=""
				onCreate=""
				storage="acts_templates"
				maxfilesize="10"
				filetypes="docx|xlsx"
			 />
		</x-card>
	</x-settings>
</section>



{{-- $table->string('title')->nullable()->default(null)->comment('Название шаблона');
			$table->string('import_filename')->nullable()->default(null)->comment('Название файла, импортируемоего через адмику (генерируется системой)');
			$table->string('export_filename')->nullable()->default(null)->comment('Название экспортируемоего файла (с интерполяцией)');
			$table->enum('format', ['docx','pdf', 'txt', 'xlsx'])->nullable()->default(null)->comment('Формат загружаемого шаблона');
			$table->string('section')->nullable()->default(null)->comment('Раздел списка шаблонов'); --}}







<script type="module">
	

	
</script>