<x-input-group group="small">
	<tr class="h6rem">
		<td>
			<x-input name="name" class="w100" value="{{$name}}" placeholder="Название" />
		</td>
		<td>
			<ul>
				<li><x-radio name="type" value="1" fieldset="stepType{{$id}}" label="Чекбокс" current="{{$type}}" /></li>
				<li><x-radio name="type" value="2" fieldset="stepType{{$id}}" label="Текстовое поле" current="{{$type}}" /></li>
				<li><x-radio name="type" value="3" fieldset="stepType{{$id}}" label="Список сотрудников" current="{{$type}}" /></li>
				<li><x-radio name="type" value="4" fieldset="stepType{{$id}}" label="Денежный формат" current="{{$type}}" /></li>
			</ul>
		</td>
		<td>
			<x-input name="deadline" type="number" class="w100" value="{{$deadline}}" placeholder="0" showrows />
		</td>
		
		<td></td>
		<td>
			<x-input name="sort" type="number" class="w100" value="{{$sort}}" showrows />
		</td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="stepUpdate:{{$id}}" title="Сохранить" disabled update><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="stepRemove:{{$id}}" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>