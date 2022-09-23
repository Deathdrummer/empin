<h4 class="text-center fz16px mb1rem">Статусы отделов</h4>

@if($list)
	<div class="table" id="contractDepsStatuses">
		<table>
			<thead>
				<tr>
					<td><strong>Отдел</strong></td>
					<td class="w6rem"><strong>Скрыт</strong></td>
				</tr>
			</thead>
			<tbody>
				@foreach($list as $item)
					<tr>
						<td><p
							@class([
								'color-gray-400' => !$item['info']
								])
								>{{$item['name']}}</p></td>
						<td class="center">
							<x-checkbox
								group="small"
								:disabled="!$item['info']"
								:checked="$item['info']['hide'] ?? false"
								action="changeDeptHideStatus:{{$item['id']}}"
								/>
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
@else
	<p class="color-gray-300">Нет данных</p>
@endif