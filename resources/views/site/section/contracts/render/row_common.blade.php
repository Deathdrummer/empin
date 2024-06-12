@forelse($userColums as $column)
	@if($column == 'period' && !$isArchive && auth('site')->user()->can('contract-col-period:site'))
		<x-table.td class="h-center" commonlist style="background-color: {{$selected_color.'75' ?? ''}};">
				{{-- <i
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
					></i> --}}
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
		<x-table.td class="h-center" commonlist onmouseenter="$.showselections('{{$id}}');" style="background-color: {{$selected_color.'75' ?? ''}};">
			<strong class="fz16px">{{$object_number ?? '-'}}</strong>
		</x-table.td>
	@endif

	@if($column == 'title' && auth('site')->user()->can('contract-col-title:site'))
		<x-table.td class="breakword h-start" commonlist contextedit="{{$id}},{{$column}},1" style="background-color: {{$selected_color.'75' ?? ''}};">
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="fz12px lh100 mt2px mb2px" edittedplace="-">{{Str::of($title ?? '-')->limit(60, '...')}}</p>
			</div>
		</x-table.td>
	@endif

	@if($column == 'applicant' && auth('site')->user()->can('contract-col-applicant:site'))
		<x-table.td class="breakword h-start" commonlist contextedit="{{$id}},{{$column}},1" style="background-color: {{$selected_color.'75' ?? ''}};">
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="fz12px lh100 mt2px mb2px" edittedplace="-">{{$applicant ?? '-'}}</p>
			</div>
		</x-table.td>
	@endif

	@if($column == 'titul' && auth('site')->user()->can('contract-col-titul:site'))
		<x-table.td class="pr2px breakword" commonlist contextedit="{{$id}},{{$column}},1" style="background-color: {{$selected_color.'75' ?? ''}};">
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="format fz12px lh100 mt2px mb2px text-justify" edittedplace="-">{{$titul ?? '-'}}</p>
			</div>
		</x-table.td>
	@endif

	@if($column == 'contract' && auth('site')->user()->can('contract-col-contract:site'))
		<x-table.td class="breakword" commonlist contextedit="{{$id}},{{$column}},1" style="background-color: {{$selected_color.'75' ?? ''}};">
			<p class="fz12px lh110" edittedplace="-">{{$contract ?? '-'}}</p>
		</x-table.td>
	@endif

	@if($column == 'customer' && auth('site')->user()->can('contract-col-customer:site'))
		<x-table.td class="breakword" commonlist contextedit="{{$id}},{{$column}},4" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($customer) && isset($customers[$customer]))
				<p class="fz12px lh90" edittedplace>{{Str::of($customers[$customer])->limit(60, '...')}}</p>
			@else
				<p class="fz12px" edittedplace>-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'locality' && auth('site')->user()->can('contract-col-locality:site'))
		<x-table.td class="breakword h-center" commonlist contextedit="{{$id}},{{$column}},1" style="background-color: {{$selected_color.'75' ?? ''}};">
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="fz12px lh100 mt2px mb2px" edittedplace="-">{{$locality ?? '-'}}</p>
			</div>
		</x-table.td>
	@endif
	
	@if($column == 'date_send_action' && auth('site')->user()->can('contract-col-date_send_action:site'))
		<x-table.td commonlist contextedit="{{$id}},{{$column}},3" editacts="date_send_action" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_send_action) && $date_send_action)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_send_action}}">{{dateFormatter($date_send_action, 'd.m.y')}}</p>
			@else
				<p class="fz12px" edittedplace="-">-</p>
			@endif
		</x-table.td>
	@endif
	
	@if($column == 'count_ks_2' && auth('site')->user()->can('contract-col-count_ks_2:site'))
		<x-table.td class="breakword h-center" commonlist contextedit="{{$id.','.$column.','.'1'}}" editacts="count_ks_2" style="background-color: {{$selected_color.'75' ?? ''}};">
			<p class="fz12px" edittedplace="-" val="{{$count_ks_2 ?? '-'}}">{{$count_ks_2 ?? '-'}}</p>
		</x-table.td>
	@endif
	
	@if($column == 'act_pir' && auth('site')->user()->can('contract-col-act_pir:site'))
		<x-table.td class="h-center" commonlist contextedit="{{$id}},{{$column}},4" editacts="act_pir" style="background-color: {{$selected_color.'75' ?? ''}};">
			<div edittedplace="">
				@if($act_pir)
					<i class="fa-solid fa-circle-check color-green fz16px"></i>
				@endif
			</div>
		</x-table.td>
	@endif
	
	@if($column == 'price' && auth('site')->user()->can('contract-col-price:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">@number($price, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">-</span> <strong hidden>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_nds' && auth('site')->user()->can('contract-col-price_nds:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_nds)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_nds|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">@number($price_nds, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_nds|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">-</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif

	@if($column == 'price_gen' && auth('site')->user()->can('contract-col-price_gen:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_gen)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_gen|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">@number($price_gen, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_gen|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">{{!($subcontracting ?? 0) ? 'НЕТ' : '-'}}</span> <strong hidden>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_gen_nds' && auth('site')->user()->can('contract-col-price_gen_nds:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_gen_nds)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_gen_nds|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">@number($price_gen_nds, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_gen_nds|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">{{!($subcontracting ?? 0) ? 'НЕТ' : '-'}}</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_sub' && auth('site')->user()->can('contract-col-price_sub:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_sub)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_sub|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">@number($price_sub, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_sub|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">{{!($gencontracting ?? 0) ? 'НЕТ' : '-'}}</span> <strong hidden>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_sub_nds' && auth('site')->user()->can('contract-col-price_sub_nds:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_sub_nds)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_sub_nds|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">@number($price_sub_nds, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_sub_nds|{{$gen_percent ?? 0}}|{{$subcontracting ?? 0}}|{{$gencontracting ?? 0}}">{{!($gencontracting ?? 0) ? 'НЕТ' : '-'}}</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_avvr' && auth('site')->user()->can('contract-col-price_avvr:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_avvr)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_avvr">@number($price_avvr, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_avvr">-</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_avvr_nds' && auth('site')->user()->can('contract-col-price_avvr_nds:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_avvr_nds)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_avvr_nds">@number($price_avvr_nds, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_avvr_nds">-</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'avvr_nds_only' && auth('site')->user()->can('contract-col-avvr_nds_only:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($avvr_nds_only)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="avvr_nds_only">@number($avvr_nds_only, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="avvr_nds_only">-</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_pir' && auth('site')->user()->can('contract-col-price_pir:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_pir)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_pir">@number($price_pir, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_pir">-</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_pir_nds' && auth('site')->user()->can('contract-col-price_pir_nds:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_pir_nds)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_pir_nds">@number($price_pir_nds, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_pir_nds">-</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'pir_nds_only' && auth('site')->user()->can('contract-col-pir_nds_only:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($pir_nds_only)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="pir_nds_only">@number($pir_nds_only, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="pir_nds_only">-</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_smr' && auth('site')->user()->can('contract-col-price_smr:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_smr)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_smr">@number($price_smr, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_smr">-</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'price_pnr' && auth('site')->user()->can('contract-col-price_pnr:site'))
		<x-table.td class="text-end" commonlist contextedit="{{$id}},{{$column}},2" style="background-color: {{$selected_color.'75' ?? ''}};">
			@isset($price_pnr)
				<p class="fz12px lh90 nobreak"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_pnr">@number($price_pnr, 2)</span> <strong>@symbal(money)</strong></p>
			@else
				<p class="fz12px"><span class="fz12px" edittedplace="0.00" replacer r-right calcprice="price_pnr">-</span> <strong hidden hiddenplace>@symbal(money)</strong></p>
			@endisset
		</x-table.td>
	@endif
	
	@if($column == 'date_report_from' && auth('site')->user()->can('contract-col-date_report_from:site'))
		<x-table.td commonlist contextedit="{{$id}},{{$column}},3" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_report_from) && $date_report_from)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_report_from}}">{{dateFormatter($date_report_from, 'd.m.y')}}</p>
			@else
				<p class="fz12px lh90" edittedplace="-">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'date_start' && auth('site')->user()->can('contract-col-date_start:site'))
		<x-table.td commonlist contextedit="{{$id}},{{$column}},3" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_start) && $date_start)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_start}}">{{dateFormatter($date_start, 'd.m.y')}}</p>
			@else
				<p class="fz12px" edittedplace="-">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'date_end' && auth('site')->user()->can('contract-col-date_end:site'))
		<x-table.td commonlist contextedit="{{$id}},{{$column}},3" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_end) && $date_end)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_end}}">{{dateFormatter($date_end, 'd.m.y')}}</p>
			@else
				<p class="fz12px" edittedplace="-">-</p>
			@endif
		</x-table.td>
	@endif
	
	@if($column == 'date_gen_start' && auth('site')->user()->can('contract-col-date_gen_start:site'))
		<x-table.td commonlist contextedit="{{!isset($date_gen_start) && !$subcontracting ? '' : $id.','.$column.','.'3'}}" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_gen_start) && $date_gen_start)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_gen_start}}">{{dateFormatter($date_gen_start, 'd.m.y')}}</p>
			@else
				@if($subcontracting)
					<p class="fz12px" edittedplace="-">-</p>
				@else
					<p class="fz12px text-center" edittedplace="-">НЕТ</p>
				@endif
			@endif
		</x-table.td>
	@endif

	@if($column == 'date_gen_end' && auth('site')->user()->can('contract-col-date_gen_end:site'))
		<x-table.td commonlist contextedit="{{!isset($date_gen_end) && !$subcontracting ? '' : $id.','.$column.','.'3'}}" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_gen_end) && $date_gen_end)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_gen_end}}">{{dateFormatter($date_gen_end, 'd.m.y')}}</p>
			@else
				@if($subcontracting)
					<p class="fz12px" edittedplace="-">-</p>
				@else
					<p class="fz12px text-center" edittedplace="-">НЕТ</p>
				@endif
			@endif
		</x-table.td>
	@endif
	
	@if($column == 'date_sub_start' && auth('site')->user()->can('contract-col-date_sub_start:site'))
		<x-table.td commonlist contextedit="{{!isset($date_sub_start) && !$gencontracting ? '' : $id.','.$column.','.'3'}}" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_sub_start) && $date_sub_start)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_sub_start}}">{{dateFormatter($date_sub_start, 'd.m.y')}}</p>
			@else
				@if($gencontracting)
					<p class="fz12px" edittedplace="-">-</p>
				@else
					<p class="fz12px text-center" edittedplace="-">НЕТ</p>
				@endif
			@endif
		</x-table.td>
	@endif

	@if($column == 'date_sub_end' && auth('site')->user()->can('contract-col-date_sub_end:site'))
		<x-table.td commonlist contextedit="{{!isset($date_sub_end) && !$gencontracting ? '' : $id.','.$column.','.'3'}}" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_sub_end) && $date_sub_end)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_sub_end}}">{{dateFormatter($date_sub_end, 'd.m.y')}}</p>
			@else
				@if($gencontracting)
					<p class="fz12px" edittedplace="-">-</p>
				@else
					<p class="fz12px text-center" edittedplace="-">НЕТ</p>
				@endif
			@endif
		</x-table.td>
	@endif

	@if($column == 'hoz_method' && auth('site')->user()->can('contract-col-hoz_method:site'))
		<x-table.td class="h-center" commonlist style="background-color: {{$selected_color.'75' ?? ''}};">
			@if($hoz_method)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</x-table.td>
	@endif

	@if($column == 'subcontracting' && auth('site')->user()->can('contract-col-subcontracting:site'))
		<x-table.td class="h-center" commonlist style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($subcontracting) && $subcontracting)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</x-table.td>
	@endif
	
	@if($column == 'gencontracting' && auth('site')->user()->can('contract-col-gencontracting:site'))
		<x-table.td class="h-center" commonlist style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($gencontracting) && $gencontracting)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</x-table.td>
	@endif
	
	@if($column == 'gen_percent' && auth('site')->user()->can('contract-col-gen_percent:site'))
		<x-table.td class="h-center" commonlist style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($gen_percent) && $gen_percent)
				<p class="fz12px" edittedplace="-">{{$gen_percent ?? '-'}}</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'type' && auth('site')->user()->can('contract-col-type:site'))
		<x-table.td class="h-center breakword" commonlist contextedit="{{$id}},{{$column}},4" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($type) && isset($types[$type]))
				<p class="fz12px lh110" edittedplace="-">{{$types[$type]}}</p>
			@else
				<p class="fz12px" edittedplace="-">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'contractor' && auth('site')->user()->can('contract-col-contractor:site'))
		<x-table.td class="h-center breakword" commonlist contextedit="{{$id}},{{$column}},4" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($contractor) && isset($contractors[$contractor]))
				<p class="fz12px lh90" edittedplace="-">{{$contractors[$contractor]}}</p>
			@else
				<p class="fz12px" edittedplace="-">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'buy_number' && auth('site')->user()->can('contract-col-buy_number:site'))
		<x-table.td class="breakword h-center" commonlist contextedit="{{$buy_number == 'БЕЗ ЗАКУПКИ' ? '' : $id.','.$column.','.'1'}}" style="background-color: {{$selected_color.'75' ?? ''}};">
			<p class="fz12px" edittedplace="-" buynumber="{{$buy_number ?? null}}" noselectcell>{{$buy_number ?? '-'}}</p>
		</x-table.td>
	@endif

	@if($column == 'date_buy' && auth('site')->user()->can('contract-col-date_buy:site'))
		<x-table.td commonlist contextedit="{{!isset($date_buy) && $without_buy ? '' : $id.','.$column.','.'3'}}" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_buy) && $date_buy)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_buy}}">{{dateFormatter($date_buy, 'd.m.y')}}</p>
			@else
				@if(!$without_buy)
					<p class="fz12px" edittedplace="-">-</p>
				@else
					<p class="fz12px text-center" edittedplace="-">БЕЗ ЗАКУПКИ</p>
				@endif
			@endif
		</x-table.td>
	@endif

	@if($column == 'date_close' && auth('site')->user()->can('contract-col-date_close:site'))
		<x-table.td commonlist contextedit="{{$id}},{{$column}},3" style="background-color: {{$selected_color.'75' ?? ''}};">
			@if(isset($date_close) && $date_close)
				<p class="fz12px lh90" edittedplace="-" date="{{$date_close}}">{{dateFormatter($date_close, 'd.m.y')}}</p>
			@else
				<p class="fz12px" edittedplace="-">-</p>
			@endif
		</x-table.td>
	@endif

	@if($column == 'archive_dir' && auth('site')->user()->can('contract-col-archive_dir:site'))
		<x-table.td class="breakword h-center" commonlist contextedit="{{$id}},{{$column}},1" style="background-color: {{$selected_color.'75' ?? ''}};">
			<div class="scrollblock-hidden maxh4rem-6px pr3px">
				<p class="fz12px lh100 mt2px mb2px" edittedplace="-">{{$archive_dir ?? '-'}}</p>
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