<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-input name="name" class="w100" placeholder="Наименование вида работ" />
		</td>
		<td>
			<x-input name="unit" class="w100" placeholder="м, шт, т" />
		</td>
		<td><p class="color-gray">Новый</p></td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="workTypeSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="workTypeRemoveNew" title="Убрать"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>
