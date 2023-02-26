import "./index.css";

let functionsMap, loadedFuntionsMap, menuSelector, abortCtrl, closeSubNavTOut = 200; // количество милисекунд, через которое закроется подменю
const initStyles = {};

$(document).on('contextmenu', '[contextmenu]', async function(e) {
	e.preventDefault();
	const target = {};
	target.selector = this;
	target.pointer = e?.target;
	let uniqueBlockId = 'ddrContextMenu';
	let maxMenuHeight = null;
	
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
			const cbMethods = { // Методы при инициализации контекстного меню
				setMaxHeight(maxHeight = null) {
					maxMenuHeight = maxHeight;
				}
			};
			
			if (!_.isNull(callback) && _.isFunction(callback)) callback(cbMethods);
		},
		onCloseContextMenu(callback = null) {
			$(document).on('closeContextMenu', (e) => {
				if (!_.isNull(callback) && _.isFunction(callback)) callback(e);
			});
		},
		changeAttrData(selector = null, argIndex = null, newData = null) {
			
			if (_.isNull(newData)) {
				newData = argIndex;
				argIndex = selector;
				selector = target.selector;
			}
			
			
			if (_.isNull(argIndex) || _.isNull(newData)) throw new Error('Ошибка! contextmenu changeAttrData -> неверно переданы аргументы!');
			
			const [chFn, chArgs] = _parseAttribString($(selector).attr('contextmenu'));
			
			let buildAttrString = chFn+':',
				i = argIndex - 1;
				
			if (chArgs[i] === undefined) throw new Error('Ошибка! contextmenu changeAttrData -> аргумента с таким порядковым номером не существует!');
			
			chArgs[i] = newData;
			
			buildAttrString += chArgs.join(',');
			
			$(selector).setAttrib('contextmenu', buildAttrString);
		},
		buildTitle(count = null, one = null, many = null, wordVariants = null) { // сформировать заголовок исходя из кол-ва выбранных элементов
			if (_.isNull(one) || (_.isNull(many) && _.isNull(wordVariants))) return;
			if (_.isNull(count)) return one;
			
			if (_.isNull(wordVariants) && !_.isArray(many)) {
				if (count > 1) return many.replaceAll(/#/ig, count);
				else return one.replaceAll(/\s*#\s*/ig, count);
			}
			
			let oneWithoutNum = false;
			if (_.isNull(wordVariants)) {
				wordVariants = many;
				many = one;
				oneWithoutNum = true;
			}
			
			const word = wordCase(count, wordVariants);
			
			if (count > 1) {
				return many.replaceAll(/%/ig, word).replaceAll(/#/ig, count);
			}
			
			return one.replaceAll(/%/ig, word).replaceAll(/\s*#\s*/ig, oneWithoutNum ? ' ' : count+' ');
		},
		preload(params = null) {
			
			const {
				iconSize
			} = $.extend({
				iconSize: '2rem'
			}, params);
			
			const [preloadMenuHtml, _] = _buildMenuHtml([{name: '<img src="/assets/images/loading.gif" class="w'+iconSize+' h'+iconSize+'">'}]);
			_renderDomElement(preloadMenuHtml, uniqueBlockId);
			_setPositionByCursor(e, preloadMenuHtml, {
				strictX: false,
				strictY: true,
			});
			_show();
		},
		setStyle(styles) { // Переопределить стили
			$.each(styles, function(prop, value) {
				initStyles[prop] = ddrCssVar('cm-'+prop);
				ddrCssVar('cm-'+prop, value);
			});
		},
		setCloseSubNavTOut(tOut = null) { // Установить количество милисекунд, через которое закроется подменю
			if (_.isNull(tOut)) return;
			closeSubNavTOut = tOut;
		}
	};
	
	
	
	
	// Вызвать функцию построения меню
	const menuData = await _callBuildMenuFunc(func, methods, ...args);
	
	// Если есть атрибут nocontext то меню не сработает, но onContextMenu сработает
	if ($(target.pointer).closest('[nocontext]').length) return;
	
	// Сформировать из данных HTML меню, карту функций и связать клик на пукнт меню с вызовом сооответствующей функции
	const [menuHtml, funcMap] = _buildMenuHtml(menuData, maxMenuHeight);
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
let ddrcontextmenuMouseEnterTOut;
let ddrcontextmenuMouseLeaveTOut;



$(document).on('mouseenter', '.ddrcontextmenu .ddrcontextmenu__item_main:not(.ddrcontextmenu__item-disabled)', function() {
	if ($(this).hasClass('ddrcontextmenu__item_parent')) {
		$(this).addClass('ddrcontextmenu__item-hovercolor');
		
		if ($(this).closest('.ddrcontextmenu_main').find('.ddrcontextmenu__item_parent.ddrcontextmenu__item-hovered').length) {
			clearTimeout(ddrcontextmenuMouseEnterTOut);
			ddrcontextmenuMouseEnterTOut = setTimeout(() => {
				if (isHover($(this))) {
					$(this).closest('.ddrcontextmenu_main').find('.ddrcontextmenu__item_parent.ddrcontextmenu__item-hovered').removeClass('ddrcontextmenu__item-hovered');
					$(this).addClass('ddrcontextmenu__item-hovered').removeClass('ddrcontextmenu__item-nohovercolor');
				} else {
					if (!isHover($('.ddrcontextmenu_sub'))) {
						$(this).closest('.ddrcontextmenu_main').find('.ddrcontextmenu__item_parent.ddrcontextmenu__item-hovered').removeClass('ddrcontextmenu__item-hovered');
					}
				}
			}, closeSubNavTOut);
		} else {
			$(this).addClass('ddrcontextmenu__item-hovered').removeClass('ddrcontextmenu__item-nohovercolor');
		}
	} else {
		$(this).removeClass('ddrcontextmenu__item-hovercolor');
		$(this).addClass('ddrcontextmenu__item-hovered').removeClass('ddrcontextmenu__item-nohovercolor');
	}
});



$(document).on('mouseleave', '.ddrcontextmenu:not(.ddrcontextmenu_sub) > li', function() {
	if ($(this).find('.ddrcontextmenu__item').hasClass('ddrcontextmenu__item_parent')) {
		$(this).find('.ddrcontextmenu__item').removeClass('ddrcontextmenu__item-hovercolor');
		$(this).find('.ddrcontextmenu__item_main').addClass('ddrcontextmenu__item-nohovercolor');
		clearTimeout(ddrcontextmenuMouseLeaveTOut);
		ddrcontextmenuMouseLeaveTOut = setTimeout(() => {
			if (!isHover($(this).find('.ddrcontextmenu__item_main').siblings('.ddrcontextmenu_sub'))) {
				$(this).find('.ddrcontextmenu__item_main').removeClass('ddrcontextmenu__item-nohovercolor');
				if (!isHover($(this))) {
					$(this).find('.ddrcontextmenu__item_main').removeClass('ddrcontextmenu__item-hovered');
				} 
			}
		}, closeSubNavTOut);
	} else {
		$(this).find('.ddrcontextmenu__item_main').removeClass('ddrcontextmenu__item-hovered');
	}
});


$(document).on('mouseleave', '.ddrcontextmenu_sub', function() {
	if (!isHover($(this).closest('.ddrcontextmenu_main'))) {
		$(this).siblings('.ddrcontextmenu__item_parent').removeClass('ddrcontextmenu__item-hovered');
	}
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
	
	const splitSrt = ddrSplit(stringToParse?.replace(/\n+/gm, ''), ':', ',');
	let [func, args] = _.isArray(splitSrt) ? splitSrt : [splitSrt, []];
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
function _buildMenuHtml(menuData = null, maxHeight = null) {
	if (!menuData || _.isEmpty(menuData)) return [];
	
	menuData = menuData.filter(item => Boolean((item.hidden == undefined || !item.hidden) && (item.visible == undefined || item.visible)));
	if (_.isEmpty(menuData)) return [];
	
	menuData = menuData.sort(function (a, b) {
		if (a == 'divline' || b == 'divline') return 0;
		let aSort = a.sort || 0,
			bSort = b.sort || 0;
		return aSort - bSort;
	});
	
	
	const hasChilds = menuData.some((item) => item.children || item.load);
	const hasCountsLeft = menuData.some((item) => item.countLeft);
	const hasCountsRight = menuData.some((item) => item.countRight && !item.countOnArrow);
	
	const funcMap = {};
	
	let menuHtml = '<ul class="ddrcontextmenu ddrcontextmenu_main noselect '+(maxHeight ? 'ddrcontextmenu_main-scrolled' : '')+'" '+(maxHeight ? 'style="max-height:'+maxHeight+';"' : '')+'>';
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
				{'ddrcontextmenu__item_full': hasChilds && hasCountsRight && (_.isUndefined(item.countOnArrow) || item.countOnArrow == false)},
				{'ddrcontextmenu__item-disabled': (item.enabled != undefined && !item.enabled) || (item.disabled != undefined && item.disabled)},
				/*{'ddrcontextmenu__item-loadingable': item.load && !item.children}*/
			],
			'ddrcontextmenuitem': {[funcCode]: item.onClick && !item.children && !item.load && ((item.enabled == undefined || item.enabled) && (item.disabled == undefined || !item.disabled))}, // коллбэк при клике на пункт меню (без дочерних)
			'ddrcontextmenuitemload': {[funcCode]: item.load && !item.children && !item.onClick && ((item.enabled == undefined || item.enabled) && (item.disabled == undefined || !item.disabled))} // загрука подменю при наведении
		});
		
		
		menuHtml += '<li>'; //------- parent li
		menuHtml += 	'<div'+itemAttrs+'>'; //------- parent li
		if (item.faIcon) {
			menuHtml += '<div class="icon"><i class="fa-fw '+item.faIcon+'"></i></div>';
		}
		
		if (hasCountsLeft) {
			menuHtml += '<div class="metablock metablock_left">'; // metablock
			if (!_.isUndefined(item.countLeft) && !_.isNull(item.countLeft)) menuHtml += '<div class="count"><span>'+item.countLeft+'</span></div>';
			menuHtml += '</div>'; // metablock
		}
		
		menuHtml += '<div class="text"><p>'+strPad(_.isFunction(item.name) ? item.name() : item.name, 110, '...')+'</p></div>';
		
		if (hasChilds || hasCountsRight) {
			menuHtml += '<div class="metablock metablock_right">'; // metablock
			if (!_.isUndefined(item.countRight) && !_.isNull(item.countRight)) menuHtml += '<div class="count"><span>'+item.countRight+'</span></div>';
			if (item.children || item.load) menuHtml += '<div class="arrow"><i class="fa-solid fa-angle-right"></i></div>';
			menuHtml += '</div>'; // metablock
		}
		
		if (item.children || item.load) {
			menuHtml += '</div>'; // ddrcontextmenu__item
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
			
			const hasCountsChildLeft = item.children.some((child) => child.countLeft);
			const hasCountsChildRight = item.children.some((child) => child.countRight);
			
			$.each(item.children, function(k, childItem) {
				let childFuncCode = childItem.onClick ? generateCode('nlLlLLnnlnnLnnn') : null;
				
				if (childItem.onClick) {
					funcMap[childFuncCode] = childItem.onClick;
				}
				
				let childItemAttrs = setTagAttribute({
					'class': [
						'ddrcontextmenu__item',
						'ddrcontextmenu__item_sub',
						{'ddrcontextmenu__item-disabled': (childItem.enabled != undefined && !childItem.enabled) || (childItem.disabled != undefined && childItem.disabled)}
					],
					'ddrcontextmenuitem': {[childFuncCode]: childItem.onClick && !childItem.load && ((childItem.enabled == undefined || childItem.enabled) && (childItem.disabled == undefined || !childItem.disabled))}, // коллбэк при клике на пункт меню
				});
				
				menuHtml += '<li>';
				menuHtml += 	'<div'+childItemAttrs+'>';
				if (childItem.faIcon) menuHtml += 	'<div class="icon"><i class="fa-fw '+childItem.faIcon+'"></i></div>';
				
				if (hasCountsChildLeft) {
					menuHtml += '<div class="metablock metablock_left">'; // metablock
					if (!_.isUndefined(childItem.countLeft) && !_.isNull(childItem.countLeft)) menuHtml += '<div class="count"><span>'+childItem.countLeft+'</span></div>';
					menuHtml += '</div>'; // metablock
				}
				
				menuHtml += 	'<div class="text"><p>'+strPad(_.isFunction(childItem.name) ? childItem.name() : childItem.name, 110, '...')+'</p></div>';
				
				if (hasCountsChildRight) {
					menuHtml += '<div class="metablock metablock_right">'; // metablock
					if (!_.isUndefined(childItem.countRight) && !_.isNull(childItem.countRight)) menuHtml += '<div class="count"><span>'+childItem.countRight+'</span></div>';
					menuHtml += '</div>'; // metablock
				}
				
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
		empty: '<li class="ddrcontextmenu__item ddrcontextmenu__item_sub ddrcontextmenu__item-loadingable"><div class="text"><p>Пусто</p></div></li>'
	}, funcData);
	
	abortCtrl = new AbortController();
	axiosQuery(method, url, params, 'json', abortCtrl)
		.then(({data, error, status, headers}) => {
			if (error || _.isEmpty(data)) {
				if (error) console.log('Ошибка! contextmenu _loadSubmenu -> axiosQuery:', error.status, error.message);
				$(subMenu).html(empty);
				return;
			}
			
			let subData = data?.map(map);
			if (_.isEmpty(subData)) return;
	
			subData = subData.filter(item => Boolean((item.hidden == undefined || !item.hidden) && (item.visible == undefined || item.visible)));
			if (_.isEmpty(subData)) return;
			
			if (sortBy) {
				subData = subData.sort(function (a, b) {
					let aSort = a[sortBy] || 0,
						bSort = b[sortBy] || 0;
					return aSort - bSort;
				});
			}
			
			const hasCountsSubLeft = subData.some((sub) => sub.countLeft);
			const hasCountsSubRight = subData.some((sub) => sub.countRight);
			
			
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
						{'ddrcontextmenu__item-disabled': (childItem.enabled != undefined && !childItem.enabled) || (childItem.disabled != undefined && childItem.disabled)}
					],
					'ddrcontextmenuitem': {[childFuncCode]: childItem.onClick && !childItem.load && ((childItem.enabled == undefined || childItem.enabled) && (childItem.disabled == undefined || !childItem.disabled))}, // коллбэк при клике на пункт меню
				});
				
				
				subMenuHtml += '<li>';
				subMenuHtml += 	'<div'+childItemAttrs+'>';
				if (childItem.faIcon) subMenuHtml += 	'<div class="icon"><i class="fa-fw '+childItem.faIcon+'"></i></div>';
				
				if (hasCountsSubLeft) {
					menuHtml += '<div class="metablock metablock_left">'; // metablock
					if (!_.isUndefined(childItem.countLeft) && !_.isNull(childItem.countLeft)) menuHtml += '<div class="count"><span>'+childItem.countLeft+'</span></div>';
					menuHtml += '</div>'; // metablock
				}
				
				subMenuHtml += 		'<div class="text"><p>'+strPad(_.isFunction(childItem.name) ? childItem.name() : childItem.name, 110, '...')+'</p></div>';
				
				if (hasCountsSubRight) {
					menuHtml += '<div class="metablock metablock_right">'; // metablock
					if (!_.isUndefined(childItem.countRight) && !_.isNull(childItem.countRight)) menuHtml += '<div class="count"><span>'+childItem.countRight+'</span></div>';
					menuHtml += '</div>'; // metablock
				}
				
				subMenuHtml += 	'</div>';
				subMenuHtml += '</li>';
			});
			
			$(subMenu).html(subMenuHtml || empty);
		
			_setSubmenuPosition(subMenu);
			
			
			if (!loadedFuntionsMap) loadedFuntionsMap = funcMap;
			else loadedFuntionsMap = _.merge(loadedFuntionsMap, funcMap);
			
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
	if (!e) throw new Error('_setPositionByCursor ошибка в аргументах!');
	if (!blockSelector) return;
	
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
	
	
	if (cursorY + blockH > winH - 10) {
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
		//$(subMenu).addClass('oppositeY');
		let calc = (itemH - (winH - posY)) + 10;
		$(subMenu).css({'transform': 'translateY(-'+calc+'px)'});
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
		if ((!functionsMap[funcCode] || !_.isFunction(functionsMap[funcCode])) && (!loadedFuntionsMap[funcCode] || !_.isFunction(loadedFuntionsMap[funcCode]))) throw new Error('Ошибка! contextmenu _clickToAction -> передана не функция!');
		
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
		
		if (useLoadedFuncs && loadedFuntionsMap[funcCode] != undefined && _.isFunction(loadedFuntionsMap[funcCode])) loadedFuntionsMap[funcCode](obj);
		else if (!useLoadedFuncs && functionsMap[funcCode] != undefined && _.isFunction(functionsMap[funcCode])) functionsMap[funcCode](obj);
		
		_close();
	});
}




// Закрыть меню
function _close() {
	if (_.isNull(menuSelector) || typeof menuSelector == 'undefined') return;
	menuSelector.removeClass("ddrcontextmenu-visible");
	//setTimeout(function() {
	menuSelector.remove();
	$(document).trigger('closeContextMenu');
	
	// Вернуть все переопределенные стили в исходное состояние
	$.each(initStyles, function(prop, value) {
		ddrCssVar('cm-'+prop, value);
	});
	//}, 100);
}