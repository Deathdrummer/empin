@forelse($list as $row)
	<x-table.tr class="h4rem">
		<x-table.td><p class="fz12px">{{$row['company'] ?? '-'}}</p></x-table.td>
		<x-table.td>
			<div class="d-flex align-items-center h3rem-2px">
				<div class="scrollblock scrollblock-hidden minh-1rem-4px maxh3rem-2px w100">
					<p class="fz12px lh90 breakword">
						@foreach(explode(',', $row['site']) as $link)
							<a href="{{trim($link)}}" target="_blnk">{{trim($link)}}</a>
						@endforeach
					</p>
				</div>
			</div>
		</x-table.td>
		<x-table.td>
			<div class="d-flex align-items-center h3rem-2px">
				<div class="scrollblock scrollblock-hidden minh-1rem-4px maxh3rem-2px w100">
					<p class="fz12px lh90 breakword">{{$row['subject']['subject'] ?? '-'}}</p>
				</div>
			</div>
		</x-table.td>
		<x-table.td><p class="fz12px lh90 breakword">{{$row['whatsapp'] ?? '-'}}</p></x-table.td>
		<x-table.td><p class="fz12px lh90 breakword">{{$row['telegram'] ?? '-'}}</p></x-table.td>
		<x-table.td><p class="fz12px lh90 breakword">{{$row['phone'] ?? '-'}}</p></x-table.td>
		<x-table.td>
			<div class="d-flex align-items-center h3rem-2px">
				<div class="scrollblock scrollblock-hidden minh-1rem-4px maxh3rem-2px w100">
					<p class="fz12px lh90 breakword">{{$row['email'] ?? '-'}}</p>
				</div>
			</div>
		</x-table.td>
		<x-table.td class="h-center">
			<x-buttons-group group="small" gx="5" inline>
				<x-button variant="yellow" action="openSite:{{$row['id']}},{{$row['site'] ?? '-'}}" title="Посмотреть сайт"><i class="fa-solid fa-fw fa-link"></i></x-button>
				<x-button variant="blue" action="processContact:{{$row['id']}},valid" title="Утвердить"><i class="fa-solid fa-fw fa-check"></i></x-button>
				<x-button variant="red" action="processContact:{{$row['id']}},banned" title="Отклонить"><i class="fa-solid fa-fw fa-ban"></i></x-button>
			</x-buttons-group>
		</x-table.td>
	</x-table.tr>
@empty
	<x-table.tr class="h4rem align-items-center justify-content-center">
		<p class="color-gray">Нет данных</p>
	</x-table.tr>
@endforelse