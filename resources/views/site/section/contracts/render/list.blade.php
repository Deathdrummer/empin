@isset($list)
	@if(isset($append) && $append)
		@foreach($list as $contract)
			<x-table.tr
				class="h5rem-4px"
				ondblclick="$.openContractInfo(this, '{{$contract['id']}}');"
				isnew="{{$contract['is_new'] ? 1 : 0}}"
				contractid="{{$contract['id']}}"
				contextmenu="contractContextMenu:{{$contract['id'] ?? ''}},{{$departmentId ?: '0'}},{{$selectionId ?: '0'}},{{$contract['object_number'] ?? ''}},{{$contract['title'] ?? ''}},{{$contract['has_deps_to_send'] ? '1' : '0'}},{{$contract['messages_count'] ?? '0'}},{{$rules}}"
				>
				@include('site.section/contracts.render.row_common', $contract)
				@include('site.section/contracts.render.row_departments', compact('contract', 'alldeps'))
			</x-table.tr>
		@endforeach
	@else
		<x-horisontal space="2rem" scroll="false" ignore="[noscroll], select, input, textarea">
			<x-horisontal.item class="h100">
				<x-table
					scrollstart="doScrollStart"
					scrollend="doScrollEnd"
					scrollpart="doScrollPart"
					>
					<x-table.head scrollfix>
						<x-table.tr
							class="{{-- showrows  --}}h{{$rowHeight ?? '7'}}rem"
							sorts
							>
							@if(!$isArchive)
								@if(auth('site')->user()->can('contract-col-period:site') && (empty($userColums) || in_array('period', $userColums)))
									<x-table.td
										class="w6rem sort{{$sortField == 'deadline_color_key' ? ' sort-'.$sortOrder : ''}}"
										onclick="$.sorting(this, 'deadline_color_key')"
										noscroll
										ddrtabletdmain
										><strong class="fz10px lh90 d-block text-center wodrbreak" title="Срок исполнения договора">Срок исполн. договора</strong>
									</x-table.td>
								@endif
							@endif
							
							@if(auth('site')->user()->can('contract-col-object_number:site') && (empty($userColums) || in_array('object_number', $userColums)))
								<x-table.td
									class="w7rem sort{{$sortField == 'object_number' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'object_number')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Номер объекта</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-title:site') && (empty($userColums) || in_array('title', $userColums)))
								<x-table.td
									style="width:{{isset($listWidth['title']) ? $listWidth['title'] : '300'}}px;"
									class="sort{{$sortField == 'title' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'title')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Название</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-applicant:site') && (empty($userColums) || in_array('applicant', $userColums)))
								<x-table.td
									style="width:{{isset($listWidth['applicant']) ? $listWidth['applicant'] : '100'}}px;"
									class="sort{{$sortField == 'applicant' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'applicant')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Заявитель</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-titul:site') && (empty($userColums) || in_array('titul', $userColums)))
								<x-table.td
									style="width:{{isset($listWidth['titul']) ? $listWidth['titul'] : '200'}}px;"
									class="sort{{$sortField == 'titul' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'titul')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Титул</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-contract:site') && (empty($userColums) || in_array('contract', $userColums)))
								<x-table.td
									style="width:{{isset($listWidth['contract']) ? $listWidth['contract'] : '100'}}px;"
									class="sort{{$sortField == 'contract' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'contract')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Номер договора</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-customer:site') && (empty($userColums) || in_array('customer', $userColums)))
								<x-table.td
									style="width:{{isset($listWidth['customer']) ? $listWidth['customer'] : '150'}}px;"
									class="sort{{$sortField == 'customer' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'customer')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Заказчик</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-locality:site') && (empty($userColums) || in_array('locality', $userColums)))
								<x-table.td
									style="width:{{isset($listWidth['locality']) ? $listWidth['locality'] : '150'}}px;"
									class="sort{{$sortField == 'locality' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'locality')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Населенный пункт</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-price:site') && (empty($userColums) || in_array('price', $userColums)))
								<x-table.td
									style="width:{{isset($listWidth['price']) ? $listWidth['price'] : '110'}}px;"
									class="sort{{$sortField == 'price' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'price')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Стоимость договора</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-date_start:site') && (empty($userColums) || in_array('date_start', $userColums)))
								<x-table.td
									class="w6rem sort{{$sortField == 'date_start' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'date_start')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Дата начала договора</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-date_end:site') && (empty($userColums) || in_array('date_end', $userColums)))
								<x-table.td
									class="w6rem sort{{$sortField == 'date_end' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'date_end')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Дата окончания договора</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-hoz_method:site') && (empty($userColums) || in_array('hoz_method', $userColums)))
								<x-table.td
									class="w3rem sort{{$sortField == 'hoz_method' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'hoz_method')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Хоз способ</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-subcontracting:site') && (empty($userColums) || in_array('subcontracting', $userColums)))
								<x-table.td
									class="w3rem sort{{$sortField == 'subcontracting' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'subcontracting')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Субподряд</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-type:site') && (empty($userColums) || in_array('type', $userColums)))
								<x-table.td
									style="width:{{isset($listWidth['type']) ? $listWidth['type'] : '80'}}px;"
									class="sort{{$sortField == 'type' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'type')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Тип договора</strong>
								</x-table.td>
							@endif
							
							@if(auth('site')->user()->can('contract-col-contractor:site') && (empty($userColums) || in_array('contractor', $userColums)))
								<x-table.td
									style="width:{{isset($listWidth['contractor']) ? $listWidth['contractor'] : '100'}}px;"
									class="sort{{$sortField == 'contractor' ? ' sort-'.$sortOrder : ''}}"
									onclick="$.sorting(this, 'contractor')"
									noscroll
									ddrtabletdmain
									><strong class="fz10px lh90 d-block text-center wodrbreak">Исполнитель</strong>
								</x-table.td>
							@endif
							
							@if($selectionEdited || ($selectionEdited && $searched))
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
							@endif
							
							
							
							
							
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
					
					
					
					
					
					
					<x-table.body style="max-height: calc(100vh - 364px);" id="contractsList">
						@foreach ($list as $contract)
							<x-table.tr
								class="h5rem-4px"
								ondblclick="$.openContractInfo(this, '{{$contract['id']}}');"
								isnew="{{$contract['is_new'] ? 1 : 0}}"
								contractid="{{$contract['id']}}"
								contextmenu="contractContextMenu:{{$contract['id'] ?? ''}},{{$departmentId ?: '0'}},{{$selectionId ?: '0'}},{{$contract['object_number'] ?? ''}},{{$contract['title'] ?? ''}},{{$contract['has_deps_to_send'] ? '1' : '0'}},{{$contract['messages_count'] ?? '0'}},{{$rules}}"
								>
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
	<div class="h5rem-4px d-flex align-items-center justify-content-center"><p class="color-light">Нет данных</p></div>
@endisset