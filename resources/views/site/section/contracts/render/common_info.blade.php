
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


<x-chooser class="h24px" variant="neutral" px="20">
	<x-chooser.item action="contractInfoTabAction:main" class="fz12px" active>Договор</x-chooser.item>
	<x-chooser.item action="contractInfoTabAction:files" class="fz12px">Файлы</x-chooser.item>
</x-chooser>




<div class="commoninfo__scrollblock commoninfo__scrollblock-visible" id="commonInfoBlock" commoninfo="main">

	<div class="commoninfo__tableblock">
		<div class="commoninfo__label">
			<p>Основная информация</p>
		</div>
		<table class="w100 commoninfo__table" id="commoninfoContent">
			<tbody contextmenu="copyCommonInfoItem" noselect>
				@cando('contract-col-customer:site')
					<tr>
						<td>
							<p class="color-gray-500" noselect="Заказчик:" format></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>{{$customers[$contract['customer']] ?? '-'}}</p>
						</td>
					</tr>
				@endcando
				
				@cando('contract-col-contractor:site')
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Исполнитель:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>{{$contractors[$contract['contractor']] ?? '-'}}</p>
						</td>
					</tr>
				@endcando
				
				@cando('contract-col-type:site')
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Тип договора:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>{{$types[$contract['type']] ?? '-'}}</p>
						</td>
					</tr>
				@endcando
				
				@cando('contract-col-date_start:site')
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Дата подписания договора:"></p>
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
							<p class="color-gray-500 format" noselect="Дата окончания работ по договору:"></p>
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
							<p class="color-gray-500 format" noselect="Номер договора:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>{{$contract['contract'] ?? '-'}}</p>
						</td>
					</tr>
				@endcando
			
				@cando('contract-col-applicant:site')
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Заявитель:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>{{$contract['applicant'] ?? '-'}}</p>
						</td>
					</tr>
				@endcando
				
				
				@cando('contract-col-locality:site')
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Населенный пункт:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>{{$contract['locality'] ?? '-'}}</p>
						</td>
					</tr>
				@endcando
			
				@cando('contract-col-price:site')
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Стоимость договора без НДС:"></p>
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
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Стоимость договора с НДС:"></p>
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
				
				@cando('contract-col-price_gen:site')
				@if($contract['subcontracting'])
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Стоимость генподрядного договора без НДС:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>
								@if(isset($contract['price_gen']) && $contract['price_gen'])
									@number($contract['price_gen']) @symbal(money)
								@else
									-
								@endif
							</p>
						</td>
					</tr>
				@endif
				@endcando
				
				@cando('contract-col-price_gen_nds:site')
				@if($contract['subcontracting'])
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Стоимость генподрядного договора с НДС:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>
								@if(isset($contract['price_gen_nds']) && $contract['price_gen_nds'])
									@number($contract['price_gen_nds']) @symbal(money)
								@else
									-
								@endif
							</p>
						</td>
					</tr>
				@endif
				@endcando
				
				
				@cando('contract-col-price_sub:site')
				@if($contract['gencontracting'])
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Стоимость генподрядного договора без НДС:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>
								@if(isset($contract['price_sub']) && $contract['price_sub'])
									@number($contract['price_sub']) @symbal(money)
								@else
									-
								@endif
							</p>
						</td>
					</tr>
				@endif
				@endcando
				
				@cando('contract-col-price_sub_nds:site')
				@if($contract['gencontracting'])
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Стоимость генподрядного договора с НДС:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>
								@if(isset($contract['price_sub_nds']) && $contract['price_sub_nds'])
									@number($contract['price_sub_nds']) @symbal(money)
								@else
									-
								@endif
							</p>
						</td>
					</tr>
				@endif
				@endcando
				
				@cando('contract-col-date_gen_start:site')
				@if($contract['subcontracting'])
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Дата подписания генподрядного договора:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>
								@if($contract['date_gen_start'])
									@date($contract['date_gen_start']) г.
								@else
									-
								@endif
							</p>
						</td>
					</tr>
				@endif
				@endcando
				
				@cando('contract-col-date_gen_end:site')
				@if($contract['subcontracting'])
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Дата окончания работ по генподрядному договору:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>
								@if($contract['date_gen_end'])
									@date($contract['date_gen_end']) г.
								@else
									-
								@endif
							</p>
						</td>
					</tr>
				@endif
				@endcando
				
				@cando('contract-col-date_sub_start:site')
				@if($contract['gencontracting'])
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Дата подписания субподрядного договора:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>
								@if($contract['date_sub_start'])
									@date($contract['date_sub_start']) г.
								@else
									-
								@endif
							</p>
						</td>
					</tr>
				@endif
				@endcando
				
				@cando('contract-col-date_sub_end:site')
				@if($contract['gencontracting'])
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Дата окончания работ по субподрядному договору:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>
								@if($contract['date_sub_end'])
									@date($contract['date_sub_end']) г.
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
							<p class="color-gray-500 format" noselect="Хоз способ:"></p>
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
							<p class="color-gray-500 format" noselect="Субподряд:"></p>
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
							<p class="color-gray-500 format" noselect="Генподряд:"></p>
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
							<p class="color-gray-500 format" noselect="Номер закупки:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>{{$contract['buy_number'] ?? '-'}}</p>
						</td>
					</tr>
				@endcando
				
				@cando('contract-col-date_buy:site')
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Дата закупки:"></p>
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
							<p class="color-gray-500 format" noselect="Дата закрытия договора:"></p>
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
							<p class="color-gray-500 format" noselect="Архивная папка:"></p>
						</td>
						<td>
							<p class="breakword select-text" tripleselect>{{$contract['archive_dir'] ?? '-'}}</p>
						</td>
					</tr>
				@endcando
				
				@cando('contract-col-titul:site')
					<tr>
						<td>
							<p class="color-gray-500 format" noselect="Титул:"></p>
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
					
		
		
		
		

<div class="commoninfo__scrollblock" id="commonInfoBlock" commoninfo="files">
	<div class="commoninfo__dropfiles" id="contractInfoDropFiles">
		<p class="color-light text-center" notouch commoninfoofiles{{count($files) ? ' hidden' : ''}}>Нет файлов</p>
		<div class="row row-cols-7 gx-20 gy-40" id="uploadedeFilesBlock">
			@if(count($files))
				@foreach($files as $file)
					<div class="col">
						<div class="commoninfofile" filecontainer="{{$file['filename_sys']}}" title="{{$file['filename_orig']}} ({{round($file['size'] / 1024 / 1024, 1, PHP_ROUND_HALF_EVEN)}}Мб)">
							<div class="commoninfofile__icon" notouch>
								<img src="{{$file['thumb']}}" title="{{$file['filename_orig']}}">
							</div>
							<div class="commoninfofile__title">
								<small filenamereplacer>{{$file['filename_orig']}}</small>
							</div>
							<div class="commoninfofile__buttons">
								<div class="commoninfofile__remove" commoninfofileremove="{{$file['filename_sys']}}"><i class="fa-solid fa-trash" title="Удалить файл"></i></div>
							</div>
						</div>
					</div>
				@endforeach
			@endif
		</div>
	</div>
	
	<x-buttons-group group="normal" class="mb20px">
		<x-button variant="green" id="contractInfoChooseFiles">Загрузить файлы</x-button>
	</x-buttons-group>
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