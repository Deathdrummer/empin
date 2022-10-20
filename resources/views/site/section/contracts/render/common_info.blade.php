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
	
	@cando('contract-col-object_number:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Номер объекта:</p>
			<p class="breakword select-text" tripleselect>{{$contract['object_number'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-title:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Название/заявитель:</p>
			<p class="breakword select-text" tripleselect>{{$contract['title'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-applicant:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Заявитель:</p>
			<p class="breakword select-text" tripleselect>{{$contract['applicant'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-titul:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Титул:</p>
			<p class="breakword select-text" tripleselect>{{$contract['titul'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-contract:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Номер договора:</p>
			<p class="breakword select-text" tripleselect>{{$contract['contract'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-customer:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Заказчик:</p>
			<p class="breakword select-text" tripleselect>{{$customers[$contract['customer']] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-locality:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Населенный пункт:</p>
			<p class="breakword select-text" tripleselect>{{$contract['locality'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-price:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Стоимость договора:</p>
			<p class="breakword select-text" tripleselect>
				@if(isset($contract['price']) && $contract['price'])
					@number($contract['price']) @symbal(money)
				@else
					-
				@endif
			</p>
		</div>
	@endcando
	
	@cando('contract-col-contractor:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Исполнитель:</p>
			<p class="breakword select-text" tripleselect>{{$contractors[$contract['contractor']] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-type:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Тип договора:</p>
			<p class="breakword select-text" tripleselect>{{$types[$contract['type']] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-subcontracting:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Субподряд:</p>
			@if($contract['subcontracting'])
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@else
				<p>-</p>
			@endif
		</div>
	@endcando
	
	@cando('contract-col-date_start:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Дата начала договора:</p>
			<p class="breakword select-text" tripleselect>@date($contract['date_start']) г. в @time($contract['date_start'])</p>
		</div>
	@endcando
	
	@cando('contract-col-hoz_method:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Хоз способ:</p>
			@if($contract['hoz_method'])
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@else
				<p>-</p>
			@endif
		</div>
	@endcando
	
	@cando('contract-col-date_end:site')
		<div class="col">
			<p class="color-gray-500 fz13px mb5px noselect">Дата окончания договора:</p>
			<p class="breakword select-text" tripleselect>@date($contract['date_end']) г. в @time($contract['date_end'])</p>
		</div>
	@endcando
	
	<div class="col">
		<p class="color-gray-500 fz13px mb5px noselect">Дата создания договора:</p>
		<p class="breakword select-text" tripleselect>@date($contract['created_at']) г. в @time($contract['created_at'])</p>
	</div>
	<div class="col">
		<p class="color-gray-500 fz13px mb5px noselect">Дата изменения договора:</p>
		<p class="breakword select-text" tripleselect>@date($contract['updated_at']) г. в @time($contract['updated_at'])</p>
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
								'mb8px'	=> !isset($field['desc']),
								'mb4px'	=> isset($field['desc'])
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
		<p class="text-center fz14px color-gray-500">Нет полей</p>
	@endif
</div>