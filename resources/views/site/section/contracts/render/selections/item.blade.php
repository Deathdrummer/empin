<tr class="h6rem">
	<td>
		<x-input
			name="title"
			group="small"
			:value="$title"
			class="w100"
			/>
	</td>
	<td class="center">
		<x-buttons-group group="small" gx="5">
			<x-button
				variant="green"
				action="selectionBuildList:{{$id}}"
				:enabled="$contracts_count ?? false"
				title="Сформировать список подборки"
				>Сформировать</x-button>
			
			<x-button
				variant="neutral"
				w="2rem-5px"
				action="selectionBuildToEdit:{{$id}}"
				:enabled="$contracts_count ?? false"
				title="Редактировать список подборки"
				><i class="fa-solid fa-pen-to-square"></i></x-button>
		</x-buttons-group>
	</td>
	<td class="center">
		<x-buttons-group group="verysmall" w="2rem-5px" gx="5">
			<x-button variant="blue" action="selectionUpdate:{{$id}}" disabled update title="Обновить"><i class="fa-solid fa-save"></i></x-button>
			<x-button variant="red" action="selectionRemove:{{$id}}" remove title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
		</x-buttons-group>
	</td>
</tr>