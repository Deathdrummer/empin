<tr
	@class([
		'h6rem',
		'clear bg-yellow-light' => $is_new && !$isArchive
	])
	ondblclick="$.openContractInfo(this, '{{$id}}');"
	isnew="{{$is_new ? 1 : 0}}"
	contractid="{{$id}}"
	>
	@if(!$isArchive)
		@if(auth('site')->user()->can('contract-col-period:site') && (empty($userColums) || in_array('period', $userColums)))
			<td class="center">
				<div class="cell">
					<i
						onclick="$.pinContract(this, {{$id}});"
						pinned="{{$pinned ? 0 : 1}}"
						@class([
							'fz10px',
							'fa-solid',
							'fa-thumbtack',
							'fa-rotate-by',
							'icon',
							'icon-left',
							'icon-top',
							'icon-hidden' => !$pinned,
							'color-gray-500' => $pinned,
							'color-gray-300' => !$pinned,
							'color-gray-400-hovered' => !$pinned,
							'pointer',
							'mt4px',
							'ml4px'
						])
						style="--fa-rotate-angle: -40deg;"
						noscroll
						title="{{$pinned ? 'Открепить договор' : 'Закрепить договор'}}"
						></i>
					<div
						@class([
							'circle',
							'd-inline-block',
							'border-all',
							'border-gray-300' => $color_forced === null,
							'border-blue border-width-2px' => $color_forced !== null,
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
				</div>
					
			</td>
		@endif
	@endif
	
	@if(auth('site')->user()->can('contract-col-object_number:site') && (empty($userColums) || in_array('object_number', $userColums)))
		<td><strong class="fz12px">{{$object_number ?? '-'}}</strong></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-title:site') && (empty($userColums) || in_array('title', $userColums)))
		<td class="breakword"><p class="fz12px lh110">{{Str::of($title ?? '-')->limit(60, '...')}}</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-applicant:site') && (empty($userColums) || in_array('applicant', $userColums)))
		<td class="breakword"><p class="fz12px lh110">{{$applicant ?? '-'}}</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-titul:site') && (empty($userColums) || in_array('titul', $userColums)))
		<td class="pr2px breakword">
			<div class="scrollblock-hidden h5rem pr3px">
				<p class="format fz12px">{{$titul ?? '-'}}</p>
			</div>
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-contract:site') && (empty($userColums) || in_array('contract', $userColums)))
		<td class="breakword"><p class="fz12px lh110">{{$contract ?? '-'}}</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-customer:site') && (empty($userColums) || in_array('customer', $userColums)))
		<td class="breakword">
			@if(isset($customer) && isset($customers[$customer]))
				<p class="fz12px lh90">{{Str::of($customers[$customer])->limit(60, '...')}}</p>
			@else
				<p class="color-gray">-</p>
			@endif
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-locality:site') && (empty($userColums) || in_array('locality', $userColums)))
		<td class="breakword"><p class="fz12px lh90">{{$locality ?? '-'}}</p></td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-price:site') && (empty($userColums) || in_array('price', $userColums)))
		<td class="text-end">
			@isset($price)
				<p class="fz12px lh90">@number($price, 2) @symbal(money)</p>
			@else
				<p class="color-gray">-</p>
			@endisset
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-date_start:site') && (empty($userColums) || in_array('date_start', $userColums)))
		<td>
			@isset($date_start)
				<p class="fz12px lh90">{{dateFormatter($date_start, 'd.m.y')}}</p>
			@else
				<p class="color-gray">-</p>
			@endisset
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-date_end:site') && (empty($userColums) || in_array('date_end', $userColums)))
		<td>
			@isset($date_start)
				<p class="fz12px lh90">{{dateFormatter($date_end, 'd.m.y')}}</p>
			@else
				<p class="color-gray">-</p>
			@endisset
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-hoz_method:site') && (empty($userColums) || in_array('hoz_method', $userColums)))
		<td class="center">
			@if($hoz_method)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-subcontracting:site') && (empty($userColums) || in_array('subcontracting', $userColums)))
		<td class="center">
			@if(isset($subcontracting) && $subcontracting)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-type:site') && (empty($userColums) || in_array('type', $userColums)))
		<td class="center breakword">
			@if(isset($type) && isset($types[$type]))
				<p class="fz12px lh110">{{$types[$type]}}</p>
			@else
				<p class="color-gray">-</p>
			@endif
		</td>
	@endif
	
	@if(auth('site')->user()->can('contract-col-contractor:site') && (empty($userColums) || in_array('contractor', $userColums)))
		<td class="center breakword">
			@if(isset($contractor) && isset($contractors[$contractor]))
				<p class="fz12px lh90">{{$contractors[$contractor]}}</p>
			@else
				<p class="color-gray">-</p>
			@endif
		</td>
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
				empty-has-value
				action="addContractToSelection:{{$id}}"
				/>
			{{-- <x-checkbox
				group="large"
				:checked="$selected"
				action="addContractToSelection:{{$id}}"
				/> --}}
		</td>
	@else
		@if(isset($departmentId))
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
						
						@cando('contract-col-chat:site')
							<x-button
								variant="light"
								action="contractChatAction:{{$id}},{{$title ?? 'Без названия'}}"
								title="Чат договора"
								><i class="fa-solid fa-comments"></i></x-button>
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
						
						@cando('contract-col-chat:site')
							<x-button
								variant="light"
								action="contractChatAction:{{$id}},{{$title ?? 'Без названия'}}"
								title="Чат договора"
								><i class="fa-solid fa-comments"></i></x-button>
						@endcando
					</x-buttons-group>
				</td>
			@endcananydo
		@elseif($isArchive)
			@cando('contract-col-return-to-work:site')
				<td class="center">
					<x-button
						group="verysmall"
						variant="light"
						action="returnContractToWorkAction:{{$id}}"
						title="Вернуть договор в работу"
						><i class="fa-solid fa-arrow-rotate-left"></i></x-button>
				</td>
			@endcando
		@endif
	@endif
		
</tr>