export default class DdrFiles { // 29.07.23
	
	selector;
	files;
	
	constructor(selector = null, files = null) {
		if (_.isNull(selector)) throw new Error('DdrFiles -> Не передан селектор');
		this.selector = selector || null;
		this.files = files;
	}
	
	
	
	
	
	
	/*	Открытие диалога загрузки файлов (навесить на любой селектор)
			- multiple: множественный выбор
			- init: перед инициализацией загрузки файлов
			- preload: маркировка ключем key блоков под миниатюры картинок или иконки файлов
			- callback: файл загружен 
	*/
	choose(params = {}, forcedSelector = null) {
		const getAddedFiles = this.getAddedFiles;
		const loadFiles = this.loadFiles.bind(this); // bind потому что в функции loadFiles идет обращение к контексту, но он там потерян, так как вызов идет отсюда, то есть уже 3 вложенности функций
		const selector = this.selector || forcedSelector;
		
		const loadFilesParams = _.omit(params, ['multiple']);
		
		$(selector).on(tapEvent, () => {
			const input = document.createElement('input');
			input.type = 'file';
			input.multiple = params?.multiple || false;
			
			input.oninput = e => {
				const files = getAddedFiles(e);
				loadFiles(files, loadFilesParams);
			}
			
			input.click();
		});
	}
	
	
	
	
	
	
	
	/*	Cобытие бросания файлов в область drop (навесить на любой селектор)
			- dragover: событие при наведении на область drop
			- dragleave: событие при уходе из области drop
			- drop: событие бросания файлов в область drop
			- init: перед инициализацией загрузки файлов
			- preload: маркировка ключем key блоков под миниатюры картинок или иконки файлов
			- callback: файл загружен 
	*/
	drop(params = {}, forcedSelector = null) {
		const selector = this.selector || forcedSelector;
		const getAddedFiles = this.getAddedFiles;
		const loadFiles = this.loadFiles.bind(this);  // bind потому что в функции loadFiles идет обращение к контексту, но он там потерян, так как вызов идет отсюда, то есть уже 3 вложенности функций
		
		const dragFuncsArr = ['dragover', 'dragleave', 'drop'];
		const dragFuncs = _.pick(params, dragFuncsArr);
		const loadFilesFuncs = _.omit(params, dragFuncsArr);
		
		const {dragover, dragleave, drop} = _.assign({
			dragover: null, // 
			dragleave: null, // 
			drop: null, //
		}, dragFuncs);
		
		$(selector).on('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			callFunc((drop || dragleave), this);
			
			const files = getAddedFiles(e);
			loadFiles(files, loadFilesFuncs);
			
			return false;
		});
		
		
		
		let dragstat = false;
		
		// при наведении
		$(selector).on('dragover', function(e) {
			event.preventDefault();
			event.stopPropagation();
			
			if (!dragstat) {
				callFunc(dragover, this);
				dragstat = true;
			}
			
			return false;
		});
		
		// при уходе
		$(selector).on('dragleave', function(e) {
			event.preventDefault();
			event.stopPropagation();
			
			if (dragstat) {
				callFunc(dragleave, this);
				dragstat = false;
			}
			
			return false;
		});
	}
	
	
	
	
	
	
	
	
	
	
	upload(params = {}) {
		if (!Object.values(params).length) {
			if (isDev) console.error('class DdrFiles -> не переданы параметры!');
			return false;
		}
		const {chooseSelector, dropSelector} = _.pick(params, ['chooseSelector', 'dropSelector']);
		const chooseParams = _.pick(params, ['multiple', 'init', 'preload', 'callback', 'done', 'fail']);
		const dropParams = _.pick(params, ['dragover', 'dragleave', 'drop', 'init', 'preload', 'callback', 'done', 'fail']);
		
		
		this.choose(chooseParams, chooseSelector)
		this.drop(dropParams, dropSelector);
	}
	
	
	
	
	
	
	
	
	
	/*	Экспорт файлов полученных через AJAX (доработать)
			- опции
				- data: приходящие данные
				- headers: заголовки
				- filename: имя файле
			- коллбэк
	*/
	async export(params = {}) {
		let {query, data, headers, filename, done} = _.assign({
			query: null, // url, params
			data: null, // 
			headers: null, // 
			filename: null, //
			done: null, //
		}, params);
		
		if (query) {
			const {url, params} = _.assign({
				url: null,
				params: {}, 
			}, query);
			
			const {data: qData, error, status, headers: qHeaders} = await ddrQuery.get(url, params, {responseType: 'blob'});
			
			if (error) {
				$.notify('export -> ddrQuery ошибка экспорта!', 'error');
				callFunc(done, false);
				return false;
			}
			
			data = qData;
			headers = qHeaders;
		}
		

		if (typeof window.navigator.msSaveBlob !== 'undefined') {
			const blob = new Blob([data], {
				type: 'application/octet-stream',
			});
			window.navigator.msSaveBlob(blob, filename);
		} else {
			const headerContentDisp = headers["content-disposition"] || null;
			
			const fName = headerContentDisp && headerContentDisp.split("filename=")[1].replace(/["']/g, "");
			
			const fExt = getFileName(fName, 2);
			const fExtToName = fExt ? '.'+fExt : '';
			
			const finalFileName = filename ? filename+fExtToName : fName;
			
			const contentType = headers["content-type"];
			const blob = new Blob([data], {contentType});
			const href = window.URL.createObjectURL(blob);
			const el = document.createElement("a");
			el.setAttribute("href", href);
			el.setAttribute("download", finalFileName);
			el.click();
			window.URL.revokeObjectURL(blob);
			
		}
		
		callFunc(done, true);
	}
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------------------------------------------------------------------------
	
	
	/*	Получить список добавленных файлов
			- объект event
			- вернуть как простой массив
	*/
	getAddedFiles(event = null) {
		if (!event) {
			if (isDev) console.error('getAddedFiles -> не передан event!');
			return false;
		}
		
		let files = null;
		
		if (event?.originalEvent?.dataTransfer != undefined) files = event.originalEvent.dataTransfer.files;
		else if (event?.dataTransfer != undefined) files = event.dataTransfer.files;
		else if (event?.currentTarget?.files != undefined) files = event.currentTarget.files;
		
		let filesArr = Object.values(files);
		
		return filesArr.filter(file => isFile(file));
	}
	
	
	
	
	
	
	
	
	
	/* Загрузка файлов и обработка из
			-  массив файлов
			- параметры
				- init: null, // перед инициализацией загрузки файлов
				- preload: null, // маркировка ключем key блоков под миниатюры картинок или иконки файлов
				- callback: null, // файл загружен 
				- done: null, // все файлы загружены
			
			Возвращает данные файла:
				
				- объект файла
					- имя
					- расширение
					- ключ
					- тип
					- размер
					- функция preview - обработка изображения для вывода превью
					- error - если ошибка загрузки файла
				- done: все файлы обработаны
				- index: индекс файла
	*/
	loadFiles(files = null, params = {}) {
		if (!files) {
			if (isDev) console.error('buildFiles -> не переданы файлы!');
			return false;
		}
		
		const compress = this.compressImage;
		const returnedFiles = this.files;
		
		const {extensions, init, preload, error, callback, done} = _.assign({
			extensions: null, // Разешенные расширения файлов
			init: null, // перед инициализацией загрузки файлов
			preload: null, // маркировка ключем key блоков под миниатюры картинок или иконки файлов
			error: null, // вызывается в случае ошибки
			callback: null, // файл загружен 
			done: null, // все файлы загружены
		}, params);
		
		callFunc(init, {count: files.length});
		
		const allFiles = {};
		let reader;
		let cbIters = 0;
		let complete;
		
		$.each(files, function(iter, rawFile) {
			let isImage = isImgFile(rawFile);
			let [name, ext] = getFileName(rawFile);
			
			if (extensions && !extensions.includes(ext)) {
				callFunc(error, {text: 'forbidden_extension', extensions, file: rawFile});
				return true;
			}
			
			rawFile.key = ddrHash(rawFile.name+rawFile.lastModified+rawFile.size, 2);
			
			callFunc(preload, {key: rawFile.key, iter, error: rawFile.error});
			
			reader = new FileReader();
			
			reader.onerror = function() {
				callFunc(error, {text: 'loading_error', file: rawFile});
				return true;
			};
			
			reader.onload = function(e) {
				if (e.target.error) {
					callFunc(error, {text: 'loading_error', file: rawFile});
					return true;
				}
				
				if (rawFile.size == 0 && !ext) {
					callFunc(error, {text: 'not_file', file: rawFile});
					return true;
				}
				
				let fileData = {
					file: rawFile,
					name,
					ext,
					size: rawFile.size,
					type: rawFile.type,
					isImage,
					key: rawFile.key,
					preview: isImage ? (compressParams) => compress(rawFile, compressParams) : null,
				}
				
				complete = (files.length === (++cbIters));
				
				callFunc(callback, fileData, {done: complete, index: cbIters});
				
				allFiles[rawFile.key] = fileData;
				
				if (complete) {
					returnedFiles.value = allFiles;
					callFunc(done, {files: allFiles});
				} 
			};
			
			reader.readAsDataURL(rawFile); // когда мы хотим использовать данные в src для img или другого тега
		});
	}
	
	
	
	
	
	
	
	/*	Уменьшить размер изображения для превью 
			- width: ширина
			- height: высота
			- quality: качество от 0 до 1
	*/
	compressImage(file = null, params = {}) {
		if (!file) return false;
		
		const {width, height, quality} = _.assign({
			width: null,
			height: null,
			quality: 0.9
		}, params);
		
		let image = new Image();
		image.src = URL.createObjectURL(file);
		
		return new Promise(function(resolve, reject) {
			try {
				image.onload = _ => {
	                let imageWidth = image.width;
	                let imageHeight = image.height;
	                let canvas = document.createElement('canvas');

	                // resize the canvas and draw the image data into it
	                if (width && height) {
	                    canvas.width = width;
	                    canvas.height = height;
	                } else if (width) {
	                    canvas.width = width;
	                    canvas.height = Math.floor(imageHeight * width / imageWidth)
	                } else if (height) {
	                    canvas.width = Math.floor(imageWidth * height / imageHeight);
	                    canvas.height = height;
	                } else {
	                    canvas.width = imageWidth;
	                    canvas.height = imageHeight
	                }

	                var ctx = canvas.getContext("2d");
	                ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

	                let dataUrl = canvas.toDataURL(file.type, quality);
					resolve(dataUrl);
	            }
			} catch(e) {
				reject(e);
			}
		});
	}
	
	
	
	
	
	
		
}