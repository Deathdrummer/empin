<x-input-group group="normal">
<tr class="h6rem">
	<td>
		<strong class="color-black">{{isset($pseudoname) ? $pseudoname : __('custom.anon')}}</strong>
	</td>
	<td>
		<p class="color-black">{{isset($email) ? $email : ''}}</p>
	</td>
	<td>
		<x-select
			name="role"
			class="w100"
			:options="(!$hasRoles && $hasPermissions) ? ($data['roles_custom'] ?? []) : ($data['roles'] ?? [])"
			choose="{{(!$hasRoles && $hasPermissions) ? '' : 'Роль не выбрана'}}"
			empty="Нет ролей"
			empty-has-value
			value="{{$hasRoles ? $roles[0]['id'] : null}}"
			/>
	</td>
	<td>
		<x-select
			name="department_id"
			class="w100"
			:options="$data['departments'] ?? []"
			choose="Без отдела"
			empty="Нет отделов"
			choose-empty
			empty-has-value
			:value="$department_id"
			/>
	</td>
	<td></td>
	<td class="center">
		<x-button variant="neutral" group="small" action="staffSendEmail:{{$id}}" class="px-0 w3rem" title="Выслать доступ повторно">
			<i class="fa-solid fa-envelope"></i>
		</x-button>
	</td>
	<td class="center">
		<x-button variant="neutral" group="small" action="staffSetRules:{{$id}},{{isset($pseudoname) ? $pseudoname : __('custom.anon')}}" class="px-0 w3rem"><i class="fa-solid fa-list-check"></i></x-button>
	</td>
	<td class="center">
		@isset($email_verified_at)
			@if($email_verified_at)
				<i class="fa-solid fa-check color-green"></i>
			@else
				<i class="fa-solid fa-ban color-red"></i>
			@endif
		@else
			<i class="fa-solid fa-ban color-red"></i>
		@endisset
	</td>
	<td class="center">
		<x-buttons-group group="small" w="3rem">
			<x-button
				variant="blue"
				action="staffUpdate"
				title="Сохранить"
				disabled
				update="{{isset($id) ? $id : ''}}"
				><i class="fa-solid fa-floppy-disk"></i></x-button>
			<x-button
				variant="red"
				action="staffRemove"
				title="Удалить"
				remove="{{isset($id) ? $id : ''}}"
				><i class="fa-solid fa-trash-can"></i></x-button>
		</x-buttons-group>
	</td>
</tr>
</x-input-group>