<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-input name="title" class="w100" placeholder="Название роли" />
		</td>
		{{-- <td>
			<x-select name="group" class="w100" :options="$data['rolesGroups']" />
		</td> --}}
		<td></td>
		<td class="center"><span class="color-gray">-</span></td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="roleSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="roleRemove" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>