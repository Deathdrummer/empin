<x-input-group group="normal">
	<tr class="h6rem">
		<td>
			<x-select
				options-type="assoc"
				class="w50rem"
				name="customer"
				choose="Заказчик не выбран"
				empty="Нет заказчиков"
				empty-has-value
				:options="$data['customers'] ?? null"
				:value="$customer ?? null"
				/>
		</td>
		
		<td></td>
		<td class="center">
			<x-button
				group="small"
				variant="purple"
				action="departmentSteps:{{$customer ?? null}},{{$data['customers'][$customer] ?? null}}"
				title="Установки чекбоксов для этапов"
				><i class="fa-solid fa-list-check"></i></x-button>
		</td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="stepsPatternUpdate:{{$id}}" title="Сохранить" disabled update><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="stepsPatternRemove:{{$id}}" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>