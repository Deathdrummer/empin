<div
	@class([
		'chat__item',
		'chat__item-right' => $self,
		'chat__item-left' => !$self,
		'mr1rem',
	])
	>
	<div
		@class([
			'p1rem',	
			'border-all',
			'border-light',
			'border-rounded-10px',
		])
		>
		<p>{{$message}}</p>
	</div>
	
</div>