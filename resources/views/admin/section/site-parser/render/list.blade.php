@forelse($list as $row)
	<x-table.tr class="h4rem{{$row['chat'] ? ' is_chating' : ''}}">
		<x-table.td><p noscroll class="fz12px">{{$row['company'] ?? '-'}}</p></x-table.td>
		<x-table.td>
			<div class="d-flex align-items-center h3rem-2px">
				<div class="scrollblock scrollblock-hidescroll minh-1rem-4px maxh3rem-2px w100">
					@foreach(explode(',', $row['site']) as $k => $link)
						@if($k == 0)
							<input type="hidden" value="{{trim($link)}}" siteurl>
						@endif
						<p noscroll class="fz12px lh90 breakword"><a href="{{trim($link)}}" target="_blnk">{{trim($link)}}</a></p>
					@endforeach
				</div>
			</div>
		</x-table.td>
		<x-table.td>
			<div class="d-flex align-items-center h3rem-2px">
				<div class="scrollblock scrollblock-hidescroll minh-1rem-4px maxh3rem-2px w100">
					<p noscroll class="fz12px lh90 breakword">{{$row['subject']['subject'] ?? '-'}}</p>
				</div>
			</div>
		</x-table.td>
		<x-table.td>
			<div class="scrollblock scrollblock-hidescroll minh-1rem-4px maxh3rem-2px w100">
				@foreach(explode(',', $row['whatsapp']) as $wp)
					<p noscroll class="fz12px lh90 breakword">{{$wp}}</p>
				@endforeach
			</div>
		</x-table.td>
		<x-table.td><p noscroll class="fz12px lh90 breakword">{{$row['telegram'] ?? '-'}}</p></x-table.td>
		<x-table.td>
			<div class="scrollblock scrollblock-hidescroll minh-1rem-4px maxh3rem-2px w100">
				@foreach(explode(',', $row['phone']) as $ph)
					<p noscroll class="fz12px lh90 breakword">{{$ph}}</p>
				@endforeach
			</div>
		</x-table.td>
		<x-table.td>
			<div class="d-flex align-items-center h3rem-2px">
				<div class="scrollblock scrollblock-hidescroll minh-1rem-4px maxh3rem-2px w100">
					@foreach(explode(',', $row['email']) as $eml)
						<p noscroll class="fz12px lh90 breakword">{{$eml}}</p>
					@endforeach
				</div>
			</div>
		</x-table.td>
		<x-table.td class="h-center">
			<x-buttons-group group="small" gx="5" inline>
				<x-button variant="yellow" action="openSite:{{$row['id']}},{{$row['site'] ?? '-'}}" title="Посмотреть сайт"><i class="fa-solid fa-fw fa-link"></i></x-button>
				@if($stat == 0 || $stat == 'banned')
					<x-button variant="blue" action="processContact:{{$row['id']}},valid" title="Утвердить"><i class="fa-solid fa-fw fa-check"></i></x-button>
				@endif
				
				@if($stat == 0 || $stat == 'valid')
					<x-button variant="red" action="processContact:{{$row['id']}},banned" title="Отклонить"><i class="fa-solid fa-fw fa-ban"></i></x-button>
				@endif
				
				@if($stat == 'valid')
					<x-button variant="purple" action="processContact:{{$row['id']}},chat" siteurl="sdfsdfsdf" title="Чат"><i class="fa-solid fa-fw fa-comment"></i></x-button>
				@endif
			</x-buttons-group>
		</x-table.td>
	</x-table.tr>
@empty
	<x-table.tr class="h4rem align-items-center justify-content-center">
		<p class="color-gray">Нет данных</p>
	</x-table.tr>
@endforelse