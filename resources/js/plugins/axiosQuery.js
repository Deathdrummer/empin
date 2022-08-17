export default function axiosQuery(method = null, url = false, data = {}, responseType = 'text', abortContr = null) {
	if (!method || !url) throw new Error('axiosQuery: не указан метод или URL!');
	if (typeof data != 'object' || isNull(data)) data = {};
	
	let hasData = !!data;
	
	if (['put', 'patch', 'delete', 'options'].indexOf(method) !== -1) {
		if (data instanceof FormData) data.append('_method', method);
		else data['_method'] = method;
		method = 'post';
	}
	
	if (url.substr(0, 1) != '/') url = '/'+url;
	
	let params = {
		method,
		url,
		responseType
	};
	
	if (hasData) {
		if (method == 'post') params['data'] = data;
		if (method == 'get') params['params'] = data;
	}
	
	if (abortContr) params['signal'] = abortContr.signal;
	
	return new Promise(function(resolve, reject) {
		try {
			axios(params).then(function ({data, status, headers}) {
				let stat = data?.status || status,
					response = {};
				
				if (stat >= 200 && stat < 300) { // Успешный ответ
					response = {
						data,
						error: false,
						status: stat,
						headers
					};
					
				} else if (stat >= 400 && stat < 500) { // Ошибка клиента
					response = {
						data: false,
						error: data,
						status: stat,
						headers
					};
					
				} else { // Другие ошибки
					response = {
						data: false,
						error: data,
						status: stat,
						headers
					};
				}
				
				resolve(response);
				
			}).catch(err => {
				if (axios.isCancel(err)) {
					console.log('axiosQuery: запрос отмменен!');
				} else {
					console.log('axiosQuery: reject!');
					reject(err);
				}
			});
		} catch(e) {
			$.notify('axiosQuery try catch: ошибка загрузки', 'error');
			reject(e);
		}
	});		
}