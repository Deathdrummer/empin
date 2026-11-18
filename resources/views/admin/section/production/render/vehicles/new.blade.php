<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-input name="name" class="w100" placeholder="Например: Автовышка" />
		</td>
		<td>
			<x-input name="plate" class="w100" placeholder="Госномер" />
		</td>
		<td><span class="color-gray">-</span></td>
		<td class="center">
			<x-checkbox name="not_used" group="large" />
		</td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="vehicleSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="vehicleRemoveNew" title="Убрать"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>
