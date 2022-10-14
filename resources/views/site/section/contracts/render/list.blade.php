@isset($list)
<x-horisontal space="2rem" scroll="false" ignore="[noscroll], select, input, textarea">
	<x-horisontal.item class="h100">
		
		{{-- <div class="table table_fixwidth border-all border-light border-2px border-rounded-3px h100"> --}}
		<x-table
			scrollsync="contractslistscroll"
			>
			<x-table.head scrollfix>
				<x-table.tr
					class="h{{$rowHeight ?? '7'}}rem"
					sorts
					>
					@if(!$isArchive)
						@if(auth('site')->user()->can('contract-col-period:site') && (empty($userColums) || in_array('period', $userColums)))
							<x-table.td
								class="w6rem sort{{$sortField == 'deadline_color_key' ? ' sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'deadline_color_key')"
								noscroll
								><strong class="fz10px lh90 d-block text-center" title="Срок исполнения договора">Срок исполн. договора</strong>
							</x-table.td>
						@endif
					@endif
					
					@if(auth('site')->user()->can('contract-col-object_number:site') && (empty($userColums) || in_array('object_number', $userColums)))
						<x-table.td
							class="w7rem sort{{$sortField == 'object_number' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'object_number')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Номер объекта</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-title:site') && (empty($userColums) || in_array('title', $userColums)))
						<x-table.td
							style="width:{{isset($listWidth['title']) ? $listWidth['title'] : '300'}}px;"
							class="sort{{$sortField == 'title' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'title')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Название</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-applicant:site') && (empty($userColums) || in_array('applicant', $userColums)))
						<x-table.td
							style="width:{{isset($listWidth['applicant']) ? $listWidth['applicant'] : '100'}}px;"
							class="sort{{$sortField == 'applicant' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'applicant')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Заявитель</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-titul:site') && (empty($userColums) || in_array('titul', $userColums)))
						<x-table.td
							style="width:{{isset($listWidth['titul']) ? $listWidth['titul'] : '200'}}px;"
							class="sort{{$sortField == 'titul' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'titul')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Титул</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-contract:site') && (empty($userColums) || in_array('contract', $userColums)))
						<x-table.td
							style="width:{{isset($listWidth['contract']) ? $listWidth['contract'] : '100'}}px;"
							class="sort{{$sortField == 'contract' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'contract')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Номер договора</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-customer:site') && (empty($userColums) || in_array('customer', $userColums)))
						<x-table.td
							style="width:{{isset($listWidth['customer']) ? $listWidth['customer'] : '150'}}px;"
							class="sort{{$sortField == 'customer' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'customer')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Заказчик</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-locality:site') && (empty($userColums) || in_array('locality', $userColums)))
						<x-table.td
							style="width:{{isset($listWidth['locality']) ? $listWidth['locality'] : '150'}}px;"
							class="sort{{$sortField == 'locality' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'locality')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Населенный пункт</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-price:site') && (empty($userColums) || in_array('price', $userColums)))
						<x-table.td
							style="width:{{isset($listWidth['price']) ? $listWidth['price'] : '110'}}px;"
							class="sort{{$sortField == 'price' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'price')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Стоимость договора</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-date_start:site') && (empty($userColums) || in_array('date_start', $userColums)))
						<x-table.td
							class="w6rem sort{{$sortField == 'date_start' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'date_start')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Дата начала договора</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-date_end:site') && (empty($userColums) || in_array('date_end', $userColums)))
						<x-table.td
							class="w6rem sort{{$sortField == 'date_end' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'date_end')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Дата окончания договора</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-hoz_method:site') && (empty($userColums) || in_array('hoz_method', $userColums)))
						<x-table.td
							class="w3rem sort{{$sortField == 'hoz_method' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'hoz_method')"
							noscroll
							><strong class="fz10px lh90 d-block text-center wodrbreak">Хоз способ</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-subcontracting:site') && (empty($userColums) || in_array('subcontracting', $userColums)))
						<x-table.td
							class="w3rem sort{{$sortField == 'subcontracting' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'subcontracting')"
							noscroll
							><strong class="fz10px lh90 d-block text-center wodrbreak">Субподряд</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-type:site') && (empty($userColums) || in_array('type', $userColums)))
						<x-table.td
							style="width:{{isset($listWidth['type']) ? $listWidth['type'] : '80'}}px;"
							class="sort{{$sortField == 'type' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'type')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Тип договора</strong>
						</x-table.td>
					@endif
					
					@if(auth('site')->user()->can('contract-col-contractor:site') && (empty($userColums) || in_array('contractor', $userColums)))
						<x-table.td
							style="width:{{isset($listWidth['contractor']) ? $listWidth['contractor'] : '100'}}px;"
							class="sort{{$sortField == 'contractor' ? ' sort-'.$sortOrder : ''}}"
							onclick="$.sorting(this, 'contractor')"
							noscroll
							><strong class="fz10px lh90 d-block text-center">Исполнитель</strong>
						</x-table.td>
					@endif
					
					@if($selectionEdited || ($selectionEdited && $searched))
						<x-table.td class="w7rem h-center">
							<strong class="fz10px lh90 d-block text-center">Удалить из подборки</strong>
						</x-table.td>
					@elseif($searched)
						<x-table.td class="w15rem h-center">
							<strong class="fz10px lh90 d-block text-center">Добавить в подборку</strong>
						</x-table.td>
					@else
						@isset($departmentId)
							@cananydo('contract-col-hiding:site, contract-col-sending:site')
								<x-table.td class="w11rem h-center">
									<strong class="fz10px lh90 d-block text-center">Действия</strong>
								</x-table.td>
							@endcananydo
						@elseif(!$isArchive)
							@cananydo('contract-col-sending-all:site, contract-col-to-archive:site')
								<x-table.td class="w11rem h-center">
									<strong class="fz10px lh90 d-block text-center">Действия</strong>
								</x-table.td>
							@endcananydo
						@elseif($isArchive)
							@cando('contract-col-return-to-work:site')
								<x-table.td class="w7rem h-center">
									<strong class="fz10px lh90 d-block text-center">Действия</strong>
								</x-table.td>
							@endcando
						@endif
					@endif
				</x-table.tr>
			</x-table.head>
			<x-table.body style="height: calc(100vh - 364px);" id="contractsListAppend">
				@foreach ($list as $item)
					@include('site.section/contracts.render.item', $item)
				@endforeach
			</x-table.body>
		</x-table>
		{{-- </div> --}}
	</x-horisontal.item>
	
	
		
	

	@forelse($alldeps as $dept)
		@if(!count($dept['steps']))
			@continue
		@endif
		<x-horisontal.item class="h100">
			{{-- <div class="table table_fixwidth border-all border-gray border-2px border-rounded-3px h100"> --}}
				<x-table
					scrollsync="contractslistscroll"
					>
					<x-table.head scrollfix>
						<x-table.tr class="h3rem">
							<x-table.td
								style="width: {{array_sum(array_column($dept['steps']->toArray() ?? [], 'width'))}}px;"
								class="pt4px h-center"
								>
								<strong
									class="fz13px lh100 d-block text-center uppercase mt5px"
									>{{$dept['name']}}
								</strong>
							</x-table.td>
						</x-table.tr>
						
						
						@if($dept['steps'])
							<x-table.tr
								@class([
									'h'.($rowHeight - 3).'rem'
								])
								main
								>
								@forelse($dept['steps'] as $step)
									<x-table.td
										style="width:{{$step['width'] ? $step['width'].'px' : 'auto'}};"
										@class([
											'w8rem' => ($step['type'] == 1 && !$step['width']),
											'w16rem' => (in_array($step['type'], [2,3]) && !$step['width']),
											'w15rem' => (in_array($step['type'], [4]) && !$step['width']),
											'pl5px',
											'pr5px',
											'sort',
											'sort-'.$sortOrder => $sortField == 'step:'.$step['id']
										])
										onclick="$.sorting(this, 'step:{{$step['id']}}')"
										>
										<p class="fz10px lh90 text-center breakword">{{$step['name']}}</p>
									</x-table.td>
								@empty	
								@endforelse
							</x-table.tr>
						@endif
					</x-table.head>
					
					
					<x-table.body
						style="height: calc(100vh - 364px);"
						departmentappend="{{$dept['id']}}"
						>
						@if($list)
							@foreach ($list as $contract)
								<x-table.tr class="h5rem-4px">
									@foreach($dept['steps'] as $step)
										@isset($contract['departments'][$dept['id']]['steps'][$step['id']])
											@if($step['type'] == 1)
												<x-table.td
													class="h-center"
													style="{{!($contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? false) ? 'background-color: '.$contract['departments'][$dept['id']]['steps'][$step['id']]['color'] ?? null : 'tranparent'}};"
													deadlinecolor="{{$contract['departments'][$dept['id']]['steps'][$step['id']]['color'] ?? null}}"
													>
													@if($edited)
														<x-checkbox
															group="normal"
															name="assigned_primary"
															:checked="$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? null"
															action="contractSetData:{{$contract['id']}},{{$dept['id']}},{{$step['id']}},{{$step['type']}}"
															/>
													@else
														@isset($contractdata[$contract['id']][$dept['id']][$step['id']]['data'])
															@if($contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? null)
																<i class="fa-solid fa-square-check color-green fz19px"></i>
															@else
																<div class="checkbox-empty checkbox-empty-normal border-gray-400"></div>
															@endif
														@else
															<div class="checkbox-empty checkbox-empty-normal border-gray-400"></div>
														@endisset
													@endif
												</x-table.td>
											@elseif($step['type'] == 2)
												<x-table.td
													{{-- @if(!$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? true)
													style="background-color: {{$contract['departments'][$dept['id']]['steps'][$step['id']]['color']}};"
													@endif --}}
													>
													@if($edited)
														<x-textarea
															group="small"
															class="w100 h100"
															rows="2"
															:value="$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? null"
															action="contractSetData:{{$contract['id']}},{{$dept['id']}},{{$step['id']}},{{$step['type']}}"
															/>
													@else
														<div class="scrollblock scrollblock-auto h5rem pr3px fz10px">
															{{$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? null}}
														</div>
													@endif
												</x-table.td>
											@elseif($step['type'] == 3)
												<x-table.td
													{{-- @if(!$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? true)
													style="background-color: {{$contract['departments'][$dept['id']]['steps'][$step['id']]['color']}};"
													@endif --}}
													>
													@if($edited)
														<x-select
															group="small"
															:options="$deps_users[$dept['id']] ?? null"
															:value="$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? null"
															class="w100"
															choose="Сотрудник не выбран"
															empty-has-value
															choose-empty
															action="contractSetData:{{$contract['id']}},{{$dept['id']}},{{$step['id']}},{{$step['type']}}"
															/>
													@else
														@isset($contractdata[$contract['id']][$dept['id']][$step['id']]['data'])
															<p class="fz12px lh100">{{$deps_users[$dept['id']][$contractdata[$contract['id']][$dept['id']][$step['id']]['data']] ?? null}}</p>
														@endisset
													@endif
												</x-table.td>
											@elseif($step['type'] == 4)
												<x-table.td
													{{-- @if(!$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? true)
													style="background-color: {{$contract['departments'][$dept['id']]['steps'][$step['id']]['color']}};"
													@endif --}}
													class="h-end"
													>
													@if($edited)
														<x-input
															group="small"
															class="w100"
															:value="$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? null"
															action="contractSetData:{{$contract['id']}},{{$dept['id']}},{{$step['id']}},{{$step['type']}}"
															tag="stepprice"
															icon="ruble-sign"
															/>
													@else
														<div class="text-end">
															@isset($contractdata[$contract['id']][$dept['id']][$step['id']]['data'])
																<p>@number($contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? null, 2) @symbal(money)</p>
															@endif
														</div>
													@endif
												</x-table.td>
											@endif
										@else
											<x-table.td class="center"></x-table.td>
										@endisset
									@endforeach
								</x-table.tr>
							@endforeach
						@endif
					</x-table.tbody>
					
				</x-table>
			{{-- </div> --}}
		</x-horisontal.item>
	@empty	
	@endforelse

		
	
	
	

</x-horisontal>

@else
	<div class="h6rem d-flex align-items-center justify-content-center"><p class="color-light">Нет данных</p></div>
@endisset


<script type="module">
	scrollSync('contractslistscroll');
</script>