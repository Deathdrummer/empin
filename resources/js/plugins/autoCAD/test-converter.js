const { LibreDwg, Dwg_File_Type } = require('@mlightcad/libredwg-web');
const DxfParser = require('dxf-parser');
const fs = require('fs');
const path = require('path');

async function testDwgToDxfToJson() {
    try {
        console.log('🔄 Инициализация LibreDWG...');

        // Инициализация LibreDWG с указанием папки WASM
        const wasmPath = './wasm/';
        const libredwg = await LibreDwg.create(wasmPath);

        // Путь к тестовому DWG файлу
        const dwgPath = 'C:\\Users\\deathdrumer\\Desktop\\cad\\architectural_example-imperial.dwg';

        console.log('📂 Чтение DWG файла:', dwgPath);

        // Чтение DWG файла
        const dwgBuffer = fs.readFileSync(dwgPath);

        console.log('🔄 Конвертация DWG в DXF...');

        // Конвертация DWG в DXF
        const result = libredwg.dwg_read_data(dwgBuffer, Dwg_File_Type.DWG);

        if (result.error !== 0) {
            throw new Error(`DWG read error code: ${result.error}`);
        }

        console.log('✅ DWG файл успешно прочитан');

        const db = libredwg.convert(result.data);

        console.log('✅ Данные конвертированы в базу данных');

        // Попробуем экспортировать как DXF (нужно найти правильный метод)
        // Временно сохраним базу данных как JSON, исключив BigInt
        const dbString = JSON.stringify(db, (key, value) =>
            typeof value === 'bigint' ? value.toString() : value
        );

        console.log('✅ База данных сгенерирована, размер:', dbString.length, 'символов');

        // Сохранение базы данных для проверки
        fs.writeFileSync('./test-output-db.json', dbString);
        console.log('💾 База данных сохранена как test-output-db.json');

        console.log('🔄 Анализ структуры данных...');

        // Анализ структуры данных
        console.log('📊 Структура базы данных:', Object.keys(db));

        if (db.entities && Array.isArray(db.entities)) {
            console.log('📊 Количество entities:', db.entities.length);

            if (db.entities.length > 0) {
                console.log('\n🎯 Первые 5 entities:');
                db.entities.slice(0, 5).forEach((entity, index) => {
                    console.log(`  ${index + 1}. ${entity.type || 'unknown'} - ID: ${entity.id || 'N/A'}`);
                });
            }
        } else {
            console.log('📊 Entities не найдены или не массив');
        }

        console.log('\n🎉 Тест успешно завершен!');

    } catch (error) {
        console.error('❌ Ошибка:', error.message);
        console.error(error.stack);
    }
}

// Запуск теста
testDwgToDxfToJson();