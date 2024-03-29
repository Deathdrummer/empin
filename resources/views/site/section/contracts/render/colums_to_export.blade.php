{{-- <div class="scrollblock h30rem"> --}}
	<x-table class="w100" noborder scrollstart="">
		<x-table.head noborder scrollfix>
			<x-table.tr noborder class="h3rem">
				<x-table.td class="w100" noborder><strong class="fz14px">Название столбца</strong></x-table.td>
				<x-table.td class="w4rem h-center" noborder>
					<i class="fa-solid fa-check-double fz16px mr15px pointer color-gray-500 color-blue-hovered" onclick="$.selectAllChecks();" title="Выделить все\снять выделение"></i>
				</x-table.td>
			</x-table.tr>
		</x-table.head>
		<x-table.body scrolled="{{$height ?? 'calc(100vh - 200px)'}}" id="excelColumsList">	
			@forelse($colums as $field => $column)
				@if(auth('site')->user()->can('contract-col-'.$field.':site'))
					<x-table.tr class="h4rem">
						<x-table.td noborder><p>{{$column['title']}}</p></x-table.td>
						<x-table.td class="h-center" nohandle noborder>
							<x-checkbox
								group="normal"
								:checked="$column['checked']"
								tag="columtoxeport:{{$field}}"
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
{{-- </div> --}}