@aware([
	'scrollsync' =>  null,
	'scrollstart' =>  null,
	'scrollend' =>  null,
	'hidescroll' =>  null,
])


<div
	{{$attributes->class([
		'ddrtable__body',
		'ddrtable__body_scrolled' => $scrollstart || $scrollend,
		'ddrtable__body_hidescroll' => $hidescroll
	])}}
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