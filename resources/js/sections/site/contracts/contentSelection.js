export function contentSelection() {
	
	let selectedRowStart, selectedRowEnd, selectedColStart, selectedColEnd, isActiveSelecting = false, cells = [], otherItemsCount;
	
	$('#contractsTable').on('mousedown', function(e) {
		const {isShiftKey, isCtrlKey, isCommandKey, isAltKey, isOptionKey, noKeys, isActiveKey} = metaKeys(e);
		const {isLeftClick, isRightClick, isCenterClick} = mouseClick(e);
		const cell = $(e.target).closest('[ddrtabletd]');
		const row = $(e.target).closest('[ddrtabletr]');
		
		if (isAltKey && $(e.target).hasAttr('noselectcell')) return;
		
		otherItemsCount = $(cell).prevAll(':not([ddrtabletd])').length;
		
		if (isLeftClick) {
			if (isAltKey) {
				isActiveSelecting = true;
				
				selectedColEnd = getColIndex(cell);
				selectedRowEnd = $(row).index();
				
				if (!selectedColStart || !selectedRowStart) {
					selectedColStart = getColIndex(cell);
					selectedRowStart = $(row).index();
					
					$(cell).find('[edittedplace]:not(.select-text)').addClass('select-text');
					
					selectionAction();
				
				} else {
					$(cell).find('[edittedplace].select-text').removeClass('select-text');
					removeSelection();
					
					if (getColIndex(cell) !== -1 || $(row).index() !== -1) {
						if (selectedColEnd !== getColIndex(cell)) selectedColEnd = getColIndex(cell);
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
			
			if (isAltKey
				&& getColIndex(selectedCol) !== -1
				&& $(selectedRow).index() !== -1
				&& ((getColIndex(selectedCol) !== -1 && selectedColEnd !== getColIndex(selectedCol))
				|| ($(selectedRow).index() !== -1 && selectedRowEnd !== $(selectedRow).index()))) {
				if (selectedColEnd !== getColIndex(selectedCol)) selectedColEnd = getColIndex(selectedCol);
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
			$.notify('Скопировано!', {autoHideDelay: 2000});
		}	
	}, () => !!$('#contractsTable').find('[ddrtabletd].selected').length);
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//-----------------------------------------------------------------------------------
	
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
	
	
	function getColIndex(selector) {
		const i = $(selector).index() ;
		if ([0, -1].indexOf(i) !== -1) return i;
		return i - otherItemsCount > 0 ? i - otherItemsCount : 0;
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
}