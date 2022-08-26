import "./index.css";

window.ddrPopup = function(settings = {}, callback = false) {
	let {
		url,
		method,
		params,
		title, // заголовок
		width, // ширина окна
		frameOnly, // Загрузить только каркас
		html, // контент
		lhtml, // контент из языковых файлов
		buttons, // массив кнопок
		buttonsAlign, // выравнивание вправо
		disabledButtons, // при старте все кнопки кроме закрытия будут disabled
		closeByBackdrop, // Закрывать окно только по кнопкам [ddrpopupclose]
		changeWidthAnimationDuration, // ms
		buttonsGroup, // группа для кнопок
		winClass, // добавить класс к модальному окну
		centerMode, // контент по центру
		topClose // верхняя кнопка закрыть
	} = _.assign({
		url: null,
		method: 'post',
		params: {},
		title: null,
		width: '500px',
		frameOnly: false,
		html: '',
		lhtml: null,
		buttons: null,
		buttonsAlign: 'end',
		disabledButtons: false,
		closeByBackdrop: true,
		changeWidthAnimationDuration: '300ms',
		buttonsGroup: 'small',
		winClass: null,
		centerMode: false,
		topClose: true
	}, settings),
	closeWinAnimationDuration = 0.2,
	changeWidthAnimationTOut,
	popupCloseTOut,
	buttonsHtml = '',
	topCloseHtml = '',
	titleHtml = '',
	popupHtml = '',
	ddrPopupId = 'ddrpopup'+generateCode('LlnlL'),
	ddrPopupSelector = '#'+ddrPopupId,
	controller  = new AbortController(),
	prObj = {isClosed: false},
	pr = new Proxy(prObj, {});
	
	
	
	const methods = {
		state: pr, // isClosed
		wait(stat = true) {
			if (stat) _showWait();
			else _hideWait();
		},
		setTitle(title = null, cb = false) {
			if (!title) throw new Error('ddrPopup -> setTitle ошибка - данные переданы неверно!');
			_insertData({title}, function(data) {
				if (cb && typeof cb == 'function') cb(data); 
			});
		},
		setButtons(buttons, cb = false) {
			if (!buttons) throw new Error('ddrPopup -> setButtons ошибка - данные переданы неверно!');
			_insertData({buttons}, function(data) {
				if (cb && typeof cb == 'function') cb(data); 
			});
		},
		loadData(url = null, params = {}, addict = {}, cb = false) {
			if (!url) throw new Error('ddrPopup -> loadData ошибка - данные переданы неверно!');
			
			if (addict && typeof addict == 'function') cb = addict;
			if (params && typeof params == 'function') cb = params;
			
			addict = _.assign(_.pick(params, ['buttons', 'title']), addict);
			params = _.omit(params, ['buttons', 'title']);
			
			_insertData({url, params, ...addict}, function(data) {
				if (cb && typeof cb == 'function') cb(data); 
			});
		},
		setHtml(html = null, addict = {}, cb = false) {
			if (!html) throw new Error('ddrPopup -> setHtml ошибка - данные переданы неверно!');
			if (addict && typeof addict == 'function') cb = addict;
			let params = _.assign({html}, addict);
			
			_insertData(params, function(data) {
				if (cb && typeof cb == 'function') cb(data); 
			});
		},
		setLHtml(lhtml = null, addict = {}, cb = false) {
			if (!lhtml) throw new Error('ddrPopup -> setHtml ошибка - данные переданы неверно!');
			if (addict && typeof addict == 'function') cb = addict;
			let params = _.assign({lhtml}, addict);
			
			_insertData(params, function(data) {
				if (cb && typeof cb == 'function') cb(data); 
			});
		},
		dialog(text = null, params = {}, cb = false) {
			if (!text || !params.buttons || typeof params.buttons != 'object') throw new Error('ddrPopup -> dialog ошибка - данные переданы неверно!');
			if (params && typeof params == 'function') cb = params;
			let buttonsData = params.buttons || false;
			
			params.buttons = _.mapValues(Object.keys(params.buttons), function(btnStr) {
				return btnStr.split('|');
			});
			
			_insertDialog(text, params, function(data) {
				_setDialogPositing();
				let dialogH = $(ddrPopupSelector).find('[ddrpopupdialogwin]').outerHeight();
				$(ddrPopupSelector).scroll(function() {
					let winPos = _getWinPosition();
					$(ddrPopupSelector).find('[ddrpopupdialogwin]').css('top', 'calc(50vh - '+(dialogH / 2)+'px + '+winPos+'px)');
				});
				
				if (buttonsData) {
					let iter = 0;
					$.each(buttonsData, function(title, callback) {
						$('#ddrpopupDialogBtn'+iter).on(tapEvent, function() {
							if (callback && typeof callback == 'function') callback({closeDialog: _closeDialog});
							else $[callback]({closeDialog: _closeDialog});
						});
						iter++;
					});
				}
				
				$('#popupDialogClose').on(tapEvent, function() {
					$(ddrPopupSelector).find('[ddrpopupdialog]').remove();
				});
				
				
				if (cb && typeof cb == 'function') cb(data); 
			});
		},
		close() {
			_close();
		},
		onClose(callback) {
			$(ddrPopupSelector).on('ddrpopup:close', () => {
				if (callback && typeof callback == 'function') callback();
			});
		},
		onScroll(callback, latency = 100) {
			$(ddrPopupSelector).on("scrollstart", function() {
				if (callback && typeof callback == 'function') callback('start');
				$(ddrPopupSelector).trigger('ddrpopup:scrollstart');
			});
			$(ddrPopupSelector).on("scrollstop", {latency: latency}, function() {
				if (callback && typeof callback == 'function') callback('stop');
				$(ddrPopupSelector).trigger('ddrpopup:scrollstop');
			});
		},
		disableButtons: function(buttonsSelectors = null, closeBtn = true) { // true|false|selectors  true|false
			let buttonsBlock = $(ddrPopupSelector).find('[ddrpopupbuttons]'),
				topCloseBlock = $(ddrPopupSelector).find('[ddrpopupheader]');
			
			if (!$(buttonsBlock).length) return false;
			
			if (isNull(buttonsSelectors) || buttonsSelectors === false)  {
				$(buttonsBlock).find('button:not([ddrpopupclose])').ddrInputs('disable');
			} else if (buttonsSelectors === true) {
				$(buttonsBlock).find('button:not([disabled])').ddrInputs('disable');
				$(topCloseBlock).find('[ddrpopupclose]').setAttrib('disabled');
				
			} else if (buttonsSelectors === 'close') {
				$(buttonsBlock).find('button[ddrpopupclose]').ddrInputs('disable');
				$(topCloseBlock).find('[ddrpopupclose]').setAttrib('disabled');
			} else {
				if (typeof buttonsSelectors != 'object') buttonsSelectors = pregSplit(buttonsSelectors); 
				buttonsSelectors.forEach(btn => {
					$(buttonsBlock).find(btn).ddrInputs('disable');
				});
				if (closeBtn) {
					$(buttonsBlock).find('button[ddrpopupclose]').ddrInputs('disable');
					$(topCloseBlock).find('[ddrpopupclose]').setAttrib('disabled');
				} 
			}
		},
		enableButtons: function(buttonsSelectors = null, closeBtn = true) { // true|false|close|selectors  true|false
			let buttonsBlock = $(ddrPopupSelector).find('[ddrpopupbuttons]'),
				topCloseBlock = $(ddrPopupSelector).find('[ddrpopupheader]');
			
			if (!$(buttonsBlock).length) return false;
			
			if (isNull(buttonsSelectors) || buttonsSelectors === false)  {
				$(buttonsBlock).find('button:not([ddrpopupclose])').ddrInputs('enable');
			} else if (buttonsSelectors === true) {
				$(buttonsBlock).find('button[disabled]').ddrInputs('enable');
				$(topCloseBlock).find('[ddrpopupclose]').removeAttrib('disabled');
			} else if (buttonsSelectors === 'close') {
				$(buttonsBlock).find('button[ddrpopupclose]').ddrInputs('enable');
				$(topCloseBlock).find('[ddrpopupclose]').removeAttrib('disabled');
			} else {
				if (typeof buttonsSelectors != 'object') buttonsSelectors = pregSplit(buttonsSelectors); 
				buttonsSelectors.forEach(btn => {
					$(buttonsBlock).find(btn).ddrInputs('enable');
				});
				if (closeBtn) {
					$(buttonsBlock).find('button[ddrpopupclose]').ddrInputs('enable');
					$(topCloseBlock).find('[ddrpopupclose]').removeAttrib('disabled');
				} 
			}
		},
		setWidth: function(width) {
			_setWidth(width, true);
		},
	};
	
	
	
	
	//------------------------------------------------------------------------------------------------
	
	
	
	
	
	//if ((!html && !lhtml) && !url) return Promise.reject('-1');
	
	
	ddrCssVar('popup-animate-duration', changeWidthAnimationDuration);
	
	_setWidth(width);
	
	_initCarcass();
	
	const popUpObj = new Promise(function(resolve, reject) {
		if (frameOnly) {
			resolve(methods);
		} else {
			try {
				_insertData({url, params, html, lhtml, title, buttons}, function() {
					resolve(methods);
				}, true);
			} catch(e) {
				reject(e);
			}
		}	
	});
	
	
	
	
	
	
	
	
	
	
	if (closeByBackdrop) {
		let target,
			evStart = thisDevice == 'mobile' && !isIos ? 'touchstart' : 'mousedown',
			evEnd = thisDevice == 'mobile' && !isIos ? 'touchend' : 'mouseup';
		
		$(ddrPopupSelector).on(evStart, function(e) {
			target = e?.target;
		});
		
		$(ddrPopupSelector).on(evEnd, function(e) {
			if (target == e?.target && tapEventInfo(e, {attribute: 'ddrpopupwrap'})) _close();
		});
		
		$(ddrPopupSelector).find('[ddrpopupwin], [ddrpopupdialog]').on(evEnd, function(e) {
			e.stopPropagation();
		});
		
		/*$(ddrPopupSelector).on(evEnd, '[ddrpopupdialog]', function(e) {
			console.log('ddrpopupdialog');
			e.stopPropagation();
		});*/
	}
	
	
	$(ddrPopupSelector).on(tapEvent, '[ddrpopupclose]', function(e) {
		_close();
	});
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------------------------------------
	
	
	
	function _initCarcass() {
		if (!ddrPopupId) throw new Error('ddrPopup -> _initCarcass ошибка - не определен ID окна!');
		let html = '';
		html +=	'<div class="ddrpopup" id="'+ddrPopupId+'" ddrpopup>';
		html +=		'<div class="ddrpopup__wrap" ddrpopupwrap>';
		html +=			'<div class="ddrpopup__container">';
		html +=				'<div class="ddrpopup__win noselect'+(winClass ? ' '+winClass : '')+'" ddrpopupwin>';
		html +=					'<div class="ddrpopup__wait" ddrpopupwait>';
		html +=						'<div class="ddrpopupwait" ddrpopupwaitblock>';
		html +=							'<img src="/assets/images/loading.gif" ddrwaiticon class="ddrpopupwait__icon">';
		html += 						'<p class="ddrpopupwait__label"></p>';
		html +=						'</div>';
		html += 				'</div>';
		html += 				'<div class="ddrpopup__content" ddrpopupcontent></div>';
		html += 			'</div>';
		html += 		'</div>';
		html += 	'</div>';
		html +=	'</div>';
		
		
		if ($('body').find('[ddrpopup]').length) {
			$('body').find('[ddrpopup]').replaceWith(html);
		} else {
			$('body').append(html);
		}
		
		_open();
	}
	
	
	
	function _insertData({url = null, params = {}, html = null, lhtml = null, title = null, buttons = null}, callback = false, init = false) {
		//if (!url && !html && !lhtml && !title && !buttons) throw new Error('ddrPopup -> _insertData ошибка - не переданы необходимые данные!');
		_showWait();
		
		if ((url || html || lhtml) && (title || buttons)) {
			Promise.all([_getLayout({title, buttons}), _getData(url, params, html, lhtml)])
				.then(function ([{data: layoutDoc, status: lStat, headers: lHeaders}, {data: dataHtml, status: dStat, headers: dHeaders}]) {
					
				if (!layoutDoc || (dataHtml.status && dataHtml.status != 200)) {
					if (dataHtml.message) $.notify(dataHtml.message, 'error');
					else $.notify('Не удалось загрузить данные!', 'error');
					_close();
					if (callback && typeof callback == 'function') callback(false);
					return false;
				}
				
				if (dataHtml) $(layoutDoc).find('[ddrpopupdata]').html(dataHtml);
				
				let header = $(layoutDoc).find('[ddrpopupheader]').length ? $(layoutDoc).find('[ddrpopupheader]')[0].outerHTML : title,
					content = $(layoutDoc).find('[ddrpopupdata]').length ? $(layoutDoc).find('[ddrpopupdata]')[0].outerHTML : null,
					footer = $(layoutDoc).find('[ddrpopupfooter]').length ? $(layoutDoc).find('[ddrpopupfooter]')[0].outerHTML : buttons;
				
				
				
				_setContent({header, content, footer});
				_hideWait();
				if (callback && typeof callback == 'function') callback(true);
				
			}, reason => {
				console.log('reason', reason);
				_hideWait();
				if (callback && typeof callback == 'function') callback(false);
			}).catch(function(e) {
				console.log('catch', e);
				console.log('ddr catch');
				_hideWait();
				if (callback && typeof callback == 'function') callback(false);
			});
			
		} else if ((title || buttons) && (!url && !html && !lhtml)) {
			_getLayout({title, buttons}).then(function({data: layoutDoc, status: lStat, headers: lHeaders}) {
				let header = $(layoutDoc).find('[ddrpopupheader]').length ? $(layoutDoc).find('[ddrpopupheader]')[0].outerHTML : title,
					content = $(layoutDoc).find('[ddrpopupdata]').length ? $(layoutDoc).find('[ddrpopupdata]')[0].outerHTML : null,
					footer = $(layoutDoc).find('[ddrpopupfooter]').length ? $(layoutDoc).find('[ddrpopupfooter]')[0].outerHTML : buttons;
					
				_setContent({header, content, footer});
				_hideWait();
				if (callback && typeof callback == 'function') callback(true);
			}, reason => {
				console.log('reason', reason);
				_hideWait();
				if (callback && typeof callback == 'function') callback(false);
			}).catch(function(e) {
				console.log('catch', e);
				_hideWait();
				if (callback && typeof callback == 'function') callback(false);
			});
			
			
		} else if ((url || html || lhtml) && (!title && !buttons)) {
			_getData(url, params, html, lhtml).then(function({data: dataHtml, status, headers}) {
				if (dataHtml?.status != 200 && dataHtml?.message) {
					$.notify(dataHtml?.message, 'error');
					_close();
					if (callback && typeof callback == 'function') callback(false);
				}
				
				_setContent({header: title, content: dataHtml, footer: buttons});
				_hideWait();
				if (callback && typeof callback == 'function') callback(true);
				
			}, reason => {
				console.log('reason', reason);
				_hideWait();
				if (callback && typeof callback == 'function') callback(false);
			}).catch(function(e) {
				console.log('catch', e);
				_hideWait();
				if (callback && typeof callback == 'function') callback(false);
			});
		}
		
	}
	
	
	
	
	function _insertDialog(text = null, params = {}, callback = false) {
		let dialogData = _.assign({text}, params);
		
		_getLayout({dialog: dialogData}).then(function({data: layoutDoc, status: lStat, headers: lHeaders}) {
			let dialog = $(layoutDoc).find('[ddrpopupdialog]').length ? $(layoutDoc).find('[ddrpopupdialog]')[0].outerHTML : dialog;
			_setContent({dialog});
			$(ddrPopupSelector).find('[ddrpopupdialog]').addClass('ddrpopup__dialog_visible');
			if (callback && typeof callback == 'function') callback(true);
		});
	}
	
	
	
	
	
	
	function _getData(url = null, params = null, html = null, lhtml = null) {
		if (!url && !html && !lhtml) {
			$.notify('Ошибка загрузки данных', 'error');
			throw new Error('ddrPopup -> _getData должен быть либо url, либо html, либо lhtml!');
		}
		 
		if (html) return Promise.resolve({data: html});
		
		if (lhtml) {
			return axios.post('/ajax/langline', {line: lhtml}, {
				responseType: 'text',
				signal: controller.signal
			});
		}
		
		if (url.substr(0, 1) != '/') url = '/'+url;
		
		return axios[method](url, params, {
			responseType: 'text',
			signal: controller.signal
		});
	}
	
	
	
	function _getLayout({title = null, buttons = null, dialog = null}) {
		return axios.post('/ajax/popup',{
			title,
			buttons,
			buttonsGroup,
			disabledButtons,
			buttonsAlign,
			topClose,
			dialog,
			//winClass,
			centerMode
		}, {
			responseType: 'document',
			signal: controller.signal
		});
	}
	
	
	function _setContent({header = null, content = null, footer = null, dialog = null}) {
		if (!header && !content && !footer && !dialog) throw new Error('ddrPopup -> _setContent ошибка - не переданы параметры!');
		
		let popupContent = $(ddrPopupSelector).find('[ddrpopupcontent]');
		
		if (header !== null) {
			if (header === false) $(popupContent).find('[ddrpopupheader]').remove();
			else if ($(popupContent).find('[ddrpopupheader]').length) $(popupContent).find('[ddrpopupheader]').replaceWith(header);
			else $(popupContent).prepend(header);
		}
		
		if (content) {
			if ($(popupContent).find('[ddrpopupdata]').length) $(popupContent).find('[ddrpopupdata]').html(content);
			else $(popupContent).append(content);
		}
		
		if (footer !== null) {
			if (footer === false) $(popupContent).find('[ddrpopupfooter]').remove();
			else if ($(popupContent).find('[ddrpopupfooter]').length) $(popupContent).find('[ddrpopupfooter]').replaceWith(footer);
			else $(popupContent).append(footer);
		}
		
		if (dialog !== null) {
			if ($(popupContent).find('[ddrpopupdialog]').length) $(popupContent).find('[ddrpopupdialog]').replaceWith(dialog);
			else $(popupContent).append(dialog);
		}
		
	}
	
	
	function _showWait() {
		if ($('[ddrpopupwait]').not('.ddrpopup__wait_visible')) $('[ddrpopupwait]').addClass('ddrpopup__wait_visible');
	}
	
	
	function _hideWait() {
		if ($('[ddrpopupwait].ddrpopup__wait_visible')) $('[ddrpopupwait]').removeClass('ddrpopup__wait_visible');
	}
	
	
	
	
	function _setWidth(width = null, animated = false, showWait = false) {
		clearTimeout(changeWidthAnimationTOut);
		if (!width) width = '500px';
		let duration = parseInt(ddrCssVar('popup-animate-duration'));
		
		if (_getWinWidth() == parseInt(width)) return false;
		if (showWait) _showWait();
		
		width = isInt(width) ? width+'px' : width;
		
		if (animated) {
			$(ddrPopupSelector).find('.ddrpopup__win:not(.ddrpopup__win_animated)').addClass('ddrpopup__win_animated');
			
			ddrCssVar('popup-width', width);
			
			changeWidthAnimationTOut = setTimeout(() => {
				$(ddrPopupSelector).find('.ddrpopup__win.ddrpopup__win_animated').removeClass('ddrpopup__win_animated');
				if (showWait) _hideWait();
			}, duration);
			
		} else {
			ddrCssVar('popup-width', width);
		}	
	}
	
	
	function _getWinWidth() {
		return $(ddrPopupSelector).find('[ddrpopupwin]').outerWidth();
	}
	
	function _getWinHeight() {
		return $(ddrPopupSelector).find('[ddrpopupwin]').outerHeight();
	}


	function _getWinPosition() {
		return $(ddrPopupSelector).scrollTop();
	}
	
	
	
	function _setDialogPositing() {
		let winH = $(window).height(),
			popupH = _getWinHeight(),
			winPos = _getWinPosition(),
			dialogH = $(ddrPopupSelector).find('[ddrpopupdialogwin]').outerHeight();
			
		function _set() {
			if (popupH > winH) {
				$(ddrPopupSelector).find('[ddrpopupdialog].popupdialog_centred').removeClass('popupdialog_centred');
				$(ddrPopupSelector).find('[ddrpopupdialogwin]').css('top', 'calc(50vh - '+(dialogH / 2)+'px + '+winPos+'px)');
			} else {
				$(ddrPopupSelector).find('[ddrpopupdialog]:not(.popupdialog_centred)').addClass('popupdialog_centred');
			}
		}
		
		_set();
		
		$(window).resize(function() {
			_set();
		});
	}
	

	function _open() {
		disableScroll();
		prObj.isClosed = false;
		$(ddrPopupSelector).addClass('ddrpopup_opening');
		$(ddrPopupSelector).find('.ddrpopup__win').addClass('ddrpopup__win_opening');
	};

	function _close() {
		clearTimeout(popupCloseTOut);
		controller.abort();
		prObj.isClosed = true;
		methods.onClose();
		$(ddrPopupSelector).trigger('ddrpopup:close');
		$(ddrPopupSelector).addClass('ddrpopup_closing');
		$(ddrPopupSelector).find('.ddrpopup__win').addClass('ddrpopup__win_closing');
		popupCloseTOut = setTimeout(function() {
			$(ddrPopupSelector).remove();
			enableScroll();
		}, (closeWinAnimationDuration * 1000));
		
	};
	
	function _closeDialog() {
		$(ddrPopupSelector).find('[ddrpopupdialog]').remove();
		controller.abort();
		controller  = new AbortController();
	};
	
	
	
	return popUpObj;
}
