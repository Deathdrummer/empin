@if($list)
	<div class="scrollblock minh-1rem-4px maxh10rem w100 p5px" noselect>
		@if($column == 'filetype')
			@foreach ($list as $ext)
				<div class="row align-items-center contractsfilesitem" filteritem="{{$ext}}">
					<div class="col-auto"><img src="{{asset('assets/images/filetypes/'.$ext.'.png')}}" title="{{$ext}}" class="h5rem w-auto"></div>
					<div class="col"><p>{{$ext}}</p></div>
				</div>
			@endforeach
		@elseif($column == 'contract.archive')
			<p class="contractsfilesitem" filteritem="1">Архивный</p>
			<p class="contractsfilesitem" filteritem="0">Действующий</p>
			{{-- @foreach ($list as $value)
				<p class="contractsfilesitem" filteritem="{{$value}}">{{$value == 0 ? '<i class="fa fa-check"></i>' : ''}}</p>
			@endforeach --}}
		@else
			@foreach ($list as $value)
				<p class="contractsfilesitem" filteritem="{{$value}}">{{$value ?? '---'}}</p>
			@endforeach
		@endif
	</div>	
	
	<div class="d-flex justify-content-end mt5px">
		<x-button group="verysmall" variant="blue" id="contractsFilesSetFilter" title="Применить" disabled class="right">Применить</x-button>
	</div>
@endif