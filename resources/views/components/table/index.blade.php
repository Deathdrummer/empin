@props([
	'id'    => 'ddrtable'.rand(0,9999999),
	
])


<div class="ddrtable" id="{{$id}}">
	{{$slot}}
</div>


<script type="module">
	const selector = $('#{{$id}}'),
		headCells = $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletd]'),
		bodyCells = $(selector).find('[ddrtablebody] [ddrtabletr]');
	
	
	$(headCells).each(function(index, cell) {
		//console.log('r');
		let width = 300; //$(cell).outerWidth();
		
		$(bodyCells).find('[ddrtabletd]:eq('+index+')').width(width);
	});
	
</script>