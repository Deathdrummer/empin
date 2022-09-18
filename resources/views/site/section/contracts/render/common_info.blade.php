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
			<p class="color-gray-400 fz13px mb5px">Номер объекта:</p>
			<p class="breakword select-text">{{$contract['object_number'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-title:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Название/заявитель:</p>
			<p class="breakword select-text">{{$contract['title'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-applicant:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Заявитель:</p>
			<p class="breakword select-text">{{$contract['applicant'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-titul:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Титул:</p>
			<p class="breakword select-text">{{$contract['titul'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-contract:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Номер договора:</p>
			<p class="breakword select-text">{{$contract['contract'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-customer:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Заказчик:</p>
			<p class="breakword select-text">{{$customers[$contract['customer']] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-locality:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Населенный пункт:</p>
			<p class="breakword select-text">{{$contract['locality'] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-price:site')
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
	@endcando
	
	@cando('contract-col-contractor:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Исполнитель:</p>
			<p class="breakword select-text">{{$contractors[$contract['contractor']] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-type:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Тип договора:</p>
			<p class="breakword select-text">{{$types[$contract['type']] ?? '-'}}</p>
		</div>
	@endcando
	
	@cando('contract-col-subcontracting:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Субподряд:</p>
			@if($contract['subcontracting'])
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@else
				<p>-</p>
			@endif
		</div>
	@endcando
	
	@cando('contract-col-date_start:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Дата начала договора:</p>
			<p class="breakword select-text">@date($contract['date_start']) г. в @time($contract['date_start'])</p>
		</div>
	@endcando
	
	@cando('contract-col-hoz_method:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Хоз способ:</p>
			@if($contract['hoz_method'])
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@else
				<p>-</p>
			@endif
		</div>
	@endcando
	
	@cando('contract-col-date_end:site')
		<div class="col">
			<p class="color-gray-400 fz13px mb5px">Дата окончания договора:</p>
			<p class="breakword select-text">@date($contract['date_end']) г. в @time($contract['date_end'])</p>
		</div>
	@endcando
	
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Дата создания договора:</p>
		<p class="breakword select-text">@date($contract['created_at']) г. в @time($contract['created_at'])</p>
	</div>
	<div class="col">
		<p class="color-gray-400 fz13px mb5px">Дата изменения договора:</p>
		<p class="breakword select-text">@date($contract['updated_at']) г. в @time($contract['updated_at'])</p>
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
		<p class="text-center fz14px color-gray-400">Нет полей</p>
	@endif
</div>