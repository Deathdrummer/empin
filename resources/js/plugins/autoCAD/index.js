// AutoCAD DWG to JSON Converter
class AutoCADConverter {
    constructor() {
        this.dropzone = document.getElementById('autocadDropzone');
        this.fileInput = document.getElementById('autocadFileInput');
        this.statusArea = document.getElementById('autocadStatus');

        this.init();
    }

    init() {
        if (!this.dropzone) return;

        // Drag and drop handlers
        this.dropzone.addEventListener('dragover', this.handleDragOver.bind(this));
        this.dropzone.addEventListener('dragleave', this.handleDragLeave.bind(this));
        this.dropzone.addEventListener('drop', this.handleDrop.bind(this));
        this.dropzone.addEventListener('click', () => this.fileInput?.click());

        // File input handler
        if (this.fileInput) {
            this.fileInput.addEventListener('change', this.handleFileSelect.bind(this));
        }

    }

    handleDragOver(e) {
        e.preventDefault();
        this.dropzone.classList.add('dragover');
    }

    handleDragLeave(e) {
        e.preventDefault();
        this.dropzone.classList.remove('dragover');
    }

    handleDrop(e) {
        e.preventDefault();
        this.dropzone.classList.remove('dragover');

        const files = Array.from(e.dataTransfer.files).filter(file =>
            file.name.toLowerCase().endsWith('.dxf')
        );

        if (files.length === 0) {
            this.showStatus('Выберите только DXF файлы', 'error');
            return;
        }

        this.processFiles(files);
    }

    handleFileSelect(e) {
        const files = Array.from(e.target.files);
        this.processFiles(files);
    }

    async processFiles(files) {
        for (const file of files) {
            await this.convertFile(file);
        }
    }

    async convertFile(file) {
        const fileItem = this.addFileToList(file);

        try {
            this.updateFileStatusWithProgress(fileItem, 'Загрузка файла...', 'processing', 10);

            const formData = new FormData();
            formData.append('file', file);
            formData.append('filename', file.name);

            console.log('🚀 Отправляем файл:', file.name);

            this.updateFileStatusWithProgress(fileItem, 'Парсинг DXF файла...', 'processing', 30);

            const response = await fetch('/ajax/autocad/convert', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            this.updateFileStatusWithProgress(fileItem, 'Обработка ответа...', 'processing', 90);

            const result = await response.json();

            if (result.success) {
                this.updateFileStatusWithProgress(fileItem, 'Парсинг завершен', 'success', 100);
                this.showJsonResult(file.name, result.data);
            } else {
                throw new Error(result.message || 'Ошибка конвертации');
            }

        } catch (error) {
            console.error('Ошибка конвертации:', error);
            this.updateFileStatus(fileItem, `Ошибка: ${error.message}`, 'error');
        }
    }

    addFileToList(file) {
        const fileItem = document.createElement('div');
        fileItem.className = 'autocad-file-item';
        fileItem.innerHTML = `
            <div class="autocad-file-info">
                <span class="autocad-file-name">${file.name}</span>
                <span class="autocad-file-size">(${this.formatFileSize(file.size)})</span>
            </div>
            <div class="autocad-file-status">Ожидание...</div>
        `;

        this.statusArea.appendChild(fileItem);
        return fileItem;
    }

    updateFileStatus(fileItem, status, type = 'info') {
        const statusElement = fileItem.querySelector('.autocad-file-status');
        statusElement.textContent = status;
        statusElement.className = `autocad-file-status status-${type}`;
    }

    updateFileStatusWithProgress(fileItem, status, type = 'info', progress = 0) {
        const statusElement = fileItem.querySelector('.autocad-file-status');
        statusElement.className = `autocad-file-status status-${type}`;

        if (type === 'processing') {
            statusElement.innerHTML = `
                <div class="autocad-progress-container">
                    <div class="autocad-progress-text">${status}</div>
                    <div class="autocad-progress-bar">
                        <div class="autocad-progress-fill" style="width: ${progress}%"></div>
                    </div>
                    <div class="autocad-progress-percent">${progress}%</div>
                </div>
            `;
        } else {
            statusElement.textContent = status;
        }
    }

    showJsonResult(filename, jsonData) {
        const resultArea = document.createElement('div');
        resultArea.className = 'autocad-json-result';
        resultArea.innerHTML = `
            <h4>Результат конвертации: ${filename}</h4>
            <div class="autocad-json-stats">
                <span>Сущностей: ${jsonData.entities?.length || 0}</span>
                <button onclick="this.parentElement.nextElementSibling.style.display = this.parentElement.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
                    Показать/Скрыть JSON
                </button>
                <button onclick="autoCADConverter.downloadJson('${filename}', arguments[0])" data-json='${JSON.stringify(jsonData)}'>
                    Скачать JSON
                </button>
            </div>
            <pre class="autocad-json-content" style="display: none;">${JSON.stringify(jsonData, null, 2)}</pre>
        `;

        this.statusArea.appendChild(resultArea);
    }

    downloadJson(filename, buttonElement) {
        const jsonData = JSON.parse(buttonElement.dataset.json);
        const blob = new Blob([JSON.stringify(jsonData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = filename.replace('.dwg', '.json');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }


    showStatus(message, type = 'info') {
        const statusDiv = document.createElement('div');
        statusDiv.className = `autocad-status-message status-${type}`;
        statusDiv.textContent = message;

        this.statusArea.insertBefore(statusDiv, this.statusArea.firstChild);

        // Удаляем сообщение через 5 секунд
        setTimeout(() => {
            if (statusDiv.parentNode) {
                statusDiv.parentNode.removeChild(statusDiv);
            }
        }, 5000);
    }

    formatFileSize(bytes) {
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 Bytes';
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    }

}

// Создаем функцию для инициализации
function initAutoCADConverter() {
    return new AutoCADConverter();
}

// Глобальный доступ для обратной совместимости
if (typeof window !== 'undefined') {
    window.autoCADConverter = initAutoCADConverter
    window.AutoCADConverter = AutoCADConverter // Прямой доступ к классу
}

export { AutoCADConverter, initAutoCADConverter }
export default initAutoCADConverter

// Инициализируем конвертер при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('autocadDropzone')) {
        window.autoCADConverterInstance = new AutoCADConverter();
    }
});