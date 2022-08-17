<x-input-group group="small">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-input name="name" class="w100" placeholder="Название" />
		</td>
		<td>
			<ul>
				<li><x-radio name="type" value="1" fieldset="stepType{{$index}}" label="Чекбокс" checked /></li>
				<li><x-radio name="type" value="2" fieldset="stepType{{$index}}" label="Текстовое поле"/></li>
				<li><x-radio name="type" value="3" fieldset="stepType{{$index}}" label="Список сотрудников" /></li>
				<li><x-radio name="type" value="4" fieldset="stepType{{$index}}" label="Денежный формат" /></li>
			</ul>
		</td>
		<td>
			<x-input name="deadline" type="number" class="w100" placeholder="0" showrows />
		</td>
		
		<td></td>
		<td>
			<x-input name="sort" type="number" class="w100" value="0" showrows />
		</td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="stepSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="stepRemove" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>