<section contextmenu="testContextMenu:45,foo,bar">
	<x-card
		id="testCard"
		
		>
		
		
		
		<ul class="context">
			<li class="top">
				Foo
				<ul class="context sub">
					<li>sub foo</li>
					<li>sub bar</li>
					<li>sub rool</li>
				</ul>
			</li>
			<li class="hilight">Bar</li>
			<li>Rool</li>
		</ul>
		
		
		
	</x-card>
</section>


<script type="module">
	
	
	
	
	
	$(document).on('keydown', function(e) {
		console.log(e);
	}
	
	
	
	
	
	
	/*$.testContextMenu = (id, foo, bar) => {
		return [
			{
				name: 'name 1',
				faIcon: 'fa-solid fa-triangle-exclamation',
				children: [
					{
						name: 'name 1 1',
						faIcon: 'fa-solid fa-triangle-exclamation',
						callback: ({close}, id, foo, bar) => {
							console.log('callback', id, foo, bar);
						}
					}
				]
			},
			{
				name: 'name 2',
				faIcon: 'fa-solid fa-triangle-exclamation',
			},
			{
				name: 'name 3',
				faIcon: 'fa-solid fa-triangle-exclamation',
			},
			{
				name: 'name 4',
				faIcon: 'fa-solid fa-triangle-exclamation',
			}
		];
	}
	*/
</script>