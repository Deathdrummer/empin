<x-input-group group="normal">
	<tr class="h6rem">
		<td><strong>{{$object_number}}</strong></td>
		<td><p class="fz12px">{{$title ?? '-'}}</p></td>
		<td>
			<div class="scrollblock-hidden h5rem">
				<p class="format fz12px">{{$titul ?? '-'}}</p>
			</div>
		</td>
		<td><p class="fz12px">{{$applicant ?? '-'}}</p></td>
		<td><p class="fz12px">{{$contract ?? '-'}}</p></td>
		<td class="breakword">
			<div class="scrollblock-hidden h5rem vertical-center">
				@if(isset($customer) && isset($data['customers'][$customer]))
					<p class="fz12px">{{$data['customers'][$customer]}}</p></td>
				@else
					<p class="color-gray">-</p>
				@endif
			</div>
		<td><p class="fz12px">{{$locality ?? '-'}}</p></td>
		<td class="text-end">
			@isset($price_nds)
				<p>@number($price_nds, 2) @symbal(money)</p></td>
			@else
				<p class="color-gray">-</p>
			@endisset
		<td>
			<p class="fz12px">@date($created_at) г.</p>
			<p class="fz12px">в @time($created_at)</p>
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
				<x-button variant="yellow" action="contractStatuses:{{$id}}" title="Статусы договора по отделам"><i class="fa-solid fa-list-check"></i></x-button>
				<x-button variant="purple" action="contractShow:{{$id}},№{{$object_number}}" title="Открыть договор"><i class="fa-solid fa-eye"></i></x-button>
				<x-button variant="red" action="contractRemove:{{$id}}" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>