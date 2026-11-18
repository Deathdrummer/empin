<x-input-group group="normal">
<tr class="h6rem">
	<td>
		<x-input name="name" value="{{$name}}" class="w100" placeholder="Наименование вида работ" />
	</td>
	<td >
		<x-input name="unit" value="{{$unit}}" class="w100" placeholder="Ед. изм." :disabled="$unit_locked" />
	</td>
	<td>
		@if($plans || $tasks)
			<span class="color-black">
				@if($plans)в планах {{$plans}} {{$plans % 10 === 1 && $plans % 100 !== 11 ? 'объекта' : 'объектов'}}@endif{{$plans && $tasks ? ', ' : ''}}@if($tasks)в {{$tasks}} {{$tasks % 10 === 1 && $tasks % 100 !== 11 ? 'задании' : 'заданиях'}}@endif
			</span>
		@elseif($unit_locked)
			<span class="color-gray">в прежних планах</span>
		@else
			<span class="color-gray">не используется</span>
		@endif
	</td>
	<td class="center">
		<x-buttons-group group="small" w="3rem">
			<x-button variant="blue" action="workTypeUpdate" title="Сохранить" disabled update="{{$id}}"><i class="fa-solid fa-floppy-disk"></i></x-button>
			<x-button variant="light" action="workTypeHistory:{{$id}}" title="История изменений"><i class="fa-solid fa-clock-rotate-left"></i></x-button>
			<x-button variant="red" action="workTypeRemove:{{$id}}" title="Удалить из справочника"><i class="fa-solid fa-trash-can"></i></x-button>
		</x-buttons-group>
	</td>
</tr>
</x-input-group>
