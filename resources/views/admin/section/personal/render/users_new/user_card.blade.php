<div class="row align-items-center bg-white pt15px mt-n15" style="position: sticky; top: 66px; z-index: 10;">
	<div class="col-auto">
		<i
			class="fa-solid fa-chevron-left fz-26px pointer color-gray-500 color-gray-600-hovered color-dark-active"
			cardback
			onclick="$.closeUserCard();"
			title="К списку"
			></i>
	</div>
	<div class="col">
		<h3 class="ml15px">{{$sname}} {{$fname}} {{$mname}}</h3>
	</div>
	<div class="col-auto">
		<x-button
			variant="blue"
			group="normal"
			action="usersNewCardEdit"
			title="Редактировать сотрудника"
			edit="{{isset($id) ? $id : ''}}"
			><i class="fa-solid fa-fw fa-pen-to-square"></i></x-button>
	</div>
	<hr class="hr-light mt10px">
</div>

<div class="row mt-5rem">
	<div class="col-8">
		<div class="ddrlist">
			<div class="ddrlist__item mb3rem">
				<p class="mb5px fz13px color-gray">ФИО:</p>
				<strong>{{$sname}} {{$fname}} {{$mname}}</strong>
			</div>
			<div class="ddrlist__item mb3rem">
				<p class="mb5px fz13px color-gray">Должность:</p>
				<strong>{{$work_post ?? '-'}}</strong>
			</div>
		</div>
	</div>
	<div class="col-4">
		<div class="ddrlist">
			<div class="ddrlist__item mb3rem">
				<p class="mb5px fz13px color-gray">Серия паспорта</p>
				<strong>{{$passport_series ?? '-'}}</strong>
			</div>
			<div class="ddrlist__item mb3rem">
				<p class="mb5px fz13px color-gray">Номер паспорта</p>
				<strong>{{$passport_number ?? '-'}}</strong>
			</div>
			<div class="ddrlist__item mb3rem">
				<p class="mb5px fz13px color-gray">Дата выдачи поспорта</p>
				<strong>{{$passport_date ?? '-'}}</strong>
			</div>
			<div class="ddrlist__item mb3rem">
				<p class="mb5px fz13px color-gray">Кем выдан поспорт</p>
				<strong>{{$passport_from ?? '-'}}</strong>
			</div>
			<div class="ddrlist__item mb3rem">
				<p class="mb5px fz13px color-gray">Место рождения</p>
				<strong>{{$birth_place ?? '-'}}</strong>
			</div>
			<div class="ddrlist__item mb3rem">
				<p class="mb5px fz13px color-gray">Почтовый индекс</p>
				<strong>{{$post_index ?? '-'}}</strong>
			</div>
			<div class="ddrlist__item mb3rem">
				<p class="mb5px fz13px color-gray">Адрес регистрации</p>
				<strong>{{$registration_address ?? '-'}}</strong>
			</div>
		</div>
	</div>
</div>


<hr class="hr-light my-50">


<h3 class="fz18px mb2rem">Доступ</h3>
<div class="ddrlist mb5rem">
	<div class="ddrlist__item mb2rem">
		<div class="row align-items-center">
			<div class="col-auto">
				<x-toggle
					group="large"
					action="setStaffToUser:{{$id}}"
					id="staffToUserToggle"
					:checked="$is_registred"
					/>
			</div>
			<div class="col-auto"><strong class="fz16px">Пользователь ЭМПИН-pro</strong></div>
		</div>
	</div>
	
	@if($registred['email'] ?? false)
	<div class="ddrlist__item mb2rem">
		<p class="mb5px fz13px color-gray">E-mail для доступа:</p>
		<strong>{{$registred['email'] ?? '-'}}</strong>
		<i
			class="fa-solid fa-fw fa-pen-to-square color-blue color-hovered-darken pointer ml10px fz18px"
			onclick="$.usersNewEditEmail(this, {{$id}});"
			title="Изменить E-mail"
			></i>
		<i
			class="fa-solid fa-fw fa-envelope-circle-check ml10px fz18px color-{{!is_null($registred['temporary_password']) ? 'green' : 'gray-600'}} color-hovered-darken pointer"
			onclick="$.usersNewSendEmail(this, {{$id}});"
			title="{{!is_null($registred['temporary_password']) ? 'Выслать доступ сотруднику' : 'Выслать доступ повторно'}}"
			></i>
			
			
			
			
	</div>
	
	{{-- <div class="ddrlist__item mb2rem">
		
		<strong>{{$registred['email'] ?? '-'}}</strong>
	</div> --}}
	@endif
</div>


@if($is_registred)
<h3 class="fz18px mb2rem">Права</h3>
<div class="ddrlist mb5rem">
	<div class="ddrlist__item mb2rem">
		<p class="mb5px fz13px color-gray-600">Отдел:</p>
		<x-select
			group="normal"
			class="w20rem"
			:options="$data['departments'] ?? []"
			choose="Без отдела"
			empty="Нет отделов"
			choose-empty
			empty-has-value
			action="setDepartmentAction:{{$id}}"
			:value="$registred['department_id'] ?? null"
			/>
	</div>
	
	<div class="ddrlist__item mb2rem">
		<p class="mb5px fz13px color-gray-600">Роль:</p>
		<x-select
			group="normal"
			class="w20rem"
			:options="(!$hasRoles && $hasPermissions) ? ($data['roles_custom'] ?? []) : ($data['roles'] ?? [])"
			choose="{{(!$hasRoles && $hasPermissions) ? '' : 'Роль не выбрана'}}"
			empty="Нет ролей"
			empty-has-value
			action="setRoleAction:{{$id}}"
			value="{{$hasRoles ? $roles[0]['id'] : null}}"
			/>
	</div>
	
	<div class="ddrlist__item mb2rem">
		<p
			class="mb5px fz14px color-blue color-hovered-darken pointer"
			onclick="$.setRulesAction(this, {{$id}}, '{{$sname}} {{$fname}} {{$mname}}')"
			>Настроить уникальные права</p>
	</div>
</div>


@if($data['dropdown_lists'] ?? false)
<h3 class="fz18px mb2rem">Выпадающие списки</h3>
<div class="ddrlist mb5rem">
	@foreach($data['dropdown_lists'] as $listId => $name)
	<div class="ddrlist__item mb2rem">
		<div class="row align-items-center">
			<div class="col-auto">
				<x-toggle
					group="large"
					action="toDropdownListAction:{{$id}},{{$listId}}"
					id=""
					:checked="in_array($listId, $data['user_lists'])"
					/>
			</div>
			<div class="col-auto"><p class="fz16px">{{$name ?? '-'}}</p></div>
		</div>
	</div>
	@endforeach
</div>
@endif



<h3 class="fz18px mb2rem">Условия</h3>
<div class="ddrlist">
	<div class="ddrlist__item mb2rem">
		<div class="row align-items-center">
			<div class="col-auto">
				<x-toggle
					group="large"
					action="setShowInSelectionAction:{{$id}}"
					id=""
					:checked="$disable_show_in_selections"
					/>
			</div>
			<div class="col-auto"><p class="fz16px">Не отображать в списке «Поделиться подборкой»</p></div>
		</div>
	</div>
	
	<div class="ddrlist__item mb2rem">
		<div class="row align-items-center">
			<div class="col-auto">
				<x-toggle
					group="large"
					action="setWorkingAction:{{$id}}"
					id=""
					:checked="!$working"
					/>
				}
			</div>
			<div class="col-auto"><p class="fz16px">Уволен</p></div>
		</div>
	</div>
</div>

@endif


<div class="h50rem"></div>
<div class="h50rem"></div>