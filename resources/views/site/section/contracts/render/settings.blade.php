<x-input-group group="large">
	<div class="table">
		<table class="w100">
			<tr class="h5rem">
				<td><p>Скрывать генподрядниые договоры</p></td>
				<td class="w5rem h-center">
					<x-checkbox onchange="$.setUserSetting('contracts.gencontracting', 'checkbox')" :checked="$settings['gencontracting'] ?? false" />
				</td>
			</tr>
		</table>
	</div>
</x-input-group>