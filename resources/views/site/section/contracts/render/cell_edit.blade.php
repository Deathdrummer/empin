

	{{-- <h4 class="fz16px mb1rem">Редактировать договор: {{$objectNumber}} ({{$columnTitle}})</h4> --}}
	
	{{-- 
		1 - текст
		2 - стоимость
		3 - дата
		4 - вып. список
	 --}}
	
	
@if($type == '1')
	<div class="placer placer-top placer-right pt3px pr3px" ondblclick="event.stopPropagation();" edittedplacer>
		<i class="fa-solid fa-floppy-disk fz12px pointer saveicon" title="Сохранить" savecelldata></i>
	</div>
	
	<div class="editted" ondblclick="event.stopPropagation();" edittedblock>
		<textarea id="edittedCellData" class="fz12px lh100 mt1px mb1px text-justify" placeholder="...">{{$content ?? null}}</textarea>
	</div>
@elseif($type == '2')
	<div class="placer placer-top placer-right pt3px pr3px" ondblclick="event.stopPropagation();" edittedplacer>
		<i class="fa-solid fa-floppy-disk fz12px pointer saveicon" title="Сохранить" savecelldata></i>
	</div>
	
	<div class="editted editted_centred" ondblclick="event.stopPropagation();" edittedblock>
		<input type="text" id="edittedCellData" class="fz12px text-end pr2px" placeholder="стоимость" value="{{$content ?? null}}">
		<strong><sup>₽</sup></strong>
	</div>
@elseif($type == '3')
@elseif($type == '4')
	<div class="scrollblock-light" style="max-height: 200px;" ondblclick="event.stopPropagation();" onclick="event.stopPropagation();">
		<ul>
			@forelse($list as $item)
				<li
					@class([
						'h3rem d-flex align-items-center',
						'color-black' => $item['id'] == $content,
						'color-gray color-gray-600-hovered color-black-active pointer' => $item['id'] != $content,
						'border-bottom border-gray-200' => !$loop->last,
					])
					
					@if($item['id'] != $content)
						edittedlistvalue="{{$item['id']}}"
					@endif
					>
				{{$item['name'] ?? $item['title']}}</li>
			@empty
				<li class="empty">Нет данных</li>
			@endforelse
		</ul>
	</div>
@endif