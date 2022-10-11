<div
	{{$attributes->class([
		'ddrtable__body',
	])->merge(['class' => 'ddrtable__body_scrolled'])}}
	ddrtablebody
	>
	{{$slot}}
</div>