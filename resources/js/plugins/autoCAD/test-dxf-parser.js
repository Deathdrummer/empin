const DxfParser = require('dxf-parser');
const fs = require('fs');

async function testDxfParser() {
    try {
        console.log('🔄 Тест DXF парсера...');

        // Путь к тестовому DXF файлу
        const dxfPath = 'C:\\Users\\deathdrumer\\Desktop\\cad\\test_dxfighter.dxf';

        console.log('📂 Чтение DXF файла:', dxfPath);

        // Чтение DXF файла
        const dxfString = fs.readFileSync(dxfPath, 'utf8');

        console.log('✅ DXF файл прочитан, размер:', dxfString.length, 'символов');

        console.log('🔄 Парсинг DXF в JSON...');

        // Парсинг DXF в JSON
        const parser = new DxfParser();
        const dxf = parser.parseSync(dxfString);

        console.log('✅ JSON сгенерирован');

        // Сохранение JSON
        const jsonOutput = JSON.stringify(dxf, null, 2);
        fs.writeFileSync('./test-dxf-output.json', jsonOutput);
        console.log('💾 JSON сохранен как test-dxf-output.json');

        // Вывод статистики
        console.log('\n📊 Статистика:');
        console.log('- Версия DXF:', dxf.header ? dxf.header.$ACADVER : 'неизвестно');
        console.log('- Количество entities:', dxf.entities ? dxf.entities.length : 0);
        console.log('- Количество блоков:', dxf.blocks ? dxf.blocks.length : 0);
        console.log('- Количество слоев:', dxf.tables && dxf.tables.layer ? Object.keys(dxf.tables.layer.layers).length : 0);

        if (dxf.entities && dxf.entities.length > 0) {
            console.log('\n🎯 Первые 10 entities:');
            dxf.entities.slice(0, 10).forEach((entity, index) => {
                console.log(`  ${index + 1}. ${entity.type} - слой: ${entity.layer || 'default'}`);
                if (entity.vertices && entity.vertices.length > 0) {
                    console.log(`     координаты: x=${entity.vertices[0].x}, y=${entity.vertices[0].y}`);
                } else if (entity.startPoint) {
                    console.log(`     начало: x=${entity.startPoint.x}, y=${entity.startPoint.y}`);
                }
            });
        }

        if (dxf.tables && dxf.tables.layer) {
            console.log('\n🎨 Слои:');
            Object.keys(dxf.tables.layer.layers).slice(0, 5).forEach((layerName) => {
                const layer = dxf.tables.layer.layers[layerName];
                console.log(`  - ${layerName}: цвет=${layer.color || 'default'}`);
            });
        }

        console.log('\n🎉 Тест DXF парсера успешно завершен!');

    } catch (error) {
        console.error('❌ Ошибка:', error.message);
        console.error(error.stack);
    }
}

// Запуск теста
testDxfParser();