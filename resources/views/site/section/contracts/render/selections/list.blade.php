@if($list)
	@foreach ($list as $item)
		@include($itemView, $item)
	@endforeach
@endif