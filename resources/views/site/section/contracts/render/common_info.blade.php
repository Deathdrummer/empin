<div
	@class([
		'mb2rem' => $fields,
		'row',
		'row-cols-2',
		'gx-20',
		'gy-30',
		'border-bottom' => $fields,
		'border-gray-200' => $fields,
		'pb2rem' => $fields,
	])>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Номер объекта:</p>
		<p class="breakword select-text">{{$contract['object_number'] ?? '-'}}</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Название/заявитель:</p>
		<p class="breakword select-text">{{$contract['title'] ?? '-'}}</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Заявитель:</p>
		<p class="breakword select-text">{{$contract['applicant'] ?? '-'}}</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Титул:</p>
		<p class="breakword select-text">{{$contract['titul'] ?? '-'}}</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Номер договора:</p>
		<p class="breakword select-text">{{$contract['contract'] ?? '-'}}</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Заказчик:</p>
		<p class="breakword select-text">{{$customers[$contract['customer']] ?? '-'}}</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Населенный пункт:</p>
		<p class="breakword select-text">{{$contract['locality'] ?? '-'}}</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Стоимость договора:</p>
		<p class="breakword select-text">
			@if(isset($contract['price']) && $contract['price'])
				@number($contract['price']) @symbal(money)
			@else
				-
			@endif
		</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Исполнтель:</p>
		<p class="breakword select-text">{{$contractors[$contract['contractor']] ?? '-'}}</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Тип договора:</p>
		<p class="breakword select-text">{{$types[$contract['type']] ?? '-'}}</p>
	</div>
</div>




<div id="commonInfoFields">
	@if($fields)
		<div class="row gy-30">
			@foreach($fields as $field)
				<div class="col-12">
					
					@if(!isset($field['type']) || $field['type'] == 'textarea')
						<p
							@class([
								'fz14px',
								'mb8px'	=> !isset($field['desc']),
								'mb4px'	=> isset($field['desc'])
							])><strong>{{$field['name']}}</strong></p>
						
						@isset($field['desc'])
							<small class="fz12px color-gray-500 d-block mb8px">{{$field['desc']}}</small>
						@endisset
						
						<x-textarea
							class="w100"
							group="normal"
							:value="$data[$field['id']] ?? null"
							rows="{{$field['rows_count'] ?? 5}}"
							tag="commoninfofield:{{$field['id']}}"
							/>
							
					@elseif($field['type'] == 'input')
						<p
							@class([
								'fz14px',
								'mb8px'	=> !isset($field['desc'])
							])
							><strong>{{$field['name']}}</strong></p>
						
						@isset($field['desc'])
							<small class="fz12px color-gray-500 d-block mb8px">{{$field['desc']}}</small>
						@endisset
						
						<x-input
							class="w100"
							group="normal"
							:value="$data[$field['id']] ?? null"
							tag="commoninfofield:{{$field['id']}}"
							/>
					
					@endif
				</div>
			@endforeach
		</div>	
	@else
		<p class="text-center fz14px color-gray-400">Нет полей</p>
	@endif
</div>