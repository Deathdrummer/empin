<x-input-group group="normal">
<tr class="h6rem bg-hovered-darken pointer" ondblclick="$.openUserCard(this, {{$id}})">
	<td>
		<strong class="color-black">{{$sname.' '.$fname.' '.$mname}}</strong>
	</td>
	<td>
		<p class="color-black">{{isset($work_post) ? $work_post : '-'}}</p>
	</td>
	
	<td></td>
	<td class="text-end">
		<x-buttons-group group="small" w="3rem">
			<x-button
				variant="red"
				action="usersNewDismiss"
				title="Удалить"
				remove="{{isset($id) ? $id : ''}}"
				><i class="fa-solid fa-trash-can"></i></x-button>
		</x-buttons-group>
	</td>
</tr>
</x-input-group>