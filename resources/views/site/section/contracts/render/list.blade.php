@isset($list)
<x-horisontal space="2rem" ignore="[noscroll], select, input, textarea">
	<x-horisontal.item class="h100">
		<div class="table table_inline border-all border-light border-2px border-rounded-3px h100">
			<table>
				<thead>
					<tr class="h{{$rowHeight ?? '7'}}rem" sorts>
						@if(!$isArchive)
							@if(auth('site')->user()->can('contract-col-period:site') && (empty($userColums) || in_array('period', $userColums)))
								<td
									class="w6rem sort {{$sortField == 'deadline_color_key' ? 'sort-'.$sortOrder : ''}} vertical-center"
									onclick="$.sorting(this, 'deadline_color_key')"
									noscroll
									><strong class="fz10px lh90 d-block text-center" title="Срок исполнения договора">Срок исполн. договора</strong></td>
							@endif
						@endif
						
						@if(auth('site')->user()->can('contract-col-object_number:site') && (empty($userColums) || in_array('object_number', $userColums)))
							<td
								class="w7rem sort {{$sortField == 'id' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'id')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Номер объекта</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-title:site') && (empty($userColums) || in_array('title', $userColums)))
							<td
								class="w15rem sort {{$sortField == 'title' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'title')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Название / заявитель</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-titul:site') && (empty($userColums) || in_array('titul', $userColums)))
							<td
								class="w20rem sort {{$sortField == 'titul' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'titul')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Титул</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-contract:site') && (empty($userColums) || in_array('contract', $userColums)))
							<td
								class="w10rem sort {{$sortField == 'contract' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'contract')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Номер договора</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-subcontracting:site') && (empty($userColums) || in_array('subcontracting', $userColums)))
							<td
								class="sort {{$sortField == 'subcontracting' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'subcontracting')"
								noscroll
								><strong class="fz10px lh90 d-block text-center wodrbreak">Субподряд</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-customer:site') && (empty($userColums) || in_array('customer', $userColums)))
							<td
								class="sort {{$sortField == 'customer' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'customer')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Заказчик</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-locality:site') && (empty($userColums) || in_array('locality', $userColums)))
							<td
								class="sort {{$sortField == 'locality' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'locality')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Населенный пункт</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-price:site') && (empty($userColums) || in_array('price', $userColums)))
							<td
								class="sort {{$sortField == 'price' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'price')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Стоимость договора</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-date_start:site') && (empty($userColums) || in_array('date_start', $userColums)))
							<td
								class="w6rem sort {{$sortField == 'date_start' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'date_start')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Дата начала договора</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-date_end:site') && (empty($userColums) || in_array('date_end', $userColums)))
							<td
								class="w6rem sort {{$sortField == 'date_end' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'date_end')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Дата окончания договора</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-hoz_method:site') && (empty($userColums) || in_array('hoz_method', $userColums)))
							<td
								class="w4rem sort {{$sortField == 'hoz_method' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'hoz_method')"
								noscroll
								><strong class="fz10px lh90 d-block text-center wodrbreak">Хоз способ</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-type:site') && (empty($userColums) || in_array('type', $userColums)))
							<td
								class="w5rem sort {{$sortField == 'type' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'type')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Тип договора</strong></td>
						@endif
						
						@if(auth('site')->user()->can('contract-col-contractor:site') && (empty($userColums) || in_array('contractor', $userColums)))
							<td
								class="sort {{$sortField == 'contractor' ? 'sort-'.$sortOrder : ''}} vertical-center"
								onclick="$.sorting(this, 'contractor')"
								noscroll
								><strong class="fz10px lh90 d-block text-center">Исполнитель</strong></td>
						@endif
						
						@if($selectionEdited || ($selectionEdited && $searched))
							<td class="w7rem vertical-center">
								<strong class="fz10px lh90 d-block text-center">Удалить из выборки</strong>
							</td>
						@elseif($searched)
							<td class="w15rem vertical-center">
								<strong class="fz10px lh90 d-block text-center">Добавить в выборку</strong>
							</td>
						@else
							@isset($departmentId)
								@cananydo('contract-col-hiding:site, contract-col-sending:site')
									<td class="w12rem vertical-center">
										<strong class="fz10px lh90 d-block text-center">Действия</strong>
									</td>
								@endcananydo
							@elseif(!$isArchive)
								@cananydo('contract-col-sending-all:site, contract-col-to-archive:site')
									<td class="w12rem vertical-center">
										<strong class="fz10px lh90 d-block text-center">Действия</strong>
									</td>
								@endcananydo
							@elseif($isArchive)
								@cando('contract-col-return-to-work:site')
									<td class="w7rem vertical-center">
										<strong class="fz10px lh90 d-block text-center">Действия</strong>
									</td>
								@endcando
							@endif
						@endif
					</tr>
				</thead>
				<tbody id="contractsList">
					@foreach ($list as $item)
						@include('site.section/contracts.render.item', $item)
					@endforeach
				</tbody>
			</table>
		</div>
	</x-horisontal.item>
	
	
		
	



		
	
	
	@forelse($alldeps as $dept)
		@if(!count($dept['steps']))
			@continue
		@endif
		<x-horisontal.item class="h100">
			<div class="table table_inline border-all border-gray border-2px border-rounded-3px h100">
				<table class="h{{$rowHeight ?? '7'}}rem">
					<thead>
						<tr>
							<td
								colspan="{{count($dept['steps'])}}"
								class="pb5px pt4px"
								>
								<strong
									class="fz13px lh100 d-block text-center uppercase"
									>{{$dept['name']}}
								</strong>
							</td>
						</tr>
						@if($dept['steps'])
							<tr>
								@forelse($dept['steps'] as $step)
									<td @class([
											'w8rem' => ($step['type'] == 1),
											'w16rem' => (in_array($step['type'], [2,3])),
											'w15rem' => (in_array($step['type'], [4])),
											'pl3px',
											'pr3px',
											'vertical-center'
										])>
										<p class="fz10px lh90 text-center">{{$step['name']}}</p>
									</td>
								@empty	
								@endforelse
							</tr>
						@endif
					</thead>
					<tbody>
						@if($list)
							@foreach ($list as $contract)
								<tr class="h6rem">
									@foreach($dept['steps'] as $step)
										@isset($contract['departments'][$dept['id']]['steps'][$step['id']])
											@if($step['type'] == 1)
												<td
													class="center"
													@if(!($contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? false))
													style="background-color: {{$contract['departments'][$dept['id']]['steps'][$step['id']]['color'] ?? null}};"
													@endif
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
												</td>
											@elseif($step['type'] == 2)
												<td
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
												</td>
											@elseif($step['type'] == 3)
												<td
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
															<p class="fz14px lh90">{{$deps_users[$dept['id']][$contractdata[$contract['id']][$dept['id']][$step['id']]['data']] ?? null}}</p>
														@endisset
													@endif
												</td>
											@elseif($step['type'] == 4)
												<td
													{{-- @if(!$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? true)
													style="background-color: {{$contract['departments'][$dept['id']]['steps'][$step['id']]['color']}};"
													@endif --}}
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
												</td>
											@endif
										@else
											<td class="center"></td>
										@endisset
									@endforeach
								</tr>
							@endforeach
						@endif
					</tbody>
				</table>
			</div>
		</x-horisontal.item>
	@empty	
	@endforelse

</x-horisontal>

@else
	<div class="h6rem d-flex align-items-center justify-content-center"><p class="color-light">Нет данных</p></div>
@endisset