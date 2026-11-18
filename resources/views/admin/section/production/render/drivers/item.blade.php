<x-input-group group="normal">
<tr class="h6rem">
	<td>
		<strong @class(['color-black' => !$fired, 'color-gray' => $fired])>{{$name}}</strong>
		@if($work_post)<span class="color-gray fz12px ml5px">{{$work_post}}</span>@endif
		@if($fired)<span class="color-red fz12px ml5px" title="В заданиях не предлагается">уволен</span>@endif
	</td>
	<td>
		@if($vehicles)
			<span class="color-black">{{implode(', ', $vehicles)}}</span>
		@else
			<span class="color-gray">не закреплён</span>
		@endif
	</td>
	<td class="center">
		<x-checkbox name="not_used" group="large" :checked="$not_used" />
	</td>
	<td class="center">
		<x-buttons-group group="small" w="3rem">
			<x-button variant="blue" action="driverUpdate" title="Сохранить" disabled update="{{$id}}"><i class="fa-solid fa-floppy-disk"></i></x-button>
			<x-button variant="light" action="driverHistory:{{$id}}" title="История изменений"><i class="fa-solid fa-clock-rotate-left"></i></x-button>
			<x-button variant="red" action="driverRemove:{{$id}}" title="Убрать из списка водителей"><i class="fa-solid fa-trash-can"></i></x-button>
		</x-buttons-group>
	</td>
</tr>
</x-input-group>
