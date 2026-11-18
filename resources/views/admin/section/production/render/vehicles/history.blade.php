@php
	$who = fn ($p) => $p['name'] ?? ('сотрудник #'.($p['id'] ?? '?'));
	$yesNo = fn ($v) => $v ? 'да' : 'нет';
	$tz = config('production.timezone');
	$fields = ['name' => 'Наименование', 'plate' => 'Госномер'];
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
								@isset($a['staff'])
									Внесён в список водителей: {{$who($a['staff'])}}
								@else
									Добавлена: {{$a['name'] ?? ''}} {{$a['plate'] ?? ''}}@if(!empty($a['not_used'])), не используется@endif
								@endisset
								@break
							@case('restored')
								Возвращён в список водителей: {{$who($a['staff'] ?? [])}}
								@break
							@case('updated')
								@foreach($fields as $key => $label)
									@if(array_key_exists($key, $a) && ($a[$key] ?? null) !== ($b[$key] ?? null))
										<div>{{$label}}: {{($b[$key] ?? null) ?: '—'}} → {{($a[$key] ?? null) ?: '—'}}</div>
									@endif
								@endforeach
								@if(array_key_exists('not_used', $a) && (bool) $a['not_used'] !== (bool) ($b['not_used'] ?? false))
									<div>«Не используется»: {{$yesNo($b['not_used'] ?? false)}} → {{$yesNo($a['not_used'])}}</div>
								@endif
								@break
							@case('driver_assigned')
								Закреплён водитель: {{$who($a['driver'] ?? [])}}
								@break
							@case('driver_unassigned')
								Откреплён водитель: {{$who($a['driver'] ?? [])}}
								@break
							@case('deleted')
								@isset($b['staff'])
									Убран из списка водителей
								@else
									Удалена из справочника
								@endisset
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
