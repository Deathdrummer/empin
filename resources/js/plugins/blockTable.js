/*
	Синхронизация скролла нескольких таблиц
		- селектор (должен быть только атрибут)
*/
window.scrollSync = function(syncSelector) {
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