@props([
	'id'			=> 'card'.rand(0,9999999),
	'loading' 		=> null,
	'ready' 		=> null,
	'title' 		=> null,
	'desc' 			=> null,
	'button'		=> null,
	'buttonId'		=> null,
	'disableBtn'	=> null,
	'action'		=> null,
	'cando'			=> null
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
		
		@if($button)
			@if($cando)
				@cando($cando)
					<x-button
						id="{{$buttonId}}"
						variant="green"
						group="normal"
						px="10"
						action="{{$action}}"
						disabled="{{isset($disableBtn)}}"
						tag="cardbutton"
						>{{$button}}</x-button>
				@endcando
			@else
				<x-button
					id="{{$buttonId}}"
					variant="green"
					group="normal"
					px="10"
					action="{{$action}}"
					disabled="{{isset($disableBtn)}}"
					tag="cardbutton"
					>{{$button}}</x-button>
			@endif
		@endif
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