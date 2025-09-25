#!/usr/bin/env node

const fs = require('fs');

// Проверяем доступность Aspose.CAD модуля
let AsposeCadConverter;
try {
    AsposeCadConverter = require('./aspose-cad-converter');
} catch (error) {
    console.error('ОШИБКА: Модуль @asposecloud/aspose-cad-cloud не найден.');
    console.error('Выполните: npm install');

    const errorResult = {
        success: false,
        message: 'Модуль @asposecloud/aspose-cad-cloud не установлен на сервере. Обратитесь к администратору для установки npm зависимостей.'
    };

    process.stdout.write(JSON.stringify(errorResult));
    process.exit(1);
}

/**
 * Серверный скрипт для конвертации DWG в DXF через Aspose.CAD
 * Использование: node convert-dwg.js <путь_к_dwg_файлу> <путь_для_dxf_файла>
 */

async function convertDwgToDxf(dwgPath, dxfPath) {
    try {
        // API ключи (в продакшне должны быть в .env)
        const clientId = 'c33a115d-09f0-4a3b-8809-898850c08af7';
        const clientSecret = 'aec376f8e93dad69f38944b009d5dacc';

        // Создаем конвертер
        const converter = new AsposeCadConverter(clientId, clientSecret);

        // Проверяем доступность API перед конвертацией
        try {
            const quotaCheck = await converter.checkApiQuota();
            if (!quotaCheck.available) {
                throw new Error(`API недоступен: ${quotaCheck.message}`);
            }
        } catch (apiError) {
            throw new Error(`Ошибка проверки API: ${apiError.message}`);
        }

        // Читаем DWG файл
        const dwgBuffer = fs.readFileSync(dwgPath);

        // Конвертируем DWG → DXF
        const result = await converter.convertDwgToDxfFull(
            `input_${Date.now()}.dwg`,
            dwgBuffer
        );

        if (!result.success) {
            throw new Error('Не удалось конвертировать DWG файл');
        }

        // Сохраняем DXF файл
        fs.writeFileSync(dxfPath, result.dxfBuffer);

        const successResult = {
            success: true,
            message: 'DWG успешно конвертирован в DXF',
            inputFile: dwgPath,
            outputFile: dxfPath,
            originalFileName: result.originalFileName,
            convertedFileName: result.convertedFileName
        };

        // Выводим только JSON результат
        process.stdout.write(JSON.stringify(successResult));

    } catch (error) {
        const errorResult = {
            success: false,
            message: `Ошибка конвертации DWG: ${error.message}`
        };

        process.stdout.write(JSON.stringify(errorResult));
        process.exit(1);
    }
}

// Запуск скрипта
if (require.main === module) {
    const dwgPath = process.argv[2];
    const dxfPath = process.argv[3];

    if (!dwgPath || !dxfPath) {
        process.stdout.write(JSON.stringify({
            success: false,
            message: 'Не указаны пути к входному и выходному файлам'
        }));
        process.exit(1);
    }

    if (!fs.existsSync(dwgPath)) {
        process.stdout.write(JSON.stringify({
            success: false,
            message: 'DWG файл не найден'
        }));
        process.exit(1);
    }

    convertDwgToDxf(dwgPath, dxfPath);
}