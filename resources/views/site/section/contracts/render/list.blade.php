@isset($list)
	@if(isset($append) && $append)
		@foreach($list as $contract)
			<x-table.tr
				class="h5rem-4px{{$contract['selected'] ? ' ddrtable__tr-selected' : ''}}"
				style="background-color: {{$contract['selected_color'] ?? ''}};"
				ondblclick="$.openContractInfo(this, '{{$contract['id']}}');"
				isnew="{{$contract['is_new'] ? 1 : 0}}"
				contractid="{{$contract['id']}}"
				contextmenu="contractContextMenu:{{$contract['id'] ?? ''}},{{$departmentId ?: '0'}},{{$selectionId ?: '0'}},{{$contract['object_number'] ?? ''}},{{$contract['title'] ?? ''}},{{$contract['has_deps_to_send'] ? '1' : '0'}},{{$contract['messages_count'] ?? '0'}},{{$rules ?? ''}},{{!!$contract['pinned'] ?: 0}}"
				:contractselected="$contract['selected']"
				>
				
				<div class="icon icon-left ml1px" pinnedicon>
					@if($contract['pinned'])
						<i
							class="fz10px fa-solid fa-thumbtack fa-rotate-by color-gray-500"
							style="--fa-rotate-angle: -40deg;"
							noscroll
							title="Закрепить договор">
						</i>
					@endif
				</div>
				
				@include('site.section/contracts.render.row_common', $contract)
				@include('site.section/contracts.render.row_departments', compact('contract', 'alldeps'))
			</x-table.tr>
		@endforeach
	@else
		<x-horisontal space="2rem" scroll="false" hidescroll="1" ignore="[noscroll], select, input, textarea" ignoremovekeys="alt, ctrl, shift">
			<x-horisontal.item class="h100">
				<x-table
					scrollstart="doScrollStart"
					scrollend="doScrollEnd"
					scrollpart="doScrollPart"
					hidescroll="1"
					>
					<x-table.head scrollfix>
						<x-table.tr
							class="{{-- showrows  --}}h{{$rowHeight ?? '7'}}rem"
							sorts
							>
							
							@forelse($userColums as $column)
								@if($column == 'period' && !$isArchive && auth('site')->user()->can('contract-col-period:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'deadline_color_key' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'deadline_color_key')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak" title="Срок исполнения договора">Срок исполн. договора</strong>
									</x-table.td>
								@endif
								
								@if($column == 'object_number' && auth('site')->user()->can('contract-col-object_number:site'))
									<x-table.td
										class="w7rem sort{{$sortField == 'object_number' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'object_number')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Номер объекта</strong>
									</x-table.td>
								@endif
								
								@if($column == 'title' && auth('site')->user()->can('contract-col-title:site'))
									<x-table.td
										style="width:{{isset($listWidth['title']) ? $listWidth['title'] : '300'}}px;"
										class="sort{{$sortField == 'title' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'title')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Название</strong>
									</x-table.td>
								@endif
								
								@if($column == 'applicant' && auth('site')->user()->can('contract-col-applicant:site'))
									<x-table.td
										style="width:{{isset($listWidth['applicant']) ? $listWidth['applicant'] : '100'}}px;"
										class="sort{{$sortField == 'applicant' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'applicant')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Заявитель</strong>
									</x-table.td>
								@endif
								
								@if($column == 'titul' && auth('site')->user()->can('contract-col-titul:site'))
									<x-table.td
										style="width:{{isset($listWidth['titul']) ? $listWidth['titul'] : '200'}}px;"
										class="sort{{$sortField == 'titul' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'titul')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Титул</strong>
									</x-table.td>
								@endif
								
								@if($column == 'contract' && auth('site')->user()->can('contract-col-contract:site'))
									<x-table.td
										style="width:{{isset($listWidth['contract']) ? $listWidth['contract'] : '100'}}px;"
										class="sort{{$sortField == 'contract' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'contract')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Номер договора</strong>
									</x-table.td>
								@endif
								
								@if($column == 'customer' && auth('site')->user()->can('contract-col-customer:site'))
									<x-table.td
										style="width:{{isset($listWidth['customer']) ? $listWidth['customer'] : '150'}}px;"
										class="sort{{$sortField == 'customer' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'customer')"
										contextmenu="contractFilterBy:{{$column}}"
										noscroll
										ddrtabletdmain
										>
										<strong class="fz10px lh90 d-block text-center wodrbreak">Заказчик</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'locality' && auth('site')->user()->can('contract-col-locality:site'))
									<x-table.td
										style="width:{{isset($listWidth['locality']) ? $listWidth['locality'] : '150'}}px;"
										class="sort{{$sortField == 'locality' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'locality')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Населенный пункт</strong>
									</x-table.td>
								@endif
								
								
								@if($column == 'date_send_action' && auth('site')->user()->can('contract-col-date_send_action:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'date_send_action' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_send_action')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Дата подачи выполнения</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								
								@if($column == 'count_ks_2' && auth('site')->user()->can('contract-col-count_ks_2:site'))
									<x-table.td
										style="width:{{isset($listWidth['count_ks_2']) ? $listWidth['count_ks_2'] : '150'}}px;"
										class="sort{{$sortField == 'count_ks_2' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'count_ks_2')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Количество актов КС-2</strong>
									</x-table.td>
								@endif
								
								
								@if($column == 'act_pir' && auth('site')->user()->can('contract-col-act_pir:site'))
									<x-table.td
										class="w3rem-4px sort{{$sortField == 'act_pir' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'act_pir')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Акт на ПИР</strong>
									</x-table.td>
								@endif
								
								
								
								@if($column == 'price' && auth('site')->user()->can('contract-col-price:site'))
									<x-table.td
										style="width:{{isset($listWidth['price']) ? $listWidth['price'] : '110'}}px;"
										class="sort{{$sortField == 'price' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Стоимость договора без НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_nds' && auth('site')->user()->can('contract-col-price_nds:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_nds']) ? $listWidth['price_nds'] : '110'}}px;"
										class="sort{{$sortField == 'price_nds' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_nds')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Стоимость договора с НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_gen' && auth('site')->user()->can('contract-col-price_gen:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_gen']) ? $listWidth['price_gen'] : '110'}}px;"
										class="sort{{$sortField == 'price_gen' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_gen')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Стоимость генподрядного договора без НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_gen_nds' && auth('site')->user()->can('contract-col-price_gen_nds:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_gen_nds']) ? $listWidth['price_gen_nds'] : '110'}}px;"
										class="sort{{$sortField == 'price_gen_nds' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_gen_nds')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Стоимость генподрядного договора с НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_sub' && auth('site')->user()->can('contract-col-price_sub:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_sub']) ? $listWidth['price_sub'] : '110'}}px;"
										class="sort{{$sortField == 'price_sub' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_sub')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Стоимость субподрядного договора без НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_sub_nds' && auth('site')->user()->can('contract-col-price_sub_nds:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_sub_nds']) ? $listWidth['price_sub_nds'] : '110'}}px;"
										class="sort{{$sortField == 'price_sub_nds' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_sub_nds')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Стоимость субподрядного договора с НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_avvr' && auth('site')->user()->can('contract-col-price_avvr:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_avvr']) ? $listWidth['price_avvr'] : '110'}}px;"
										class="sort{{$sortField == 'price_avvr' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_avvr')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Сумма АВВР без НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_avvr_nds' && auth('site')->user()->can('contract-col-price_avvr:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_avvr_nds']) ? $listWidth['price_avvr_nds'] : '110'}}px;"
										class="sort{{$sortField == 'price_avvr_nds' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_avvr_nds')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Сумма АВВР с НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'avvr_nds_only' && auth('site')->user()->can('contract-col-avvr_nds_only:site'))
									<x-table.td
										style="width:{{isset($listWidth['avvr_nds_only']) ? $listWidth['avvr_nds_only'] : '110'}}px;"
										class="sort{{$sortField == 'avvr_nds_only' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'avvr_nds_only')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Сумма АВВР процента НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_pir' && auth('site')->user()->can('contract-col-price_pir:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_pir']) ? $listWidth['price_pir'] : '110'}}px;"
										class="sort{{$sortField == 'price_pir' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_pir')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Сумма ПИР без НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_pir_nds' && auth('site')->user()->can('contract-col-price_pir_nds:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_pir_nds']) ? $listWidth['price_pir_nds'] : '110'}}px;"
										class="sort{{$sortField == 'price_pir_nds' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_pir_nds')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Сумма ПИР с НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'pir_nds_only' && auth('site')->user()->can('contract-col-pir_nds_only:site'))
									<x-table.td
										style="width:{{isset($listWidth['pir_nds_only']) ? $listWidth['pir_nds_only'] : '110'}}px;"
										class="sort{{$sortField == 'pir_nds_only' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'pir_nds_only')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Сумма ПИР процента НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_smr' && auth('site')->user()->can('contract-col-price_smr:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_smr']) ? $listWidth['price_smr'] : '110'}}px;"
										class="sort{{$sortField == 'price_smr' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_smr')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Сумма СМР без НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'price_pnr' && auth('site')->user()->can('contract-col-price_pnr:site'))
									<x-table.td
										style="width:{{isset($listWidth['price_pnr']) ? $listWidth['price_pnr'] : '110'}}px;"
										class="sort{{$sortField == 'price_pnr' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'price_pnr')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Сумма ПНР без НДС</strong>
									</x-table.td>
								@endif
								
								@if($column == 'date_report_from' && auth('site')->user()->can('contract-col-date_report_from:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'date_report_from' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_report_from')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Отчет-ный период с</strong>
									</x-table.td>
								@endif
								
								@if($column == 'date_start' && auth('site')->user()->can('contract-col-date_start:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'date_start' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_start')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										>
										<strong class="fz10px lh90 d-block text-center wodrbreak">Дата подписания договора</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'date_end' && auth('site')->user()->can('contract-col-date_end:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'date_end' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_end')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										>
										<strong class="fz10px lh90 d-block text-center wodrbreak">Дата окончания работ по договору</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'date_gen_start' && auth('site')->user()->can('contract-col-date_gen_start:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'date_gen_start' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_gen_start')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										>
										<strong class="fz10px lh90 d-block text-center wodrbreak">Дата подписания генподрядного договора</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'date_gen_end' && auth('site')->user()->can('contract-col-date_gen_end:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'date_gen_end' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_gen_end')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										>
										<strong class="fz10px lh90 d-block text-center wodrbreak">Дата окончания работ по генподрядному договору</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'date_sub_start' && auth('site')->user()->can('contract-col-date_sub_start:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'date_sub_start' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_sub_start')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										>
										<strong class="fz10px lh90 d-block text-center wodrbreak">Дата подписания субподрядного договора</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'date_sub_end' && auth('site')->user()->can('contract-col-date_sub_end:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'date_sub_end' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_sub_end')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										>
										<strong class="fz10px lh90 d-block text-center wodrbreak">Дата окончания работ по субподрядному договору</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'hoz_method' && auth('site')->user()->can('contract-col-hoz_method:site'))
									<x-table.td
										class="w3rem sort{{$sortField == 'hoz_method' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'hoz_method')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Хоз способ</strong>
									</x-table.td>
								@endif
								
								@if($column == 'subcontracting' && auth('site')->user()->can('contract-col-subcontracting:site'))
									<x-table.td
										class="w3rem sort{{$sortField == 'subcontracting' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'subcontracting')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Субподряд</strong>
									</x-table.td>
								@endif
								
								@if($column == 'gencontracting' && auth('site')->user()->can('contract-col-gencontracting:site'))
									<x-table.td
										class="w3rem sort{{$sortField == 'gencontracting' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'gencontracting')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Генподряд</strong>
									</x-table.td>
								@endif
								
								@if($column == 'gen_percent' && auth('site')->user()->can('contract-col-gen_percent:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'gen_percent' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'gen_percent')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Генподрядный процент</strong>
									</x-table.td>
								@endif
								
								@if($column == 'type' && auth('site')->user()->can('contract-col-type:site'))
									<x-table.td
										style="width:{{isset($listWidth['type']) ? $listWidth['type'] : '80'}}px;"
										class="sort{{$sortField == 'type' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'type')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Тип договора</strong>
									</x-table.td>
								@endif
								
								@if($column == 'contractor' && auth('site')->user()->can('contract-col-contractor:site'))
									<x-table.td
										style="width:{{isset($listWidth['contractor']) ? $listWidth['contractor'] : '100'}}px;"
										class="sort{{$sortField == 'contractor' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'contractor')"
										contextmenu="contractFilterBy:{{$column}}"
										noscroll
										ddrtabletdmain
										>
										<strong class="fz10px lh90 d-block text-center wodrbreak">Исполнитель</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'buy_number' && auth('site')->user()->can('contract-col-buy_number:site'))
									<x-table.td
										class="w7rem sort{{$sortField == 'buy_number' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'buy_number')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Номер закупки</strong>
									</x-table.td>
								@endif
								
								@if($column == 'date_buy' && auth('site')->user()->can('contract-col-date_buy:site'))
									<x-table.td
										class="w7rem sort{{$sortField == 'date_buy' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_buy')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Дата закупки</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'date_close' && auth('site')->user()->can('contract-col-date_close:site'))
									<x-table.td
										class="w6rem sort{{$sortField == 'date_close' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'date_close')"
										oncontextmenu="$.contractFilterByDate('{{$column}}')"
										noscroll
										ddrtabletdmain
										>
										<strong class="fz10px lh90 d-block text-center wodrbreak">Дата закрытия договора</strong>
										@if(isset($columnFilter) && in_array($column, $columnFilter))
											<div class="placer placer-bottom placer-center">
												<i onclick="$.cancelContractFilter(event, '{{$column}}')" class="fa-solid fa-filter-circle-xmark fa-fw color-orange color-orange-hovered mb4px fz14px"></i>
											</div>
										@endif
									</x-table.td>
								@endif
								
								@if($column == 'archive_dir' && auth('site')->user()->can('contract-col-archive_dir:site'))
									<x-table.td
										style="width:{{isset($listWidth['archive_dir']) ? $listWidth['archive_dir'] : '100'}}px;"
										class="sort{{$sortField == 'archive_dir' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'archive_dir')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Архивная папка</strong>
									</x-table.td>
								@endif
								
								@if($column == 'power' && auth('site')->user()->can('contract-col-power:site'))
									<x-table.td
										class="w7rem sort{{$sortField == 'power' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'power')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak">Мощность кВт</strong>
									</x-table.td>
								@endif
								
							@empty	
							@endforelse

							
								
							
							{{-- @if($selectionEdited || ($selectionEdited && $searched))
								<x-table.td class="w7rem h-center" ddrtabletdmain>
									<strong class="fz10px lh90 d-block text-center wodrbreak">Удалить из подборки</strong>
								</x-table.td>
							@elseif($searched)
								<x-table.td class="w15rem h-center" ddrtabletdmain>
									<strong class="fz10px lh90 d-block text-center wodrbreak">Добавить в подборку</strong>
								</x-table.td>
							@else
								@isset($departmentId)
									@cananydo('contract-col-hiding:site, contract-col-sending:site')
										<x-table.td class="w11rem h-center" ddrtabletdmain>
											<strong class="fz10px lh90 d-block text-center wodrbreak">Действия</strong>
										</x-table.td>
									@endcananydo
								@elseif(!$isArchive)
									@cananydo('contract-col-sending-all:site, contract-col-to-archive:site')
										<x-table.td class="w11rem h-center" ddrtabletdmain>
											<strong class="fz10px lh90 d-block text-center wodrbreak">Действия</strong>
										</x-table.td>
									@endcananydo
								@elseif($isArchive)
									@cando('contract-col-return-to-work:site')
										<x-table.td class="w7rem h-center" ddrtabletdmain>
											<strong class="fz10px lh90 d-block text-center wodrbreak">Действия</strong>
										</x-table.td>
									@endcando
								@endif
							@endif --}}
							
							
							
							
							
							{{-- Departments titles --}}
							@forelse($alldeps as $dept)
								@if(!count($dept['steps']))
									@continue
								@endif
								
								<x-table.td class="w2rem nopadding bg-white" ddrtabletdmain></x-table.td>
								
								<x-table.td
									style="width: {{array_sum(array_column($dept['steps']->toArray() ?? [], 'width'))}}px;"
									class="v-end nopadding"
									>
									
									<div
										class="d-flex align-items-end justify-content-center h3rem text-center border-bottom border-gray-200"
										>
										<strong
											class="fz13px lh100 uppercase mb6px"
											>{{$dept['name']}}
										</strong>
									</div>
										
									
									
									<div
										@class([
											'd-flex',
											'h'.($rowHeight - 3).'rem',
										])
										style="margin-left: -1px;"
										>
										@forelse($dept['steps'] as $step)
											<div
												@class([
													'border-left' => !$loop->first,
													'border-gray-200' => !$loop->first,
													'd-flex align-items-center justify-content-center',
													'w8rem' => ($step['type'] == 1 && !$step['width']),
													'w16rem' => (in_array($step['type'], [2,3]) && !$step['width']),
													'w15rem' => (in_array($step['type'], [4]) && !$step['width']),
													'pl5px',
													'pr5px',
													'sort',
													'sort-'.$sortOrder => $sortField == 'step:'.$step['id']
												])
												style="width:{{$step['width'] ? $step['width'].'px' : 'auto'}};"
												onclick="$.sorting(this, 'step:{{$step['id']}}')"
												ddrtabletdmain
												>
												<p class="fz10px lh90 text-center breakword">{{$step['name']}}</p>
											</div>	
										@empty	
										@endforelse
									</div>
									
								</x-table.td>
							@empty	
							@endforelse
						</x-table.tr>
					</x-table.head>
					
					
					
					
					
					
					<x-table.body style="max-height: calc(100vh - {{$selectionId ? '294px' : '274px'}});" id="contractsList">
						@foreach ($list as $contract)
							<x-table.tr
								class="h5rem-4px"
								ondblclick="$.openContractInfo(this, '{{$contract['id']}}');"
								isnew="{{$contract['is_new'] ? 1 : 0}}"
								contractid="{{$contract['id']}}"
								contextmenu="contractContextMenu:{{$contract['id'] ?? ''}},{{$departmentId ?: '0'}},{{$selectionId ?: '0'}},{{$contract['object_number'] ?? ''}},{{$contract['title'] ?? ''}},{{$contract['has_deps_to_send'] ? '1' : '0'}},{{$contract['messages_count'] ?? '0'}},{{$rules ?? ''}},{{!!$contract['pinned'] ?: 0}}"
								>
								<div class="icon icon-left ml1px" pinnedicon>
									@if($contract['pinned'])
										<i
											class="fz10px fa-solid fa-thumbtack fa-rotate-by color-gray-500"
											style="--fa-rotate-angle: -40deg;"
											noscroll
											title="Закрепить договор">
										</i>
									@endif
								</div>
								@include('site.section/contracts.render.row_common', $contract)
								@include('site.section/contracts.render.row_departments', compact('contract', 'alldeps'))
							</x-table.tr>
						@endforeach
					</x-table.body>
					
				</x-table>
			</x-horisontal.item>
		</x-horisontal>
	@endif
@elseif(!isset($append) || !$append)
	<div class="h5rem-4px d-flex align-items-center justify-content-center">
		<div class="text-center">
			<p class="color-light">Нет данных</p>
			@if(isset($columnFilter) && $columnFilter)
				<p class="color-blue pointer fz12px mt5px" onclick="$.cancelContractFilter(event)">Очистить фильтр</p>
			@endif
		</div>
	</div>
@endisset