<div
	{{$attributes->whereDoesntStartWith('if')->class([
		'ddrtable__td',
	])}}
	ddrtabletd
	>
	{{$slot}}
</div>