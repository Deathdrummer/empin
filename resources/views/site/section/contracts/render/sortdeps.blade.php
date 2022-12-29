<x-table class="w100" noborder>
	<x-table.head noborder>
		<x-table.tr noborder class="h3rem">
			<x-table.td class="w4rem" noborder></x-table.td>
			<x-table.td class="w100" noborder><strong class="fz14px">Название отдела</strong></x-table.td>
		</x-table.tr>
	</x-table.head>
	<x-table.body id="contractDepsList">	
		@forelse($sortDeps as $id => $name)
			<x-table.tr sortdept="{{$id}}" class="h4rem">
				<x-table.td class="h-center" noborder><i class="fa-solid fa-fw fa-arrows-up-down color-gray fz12px"></i></x-table.td>
				<x-table.td noborder><p>{{$name}}</p></x-table.td>
			</x-table.tr>
		@empty
			<x-table.tr>
				<p class="color-gray-300">Нет данных</p>
			</x-table.tr>
		@endforelse
	</x-table.body>
</x-table>