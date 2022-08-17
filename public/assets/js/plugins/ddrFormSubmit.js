/*
	отправка формы на сервер
*/

$.ddrFormSubmit = function(params = {}) {
	if (!params) throw new Error('ddrFormSubmit ошибка не переданы данные!');
	if (!params.url) throw new Error('ddrFormSubmit ошибка не указан URL');
	request(params, buildAddictData(params));
};



$.fn.ddrFormSubmit = async function(params = {}) {
	if (!params) throw new Error('ddrFormSubmit ошибка не переданы данные!');
	let formContainer = this,
		formItems = $(formContainer).find('input[name], textarea[name], select[name], [contenteditable][name]').not('input[type="button"], input[type="file"]');
	if (formContainer == undefined || formItems.length == 0) return false;
	
	if (!params.url) throw new Error('ddrFormSubmit ошибка не указан URL');
	
	let formData = _.assign(buildFormData(formItems), buildAddictData(params));
	
	request(params, formData);
};








//-------- Получение данных формы БЕЗ отправки на сервер

// fields
// files
$.fn.ddrForm = function(params = {}) {
	let formContainer = this,
		formItems = $(formContainer).find('input[name], textarea[name], select[name], [contenteditable][name]').not('input[type="button"], input[type="file"]');
	if (formContainer == undefined || formItems.length == 0) return false;
	
	let formData = _.assign(buildFormData(formItems), buildAddictData(params)); // params -> fields files
	return formData;
};


// fields
// files
$.ddrForm = function(params = {}) {
	if (!params) throw new Error('ddrForm ошибка не переданы данные!');
	
	return buildAddictData(params);  // params -> fields files
};










/*
	получить дополнительные поля для формы
*/
function buildAddictData(params = {}) {
	if (!params) throw new Error('ddrFormSubmit -> buildAddictData: не переданы параметры!');
	
	let {fields, files} = _.assign({
		fields: null,
		files: null,
	}, params);
	
	const formData = {};
	
	if (fields) {
		$.each(fields, function(field, value) {
			let v = typeof value == 'object' ? JSON.stringify(value) : value;
			_.set(formData, field, v);
		});
	}
	
	if (files) {
		if (files.name !== undefined && files.items !== undefined) {
			if (files.items.length > 1) {
				$.each(files.items, (k, file) => {
					//console.log(files.name+'['+file.key+']', file);
					//formData.append(files.name+'['+file.key+']', file);
					_.set(formData, files.name+'['+file.key+']', file);
				});
			} else {
				console.log(files);
				//formData.append(files.name, files.items[0]);
				_.set(formData, files.name, files.items[0]);
			}
				
		} else {
			$.each(files, (fieldName, file) => {
				//console.log(fieldName, file);
				//formData.append(fieldName, file);
				_.set(formData, fieldName, file);
			});
		}	
	}
	return formData;
}



/*
	получить данные из формы
*/
function buildFormData(formItems = false) {
	const formData = {};
	
	if (!formItems) return false;
	
	$.each(formItems, function(k, formItem) {
		let f = formItem,
			n = $(f).attr('name') || false,
			t = $(f).attr('type') || ($(f)[0].type !== undefined ? $(f)[0].type.replace('select-one', 'select') : ($(f).hasAttr('contenteditable') ? 'contenteditable' : false)),
			v; // = ['checkbox', 'radio'].indexOf(t) !== -1 ? (($(f).is(':checked') || $(f).is('[checked]')) ? (t == 'radio' ? $(f).val() : 1) : (t == 'radio' ? null : 0)) : (t == 'contenteditable' ? (getContenteditable($(f)) || null) : ((t =='hidden' ? ($(f).prop('value') || null) : $(f).val()) || null));
		
		switch(t) {
			case 'checkbox':
				v = $(f).is(':checked') || $(f).is('[checked]') ? 1 : 0;
				break;
				
			case 'radio':
				v = ($(f).is(':checked') || $(f).is('[checked]')) ? $(f).val() : null;
				break;
				
			case 'contenteditable':
				v = getContenteditable($(f)) || null;
				break;
			
			case 'hidden':
				v = $(f).prop('value') || null;
				break;
				
			default:
				v = $(f).val() || null;
				break;
		}
		
		if (t == 'radio') {
			if (_.has(formData, n) == false || v) {
				_.set(formData, n, v);
			}
		} else if (t == 'checkbox') {
			_.set(formData, n, v);
		} else {
			_.set(formData, n, (v || ''));
		}
	});
	
	return formData;
}



/*
	сформировать данные из формы в объект формы и передать на сервер
*/
function request(params = false, formItems = false) {
	if (!params || !formItems) throw new Error('ddrFormSubmit -> request: не переданы параметры');
	
	let {url, method, responseType, callback, fail, complete} = _.assign({
		url: false,
		method: 'POST',
		responseType: 'json',
		callback: false,
		fail: false,
		complete: false
	}, params),
		formData = new FormData();
	
	
	$.each(formItems, function(field, value) {
		formData.append(field, value);
	});
	
	/* 
	if (Array.from(formData.entries()).length === 0) {
		throw new Error('ddrFormSubmit -> request: ничего не передано!');
		return false;
	} */
	
	if (!formData.get('_token')) {
		if ($('meta[name*="csrf"]').length) {
			formData.append('_token', $('meta[name*="csrf"]').attr('content') || null);
		} else {
			throw new Error('ddrFormSubmit -> request: не найден CSRF токен!');
		}
	}
	
	
	
	try {
		axios({
			method,
			responseType,
			url,
			data: formData,
			headers: {'Accept': 'application/json'}
		}).then(({data, status, headers}) => {
			if (callback && typeof callback === 'function') callback(data, status, headers);
		}).catch((error) => {
			if (error.response) {
				let {data = null, status = null, statusText = null, headers = null} = error?.response;
				if (callback && typeof callback === 'function') callback(data, status, headers);
			} else if (error) {
				console.error('ddrFormSubmit error:', error);
			}
		}).then(() => {
			if (complete && typeof complete === 'function') complete(true);
		});
	} catch(e) {
		if (fail && typeof fail === 'function') fail(e, status);
	} finally {
		if (complete && typeof complete === 'function') complete(false);
	}
}