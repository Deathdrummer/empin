@forelse($alldeps as $dept)
	@if(!count($dept['steps']))
		@continue
	@endif
	
	<x-table.td class="w2rem bg-white"></x-table.td>
	
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
	
	
@empty	
@endforelse