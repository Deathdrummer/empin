
@cananydo('contract-col-object_number:site, contract-col-title:site')
	<div class="
		d-flex
		align-items-center
		justify-content-between
		pl30px
		pr60px
		">
		
		@cando('contract-col-title:site')
			<h3 class="select-text" tripleselect>{{$contract['title'] ?? '-'}}</h3>
		@endcando
		
		@cando('contract-col-object_number:site')
			<h3 class="select-text" tripleselect>{{$contract['object_number'] ?? '#####'}}</h3>
		@endcando
	</div>
	
	<div class="
		commoninfo__line
		mt26px
		mb1rem
		mr1rem
		"></div>
@endcananydo

<div class="commoninfo__scrollblock">

<div class="commoninfo__tableblock">
	<div class="commoninfo__label">
		<p>Основная информация</p>
	</div>
	<table class="w100 commoninfo__table">
		<tbody>
			@cando('contract-col-customer:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Заказчик:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>{{$customers[$contract['customer']] ?? '-'}}</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-contractor:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Исполнитель:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>{{$contractors[$contract['contractor']] ?? '-'}}</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-type:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Тип договора:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>{{$types[$contract['type']] ?? '-'}}</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-date_start:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Дата подписания договора:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>
							@if($contract['date_start'])
								@date($contract['date_start']) г.
							@else
								-
							@endif
						</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-date_end:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Дата окончания работ по договору:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>
							@if($contract['date_end'])
								@date($contract['date_end']) г.
							@else
								-
							@endif
						</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-contract:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Номер договора:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>{{$contract['contract'] ?? '-'}}</p>
					</td>
				</tr>
			@endcando
		
			@cando('contract-col-applicant:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Заявитель:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>{{$contract['applicant'] ?? '-'}}</p>
					</td>
				</tr>
			@endcando
			
			
			@cando('contract-col-locality:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Населенный пункт:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>{{$contract['locality'] ?? '-'}}</p>
					</td>
				</tr>
			@endcando
		
			@cando('contract-col-price_nds:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Стоимость договора с НДС:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>
							@if(isset($contract['price_nds']) && $contract['price_nds'])
								@number($contract['price_nds']) @symbal(money)
							@else
								-
							@endif
						</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-price:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Стоимость договора без НДС:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>
							@if(isset($contract['price']) && $contract['price'])
								@number($contract['price']) @symbal(money)
							@else
								-
							@endif
						</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-price_nds:site')
			@if($contract['subcontracting'])
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Стоимость генподрядного договора с НДС:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>
							@if(isset($contract['price_nds']) && $contract['price_nds'])
								@number($contract['price_nds'] / ((100 - $contract['gen_percent']) / 100)) @symbal(money)
							@else
								-
							@endif
						</p>
					</td>
				</tr>
			@endif
			@endcando
			
			@cando('contract-col-price:site')
			@if($contract['subcontracting'])
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Стоимость генподрядного договора без НДС:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>
							@if(isset($contract['price']) && $contract['price'])
								@number($contract['price'] / ((100 - $contract['gen_percent']) / 100)) @symbal(money)
							@else
								-
							@endif
						</p>
					</td>
				</tr>
			@endif
			@endcando
			
			@cando('contract-col-hoz_method:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Хоз способ:</p>
					</td>
					<td>
						@if($contract['hoz_method'])
							<p>Да</p>
						@else
							<p>Нет</p>
						@endif
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-subcontracting:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Субподряд:</p>
					</td>
					<td>
						@if($contract['subcontracting'])
							<p>Да</p>
						@else
							<p>Нет</p>
						@endif
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-gencontracting:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Генподряд:</p>
					</td>
					<td>
						@if($contract['gencontracting'])
							<p>Да</p>
						@else
							<p>Нет</p>
						@endif
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-buy_number:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Номер закупки:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>{{$contract['buy_number'] ?? '-'}}</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-date_buy:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Дата закупки:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>
							@if($contract['date_buy'])
								@date($contract['date_buy']) г.
							@else
								-
							@endif
						</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-date_close:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Дата закрытия договора:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>
							@if($contract['date_close'])
								@date($contract['date_close']) г.
							@else
								-
							@endif
						</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-archive_dir:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Архивная папка:</p>
					</td>
					<td>
						<p class="breakword select-text" tripleselect>{{$contract['archive_dir'] ?? '-'}}</p>
					</td>
				</tr>
			@endcando
			
			@cando('contract-col-titul:site')
				<tr>
					<td>
						<p class="color-gray-500 noselect format">Титул:</p>
					</td>
					<td>
						<p class="
							breakword
							select-text
							scrollblock-hidden
							maxh10rem
							"
							tripleselect>
							{{$contract['titul'] ?? '-'}}</p>
					</td>
				</tr>
			@endcando
		</tbody>
	</table>
</div>
	


<div class="
	commoninfo__line
	"></div>
	



@if($fields)
<div class="commoninfo__tableblock">
	<div class="commoninfo__label">
		<p>Дополнительная информация</p>
	</div>
	<table class="w100 commoninfo__table" id="commonInfoFields">
		<tbody>
			@foreach($fields as $field)
				<tr>
					<td>
						<p class="color-gray-500 noselect format">{{$field['name']}}:</p>
					</td>
					<td>
						@if(!isset($field['type']) || $field['type'] == 'textarea')
							<x-textarea
								class="w100"
								group="large"
								:value="Str::limit($data[$field['id']] ?? '', $field['limit'] ?? 1000, '')"
								rows="{{$field['rows_count'] ?? 5}}"
								tag="commoninfofield:{{$field['id']}}"
								/>
						@elseif($field['type'] == 'input')
							<x-input
								class="w100"
								inpclass="h4rem-5px"
								group="large"
								:value="Str::limit($data[$field['id']] ?? '', $field['limit'] ?? 1000, '')"
								tag="commoninfofield:{{$field['id']}}"
								/>
						@endif
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
</div>
	
@else
	<p class="text-center fz14px color-gray-500">Нет полей</p>
@endif

</div>
					
		






{{-- <div
	@class([
		'mb2rem' => $fields,
		'row',
		'row-cols-2',
		'gx-20',
		'gy-30',
		'border-bottom' => $fields,
		'border-gray-200' => $fields,
		'pb2rem' => $fields,
	])
	hidden>
	
	
</div> --}}



{{-- <div id="commonInfoFields">
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
</div> --}}