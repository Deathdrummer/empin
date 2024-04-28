import DdrFiles from './ddrFiles';






$.ddrChooseFiles = function(params = {}) {
	const chooseParams = _.pick(params, ['multiple', 'init', 'preload', 'callback', 'done', 'fail']);
	
	const files = ref({});
	
	new DdrFiles(true, files).choose(chooseParams);
	
	return methodsObj(files);
}





$.fn.ddrFiles = function(method = null, ...params) {
	if (!method) {
		if (isDev) console.error('ddrFiles -> не указан метод!');
		return false;
	}
	
	const hasMethod = ['choose', 'drop', 'export'].includes(method);
	
	if (!hasMethod) throw new Error(`ddrFiles -> такого метода «${method}» нет!`);
	
	const files = ref({});
	
	const methods = new DdrFiles(this, files);
	if (method.includes(':')) method = method.replace(':', '_');
		
	methods[method](...params);
	
	return methodsObj(files);
}






/*	Комбинирование методов choose и drop
		- params
			- method: метод choose или drop (если не указать - будет и то и то)
			- chooseSelector: селектор открытия кна диалога
			- dropSelector: селектор drop - области бросания файлов 
			- multiple: множественный выбор
			- dragover: событие при наведении на область drop
			- dragleave: событие при уходе из области drop
			- drop: событие бросания файлов в область drop
			- init: перед инициализацией загрузки файлов
			- preload: маркировка ключем key блоков под миниатюры картинок или иконки файлов
			- callback: файл загружен 
			- fail: ошибка загрузки
*/
$.ddrFiles = function(params = {}) {
	if (!params) {
		if (isDev) console.error('$.ddrFiles -> не переданы параметры!');
		return false;
	}
	
	const {chooseSelector, dropSelector} = _.pick(params, ['chooseSelector', 'dropSelector']);
	const chooseParams = _.pick(params, ['multiple', 'init', 'preload', 'callback', 'done', 'fail']);
	const dropParams = _.pick(params, ['dragover', 'dragleave', 'drop', 'init', 'preload', 'callback', 'done', 'fail']);
	
	const files = ref({});
	
	if (params?.method) {
		if (params.method == 'choose') new DdrFiles(document.querySelector(chooseSelector), files).choose(chooseParams);
		if (params.method == 'drop') new DdrFiles(document.querySelector(dropSelector), files).drop(dropParams);
	} else {
		new DdrFiles(document.querySelector(chooseSelector), files).choose(chooseParams);
		new DdrFiles(document.querySelector(dropSelector), files).drop(dropParams);
	}
	
	return methodsObj(files);
}





/* Это временное решение */
$.ddrExport = function(ops = {}, cb) {
	const {data, headers, filename} = ops;
	const headerContentDisp = headers["content-disposition"] || null;
	
	let fName, fExt;
	
	if (headerContentDisp) {
		if (headerContentDisp.includes('filename*=utf-8')) {
		  fName = headerContentDisp.split("filename*=utf-8")[1].replace(/["']/g, "");
		} else if (headerContentDisp.includes('filename=')) {
		  fName = headerContentDisp.split("filename=")[1].replace(/["']/g, "");
		}
		fName = decodeURI(fName);
		fExt = getFileName(fName, 2);
	}
	
	const finalFileName = filename ? filename+'.'+fExt : fName;
	
	console.log({finalFileName});
	
	const contentType = headers["content-type"];
	const blob = new Blob([data], {contentType});
	const href = window.URL.createObjectURL(blob);
	const el = document.createElement("a");
	el.setAttribute("href", href);
	el.setAttribute("download", finalFileName);
	el.click();
	window.URL.revokeObjectURL(blob);
	
	callFunc(cb);
}






/* методы
	- getFiles: получить все выбранне файлы
	- removeFile: удалить файл(ы) по ключу
*/
const methodsObj = function(files) {
	return {
		getFiles() {
			return files.value;
		},
		getFile() {
			return Object.values(files.value)[0]['file'];
		},
		removeFile(key = null, count = 1) {
			if (_.isNull(key)) return false;
			if (files.value[key] !== undefined) delete files.value[key];
		},
	};
}