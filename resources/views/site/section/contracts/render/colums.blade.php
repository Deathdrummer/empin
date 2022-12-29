<x-table class="w100" noborder>
	<x-table.head noborder>
		<x-table.tr noborder class="h3rem">
			<x-table.td class="w4rem" noborder></x-table.td>
			<x-table.td class="w100" noborder><strong class="fz14px">Название столбца</strong></x-table.td>
			<x-table.td class="w4rem h-center" noborder><i class="fa-solid fa-eye"></i></x-table.td>
			
		</x-table.tr>
	</x-table.head>
	<x-table.body id="contractColumnList">	
		@forelse($colums as $field => $column)
			@if(auth('site')->user()->can('contract-col-'.$field.':site'))
				<x-table.tr class="h4rem">
					<x-table.td class="h-center" noborder><i class="fa-solid fa-fw fa-arrows-up-down color-gray fz12px"></i></x-table.td>
					<x-table.td noborder><p>{{$column['title']}}</p></x-table.td>
					<x-table.td class="h-center" nohandle noborder>
						<x-checkbox
							group="normal"
							:checked="$column['checked']"
							tag="contractcolumn:{{$field}}"
							/>
					</x-table.td>
				</x-table.tr>
			@endif
		@empty
			<x-table.tr>
				<p class="color-gray-300">Нет данных</p>
			</x-table.tr>
		@endforelse
	</x-table.body>
</x-table>









{{-- <div class="table">
	<table>
		<thead>
			<tr>
				<td></td>
				<td><strong class="fz12px">Название столбца</strong></td>
				<td class="w4rem center"><i class="fa-solid fa-eye"></i></td>
			</tr>
		</thead>
		<tbody id="contractColumnList">
			@forelse($colums as $field => $column)
				@if(auth('site')->user()->can('contract-col-'.$field.':site'))
					<tr class="h4rem">
						<td class="center"><i class="fa-solid fa-fw fa-arrows-up-down color-gray fz12px"></i></td>
						<td>
							<p>{{$column['title']}}</p>
						</td>
						<td class="center" nohandle>
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
					<td colspan="3">
						<p class="color-gray-300">Нет данных</p>
					</td>
				</tr>
			@endforelse
		</tbody>
	</table>
</div> --}}