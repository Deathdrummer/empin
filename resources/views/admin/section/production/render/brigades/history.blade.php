@php
	$who = fn ($p) => $p['name'] ?? ('сотрудник #'.($p['id'] ?? '?'));
	$day = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d.m.Y') : '—';
	$tz = config('production.timezone');
@endphp
<div class="table">
	<table class="w100">
		<thead>
			<tr>
				<td class="w16rem"><strong>Когда</strong></td>
				<td><strong>Что изменилось</strong></td>
				<td class="w20rem"><strong>Кто</strong></td>
			</tr>
		</thead>
		<tbody>
			@forelse($history as $entry)
				@php
					$b = $entry['before'] ?? [];
					$a = $entry['after'] ?? [];
				@endphp
				<tr class="h4rem">
					<td>{{$entry['at'] ? $entry['at']->copy()->timezone($tz)->format('d.m.Y H:i') : ''}}</td>
					<td>
						@switch($entry['action'])
							@case('created')
								Создана бригада «{{$a['name'] ?? ''}}»
								@break
							@case('renamed')
								Название: «{{$b['name'] ?? ''}}» → «{{$a['name'] ?? ''}}»
								@break
							@case('foreman_changed')
								Бригадир: {{!empty($b['foreman']) ? $who($b['foreman']) : 'не назначен'}} → {{!empty($a['foreman']) ? $who($a['foreman']) : 'не назначен'}}
								@break
							@case('member_added')
								В состав: {{$who($a['staff'] ?? [])}} с {{$day($a['joined_on'] ?? null)}}@if(!empty($a['transferred_from'])) — переведён из бригады «{{$a['transferred_from']['name']}}»@endif
								@break
							@case('member_excluded')
								@if(!empty($a['transferred_to']))Переведён в бригаду «{{$a['transferred_to']['name']}}»: {{$who($a['staff'] ?? [])}} с {{$day($a['left_on'] ?? null)}}@else Исключён: {{$who($a['staff'] ?? [])}} с {{$day($a['left_on'] ?? null)}}@endif
								@break
							@case('member_corrected')
								Исправлены даты: {{$who($a['staff'] ?? [])}} — было {{$day($b['joined_on'] ?? null)}} – {{$day($b['left_on'] ?? null)}}, стало {{$day($a['joined_on'] ?? null)}} – {{$day($a['left_on'] ?? null)}}
								@break
							@case('member_removed')
								Удалена ошибочная запись: {{$who($b['staff'] ?? [])}}, {{$day($b['joined_on'] ?? null)}} – {{$day($b['left_on'] ?? null)}}
								@break
							@case('disbanded')
								Расформирована с {{$day($a['disbanded_on'] ?? null)}}
								@break
							@case('restored')
								Отменено расформирование с {{$day($b['disbanded_on'] ?? null)}}
								@break
							@default
								{{$entry['action']}}
						@endswitch
					</td>
					<td>{{$entry['admin'] ?? '—'}}</td>
				</tr>
			@empty
				<tr><td colspan="3"><p class="color-gray">Изменений пока нет</p></td></tr>
			@endforelse
		</tbody>
	</table>
</div>
