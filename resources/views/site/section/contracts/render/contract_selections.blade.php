<div ondblclick="event.stopPropagation();" oncontextmenu="event.stopPropagation();event.preventDefault();">
	<h4 class="fz16px mb1rem">Подборки</h4>
	<div class="scrollblock-light pr5px minh2rem" style="max-height: calc(100vh - 100px);">
		@if($selections)
			<ul>
				@foreach($selections as $selection)
					<li
						@class([
							'border-top border-light' => !$loop->first,
							'color-gray-600',
							'd-flex align-items-center h3rem',
						])
					>{{$selection['title'] ?? '--'}}</li>
				@endforeach
			</ul>
		@else
			<p class="color-gray fz14px">Нет подборок</p>
		@endif
	</div>
</div>