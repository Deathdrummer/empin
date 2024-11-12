{{-- <x-data :data="$data"> --}}
	@if($files)
		@foreach ($files as $file)
			@include($itemView, $file)
		@endforeach
	@endif
{{-- </x-data> --}}



{{-- <div class="row row-cols-9 gx-20 gy-40">
	@if(count($files))
		@foreach($files as $file)
			<div class="col">
				<div class="commoninfofile" noselect filecontainer="{{$file['filename_sys']}}|{{$file['contract_id']}}" title="{{$file['filename_orig']}} ({{round($file['size'] / 1024 / 1024, 1, PHP_ROUND_HALF_EVEN)}}Мб)">
					<div class="commoninfofile__icon" notouch>
						<img src="{{$file['thumb']}}" title="{{$file['filename_orig']}}">
					</div>
					<div class="commoninfofile__title">
						<small filenamereplacer>{{$file['filename_orig']}}</small>
					</div>
					<div class="commoninfofile__buttons">
						<div class="commoninfofile__remove" commoninfofileremove="{{$file['filename_sys']}}|{{$file['contract_id']}}"><i class="fa-solid fa-trash" title="Удалить файл"></i></div>
					</div>
				</div>
			</div>
		@endforeach
	@endif
</div> --}}