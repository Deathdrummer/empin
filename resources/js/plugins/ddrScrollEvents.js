/*
	Все эвенты при событии скролла
		params
			- onScrollStart: событие начала скроллинга
			- onScrollStop: событие окончания скроллинга
			- onScrollTop: события достижения верхней точки скроллинга
			- onScrollBottom: события достижения нижней точки скроллинга
		latency - задержка
		
*/
;$.fn.ddrScrollEvents = function(params, latency) {
	let selector = this,
		ops = $.extend({
			onScrollStart: false,
			onScrollStop: false,
			onScrollTop: false,
			onScrollBottom: false 
		}, params),
		scrStart = 0,
		dir;
		
	$(selector).on("scrollstart", {latency: (latency || 250)}, function() {
		scrStart = $(this).scrollTop();
		if (ops.onScrollStart && typeof ops.onScrollStart == 'function') ops.onScrollStart();
	}).on("scrollstop", {latency: (latency || 250)}, function(data) {
		let scrTop =  $(this).scrollTop(),
			scrollHeight = $(this).prop('scrollHeight'),
			overflowHeight = $(this).height();
		
		if (scrStart > scrTop) dir = 'up';
		else if (scrStart < scrTop) dir = 'down';
		
		if (ops.onScrollStop && typeof ops.onScrollStop == 'function') ops.onScrollStop(dir);
		
		if (scrTop + overflowHeight >= scrollHeight) {
			if (ops.onScrollBottom && typeof ops.onScrollBottom == 'function') ops.onScrollBottom();
		} else if (scrTop <= 0) {
			if (ops.onScrollTop && typeof ops.onScrollTop == 'function') ops.onScrollTop();
		}
	});
};