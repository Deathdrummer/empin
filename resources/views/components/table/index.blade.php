@props([
	'id'    => 'ddrtable'.rand(0,9999999),
	'scrollsync' =>  null,
	'scrollstart' =>  null,
	'scrollend' =>  null,
])


<div
	class="ddrtable"
	id="{{$id}}"
	ddrtable
	scrollsync="{{$scrollsync}}"
	>
	{{$slot}}
</div>


<script type="module">
	const selector = $('#{{$id}}'),
		headCells = $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletdmain]').length
			? $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletdmain]')
			: $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletd]'),
		bodyRows = $(selector).find('[ddrtablebody] [ddrtabletr]'),
		scrollstartObserver = '{{$scrollstart}}',
		scrollendObserver = '{{$scrollend}}';
	
	
	
	let cellsWidths = [];
	$(headCells).each(function(index, cell) {
		let width = Math.max($(cell).width(), $(cell)[0].offsetWidth, $(cell)[0].clientWidth, $(cell).outerWidth());
		cellsWidths.push(width);
	});
	
	
	if (cellsWidths) {
		$(bodyRows).each(function(_, row) {
			$.each(cellsWidths, function(index, width) {
				$(row).find('[ddrtabletd]:eq('+index+')').css('width', width+'px');
			});
			$(row).addClass('ddrtable__tr_visible');
		});
	} else {
		$(bodyRows).find('[ddrtabletd]').css('width', (100 / headCells.length)+'%');
	}
		
	
		
	/*$(headCells).each(function(index, cell) {
		let width = Math.max($(cell).width(), $(cell)[0].offsetWidth, $(cell)[0].clientWidth, $(cell).outerWidth());
		
		if (width) {
			$(bodyRows).find('[ddrtabletd]:eq('+index+')').css('width', width+'px');
		} else {
			$(bodyRows).find('[ddrtabletd]').css('width', (100 / headCells.length)+'%');
		}
	});
	
	$(selector).find('[ddrtablebody]:not(.ddrtable__body_visible)').addClass('ddrtable__body_visible');*/
	
	
	
	
	//-----------------------------------------------------------------------------------------------------------
	
	// наблюдение за скроллом в начало или конец списка
	let observer = new IntersectionObserver(function (entries) {
		let target = entries[0];
		
		if (target?.isIntersecting) { // вход
			let targetType = target?.target?.id;
			if (targetType == 'intersectionTop') {
				$['{{$scrollstart}}'](target);
			} else if (targetType == 'intersectionBottom') {
				$['{{$scrollend}}'](target);
			}
		}
		
	}, {
		threshold: 1.0,
		root: document.getElementById('contractsList'),
		rootMargin: '50px',
	});
	
	
	if (scrollstartObserver) {
		$(selector).find('[ddrtablebody]').one('scrollstop', {latency: 20}, function() {
			observer.observe(document.querySelector('#intersectionTop'));
		});
	} 
	
	if (scrollendObserver) observer.observe(document.querySelector('#intersectionBottom'));
	
</script>