<x-input-group group="normal">
	<tr class="h6rem" index="{{$index}}">
		<td>
			<x-input name="pseudoname" value="{{isset($pseudoname) ? $pseudoname : ''}}" class="w100" placeholder="Имя администратора (только для Вас)" />
		</td>
		<td>
			<x-input name="email" value="{{isset($email) ? $email : ''}}" class="w100"  placeholder="E-mail сотрудника" />
		</td>
		<td>
			<x-select name="role" class="w100" :options="$data['roles']" choose="Роль не выбрана" empty="Нет ролей" no-choose-has-value no-choose-has-value  />
		</td>
		<td></td>
		<td class="center"><span class="color-gray">-</span></td>
		<td class="center"><span class="color-gray">-</span></td>
		<td class="center"><span class="color-gray">-</span></td>
		<td class="center">
			<x-buttons-group group="small" w="3rem">
				<x-button variant="blue" action="adminsSave" title="Сохранить" disabled save><i class="fa-solid fa-floppy-disk"></i></x-button>
				<x-button variant="red" action="adminsRemove" title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			</x-buttons-group>
		</td>
	</tr>
</x-input-group>