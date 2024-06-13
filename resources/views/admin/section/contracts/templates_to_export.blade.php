<section>
	<x-settings>
		<x-card
			loading
			ready
			title="Шаблоны"
			desc="Список шаблонов для выгрузки"
			:buttons="[['type' => 'icon', 'icon' => 'circle-info', 'action' => 'openHints', 'group' => 'small', 'variant' => 'light'], ['title' => 'Переменные для интерполяции', 'action' => 'openCheatSheet', 'group' => 'small', 'variant' => 'light']]"
			>
			<x-simplelist
				group="normal"
				id="templatesToExportList"
				setting="templates-to-export"
				fieldset="ID:w7rem|number|id|1,Название шаблона:w30rem|text|name,Имя файла на выходе:w40rem|text|export_name,Шаблон:w30rem|file|file,По-умолчанию:w12rem|radio|default,Активен:w8rem|checkbox|show,Право:w-auto|text|rule"
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
	
	$.openCheatSheet = () => {
		ddrPopup({
			title: 'Переменные для интерполяции',
			width: 900,
			buttons: ['Закрыть'],
		}).then(async ({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			wait();
			
			const {data, error, status, headers} = await axiosQuery('get', 'ajax/get_export_cheatsheet');
			
			
			if (error) {
				$.notify(error.message, 'error');
				wait(false);
				return;
			} 
			
			
			let html = '';
			html += '<div class="table">';
			html += '<table>';
			html += 	'<thead>';
			html += 		'<tr class="h5rem">';
			html += 			'<td class="w20rem"><strong>Переменная</strong></td>';
			html += 			'<td><strong>Обозначение</strong></td>';
			html += 		'</tr>';
			html += 	'</thead>';
			html += 	'<tbody>';
			for (const [key, value] of Object.entries(data?.cols)) {
				html +=	'<tr>';
				html +=		`<td class="pointer color-gray-500 color-gray-700-hovered color-blue-active" onclick="$.copyVariable('{${key}}')">{${key}}</td>`;
				html +=		`<td>${value}</td>`;
				html +=	'</tr>';
			}
			
			html +=	'<tr class="h8rem">';
			html +=		'<td class="w100" colspan="2"><strong class="mt-2rem d-block fz16px">Виртуальные переменные</strong></td>';
			html +=	'</tr>';
			
			for (const [key, value] of Object.entries(data?.virtVars)) {
				html +=	'<tr>';
				html +=		`<td class="pointer color-gray-500 color-gray-700-hovered color-blue-active" onclick="$.copyVariable('{${key}}')">{${key}}</td>`;
				html +=		`<td>${value}</td>`;
				html +=	'</tr>';
			}
			html += 	'</tbody>';
			html += '</table>';
			html += '</div>';
			
			setHtml(html);
			
			
			
			$.copyVariable = (data) => {
				if (event.detail == 1) copyStringToClipboard(data);
				else if (event.detail == 2) close();
			}
			
			
			
			wait(false);
		});
	}
	
	
	
	
	
	$.openHints = () => {
		ddrPopup({
			title: 'Подсказки',
			width: 1200,
			buttons: ['Закрыть'],
		}).then(async ({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			wait();
			
			const {data, error, status, headers} = await axiosQuery('get', 'ajax/get_export_hints');
			
			if (error) {
				$.notify(error.message, 'error');
				wait(false);
				return;
			} 
			
			setHtml(data, () => {
				wait(false);
			});
		});
	}
	
	
	






	
</script>