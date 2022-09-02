<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-input name="name" class="w100" placeholder="Название" />
		</td>
		<td></td>
		<td class="center"><span class="color-gray">-</span></td>
		<td class="center">
			<x-checkbox
				name="show_only_assigned"
				group="large"
				/>
		</td>
		<td>
			<x-input name="sort" type="number" class="w100" placeholder="0" showrows />
		</td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="departmentSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="departmentRemove" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>