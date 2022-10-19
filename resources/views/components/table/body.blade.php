@aware([
	'scrollsync' =>  null,
	'scrollstart' =>  null,
	'scrollend' =>  null,
])


<div
	{{$attributes->class([
		'ddrtable__body',
	])->merge(['class' => 'ddrtable__body_scrolled'])}}
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