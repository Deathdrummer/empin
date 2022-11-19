import "./index.css";



let functionsMap, loadedFuntionsMap, menuSelector, abortCtrl;

$(document).on('contextmenu', '[contextmenu]', function(e) {
	e.preventDefault();
	const target = {};
	target.selector = this;
	let uniqueBlockId = 'ddrContextMenu';
	
	
	// Получить название функции-построителя меню и ее аргументы
	const [func, args] = _parseAttribString($(target.selector).attr('contextmenu'));
	
	
	// Методы для основной функции
	const methods = {
		target,
		close() { // закрыть контекстное меню
			_close();
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
		},
		onContextMenu(callback = null) {
			if (!_.isNull(callback) && _.isFunction(callback)) callback();
		}
	};
	
	// Вызвать функцию построения меню
	const menuData = _callBuildMenuFunc(func, methods, ...args);
	
	// Сформировать из данных HTML меню, карту функций и связать клик на пукнт меню с вызовом сооответствующей функции
	const [menuHtml, funcMap] = _buildMenuHtml(menuData);
	menuSelector = menuHtml;
	functionsMap = funcMap;
	
	
	// Отрисовать меню
	_renderDomElement(menuHtml, uniqueBlockId);
	
	// Установить положение меню
	_setPositionByCursor(e, menuHtml, {
		strictX: false,
		strictY: true,
	});
	
	// Установить положение всех списков подменю
	_initSubmenuPositions(menuHtml);
	
	// Показать меню
	_show();
	
	// клик на пункт меню
	_clickToAction(menuHtml);
	
	// Скрытие меню при клике на любое место документа
	_clickToHideEvent(menuHtml, uniqueBlockId);
});























//--------------------------------------------------------------------------------------------------------------------------

$(document).on('mouseenter', '.ddrcontextmenu .ddrcontextmenu__item_main', function() {
	$(this).addClass('ddrcontextmenu__item-hovered');
});


$(document).on('mouseleave', '.ddrcontextmenu > li', function() {
	$(this).find('.ddrcontextmenu__item_main').removeClass('ddrcontextmenu__item-hovered');
});


// наведение на пункт меню с подгрузкой
$(document).on('mouseenter touchstart', '.ddrcontextmenu [ddrcontextmenuitemload]:not([ddrcmloaded])', function(e) {
	_loadSubmenu(this);
	$(this).setAttrib('ddrcmloaded');
});




















//--------------------------------------------------------------------------------------------------------------------------




// Спарсить данные, переданные через атрибут contextmenu в теге.
// Получить название функции-построителя меню и ее аргументы
function _parseAttribString(stringToParse = null) {
	if (_.isNull(stringToParse)) throw new Error('Ошибка! contextmenu _parseStringToFuncArgs -> не передан аргумент!');
	if (!_.isString(stringToParse)) throw new Error('Ошибка! contextmenu _parseStringToFuncArgs -> передаваемый аргумент должен быть строкой!');
	
	let [func, args] = ddrSplit(stringToParse?.replace(/\n+/gm, ''), ':', ',');
	if (!_.isArray(args)) args = [args];
	return [func, args];
}




// Вызвать функцию построения меню
function _callBuildMenuFunc(func = null, methods, ...args) {
	if (_.isNull(func)) throw new Error('Ошибка! contextmenu _callGlobalFunc -> неверно переданы аргументы!');
	if (!$[func] && !window[func]) throw new Error('Ошибка! contextmenu _callGlobalFunc -> указанная функция не создана!');
	
	if ($[func] && typeof $[func] == 'function') return $[func](methods, ...args);
	else if (window[func] && typeof window[func] == 'function') return window[func](methods, ...args);
	else throw new Error('Ошибка! contextmenu _callGlobalFunc -> указанная функция не является функцией!');
}







// Сформировать из данных HTML меню, карту функций и связать клик на пукнт меню с вызовом сооответствующей функции
function _buildMenuHtml(menuData = null) {
	if (!menuData || _.isEmpty(menuData)) return [];
	
	menuData = menuData.filter(item => Boolean((item.hidden == undefined || !item.hidden) && (item.visible == undefined || item.visible)));
	if (_.isEmpty(menuData)) return [];
	
	menuData = menuData.sort(function (a, b) {
		if (a == 'divline' || b == 'divline') return 0;
		let aSort = a.sort || 0,
			bSort = b.sort || 0;
		return aSort - bSort;
	});

	const funcMap = {};
	
	let menuHtml = '<ul class="ddrcontextmenu ddrcontextmenu_main noselect">';
	$.each(menuData, function(k, item) {
		if (item == 'divline') {
			menuHtml += '<li><div class="ddrcontextmenu__divline"></div></li>';
			return;
		}
		
		let funcCode = !item.children && (item.load || item.onClick) ? generateCode('nlLlLLnnlnnLnnn') : null;
		
		if (item.onClick && !item.children && !item.load) {
			funcMap[funcCode] = item.onClick;
		
		} else if (item.load && !item.children && !item.onClick) {
			funcMap[funcCode] = item.load;
		}
		
		let itemAttrs = setTagAttribute({
			'class': [
				'ddrcontextmenu__item',
				'ddrcontextmenu__item_main',
				{'ddrcontextmenu__item_parent': item.children || item.load},
				{'ddrcontextmenu__item_single': !item.children && !item.load},
				{'ddrcontextmenu__item-disabled': (item.enabled != undefined && !item.enabled) || item.disabled},
				/*{'ddrcontextmenu__item-loadingable': item.load && !item.children}*/
			],
			'ddrcontextmenuitem': {[funcCode]: item.onClick && !item.children && !item.load && ((item.enabled != undefined && item.enabled) && !item.disabled)}, // коллбэк при клике на пункт меню (без дочерних)
			'ddrcontextmenuitemload': {[funcCode]: item.load && !item.children && !item.onClick} // загрука подменю при наведении
		});
		
		
		menuHtml += '<li>'; //------- parent li
		menuHtml += 	'<div'+itemAttrs+'>'; //------- parent li
		if (item.faIcon) {
			menuHtml += '<div class="icon"><i class="fa-fw '+item.faIcon+'"></i></div>';
		}
		menuHtml += '<p class="text">'+strPad(item.name, 110, '...')+'</p>';
		
		
		if (item.children || item.load) {
			menuHtml += '<div class="arrow"><i class="fa-solid fa-chevron-right"></i></div>';
			menuHtml += '</div>';
			menuHtml += '<ul class="ddrcontextmenu ddrcontextmenu_sub">';
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
				
				let childFuncCode = childItem.onClick ? generateCode('nlLlLLnnlnnLnnn') : null;
				
				if (childItem.onClick) {
					funcMap[childFuncCode] = childItem.onClick;
				}
				
				let childItemAttrs = setTagAttribute({
					'class': [
						'ddrcontextmenu__item',
						'ddrcontextmenu__item_sub',
						{'ddrcontextmenu__item-disabled': (childItem.enabled != undefined && !childItem.enabled) || childItem.disabled}
					],
					'ddrcontextmenuitem': {[childFuncCode]: childItem.onClick && !childItem.load && ((childItem.enabled != undefined && childItem.enabled) && !childItem.disabled)}, // коллбэк при клике на пункт меню
				});
				
				menuHtml += '<li>';
				menuHtml += 	'<div'+childItemAttrs+'>';
				if (childItem.faIcon) menuHtml += 	'<div class="icon"><i class="fa-fw '+childItem.faIcon+'"></i></div>';
				menuHtml += 	'<p class="text">'+strPad(childItem.name, 110, '...')+'</p>';
				menuHtml += 	'</div>';
				menuHtml += '</li>';
			});
		}
		
		
		if (item.load && !item.children) {
			menuHtml += '<li class="ddrcontextmenu__item ddrcontextmenu__item_sub ddrcontextmenu__item-loadingable">';
			menuHtml += 	'<img class="ddrcontextloadingicon" src="/assets/images/loading.gif">';
			menuHtml += '</li>';
		}
		
		
		if (item.load || item.children) {
			menuHtml += '</ul>'; //------- child ul
		}
		
		menuHtml += '</li>'; //------- parent li
	});
	
	menuHtml +=	'</ul>'; //------- parent ul
	
	return [$(menuHtml), funcMap];
}











// Сформировать подгруженное подменю
function _loadSubmenu(menuItem = null) {
	if (_.isNull(menuItem)) return;
	
	const subMenu = $(menuItem).siblings('.ddrcontextmenu_sub'),
		funcCode = $(menuItem).attr('ddrcontextmenuitemload'),
		funcData = functionsMap[funcCode];
	
	if (!_.isPlainObject(funcData)) return;
	
	const {url, method, params, map, sortBy, empty} = _.assign({
		url: null,
		method: 'get',
		params: {},
		map(item) {return item;},
		sortBy: 'sort',
		empty: '<li class="ddrcontextmenu__item ddrcontextmenu__item_sub ddrcontextmenu__item-loadingable"> <p class="text">Пусто</p></li>'
	}, funcData);
	
	abortCtrl = new AbortController();
	axiosQuery(method, url, params, 'json', abortCtrl)
		.then(({data, error, status, headers}) => {
			if (error || _.isEmpty(data)) {
				if (error) console.log(error.message);
				$(subMenu).html(empty);
				return;
			}
			
			let subData = data.map(map);
	
			subData = subData.filter(item => Boolean((item.hidden == undefined || !item.hidden) && (item.visible == undefined || item.visible)));
			if (_.isEmpty(subData)) return;
			
			if (sortBy) {
				subData = subData.sort(function (a, b) {
					let aSort = a[sortBy] || 0,
						bSort = b[sortBy] || 0;
					return aSort - bSort;
				});
			}
			
			
			let subMenuHtml = '',
				funcMap = {};
			$.each(subData, function(k, childItem) {
				
				
				let childFuncCode = childItem.onClick ? generateCode('nlLlLLnnlnnLnnn') : null;
		
				if (childFuncCode) {
					funcMap[childFuncCode] = childItem.onClick;
				}
				
				let childItemAttrs = setTagAttribute({
					'class': [
						'ddrcontextmenu__item',
						'ddrcontextmenu__item_sub',
						{'ddrcontextmenu__item-disabled': (childItem.enabled != undefined && !childItem.enabled) || childItem.disabled}
					],
					'ddrcontextmenuitem': {[childFuncCode]: childItem.onClick && !childItem.load && ((childItem.enabled != undefined && childItem.enabled) && !childItem.disabled)}, // коллбэк при клике на пункт меню
				});
				
				
				subMenuHtml += '<li>';
				subMenuHtml += 	'<div'+childItemAttrs+'>';
				if (childItem.faIcon) subMenuHtml += 	'<div class="icon"><i class="fa-fw '+childItem.faIcon+'"></i></div>';
				subMenuHtml += 	'<p class="text">'+strPad(childItem.name, 110, '...')+'</p>';
				subMenuHtml += 	'</div>';
				subMenuHtml += '</li>';
			});
		
		

			$(subMenu).html(subMenuHtml || empty);
		
			_setSubmenuPosition(subMenu);
		
			loadedFuntionsMap = funcMap;
			
			// клик на пункт меню
			_clickToAction(subMenu, true);	
			
		}).catch((e) => {
			console.error(e);
			throw new Error('Ошибка! contextmenu _loadSubmenu -> axiosQuery вернула ошибку!');
		});
}















// Вывется HTML DOM элемент
function _renderDomElement(html = null, bdeid = null, append = false) {
	const htmlSelector = $(html);	
	htmlSelector.setAttrib('bdeid', bdeid);
	
	if (append == false && $('body').find('[bdeid="'+bdeid+'"]').length > 0) {
		$('body').find('[bdeid="'+bdeid+'"]').replaceWith(htmlSelector);
	} else {
		$('body').append(htmlSelector);
	}
	
	return htmlSelector;
}





function _setPositionByCursor(e, blockSelector, options) {
	if (!e || !blockSelector) throw new Error('ddrSetPosition ошибка в аргументах!');
	
	let {strictX, strictY, target} = _.assign({
			target: window, 
			strictX: false,
			strictY: false,
		}, options),
		cursorX = e.clientX,
		cursorY = e.clientY,
		winW 	= $(target).width(),
		winH 	= $(target).height(),
		blockW 	= blockSelector.outerWidth(),
		blockH 	= blockSelector.outerHeight(),
		leftPos,
		topPos;
	
	if (cursorX + blockW > winW) {
		leftPos = (strictX ? (cursorX - blockW < 0 ? 0 : cursorX - blockW) : cursorX - (cursorX + blockW - winW))
	} else {
		leftPos = cursorX;
	}
	
	if (cursorY + blockH > winH) {
		topPos = (strictY ? (cursorY - blockH < 0 ? 0 : cursorY - blockH) : cursorY - (cursorY + blockH - winH))
	} else {
		topPos = cursorY;
	}
	
	blockSelector.css({'top': topPos+'px', 'left': leftPos+'px'});
}




// Установить положение всех списков подменю
function _initSubmenuPositions(menuHtml = null) {
	if (_.isNull(menuHtml)) return;
	
	$(menuHtml).children('li:not(.ddrcontextmenu__divline)').each(function(k, item) {
		if ($(item).find('.ddrcontextmenu_sub').length == 0) return;
		_setSubmenuPosition($(item).find('.ddrcontextmenu_sub'));
		
	});
}




function _setSubmenuPosition(subMenu = null) {
	if (_.isNull(subMenu)) return;
	
	let posX 	= subMenu.offset().left,
		posY 	= subMenu.offset().top,
		winW 	= $(window).width(),
		winH 	= $(window).height(),
		itemW 	= subMenu.outerWidth(),
		itemH 	= subMenu.outerHeight();
	
	if (posX + itemW > winW) {
		$(subMenu).addClass('oppositeX');
	}
	
	if (posY + itemH > winH) {
		$(subMenu).addClass('oppositeY');
	}
}






// Показать меню
function _show() {
	$('.ddrcontextmenu_main').addClass('ddrcontextmenu_main-visible');
}



// Скрытие меню при клике на любое место документа
function _clickToHideEvent(menuSelector = null, uniqueBlockId = null) {
	if (_.isNull(menuSelector) || _.isNull(uniqueBlockId)) return;
	$(document).on("mousedown", function(e) {
		let target = $(e.target);
		if (abortCtrl instanceof AbortController) abortCtrl.abort();
		
		if (!target.is(menuSelector) && !target.closest('[bdeid="'+uniqueBlockId+'"]').length) {
			_close();
			$(document).off(e);
		}
	});
}
	
	


// Клик на пункт меню
function _clickToAction(menuSelector = null, useLoadedFuncs = false) {
	if (_.isNull(menuSelector)) return;
	menuSelector.on(tapEvent, '[ddrcontextmenuitem]', function(e) {
		let funcCode = $(this).attr('ddrcontextmenuitem');
		if ((!functionsMap[funcCode] || typeof functionsMap[funcCode] != 'function') && (!loadedFuntionsMap[funcCode] || typeof loadedFuntionsMap[funcCode] != 'function')) throw new Error('Ошибка! contextmenu _clickToAction -> передана не функция!');
		
		const obj = {};
		obj.target = this;
		$.extend(obj, {
			text() {
				return $(this.target).text().trim();
			},
			items() {
				return $(this.target).parent('li').siblings(':not(.ddrcontextmenu__divline)');
			}
		});
		
		if (useLoadedFuncs && loadedFuntionsMap[funcCode] != undefined && typeof loadedFuntionsMap[funcCode] == 'function') loadedFuntionsMap[funcCode](obj);
		else if (!useLoadedFuncs && functionsMap[funcCode] != undefined && typeof functionsMap[funcCode] == 'function') functionsMap[funcCode](obj);
		
		_close();
	});
}



// Закрыть меню
function _close() {
	if (_.isNull(menuSelector)) return;
	menuSelector.removeClass("ddrcontextmenu-visible");
	//setTimeout(function() {
		menuSelector.remove();
	//}, 100);
}