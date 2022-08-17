<x-input-group group="normal">
	<tr class="h6rem">
		<td><strong>{{$object_id}}</strong></td>
		<td><p>{{$title}}</p></td>
		<td>
			<div class="scrollblock h5rem">
				<p class="format fz12px">{{$titul}}</p>
			</div>
		</td>
		<td><p>{{$data['customers'][$customer]}}</p></td>
		<td><p>{{$data['locality'][$locality]}}</p></td>
		<td class="text-end"><p>@number($price, 2) @symbal(money)</p></td>
		<td>
			<p>@date($created_at) г.</p>
			<p>в @time($created_at)</p>
		</td>
		<td class="center">
			<x-checkbox
				name="archive"
				group="large"
				:checked="$archive ?? null"
				action="contractToArchive:{{$id}}"
				/>
		</td>
		<td class="center">
			<x-buttons-group group="small" w="3rem" gx="5">
				<x-button variant="purple" action="contractShow:{{$id}},№{{$object_id}}" title="Открыть договор"><i class="fa-solid fa-eye"></i></x-button>
				<x-button variant="red" action="contractRemove:{{$id}}" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>