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
					<x-checkbox onchange="$.setUserSetting('contracts.dopsearch', 'checkbox')" :checked="$settings['dopsearch'] ?? false" />
				</td>
			</tr>
		</table>
	</div>
</x-input-group>