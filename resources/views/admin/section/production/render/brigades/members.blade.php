<div brigademembers="{{$brigade->id}}">
	<p class="mb10px">
		Бригадир: <strong>{{$foremanName ?? 'не назначен'}}</strong>.
	</p>

	<div class="table">
		<table class="w100">
			<thead>
				<tr>
					<td><strong>Сотрудник</strong></td>
					<td class="w16rem"><strong>В бригаде с</strong></td>
					<td class="w16rem"><strong>Вышел с</strong></td>
					<td class="w22rem center"><strong>Действия</strong></td>
				</tr>
			</thead>
			<tbody>
				@forelse($members as $member)
					<x-input-group group="small">
					<tr class="h5rem" member="{{$member['id']}}">
						<td>
							<span @class(['color-black' => $member['active'], 'color-gray' => !$member['active']])>{{$member['name']}}</span>
							@if($member['foreman'])<span class="color-green fz12px ml5px">бригадир</span>@endif
							@if($member['fired'])<span class="color-red fz12px ml5px">уволен</span>@endif
							@if($member['open'] && !$member['active'])<span class="color-gray fz12px ml5px">с будущей даты</span>@endif
						</td>
						<td><x-input type="date" name="joined_on" value="{{$member['joined_on']}}" class="w100" /></td>
						<td><x-input type="date" name="left_on" value="{{$member['left_on']}}" class="w100" /></td>
						<td class="center">
							<x-buttons-group group="small" w="3rem">
								<x-button variant="blue" action="brigadeMemberSave:{{$member['id']}}" title="Сохранить даты"><i class="fa-solid fa-floppy-disk"></i></x-button>
								@if($member['open'] && !$member['fired'] && !$brigade->disbanded_on)
									@if($member['foreman'])
										<x-button variant="green" action="brigadeForeman:{{$brigade->id}},0" title="Снять бригадира"><i class="fa-solid fa-star"></i></x-button>
									@else
										<x-button variant="light" action="brigadeForeman:{{$brigade->id}},{{$member['staff_id']}}" title="Сделать бригадиром"><i class="fa-regular fa-star"></i></x-button>
									@endif
									<x-button variant="neutral" action="brigadeMemberTransfer:{{$member['id']}}" title="Перевести"><i class="fa-solid fa-right-left"></i></x-button>
								@endif
								<x-button variant="red" action="brigadeMemberRemove:{{$member['id']}}" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
							</x-buttons-group>
						</td>
					</tr>
					</x-input-group>
				@empty
					<tr><td colspan="4"><p class="color-gray">В составе никого нет</p></td></tr>
				@endforelse
			</tbody>
		</table>
	</div>

	<div class="mt15px" brigadetransfer hidden>
		<p class="mb5px"><strong>Перевод <span brigadetransfername></span></strong></p>
		<x-input-group group="small">
			<div class="d-flex align-items-center">
				<x-select name="to" class="w30rem mr10px" :options="$targets" choose="В какую бригаду" empty="Нет других действующих бригад" choose-empty empty-has-value />
				<x-input type="date" name="date" value="{{$today}}" class="w16rem mr10px" />
				<x-button variant="blue" action="brigadeMemberTransferDo" class="mr5px">Перевести</x-button>
				<x-button variant="light" action="brigadeMemberTransferCancel">Отмена</x-button>
			</div>
		</x-input-group>
	</div>

	@unless($brigade->disbanded_on)
		<div class="mt15px" brigadeadd>
			<p class="mb5px"><strong>Добавить в состав</strong></p>
			<x-input-group group="small">
				<div class="d-flex align-items-center">
					<x-select name="staff_id" class="w40rem mr10px" :options="$data['staff'] ?? []" choose="Выберите сотрудника" empty="Нет сотрудников" choose-empty empty-has-value />
					<x-input type="date" name="joined_on" value="{{$today}}" class="w16rem mr10px" />
					<x-button variant="blue" action="brigadeMemberAdd:{{$brigade->id}}">Добавить</x-button>
				</div>
			</x-input-group>
		</div>
	@else
		<p class="mt15px color-red">Бригада расформирована с {{$brigade->disbanded_on->format('d.m.Y')}}</p>
	@endunless
</div>
