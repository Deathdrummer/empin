/*
	Горизонтальная прокрутка блока мышью и колесиком
		- шаг прокрутки (для колеса)
		- скорость прокрутки (для колеса)
		- разрешить прокрутку колесом
		- Игнорировать селекторы
		- добавить блок к синхронному скроллу
*/
$.fn.ddrScrollX = function(params) {
	let block = this,
	{
		scrollStep,
		scrollSpeed,
		enableMouseScroll,
		ignoreSelectors,
		addict,
		moveKey,
		ignoreMoveKeys,
	} = _.assign({
		scrollStep: 50,
		scrollSpeed: 100,
		enableMouseScroll: false,
		ignoreSelectors: false,
		addict: false,
		moveKey: false, // alt shift ctrl
		ignoreMoveKeys: [],
	}, params);
	
	if (ignoreSelectors) ignoreSelectors = _.isArray(ignoreSelectors) ? ignoreSelectors.join(', ') : ignoreSelectors;
	if (ignoreMoveKeys) ignoreMoveKeys = pregSplit(ignoreMoveKeys);
	
	
	//console.log(ignoreMoveKeys);
	
	//if (enableMouseScroll == true) {
		/*$(block).mousewheel(function(e) {
			let tag = e.target?.tagName?.toLowerCase();
			if (!ignoreSelectors || isHover(ignoreSelectors)) {
				e.preventDefault();
				$(this).stop(false, true).animate({scrollLeft: ($(this).scrollLeft() + scrollStep * -e.deltaY)}, scrollSpeed);
			}
		});*/
	//}
	
	
	
	$(block).mousedown(function(e) {
		const {isShiftKey, isCtrlKey, isCommandKey, isAltKey, isOptionKey, noKeys, isActiveKey} = metaKeys(e);
		const {isLeftClick, isRightClick, isCenterClick} = mouseClick(e);
		
		
		
		if (isLeftClick && ((moveKey && isActiveKey(moveKey)) || ((ignoreMoveKeys && !isActiveKey(ignoreMoveKeys))))) {
			if (!ignoreSelectors || isHover(ignoreSelectors) == false) {
				let startX = this.scrollLeft + e.pageX;
				$(block).mousemove(function (e) {
					$(block).css('cursor', 'e-resize');
					let pos = startX - e.pageX;
					this.scrollLeft = pos;
					if (addict) $(addict)[0].scrollLeft = pos;
					return false;
				});
			}
			
			$(block).one('mouseup', function (e) {
				if (!ignoreSelectors || isHover(ignoreSelectors) == false) {
					$(block).css('cursor', 'default');
					$(block).off("mousemove");
				}
			});
		}
		
		
		//if (!isLeftClick || (moveKey && !isActiveKey(moveKey)) || (ignoreMoveKeys && isActiveKey(ignoreMoveKeys))) return false;
		
		
		
		
		
		
	});
};