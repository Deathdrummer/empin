<x-input-group group="normal">
<tr class="h6rem">
	<td>
		<x-input name="name" value="{{$name}}" class="w100" placeholder="Наименование" />
	</td>
	<td>
		<x-input name="plate" value="{{$plate}}" class="w100" placeholder="Госномер" />
	</td>
	<td>
		<div class="d-flex align-items-center justify-content-between">
			<div class="mr10px">
				@forelse($drivers as $driver)
					<span @class(['color-black' => !$driver['unavailable'], 'color-gray' => $driver['unavailable']])
						>{{$driver['name']}}</span>@if(!$loop->last), @endif
				@empty
					<span class="color-red">водители не закреплены</span>
				@endforelse
			</div>
			<x-button variant="neutral" group="small" action="vehicleDrivers:{{$id}}" title="Водители" class="px-0 w3rem"><i class="fa-solid fa-user-gear"></i></x-button>
		</div>
	</td>
	<td class="center">
		<x-checkbox name="not_used" group="large" :checked="$not_used" />
	</td>
	<td class="center">
		<x-buttons-group group="small" w="3rem">
			<x-button variant="blue" action="vehicleUpdate" title="Сохранить" disabled update="{{$id}}"><i class="fa-solid fa-floppy-disk"></i></x-button>
			<x-button variant="light" action="vehicleHistory:{{$id}}" title="История изменений"><i class="fa-solid fa-clock-rotate-left"></i></x-button>
			<x-button variant="red" action="vehicleRemove:{{$id}}" title="Удалить из справочника"><i class="fa-solid fa-trash-can"></i></x-button>
		</x-buttons-group>
	</td>
</tr>
</x-input-group>
