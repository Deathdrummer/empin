@isset($list)
<x-horisontal space="2rem" ignore="[noscroll], select, input, textarea">
	<x-horisontal.item class="h100">
		<div class="table table_inline border-all border-light border-2px border-rounded-3px h100">
			<table>
				<thead>
					<tr class="h7rem" sorts>
						@if(!$isArchive)
							@cando('contract-col-period:site')
								<td
									class="w6rem sort {{$sortField == 'date_end' ? 'sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'date_end')"
									noscroll
									><strong class="fz10px lh90 d-block mt2px" title="Срок исполнения договора">Срок исполн. договора</strong></td>
							@endcando
						@endif
						
						@cando('contract-col-object_id:site')
							<td
								class="w6rem sort {{$sortField == 'id' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'id')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Номер объекта</strong></td>
						@endcando
						
						@cando('contract-col-title:site')
							<td
								class="w6rem sort {{$sortField == 'title' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'title')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Название / заявитель</strong></td>
						@endcando
						
						@cando('contract-col-titul:site')
							<td
								class="w20rem sort {{$sortField == 'titul' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'titul')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Титул</strong></td>
						@endcando
						
						@cando('contract-col-contract:site')
							<td
								class="sort {{$sortField == 'contract' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'contract')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Номер договора</strong></td>
						@endcando
						
						@cando('contract-col-subcontracting:site')
							<td
								class="sort {{$sortField == 'subcontracting' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'subcontracting')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px wodrbreak">Субподряд</strong></td>
						@endcando
						
						@cando('contract-col-customer:site')
							<td
								class="sort {{$sortField == 'customer' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'customer')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Заказчик</strong></td>
						@endcando
						
						@cando('contract-col-locality:site')
							<td
								class="sort {{$sortField == 'locality' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'locality')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Населенный пункт</strong></td>
						@endcando
						
						@cando('contract-col-price:site')
							<td
								class="sort {{$sortField == 'price' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'price')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Стоимость договора</strong></td>
						@endcando
						
						@cando('contract-col-date_start:site')
							<td
								class="w6rem sort {{$sortField == 'date_start' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'date_start')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Дата начала договора</strong></td>
						@endcando
						
						@cando('contract-col-date_end:site')
							<td
								class="w6rem sort {{$sortField == 'date_end' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'date_end')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Дата окончания договора</strong></td>
						@endcando
						
						@cando('contract-col-hoz_method:site')
							<td
								class="w4rem sort {{$sortField == 'hoz_method' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'hoz_method')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px wodrbreak">Хоз способ</strong></td>
						@endcando
						
						@cando('contract-col-type:site')
							<td
								class="w5rem sort {{$sortField == 'type' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'type')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Тип договора</strong></td>
						@endcando
						
						@cando('contract-col-contractor:site')
							<td
								class="sort {{$sortField == 'contractor' ? 'sort-'.$sortOrder : ''}}"
								onclick="$.sorting(this, 'contractor')"
								noscroll
								><strong class="fz10px lh90 d-block mt2px">Исполнитель</strong></td>
						@endcando
						
						@isset($departmentId)
							@cananydo('contract-col-hiding:site, contract-col-sending:site')
								<td class="w8rem"><strong class="fz10px lh90 d-block mt2px">Действия</strong></td>
							@endcananydo
						@elseif(!$isArchive)
							@cananydo('contract-col-sending-all:site, contract-col-to-archive:site')
								<td class="w8rem"><strong class="fz10px lh90 d-block mt2px">Действия</strong></td>
							@endcananydo
						@endif
					</tr>
				</thead>
				<tbody>
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
				<table class="">
					<thead class="h7rem">
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
											'w20rem' => (in_array($step['type'], [2,3])),
											'w15rem' => (in_array($step['type'], [4])),
											'pl3px',
											'pr3px'
										])>
										<p class="fz10px lh90">{{$step['name']}}</p>
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
												<td class="center" style="background-color: {{$contract['departments'][$dept['id']]['steps'][$step['id']]['color']}};">
													@if($edited)
														<x-checkbox
															group="normal"
															name="assigned_primary"
															:checked="$contractdata[$contract['id']][$dept['id']][$step['id']]['data'] ?? null"
															action="contractSetData:{{$contract['id']}},{{$dept['id']}},{{$step['id']}},{{$step['type']}}"
															/>
													@else
														@isset($contractdata[$contract['id']][$dept['id']][$step['id']]['data'])
															@if($contractdata[$contract['id']][$dept['id']][$step['id']]['data'])
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
												<td style="background-color: {{$contract['departments'][$dept['id']]['steps'][$step['id']]['color']}};">
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
												<td style="background-color: {{$contract['departments'][$dept['id']]['steps'][$step['id']]['color']}};">
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
												<td style="background-color: {{$contract['departments'][$dept['id']]['steps'][$step['id']]['color']}};">
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