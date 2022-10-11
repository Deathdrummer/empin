<section>
	<x-card
		id="testCard"
		>
		
		
		
		<x-horisontal space="2rem" scroll="false">
			<x-horisontal.item>
				<x-table>
					<x-table.head>
						<x-table.tr>
							<x-table.td style="width: 250px;"><p>thead 1</p></x-table.td>
							<x-table.td style="width: 300px;"><p>thead 2</p></x-table.td>
							<x-table.td style="width: 130px;"><p>thead 3</p></x-table.td>
						</x-table.tr>
					</x-table.head>
					<x-table.body class="h30rem">
						@for ($i = 0; $i < 10; $i++)
						<x-table.tr class="h5rem">
							<x-table.td><p>body 1</p></x-table.td>
							<x-table.td><p>body 2</p></x-table.td>
							<x-table.td><p>body 3</p></x-table.td>
						</x-table.tr>
						@endfor
					</x-table.body>
					<x-table.foot></x-table.foot>
				</x-table>
			</x-horisontal.item>
			
			<x-horisontal.item>
				<x-table>
					<x-table.head>
						<x-table.tr>
							<x-table.td style="width: 250px;"><p>thead 1</p></x-table.td>
							<x-table.td style="width: 300px;"><p>thead 2</p></x-table.td>
							<x-table.td style="width: 130px;"><p>thead 3</p></x-table.td>
						</x-table.tr>
					</x-table.head>
					<x-table.body class="h30rem">
						@for ($i = 0; $i < 10; $i++)
						<x-table.tr class="h5rem">
							<x-table.td><p>body 1</p></x-table.td>
							<x-table.td><p>body 2</p></x-table.td>
							<x-table.td><p>body 3</p></x-table.td>
						</x-table.tr>
						@endfor
					</x-table.body>
					<x-table.foot></x-table.foot>
				</x-table>
			</x-horisontal.item>
			
			<x-horisontal.item>
				<x-table>
					<x-table.head>
						<x-table.tr>
							<x-table.td style="width: 250px;"><p>thead 1</p></x-table.td>
							<x-table.td style="width: 300px;"><p>thead 2</p></x-table.td>
							<x-table.td style="width: 130px;"><p>thead 3</p></x-table.td>
						</x-table.tr>
					</x-table.head>
					<x-table.body class="h30rem">
						@for ($i = 0; $i < 10; $i++)
						<x-table.tr class="h5rem">
							<x-table.td><p>body 1</p></x-table.td>
							<x-table.td><p>body 2</p></x-table.td>
							<x-table.td><p>body 3</p></x-table.td>
						</x-table.tr>
						@endfor
					</x-table.body>
					<x-table.foot></x-table.foot>
				</x-table>
			</x-horisontal.item>
			
			<x-horisontal.item>
				<x-table>
					<x-table.head>
						<x-table.tr>
							<x-table.td style="width: 250px;"><p>thead 1</p></x-table.td>
							<x-table.td style="width: 300px;"><p>thead 2</p></x-table.td>
							<x-table.td style="width: 130px;"><p>thead 3</p></x-table.td>
						</x-table.tr>
					</x-table.head>
					<x-table.body class="h30rem">
						@for ($i = 0; $i < 10; $i++)
						<x-table.tr class="h5rem">
							<x-table.td><p>body 1</p></x-table.td>
							<x-table.td><p>body 2</p></x-table.td>
							<x-table.td><p>body 3</p></x-table.td>
						</x-table.tr>
						@endfor
					</x-table.body>
					<x-table.foot></x-table.foot>
				</x-table>
			</x-horisontal.item>
		</x-horisontal>
		
		
		
	
	</x-card>
</section>




<script type="module">
	
	$('[ddrtablebody]').on('mouseover', function() {
		$(this).addClass('ddrtablebody-hovered');
		$('[ddrtablebody]').not(this).removeClass('ddrtablebody-hovered');
	});
	
	
	
	
	$('[ddrtablebody]').scroll(function() {
		if ($(this).hasClass('ddrtablebody-hovered') == false) return;
		let scrTop = $(this).scrollTop();
		$('[ddrtablebody]').not('.ddrtablebody-hovered').scrollTop(scrTop);
	});
	
	
</script>