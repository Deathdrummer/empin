<x-input-group group="normal">
	<p class="mb10px color-gray fz12px">
		Отметьте водителей этой машины. При одном закреплённом водителе он подставляется в задание сам, при нескольких — выбирают из них.
		Заменить в задании можно на любого водителя из списка. Список водителей ведётся в разделе «Водители».
	</p>
	@forelse($drivers as $driver)
		<div class="mb10px">
			<x-checkbox
				label="{{$driver['name']}}{{$driver['fired'] ? ' (уволен)' : ($driver['not_used'] ? ' (не используется)' : '')}}"
				:checked="$driver['assigned']"
				action="vehicleDriverToggle:{{$vehicle->id}},{{$driver['id']}}"
				/>
		</div>
	@empty
		<p class="color-gray">Список водителей пуст — сначала добавьте водителей в разделе «Водители»</p>
	@endforelse
</x-input-group>
