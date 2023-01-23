/*
	Горизонтальная прокрутка блока мышью и колесиком
		- шаг прокрутки (для колеса)
		- скорость прокрутки (для колеса)
		- разрешить прокрутку колесом
		- Игнорировать селекторы
		- добавить блок к синхронному скроллу
*/
$.fn.ddrScrollX = function(scrollStep, scrollSpeed, enableMouseScroll, ignoreSelectors, addict) {
	var block = this,
		scrollStep = scrollStep || 50,
		scrollSpeed = scrollSpeed || 100,
		ignoreSelectors = _.isArray(ignoreSelectors) ? ignoreSelectors.join(', ') : ignoreSelectors;
	
	
	if (enableMouseScroll != undefined && enableMouseScroll == true) {
		$(block).mousewheel(function(e) {
			let tag = e.target?.tagName?.toLowerCase();
			if (!ignoreSelectors || isHover(ignoreSelectors)) {
				e.preventDefault();
				$(this).stop(false, true).animate({scrollLeft: ($(this).scrollLeft() + scrollStep * -e.deltaY)}, scrollSpeed);
			}
		});
	}
	
	
	
	
	$(block).mousedown(function(e) {
		if ([2, 3].indexOf(e.which) !== -1 || (e.altKey == true || e.metaKey == true)) {
			e.stopPropagation();
			$(e.target).css('user-select', 'text');
			return;
		} 
		
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
		
	});
	
	$(block).mouseup(function (e) {
		if (e.altKey == true || e.metaKey == true) {
			const selObj = window.getSelection();
			const selectString =  selObj.toString();
			if (selectString.length) {
				copyStringToClipboard(selObj.toString());
				$.notify('Скопировано!');
			}
			return;
		}
		
		if (!ignoreSelectors || isHover(ignoreSelectors) == false) {
			$(block).css('cursor', 'default');
			$(block).off("mousemove");
		}
	});
};