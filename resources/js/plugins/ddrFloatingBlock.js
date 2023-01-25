





$.fn.ddrFloatingBlock = function(blockSelector = null, params) {
	const containerSelector = this;
	
	const condition = ref(false);
	
	const rool = (params?.top || 0) + (params?.bottom || 0);
	
	let //containerHeight	= $(containerSelector).outerHeight(),
		blockHeight 	= $(blockSelector).outerHeight(),
		winHeight		= $(window).height() - rool,
		initialTop 		= params?.top || 0, // положение от верха
		offsetDown		= params?.bottom || 0, // промежуток до низа в конце списка
		downLimit;
	
	_setCondition();
	
	_setDownLimit();
	
	let upLimit = initialTop;
	let currentTop = Number($(window).scrollTop());
	currentTop = currentTop == 0 ? 0 : -currentTop;
	$(blockSelector).css('top', Math.max(currentTop, downLimit)+'px');
	
	$(window).ddrScroll(({dir, top, step, accumulate}) => {
		if (dir == 'down') {
			currentTop = currentTop - step < downLimit ? downLimit : currentTop - step;
			$(blockSelector).css('top', Math.max(currentTop, downLimit)+'px');
		} else if (dir == 'up') {
			currentTop = currentTop + step > upLimit ? upLimit : currentTop + step;
			$(blockSelector).css('top', Math.min(currentTop, upLimit)+'px');
		}
	}, condition);
	
	
	
	
	
	
	/*$(containerSelector).ddrWatch('resize', (data) => {
		containerHeight = data[0]?.contentRect?.height || $(containerSelector).outerHeight();
		//_setDownLimit();
		_setCondition();
	});*/
	
	$(blockSelector).ddrWatch('resize', (data) => {
		blockHeight = data[0]?.contentRect?.height || $(blockSelector).outerHeight();
		
		_setDownLimit();
		_setCondition();
		
		currentTop = initialTop;
		$(blockSelector).css('top', initialTop+'px');
	});
	
	$(window).resize(() => {
		winHeight = $(window).height() - rool;
		
		_setDownLimit();
		_setCondition();
		
		currentTop = initialTop;
		$(blockSelector).css('top', initialTop+'px');
	});
	
	
	
	
	function _setDownLimit() {
		downLimit = -blockHeight + (winHeight + initialTop) - offsetDown;
	}
	
	
	function _setCondition() {
		if (blockHeight > winHeight) {
			condition.value = true;
		} else {
			condition.value = false;
			$(blockSelector).css('top', initialTop);
		} 
	}
	
	
	
};


	











/*Добавить css стили
#модуль
	z-index: 998
	position: sticky
*/
/*$.fn.ddrFloatingBlock = function(mutationSelector = null) {
	
	let block = this,
		setParams,
		blockHeight = 0,
		blockPos = 0,
		winHeight = 0,
		scrTop,
		startScrSpace,
		scrPos,
		scrSpace;


	(setParams = function() {
		blockHeight = $(block).outerHeight();
		blockPos = $(block).offset().top;
		winHeight = $(window).height();
		startScrSpace = -(blockHeight - winHeight);
		scrPos = $(window).scrollTop();
		scrSpace = -(blockHeight - winHeight);
	})();
	
	
	if (mutationSelector) {
		let observer = new MutationObserver(mutateNav);
		observer.observe($(mutationSelector)[0], {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['class']
		});
	}
	
	


	let rsTOut;
	$(window).resize(function() {
		clearTimeout(rsTOut);
		rsTOut = setTimeout(function() {
			$(block).removeAttrib('style');
			setParams();
			if (blockPos + blockHeight <= winHeight) $(block).css('top', blockPos);
			else $(block).css('top', scrSpace);
		}, 100);
	});
	
	
	function mutateNav() {
		$(block).removeAttrib('style');
		setParams();
		if (blockPos + blockHeight <= winHeight) $(block).css('top', blockPos);
		else $(block).css('top', scrSpace);
	}
	

	if (blockPos + blockHeight <= winHeight) $(block).css('top', blockPos);
	else $(block).css('top', scrSpace);


	$(window).scroll(function() {
		if (blockPos + blockHeight <= winHeight) return false;
		scrTop = $(window).scrollTop();
		if (scrPos < scrTop) { // прокрутка вниз
			let posDiff = (scrPos - scrTop); // отрицательное
			let shift = scrSpace + posDiff;
			scrSpace = shift > startScrSpace ? shift : startScrSpace;
			$(block).css('top', scrSpace);

		} else { // прокрутка вверх
			let posDiff = (scrPos - scrTop); // положительное
			let shift = scrSpace + posDiff;
			scrSpace = shift < (blockPos) ? shift : (blockPos);
			$(block).css('top', scrSpace);
		}

		scrPos = scrTop;
	});
}
*/