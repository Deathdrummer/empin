window.isIos = /iPhone|iPad|iPod/i.test(navigator.userAgent);
window.tapEvent = (('ontouchstart' in window) && !isIos) ? 'tap' : 'click';


$.fn.doubleTap = function(callback) {
	$(this).on(tapEvent, function (e) {
		e.preventDefault();
		if (e.detail >= 2) {
			if (callback && typeof callback == 'function') callback(this);
		}
	});
}


$.fn.tripleTap = function(callback) {
	$(this).on(tapEvent, function (e) {
		e.preventDefault();
		if (e.detail >= 3) {
			if (callback && typeof callback == 'function') callback(this);
		}
	});
}
	




	
window.toDate = function (val = null) {
    if (!val) return null;
    // Если уже Date — обрезаем время
    if (val instanceof Date) return new Date(val.getFullYear(), val.getMonth(), val.getDate());
    // Если строка: берём только yyyy-mm-dd (или парсим, если это ISO)
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(val);
    if (m) return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
    // иначе просто пытаемся спарсить через Date
    const d = new Date(val);
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}




/*
	Разделяет название файла на само название и расширение.
	возвращает:
		- 1: название
		- 2: расширение
	Третий аргумент: обрезает название до заданного количества символов
*/
window.getFileName = function(fileName, nameOrExt = null, nameLimit) {
	let fn = typeof fileName === 'object' ? fileName?.name.split('.') : fileName.split('.'),
		e = fn.pop(),
		n = fn.join('.');
		
	if (!nameOrExt) return [n, e];
	else if (nameOrExt == 1) return (nameLimit != undefined && isInt(nameLimit) && n.length > nameLimit) ? n.substr(0, nameLimit) : n;
	else if (nameOrExt == 2) return e;
}



window.loadImage = async (fileName, fallbackImage) => {
    return new Promise((resolve) => {
        const img = new Image();

        // Если изображение загрузилось, возвращаем его URL
        img.onload = () => resolve(getImageUrl(fileName));

        // Если произошла ошибка загрузки, возвращаем fallback-изображение
        img.onerror = () => resolve(getImageUrl(fallbackImage));

        // Присваиваем путь изображению
        img.src = getImageUrl(fileName);
    });
}


$.errorLoadingImage = async (img, src = 'none_img.png') => {
	const resource = await loadImage(src);
	img.src = resource;
}

	
	
window.callFunc = function(func, ...params) {
	if (!_.isFunction(func)) {
		if (isDev) console.log(`callFunc -> ${func} не является функцией!`);
		return;
	}
	if (_.isFunction(func)) return func(...params);
}
	
	
	


window.getOS = function() {
	let userAgent = window.navigator.userAgent,
		platform = window.navigator?.userAgentData?.platform || window.navigator.platform,
		macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K', 'macOS'],
		windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'],
		iosPlatforms = ['iPhone', 'iPad', 'iPod'],
		os = null;
	
	if (macosPlatforms.indexOf(platform) !== -1) {
		os = 'MacOS';
	} else if (iosPlatforms.indexOf(platform) !== -1) {
		os = 'iOS';
	} else if (windowsPlatforms.indexOf(platform) !== -1) {
		os = 'Windows';
	} else if (/Android/.test(userAgent)) {
		os = 'Android';
	} else if (/Linux/.test(platform)) {
		os = 'Linux';
	}

	return os;
}





//window.getSelectorEvents = function(selector) {
//	const s = $(selector)[0];
//	return $._data(s, 'events');
//}
//
//$.fn.getSelectorEvents = function() {
//	//const s = $(this)[0];
//	
//	//console.log($._data(s, 'events'));
//	
//	
//	return $._data($(this).get(0), "events")
//	
//	
//	
//	
//	/*const s = $(this)[0];
//	const eventsData = $._data(s, 'events');
//	
//	if (_.isNull(events)) return eventsData;
//	
//	events = !_.isArray(events) ? [events] : events;
//	
//	const eventsRes = {};
//	$.each(events, function(k, event) {
//		eventsRes[event] = eventsData[event];
//	});
//	return eventsRes;*/
//	
//}






/*
	Вызвать событие нативного копирование при отсутсвующем выделении
*/
window.ddrCopy = (callback = false, rule = false) => {
	let selection = null;
	const os = getOS();
	
	$(document).on('copy', function(e) {
		if (rule()) {
			selection = getSelectionStr();
			
			if (os == 'Windows' && !selection) {
				if (callback && _.isFunction(callback)) callback();
			
			} else if (os == 'MacOS' && !selection) {
				if (callback && _.isFunction(callback)) callback();
			}
		}
	});
}







window.isFile = function(file = null) {
	let ext = getFileName(file, 2);
	return !(_.isNull(ext) && file.type == '');
}



window.isDev = getFileName(location.host, 2) == 'loc';





window.isNumeric = function(num) {
	return !_.isNaN(num) && !_.isBoolean(num) && !_.isString(num) && !_.isNull(num);
}
						
window.ref = function (data) {
	let target = {};
	let proxy = new Proxy(target, {
		get(target, prop) {
			if (prop in target) {
				if (_.isNumber(target[prop])) return Number(target[prop]);
				return target[prop];
			} else {
				return null;
			}
		}
	});
	proxy.value = data;
	return proxy;
}

















/*
	shift  		shift 	shiftKey
	option 		alt  	altKey
	command  	ctrl 	metaKey \ ctrlKey
*/
window.metaKeys = function(event = null) {
	if (_.isNull(event)) throw new Error('metaKeys ошибка! Не передан event!');
	
	const {shiftKey, ctrlKey, altKey, metaKey} = event;
	
	return {
		isShiftKey: shiftKey,
		isCtrlKey: ctrlKey || metaKey,
		isCommandKey: ctrlKey || metaKey,
		isAltKey: altKey,
		isOptionKey: altKey,
		noKeys: !shiftKey && !altKey && !(ctrlKey || metaKey),
		isActiveKey(key = null) {
			if (_.isNull(event)) return false;
			
			if (_.isArray(key)) {
				if (['ctrl', 'command'].some((k) => key.indexOf(k) !== -1) && (ctrlKey || metaKey)) return true;
				if (['shift'].some((k) => key.indexOf(k) !== -1) && shiftKey) return true;
				if (['alt', 'option'].some((k) => key.indexOf(k) !== -1) && altKey) return true;
				return false;
			}
			
			if (['ctrl', 'command'].indexOf(key) !== -1 && (ctrlKey || metaKey)) return true;
			if (key == 'shift' && shiftKey) return true;
			if (['alt', 'option'].indexOf(key) !== -1 && altKey) return true;
			return false;
		},
	};
}




/*
	shift  		shift 	shiftKey
	option 		alt  	altKey
	command  	ctrl 	metaKey \ ctrlKey
*/
window.mouseClick = function(event = null) {
	if (_.isNull(event)) throw new Error('mouseClick ошибка! Не передан event!');
	
	const {which} = event;
	
	return {
		isLeftClick: which == 1,
		isRightClick: which == 3,
		isCenterClick: which == 2,
	};
}












/*
	AJAX экспорт файла клиенту
*/
window.exportFile = (ops = {}, cb) => {
	const {data, headers, filename = 'noname'} = ops;
	const headerContentDisp = headers["content-disposition"] || null;
	
	const fName = headerContentDisp && headerContentDisp.split("filename=")[1].replace(/["']/g, "");
	const fExt = getFileName(fName, 2);
	
	const finalFileName = filename ? filename+'.'+fExt : fName;
	
	const contentType = headers["content-type"];
	const blob = new Blob([data], {contentType});
	const href = window.URL.createObjectURL(blob);
	const el = document.createElement("a");
	el.setAttribute("hidden", true);
	el.setAttribute("href", href);
	el.setAttribute("download", finalFileName);
	el.click();
	window.URL.revokeObjectURL(blob);
	
	if (_.isFunction(cb)) cb();
}








$.fn.ddrScroll = function(callback, condition = true) {
	if (!callback || !_.isFunction(callback)) return;
	
	const selector = this;
	const randEventHash = generateCode('lLnlLllnnnLll');
	
	let lastScrollTop = 0;
	let accumulateDir;
	let accumulate = 0;
	
	$(selector).on('scroll.'+randEventHash, function(event) {
		if (!condition || !condition?.value) return;
		var st = $(event.target).scrollTop();
		if (st > lastScrollTop) {
			
			if (accumulateDir != 'down') {
				accumulate = 0;
				accumulateDir = 'down';
			}
			 
			accumulate += (st - lastScrollTop);
			
			callback({dir: 'down', top: st, step: st - lastScrollTop, accumulate});
		} else {
			
			if (accumulateDir != 'up') {
				accumulate = 0;
				accumulateDir = 'up';
			} 
			
			accumulate += Math.abs(st - lastScrollTop);
			
			callback({dir: 'up', top: st, step: lastScrollTop - st, accumulate});
		}
		lastScrollTop = st;
	});
	
	return {
		destroy() {
			$(selector).off('scroll.'+randEventHash);
		}
	}
}








window.ddrDateBuilder = function(dateStr = false) {
	var monthNames = {1: 'января', 2: 'февраля', 3: 'марта', 4: 'апреля', 5: 'мая', 6: 'июня', 7: 'июля', 8: 'августа', 9: 'августа', 10: 'октября', 11: 'ноября', 12: 'декабря'};

	const d = dateStr ? new Date(dateStr) : new Date();
	
	const year = {
		short: d.getFullYear().toString().substr(-2),
		full: d.getFullYear(),
	};
	
	const month = {
		short: d.getMonth() + 1,
		zero: addZero(d.getMonth() + 1),
		named: monthNames[d.getMonth() + 1],
	};
	
	const day = {
		short: d.getDate(),
		zero: addZero(d.getDate()),
	};
	

	return {
		year,
		month,
		day,
		hours: d.getHours(),
		minutes: d.getMinutes(),
		seconds: d.getSeconds(),
	};
	
	
	
};


















$.fn.ddrWatch = function(method = null, opsOrCb = null, callback = null) {
	if (_.isNull(method)) throw Error('Ошибка! watch не указан метод!');
	if (_.isNull(callback) && _.isFunction(opsOrCb)) {
		callback = opsOrCb;
		opsOrCb = {};
	}
	
	if (!callback || !_.isFunction(callback)) throw Error('Ошибка! watch не указан коллбэк!');
	
	const selector = this;
	
	if (method == 'mutate') {
		let observer = new MutationObserver(callback);
		
		const {
			childList,
			subtree,
			attributes,
			attributeFilter,
		} = _.assign(opsOrCb, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['class'],
		});
		
		observer.observe($(selector)[0], {
			childList,
			subtree,
			attributes,
			attributeFilter,
		});
		
	} else if (method == 'resize') {
		let observer = new ResizeObserver((entries) => {
			callback(entries);
		});
		
		observer.observe($(selector)[0]);
	}
	
};













/*
	Массив данных брейкпоинтов, пример: {sm: 576, md: 768, lg: 992, xl: 1370}
	либо переменные: breakpointSM, breakpointMD, breakpointLG, breakpointXL
*/
window.breakpoints = {};
['SM', 'MD', 'LG', 'XL', 'XXL'].forEach(function(brName) {
	var val = parseInt($(':root').css('--breakpoint-'+brName.toLowerCase()));
	window['breakpoint'+brName] = val;
	window.breakpoints[brName.toLowerCase()] = val;
});


/*
	Задать значения переменной для каждого брейкпоинта
	brSteps({xs: ..., sm: ..., ...});
*/
window.brSteps = function(bpMap = false) {
	if (!bpMap) return false;
	let winW = $(window).width(),
		currentBpVal = null;
	
	['xs', 'sm', 'md', 'lg', 'xl', 'xxl'].forEach(function(brName) {
		if (typeof bpMap[brName] !== 'undefined') currentBpVal = bpMap[brName];
		bpMap[brName] = currentBpVal;
	});
	
	if (winW < breakpoints['sm']) return bpMap['xs'];
	else if (winW >= breakpoints['sm'] && winW < breakpoints['md']) return bpMap['sm'];
	else if (winW >= breakpoints['md'] && winW < breakpoints['lg']) return bpMap['md'];
	else if (winW >= breakpoints['lg'] && winW < breakpoints['xl']) return bpMap['lg'];
	else if (winW >= breakpoints['xl'] && winW < breakpoints['xxl']) return bpMap['xl'];
	else if (winW >= breakpoints['xxl']) return  bpMap['xxl'];
}



/*
	Получить текущий брейкпоинт
		- объект значений. Пример: {xs: значение 1, sm: значение 2, ...и т.д.} - вернет значение, соответствующее текущему брейпоинту
*/
window.getCurrentBreakPoint = function(values = false) {
	let bps = ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'],
		winW = $(document).width(),
		returnValue;

	bps.forEach(function(bp, k) {
		let bpWidth = breakpoints[bp] || 0,
			nextBpWidth = breakpoints[bps[k+1]] || null;
		if (winW >= bpWidth && (nextBpWidth == null || winW < nextBpWidth)) {
			returnValue = bp;
			return false;
		}

	});
	
	if (values && typeof values == 'object') return values[returnValue] || returnValue;
	return returnValue;
}




/*
	top(scrPos)
	bottom(scrPos)
	both(scrPos)
*/
window.scroll = function({top = null, bottom = null, both = null}) {
	$(window).scroll(() => {
		scrTop = $(window).scrollTop();
		if (scrPos < scrTop) { // прокрутка вниз
			if (bottom && typeof bottom == 'function') bottom(scrTop);
		} else { // прокрутка вверх
			if (top && typeof top == 'function') top(scrTop);
		}
		if (both && typeof both == 'function') both(scrTop);
		scrPos = scrTop;
	});
}







window.selectText = function(elem) {
	if (window.getSelection) {
		window.getSelection().removeAllRanges();
	} else if (document.selection) {
		document.selection.empty();
	}
	
	let selection = window.getSelection();
	let range = document.createRange();
	range.selectNodeContents(elem);
	selection.removeAllRanges();
	selection.addRange(range);
}




window.getSelectionStr = function(toString = true) {
	var text = "";
	if (window.getSelection) {
		text = toString ? window.getSelection().toString() : window.getSelection();
	} else if (document.selection && document.selection.type != "Control") {
		text = document.selection.createRange().text;
	}
	return text;
}




window.removeSelection = function() {
	if (window.getSelection) {
		if (window.getSelection().empty) {  // Chrome
			window.getSelection().empty();
		} else if (window.getSelection().removeAllRanges) {  // Firefox
			window.getSelection().removeAllRanges();
		}
	} else if (document.selection) {  // IE?
		document.selection.empty();
	}
}

	
	


/*
	Скопировать в буфер обмена 
		- строка для копирования
*/
window.copyStringToClipboard = function(str = null) {
	if (_.isNull(str)) return false;
	
	if (navigator.clipboard) {
		navigator.clipboard.writeText(str);
	} else {
		let el = document.createElement('textarea');
		el.value = str;
		el.setAttribute('readonly', '');
		el.style.position = 'absolute';
		el.style.left = '-9999px';
		document.body.appendChild(el);
		el.select();
		document.execCommand('copy');
		document.body.removeChild(el);
	}	
}






window.pasteStringFromClipboard = async function() {
    if (!navigator.clipboard) {
        throw new Error('Clipboard API не поддерживается в этом браузере');
    }
    return await navigator.clipboard.readText();
}





/*
	Определение устройства: desktop или mobile
*/
window.thisDevice = 'desktop';
if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini/i.test(navigator.userAgent)) {
	window.thisDevice = 'mobile';
}





window.isHover = (selector = null) => {
	if (!selector) return false;
	if (_.isArray(selector)) selector = selector.join(', ')
	return !!$(selector).filter(function() {
		return $(this).is(":hover"); 
	}).length;
}








window.pregSplit = function(str = null, separator = null) {
	if (!_.isString(str) || !str) return;
	
	const splitData = str.split(/\s*[,|]\s*|\s*[;]\s*|\s+/);
	
	return splitData.map((item) => {
		return _clearData(item);
	});
	
	function _clearData(strItem = null) {
		if (_.isNull(strItem)) return strItem;
		strItem = strItem?.trim();
		return isInt(strItem) ? parseInt(strItem) : (isFloat(strItem) ? parseFloat(strItem) : strItem);
	}
}






window.ddrSplit = function(string = null, ...separators) {
	if (_.isNull(string)) throw new Error('ddrSplit ошибка! Не передана строка!');
	if (!_.isString(string)) throw new Error('ddrSplit ошибка! Первый аргумент не является строкой!');
	let seps = [...separators];
	if (seps.length == 0) throw new Error('ddrSplit ошибка! Не переданы разделители!');
	
	function splitRecursive(str, iter = 0) {
		if (iter + 1 > seps.length) {
			return _clearData(str);
		}
		
		let res = _runRegSplit(str, seps[iter++]);
		
		if (res.length == 1) {
			return _clearData(res[0]);
		} 
		
		return res.map(function(s, k) {
			return splitRecursive(s, iter);
		});
	}
	
	return splitRecursive(string);
	
	
	
	function _runRegSplit(str, separator = null) {
		separator = _.isArray(separator) ? separator.join('|') : separator;
		let regex = new RegExp(`\\s*[${separator}]\\s*`);
		return str.split(regex);
	}
	
	function _clearData(strItem = null) {
		if (_.isNull(strItem)) return strItem;
		strItem = strItem?.trim();
		return isInt(strItem) ? parseInt(strItem) : (isFloat(strItem) ? parseFloat(strItem) : strItem);
	}
}






window.strPad = function(str) {
	return str;
}






window.wordCase = function(count = null, variants = null) {
	if (_.isNull(count) || _.isNull(variants)) return;
	if (!_.isArray(variants)) variants = pregSplit(variants);
	count = ''+count;

	if (['11', '12', '13', '14'].indexOf(count) != -1 || ['5', '6', '7', '8', '9', '0'].indexOf(count.substr(-1)) != -1) return variants[2];
	else if (['2', '3', '4'].indexOf(count.substr(-1)) != -1 ) return variants[1];
	else if (count.substr(-1) == '1') return variants[0];
}








/*
	события активной или неактивной вкладки сайта в брайзере
		- коллбэк активной вкладки
		- коллбэк неактивной вкладки
*/
window.ddrBrowserTab = function(focusCb, blurCb) {
	$(window).load(() => {
		window.focus();
	});
	$(window).bind('focus', function() {
		if (focusCb && typeof focusCb === 'function') focusCb();
	});
	$(window).bind('blur', function() {
		if (blurCb && typeof blurCb === 'function') blurCb();
	});
}









/*
	Передать в функцию event
	- можно передать объект с предполагаемым атрибутом или склассом {attribute: 'любое зачение'} или {class: ['любое зачение 1', 'любое зачение 2']}
	возвращает аттрибуты и классы элемента или true/false если находит заданный атрибут(ы) или класс(ы)
*/
window.tapEventInfo = function(e, d) {
	var data, attrs, classes, at = '';
	if (thisDevice == 'mobile' && !isIos) {
		attrs = e.changedTouches != undefined ? e.changedTouches[0].target.attributes : false;
		classes = e.changedTouches != undefined ? e.changedTouches[0].target.className : false;
	} else {
		attrs = e.target.attributes || false;
		classes = e.target.className || false;
	}

	if (attrs.length) {
		$.each(attrs, function(k, a) {
			at += ' '+a.name;
		});
	}

	data = {
		classes: (classes && typeof classes == 'string') ? classes.split(' ') : false,
		attributes: (at && typeof at == 'string') ? at.trim().split(' ') : false
	};

	if (d != undefined && d.class) {
		if (data.classes) {
			var fStat = false;
			if (typeof d.class == 'object') {
				$.each(d.class, function(k, cls) {
					if (data.classes.indexOf(cls) != -1) fStat = true;
				});
				return fStat;
			} else return (data.classes.indexOf(d.class) != -1);
		} else return false;
	}

	if (d != undefined && d.attribute) {
		if (data.attributes) {
			var fStat = false;
			if (typeof d.attribute == 'object') {
				$.each(d.attribute, function(k, attr) {
					if (data.attributes.indexOf(attr) != -1) fStat = true;
				});
				return fStat;
			} else return (data.attributes.indexOf(d.attribute) != -1);
		} else return false;
	}

	return data.classes || data.attributes ? data : false;
}









window.attribData = function(e, d) {
	var data, attrs, at = '';
	if (thisDevice == 'mobile' && !isIos) {
		attrs = e.changedTouches != undefined ? e.changedTouches[0].target.attributes : false;
	} else {
		attrs = e.target.attributes || false;
	}

	if (attrs.length) {
		$.each(attrs, function(k, a) {
			at += ' '+a.name;
		});
	}

	data = {
		attributes: (at && typeof at == 'string') ? at.trim().split(' ') : false
	};
	
	if (d != undefined && d.attribute) {
		if (data.attributes) {
			var fStat = false;
			if (typeof d.attribute == 'object') {
				$.each(d.attribute, function(k, attr) {
					if (data.attributes.indexOf(attr) != -1) fStat = true;
				});
				return fStat;
			} else return (data.attributes.indexOf(d.attribute) != -1);
		} else return false;
	}

	return data?.attributes;
}














// Хэш сторка
window.ddrHash = function(str, seed = 0) {
	let h1 = 0xdeadbeef ^ seed, h2 = 0x41c6ce57 ^ seed;
	for (let i = 0, ch; i < str.length; i++) {
		ch = str.charCodeAt(i);
		h1 = Math.imul(h1 ^ ch, 2654435761);
		h2 = Math.imul(h2 ^ ch, 1597334677);
	}
	h1 = Math.imul(h1 ^ (h1>>>16), 2246822507) ^ Math.imul(h2 ^ (h2>>>13), 3266489909);
	h2 = Math.imul(h2 ^ (h2>>>16), 2246822507) ^ Math.imul(h1 ^ (h1>>>13), 3266489909);
	return 4294967296 * (2097151 & h2) + (h1>>>0);
};





/*
	массив с накоплением каких-либо данных
		- сам массив
		- значение, которое нужно добавить или удалить
		- флаг добавить или удалить true \ false
	Возвращает измененный массив
*/
window.storeArray = function(storeArr = [], value, stat = true) {
	if (stat) {
		if (_.isArray(value)) {
			$.each(value, (item) => {
				storeArr.push(value);
			});
		} else {
			storeArr.push(value);
		}
	} else {
		_.pull(storeArr, value);
	}
	return storeArr;
}







// Задать куки
window.setCookie = function(cname, cvalue, exdays, encode) {
	if (encode) {
		cvalueStr = (cvalue * 567)+'346045267804235468667352353ddr';
		let bta = encodeURIComponent(btoa(cvalueStr.replace(/%([0-9A-F]{2})/g, function toSolidBytes(match, p1) {
			  return String.fromCharCode('0x' + p1);
		})));
		cvalue = bta;			
	}
	
	// expire the old cookie if existed to avoid multiple cookies with the same name
	if  (getCookie(cname)) {
		document.cookie = cname + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
	}
	let d = new Date();
	d.setTime(d.getTime() + ((exdays || 365) * 24 * 60 * 60 * 1000));
	let expires = "expires=" + d.toGMTString();
	document.cookie = cname + "=" + cvalue + "; " + expires + "; path=/";
}


// Получить куки
window.getCookie = function(cname, decode) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ')
			c = c.substring(1);
		if (c.indexOf(name) == 0) {
			let cookie = c.substring(name.length, c.length);
			if (decode) {
				let atb = decodeURIComponent(atob(decodeURIComponent(cookie)).split('').map(function (c) {
					return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
				}).join(''));
				return atb.replace('346045267804235468667352353ddr', '') / 567;
			}
			return decodeURIComponent(cookie);
		}
	}
	return false;
}



window.deleteCookie = function(name, path) {
	if (getCookie(name)) {
		document.cookie = name+"="+((path) ? ";path="+(path || '/'):"")+";expires=Thu, 01 Jan 1970 00:00:01 GMT";
	}
}













window.pageReload = function() {
	location.reload();
	location['reload']();
	window.location.reload();
	window['location'].reload();
	window.location['reload']();
	window['location']['reload']();
	self.location.reload();
	self['location'].reload();
	self.location['reload']();
	self['location']['reload']();
	window.location = window.location;
}












$.fn.imgLoaded = function(func) {
	var i = new Image(),
		imageSrc = $(this).attr('src');
	i.onload = function() {
		if (typeof func == 'function') func();
	}
	i.src = imageSrc;
};






/*
	Счетчик
		- начало отсчета
		- направление
		- шаг
*/
window.Counter = function(start, order, step) {
	var count = start || 0;
	return function(num) {
		count = num != undefined ? num : count;
		return order == undefined || order == '+' ? (order == '-' ? count-=step : count+=step) : count-=step;
	}
}




/*
	Поиск в массиве объектов по значению ключа
		- массив объектов
		- поле, по которому искать
		- значение, которое искать
		возвращает индекс объекта массива
*/
window.searchInObject = function(arrObj, field, value) {
	let objIndex = arrObj.findIndex(function(element, index) {
		if (element[field] == value) return index;
	});
	return objIndex;
}




/*
	Является ли строка null
	- строка
*/
window.isNull = function(str) {
	return str === null;
}





/*
	Является ли строка json
	- строка
*/
window.isJson = function(str) {
	if (str == undefined || typeof str == 'undefined') return false;
	try {
		JSON.parse(str);
	} catch (e) {
		return false;
	}
	return true;
}


/*
	Является ли строка целым числом
*/
window.isInt = function(n) {
	if (n == undefined || typeof n == 'undefined') return false;
	if (typeof n != 'string') return Number(n) === n && n % 1 === 0;
	return Number(n)+'' === n;
}


/*
	Является ли строка числом с плавающей точкой
*/
window.isFloat = function(n) {
	if (n == undefined || typeof n == 'undefined') return false;
	if (typeof n != 'string') return Number(n) === n && n % 1 !== 0;
	return Number(n)+'' === n && Number(n) % 1 !== 0;
}






window.count = function(items) {
	var arrLength = items.length;
	if (arrLength != undefined) return arrLength;
	return Object.values(items).length;
}




/*
	добавить ноль к числу
		- число
		- общее кол-во цифр у числа, с учетом самого переданного числа
*/
window.addZero = function(value = null, numLength = 2) {
	return (''+value).padStart(numLength, '0')
}





/*
	проверить наличие элемента в массиве или объекте
		- массив или объект
		- искомый элемент
		- является ли ключем искомый элемент

	ВНИМАНИЕ!!! Может возвращать 0 - это найденный индекс
*/
window.hasIn = function(data, elem, isKey) {
	if (elem == undefined || data == undefined || data.length == 0) return false;
	var findKey;
	if (isKey != undefined && isKey == true) {
		var keysData = Object.keys(data);
		findKey = keysData.indexOf(elem);

		if (findKey != -1) {
			return data[keysData[findKey]];
		}
		return false;
	}

	findKey = data.indexOf(elem);
	return (findKey != -1 ? findKey : false);
}






window.isImgFile = function(fileName) {
	let fn = typeof fileName === 'object' ? fileName.name.split('.') : fileName.split('.'),
		ext = fn.pop();
	return _.indexOf(['png', 'apng', 'jpeg', 'jpg', 'gif', 'bmp', 'webp'], ext) >= 0;
}





$.fn.disableDrop = function(extClasses, callback) {
	var selector = this;

	function action(e, cb) {
		if (extClasses && typeof extClasses != 'function') {
			var stat = false;
			if (typeof extClasses == 'object') {
				for (var i = 0; i < extClasses.length; i++) {
					if ($(e.target).hasClass(extClasses[i]) != false) {
						stat = true;
					}
				}
			} else {
				if ($(e.target).hasClass(extClasses) != false) {
					stat = true;
				}
			}

			if (!stat) {
				e.preventDefault();
				if (cb && callback && typeof callback == 'function') callback(e.target);
			}
		} else {
			e.preventDefault();
			if (cb && extClasses && typeof extClasses == 'function') extClasses(e.target);
		}
	}

	$(selector).off(tapEvent).on(tapEvent, function(e) {
		action(e, true);
	});

	$(selector).off('dragover').on('dragover', function(e) {
		action(e);
	});

	$(selector).off('dragleave').on('dragleave', function(e) {
		action(e);
	});

	$(selector).off('drop').on('drop', function(e) {
		action(e, true);
	});
};




/*
	Добавление атрибута
		- название атрибута
		- значение
*/
$.fn.setAttrib = function(attr, value) {
	if (attr == undefined) return false;
	if ($(this).length == 0) return false;
	$(this).attr(attr, (value || ''));
	$(this)[0].setAttribute(attr, (value || ''));
	return this;
};

/*
	Удаление атрибута
		- название атрибута
*/
$.fn.removeAttrib = function(attr) {
	if (attr == undefined) return false;
	if ($(this).length == 0) return false;
	$(this).prop(attr, false);
	$(this).removeAttr(attr);
	$(this)[0].removeAttribute(attr);
	return this;
};



/*
	Проверка наличия атрибута
		- название атрибута
*/
$.fn.hasAttr = function(a) {
	var attr = $(this).attr(a);
	return typeof attr !== typeof undefined && attr !== false;
}



/*
	Добавить аттрибуты к тегу
		- название атрибута
		- условия {[название значения]: условия} (если в названии переменная - обернуть в [])
*/
window.setTagAttribute = function(attrName = null, rules = null, joinSign = ' ') {
	if (_.isNull(attrName)) throw new Error('Ошибка! setTagAttribute -> не указан атрибут');
	if (!_.isObject(attrName) && _.isNull(rules)) return '';
	if (_.isObject(attrName)) joinSign = rules || ' ';
	
	if (_.isObject(attrName)) {
		let allAttrsValues = '';
		$.each(attrName, function(attrNameItem, rulesItem) {
			
			let attrValueItem = [];
			
			if (_.isPlainObject(rulesItem)) {
				$.each(rulesItem, function(val, rule) {
					if (Boolean(rule)) attrValueItem.push(val);
				});
				
				if (attrValueItem.length == 0) return '';
				allAttrsValues += ' '+attrNameItem+'="'+attrValueItem.join(joinSign)+'"';
			
			} else if (_.isArray(rulesItem)) {
				$.each(rulesItem, function(k, val) {
					if (_.isPlainObject(val)) {
						$.each(val, function(v, r) {
							if (Boolean(r)) attrValueItem.push(v);
						});
					} else {
						attrValueItem.push(val);
					}
				});
				
				if (attrValueItem.length == 0) return '';
				allAttrsValues += ' '+attrNameItem+'="'+attrValueItem.join(joinSign)+'"';
				
			} else {
				allAttrsValues += Boolean(rulesItem) ? ' '+attrNameItem : '';
			}	
		});
		
		return allAttrsValues;
	}
	
	
	
	if (_.isPlainObject(rules)) {
		let attrValues = [];
		$.each(rules, function(val, rule) {
			if (Boolean(rule)) attrValues.push(val);
		});
		
		if (attrValues.length == 0) return '';
		return ' '+attrName+'="'+attrValues.join(joinSign)+'"';
	
	} else if (_.isArray(rules)) {
		let attrValues = [];
		$.each(rules, function(k, val) {
			attrValues.push(val);
		});
		
		if (attrValues.length == 0) return '';
		return ' '+attrName+'="'+attrValues.join(joinSign)+'"';
	}
	
	return Boolean(rules) ? ' '+attrName : '';
}









let scrollPosition = 0;

/*
	Запретить скролл
*/
window.disableScroll = function() {
    scrollPosition = $(window).scrollTop();
    $('body').css({
        'overflow': 'hidden',
        'position': 'fixed',
        'width': '100%',
        'top': `-${scrollPosition}px`
    });
}

/*
	Разрешить скролл
*/
window.enableScroll = function() {
    $('body').css({
        'overflow': '',
        'position': '',
        'width': '',
        'top': ''
    });
    $(window).scrollTop(scrollPosition);
}










/*
	Зафиксировать элемент при скролле
		- настройки
			- класс, который назначается фиксируемоему элементу
			- положение относительно начала документа, при котором назначается скролл
*/
$.fn.scrollFix = function(settings) {
	var selector = this,
		scrTOut,
		scrollTop,
		ops = $.extend({
			cls: 'fixed',
			pos: 200,
		}, settings);

	scrollTop = $(window).scrollTop();
	if (scrollTop > ops.pos && !$(selector).hasClass(ops.cls)) {
		$(selector).addClass(ops.cls);
	} else if (scrollTop <= ops.pos && $(selector).hasClass(ops.cls)) {
		$(selector).removeClass(ops.cls);
	}

	$(window).scroll(function(e) {
		clearTimeout(scrTOut);
		scrTOut = setTimeout(function() {
			scrollTop = $(window).scrollTop();
			if (scrollTop > ops.pos && !$(selector).hasClass(ops.cls)) {
				$(selector).addClass(ops.cls);
			} else if (scrollTop <= ops.pos && $(selector).hasClass(ops.cls)) {
				$(selector).removeClass(ops.cls);
			}
		}, 10);
	});
};





/*
	Генерация рандомного числа
		- минимальное число
		- максимальное число
*/
export function random(min, max) {
	return Math.floor(Math.random() * (max - min + 1)) + min;
}


window.random = function(min, max) {
	return Math.floor(Math.random() * (max - min + 1)) + min;
}


/*
	Генерация кода
		- маска: l - буква с нижним регистром, L - буква с верхним регистром, n - цифра
*/
window.generateCode = function(mask) {
	var letters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
	var code = '';
	for(var x = 0; x < mask.length; x++) {
		if (mask.substr(x, 1) == 'l') code += letters[random(0,25)];
		else if (mask.substr(x, 1) == 'L') code += letters[random(0,25)].toUpperCase();
		else if (mask.substr(x, 1) == 'n') code += random(0,9);
	}
	return code;
}





/*
	Получить контент из блока [contenteditable]
		- селектор
*/
window.getContenteditable = function(selector) {
	if (typeof $(selector).attr('contenteditable') === 'undefined') return null;
	
	let content = $(selector).html(),
		temp = document.createElement("div");
	
	if (content == '') return '';

	content = content.replace(/<br><br>/gu, "\n")
		.replace(/<div><br><\/div>/gu, "\n")
		.replace(/<br>/gu, "\n")
		.replace(/<div>/gu, "\n")
		.replace(/<\/div>/gu, '')
		.replace(/&nbsp;/gu, '');

	temp.innerHTML = content;
	var sanitized = temp.textContent || temp.innerText,
	sanitized = sanitized.trim();
	return sanitized || '';
}






/*
	Аргументы с GET данных
		- вернуть конкретный аргумент
*/
window.getArgs = function(arg) {
	var args = location.search.substr(1, location.search.length).split('&'),
		item,
		argsArr = {};

	$.each(args, function(k, i) {
		if (i == '') return true;
		item = i.split('=');
		argsArr[item[0]] = item[1];
	});

	if (arg != undefined && argsArr[arg] != undefined) {
		return argsArr[arg];
	} else if (arg != undefined && argsArr[arg] == undefined) {
		return false;
	} else {
		return Object.keys(argsArr).length > 0 ? argsArr : null;
	}
}





/*
	Проверка существования файла
		- путь до файла
*/
window.urlExists = function(url) {
	var http = new XMLHttpRequest();
	http.open('HEAD', url, false);
	http.send();
	return http.status != 404;
}



/*
	Вывод ошибки
		- объект данных ошибки из AJAX
*/
window.showError = function(e) {
	var showId = generateCode('lLLnnn');
	if (e.responseText) {
		var html = '<div>'+e.responseText+'</div>';
		var errData;
		if ($(html).find('#container').length > 0) {
			errData = '<div class="show_error"><div>'+$(html).find('#container').html()+'</div><button id="'+showId+'">Закрыть</button></div>';
		} else {
			errData = '<div class="show_error"><div>'+e.responseText+'</div><button id="'+showId+'">Закрыть</button></div>';
		}

		$('body').append(errData);

		$('#'+showId).on(tapEvent, function() {
			$(this).parent('.show_error').remove();
		});
	}
}





/*
	Генерация HTML через AJAX
*/

window.getAjaxHtml = function() {
	let a = arguments,
		url = (typeof a[0] == 'string' ? (a[0].substr(0, 1) != '/' ? '/'+a[0] : a[0]) : false),
		params = typeof a[1] == 'object' ? a[1] : {},
		callback = typeof a[1] == 'function' ? a[1] : (a[2] !== undefined ? a[2] : false),
		always = typeof a[1] == 'function' ? (typeof a[2] == 'function' ? a[2] :false) : (typeof a[2] == 'function' ? (typeof a[3] == 'function' ? a[3] :false) : false);
		
	return new Promise(function(resolve, reject) {
		try {
			$.ajaxSetup({headers: {'X-Requested-With': 'XMLHttpRequest'}}); // это чтобы апрос распознавался как AJAX
			$.post(url, params, function(html, stat, xhr) {
				html = html.trim();
				let headersData = xhr.getAllResponseHeaders(),
					headers = {};
					
				headersData = headersData.split("\n");
				headersData.forEach(function (header) {
					header = header.split(": ");
					let key = header.shift();
					if (key.length == 0) return;
					if (!/ddr-/.test(key)) return;
					key = key.replace('ddr-', '');
					key = key.toLowerCase(); 
					val = header[0].replace("\r", '');
					if (isInt(val)) val = parseInt(val);
					if (isJson(val)) val = JSON.parse(val);
					headers[key] = val;
				});
				
				resolve({html: (html || null), headers: headers});
				if (html && callback) callback(html, true, headers);
				else if (callback) callback('<p class="empty center">Нет данных</p>', false, headers);
			}, 'html').always(function() {
				if (always) always();
			}).fail(function(e) {
				reject(e);
				notify('Системная ошибка!', 'error');
				showError(e);
			});
		} catch(e) {
			reject(e);
			notify('Системная ошибка!', 'error');
			showError(e);
		}
	});
}












window.getAjaxJson = function() {
	let a = arguments,
		url = (typeof a[0] == 'string' ? (a[0].substr(0, 1) != '/' ? '/'+a[0] : a[0]) : false),
		params = typeof a[1] == 'object' ? a[1] : {},
		callback = typeof a[1] == 'function' ? a[1] : (a[2] !== undefined ? a[2] : false),
		always = typeof a[1] == 'function' ? (typeof a[2] == 'function' ? a[2] :false) : (typeof a[2] == 'function' ? (typeof a[3] == 'function' ? a[3] :false) : false);
	
	return new Promise(function(resolve, reject) {
		try {
			$.ajaxSetup({headers: {'X-Requested-With': 'XMLHttpRequest'}}); // это чтобы апрос распознавался как AJAX
			$.post(url, params, function(json, stat, xhr) {
				let headersData = xhr.getAllResponseHeaders(),
					headers = {};
				headersData = headersData.split("\n");
				headersData.forEach(function (header) {
					header = header.split(": ");
					let key = header.shift();
					if (key.length == 0) return;
					if (!/ddr-/.test(key)) return;
					key = key.replace('ddr-', '');
					key = key.toLowerCase(); 
					val = header[0].replace("\r", '');
					if (isInt(val)) val = parseInt(val);
					if (isJson(val)) val = JSON.parse(val);
					headers[key] = val;
				});
				
				resolve({json: (json || null), headers: headers});
				if (callback) callback(json || null, headers);
			}, 'json').always(function() {
				if (always) always();
			}).fail(function(e) {
				reject(e);
				notify('Системная ошибка!', 'error');
				showError(e);
			});
		} catch(e) {
			reject(e);
			notify('Системная ошибка!', 'error');
			showError(e);
		}	
	});		
}











/*
	Получить данные секции через AJAX
	Используется для получения данных секции через AJAX. Написана 19.04.21 для страницы http://baneti.ru/contacts
		params
		- section: название секции на странице, данные которой необходимо получить
		- template: шаблон для вывода информации. пример: 'render/contacts/more_info.tpl'. Если не указать - данные вернутся в JSON формате
		- data: дополнительные данные в объекте. пример: {foo: bar}
		callback: функция, возвращающая данные
*/
window.getSectionData = function(params, callback) {
	if (params == undefined) return false;
	
	let data = params.data || {};
	
	data['json'] = 1;
	if (params.section) data['section'] = params.section;
	if (params.template) data['template'] = params.template;
	
	$.ajax({
		url: location.href,
		method: 'POST',
		data: data,
		dataType: params.template ? 'html' : 'json',
		success: function(data) {
			if (params.template) {
				html = data.trim();
				if (html && callback) callback(html, true);
				else if (callback) callback('<p class="empty center">Нет данных</p>', false);
			} else {
				callback(data);
			}
		},
		error: function(e) {
			notify('Системная ошибка!', 'error');
			showError(e);
			callback(false);
		}
	});
}








/*
	Событие изменения полей в заданном блоке
		- каллбэк
			- изменненный элемент
			- данные события
*/
$.fn.changeInputs = function(callback) {
	if (this.length == 0) {
		console.warn('changeInputs ошибка! Селектор: не найден!');
		return false;
	}

	if (callback == undefined || typeof callback != 'function') {
		console.warn('changeInputs ошибка! callback функция не задана!');
		return false;
	}

	var selector = this;

	function _getEventData(e) {
		return {
			t: e.type,
			k: e.keyCode,
			s: e.currentTarget.localName,
			st: (e.currentTarget.type == undefined) ? 'contenteditable' : (e.currentTarget.type == 'select-one' ? e.currentTarget.type.replace('select-one', 'select') : e.currentTarget.type),
			isShiftKey: e.shiftKey,
			isCtrlKey: e.ctrlKey,
			codeShift: (e.keyCode == 16),
			codeCtrl: (e.keyCode == 17),
			codeEnter: (e.keyCode == 13),
			codeX: (e.keyCode == 88),
			codeV: (e.keyCode == 86)
		};
	}

	function _setAction(thisItem, eData) {
		callback(thisItem, eData);
	}


	$(selector).on('change', 'select, input[type="checkbox"], input[type="radio"], input[type="color"], input[type="number"]', function(event) {
		var eData = _getEventData(event);
		_setAction(this, eData);
	});


	var keyDownVal;
	$(selector).on('keyup keydown', 'input, textarea, [contenteditable]', function(event) {
		var thisItem = this, eData = _getEventData(event);
		if (['text', 'password', 'email', 'tel', 'number'].indexOf(eData.st) != -1 || (eData.s == 'textarea' && !eData.codeShift && !eData.codeCtrl) || (eData.st == 'contenteditable')) {
			if (eData.t == 'keydown') keyDownVal = (eData.st == 'contenteditable') ? $(thisItem).html() : $(thisItem).val();
			else if (eData.t == 'keyup') {
				var thisKeyUpVal = (eData.st == 'contenteditable') ? $(thisItem).html() : $(thisItem).val();
				if (keyDownVal !== thisKeyUpVal || (eData.st == 'contenteditable' && eData.codeEnter)) {
					keyDownVal = thisKeyUpVal;
					_setAction(thisItem, eData);
				}
			}
		}
	});
};











/*
	Summernote
*/
window.initEditors = function() {
	var isLoaded = false,
		fontSizes = [],
		lineHeights = [];

	for (i = 0; i < 100; i++) {
		fontSizes.push(''+i);
		lineHeights.push(i+'px');
	}


	options = {
		disableDragAndDrop: true,
		height: 500,
		lang: 'en-US',
		emptyPara: '',
		lineHeights: lineHeights,
		fontSizes: fontSizes,
		codeviewFilter: false,
		codeviewIframeFilter: true,
		disableGrammar: true,
		codemirror: {
			theme: 'monokai'
		},
		toolbar: [
			['font', ['bold', 'italic', 'underline', 'clear']],
			['height', ['height']],
			['style', ['style']],
			['fontsize', ['fontsize']],
			['fontname', ['fontname']],
			['color', ['color']],
			['para', ['ul', 'ol', 'paragraph']],
			['table', ['table']],
			['insert', ['link', 'image', 'video']],
			['view', ['fullscreen', 'codeview']]
		],
		callbacks: {
			onChange: function (contents) {
				if ($(this).closest('tr').find('[save], [update]').length) {
					$(this).closest('tr').find('[save], [update]').removeAttrib('disabled');
				}
			}
			/*onChange: function (contents) {
				$summernote.summernote('code', '');
			},
			onBlurCodeview: function() {
			}*/
		},
		buttons: {
			image: function(context) {
				return $.summernote.ui.button({
					contents: '<i class="note-icon-picture"></i>',
					tooltip: 'тултип',
					className: 'editorfile',
					click: function () {
						$('#clientFileManager:not(.visible)').addClass('visible');
						currentDir = lscache.get('clientmanagerdir') || false;
						if (currentDir && !isLoaded) {
							getAjaxHtml('filemanager/files_get', {directory: currentDir, filetypes: 'png|jpg|jpeg|gif|ico|bmp', client: 1}, function(html) {
								$('#clientFilemanagerContentFiles').html(html);
							});

							getAjaxHtml('filemanager/dirs_get', {current_dir: currentDir}, function(html) {
								$('#clientFilemanagerDirs').html(html);
							});
							isLoaded = true;
						}

						$('#clientFilemanagerContentFiles').off(tapEvent, '.image').on(tapEvent, '.image', function() {
							var thisFileBlock = $(this).closest('.clientfilemanager__file'),
								thisFilePath = $(thisFileBlock).attr('dirfile'),
								thisFileName = $(thisFileBlock).attr('namefile'),
								thisFileSrc = $(this).find('img').attr('src');
							context.invoke('editor.insertImage', location.origin+'/public/filemanager/'+thisFilePath);
							$('#clientFileManager').removeClass('visible');
						});
					}
				}).render();
			}
		}
	};


	/*var selectors = [],
		editors = $('body').find('[editor]');

	if (editors.length > 0) {
		$.each(editors, function(k, item) {
			var d = $(item).attr('editor').split('|'),
				h = d[1] != undefined ? parseInt(d[1]) : 500;

			selectors.push({
				selector: $(item).attr('editor'),
				height: h
			});
		});

		$.each(selectors, function(k, s) {
			options.height = s.height;
			$('[editor="'+s.selector+'"]').summernote(options);
			$('[editor="'+s.selector+'"]').addClass('activate');
		});
	}*/

	$('body').find('[editor]:not(.activated)').summernote(options);
	$('body').find('[editor]').addClass('activated');

}










// -------------------------------------------------------------------- Динамически инициализировать табы
window.ddrInitTabs = function() {
	
	var hashData = location.hash.substr(1, location.hash.length).split('.'),
		section = hashData[0];
	
	if (hashData[1] != undefined) {
		$('#'+section).find('.tabstitles:not(.sub) li').removeClass('active');
		$('#'+section).find('.tabstitles:not(.sub) li#'+hashData[1]).addClass('active');
		
		$('#'+section).find('.tabstitles:not(.sub)').siblings('.tabscontent').find('[tabid]').removeClass('visible');
		$('#'+section).find('.tabstitles:not(.sub)').siblings('.tabscontent').find('[tabid="'+hashData[1]+'"]').addClass('visible');
	} else {
		$('#'+section).find('.tabstitles:not(.sub) li:first').addClass('active');
		$('#'+section).find('.tabstitles:not(.sub)').siblings('.tabscontent').find('[tabid]:first').addClass('visible');
	}
	
	if (hashData[2] != undefined) {
		if ($('#'+section).find('.tabstitles:not(.sub)').siblings('.tabscontent').find('.tabstitles.sub').length > 0) {
			$('#'+section).find('.tabstitles:not(.sub)').siblings('.tabscontent').find('.tabstitles.sub').each(function() {
				if ($(this).children('li#'+hashData[2]).length > 0) {
					$(this).children('li').removeClass('active');
					$(this).children('li#'+hashData[2]).addClass('active');
					
					$(this).siblings('.tabscontent').find('[tabid]').removeClass('visible');
					$(this).siblings('.tabscontent').find('[tabid="'+hashData[2]+'"]').addClass('visible');
				} else {
					$(this).children('li:first').addClass('active');
					$(this).siblings('.tabscontent').find('[tabid]:first').addClass('visible');
				}
			});
		}
	} else {
		if ($('#'+section).find('.tabstitles:not(.sub)').siblings('.tabscontent').find('.tabstitles.sub').length > 0) {
			$('#'+section).find('.tabstitles:not(.sub)').siblings('.tabscontent').find('.tabstitles.sub').each(function() {
				$(this).children('li').removeClass('active');
				$(this).children('li:first').addClass('active');
				
				$(this).siblings('.tabscontent').find('[tabid]').removeClass('visible');
				$(this).siblings('.tabscontent').find('[tabid]:first').addClass('visible');
			});
		}	
	}
}














/*
	Файлменеджер
		- Каллбэки
			- onOpenFilemanager: событие при открытии окна файлменеджера. Передается селектор файла, на который кликнули
			- onRemoveFile: событие при удалении файла. Передается селектор файла
			- onChooseFile: событие при выборе файла. Передается селектор файла
*/
window.clientFileManager = function(callback) {
	var html = '',
		isLoadedData = false,
		fileTypes = false,
		activeSelector,
		currentDir,
		typesGroups = {
			all: 'png|jpg|jpeg|gif|ico|bmp|mp4|avi|mov|wmv|mpeg|3gp|flv|m4v|mpg|swf|mp3|wav|wma|m3u|ogg|wav|wave|doc|docx|pdf|ppt|pptx|rtf|xls|xlsx|txt|glb|gltf|obj',
			images: 'png|jpg|jpeg|gif|ico|bmp',
			threed: 'glb|gltf|obj',
			videos: 'mp4|avi|mov|wmv|mpeg|3gp|flv|m4v|mpg|swf',
			audios: 'mp3|wav|wma|m3u|ogg|wav|wave',
			docs: 'doc|docx|pdf|ppt|pptx|rtf|xls|xlsx|txt',
		},
		filter = ['#clientFileManager', '[filemanager]', '.editorfile'],
		cb = $.extend({
			onOpenFilemanager: false,
			onRemoveFile: false,
			onChooseFile: false
		}, callback);
	
	
	
	
		
		


	html += '<div class="clientfilemanager" id="clientFileManager">';
	html += 	'<div class="clientfilemanager__dirs mb-2">';
	html += 		'<ul class="noselect" id="clientFilemanagerDirs"></ul>';
	html += 	'</div>';
	html += 	'<div class="clientfilemanager__files noselect" id="clientFilemanagerContentFiles">';
	html += 		'<p class="empty center">Выберите раздел</p>';
	html += 	'</div>';
	html += '</div>';
	$('body').append(html);



	// клик на картинку на странице
	$('body').off(tapEvent, '[filemanager]').on(tapEvent, '[filemanager]', function() {
		if ($(this).find('[fileremove]:hover, [filedelete]:hover').length > 0) return false;
		currentDir = lscache.get('clientmanagerdir') || false,
		activeSelector = this;
		
		let fileTypesData = $(activeSelector).attr('filemanager').split('|'), parsedFilesTypes = '';
		fileTypesData.forEach(function(type) {parsedFilesTypes += (typesGroups[type] || type)+'|';});
		parsedFilesTypes = parsedFilesTypes.slice(0, -1);
		
		
		if (fileTypes && fileTypes != parsedFilesTypes) {
			fileTypes = parsedFilesTypes || 0;
			getAjaxHtml('filemanager/files_get', {directory: currentDir, filetypes: (typesGroups[fileTypes] || fileTypes), client: 1}, function(html) {
				$('#clientFilemanagerContentFiles').html(html);
			});
		} else {
			fileTypes = parsedFilesTypes || 0;
		}

		if ($('#clientFileManager').hasClass('visible') == false || $('#clientFileManager').length == 0) {
			//if (typesGroups[fileTypes] != undefined) fileTypes = typesGroups[fileTypes];
			if (currentDir && !isLoadedData) {
				getAjaxHtml('filemanager/files_get', {directory: currentDir, filetypes: (typesGroups[fileTypes] || fileTypes), client: 1}, function(html) {
					$('#clientFilemanagerContentFiles').html(html);
				});

				getAjaxHtml('filemanager/dirs_get', {current_dir: currentDir}, function(html) {
					$('#clientFilemanagerDirs').html(html);
				});
				isLoadedData = true;
				if (cb.onOpenFilemanager != undefined && typeof cb.onOpenFilemanager == 'function') cb.onOpenFilemanager(activeSelector);
			}
			$('#clientFileManager').addClass('visible');
		}
	});

	// клик на удаление картинки на странице
	$('body').off(tapEvent, '[fileremove], [filedelete]').on(tapEvent, '[fileremove], [filedelete]', function(e) {
		var thisImgBlock = $(this).closest('.file')
			ext = $(thisImgBlock).find('[filemanager]').attr('filemanager'),
			noneImg = 'none.png';

		if (/images|jpg|png|jpeg|ico|bmp/.test(ext)) noneImg = 'none_img.png';

		if ($(this)[0].hasAttribute('fileremove')) {
			$(thisImgBlock).addClass('empty');
			$(thisImgBlock).find('[filename]').text('');
			$(thisImgBlock).find('[fileimage]').children('img').attr('src', './public/images/'+noneImg);
			$(thisImgBlock).find('[filesrc]').val('');
			if (cb.onRemoveFile != undefined && typeof cb.onRemoveFile == 'function') cb.onRemoveFile(thisImgBlock);
		} else if ($(this)[0].hasAttribute('filedelete')) {
			$(thisImgBlock).remove();
			if (cb.onDeleteFile != undefined && typeof cb.onDeleteFile == 'function') cb.onDeleteFile();
		}
	});



	getAjaxHtml('filemanager/dirs_get', {current_dir: currentDir}, function(html) {
		$('#clientFilemanagerDirs').html(html);
	});


	$('#clientFilemanagerDirs').off(tapEvent, '[directory]:not(.disabled):not(.active)').on(tapEvent, '[directory]:not(.disabled):not(.active)', function() {
		var thisDirectory = $(this).attr('directory');
		lscache.set('clientmanagerdir', thisDirectory);
		$('#clientFilemanagerDirs').find('[directory]').removeClass('active');
		$(this).addClass('active');
		$('#clientFilemanagerContentFiles').addClass(' clientfilemanager__files_loading');
		console.log('before');
		getAjaxHtml('filemanager/files_get', {directory: thisDirectory, filetypes: (typesGroups[fileTypes] || fileTypes), client: 1}, function(html, stat) {
			console.log('after', stat);
			$('#clientFilemanagerContentFiles').html(html);
			$('#clientFilemanagerContentFiles').removeClass('clientfilemanager__files_loading');
		});
	});


	// Выбор картинки
	$('#clientFilemanagerContentFiles').off(tapEvent, '.image').on(tapEvent, '.image', function() {
		var thisFileBlock = $(this).closest('.clientfilemanager__file'),
			thisFilePath = $(thisFileBlock).attr('dirfile'),
			thisFileName = $(thisFileBlock).attr('namefile'),
			thisFileSrc = $(this).find('img').attr('src');

		$(activeSelector).closest('.file').removeClass('empty');
		$(activeSelector).closest('.file').find('[filesrc]').prop('value', thisFilePath);
		$(activeSelector).closest('.file').find('[fileimage]').children('img').attr('src', thisFileSrc);
		$(activeSelector).closest('.file').find('[filename]').text(thisFileName);
		$('#clientFileManager').removeClass('visible');
		if (cb.onChooseFile != undefined && typeof cb.onChooseFile == 'function') cb.onChooseFile(activeSelector, {filesrc: thisFilePath, fileimage: thisFileSrc, filename: thisFileName});
		else $(activeSelector).addClass('changed');
	});


	$('body').on(tapEvent, function() {
		if ($(filter.join(':hover, ')+':hover').length == 0) {
			if ($('#clientFileManager').hasClass('visible')) {
				$('#clientFileManager').removeClass('visible');
			}
		}
	});


	$('#clientFileManager').on('mouseenter', function() {
		disableScroll();
	});

	$('#clientFileManager').on('mouseleave', function() {
		enableScroll();
	});
}












window.notify = function(message = '', className = 'success', timeOut) {
	if (!_.isString(className) && _.isNumber(className)) {
		timeOut = className;
		className = 'success';
	} 
	
	let icon = 'fa fa-info';

	if (className == 'success') {
		icon = 'fa fa-check-circle';
	} else if (className == 'error') {
		icon = 'fa fa-exclamation-triangle';
	} else if (className == 'info') {
		icon = 'fa fa-info-circle';
	}

	$.notify.addStyle('ddr', {
		html: '<div><i class="'+icon+'"></i><span data-notify-text/></div>'
	});

	$.notify(message, {
		clickToHide: true,
		autoHide: true,
		autoHideDelay: timeOut ? (timeOut * 1000) : 5000,
		arrowShow: false,
		arrowSize: 15,
		position: 'top right',
		style: 'ddr',
		className: className,
		showAnimation: 'fadeIn',
		showDuration: 200,
		hideAnimation: 'fadeOut',
		hideDuration: 100,
		gap: 2
	});
	
	return {
		close() {
			$('.notifyjs-ddr-base').trigger('notify-hide');
		}
	} 
}









// задать или получить CSS переменную. [Если не указывать значение - то функция вернет значение переменной ]
//	- название переменной (можно без «--»)
//	- значение
window.ddrCssVar = function(variable, value) {
	if (variable === undefined) return false;
	let v = variable.replace('--', '');
	if (value !== undefined) {
		return document.documentElement.style.setProperty('--'+v, value);
	}
	//return document.documentElement.style.getPropertyValue('--'+v);
	return getComputedStyle(document.documentElement).getPropertyValue('--'+v);
}





/*
	функция работы с localStorage
		- ключ
		- значение (если значение не указано - то ведется запрос данных по ключу, если значение - FALSE - то данные по ключу удаляются)
*/
window.ddrStore = function(key, value) {
	if (!key || typeof key != 'string') return false;
	if (value === false) {
		localStorage.removeItem(key);
	} else if (value !== undefined) {
		if (typeof value == 'object') value = JSON.stringify(value);
		localStorage.setItem(key, value);
	} else {
		let getValue = localStorage.getItem(key);
		if (isJson(getValue)) getValue = JSON.parse(getValue);
		return getValue !== null ? getValue : null;
	}
}








/*
	Аналог функции translit из helpers.php
*/
window.translit = function(str, params) {
	let {slug, lower} = _.assign({
		slug: false,
		lower: false
	}, params);
	
	
	let converter = {
		'а': 'a',    'б': 'b',    'в': 'v',    'г': 'g',    'д': 'd',
		'е': 'e',    'ё': 'e',    'ж': 'zh',   'з': 'z',    'и': 'i',
		'й': 'y',    'к': 'k',    'л': 'l',    'м': 'm',    'н': 'n',
		'о': 'o',    'п': 'p',    'р': 'r',    'с': 's',    'т': 't',
		'у': 'u',    'ф': 'f',    'х': 'h',    'ц': 'c',    'ч': 'ch',
		'ш': 'sh',   'щ': 'sch',  'ь': '',     'ы': 'y',    'ъ': '',
		'э': 'e',    'ю': 'yu',   'я': 'ya',
		
		'А': 'A',    'Б': 'B',    'В': 'V',    'Г': 'G',    'Д': 'D',
		'Е': 'E',    'Ё': 'E',    'Ж': 'Zh',   'З': 'Z',    'И': 'I',
		'Й': 'Y',    'К': 'K',    'Л': 'L',    'М': 'M',    'Н': 'N',
		'О': 'O',    'П': 'P',    'Р': 'R',    'С': 'S',    'Т': 'T',
		'У': 'U',    'Ф': 'F',    'Х': 'H',    'Ц': 'C',    'Ч': 'Ch',
		'Ш': 'Sh',   'Щ': 'Sch',  'Ь': '',     'Ы': 'Y',    'Ъ': '',
		'Э': 'E',    'Ю': 'Yu',   'Я': 'Ya',
	};
	
	let strtrData = strtr(str, converter);
	if (slug) strtrData = strtrData.replaceAll(/[_\s]/g, '-');
	if (lower) strtrData = strtrData.toLowerCase();
	return strtrData;
}










window.processNotify = function(message = null) {
	if (_.isNull(message)) return;
	const waitNotifyWrapper = $('body').find('[waitnotify]');
	
	let waitNotifyHtml = '<div class="waitnotify__item" waitnotifyitem>' +
							'<div class="waitnotify__iconcontainer">' +
								'<div class="waitnotify__icon" waitnotifyitemwait><img src="/assets/images/loading.gif" ddrwaiticon></div>' +
							'</div>' +
							'<div class="waitnotify__message" waitnotifymessage>'+message+'</div>' +
						 '</div>';
	
	const waitNotifyDOM = $(waitNotifyHtml);
	
	if ($(waitNotifyWrapper).length == 0) {
		$('body').append('<div class="waitnotify noselect" waitnotify></div>');
		$('[waitnotify]').html(waitNotifyDOM);
	} else {
		$(waitNotifyWrapper).append(waitNotifyDOM); // prepend
	}
	
	$(waitNotifyDOM).on(tapEvent, function() {
		$(this).remove();
	});
	
	$.extend(waitNotifyDOM, {
		done(params) {
			const item = this,
				{message, close, iconFa, icon} = _.assign({
					message: null,
					iconFa: '<i class="fa-regular fa-fw fa-circle-check"></i>',
					icon: null,
					close: 5
				}, params);
			
			$(item).addClass('waitnotify__item_done');
			if (message) $(item).find('[waitnotifymessage]').html(message);
			$(item).find('[waitnotifyitemwait]').html(icon || iconFa);
			
			setTimeout(() => {
				$(item).remove();
			}, close * 1000);
		},
		cancelled(params) {
			const item = this,
				{message, close, iconFa, icon} = _.assign({
					message: null,
					iconFa: '<i class="fa-solid fa-fw fa-ban"></i>',
					icon: null,
					close: 5
				}, params);
			
			$(item).addClass('waitnotify__item_cancelled');
			if (message) $(item).find('[waitnotifymessage]').html(message);
			$(item).find('[waitnotifyitemwait]').html(icon || iconFa);
			
			setTimeout(() => {
				$(item).remove();
			}, close * 1000);
		},
		error(params) {
			const item = this,
				{message, close, iconFa, icon} = _.assign({
					message: null,
					iconFa: '<i class="fa-solid fa-fw fa-triangle-exclamation"></i>',
					icon: null,
					close: 5
				}, params);
			
			$(item).addClass('waitnotify__item_error');
			if (message) $(item).find('[waitnotifymessage]').html(message);
			$(item).find('[waitnotifyitemwait]').html(icon || iconFa);
			
			setTimeout(() => {
				$(item).remove();
			}, close * 1000);
		}
	});
	
	return waitNotifyDOM;
}












































window.isNumeric = function(num) {
	return !_.isNaN(num) && !_.isBoolean(num) && !_.isString(num) && !_.isNull(num);
}




window.isHover = (selector = null) => {
	if (!selector) return false;
	if (_.isArray(selector)) selector = selector.join(', ')
	return !!$(selector).filter(function() {
		return $(this).is(":hover"); 
	}).length;
}




/*
	Является ли строка null
	- строка
*/
window.isNull = function(str) {
	return str === null;
}


/*
	Является ли строка json
	- строка
*/
window.isJson = function(str = null) {
	if (_.isNull(str) || typeof str == 'undefined') return false;
	try {
		return !!JSON.parse(str);
	} catch (e) {
		return false;
	}
	return true;
}


/*
	Является ли строка целым числом
*/
window.isInt = function(n) {
	if (n == undefined || typeof n == 'undefined') return false;
	if (typeof n != 'string') return Number(n) === n && n % 1 === 0;
	return Number(n)+'' === n;
}


/*
	Является ли строка числом с плавающей точкой
*/
window.isFloat = function(n) {
	if (n == undefined || typeof n == 'undefined') return false;
	if (typeof n != 'string') return Number(n) === n && n % 1 !== 0;
	return Number(n)+'' === n && Number(n) % 1 !== 0;
}


/*
	проверить наличие элемента в массиве или объекте
		- массив или объект
		- искомый элемент
		- является ли ключем искомый элемент

	ВНИМАНИЕ!!! Может возвращать 0 - это найденный индекс
*/
window.hasIn = function(data, elem, isKey) {
	if (elem == undefined || data == undefined || data.length == 0) return false;
	var findKey;
	if (isKey != undefined && isKey == true) {
		var keysData = Object.keys(data);
		findKey = keysData.indexOf(elem);

		if (findKey != -1) {
			return data[keysData[findKey]];
		}
		return false;
	}

	findKey = data.indexOf(elem);
	return (findKey != -1 ? findKey : false);
}







/*
	Проверка существования файла
		- путь до файла
*/
window.urlExists = function(url) {
	var http = new XMLHttpRequest();
	http.open('HEAD', url, false);
	http.send();
	return http.status != 404;
}











window.buildFolders = function(folders = null, fileName = null) {
	if (!folders || !fileName) return false;
	
	const zip = new JSZip();
	createFolders(zip, folders);
	
	zip.generateAsync({ type: "blob" })
        .then(function(content) {
            saveAs(content, fileName);
        });
       
	function createFolders(zip, folders) {
        for (const [key, value] of Object.entries(folders)) {
            const subFolder = zip.folder(key);
            if (Array.isArray(value)) {
                value.forEach(subValue => subFolder.folder(subValue));
            } else if (typeof value === 'object') {
                createFolders(subFolder, value);
            }
        }
    }
}





window.capitalFirstLetter = function(str = null) {
	if (!str) return str;
	str = str.toLowerCase();
	return str.charAt(0).toUpperCase() + str.slice(1);
}
