<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-select
				name="staff_id"
				class="w100"
				:options="$data['staff'] ?? []"
				choose="Выберите сотрудника"
				empty="Нет сотрудников"
				choose-empty
				empty-has-value
				/>
		</td>
		<td><span class="color-gray">-</span></td>
		<td class="center"><span class="color-gray">-</span></td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="driverSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="driverRemoveNew" title="Убрать"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>
