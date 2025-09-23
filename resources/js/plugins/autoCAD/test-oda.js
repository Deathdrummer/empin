const ODAConverter = require('./oda-converter');

async function testODA() {
    try {
        const converter = new ODAConverter();

        console.log('🔍 Поиск ODA File Converter...');
        const found = await converter.findConverter();

        if (!found) {
            console.log('❌ ODA File Converter не найден');
            console.log('📥 Скачайте с: https://www.opendesign.com/guestfiles/oda_file_converter');
            return;
        }

        // Тестируем конвертацию
        const inputFile = 'C:\\Users\\deathdrumer\\Desktop\\cad\\test.dwg';
        const outputFile = './test-oda-output.json';

        const result = await converter.convertDwgToJson(inputFile, outputFile);

        if (result.success) {
            console.log('\n🎉 Конвертация успешно завершена!');
            console.log('📄 Результат сохранен в:', result.jsonPath);
            console.log('📊 Найдено entities:', result.stats.entitiesCount);
        }

    } catch (error) {
        console.error('❌ Ошибка:', error.message);
    }
}

// Запуск теста
testODA();