<div id="importPreserForm">
	<div class="row row-cols-1 gy-30">
		<div class="col">
			<p class="mb10px fz16px">Выбрать файл</p>
			<x-button group="large" variant="blue" id="parserAddFile"><i class="fa-solid fa-fw fa-file-csv"></i></x-button>
			{{-- <x-textarea
				group="normal"
				class="w100 mb1rem"
				id="importDataInput"
				rows="20"
				></x-textarea> --}}
		</div>
		<div class="col">
			<p class="mb10px fz16px">Сопоставление столбцов</p>
			<x-input-group group="small">
				<div class="table" id="importDataSelects">
					
					<table>
						<thead>
							<tr>
								<td class="w16rem"><strong class="color-gray-600">Название поля</strong></td>
								<td><strong class="color-gray-600">Сопоставить</strong></td>
								<td class="w4rem text-center" title="Обязательное присутствие значения"><i class="fa-solid fa-asterisk color-gray-600"></i></td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="h-end"><p class="color-gray-600 fz12px mb4px">Название компании</p></td>
								<td><x-select name="colums[company]" tag="titlesselect" class="w100" /></td>
								<td class="text-center"><x-checkbox name="required[company]"/></td>
							</tr>
							<tr>
								<td class="h-end"><p class="color-gray-600 fz12px mb4px">Сайт</p></td>
								<td><x-select name="colums[site]" tag="titlesselect" class="w100" /></td>
								<td class="text-center"><x-checkbox name="required[site]"/></td>
							</tr>
							<tr>
								<td class="h-end"><p class="color-gray-600 fz12px mb4px">Тематика</p></td>
								<td><x-select name="colums[subject]" tag="titlesselect" class="w100" /></td>
								<td class="text-center"><x-checkbox name="required[subject]"/></td>
							</tr>
							<tr>
								<td class="h-end"><p class="color-gray-600 fz12px mb4px">Whatsapp</p></td>
								<td><x-select name="colums[whatsapp]" tag="titlesselect" class="w100" /></td>
								<td class="text-center"><x-checkbox name="required[whatsapp]"/></td>
							</tr>
							<tr>
								<td class="h-end"><p class="color-gray-600 fz12px mb4px">Telegram</p></td>
								<td><x-select name="colums[telegram]" tag="titlesselect" class="w100" /></td>
								<td class="text-center"><x-checkbox name="required[telegram]"/></td>
							</tr>
							<tr>
								<td class="h-end"><p class="color-gray-600 fz12px mb4px">Телефон</p></td>
								<td><x-select name="colums[phone]" tag="titlesselect" class="w100" /></td>
								<td class="text-center"><x-checkbox name="required[phone]"/></td>
							</tr>
							<tr>
								<td class="h-end"><p class="color-gray-600 fz12px mb4px">E-mail</p></td>
								<td><x-select name="colums[email]" tag="titlesselect" class="w100" /></td>
								<td class="text-center"><x-checkbox name="required[email]"/></td>
							</tr>
						</tbody>
					</table>
				</div>
			</x-input-group>
		</div>
	</div>	
</div>