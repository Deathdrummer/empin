import "./index.css";







$(document).on('contextmenu', '[contextmenu]', function(e) {
	e.preventDefault();
	const target = {};
	target.selector = this;
	let menuSelector;
	
	
	
	// методы, которые привязаны к селектору.
	// То есть, можно использовать селектор target.selector и можно вызывать через target.методы.
	$.extend(target, {
		changeAttrData(argIndex = null, newData = null) {
			if (_.isNull(argIndex) || _.isNull(newData)) throw new Error('Ошибка! contextmenu changeAttrData -> неверно переданы аргументы!');
			
			const [chFn, chArgs] = _parseStringToFuncArgs($(this.selector).attr('contextmenu'));
			
			let buildAttrString = chFn+':',
				i = argIndex - 1;
			
			if (!chArgs[i]) throw new Error('Ошибка! contextmenu changeAttrData -> аргумента с таким порядковым номером не существует!');
			
			chArgs[i] = newData;
			
			buildAttrString += chArgs.join(',');
			
			$(this.selector).setAttrib('contextmenu', buildAttrString);
		}
	});
	
	
	
	const [func, args] = _parseStringToFuncArgs($(target.selector).attr('contextmenu'));
	
	
	// Метода для основной функции
	const methods = {
		target,
		close() { // закрыть контекстное меню
			menuSelector.removeClass("is-visible");
			setTimeout(function() {
				menuSelector.remove();
			}, 100);
		},
		setCallback(cbFnc, cbArgs, ...addArgs) { // при перечислении пунктов меню применять данную функцию как построитель для передачи функции с аргументами
			let argsData = _.isArray(cbArgs) ? cbArgs : _.concat(cbArgs, [...addArgs]);
			return cbFnc+':'+argsData.join(',');
		},
		closeOnScroll(...selectors) {
			let s = [...selectors];
			selectors.push('html, body');
			let allSelectorsStr = selectors.join(', ');
			
			$(allSelectorsStr).one('scroll', function() {
				methods.close();
			});
		}
		
	};
	
	
	const menuData = _callGlobalFunc(func, methods, ...args);
	
	const [menuHtml, funcMap] = _buildMenuHtml(menuData);
	
	if (!menuHtml || !funcMap) return;
	
	menuSelector = _render(e, menuHtml);
	
	
	
	
	
	
	
	
	
	
	// Скрытие меню при клике на пункт меню
	menuHtml.one("mousedown mouseup touchstart touchend", "li:not(.nope):not(.parent)", function(e) {
		e.stopPropagation();
		if (e.which !== 1) return;
		
		if (hasIn(['mousedown', 'touchstart'], e.type) !== false) {
			$(this).addClass("active");
		} else if (hasIn(['mouseup', 'touchend'], e.type) !== false) {
			menuHtml.removeClass("is-visible");
			setTimeout(function() {
				menuHtml.remove();
			}, 100);
			$(this).removeClass("active");
		}
	});
	
	
	
	
	// клик на пункт меню без дочерних
	menuHtml.on(tapEvent, '[contextmenuitem]', function(e) {
		let funcCode = $(this).attr('contextmenuitem');
		if (!funcMap[funcCode] || typeof funcMap[funcCode] != 'function') throw new Error('Ошибка! contextmenu contextmenuitem parent -> передана не функция!');
		
		const obj = {};
		obj.target = this;
		$.extend(obj, {
			text() {
				return $(this.target).text().trim();
			},
			items() {
				return $(this.target).siblings();
			}
		});
		
		funcMap[funcCode](obj);
	});
	
	
	
	
	// наведение на пункт меню с дочерними
	let waiting, abortCtrl;
	menuHtml.on('mouseenter mouseleave touchstart touchend', '[contextmenuitemload]:not(.nope).parent', function(e) {
		const menuItem = this,
			subContext = $(menuItem).find('.context.sub');
		
		if (hasIn(['mouseenter', 'touchstart'], e.type) !== false) {
			
			let funcCode = $(menuItem).attr('contextmenuitemload');
			if (!_.isPlainObject(funcMap[funcCode])) throw new Error('Ошибка! contextmenu [contextmenuitemload] -> не передан объект с данными!');
		
			
			
			if ($(menuItem).hasClass('loaded')) {
				$(subContext).addClass('context__sub-hover');
				return;
			}
			
			$(subContext).addClass('context__sub-hover minh4rem-5px');
			
			waiting = $(subContext).ddrWait({
				bgColor: 'transparent',
				iconHeight: '25px'
			});
			$(menuItem).addClass('loaded');
			
			
			const {url, method, params, map, empty} = _.assign({
				url: null,
				method: 'get',
				params: {},
				map(item) {return item;},
				empty: '<li class="nope w100 color-gray-500">Пусто</li>'
			}, funcMap[funcCode]);
			
			abortCtrl = new AbortController();
			axiosQuery(method, url, params, 'json', abortCtrl)
				.then(({data, error, status, headers}) => {
					if (error) {
						console.log(error.message);
						return;
					}
					
					if (_.isEmpty(data)) {
						$(subContext).html(empty);
						return;
					}
					
					_buildSubMenu(subContext, data, map, empty);
					
				}).catch((e) => {
					console.error(e);
					$(menuItem).removeClass('loaded');
					//throw new Error('Ошибка! contextmenu _buildSubMenu -> axiosQuery вернула ошибку!');
				});
			
		} else if (hasIn(['mouseleave', 'touchend'], e.type) !== false) {
			$(subContext).removeClass('context__sub-hover');
		}
	});
	
	
	
	
	
	
	
	
	// Скрытие меню при клике на любое место документа
	$(document).on("mousedown", function(e) {
		let $tar = $(e.target);
		
		if (abortCtrl instanceof AbortController) abortCtrl.abort();
		
		if (!$tar.is(menuHtml) && !$tar.closest(".context").length) {     
			menuHtml.removeClass("is-visible");
			setTimeout(function() {
				menuHtml.remove();
			}, 100);
			$(document).off(e);
		}
	});
	
	
	
});





//const itemMethods = {};









// Спарсить данные, переданные через атрибут contextmenu в теге.
function _parseStringToFuncArgs(stringToParse = null) {
	if (_.isNull(stringToParse)) throw new Error('Ошибка! contextmenu _parseStringToFuncArgs -> не передан аргумент!');
	if (!_.isString(stringToParse)) throw new Error('Ошибка! contextmenu _parseStringToFuncArgs -> передаваемый аргумент должен быть строкой!');
	
	let [func, args] = ddrSplit(stringToParse?.replace(/\n+/gm, ''), ':', ',');
	if (!_.isArray(args)) args = [args];
	return [func, args];
}





// Вызвать инициирующую функцию коллбкэа
function _callGlobalFunc(func = null, methods, ...args) {
	if (_.isNull(func)) throw new Error('Ошибка! contextmenu _callGlobalFunc -> неверно переданы аргументы!');
	if (!$[func] && !window[func]) throw new Error('Ошибка! contextmenu _callGlobalFunc -> указанная функция не создана!');
	
	if ($[func] && typeof $[func] == 'function') return $[func](methods, ...args);
	else if (window[func] && typeof window[func] == 'function') return window[func](methods, ...args);
	else throw new Error('Ошибка! contextmenu _callGlobalFunc -> указанная функция не является функцией!');
}



function _buildMenuHtml(menuData = null) {
	if (!menuData || _.isEmpty(menuData)) return [];
	
	menuData = menuData.filter(item => Boolean((item.hidden == undefined || !item.hidden) && (item.visible == undefined || item.visible)));
	if (_.isEmpty(menuData)) return [];
	
	menuData = menuData.sort(function (a, b) {
		let aSort = a.sort || 0,
			bSort = b.sort || 0;
		return aSort - bSort;
		//if (a.sort > b.sort) return 1;
		//if (a.sort < b.sort) return -1;
		//return 0;
	});

	const funcMap = {};
	
	let menuHtml = '<ul class="context noselect">';
	$.each(menuData, function(k, item) {
		let funcCode = !item.children && (item.load || item.onClick) ? generateCode('nlLlLLnnlnnLnnn') : null;
		
		if (item.onClick && !item.children && !item.load) {
			funcMap[funcCode] = item.onClick;
		
		} else if (item.load && !item.children && !item.onClick) {
			funcMap[funcCode] = item.load;
		}
		
		let itemAttrs = setTagAttribute({
			'class': {
				'parent': item.children || item.load,
				'nope': (item.enable != undefined && !item.enable) || item.disable,
				'loadingable': item.load && !item.children
			},
			'contextmenuitem': {[funcCode]: item.onClick && !item.children && !item.load}, // коллбэк при клике на пункт меню (без дочерних)
			'contextmenuitemload': {[funcCode]: item.load && !item.children && !item.onClick} // загрука подменю при наведении
		});
		
		
		menuHtml += '<li'+itemAttrs+'>'; //------- parent li
		if (item.faIcon) {
			menuHtml += '<i class="icon fa-fw '+item.faIcon+'"></i>';
		}
		menuHtml += '<p>'+item.name+'</p>';
		
		
		if (item.children || item.load) {
			menuHtml += '<i class="f fa-solid fa-chevron-right"></i>';
			menuHtml += '<ul class="context sub">';
		}
		
		
		if (item.children) {
			item.children = item.children.filter(child => Boolean((child.hidden == undefined || !child.hidden) && (child.visible == undefined || child.visible)));
			if (_.isEmpty(item.children)) return [];
			
			item.children = item.children.sort(function (a, b) {
				let aSort = a.sort || 0,
					bSort = b.sort || 0;
				return aSort - bSort;
			});
			
			$.each(item.children, function(k, childItem) {
				let childItemAttrs = setTagAttribute({
					'class': {'nope': (childItem.enable != undefined && !childItem.enable) || childItem.disable},
					'contextmenuitem': {[funcCode]: childItem.onClick && !childItem.load}, // коллбэк при клике на пункт меню (без дочерних)
				});
				
				menuHtml += '<li'+childItemAttrs+'>';
				menuHtml += 	'<i class="icon fa-fw '+childItem.faIcon+'"></i>';
				menuHtml += 	'<p>'+childItem.name+'</p>';
				menuHtml += '</li>';
			});
		}
		
		if (item.load || item.children) {
			menuHtml += '</ul>';
		}
		
		menuHtml += '</li>'; //------- parent li
	});
	menuHtml +=	'</ul>';
	
	return [$(menuHtml), funcMap];
}







function _buildSubMenu(subContext = null, itemsData = null, map, empty) {
	if (_.isNull(subContext)) throw new Error('Ошибка! contextmenu _buildSubMenu -> неверный селектор!');
	
	if (_.isNull(itemsData) || !_.isArray(itemsData)) return;
	
	let subData = itemsData.map(map);
	
	subData = subData.filter(item => Boolean((item.hidden == undefined || !item.hidden) && (item.visible == undefined || item.visible)));
	if (_.isEmpty(subData)) return;
	
	subData = subData.sort(function (a, b) {
		let aSort = a.sort || 0,
			bSort = b.sort || 0;
		return aSort - bSort;
	});
	
	let menuHtml = '',
		funcMap = {};
	if (subData) {
		$.each(subData, function(k, childItem) {
			
			let funcCode = childItem.onClick ? generateCode('nlLlLLnnlnnLnnn') : null;
			
			if (childItem.onClick) {
				funcMap[funcCode] = childItem.onClick;
			}
			
			let childItemAttrs = setTagAttribute({
				'class': {'nope': (childItem.enable != undefined && !childItem.enable) || childItem.disable},
				'contextmenuitemloaded': {[funcCode]: childItem.onClick}, // коллбэк при клике на пункт меню (без дочерних)
			});
			
			menuHtml += '<li'+childItemAttrs+'>';
			menuHtml += 	'<i class="icon fa-fw '+childItem.faIcon+'"></i>';
			menuHtml += 	'<p>'+childItem.name+'</p>';
			menuHtml += '</li>';
		});
	}
	
	$(subContext).addClass('loaded');
	
	$(subContext).html(menuHtml || empty);

	
	//-----------------------------------------------------------------
	
	let $window = $(window),
		sw = subContext.width(),
		sh = subContext.height(),
		sx = subContext.offset().left,
		sy = subContext.offset().top,
		padx = 30,
		pady = 20,
		ww = $window.width(),
		wh = $window.height(),
		subHitsRight = (sx + sw - padx >= ww - padx),
		subHitsBottom = (sy + sh - pady >= wh - pady);
	
	if(subHitsRight) {
		$(subContext).addClass("oppositeX");
	}
	
	if(subHitsBottom) {
		$(subContext).addClass("oppositeY");
	}
	
	
	// клик на пункт меню без дочерних
	subContext.on(tapEvent, '[contextmenuitemloaded]', function(e) {
		let funcCode = $(this).attr('contextmenuitemloaded');
		if (!funcMap[funcCode] || typeof funcMap[funcCode] != 'function') throw new Error('Ошибка! contextmenu contextmenuitemloaded -> передана не функция!');
		
		const obj = {};
		obj.target = this;
		$.extend(obj, {
			text() {
				return $(this.target).text().trim();
			},
			items() {
				return $(this.target).siblings();
			}
		});
		
		funcMap[funcCode](obj);
	});
		
}







function _render(e, context) {
	
	if ($('body').find('.context').length) {
		$('body').find('.context').remove();
	}
	
	$('body').append(context);
	
	
	let $doc = $(document),
		$context = $(context).not('.sub'),
		$window = $(window),
		$sub = $context.find('.sub');
		
	
	
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
	
	return $context; 
} 
