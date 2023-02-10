@aware([
	'scrolled' 		=>  null,
	'scrollsync' 	=>  null,
	'scrollstart' 	=>  null,
	'scrollend' 	=>  null,
	'hidescroll' 	=>  null,
])


<div
	{{$attributes->class([
		'ddrtable__body',
		'ddrtable__body_scrolled' 	=> $scrollstart || $scrollend || $scrolled,
		'ddrtable__body_hidescroll' => $hidescroll
	])}}
	
	@if($scrolled)
		style="max-height: {{$scrolled}};"
	@endif
	role="rowgroup"
	ddrtablebody
	{{$scrollsync}}
	>
	@if($scrollstart)
		<div id="intersectionTop" class="w100"></div>
	@endif
	{{$slot}}
	@if($scrollend)
		<div id="intersectionBottom" class="w100"></div>
	@endif
</div>