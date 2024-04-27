<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-select
				options-type="assoc"
				class="w50rem"
				name="customer"
				choose="Роль не выбрана"
				empty="Нет ролей"
				empty-has-value
				:options="$data['customers'] ?? null"
				/>
		</td>
		
		<td></td>
		<td class="center">
			<p class="color-gray">-</p>
		</td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="stepsPatternSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="stepsPatternRemove" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>