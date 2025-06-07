<x-input-group group="normal">
	<tr class="h6rem">
		<td>
			<x-input name="name" class="w100" value="{{$name}}" placeholder="Название" />
		</td>
		<td></td>
		<td class="center">
			<x-button
				variant="neutral"
				group="small"
				w="3rem"
				action="departmentSteps:{{$id}}"
				title="Этапы отдела"
				><i class="fa-brands fa-buffer"></i></x-button>
		</td>
		<td class="center">
			<x-checkbox
				name="show_only_assigned"
				group="large"
				:checked="$show_only_assigned ?? null"
				/>
		</td>
		<td class="center">
			<x-checkbox
				name="show_in_timesheet"
				group="large"
				:checked="$show_in_timesheet ?? null"
				/>
		</td>
		<td>
			<x-input name="sort" type="number" class="w100" value="{{$sort}}" placeholder="0" showrows />
		</td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="departmentUpdate:{{$id}}" title="Сохранить" disabled update><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="departmentRemove:{{$id}}" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>