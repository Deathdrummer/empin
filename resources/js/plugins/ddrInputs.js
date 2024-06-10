/*
	Работа с инпутами
	
	методы
		- clear: очистить инпуты, убрать ошибки
		- error: добавить ошибку
		- disable: запретить
		- enable: разрешить
		- value: задать значение
		- checked: пометить галочкой
		- selected: выбрать пункт вып. списка
		- setOptions: добавить пункты вып. списка
		- file: работа с файлами
		- change: событие изменения поля или полей
		- state: комплексный метод, команды:
			- clear: убрать ошибки, иземенность
		- addClass: добавить класс к обертке инпута
		- removeClass: убрать класс у обертки инпута
		
		
		- сделать элемент некликабельным, напрмер, чекбокс:
			$(input).ddrInputs('addClass', 'notouch');
*/


$.fn.ddrInputs = function(method = false, ...params) {
	const items = this,
		isMultiple = $(this).attr('multiple') !== undefined,
		fieldName = $(this).attr('name') || 'file',
		hasMethod = _.indexOf(['clear', 'error', 'disable', 'enable', 'value', 'checked', 'selected', 'setOptions', 'file', 'change', 'state', 'addClass', 'removeClass'], method) >= 0,
		proxyData = new Proxy({}, {});
		
	if (!hasMethod) throw new Error('ddrInputs -> такого метода нет');
	
	if (hasMethod && items.length) {
		const methods = new DdrInput(items, proxyData, method);
		methods[method](...params);
	}
	
	// Возвращаем методы
	return {
		getFiles() {
			let filesData = {};
			$.each(proxyData, (k, file) => {
				filesData[fieldName+'['+file.key+']'] = file;
			});
			return filesData;
		},
		removeFile(index = false, count = 1) {
			if (index === false) return false;
			if (proxyData[index] !== undefined) delete proxyData[index];
		},
		getFormFiles() {
			return Object.values(proxyData);
			
			//let formData = new FormData();
			/*if (Object.keys(proxyData).length == 0) return false;
			
			$.each(proxyData, (k, file) => {
				formData.append(fieldName+'['+file.key+']', file, file.name);
			});
			
			return formData;*/
		}
	}
}




class DdrInput {
	
	inputs = [];
	proxyData;
	selector;
	
	constructor(items, proxyData, method) {
		if (!items) return false;
		
		this.proxyData = proxyData;
		
		if (['change', 'state', 'enable', 'disable', 'addClass', 'removeClass'].indexOf(method) !== -1) {
			if (items.length == 1 && (['input', 'select', 'textarea', 'button'].indexOf(items[0]?.tagName?.toLowerCase()) == -1 && !$(items).hasAttr('contenteditable') && !$(items).hasAttr('datepicker'))) {
				this.selector = items;
				items = items.find('input, select, textarea, button, [contenteditable], [datepicker]');
			}
		}
		
		
		let allData = [];
		items.each(function(k, item) {
			let tag = item?.tagName?.toLowerCase(),
				type = typeof $(item).attr('contenteditable') !== 'undefined' ? 'contenteditable' : (item?.type ? item?.type?.toLowerCase()?.replace('select-one', 'select') : null),
				group = typeof $(item).attr('inpgroup') !== 'undefined' ? $(item).attr('inpgroup')+'-' : '',
				wrapperClass = findWrapByInputType.indexOf(type) !== -1 ? group+type : group+tag,
				wrapperSelector = $(item).closest('.'+wrapperClass).length ? $(item).closest('.'+wrapperClass) : false;
			
			allData.push({
				item,
				tag,
				type,
				group,
				wrapperClass,
				wrapperSelector
			});
		});
		
		this.inputs = allData.length ? allData : null;
	}
	
	
	
	
	
	
	
	
	
	/* input type file событие изменения поля, на выходе даные загружаемого файла
	- settings
		- sync - каллбэк на каждый файл или при загрузке всех файлов
	
	Возвращает данные файла:
		- имя
		- расширение
		- тип
		- размер
		- предзагруженную картинку base64 (если файл - изображение) */
	file(params = {}) {
		if (!this.inputs) return false;
		
		let {compress, watermark, background, before, callback, fail, proxy} = _.assign({
			compress: {},
			watermark: {},
			background: false,
			before: false,
			callback: false,
			fail: false,
			proxy: false
		}, params),
			compressor = this.#compress,
			proxyData = this.proxyData;
		
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if (type !== 'file') return true;
				
			compress = _.assign({
				quality: 0.8,
				width: undefined,
				height: undefined,
				minWidth: 0,
				minHeigh: 0,
				maxWidth: 'infinity',
				maxHeight: 'infinity',
				resize: 'none', // none, contain,  cover.
			}, compress);
			
			
			$(item).on('input', function(e) {
				if (e.currentTarget.files.length == 0) return false;
				
				let reader,
					files = e.currentTarget.files,
					cbIters = 0,
					complete = false,
					isMultiple = isMultiple = tapEventInfo(e, {attribute: 'multiple'});
				
				if (e.currentTarget.type != 'file') return false;
				
				if (!files.length) {
					if (fail && typeof fail == 'function') fail(item, 'nofiles');
					return false;
				} else {
					if (before && typeof before == 'function') before({item, count: e.currentTarget.files.length});
				}
				
				
				$.each(files, async function(__, rawFile) {
					let isImage = isImgFile(rawFile),
						fileToView = isImage ? await compressor(rawFile, {compress, watermark, background}) : rawFile;
					
					rawFile.key = ddrHash(rawFile.name+rawFile.lastModified+rawFile.size, 2);
					
					reader = new FileReader();
					reader.onload = function(e) {
						if (e.target.error) {
							if (fail && typeof fail == 'function') fail(item, 'errorload');
							return false;
						}
						
						let [name, ext] = getFileName(rawFile);
						
						proxyData[rawFile.key] = rawFile;
						
						complete = (files.length === (++cbIters));
						if (callback && typeof callback === 'function') {
							callback({
								selector: item,
								file: rawFile,
								name,
								ext,
								key: rawFile.key,
								type: rawFile.type,
								size: rawFile.size,
								preview: isImage ? e.target.result : null
							}, complete);
						}
						
						if (complete) {
							if (proxy && typeof proxy === 'function') proxy(proxyData);
						}
					};
					
					reader.readAsDataURL(fileToView);
				});
				
			});		
			
		});	
	}
	
	
	
	
	
	disable() {
		if (!this.inputs) return false;
		
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if (wrapperSelector) {
				if ($(wrapperSelector).hasClass(wrapperClass+'_disabled') === false) $(wrapperSelector).addClass(wrapperClass+'_disabled');
				if (type === 'contenteditable') {
					$(item).attr('contenteditable', false);
				} else {
					$(item).setAttrib('disabled');
					
					const iconSelector = $(item).parent().find('input[datepicker]').length ? $(item).parent().find('.icon') : false;
					if (iconSelector) {
						$(iconSelector).removeClass('icon_active');
						$(iconSelector).html('<i class="fa-solid fa-fw fa-calendar-days"></i>'); 
					}
				}
			}
		});	
	}
	
	
	
	
	enable() {
		if (!this.inputs) return false;
		
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if (wrapperSelector) {
				if ($(wrapperSelector).hasClass(wrapperClass+'_disabled')) $(wrapperSelector).removeClass(wrapperClass+'_disabled');
				if (type === 'contenteditable') {
					$(item).attr('contenteditable', true);
				} else {
					$(item).removeAttrib('disabled');
				}
			}
		});	
	}
	
	
	
	
	value(val = null) {
		if (!this.inputs || val === null) return false;
		
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if (wrapperSelector && ['checkbox', 'radio', 'select'].indexOf(type) === -1) {
				if (val !== false) {
					if (type === 'contenteditable') $(item).text(val);
					else $(item).val(val);
					
					if ($(wrapperSelector).hasClass(wrapperClass+'_changed') === false) $(wrapperSelector).addClass(wrapperClass+'_changed');
					if ($(wrapperSelector).hasClass(wrapperClass+'_noempty') === false && val) $(wrapperSelector).addClass(wrapperClass+'_noempty');
				} else {
					if (type === 'contenteditable') $(item).text('');
					else $(item).val('');
					
					if ($(wrapperSelector).hasClass(wrapperClass+'_changed') === false) $(wrapperSelector).addClass(wrapperClass+'_changed');
					if ($(wrapperSelector).hasClass(wrapperClass+'_noempty')) $(wrapperSelector).removeClass(wrapperClass+'_noempty');
				}
			}
		});	
	}
	

	
	
	checked(stat = true) {
		if (!this.inputs) return false;
		
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if (wrapperSelector && ['checkbox', 'radio'].indexOf(type) !== -1) {
				if (stat === true) {
					if ($(wrapperSelector).hasClass(wrapperClass+'_checked') === false) $(wrapperSelector).addClass(wrapperClass+'_checked');
					if (type == 'checkbox' && $(wrapperSelector).hasClass(wrapperClass+'_changed') === false)
						$(wrapperSelector).addClass(wrapperClass+'_changed');
					
					
					if (type == 'radio') {
						let radioName = $(item).attr('name');
						$('body').find('input[name="'+radioName+'"]').not(item).removeAttrib('checked');
						$('body').find('input[name="'+radioName+'"]').not(item).closest('.'+wrapperClass).removeClass(wrapperClass+'_checked');
					}
					
					if ($(item).prop('checked') === false) {
						$(item).prop('checked', true);
						$(item).setAttrib('checked');
						$(wrapperSelector).find('.'+wrapperClass+'__errorlabel').empty();
					}
					
				} else if (stat === false) {
					if ($(wrapperSelector).hasClass(wrapperClass+'_checked')) $(wrapperSelector).removeClass(wrapperClass+'_checked');
					if (type == 'checkbox' && $(wrapperSelector).hasClass(wrapperClass+'_changed') === false)
						$(wrapperSelector).addClass(wrapperClass+'_changed');
					
					if ($(item).prop('checked')) {
						$(item).prop('checked', false);
						$(item).removeAttrib('checked');
						$(wrapperSelector).find('.'+wrapperClass+'__errorlabel').empty();
					}
				}
			}
		});	
	}
	
	
	
	selected(val = null) {
		if (!this.inputs || val === null) return false;
		
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if (wrapperSelector && ['select'].indexOf(type) !== -1) {
				if (val !== false) {
					$(item).val(val);
					if ($(wrapperSelector).hasClass(wrapperClass+'_changed') === false) $(wrapperSelector).addClass(wrapperClass+'_changed');
					if ($(wrapperSelector).hasClass(wrapperClass+'_noempty') === false) $(wrapperSelector).addClass(wrapperClass+'_noempty');
				} else {
					$(item).children('option').prop('selected', false);
					$(item).children('option:first').prop('selected', true);
					if ($(wrapperSelector).hasClass(wrapperClass+'_changed') === false) $(wrapperSelector).addClass(wrapperClass+'_changed');
					if ($(wrapperSelector).hasClass(wrapperClass+'_noempty')) $(wrapperSelector).removeClass(wrapperClass+'_noempty');
				}
			}
		});	
	}
	
	
	// optionsData - один или массив option {value = null, title = null, defaultSelected = false, selected = false, disabled = false}
	// insertElement - option до после или сместо которой вставить новую или новые option Если не указать - то убираетсяя все и заменятеся
	// insType – before after replace,
	setOptions(optionsData = null, insertElement = null, insType = 'after') {
		if (!this.inputs || optionsData === null) return false;
		
		if (Array.isArray(optionsData)) {
			
			let options = [];
			$.each(optionsData, function(key, {value = '', title = null, defaultSelected = false, selected = false, disabled = false}) {
				if (!value && !title) return false;
				if (value && !title) title = value;
				let opt = new Option(title, value, defaultSelected, selected);
				if (disabled) opt.disabled = true;
				options.push(opt);
			});
			
			
			this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
				if (wrapperSelector && ['select'].indexOf(type) !== -1) {
					
					if (!insertElement) {
						$(item).children('option').remove();
						$(item).html(options);
					} else if (insertElement == 'before') {
						$(item).prepend(options);
					} else if (insertElement == 'after') {
						$(item).children().append(options);
					} else if (insType == 'before') {
						$(item).children(insertElement).before(options);
					} else if (insType == 'after') {
						$(item).children(insertElement).after(options);
					} else if (insType == 'replace') {
						$(item).children(insertElement).replaceWith(options);
					}
					
					if (selected) {
						if ($(wrapperSelector).hasClass(wrapperClass+'_changed') === false) $(wrapperSelector).addClass(wrapperClass+'_changed');
					}
					
					if (selected && !value) {
						if ($(wrapperSelector).hasClass(wrapperClass+'_noempty')) $(wrapperSelector).removeClass(wrapperClass+'_noempty');
					} else if (selected && value) {
						if ($(wrapperSelector).hasClass(wrapperClass+'_noempty') === false) $(wrapperSelector).addClass(wrapperClass+'_noempty');
					}
				}
			});	
			
		} else {
			
			let {value = '', title = null, defaultSelected = false, selected = false, disabled = false} = optionsData;
			
			if (!value && !title) return false;
			if (value && !title) title = value;
			let option = new Option(title, value, defaultSelected, selected);
			if (disabled) option.disabled = true;
			
			this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
				if (wrapperSelector && ['select'].indexOf(type) !== -1) {
					if (!insertElement) {
						$(item).children('option').remove();
						$(item).html(option);
					} else if (insertElement == 'before') {
						$(item).prepend(option);
					} else if (insertElement == 'after') {
						$(item).children().append(option);
					} else if (insType == 'before') {
						$(item).children(insertElement).before(option);
					} else if (insType == 'after') {
						$(item).children(insertElement).after(option);
					} else if (insType == 'replace') {
						$(item).children(insertElement).replaceWith(option);
					}
					
					if (selected) {
						if ($(wrapperSelector).hasClass(wrapperClass+'_changed') === false) $(wrapperSelector).addClass(wrapperClass+'_changed');
					}
					
					if (selected && !value) {
						if ($(wrapperSelector).hasClass(wrapperClass+'_noempty')) $(wrapperSelector).removeClass(wrapperClass+'_noempty');
					} else if (selected && value) {
						if ($(wrapperSelector).hasClass(wrapperClass+'_noempty') === false) $(wrapperSelector).addClass(wrapperClass+'_noempty');
					}
				}
			});	
		}
	}
	
		
	
	
	
	
	
	
	
	
	error(content = null) {
		if (!this.inputs) return false;
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if (/* ['checkbox', 'radio'].indexOf(type) === -1 &&  */wrapperSelector) {
				if ($(wrapperSelector).hasClass(wrapperClass+'_error') === false) $(wrapperSelector).addClass(wrapperClass+'_error');
				//if (['checkbox', 'radio'].indexOf(type) !== -1) return false;
				if (content) {
					if (['checkbox', 'radio'].indexOf(type) === -1) {
						if ($(wrapperSelector).find('[errorlabel]').length == 0) $(wrapperSelector).append('<div errorlabel></div>');
						$(wrapperSelector).find('[errorlabel]').html('<div>'+content+'</div>');
					} else {
						if ($(wrapperSelector).find('[errorlabel]').length == 0) $(wrapperSelector).append('<div errorlabel></div>');
						$(wrapperSelector).find('[errorlabel]').html('<div>'+content+'</div>');
					}
				}	
			}
		});	
	}
	
	
	
	clear(callback = null) {
		if (!this.inputs) return false;
		
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if ($(wrapperSelector).hasClass(wrapperClass+'_error')) $(wrapperSelector).removeClass(wrapperClass+'_error');
		
			$(wrapperSelector).find('.'+wrapperClass+'__errorlabel').empty();
			
			$(wrapperSelector).addClass(wrapperClass+'_changed');
			$(wrapperSelector).removeClass(wrapperClass+'_noempty');
			
			
			if ($(item).hasAttr('date')) {
				$(item).setAttrib('date');
				$(wrapperSelector).find('[datepicker]').val('');
				$(wrapperSelector).find('.icon').html('<i class="fa-solid fa-fw fa-calendar-days"></i>'); 
			}
			
			
			if (['checkbox', 'radio'].indexOf(type) !== -1) {
				$(wrapperSelector).removeClass(wrapperClass+'_checked');
				if ($(item).is(':checked')) $(item).removeAttrib('checked');
			} else if (type === 'contenteditable') {
				$(item).empty();
			} else {
				$(item).val('');
			}
		});
		
		if (callback && typeof callback === 'function') callback(this.inputs);
	}
	
	
	
	
	state(comand = null, callback = false) {
		if (!this.inputs) return false;
		
		if (comand === 'clear') {
			this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
				if ($(wrapperSelector).hasClass(wrapperClass+'_error')) $(wrapperSelector).removeClass(wrapperClass+'_error');
				if ($(wrapperSelector).hasClass(wrapperClass+'_changed')) $(wrapperSelector).removeClass(wrapperClass+'_changed');
			
				$(wrapperSelector).find('.'+wrapperClass+'__errorlabel').empty();
			});
		}
		
		if (callback && typeof callback === 'function') callback(this.inputs);
	}
	
	
	
	
	change(callback = null, tOut = 0) {
		if (!this.inputs) return false;
		
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			let changeTOut;
			if (type === 'contenteditable') {
				let keyDownVal;
				$(item).on('keyup keydown', function(e) {
					let thisItem = this;
					if (e.type == 'keydown') {
						keyDownVal = $(thisItem).html();
					} else if (e.type == 'keyup') {
						let thisKeyUpVal = $(thisItem).html();
						if (keyDownVal !== thisKeyUpVal) {
							keyDownVal = thisKeyUpVal;
							
							if (tOut) {
								clearTimeout(changeTOut);
								changeTOut = setTimeout(() => {
									if (callback && typeof callback === 'function') callback(this);
								}, tOut);
							} else {
								if (callback && typeof callback === 'function') callback(this);
							}
						}
					}
				});
			} else {
				clearTimeout(changeTOut);
				changeTOut = setTimeout(() => {
					$(item).on('input datepicker', function(event) {
						if (tOut) {
							clearTimeout(changeTOut);
							changeTOut = setTimeout(() => {
								if (callback && typeof callback === 'function') callback(this);
							}, tOut);
						} else {
							if (callback && typeof callback === 'function') callback(this);
						}
					});
				}, tOut);
			}	
		});	
	}
	
	
	
	
	addClass(cls = null) {
		if (!this.inputs) return false;
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if (!$(wrapperSelector).hasClass(cls)) $(wrapperSelector).addClass(cls);
		});
	}
	
	
	
	
	removeClass(cls = null) {
		if (!this.inputs) return false;
		this.inputs.forEach(({item, tag, type, group, wrapperClass, wrapperSelector}) => {
			if ($(wrapperSelector).hasClass(cls)) $(wrapperSelector).removeClass(cls);
		});
	}
	
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------------------------------------------------------------
	
	
	async #compress(file, {compress, watermark, background}) {
		const {default: Compressor} = await import('compressorjs');
		
		return new Promise((resolve, reject) => {
			new Compressor(file, Object.assign(compress, {
				beforeDraw(context, canvas) {
					if (background) {
						context.fillStyle = background;
						context.fillRect(0, 0, canvas.width, canvas.height);
					}
					//context.filter = 'grayscale(100%)';
				},
				/*drew(context, canvas) {
					if (watermark) {
						context.fillStyle = watermark.color;
						context.font = watermark.font || '2rem serif';
						context.fillText(watermark.text, 20, canvas.height - 20);
					}	
				},*/
				success(file) {
					resolve(file);
				},
				error(err) {
					reject(err);
				},
			}));
		});	
	}
	
	
	
	
	
}