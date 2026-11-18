<x-input-group group="normal">
<tr class="h6rem">
	<td>
		<x-input name="name" value="{{$name}}" class="w100" placeholder="Название бригады" />
	</td>
	<td>
		@if($foreman_name)
			<p class="color-black">{{$foreman_name}}</p>
		@else
			<p class="color-gray">не назначен</p>
		@endif
	</td>
	<td class="center">
		<x-button variant="neutral" group="small" action="brigadeMembers:{{$id}}" title="Состав бригады, бригадир, перевод, исключение">{{$members_count}} чел.</x-button>
	</td>
	<td>
		@if($disbanded_on)
			<p class="color-red">Расформирована с {{\Carbon\Carbon::parse($disbanded_on)->format('d.m.Y')}}</p>
		@else
			<p class="color-green">Действует</p>
		@endif
	</td>
	<td class="center">
		<x-buttons-group group="small" w="3rem">
			<x-button variant="blue" action="brigadeUpdate" title="Сохранить название" disabled update="{{$id}}"><i class="fa-solid fa-floppy-disk"></i></x-button>
			<x-button variant="light" action="brigadeHistory:{{$id}}" title="История изменений"><i class="fa-solid fa-clock-rotate-left"></i></x-button>
			@if($disbanded_on)
				<x-button variant="green" action="brigadeRestore:{{$id}}" title="Отменить расформирование"><i class="fa-solid fa-rotate-left"></i></x-button>
			@else
				<x-button variant="red" action="brigadeDisband:{{$id}}" title="Расформировать"><i class="fa-solid fa-users-slash"></i></x-button>
			@endif
		</x-buttons-group>
	</td>
</tr>
</x-input-group>
