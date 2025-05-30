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
		</table>
	</div>
</x-input-group>