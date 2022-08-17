
/*Добавить css стили
#модуль
	z-index: 998
	position: sticky
*/
$.fn.ddrFloatingBlock = function(mutationSelector = null) {
	
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
