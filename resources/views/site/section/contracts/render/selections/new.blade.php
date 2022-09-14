<tr class="h6rem" index="{{$index}}">
	<td>
		<x-input
			name="title"
			group="small"
			class="w100"
			/>
	</td>
	<td class="center">
		<p class="color-gray-500">-</p>
	</td>
	<td class="center">
		<p class="color-gray-500">-</p>
	</td>
	<td class="center">
		<p class="color-gray-500">-</p>
	</td>
	<td class="center">
		<p class="color-gray-500">-</p>
	</td>
	<td class="center">
		<x-buttons-group group="verysmall" w="2rem-5px" gx="5">
			{{-- <x-button variant="neutral" title="Редактировать список подборки" disabled><i class="fa-solid fa-pen-to-square"></i></x-button> --}}
			<x-button variant="blue" action="selectionSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
			<x-button variant="red" action="selectionRemove" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
		</x-buttons-group>
	</td>
</tr>