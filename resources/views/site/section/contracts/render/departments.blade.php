@if($departments)
	<div class="row flex-column gy-10" departmentslist>
		@foreach($departments as $department)
			<div class="col">
				<div class="border-width-1px border-rounded-5px p15px hovered" onclick="$.sendToDepartment(this, {{$department['id']}})" departmentitem>
					{{$department['name']}}
				</div>
			</div>
		@endforeach
	</div>
@else
	<p class="color-light">Нет отделов</p>
@endif