<x-input-group group="normal">
	<div class="table">
		<table class="w100">
			<tr class="h5rem">
				<td><p>Скрывать генподрядные договоры:</p></td>
				<td class="w5rem h-center">
					<x-checkbox onchange="$.setUserSetting('contracts.gencontracting', 'checkbox')" :checked="$settings['gencontracting'] ?? false" />
				</td>
			</tr>
			<tr class="h5rem">
				<td><p>Искать в окне доп. информации:</p></td>
				<td class="w5rem h-center">
					<x-checkbox onchange="$.setUserSetting('contracts.dopsearch.info', 'checkbox')" :checked="$settings['dopsearch']['info'] ?? false" />
				</td>
			</tr>
			<tr class="h5rem">
				<td><p>Искать в чатах:</p></td>
				<td class="w5rem h-center">
					<x-checkbox onchange="$.setUserSetting('contracts.dopsearch.chats', 'checkbox')" :checked="$settings['dopsearch']['chats'] ?? false" />
				</td>
			</tr>
			<tr class="h5rem">
				<td><p>Отображать архивные подборки при наведении на ячейку "номер объекта":</p></td>
				<td class="w5rem h-center">
					<x-checkbox onchange="$.setUserSetting('contracts.show_archive_selections_in_object_number', 'checkbox')" :checked="$settings['show_archive_selections_in_object_number'] ?? false" />
				</td>
			</tr>
			<tr class="h5rem">
				<td colspan="2"><p class="mb-4px">Локальное расположение файлов чертежей:</p>
				<x-input
					type="url"
					class="w100"
					oninput="$.setUserSetting('contracts.local_path_to_cad_files', 'text', 500)"
					:value="$settings['local_path_to_cad_files'] ?? false"
					placeholder="Введите путь" />	
				</td>
			</tr>
		</table>
	</div>
</x-input-group>