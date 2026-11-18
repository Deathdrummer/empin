<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-input name="name" class="w100" placeholder="Название бригады" />
		</td>
		<td>
			<x-select
				name="foreman_staff_id"
				class="w100"
				:options="$data['staff'] ?? []"
				choose="Бригадир не выбран"
				empty="Нет сотрудников"
				choose-empty
				empty-has-value
				/>
		</td>
		<td class="center">
			<x-input type="date" name="foreman_since" value="{{$today}}" class="w100" />
		</td>
		<td><p class="color-gray">Новая</p></td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="brigadeSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="brigadeRemoveNew" title="Убрать"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>
