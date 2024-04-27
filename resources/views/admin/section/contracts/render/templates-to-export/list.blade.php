{{-- <x-data :data="$data"> --}}
	@if($list)
		@foreach ($list as $item)
			@include($itemView, $item)
		@endforeach
	@endif
{{-- </x-data> --}}