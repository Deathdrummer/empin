<x-input-group group="normal">
	@forelse($drivers as $driver)
		<div class="mb10px">
			<x-checkbox
				label="{{$driver['name']}}{{$driver['fired'] ? ' (уволен)' : ($driver['not_used'] ? ' (не используется)' : '')}}"
				:checked="$driver['assigned']"
				action="vehicleDriverToggle:{{$vehicle->id}},{{$driver['id']}}"
				/>
		</div>
	@empty
		<p class="color-gray">Список водителей пуст</p>
	@endforelse
</x-input-group>
