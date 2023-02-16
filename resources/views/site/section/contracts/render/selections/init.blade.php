{{-- <x-chooser
	class="mb2rem"
	variant="neutral"
	group="normal"
	px="10"
	>
	<x-chooser.item
		action="getListAction"
		>Активные
		</x-chooser.item>
	
	<x-chooser.item
		id="chooserArchive"
		action="getListAction:-1"
		>Архив
		</x-chooser.item>
</x-chooser>
 --}}






<x-table id="selectionsTable" class="w100" noborder>
	<x-table.head>
		<x-table.tr>
			<x-table.td class="w-auto">
				<strong class="d-block fz12px lh90">Название подборки</strong>
			</x-table.td>
			<x-table.td class="w6rem">
				<strong class="d-block fz12px lh90">Кол-во договоров</strong>
			</x-table.td>
			<x-table.td class="w16rem h-center">
				<strong class="d-block fz12px lh90">Действия со списками</strong>
			</x-table.td>
			<x-table.td class="w7rem h-center" title="Отправить сообщение в чаты договоров подборки">
				<strong class="d-block fz12px lh90">Отпр. сообщ.</strong>
			</x-table.td>
			<x-table.td class="w9rem h-center" title="Поделиться подборкой с другими сотрудниками">
				<strong class="d-block fz12px lh90">Поделиться</strong>
			</x-table.td>
			<x-table.td class="w7rem h-center" title="Отправить подборку в архив">
				<strong class="d-block fz12px lh90">В архив</strong>
			</x-table.td>
			<x-table.td class="w8rem h-center">
				<strong class="d-block fz12px lh90">Действия</strong>
			</x-table.td>
		</x-table.tr>
	</x-table.head>
	<x-table.body
		scrolled="calc(100vh - 200px)"
		id="selectionsList"
		>
	</x-table.body>
</x-table>