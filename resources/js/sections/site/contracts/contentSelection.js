export function contentSelection() {
	
	let selectedRowStart, selectedRowEnd, selectedColStart, selectedColEnd, isActiveSelecting = false, cells = [];
	
	$('#contractsTable').on('mousedown', function(e) {
		const {isShiftKey, isCtrlKey, isCommandKey, isAltKey, isOptionKey, noKeys, isActiveKey} = metaKeys(e);
		const {isLeftClick, isRightClick, isCenterClick} = mouseClick(e);
		const cell = $(e.target).closest('[ddrtabletd]');
		const row = $(e.target).closest('[ddrtabletr]');
		
		if (isLeftClick) {
			if (isAltKey) {
				isActiveSelecting = true;
				
				selectedColEnd = $(cell).index();
				selectedRowEnd = $(row).index();
				
				if (!selectedColStart || !selectedRowStart) {
					selectedColStart = $(cell).index();
					selectedRowStart = $(row).index();
					
					$(cell).find('[edittedplace]:not(.select-text)').addClass('select-text');
					
					//selectionAction();
				
				} else {
					$(cell).find('[edittedplace].select-text').removeClass('select-text');
					removeSelection();
					
					if ($(cell).index() !== -1 || $(row).index() !== -1) {
						if (selectedColEnd !== $(cell).index()) selectedColEnd = $(cell).index();
						if (selectedRowEnd !== $(row).index()) selectedRowEnd = $(row).index();
						selectionAction();
					}
				}
				
			} else {
				selectedColStart = selectedColEnd = null;
				selectedRowStart = selectedRowEnd = null;
				selectionAction();
			}
		} 
		
		
		$(this).one('mouseup', function() {
			isActiveSelecting = false;
		});
	});
	
	
	
	
	$('#contractsTable').on('mousemove.contentSelection', function(e) {
		const {isLeftClick, isRightClick, isCenterClick} = mouseClick(e);
		const {isShiftKey, isCtrlKey, isCommandKey, isAltKey, isOptionKey, noKeys, isActiveKey} = metaKeys(e);
		
		const cell = $(e.target).closest('[ddrtabletd][commonlist]');
		
		if (isLeftClick && isAltKey && isActiveSelecting) {
			let selectedCol = $(e.target).closest('[ddrtabletd]'),
				selectedRow = $(e.target).closest('[ddrtabletr]');
			
			if (isAltKey && (($(selectedCol).index() !== -1 && selectedColEnd !== $(selectedCol).index() ) || ($(selectedRow).index() !== -1 && selectedRowEnd !== $(selectedRow).index()))) {
				if (selectedColEnd !== $(selectedCol).index()) selectedColEnd = $(selectedCol).index();
				if (selectedRowEnd !== $(selectedRow).index()) selectedRowEnd = $(selectedRow).index();
				selectionAction();
			}
		}
	});
	
	
	
	
	ddrCopy(() => {
		let row = null, allData = '';
		$('#contractsTable').find('[ddrtabletd][copied]').each((k, item) => {
			if (k == 0) row = $(item).closest('[ddrtabletr]')[0];
			
			if (k > 0 && row !== $(item).closest('[ddrtabletr]')[0]) {
				row = $(item).closest('[ddrtabletr]')[0];
				allData += "\n";
			} else if (k > 0) {
				allData += "\t";
			}
			
			allData += $(item).find('[edittedplace]').text();
		});
		
		let copiedData = allData.trim();
		
		if (copiedData) {
			copyStringToClipboard(copiedData);
			$.notify('Скопировано!');
		}	
	}, () => !!$('#contractsTable').find('[ddrtabletd].selected').length);
	
	
	
	
	function selectionAction() {
		unSelect($('#contractsTable').find('[ddrtabletd][commonlist]'));
		
		let minRow = Math.min(selectedRowStart, selectedRowEnd),
			maxRow = Math.max(selectedRowStart, selectedRowEnd),
			minCol = Math.min(selectedColStart, selectedColEnd),
			maxCol = Math.max(selectedColStart, selectedColEnd);
			
		cells = [];
		
		for (let r = minRow; r <= maxRow; r++) {
			let row = $('#contractsTable').find('[ddrtabletr]').eq(r);
			for (let c = minCol; c <= maxCol; c++) {
				cells.push($(row).find('[ddrtabletd][commonlist]').eq(c)[0]);
			}
		}
		
		select(cells);
	}
	
	
	
	
	
	function select(cells) {
		cells = cells.filter(item => !!item);
		if (!cells.length) return;
		
		$(cells).addClass('selected');
		$(cells).setAttrib('copied');
		//$(cells).find('[edittedplace]:not(.select-text)').addClass('select-text');	
	}
	
	
	function unSelect(selector) {
		$(selector).find('[edittedplace].select-text').removeClass('select-text');
		$(selector).removeAttrib('copied');
		$(selector).removeClass('selected');
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*$('#contractsTable').on('mousemove', '[ddrtabletd]', function(e) {
		console.log('mousemove');
		mouseMoveEvent = e;
		const {isLeftClick, isRightClick, isCenterClick} = mouseClick(e);
		
		if (isLeftClick) {
			let movedCell = $(e.target).closest('[ddrtabletd]')[0];
			
			//removeSelection();
			$('#contractsTable').find('[edittedplace].select-text').removeClass('select-text');	
			if (isAltKey && initialCell === movedCell) {
				$(e.target).addClass('select-text');
			}
			
			
			if (initialCell !== movedCell) {
				console.log('cross');
				initialCell = movedCell;
			}
		}
	});*/
		
		
		
	
	
	
	// Выделение текста в ячейках
	/*$('#contractsTable').on('mousedown', '[edittedplace]', function(e) {
		const block = this;
		const {isLeftClick, isRightClick, isCenterClick} = mouseClick(e);
		const {isShiftKey, isCtrlKey, isCommandKey, isAltKey, isOptionKey, noKeys, isActiveKey} = metaKeys(e);
		
		if (isLeftClick) {
			removeSelection();
			$('#contractsTable').find('[edittedplace].select-text').removeClass('select-text');	
			if (isAltKey) {
				$(block).addClass('select-text');
			}
		}
	});*/
	
	
	
	/*$('#contractsTable').on('mousemove', '[ddrtabletr]', function(e) {
		const {isLeftClick, isRightClick, isCenterClick} = mouseClick(e);
		const {isShiftKey, isCtrlKey, isCommandKey, isAltKey, isOptionKey, noKeys, isActiveKey} = metaKeys(e);
		
		if (isAltKey && isShiftKey && isLeftClick) {
			let edittedText = $(e.currentTarget).find('[edittedplace]');
			$(edittedText).not('.select-text').addClass('select-text');
		}
	});
	
	
	
	$('#contractsTable').on('mousemove', '[ddrtabletd]', function(e) {
		const {isLeftClick, isRightClick, isCenterClick} = mouseClick(e);
		const {isShiftKey, isCtrlKey, isCommandKey, isAltKey, isOptionKey, noKeys, isActiveKey} = metaKeys(e);
		
		if (isAltKey && isLeftClick) {
			let edittedText = $(e.target).find('[edittedplace]');
			$(edittedText).not('.select-text').addClass('select-text');
		}
	});*/
	
	
	
	
	
	
	// Снятие выделения
	/*$('#contractsTable').on(tapEvent, function(e) {
		const {isLeftClick, isRightClick, isCenterClick} = mouseClick(e);
		const {isShiftKey, isCtrlKey, isCommandKey, isAltKey, isOptionKey, noKeys, isActiveKey} = metaKeys(e);
		
		let isEdittedBlock = !!$(e.target).closest('[ddrtabletd]').find('[edittedblock]').length;
		
		if (isLeftClick && noKeys && !isEdittedBlock) {
			removeSelection();
			$('#contractsTable').find('[edittedplace].select-text').removeClass('select-text');
		} 
	});*/
}