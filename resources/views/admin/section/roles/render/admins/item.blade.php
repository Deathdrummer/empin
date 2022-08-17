<x-input-group group="normal">
	<tr class="h6rem">
		<td>
			<x-input name="title" class="w100" value="{{$title}}" placeholder="Название роли" />
		</td>
		{{-- <td>
			<x-select name="group" class="w100" :options="$data['rolesGroups']" value="{{$group}}" />
		</td> --}}
		<td></td>
		<td class="center">
			<x-button variant="neutral" group="small" action="roleGetPermissions:{{$id}}" class="px-0 w3rem"><i class="fa-solid fa-list-check"></i></x-button>
		</td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="roleUpdate:{{$id}}" title="Сохранить" disabled update><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="roleRemove:{{$id}}" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>