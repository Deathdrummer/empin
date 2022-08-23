<tr class="h6rem">
	@if(!$isArchive)
		@cando('contract-col-period:site')
			<td class="center">
				@isset($color)
					<div
						class="circle d-inline-block w2rem-5px h2rem-5px"
						style="background-color: {{$color}};"
						title="{{$name}}"
						></div>
				@endisset
			</td>
		@endcando
	@endif
	
	@cando('contract-col-object_id:site')
		<td><strong class="fz14px">{{$object_id}}</strong></td>
	@endcando
	
	@cando('contract-col-title:site')
		<td><p>{{$title}}</p></td>
	@endcando
	
	@cando('contract-col-titul:site')
		<td class="pr2px">
			<div class="scrollblock scrollblock-auto h5rem pr3px">
				<p class="format fz12px">{{$titul}}</p>
			</div>
		</td>
	@endcando
	
	@cando('contract-col-contract:site')
		<td><p>{{$contract}}</p></td>
	@endcando
	
	@cando('contract-col-subcontracting:site')
		<td class="center">
			@if($subcontracting)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</td>
	@endcando
	
	@cando('contract-col-customer:site')
		<td><p>{{$customers[$customer]}}</p></td>
	@endcando
	
	@cando('contract-col-locality:site')
		<td><p>{{$localities[$locality]}}</p></td>
	@endcando
	
	@cando('contract-col-price:site')
		<td class="text-end"><p>@number($price, 2) @symbal(money)</p></td>
	@endcando
	
	@cando('contract-col-date_start:site')
		<td>
			<p>{{dateFormatter($date_start, 'd.m.y')}}</p>
		</td>
	@endcando
	
	@cando('contract-col-date_end:site')
		<td>
			<p>{{dateFormatter($date_end, 'd.m.y')}}</p>
		</td>
	@endcando
	
	@cando('contract-col-hoz_method:site')
		<td class="center">
			@if($hoz_method)
				<i class="fa-solid fa-circle-check color-green fz16px"></i>
			@endif
		</td>
	@endcando
	
	@cando('contract-col-type:site')
		<td><p>{{$types[$type]}}</p></td>
	@endcando
	
	@cando('contract-col-contractor:site')
		<td><p>{{$contractors[$contractor]}}</p></td>
	@endcando
	
	
	
	@if($searched)
		<td>
			<p>search</p>
		</td>
	@else
		@isset($departmentId)
			@cananydo('contract-col-hiding:site, contract-col-sending:site')
				<td class="center">
					<x-buttons-group group="verysmall" w="2rem-7px" gx="5" px="1" tag="noscroll">
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