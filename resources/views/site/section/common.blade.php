<section>
	<x-card
		id="testCard"
		>
		
		
		
		<x-table>
			<x-table.head>
				<x-table.tr>
					<x-table.td class="w20rem"><p>thead 1</p></x-table.td>
					<x-table.td><p>thead 2</p></x-table.td>
					<x-table.td><p>thead 3</p></x-table.td>
				</x-table.tr>
			</x-table.head>
			<x-table.body>
				@for ($i = 0; $i < 10; $i++)
				<x-table.tr>
					<x-table.td><p>body 1</p></x-table.td>
					<x-table.td><p>body 2</p></x-table.td>
					<x-table.td><p>body 3</p></x-table.td>
				</x-table.tr>
				@endfor
			</x-table.body>
			<x-table.foot></x-table.foot>
		</x-table>
		
		
		
	
	
	</x-card>
</section>