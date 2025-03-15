<x-input-group group="normal">
	<form class="form" id="staffForm">
		<div class="row gx-30">
			<div class="col-6">
				<div class="form__item">
					<label class="fz13px color-gray-600">Фамилия</label>
					<x-input name="sname" class="w100" value="{{$sname ?? null}}" tag="required" />
				</div>
				<div class="form__item">
					<label class="fz13px color-gray-600">Имя</label>
					<x-input name="fname" class="w100" value="{{$fname ?? null}}" tag="required" />
				</div>
				<div class="form__item">
					<label class="fz13px color-gray-600">Отчество</label>
					<x-input name="mname" class="w100" value="{{$mname ?? null}}" tag="required" />
				</div>
				<div class="form__item">
					<label class="fz13px color-gray-600">Должность</label>
					<x-input name="work_post" value="{{$work_post ?? null}}" class="w100" />
				</div>
			</div>
			
			<div class="col-6">
				<div class="form__item">
					<div class="row">
						<div class="col">
							<label class="fz13px color-gray-600">Паспорт серия</label>
							<x-input type="number" name="passport_series" value="{{$passport_series ?? null}}" class="w100" inpclass="hiderows" />
						</div>
						<div class="col">
							<label class="fz13px color-gray-600">Паспорт номер</label>
							<x-input type="number" name="passport_number" value="{{$passport_number ?? null}}" class="w100" inpclass="hiderows" />
						</div>
					</div>
					
				</div>
				
				<div class="form__item">
					<label class="fz13px color-gray-600">Дата выдачи паспорта</label>
					<x-input type="date" name="passport_date" value="{{$passport_date ?? null}}" class="w100" />
				</div>
				<div class="form__item">
					<label class="fz13px color-gray-600">Кем выдан</label>
					<x-textarea name="passport_from" value="{{$passport_from ?? null}}" class="w100" rows="3"></x-textarea>
				</div>
				<div class="form__item">
					<label class="fz13px color-gray-600">Место рождения</label>
					<x-textarea name="birth_place" value="{{$birth_place ?? null}}" class="w100" rows="3"></x-textarea>
				</div>
				<div class="form__item">
					<label class="fz13px color-gray-600">Индекс по месту регистрации</label>
					<x-input type="number" name="post_index" value="{{$post_index ?? null}}" class="w100" inpclass="hiderows" />
				</div>
				<div class="form__item">
					<label class="fz13px color-gray-600">Адрес постоянной регистрации</label>
					<x-textarea name="registration_address" value="{{$registration_address ?? null}}" class="w100" rows="3"></x-textarea>
				</div>
			</div>
		</div>
	</form>
</x-input-group>