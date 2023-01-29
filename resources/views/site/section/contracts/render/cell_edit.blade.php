<div class="placer placer-top placer-right pt3px pr3px">
	<i class="fa-solid fa-floppy-disk fz12px pointer saveicon" title="Сохранить" onclick="$.saveCellData(); event.stopPropagation();"></i>
</div>

<div class="editted" ondblclick="event.stopPropagation();" edittedblock>
	{{-- <h4 class="fz16px mb1rem">Редактировать договор: {{$objectNumber}} ({{$columnTitle}})</h4> --}}
	
	@if($type == '1')
		<textarea id="edittedCellData" class="fz12px lh100" placeholder="...">{{$content ?? null}}</textarea>
	@elseif($type == '2')
		
	@elseif($type == '3')
		
	@endif
</div>