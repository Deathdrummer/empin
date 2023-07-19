$.ddrCRUD = function(settings = false) {
	// viewsPath, list, changeInputs, create, store, storeWithShow, edit, update, destroy, query, getParams, abort, remove
	let {
			container, //
			itemToIndex, //
			route, //
			params,
			globalParams,
			viewsPath, //
			saveCursor, // 
			updateCursor, //
			newItemIndexCursorAttr, //
			sortField
		} = _.assign({
			container: null,
			itemToIndex: null,
			route: null,
			params: null,
			globalParams: {},
			viewsPath: null,
			saveCursor: '[save]',
			updateCursor: '[update]',
			newItemIndexCursorAttr: 'index',
			sortField: '_sort'
		}, settings),
		methods = {},
		lastSortIndex = 0,
		newItemIndex = 1,
		abortCtrl;
	
	
	if (!container || !itemToIndex || !route || !viewsPath) {
		throw new Error('ddrCRUD ошибка! Не переданы все необходимые параметры!');
	}
	
	// Дополнительные параметры для всех действий 
	let {list: listParams, create: createParams, store: storeParams, edit: editParams, update: updateParams, destroy: destroyParams} = _.assign({
		list: null,
		create: null,
		store: null,
		edit: null,
		update: null,
		destroy: null
	}, params);
	
	
	
	/*let observer = new MutationObserver(() => {
		if ($(container).find($(container)).length == 0) $(container).empty();
		console.log('empty');
	});
	observer.observe($(container)[0], {
		childList: true,
		subtree: true,
		attributes: true,
		//attributeFilter: ['class']
	});*/
	
	
	
	
	
	
	
	methods = {
		viewsPath,
		abort() {
			abortCtrl.abort();
		},
		
		// можно указать непосредственно строку, 
		// также, можно указать любой селектор в рамках удаляемой строки, например btn (селектор кнопки)
		remove(selector = null) {
			if (!selector) return false;
			
			let rowSelector = _getRowSelector(selector);
			
			if (!rowSelector) throw new Error('ddrCRUD -> remove! Ошибка! Что-то не так с переданным селектором!');
			
			if ($(rowSelector).siblings().length > 0) {
				$(rowSelector).remove();
			} else {
				$(container).empty();
			}
		},
		
		
		// Педедается обект {селекторы: [функция или метод из ddrInputs]}
		// прослушивается весь список, но изменения применяются в рамках одной записи
		changeInputs(rules = {}) {
			if (!rules || !_.isObject(rules)) throw new Error('ddrCRUD -> changeInputs ошибка: не переданы или переданые неверно условия rules!');
			
			$(container).on('input datepicker', 'input, textarea, select', function() {
				let thisItem = this,
					row = $(thisItem).closest(itemToIndex);
				
				$.each(rules, function(selector, comand) {
					if (typeof comand == 'function') {
						comand($(row).find(selector), thisItem);
					} else {
						$(row).find(selector).ddrInputs(comand);
					}
				});
			});
			
			let keyDownVal;
			$(container).on('keyup keydown', '[contenteditable]', function(e) {
				let thisItem = this,
					row = $(thisItem).closest(itemToIndex);
				
				if (e.type == 'keydown') {
					keyDownVal = $(thisItem).html();
				} else if (e.type == 'keyup') {
					let thisKeyUpVal = $(thisItem).html();
					if (keyDownVal !== thisKeyUpVal) {
						keyDownVal = thisKeyUpVal;
						
						$.each(rules, function(selector, comand) {
							if (typeof comand == 'function') {
								comand($(row).find(selector));
							} else {
								$(row).find(selector).ddrInputs(comand);
							}
						});;
					}
				}
			});
		},
		
		
		list(data, cb = false) { // Вывод всех записей
			_getList(cb, true, data);
		},
		create(cb = false) { // Показ формы создания
			let params = _.assign(globalParams, {views: viewsPath, newItemIndex}, createParams);
			
			abortCtrl = new AbortController();
			axiosQuery('get', _route('create'), params, 'text', abortCtrl).then(({data, error, status, headers}) => {
				if (!error) newItemIndex++;
				if (cb && typeof cb == 'function') cb(data, container, {error, status, headers});
			}).catch((e) => {
				if (cb && typeof cb == 'function') cb(false, e);
			});
		},
		
		store(formSelector = false, cb = false, withShow = false) { // Сохранение ресурса
			if (!formSelector) {
				if (cb && typeof cb == 'function') cb(false);
				throw new Error('ddrCRUD -> store ошибка: не передан formSelector!');
			}
			
			let form = _addFieldsToFormData(formSelector, globalParams, storeParams);
			
			let route;
			if (withShow) route = _route('store_show');
			else route = _route();
			
			if (withShow) form = _addFieldsToFormData(form, 'views', viewsPath);
			
			let index = _getIndex(formSelector);
			
			if (index) form = _addFieldsToFormData(form, sortField, (index + lastSortIndex));
			
			abortCtrl = new AbortController();
			axiosQuery('post', route, form, 'text', abortCtrl).then(({data, error, status, headers}) => {
				if (cb && typeof cb == 'function') cb(data, container, {error, status, headers});
			}).catch((e) => {
				if (cb && typeof cb == 'function') cb(false, e);
			});
		},
		
		
		storeWithShow(formSelector = false, cb = false) {
			if (!formSelector) throw new Error('ddrCRUD -> storeWithShow ошибка: не передан formSelector!');
			methods.store(formSelector, cb, true);
		},
		
		show(id = false, cb = false) { // Показ определенной записи
			if (!id) throw new Error('ddrCRUD -> show ошибка: не передан ID!');
			let params = _.assign(globalParams, {views: viewsPath});
			
			abortCtrl = new AbortController();
			axiosQuery('get', _route(id), params, 'text', abortCtrl).then(({data, error, status, headers}) => {
				if (cb && typeof cb == 'function') cb(data, container, {error, status, headers});
			}).catch((e) => {
				if (cb && typeof cb == 'function') cb(false, e);
			});
		}, 
		
		edit(id = false, cb = false) { // Показ формы редактирования
			if (!id) throw new Error('ddrCRUD -> edit ошибка: не передан ID!');
			let params = _.assign(globalParams, editParams, {views: viewsPath});
			
			abortCtrl = new AbortController();
			axiosQuery('get', _route(id, 'edit'), params, 'text', abortCtrl).then(({data, error, status, headers}) => {
				if (cb && typeof cb == 'function') cb(data, container, {error, status, headers});
			}).catch((e) => {
				if (cb && typeof cb == 'function') cb(false, e);
			});
		},
		
		update(id = false, formSelector = false, cb = false) { // Обновление ресурса
			if (!id) throw new Error('ddrCRUD -> update ошибка: не передан ID!');
			if (!formSelector) {
				if (cb && typeof cb == 'function') cb(false);
				throw new Error('ddrCRUD -> update ошибка: не передан ID!');
			}
			
			let form = _addFieldsToFormData(formSelector, globalParams, updateParams);
			
			abortCtrl = new AbortController();
			axiosQuery('put', _route(id), form, 'text', abortCtrl).then(({data, error, status, headers}) => {
				if (cb && typeof cb == 'function') cb(data, container, {error, status, headers});
			}).catch((e) => {
				if (cb && typeof cb == 'function') cb(false, e);
			});
		},
		
		destroy(id = false, cb = false) { // Удаление записи
			if (!id) throw new Error('ddrCRUD -> destroy ошибка: не передан ID!');
			let params = destroyParams || {};
			
			abortCtrl = new AbortController();
			axiosQuery('delete', _route(id), params, 'text', abortCtrl).then(({data, error, status, headers}) => {
				if (cb && typeof cb == 'function') cb(data, container, {error, status, headers});
				if ($(container).find(itemToIndex).length == 0) $(container).empty();
			}).catch((e) => {
				console.log('ddrCRUD -> destroy', e);
				if (cb && typeof cb == 'function') cb(false, e);
			});
		},
		
		// params - это если нужно к данным добавить те данне, что указаны в параметрах при инициализации, к примеру views
		query({method = false, route = null, data = false, responseType = 'json', params = false}, cb = false) {
			if (!method) throw new Error('ddrCRUD -> query: не указан метод или URL!');
			
			if (params && globalParams) {
				let pData = pregSplit(params);
				if (pData) {
					$.each(pData, function(k, param) {
						if (globalParams[param]) data[param] = globalParams[param];
					});
				}
			}
			
			abortCtrl = new AbortController();
			axiosQuery(method, _route(route), data, responseType, abortCtrl).then(({data, error, status, headers}) => {
				if (cb && typeof cb == 'function') cb(data, container, {error, status, headers});
			}).catch((e) => {
				if (cb && typeof cb == 'function') cb(false, e);
			});
		},
		
		getParams(params = false) {
			if (!params || !globalParams) return false;
			
			let pData = pregSplit(params);
			if (!pData) return false;
			let data = {};
			$.each(pData, function(k, param) {
				if (globalParams[param]) data[param] = globalParams[param];
			});
			return data || null;
		}
	};
	
	
	
	
	
	
	return new Promise(function(resolve, reject) {
		try {
			_getList(function(stat, error) {
				if (stat) resolve(methods);
				else resolve(error);
			}, true);
		} catch(e) {
			reject(e);
			throw new Error('ddrCRUD ошибка!');
		}
	});
	
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------
	
	
	
	
	function _getIndex(selector = false) {
		if (selector instanceof FormData) return 1;
		let idx = $(selector).attr(newItemIndexCursorAttr);
		if (!idx) return 1;
		return parseInt(idx);
	}
	
	
	
	function _getRowSelector(selector = null) {
		if (!selector) return false;
		let itemTag = $(container).find(itemToIndex).first()[0]?.tagName?.toLowerCase(),
			selectorTag = $(selector)[0]?.tagName?.toLowerCase();
		return itemTag == selectorTag ? selector : $(selector).closest(itemToIndex);
	}
	
	
	
	
	function _getList(cb = false, init = false, data = {}) {
		let params = _.assign(globalParams, {views: viewsPath}, listParams, data);
		
		abortCtrl = new AbortController();
		axiosQuery('get', _route(), params, 'text', abortCtrl).then(({data, error, status, headers}) => {
			if (error) {
				if (cb && typeof cb == 'function') cb(false, {error});
				console.log(error);
			} else {
				lastSortIndex = parseInt(headers['x-last-sort-index']);
				if (init) {
					if (data) $(container).html(data);
					else $(container).empty();
					
					if (cb && typeof cb == 'function') cb(!error);
				} else {
					if (cb && typeof cb == 'function') cb(data, {error, status, headers});
				}
			}
		});
	}
	
	
	
	
	
	function _route(...params) {
		let clearRoute = route.substr(0,1) == '/' ? route : '/'+route;
		if (params.length == 0) return clearRoute;
		
		let clearParams = [];
		params.forEach((param) => {
			clearParams.push(_.trim(param, '/'));
		});
		
		clearParams = clearParams.join('/');
		
		return clearRoute+'/'+clearParams;
	}
	
	
	
		
	
	/*
		Добавить поля к данным формы
	*/
	function _addFieldsToFormData(form = null, ...params) {
		if (!form || params.length == 0) return false;
		
		if (form instanceof FormData) {
			if (_.isString(params[0]) && params[1] !== undefined) {
				form.append(params[0], params[1]);
			} else {
				params.forEach((p) => {
					if (_.isObject(p)) {
						$.each(p, function(f, v) {
							form.append(f, v);
						});
					}
				});
			}
			
		} else {
			if (_.isString(form) || form instanceof jQuery) form = $(form).ddrForm();
			if (_.isString(params[0]) && params[1] !== undefined) {
				form[params[0]] = params[1];
			} else {
				params.forEach((p) => {
					if (_.isObject(p)) {
						$.each(p, function(f, v) {
							form[f] = v;
						});
					}
				});
			}
		}
		
		return form;
	}
	
	
	
	
	
}