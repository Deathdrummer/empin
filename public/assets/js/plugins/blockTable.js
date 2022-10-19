
window.blockTable = function(method = null, ...params) {
	const blockTableCls = new BlockTable;
	
	blockTableCls[method](...params);
}


$.fn.blockTable = function(method = null, ...params) {
	const blockTableCls = new BlockTable;
	
	blockTableCls[method](this, ...params);
}




class BlockTable {
	
	prependData(selector = false, data = false) {
		if (!selector || !data) return false;
		$(selector).find('[ddrtabletr]:first').before(data);
		this.buildTable(selector);
	}
	
	appendData(selector = false, data = false) {
		if (!selector || !data) return false;
		$(selector).find('[ddrtabletr]:last').after(data);
		this.buildTable(selector);
	}
	
	
	
	removeRowsBefore() {}
	removeRowsAfter() {}
	
	
	/*
		Синхронизация скролла нескольких таблиц
			- селектор (должен быть только атрибут)
	*/
	scrollSync(syncSelector) {
		const selector = $('['+syncSelector+']');
		let hasScrollCls = false,
			scrTop = 0;
		
		if ($(selector).length == 0) {
			throw new Error('scrollSync нет селекторов!');
			return;
		}
		
		
		
		
		
		
		$(selector).on('mouseover touchenter touchstart', function(e) {
			hasScrollCls = true;
			$(this).addClass('ddrtablebody-scrollsync');
			$(selector).not(this).removeClass('ddrtablebody-scrollsync');
		});
		
		
		
		
		$(selector).scroll(function() {
			if (!hasScrollCls && $(selector).filter('.ddrtablebody-scrollsync').length == 0) {
				$(this).addClass('ddrtablebody-scrollsync');
			}
			
			if ($(this).hasClass('ddrtablebody-scrollsync') == false) return;
			scrTop = $(this).scrollTop();
			$(selector).not('.ddrtablebody-scrollsync').scrollTop(scrTop);
		});
		
		
		
		$(selector).on('scrollstop', {latency: 30}, function() {
			scrTop = $(this).scrollTop();
			$(selector).scrollTop(scrTop);
		});
	}
	
	
	
	
	
	
	
	
	
	
	buildTable(listSelector) {
		const selector = $(listSelector).closest('[ddrtable]'),
			headCells = $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletdmain]').length
				? $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletdmain]')
				: $(selector).find('[ddrtablehead]').find('[ddrtabletr]').find('[ddrtabletd]'),
			bodyRows = $(selector).find('[ddrtablebody] [ddrtabletr]'),
			scrollstartObserver = '{{$scrollstart}}',
			scrollendObserver = '{{$scrollend}}';
		
		const cellsWidths = [];
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
	}
}