<p class="fz16px mb2rem">Выбрать шаблон</p>

<ul class="buttonslist" >
	@forelse($templates as $template)
		<li class="buttonslist__item" choosetemplateid="{{$template['id'] ?? null}}">
			{{$template['name'] ?? 'Нет названия'}}
		</li>
	@empty
		<p class="color-gray">Нет шаблонов</p>
	@endforelse
</ul>