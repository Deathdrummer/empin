@aware([
	'scrollsync' =>  null
])


<div
	{{$attributes->class([
		'ddrtable__body',
	])->merge(['class' => 'ddrtable__body_scrolled'])}}
	ddrtablebody
	{{$scrollsync}}
	>
	{{$slot}}
</div>