class AutoCADConverter {
	constructor() {
		this.dropzone = null;
		this.fileInput = null;
		this.filesContainer = null;
		this.statusElement = null;
		this.uploadedFiles = [];
		this.initialized = false;
	}

	init() {
		if (this.initialized) {
			console.warn('AutoCAD converter already initialized');
			return;
		}

		this.dropzone = document.getElementById('autocadDropzone');
		this.fileInput = document.getElementById('autocadFileInput');
		this.filesContainer = document.getElementById('autocadFilesContainer');
		this.statusElement = document.getElementById('autocadStatus');

		if (!this.dropzone || !this.fileInput) {
			console.warn('AutoCAD converter elements not found');
			return;
		}

		this.bindEvents();
		this.initialized = true;
	}

	bindEvents() {
		console.log('Binding AutoCAD events...');

		// Кнопка выбора файлов
		const chooseButton = document.getElementById('autocadChooseFiles');
		if (chooseButton) {
			console.log('Choose button found, binding click event');
			chooseButton.addEventListener('click', () => {
				console.log('Choose button clicked');
				this.fileInput.click();
			});
		} else {
			console.warn('Choose button not found');
		}

		// Выбор файлов через input
		this.fileInput.addEventListener('change', (e) => {
			console.log('File input changed', e.target.files);
			this.handleFiles(e.target.files);
		});

		// Drag & Drop события
		console.log('Binding drag&drop events to dropzone');
		this.dropzone.addEventListener('dragenter', this.handleDragEnter.bind(this));
		this.dropzone.addEventListener('dragover', this.handleDragOver.bind(this));
		this.dropzone.addEventListener('dragleave', this.handleDragLeave.bind(this));
		this.dropzone.addEventListener('drop', this.handleDrop.bind(this));

		// Тестовый клик на зону
		this.dropzone.addEventListener('click', () => {
			console.log('Dropzone clicked - triggering file input');
			this.fileInput.click();
		});

		console.log('AutoCAD events bound successfully');
	}

	handleDragEnter(e) {
		console.log('Drag enter event');
		e.preventDefault();
		e.stopPropagation();
		this.dropzone.classList.add('dragover');
	}

	handleDragOver(e) {
		console.log('Drag over event');
		e.preventDefault();
		e.stopPropagation();
	}

	handleDragLeave(e) {
		console.log('Drag leave event');
		e.preventDefault();
		e.stopPropagation();
		if (!this.dropzone.contains(e.relatedTarget)) {
			this.dropzone.classList.remove('dragover');
		}
	}

	handleDrop(e) {
		console.log('Drop event', e.dataTransfer.files);
		e.preventDefault();
		e.stopPropagation();
		this.dropzone.classList.remove('dragover');

		const files = e.dataTransfer.files;
		this.handleFiles(files);
	}

	handleFiles(files) {
		if (!files || files.length === 0) return;

		// Фильтруем только DWG файлы
		const dwgFiles = Array.from(files).filter(file => {
			return file.name.toLowerCase().endsWith('.dwg');
		});

		if (dwgFiles.length === 0) {
			this.showStatus('Выберите файлы с расширением .dwg', 'error');
			return;
		}

		if (dwgFiles.length !== files.length) {
			this.showStatus('Некоторые файлы были проигнорированы. Поддерживаются только .dwg файлы', 'warning');
		}

		// Добавляем файлы в очередь
		dwgFiles.forEach(file => {
			this.addFileToList(file);
		});

		this.showFilesList();
		this.processFiles();
	}

	addFileToList(file) {
		const fileId = 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

		this.uploadedFiles.push({
			id: fileId,
			file: file,
			status: 'pending',
			name: file.name,
			size: file.size
		});

		const fileElement = this.createFileElement(fileId, file);
		this.filesContainer.appendChild(fileElement);
	}

	createFileElement(fileId, file) {
		const fileDiv = document.createElement('div');
		fileDiv.className = 'autocad-files-list__item';
		fileDiv.dataset.fileId = fileId;

		const sizeFormatted = this.formatFileSize(file.size);

		fileDiv.innerHTML = `
			<div class="autocad-files-list__item-info">
				<i class="fa-solid fa-file file-icon"></i>
				<span class="file-name">${file.name}</span>
				<span class="file-size">(${sizeFormatted})</span>
			</div>
			<div class="autocad-files-list__item-status processing">
				<i class="fa-solid fa-clock status-icon"></i>
				<span>Ожидание</span>
			</div>
		`;

		return fileDiv;
	}

	showFilesList() {
		const filesList = document.getElementById('autocadFilesList');
		if (filesList) {
			filesList.style.display = 'block';
		}
	}

	async processFiles() {
		this.showStatus('Начинаем обработку файлов...', 'info');

		for (const fileData of this.uploadedFiles) {
			if (fileData.status !== 'pending') continue;

			try {
				await this.processFile(fileData);
			} catch (error) {
				console.error('Ошибка обработки файла:', error);
				this.updateFileStatus(fileData.id, 'error', 'Ошибка обработки');
			}
		}

		const processedCount = this.uploadedFiles.filter(f => f.status === 'success').length;
		const totalCount = this.uploadedFiles.length;

		if (processedCount === totalCount) {
			this.showStatus(`Все файлы успешно обработаны (${processedCount}/${totalCount})`, 'success');
		} else {
			this.showStatus(`Обработано ${processedCount} из ${totalCount} файлов`, 'warning');
		}
	}

	async processFile(fileData) {
		this.updateFileStatus(fileData.id, 'processing', 'Обработка...');

		const formData = new FormData();
		formData.append('file', fileData.file);
		formData.append('filename', fileData.name);

		try {
			console.log('Sending request to /ajax/autocad/convert');
			const response = await fetch('/ajax/autocad/convert', {
				method: 'POST',
				body: formData,
				headers: {
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
					'Accept': 'application/json'
				}
			});

			console.log('Response status:', response.status);
			console.log('Response headers:', response.headers);

			// Проверяем, что ответ действительно JSON
			const contentType = response.headers.get('content-type');
			if (!contentType || !contentType.includes('application/json')) {
				const textResponse = await response.text();
				console.error('Server returned non-JSON response:', textResponse.substring(0, 500));
				throw new Error(`Сервер вернул неожиданный ответ (${response.status}). Проверьте маршруты.`);
			}

			const result = await response.json();
			console.log('Response result:', result);

			if (response.ok && result.success) {
				this.updateFileStatus(fileData.id, 'success', 'Готово');
				fileData.status = 'success';
				fileData.result = result.data;
			} else {
				throw new Error(result.message || 'Ошибка сервера');
			}
		} catch (error) {
			console.error('Полная ошибка обработки файла:', error);
			this.updateFileStatus(fileData.id, 'error', error.message || 'Ошибка сети');
			fileData.status = 'error';
			throw error;
		}
	}

	updateFileStatus(fileId, status, message) {
		const fileElement = this.filesContainer.querySelector(`[data-file-id="${fileId}"]`);
		if (!fileElement) return;

		const statusElement = fileElement.querySelector('.autocad-files-list__item-status');
		if (!statusElement) return;

		// Убираем все классы статуса
		statusElement.classList.remove('processing', 'success', 'error');
		statusElement.classList.add(status);

		let iconClass = 'fa-clock';
		switch (status) {
			case 'processing':
				iconClass = 'fa-spinner fa-spin';
				break;
			case 'success':
				iconClass = 'fa-check';
				break;
			case 'error':
				iconClass = 'fa-times';
				break;
		}

		statusElement.innerHTML = `
			<i class="fa-solid ${iconClass} status-icon"></i>
			<span>${message}</span>
		`;
	}

	showStatus(message, type = 'info') {
		if (!this.statusElement) return;

		const statusMessage = this.statusElement.querySelector('#autocadStatusMessage');
		if (!statusMessage) return;

		// Убираем все классы типов
		statusMessage.classList.remove('alert-info', 'alert-success', 'alert-danger', 'alert-warning');
		statusMessage.classList.add(`alert-${type}`);

		let iconClass = 'fa-info-circle';
		switch (type) {
			case 'success':
				iconClass = 'fa-check-circle';
				break;
			case 'error':
			case 'danger':
				iconClass = 'fa-exclamation-circle';
				break;
			case 'warning':
				iconClass = 'fa-exclamation-triangle';
				break;
			case 'info':
			default:
				iconClass = 'fa-spinner fa-spin';
				break;
		}

		statusMessage.innerHTML = `
			<i class="fa-solid ${iconClass}"></i>
			<span>${message}</span>
		`;

		this.statusElement.style.display = 'block';

		// Автоматически скрываем сообщения об успехе через 5 секунд
		if (type === 'success') {
			setTimeout(() => {
				if (this.statusElement) {
					this.statusElement.style.display = 'none';
				}
			}, 5000);
		}
	}

	formatFileSize(bytes) {
		if (bytes === 0) return '0 Bytes';
		const k = 1024;
		const sizes = ['Bytes', 'KB', 'MB', 'GB'];
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
	}
}

// Фабричная функция для создания экземпляра
const ddrAutoCAD = () => {
	const instance = new AutoCADConverter();
	return {
		init: () => instance.init(),
		handleFiles: (files) => instance.handleFiles(files),
		showStatus: (message, type) => instance.showStatus(message, type),
		processFiles: () => instance.processFiles(),
		getUploadedFiles: () => instance.uploadedFiles,
		destroy: () => {
			instance.dropzone = null;
			instance.fileInput = null;
			instance.filesContainer = null;
			instance.statusElement = null;
			instance.uploadedFiles = [];
			instance.initialized = false;
		}
	};
};

// Глобальный доступ для обратной совместимости
if (typeof window !== 'undefined') {
	window.ddrAutoCAD = ddrAutoCAD;
	window.AutoCADConverter = AutoCADConverter; // Прямой доступ к классу
}

export { AutoCADConverter, ddrAutoCAD };
export default ddrAutoCAD;