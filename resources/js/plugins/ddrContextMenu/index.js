import "./index.css";


/*
	как работает функция:
		- в области, где нужно вызвать меню - прописывается атрибут contextmenu в значение которого заносятся
			1. функция коллбэк, которая должна вернуть объект пунктов меню
			2. любые необходимые данные для формирования этогосамого объекта пунктов меню
		- создается функция коллбэк, которая была указана в атрибуте
			- функция передает объект методов (close, setCallback... см. const methods) и далее, все аргументы, переданные через атрибут
			- для каждого пункта указаывается коллбэк функция (можно через метод setCallback), которая вызывается при клике на соответствующй пункт меню
*/



// Функция вызова контекстного меню возвращает объект методов и все переданные аргументы
$(document).on('contextmenu', '[contextmenu]', function(e) {
	let context = this,
		d = $(context).attr('contextmenu')?.replace(/\n+/gm, '')?.split(':'),
		func = d[0],
		args = d[1]?.split(',');
	
	
	if (!$[func] && !window[func]) {
		e.preventDefault();
		throw new Error('Ошибка! contextmenu -> Указанная функция не создана!');
	}
	
	args = args.map(function(item) {
		item = item.trim();
		return isInt(item) ? parseInt(item) : item;
	});
	
	
	const methods = {
		close() { // закрыть контекстное меню
			$context.removeClass("is-visible");
			setTimeout(function() {
				$context.remove();
			}, 100);
		},
		setCallback(cbFnc, args, ...addArgs) { // при перечислении пунктов меню применять данную функцию как построитель для передачи функции с аргументами
			let argsData = _.isArray(args) ? args : _.concat(args, [...addArgs]);
			return cbFnc+':'+argsData.join(',');
		}
	};
	
	
	const navData = ($[func] && typeof $[func] == 'function') ? 
			$[func](methods, ...args) : 
			(window[func] && typeof window[func] == 'function' ? window[func](methods, ...args) : false);
	
	
	if (!navData || !navData.length) {
		console.warn('contextmenu -> Указанная функция не возвращает данные!');
		return;
		//throw new Error('Ошибка! contextmenu -> Указанная функция не возвращает данные!');
	} 
	
	
	
	
	
	let menuHtml = '<ul class="context noselect">';
	$.each(navData, function(k, item) {
		//let itemAttrs = setTagAttribute('class', {'parent': item.children, 'nope': !item.enable || item.disable});
		//	itemAttrs += setTagAttribute('onclick', {['$.contextMenuCallFunc(\''+item.callback+'\');']: item.callback && !item.children});
		
		let itemAttrs = setTagAttribute({
			'class': {'parent': item.children, 'nope': (item.enable != undefined && !item.enable) || item.disable},
			'onclick': {['$.contextMenuCallFunc(\''+item.callback+'\');']: item.callback && !item.children}
		});
		
		
		
		menuHtml += '<li'+itemAttrs+'>';
		if (item.faIcon) {
			menuHtml += '<i class="icon fa-fw '+item.faIcon+'"></i>';
		}
		menuHtml += '<p>'+item.name+'</p>';
		if (item.children) {
			menuHtml += '<i class="f fa-solid fa-chevron-right"></i>';
			menuHtml += '<ul class="context sub">';
			$.each(item.children, function(k, childItem) {
				let childItemAttrs = setTagAttribute('class', {'nope': !childItem.enable || childItem.disable});
					childItemAttrs += setTagAttribute('onclick', {['$.contextMenuCallFunc(\''+childItem.callback+'\');']: childItem.callback});
				
				menuHtml += '<li'+childItemAttrs+'>';
				menuHtml += 	'<i class="icon fa-fw '+childItem.faIcon+'"></i>';
				menuHtml += 	'<p>'+childItem.name+'</p>';
				menuHtml += '</li>';
			});
			menuHtml += '</ul>';
		}
		menuHtml += '</li>';
	});
	menuHtml +=	'</ul>';
	
	
	
	
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
	
	
	
	
	
	
	$.contextMenuCallFunc = function(cbData = false) {
		if (!cbData) throw new Error('Ошибка! contextmenu contextMenuCallFunc -> Не переданы данные!');
		
		let d = cbData?.replace(/\n+/gm, '')?.split(':'),
			cbFunc = d[0],
			cbArgs = d[1]?.split(',');
			
		if (!$[cbFunc] && !window[cbFunc]) {
			e.preventDefault();
			throw new Error('Ошибка! contextmenu contextMenuCallFunc -> Указанная функция коллбэка не создана!');
		}
		
		cbArgs = cbArgs.map(function(item) {
			item = item.trim();
			return isInt(item) ? parseInt(item) : item;
		});
		
		
		
		
		// методы, которые привязаны к селектору.
		// То есть, можно использовать селектор по прямому назначению и можно вызывать через него методы.
		$.extend(context, {
			changeAttrData(argIndex = null, newData = null) {
				if (_.isNull(argIndex) || _.isNull(newData)) throw new Error('Ошибка! contextmenu changeAttrData -> неверно переданы аргументы!');
				
				let d = $(context).attr('contextmenu')?.replace(/\n+/gm, '')?.split(':'),
					func = d[0],
					args = d[1]?.split(','),
					buildAttrString = func+':',
					i = argIndex - 1;
				
				if (!args[i]) throw new Error('Ошибка! contextmenu changeAttrData -> аргумента с таким порядковым номером не существует!');
				
				args[i] = newData;
				
				buildAttrString += args.join(',');
				
				$(context).setAttrib('contextmenu', buildAttrString);
			}
		});
		
				
		if (window[cbFunc] && typeof window[cbFunc] == 'function') window[cbFunc](context, ...cbArgs);
		else if ($[cbFunc] && typeof $[cbFunc] == 'function') $[cbFunc](context, ...cbArgs);
	}
	
});