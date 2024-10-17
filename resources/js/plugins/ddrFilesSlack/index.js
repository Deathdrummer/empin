import DdrFiles from './ddrFiles';



$.fn.ddrFiles = function(method = null, ...params) {
	if (!method) {
		if (isDev) console.error('ddrFiles -> не указан метод!');
		return false;
	}
	
	const hasMethod = [ 'choose', 'drop'].includes(method);
	
	if (!hasMethod) throw new Error(`$.fn.ddrFiles -> такого метода «${method}» нет!`);
	
	const files = ref({});
	
	const methods = new DdrFiles(this, files);
	if (method.includes(':')) method = method.replace(':', '_');
		
	methods[method](...params);
	
	return methodsObj(files);
}






/*	Вызов методов, не привязанных к селектору
		- method - метод 
		- params
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
$.ddrFiles = function(method = null, params = {}) {
	if (!method) {
		if (isDev) console.error('$.ddrFiles -> не указан method!');
		return false;
	}
	if (!params) {
		if (isDev) console.error('$.ddrFiles -> не переданы параметры!');
		return false;
	}
	
	const hasMethod = ['upload', 'export'].includes(method);
	
	if (!hasMethod) throw new Error(`$.ddrFiles -> такого метода «${method}» нет!`);
	
	
	const files = ref({});
	
	const methods = new DdrFiles(false, files);
	
	if (method.includes(':')) method = method.replace(':', '_');
	
	methods[method](params);
	
	return methodsObj(files);
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