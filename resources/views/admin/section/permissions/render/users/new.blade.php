<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			{{-- <p class="color-gray">-</p> --}}
			<x-input
				name="name"
				class="w100"
				placeholder="Системное название"
				tag="permissionsystem"
				/>
		</td>
		<td>
			<x-input
				name="title"
				class="w100"
				placeholder="Имя"
				action="setSystemPermissionName"
				/>
		</td>
		<td>
			<x-select name="group" class="w100" :options="$data['permissions_groups']"/>
		</td> 
		<td></td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="permissionSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="permissionRemove" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>