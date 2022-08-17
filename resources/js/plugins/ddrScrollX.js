/*
	Горизонтальная прокрутка блока мышью и колесиком
		- шаг прокрутки (для колеса)
		- скорость прокрутки (для колеса)
		- разрешить прокрутку колесом
*/
$.fn.ddrScrollX = function(scrollStep, scrollSpeed, enableMouseScroll, ignoreSelectors) {
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
		if (!ignoreSelectors || isHover(ignoreSelectors) == false) {
			$(block).children().css('cursor', 'e-resize');
			var startX = this.scrollLeft + e.pageX;
			$(block).mousemove(function (e) {
				this.scrollLeft = startX - e.pageX;
				return false;
			});
		}
	});
	
	$(window).mouseup(function (e) {
		if (!ignoreSelectors || isHover(ignoreSelectors) == false) {
			$(block).children().css('cursor', 'default');
			$(block).off("mousemove");
		}
	});
};