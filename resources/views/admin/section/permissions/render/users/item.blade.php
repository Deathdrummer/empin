<x-input-group group="normal">
	<tr class="h6rem">
		<td>
			<p class="color-gray" permission-name>{{$name}}</p>
			{{-- <x-input name="title" class="w100" value="{{$name}}" placeholder="Системное название" /> --}}
		</td>
		<td>
			<x-input name="title" class="w100" value="{{$title}}" placeholder="Имя" />
		</td>
		<td>
			<x-select name="group" class="w100" :options="$data['permissions_groups']" value="{{$group}}" />
		</td> 
		<td></td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="permissionUpdate:{{$id}}" title="Сохранить" disabled update><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="permissionRemove:{{$id}}" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>