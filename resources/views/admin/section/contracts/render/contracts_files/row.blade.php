<x-table.tr class="h6rem" filecontainer="{{$file['filename_sys']}}|{{$file['contract_id']}}">
	<x-table.td class="h-center noselect covered"><img src="{{$thumb}}" title="{{$filename_orig}}" class="h5rem w-auto"></x-table.td>
	<x-table.td class="noselect"><p class="fz12px lh100">{{$filename_orig}}</p></x-table.td>
	<x-table.td class="noselect">
		@if(($size / 1024 / 1024) < 0.1)
			<p>{{round($size / 1024, 1)}} Кб</p>
		@else
			<p>{{round($size / 1024 / 1024, 1)}} Мб</p>
		@endif
	</x-table.td>
	
	<x-table.td></x-table.td>
	
	<x-table.td class="noselect"><p>{{$contract['object_number'] ?? '-'}}</p></x-table.td>
	<x-table.td class="noselect"><p class="fz12px lh100">{{$contract['applicant'] ?? '-'}}</p></x-table.td>
	<x-table.td class="noselect"><p class="fz12px lh100">{{$author['full_name'] ?? '-'}}</p></x-table.td>
	<x-table.td class="noselect">
		<p class="fz12px">
			{{dateFormatterNew($upload_date, '{dd} {mmm} {yyyy} г.')}}
			<span class="d-block mt3px">{{dateFormatterNew($upload_date, 'в {h}:{i}')}}</span>
		</p>
	</x-table.td>
	<x-table.td class="h-center noselect">
		@if($contract['archive'] ?? false)
			<i class="fa-solid fa-box-archive color-gray fz16px" title="Архивный договор"></i>
		@else
			<i class="fa-solid fa-circle-check color-green fz16px" title="Действующий договор"></i>
		@endif
	</x-table.td>
	
	<x-table.td class="h-center noselect">
		<i
			class="fa-solid fa-fw fa-ellipsis-vertical color-gray-600 color-blue-hovered"
			noselectrow
			pointer
			openrowmenu="{{$file['filename_sys']}}|{{$file['filename_orig']}}|{{$file['contract_id']}}"
			></i>
	</x-table.td>
</x-table.tr>