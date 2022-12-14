@forelse($userColums as $column)
	@if($column == 'period' && !$isArchive && auth('site')->user()->can('contract-col-period:site'))
		<x-table.td class="h-center" commonlist>
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
			
				
		</x-table.td>
	@endif

	@if($column == 'object_number' && auth('site')->user()->can('contract-col-object_number:site'))
		<x-table.td class="h-center" commonlist><strong class="fz16px">{{$object_number ?? '-'}}</strong></x-table.td>
	@endif

	@if($column == 'title' && auth('site')->user()->can('contract-col-title:site'))
		<x-table.td class="breakword h-start" commonlist>
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="fz12px lh100 mt2px mb2px">{{Str::of($title ?? '-')->limit(60, '...')}}</p>
			</div>
		</x-table.td>
	@endif

	@if($column == 'applicant' && auth('site')->user()->can('contract-col-applicant:site'))
		<x-table.td class="breakword h-start" commonlist>
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="fz12px lh100 mt2px mb2px">{{$applicant ?? '-'}}</p>
			</div>
		</x-table.td>
	@endif

	@if($column == 'titul' && auth('site')->user()->can('contract-col-titul:site'))
		<x-table.td class="pr2px breakword" commonlist>
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="format fz12px lh100 mt2px mb2px text-justify">{{$titul ?? '-'}}</p>
			</div>
		</x-table.td>
	@endif

	@if($column == 'contract' && auth('site')->user()->can('contract-col-contract:site'))
		<x-table.td class="breakword" commonlist><p class="fz12px lh110">{{$contract ?? '-'}}</p></x-table.td>
	@endif

	@if($column == 'customer' && auth('site')->user()->can('contract-col-customer:site'))
		<x-table.td class="breakword" commonlist>
			@if(isset($customer) && isset($customers[$customer]))
				<p class="fz12px lh90">{{Str::of($customers[$customer])->limit(60, '...')}}</p>
			@else
				<p class="color-gray">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'locality' && auth('site')->user()->can('contract-col-locality:site'))
		<x-table.td class="breakword h-center" commonlist>
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="fz12px lh100 mt2px mb2px">{{$locality ?? '-'}}</p>
			</div>
		</x-table.td>
	@endif

	@if($column == 'price_nds' && auth('site')->user()->can('contract-col-price_nds:site'))
		<x-table.td class="text-end" commonlist>
			@isset($price_nds)
				<p class="fz12px lh90 nobreak">@number($price_nds, 2) <strong>@symbal(money)</strong></p>
			@else
				<p class="color-gray">-</p>
			@endisset
		</x-table.td>
	@endif

	@if($column == 'price' && auth('site')->user()->can('contract-col-price:site'))
		<x-table.td class="text-end" commonlist>
			@isset($price)
				<p class="fz12px lh90 nobreak">@number($price, 2) <strong>@symbal(money)</strong></p>
			@else
				<p class="color-gray">-</p>
			@endisset
		</x-table.td>
	@endif

	@if($column == 'date_start' && auth('site')->user()->can('contract-col-date_start:site'))
		<x-table.td commonlist>
			@if(isset($date_start) && $date_start)
				<p class="fz12px lh90">{{dateFormatter($date_start, 'd.m.y')}}</p>
			@else
				<p class="color-gray">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'date_end' && auth('site')->user()->can('contract-col-date_end:site'))
		<x-table.td commonlist>
			@if(isset($date_end) && $date_end)
				<p class="fz12px lh90">{{dateFormatter($date_end, 'd.m.y')}}</p>
			@else
				<p class="color-gray">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'hoz_method' && auth('site')->user()->can('contract-col-hoz_method:site'))
		<x-table.td class="h-center" commonlist>
			@if($hoz_method)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</x-table.td>
	@endif

	@if($column == 'subcontracting' && auth('site')->user()->can('contract-col-subcontracting:site'))
		<x-table.td class="h-center" commonlist>
			@if(isset($subcontracting) && $subcontracting)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</x-table.td>
	@endif
	
	@if($column == 'gencontracting' && auth('site')->user()->can('contract-col-gencontracting:site'))
		<x-table.td class="h-center" commonlist>
			@if(isset($gencontracting) && $gencontracting)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</x-table.td>
	@endif

	@if($column == 'type' && auth('site')->user()->can('contract-col-type:site'))
		<x-table.td class="h-center breakword" commonlist>
			@if(isset($type) && isset($types[$type]))
				<p class="fz12px lh110">{{$types[$type]}}</p>
			@else
				<p class="color-gray">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'contractor' && auth('site')->user()->can('contract-col-contractor:site'))
		<x-table.td class="h-center breakword" commonlist>
			@if(isset($contractor) && isset($contractors[$contractor]))
				<p class="fz12px lh90">{{$contractors[$contractor]}}</p>
			@else
				<p class="color-gray">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'buy_number' && auth('site')->user()->can('contract-col-buy_number:site'))
		<x-table.td class="breakword h-center" commonlist><p class="fz12px">{{$buy_number ?? '-'}}</p></x-table.td>
	@endif

	@if($column == 'date_buy' && auth('site')->user()->can('contract-col-date_buy:site'))
		<x-table.td commonlist>
			@if(isset($date_buy) && $date_buy)
				<p class="fz12px lh90">{{dateFormatter($date_buy, 'd.m.y')}}</p>
			@else
				<p class="color-gray">{!!$without_buy ? '<p class="fz12px text-center">БЕЗ ЗАКУПКИ</p>' : '-'!!}</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'date_close' && auth('site')->user()->can('contract-col-date_close:site'))
		<x-table.td commonlist>
			@if(isset($date_close) && $date_close)
				<p class="fz12px lh90">{{dateFormatter($date_close, 'd.m.y')}}</p>
			@else
				<p class="color-gray">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'archive_dir' && auth('site')->user()->can('contract-col-archive_dir:site'))
		<x-table.td class="breakword h-center" commonlist>
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="fz12px lh100 mt2px mb2px">{{$archive_dir ?? '-'}}</p>
			</div>
		</x-table.td>
	@endif
@empty	
@endforelse



	


{{-- @if($selectionEdited || ($selectionEdited && $searched))
	<x-table.td class="h-center">
		<x-button
			variant="red"
			group="small"
			action="removeContractFromSelection:{{$id}},{{$selectionId}}"
			title="Удалить из подборки"
			><i class="fa-solid fa-trash-can"></i></x-button>
	</x-table.td>

@elseif($searched && !$selectionEdited)
	<x-table.td class="h-center">
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
	</x-table.td>
@else
	@if(isset($departmentId))
		@cananydo('contract-col-hiding:site, contract-col-sending:site')
			<x-table.td class="h-center">
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
			</x-table.td>
		@endcananydo
	@elseif(!$isArchive)
		@cananydo('contract-col-sending-all:site, contract-col-to-archive:site')
			<x-table.td class="h-center">
				<x-buttons-group group="verysmall" w="2rem-7px" gx="5" px="1" tag="noscroll">
					@cando('contract-col-to-archive:site')
						<x-button
							variant="{{$ready_to_archive ? 'green' : 'neutral'}}"
							:animation="$ready_to_archive ? 'fa-shake' : false"
							animationDuration="2s"
							action="toArchiveContractAction:{{$id}}"
							title="Отправить в архив"
							tag="toarchivedata:{{$object_number ?? '-'}},{{$title ?? '-'}}"
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
							:variant="$messages_count ? 'yellow' : 'light'"
							action="contractChatAction:{{$id}},{{$title ?? 'Без названия'}}"
							title="Чат договора"
							><i class="fa-solid fa-comments"></i></x-button>
					@endcando
				</x-buttons-group>
			</x-table.td>
		@endcananydo
	@elseif($isArchive)
		@cando('contract-col-return-to-work:site')
			<x-table.td class="h-center">
				<x-button
					group="verysmall"
					variant="light"
					action="returnContractToWorkAction:{{$id}}"
					title="Вернуть договор в работу"
					><i class="fa-solid fa-arrow-rotate-left"></i></x-button>
			</x-table.td>
		@endcando
	@endif
@endif --}}