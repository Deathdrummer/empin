import "./index.css";

$(document).on('contextmenu', '[contextmenu]', function(e) {
	const context = this,
		d = $(context).attr('contextmenu').split(':'),
		func = d[0],
		args = d[1]?.split(',');
	
	if (!$[func]) {
		e.preventDefault();
		throw new Error('Ошибка! contextmenu -> Указанная функция не создана!');
	}
	
	
	const navData = $[func](...args);
	
	if (!navData.length) {
		console.warn('contextmenu -> Указанная функция не возвращает данные!');
		return;
		//throw new Error('Ошибка! contextmenu -> Указанная функция не возвращает данные!');
	} 
	
	
	let menuHtml = '<ul class="context noselect">';
	$.each(navData, function(k, item) {
		menuHtml += '<li'+(item.children ? ' class="parent"' : (item.callback ? ' onclick="$.contextMenuCallFunc(\''+item.callback+'\');"' : ''))+'>';
		if (item.faIcon) {
			menuHtml += '<i class="icon fa-fw '+item.faIcon+'"></i>';
		}
		menuHtml += '<p>'+item.name+'</p>';
		if (item.children) {
			menuHtml += '<i class="f fa-solid fa-chevron-right"></i>';
			menuHtml += '<ul class="context sub">';
			$.each(item.children, function(k, childItem) {
				menuHtml += '<li'+(childItem.callback ? ' onclick="$.contextMenuCallFunc(\''+childItem.callback+'\');"' : '')+'>';
				menuHtml += 	'<i class="icon fa-fw '+childItem.faIcon+'"></i>';
				menuHtml += 	'<p>'+childItem.name+'</p>';
				menuHtml += '</li>';
			});
			menuHtml += '</ul>';
		}
		menuHtml += '</li>';
	});
	menuHtml +=	'</ul>';
	
	
	
	console.log(menuHtml);
	
	
	
	
	if ($('body').find('.context').length) {
		$('body').find('.context').remove();
	}
	
	$('body').append(menuHtml);
	
	
	let $doc = $(document),
		$context = $(".context:not(.sub)"),
		$window = $(window),
		$sub = $context.find(".sub");
		
	$sub.removeClass("oppositeX oppositeY");
	
	e.preventDefault();
	
	let w = $context.width(),
		h = $context.height(),
		x = e.clientX,
		y = e.clientY,
		ww = $window.width(),
		wh = $window.height(),
		padx = 30,
		pady = 20,
		fx = x,
		fy = y,
		hitsRight = ( x + w >= ww - padx ),
		hitsBottom = ( y + h >= wh - pady );
	
	if (hitsRight) {
		fx = ww - w - padx;
	}
	
	if (hitsBottom) {
		fy = wh - h - pady;
	}
	
	$context.css({
		left: fx - 1,
		top: fy - 1
	});        
	
	if ($sub.length) {
		let sw = $sub.width(),
			sh = $sub.height(),
			sx = $sub.offset().left,
			sy = $sub.offset().top,
			subHitsRight = ( sx + sw - padx >= ww - padx ),
			subHitsBottom = ( sy + sh - pady >= wh - pady );
		
		if(subHitsRight) {
			$sub.addClass("oppositeX");
		}
		
		if(subHitsBottom) {
			$sub.addClass("oppositeY");
		}
	}
	
	$context.addClass("is-visible");
	
	
	$doc.on("mousedown", function(e) {
		let $tar = $(e.target);
		
		if (!$tar.is( $context ) && !$tar.closest(".context").length) {     
			$context.removeClass("is-visible");
			setTimeout(function() {
				$context.remove();
			}, 100);
			$doc.off(e);
		}
	});
	
	
	$context.one("mousedown mouseup touchstart touchend", "li:not(.nope):not(.parent)", function(e) {
		e.stopPropagation();
		if (e.which !== 1) return;
		
		if (hasIn(['mousedown', 'touchstart'], e.type) !== false) {
			$(this).addClass("active");
		} else if (hasIn(['mouseup', 'touchend'], e.type) !== false) {
			$context.removeClass("is-visible");
			setTimeout(function() {
				$context.remove();
			}, 100);
			$(this).removeClass("active");
		}
	});
	
	
	
	
	
	/*const cbObj = {
		close() {
			$context.removeClass("is-visible");
			setTimeout(function() {
				$context.remove();
			}, 100);
		}
	};*/
	
	
	$.contextMenuCallFunc = (cbFunc = false) => {
		if ($[cbFunc] && typeof $[cbFunc] == 'function') $[cbFunc](context, ...args);
	}
	
	

});