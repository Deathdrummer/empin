@props([
	'id'    		=> 'ddrtable'.rand(0,9999999),
	'scrollsync' 	=>  null,
	'scrollstart' 	=>  null,
	'scrollend' 	=>  null,
	'scrollpart' 	=>  null,
	'hidescroll' 	=>  null,
])


<div
	{{$attributes->class([
		'ddrtable',
	])}}
	id="{{$id}}"
	ddrtable
	scrollsync="{{$scrollsync}}"
	role="table"
	>
	{{$slot}}
</div>


<script type="module">
	const selector = $('#{{$id}}'),
		bodyList = $(selector).find('[ddrtablebody]'),
		headCells = $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletdmain]').length
			? $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletdmain]')
			: $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletd]'),
		bodyRows = $(selector).find('[ddrtablebody] [ddrtabletr]'),
		scrollstartObserver = '{{$scrollstart}}',
		scrollendObserver = '{{$scrollend}}',
		scrollpartObserver = '{{$scrollpart}}',
		intersectionTopSelector = $(selector).find('#intersectionTop')[0],
		intersectionBottomSelector = $(selector).find('#intersectionBottom')[0],
		intersectionPartSelector = $(selector).find('[ddrtablepartend]')[0],
		cellsWidths = [];
	
	
	$(headCells).each(function(index, cell) {
		let width = Math.max($(cell).width(), $(cell)[0].offsetWidth, $(cell)[0].clientWidth, $(cell).outerWidth());
		cellsWidths.push(width);
	});
	
	
	if (cellsWidths) {
		$(bodyRows).each(function(rIndex, row) {
			$.each(cellsWidths, function(cIndex, width) {
				$(row).find('[ddrtabletd]:eq('+cIndex+')').css('width', width+'px');
			});
			$(row).addClass('ddrtable__tr_visible');
			if (bodyRows.length == rIndex + 1) $(row).setAttrib('ddrtablepartend');
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
			let targetId = target?.target?.id;
			let targetAttr = target?.target?.attributes;
			
			if ($[scrollstartObserver] && targetId == 'intersectionTop') {
				$[scrollstartObserver](target);
			} else if ($[scrollendObserver] && targetId == 'intersectionBottom') {
				$[scrollendObserver](target);
			} else if (target.target.hasAttribute("ddrtablepartend")) {
				if ($[scrollpartObserver] && $(selector).find('[ddrtablebody]')[0].offsetHeight > $(target.target).position().top) {
					$[scrollpartObserver](target);
				} else {}
			}
		}
		
	}, {
		threshold: 0.5,
		root: $(bodyList)[0],
		rootMargin: '50px',
	});
	
	
	$(selector).find('[ddrtablebody]').one('scrollstart', {latency: 20}, function() {
		if (scrollstartObserver && intersectionTopSelector) observer.observe(intersectionTopSelector);
		if (scrollendObserver && intersectionBottomSelector) observer.observe(intersectionBottomSelector);
		if (scrollpartObserver && intersectionPartSelector) observer.observe(intersectionPartSelector);
	});
	
</script>