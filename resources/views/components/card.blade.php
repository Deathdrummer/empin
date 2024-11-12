@props([
	'id'			=> 'card'.rand(0,9999999),
	'loading' 		=> null,
	'ready' 		=> null,
	'title' 		=> null,
	'desc' 			=> null,
	'button'		=> null,
	'buttonId'		=> null,
	'buttonVariant'	=> null,
	'buttonGroup'	=> null,
	'disableBtn'	=> null,
	'action'		=> null,
	'cando'			=> null,
	'buttons'		=> []
])


<div
	{{$attributes->class(['card'])}}
	@if($id)id="{{$id}}"@endif
	>
	
	
	@isset($title)
	<div class="card__header">
		<div class="mr20px">
			<h3 class="card__title color-dark">{{$title}}</h3>
			<p class="card__desc color-gray">{{$desc}}</p>
		</div>
		
		<div class="row">
			@if($button)
				@if($cando)
					@cando($cando)
						<div class="col-auto">
							<x-button
								id="{{$buttonId}}"
								variant="{{$buttonVariant ?? 'green'}}"
								group="{{$buttonGroup ?? 'normal'}}"
								px="10"
								action="{{$action}}"
								disabled="{{isset($disableBtn)}}"
								tag="cardbutton"
								>{{$button}}</x-button>
						</div>
					@endcando
				@else
					<div class="col-auto">
						<x-button
							id="{{$buttonId}}"
							variant="{{$buttonVariant ?? 'green'}}"
							group="{{$buttonGroup ?? 'normal'}}"
							px="10"
							action="{{$action}}"
							disabled="{{isset($disableBtn)}}"
							tag="cardbutton"
							>{{$button}}</x-button>
					</div>
				@endif
			@endif
			
			@forelse($buttons as $button)
				@if($button['cando'] ?? false)
					@cando($cando)
						<div class="col-auto">
							<x-button
								id="{{$button['id'] ?? null}}"
								variant="{{$button['variant'] ?? 'green'}}"
								group="{{$button['group'] ?? 'normal'}}"
								px="10"
								action="{{$button['action'] ?? null}}"
								disabled="{{isset($button['disabled'])}}"
								tag="cardbutton"
								>{!!$button['icon'] ?? false ? '<i class="fa-solid fa-'.$button['icon'].'"></i>' : ''!!} {{$button['title'] ?? ''}}</x-button>
						</div>
					@endcando
				@else
					<div class="col-auto">
						<x-button
							id="{{$button['id'] ?? null}}"
							variant="{{$button['variant'] ?? 'green'}}"
							group="{{$button['group'] ?? 'normal'}}"
							px="10"
							action="{{$button['action'] ?? null}}"
							disabled="{{isset($button['disabled'])}}"
							tag="cardbutton"
							>{!!$button['icon'] ?? false ? '<i class="fa-solid fa-'.$button['icon'].'"></i>' : ''!!} {{$button['title'] ?? ''}}</x-button>
					</div>
				@endif
			@empty
			@endforelse
		</div>
	</div>
	@endisset
	{{$slot}}
	@isset($loading)
	<div class="card__wait" cardwait>
		<div class="cardwait">
			<img src="/assets/images/loading.gif" class="cardwait__icon">
			@if(is_string($loading))
				<p class="cardwait__text">{{$loading}}</p>
			@endif
		</div>
	</div>
	@endisset
</div>




<script type="module">
	let id = '{{$id ?? null}}',
		ready = '{{$ready ?? null}}';
	
	if (ready) {
		$('#'+id).ready(function() {
			$(this).card('ready');
		});
	}
</script>