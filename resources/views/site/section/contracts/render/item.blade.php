<tr class="h6rem">
	@if(!$isArchive)
		@if(auth('site')->user()->can('contract-col-period:site') && in_array('period', $userColums))
			<td class="center">
				<div
					@class([
						'circle',
						'd-inline-block',
						'border-all',
						'border-gray-300' => $color_forced === null,
						'border-green border-width-2px' => $color_forced !== null,
						'border-rounded-circle',
						'w2rem-5px',
						'h2rem-5px',
						'pointer' => auth('site')->user()->can('force-set-contract-color:site'),
					])
					@if(isset($color) || isset($color_forced)) style="background-color: {{$color_forced ?: $color}};" @endif
					title="{{$name_forced ?: $name}}"
					noscroll
					dcolor="{{$color}}"
					dname="{{$name}}"
					@cando('force-set-contract-color:site')onmousedown="$.openColorsStatuses(this, '{{$id}}')"@endcando
					></div>
			</td>
		@endif
	@endif
	
	@if(auth('site')->user()->can('contract-col-object_id:site') && in_array('object_id', $userColums))
		<td><strong class="fz14px">{{$object_id}}</strong></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-title:site') && in_array('title', $userColums))
		<td><p>{{$title}}</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-titul:site') && in_array('titul', $userColums))
		<td class="pr2px">
			<div class="scrollblock scrollblock-auto h5rem pr3px">
				<p class="format fz12px">{{$titul}}</p>
			</div>
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-contract:site') && in_array('contract', $userColums))
		<td><p>{{$contract}}</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-subcontracting:site') && in_array('subcontracting', $userColums))
		<td class="center">
			@if($subcontracting)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-customer:site') && in_array('customer', $userColums))
		<td><p>{{$customers[$customer]}}</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-locality:site') && in_array('locality', $userColums))
		<td><p>{{$localities[$locality]}}</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-price:site') && in_array('price', $userColums))
		<td class="text-end"><p>@number($price, 2) @symbal(money)</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-date_start:site') && in_array('date_start', $userColums))
		<td>
			<p>{{dateFormatter($date_start, 'd.m.y')}}</p>
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-date_end:site') && in_array('date_end', $userColums))
		<td>
			<p>{{dateFormatter($date_end, 'd.m.y')}}</p>
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-hoz_method:site') && in_array('hoz_method', $userColums))
		<td class="center">
			@if($hoz_method)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-type:site') && in_array('type', $userColums))
		<td><p>{{$types[$type]}}</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-contractor:site') && in_array('contractor', $userColums))
		<td><p>{{$contractors[$contractor]}}</p></td>
	@endif
	
	
	@if($selectionEdited || ($selectionEdited && $searched))
		<td class="center">
			<x-button
				variant="red"
				group="small"
				action="removeContractFromSelection:{{$id}},{{$selection}}"
				title="Удалить из подборки"
				><i class="fa-solid fa-trash-can"></i></x-button>
		</td>
	
	@elseif($searched)
		<td class="center">
			<x-select
				group="small"
				class="w100"
				:options="$allSelections"
				:exclude="$selections ?? []"
				choose="Выбрать..."
				empty="Нет подборок"
				no-choose-has-value
				action="addContractToSelection:{{$id}}"
				/>
			{{-- <x-checkbox
				group="large"
				:checked="$selected"
				action="addContractToSelection:{{$id}}"
				/> --}}
		</td>
	@else
		@isset($departmentId)
			@cananydo('contract-col-hiding:site, contract-col-sending:site')
				<td class="center">
					<x-buttons-group group="verysmall" w="2rem-7px" gx="5" px="1" tag="noscroll:45">
						@cando('contract-col-hiding:site')
							<x-button
								variant="light"
								action="hideContractAction:{{$id}},{{$departmentId}}"
								title="Скрыть"
								><i class="fa-solid fa-eye-slash"></i></x-button>
						@endcando
						@cando('contract-col-sending:site')
							<x-button
								variant="blue"
								action="sendContractAction:{{$id}}"
								:enabled="$has_deps_to_send ?? false"
								title="Отправить в другой отдел"
								><i class="fa-solid fa-angles-right"></i></x-button>
						@endcando
					</x-buttons-group>
				</td>
			@endcananydo
		@elseif(!$isArchive)
			@cananydo('contract-col-sending-all:site, contract-col-to-archive:site')
				<td class="center">
					<x-buttons-group group="verysmall" w="2rem-7px" gx="5" px="1" tag="noscroll">
						@cando('contract-col-to-archive:site')
							<x-button
								variant="{{$ready_to_archive ? 'green' : 'neutral'}}"
								{{-- :animation="$ready_to_archive ? 'fa-shake' : false" --}}
								animationDuration="2s"
								action="toArchiveContractAction:{{$id}}"
								title="Отправить в архив"
								><i class="fa-solid fa-box-archive"></i></x-button>
						@endcando
						@cando('contract-col-sending-all:site')
							<x-button
								variant="blue"
								action="sendContractAction:{{$id}}"
								:enabled="$has_deps_to_send ?? false"
								title="Отправить в другой отдел"
								><i class="fa-solid fa-angles-right"></i></x-button>
						@endcando
					</x-buttons-group>
				</td>
			@endcananydo
		@endisset
	@endif
		
</tr>