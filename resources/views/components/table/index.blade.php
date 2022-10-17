@props([
	'id'    => 'ddrtable'.rand(0,9999999),
	'scrollsync' =>  null
])


<div class="ddrtable" id="{{$id}}" scrollsync="{{$scrollsync}}">
	{{$slot}}
</div>


<script type="module">
	const selector = $('#{{$id}}'),
		headCells = $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletdmain]').length
			? $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletdmain]')
			: $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletd]'),
		bodyCells = $(selector).find('[ddrtablebody] [ddrtabletr]');
	
	$(headCells).each(function(index, cell) {
		let width = $(cell).width(),
			offsetWidth = $(cell)[0].offsetWidth,
			clientWidth = $(cell)[0].clientWidth,
			outerWidth = $(cell).outerWidth();
		console.log(width, outerWidth, offsetWidth, clientWidth);
		
		let maxValue = Math.max(width, offsetWidth, clientWidth, outerWidth);
		if (clientWidth) $(bodyCells).find('[ddrtabletd]:eq('+index+')').css('width', maxValue+'px');
	});
	
	
	$(selector).find('[ddrtablebody]').addClass('ddrtable__body_visible');
</script>