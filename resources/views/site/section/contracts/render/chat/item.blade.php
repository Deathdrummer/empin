<div
	@class([
		'chat__item',
		'chat__item-right' => $self,
		'chat__item-left' => !$self,
		'mr1rem',
	])
	>
	
	<div class="chat__avatar">
		<p class="color-black fz20px">{{$account_id}}</p>
	</div>
	
	<div
		@class([
			'chat__post',
			'p1rem',	
			'border-all',
			'border-light',
		])
		>
		<strong class="chat__author breakword">{{$name ?? null}}</strong>
		<p class="chat__message breakword select-text">{{$message}}</p>
		<small class="fz10px color-gray-500 chat__date mt5px">@date($created_at) г. в @time($created_at)</small>
	</div>
	
</div>