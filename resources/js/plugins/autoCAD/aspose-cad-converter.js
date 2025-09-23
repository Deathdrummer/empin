const { CadApi } = require('@asposecloud/aspose-cad-cloud');

/**
 * Класс для работы с Aspose.CAD Cloud API
 * Конвертирует DWG файлы в DXF через облачный API
 */
class AsposeCadConverter {
    constructor(clientId, clientSecret) {
        this.clientId = clientId;
        this.clientSecret = clientSecret;
        this.cadApi = null;
        this.isInitialized = false;
    }

    /**
     * Инициализация API клиента
     */
    async initialize() {
        try {
            this.cadApi = new CadApi(this.clientSecret, this.clientId);
            this.isInitialized = true;
            // // console.log('✅ Aspose.CAD API инициализирован');
            return true;
        } catch (error) {
            console.error('❌ Ошибка инициализации Aspose.CAD API:', error.message);
            throw new Error(`Не удалось инициализировать Aspose.CAD API: ${error.message}`);
        }
    }

    /**
     * Загрузка файла в облачное хранилище
     */
    async uploadFile(fileName, fileBuffer) {
        if (!this.isInitialized) {
            await this.initialize();
        }

        try {
            // // console.log('🔄 Загрузка файла в облачное хранилище...');

            // Загружаем файл в облачное хранилище Aspose
            const uploadRequest = {
                path: fileName,
                file: fileBuffer
            };
            const uploadResponse = await this.cadApi.uploadFile(uploadRequest);

            // // console.log('✅ Файл успешно загружен:', fileName);
            return fileName;

        } catch (error) {
            console.error('❌ Ошибка загрузки файла:', error.message);
            throw new Error(`Не удалось загрузить файл: ${error.message}`);
        }
    }

    /**
     * Конвертация DWG в DXF
     */
    async convertDwgToDxf(sourceFileName, outputFileName = null) {
        if (!this.isInitialized) {
            await this.initialize();
        }

        try {
            // Генерируем имя выходного файла, если не указано
            if (!outputFileName) {
                const baseName = sourceFileName.replace(/\.[^/.]+$/, '');
                outputFileName = `${baseName}.dxf`;
            }

            // console.log('🔄 Конвертация DWG → DXF через Aspose.CAD API...');

            // Параметры конвертации
            const request = {
                name: sourceFileName,
                outputFormat: 'dxf',
                outPath: outputFileName,
                folder: undefined, // используем корневую папку
                storage: undefined // используем дефолтное хранилище
            };

            // Выполняем конвертацию
            const convertResponse = await this.cadApi.getDrawingSaveAs(request);

            // console.log('✅ Конвертация завершена:', outputFileName);
            return {
                success: true,
                outputFileName: outputFileName,
                response: convertResponse
            };

        } catch (error) {
            console.error('❌ Ошибка конвертации:', error.message);
            throw new Error(`Не удалось конвертировать файл: ${error.message}`);
        }
    }

    /**
     * Скачивание конвертированного файла
     */
    async downloadFile(fileName) {
        if (!this.isInitialized) {
            await this.initialize();
        }

        try {
            // console.log('🔄 Скачивание конвертированного файла...');

            const downloadRequest = {
                path: fileName
            };
            const downloadResponse = await this.cadApi.downloadFile(downloadRequest);

            // console.log('✅ Файл успешно скачан');
            return downloadResponse;

        } catch (error) {
            console.error('❌ Ошибка скачивания файла:', error.message);
            throw new Error(`Не удалось скачать файл: ${error.message}`);
        }
    }

    /**
     * Удаление файла из облачного хранилища
     */
    async deleteFile(fileName) {
        if (!this.isInitialized) {
            await this.initialize();
        }

        try {
            const deleteRequest = {
                path: fileName
            };
            await this.cadApi.deleteFile(deleteRequest);
            // console.log('🗑️ Файл удален из облачного хранилища:', fileName);
        } catch (error) {
            console.warn('⚠️ Не удалось удалить файл:', error.message);
            // Не выбрасываем ошибку, так как это не критично
        }
    }

    /**
     * Полный цикл конвертации: загрузка → конвертация → скачивание → очистка
     */
    async convertDwgToDxfFull(fileName, fileBuffer) {
        const uploadedFileName = `upload_${Date.now()}_${fileName}`;
        const convertedFileName = `converted_${Date.now()}_${fileName.replace(/\.[^/.]+$/, '')}.dxf`;

        try {
            // 1. Загружаем исходный файл
            await this.uploadFile(uploadedFileName, fileBuffer);

            // 2. Конвертируем DWG → DXF
            const convertResult = await this.convertDwgToDxf(uploadedFileName, convertedFileName);

            // 3. Скачиваем результат
            const dxfBuffer = await this.downloadFile(convertedFileName);

            // 4. Очищаем временные файлы
            await this.deleteFile(uploadedFileName);
            await this.deleteFile(convertedFileName);

            return {
                success: true,
                dxfBuffer: dxfBuffer,
                originalFileName: fileName,
                convertedFileName: convertedFileName
            };

        } catch (error) {
            // Очищаем файлы в случае ошибки
            try {
                await this.deleteFile(uploadedFileName);
                await this.deleteFile(convertedFileName);
            } catch (cleanupError) {
                console.warn('⚠️ Ошибка очистки временных файлов:', cleanupError.message);
            }

            throw error;
        }
    }

    /**
     * Проверка квоты API
     */
    async checkApiQuota() {
        try {
            // Aspose.CAD API не предоставляет прямой метод проверки квоты
            // Можем попробовать выполнить простой запрос
            // console.log('🔍 Проверка доступности API...');

            // Простая проверка - получение списка файлов
            await this.cadApi.getFilesList();

            return {
                available: true,
                message: 'API доступен'
            };
        } catch (error) {
            if (error.message.includes('quota') || error.message.includes('limit')) {
                return {
                    available: false,
                    message: 'Превышена квота API'
                };
            }

            return {
                available: false,
                message: `Ошибка API: ${error.message}`
            };
        }
    }

    /**
     * Получение информации о поддерживаемых форматах
     */
    getSupportedFormats() {
        return {
            input: ['dwg', 'dxf', 'dgn', 'dwf'],
            output: ['dxf', 'pdf', 'bmp', 'gif', 'jpeg', 'png', 'tiff', 'psd', 'svg']
        };
    }

    /**
     * Валидация DWG файла
     */
    validateDwgFile(fileName, fileSize) {
        const maxSize = 100 * 1024 * 1024; // 100MB - лимит Aspose.CAD
        const allowedExtensions = ['dwg'];
        const extension = fileName.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(extension)) {
            throw new Error('Поддерживаются только .dwg файлы');
        }

        if (fileSize > maxSize) {
            throw new Error('Файл слишком большой. Максимальный размер: 100MB');
        }

        return true;
    }
}

module.exports = AsposeCadConverter;