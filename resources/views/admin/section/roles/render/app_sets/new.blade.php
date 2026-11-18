<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-input name="title" class="w100" placeholder="Название" />
		</td>
		<td><span class="color-gray">-</span></td>
		<td class="center"><span class="color-gray">-</span></td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="appSetSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="appSetRemoveNew" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>
