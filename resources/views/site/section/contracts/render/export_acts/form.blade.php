@if(count($templates))
	<strong class="d-block fz14px mb1rem">Одиночные шаблоны</strong>
	
	<ul class="buttonslist mb2rem">
		@forelse($templates as $template)
			<li class="buttonslist__item" choosetemplateid="{{$template['id'] ?? null}}">
				{{$template['name'] ?? 'Нет названия'}}
			</li>
		@empty
			<p class="color-gray">Нет шаблонов</p>
		@endforelse
	</ul>
@endif

@if($rangeTemplates)
	<strong class="d-block fz14px mb1rem">Шаблоны для диапазонов</strong>

	<ul class="buttonslist" >
		@forelse($rangeTemplates as $rtemplate)
			<li class="buttonslist__item" choosetemplateid="{{$rtemplate['id'] ?? null}}" ranged>
				{{$rtemplate['name'] ?? 'Нет названия'}}
			</li>
		@empty
			<p class="color-gray">Нет шаблонов</p>
		@endforelse
	</ul>
@endif