@php
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
								Добавлен: {{$a['name'] ?? ''}}, {{$a['unit'] ?? ''}}
								@break
							@case('updated')
								@if(($a['name'] ?? null) !== ($b['name'] ?? null))<div>Наименование: {{$b['name'] ?? '—'}} → {{$a['name'] ?? '—'}}</div>@endif
								@if(($a['unit'] ?? null) !== ($b['unit'] ?? null))<div>Ед. изм.: {{$b['unit'] ?? '—'}} → {{$a['unit'] ?? '—'}}</div>@endif
								@break
							@case('deleted')
								Удалён из справочника
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
