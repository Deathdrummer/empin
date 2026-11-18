@php
	$r = $row ?? [];
	$staffIds = $r['staff_ids'] ?? [];
	$brigadeIds = $r['brigade_ids'] ?? [];
	// Выбранная область строки
	$scope = match (true) {
		!$row => null,
		$right === \App\Production\Rights\Right::TimesheetView => !empty($r['scope_all']) ? 'company' : (!empty($r['all_brigades']) ? 'all_brigades' : (!empty($r['brigade_ids']) ? 'brigades' : 'people')),
		default => !empty($r['scope_all']) ? 'all' : 'selected',
	};
	$scopes = match (true) {
		$right->hasCreatorScope() => ['all' => 'Все задания компании', 'selected' => 'Выбранные создатели'],
		$right === \App\Production\Rights\Right::TaskViewAll => ['all' => 'Все задания компании', 'selected' => 'Выбранные начальники участков и бригады'],
		$right->hasBrigadeScope() => ['all' => 'Все бригады', 'selected' => 'Выбранные бригады'],
		$right === \App\Production\Rights\Right::TimesheetView => ['company' => 'Вся компания', 'all_brigades' => 'Все бригады', 'brigades' => 'Выбранные бригады', 'people' => 'Выбранные сотрудники'],
		default => [],
	};
	$withStaff = $right->hasCreatorScope() || in_array($right, [\App\Production\Rights\Right::TaskViewAll, \App\Production\Rights\Right::TimesheetView], true);
	$withBrigades = $right->hasBrigadeScope() || in_array($right, [\App\Production\Rights\Right::TaskViewAll, \App\Production\Rights\Right::TimesheetView], true);
@endphp
<div class="mb10px pb10px" settingrow style="border-bottom: 1px solid #e5e5e5;">
	@if($right->hasDepth())
		<div class="d-flex align-items-center mb10px">
			<span class="mr10px">Глубина, дней</span>
			<x-input type="number" name="depth_days" value="{{$r['depth_days'] ?? ''}}" class="w10rem mr15px" />
			<x-checkbox name="depth_unlimited" label="Без ограничений" :checked="$row && $r['depth_days'] === null" />
		</div>
	@endif

	@if($scopes)
		<div class="d-flex mb10px">
			@foreach($scopes as $value => $label)
				<x-radio name="scope{{$uid}}" fieldset="scope{{$uid}}" value="{{$value}}" label="{{$label}}" :checked="$scope === $value" class="mr20px" />
			@endforeach
		</div>
	@endif

	<div class="row gx-20">
		@if($withStaff)
			<div class="col" checklist="staff">
				<x-input class="w100 mb5px" placeholder="Поиск сотрудника" action="appRightFilter" />
				<div class="scrollblock maxh20rem">
					@foreach($staff as $person)
						<div class="mb5px" checkitem="{{$person['id']}}">
							<x-checkbox label="{{$person['name']}}" :checked="in_array($person['id'], $staffIds, true)" />
						</div>
					@endforeach
				</div>
			</div>
		@endif
		@if($withBrigades)
			<div class="col" checklist="brigades">
				<x-input class="w100 mb5px" placeholder="Поиск бригады" action="appRightFilter" />
				<div class="scrollblock maxh20rem">
					@foreach($brigades as $brigade)
						<div class="mb5px" checkitem="{{$brigade['id']}}">
							<x-checkbox label="{{$brigade['name']}}" :checked="in_array($brigade['id'], $brigadeIds, true)" />
						</div>
					@endforeach
				</div>
			</div>
		@endif
	</div>

	@if($right === \App\Production\Rights\Right::TimesheetView)
		<div class="d-flex align-items-center mt10px">
			<span class="mr10px">С</span>
			<x-input type="date" name="date_from" value="{{$r['date_from'] ?? ''}}" class="w16rem mr15px" />
			<span class="mr10px">по</span>
			<x-input type="date" name="date_to" value="{{$r['date_to'] ?? ''}}" class="w16rem" />
		</div>
	@endif

	<div class="mt10px">
		<x-button variant="red" group="small" action="appRightRowRemove" px="10">Удалить настройку</x-button>
	</div>
</div>
