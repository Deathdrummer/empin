<table>
	<thead>
		<tr>
			<td><strong class="fz12px">Название столбца</strong></td>
			<td class="w4rem center"><i class="fa-solid fa-eye"></i></td>
		</tr>
	</thead>
	<tbody id="contractColumnList">
		@forelse($colums as $field => $column)
			@if(auth('site')->user()->can('contract-col-'.$field.':site'))
				<tr class="h4rem">
					<td>
						<p>{{$column['title']}}</p>
					</td>
					<td class="center">
						<x-checkbox
							group="normal"
							:checked="$column['checked']"
							tag="contractcolumn:{{$field}}"
							/>
					</td>
				</tr>
			@endif
		@empty
			<tr>
				<td colspan="2">
					<p class="color-gray-300">Нет данных</p>
				</td>
			</tr>
		@endforelse
	</tbody>
</table>