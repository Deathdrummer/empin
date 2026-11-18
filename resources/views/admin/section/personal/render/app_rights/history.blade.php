@php
	$tz = config('production.timezone');
	$actions = [
		'granted' => 'Выдано право',
		'revoked' => 'Отозвано право',
		'settings_changed' => 'Изменены настройки',
		'role_set' => 'Назначена роль',
		'role_converted' => 'Права роли переведены в личные',
		'role_permission_granted' => 'В роль добавлено право',
		'role_permission_revoked' => 'Из роли убрано право',
		'set_applied' => 'Применён типовой набор',
	];
@endphp
<div class="table">
	<table class="w100">
		<thead>
			<tr>
				<td class="w16rem"><strong>Когда</strong></td>
				<td><strong>Что</strong></td>
				<td class="w20rem"><strong>Кто</strong></td>
			</tr>
		</thead>
		<tbody>
			@forelse($history as $entry)
				<tr class="h4rem">
					<td>{{$entry['at'] ? $entry['at']->copy()->timezone($tz)->format('d.m.Y H:i') : ''}}</td>
					<td>
						{{$actions[$entry['action']] ?? $entry['action']}}@if($entry['permission']): {{$entry['permission']}}@endif @if($entry['role'])({{$entry['role']}})@endif
						@if($entry['action'] === 'settings_changed')
							<div class="fz12px color-gray">было: {{$entry['before'] ?: '-'}}</div>
							<div class="fz12px">стало: {{$entry['after'] ?: '-'}}</div>
						@endif
					</td>
					<td>{{$entry['admin'] ?? '-'}}</td>
				</tr>
			@empty
				<tr><td colspan="3"><p class="color-gray">Записей нет</p></td></tr>
			@endforelse
		</tbody>
	</table>
</div>
