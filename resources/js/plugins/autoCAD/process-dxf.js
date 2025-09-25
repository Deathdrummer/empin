#!/usr/bin/env node

const fs = require('fs');
const DxfParser = require('dxf-parser');

/**
 * Серверный скрипт для обработки DXF файлов
 * Использование: node process-dxf.js <путь_к_dxf_файлу>
 */

function processDxfFile(filePath) {
    try {
        // Получаем информацию о файле
        const fileStats = fs.statSync(filePath);
        console.error(`DEBUG: Обрабатывается файл: ${filePath}, размер: ${fileStats.size} байт`);

        // Пробуем разные кодировки
        let dxfContent;
        let encoding = 'utf8';

        try {
            dxfContent = fs.readFileSync(filePath, 'utf8');
        } catch (encodingError) {
            console.error('DEBUG: Ошибка UTF-8, пробуем latin1');
            try {
                dxfContent = fs.readFileSync(filePath, 'latin1');
                encoding = 'latin1';
            } catch (latin1Error) {
                console.error('DEBUG: Ошибка latin1, пробуем ascii');
                dxfContent = fs.readFileSync(filePath, 'ascii');
                encoding = 'ascii';
            }
        }

        console.error(`DEBUG: Файл прочитан в кодировке: ${encoding}, длина контента: ${dxfContent.length}`);

        // Проверяем что файл похож на DXF
        if (!dxfContent.includes('SECTION') && !dxfContent.includes('ENTITIES')) {
            throw new Error('Файл не содержит стандартных DXF секций');
        }

        // Парсим DXF с защитой от ошибок
        const parser = new DxfParser();
        let dxfData;

        console.error(`DEBUG: Парсинг DXF файла размером ${dxfContent.length} символов`);

        try {
            dxfData = parser.parseSync(dxfContent);
        } catch (parsingError) {
            console.error(`DEBUG: Ошибка парсинга DXF: ${parsingError.message}`);

            // Создаем минимальную структуру данных
            dxfData = {
                header: {},
                entities: [],
                tables: {},
                blocks: {}
            };
        }

        // Проверяем что парсинг прошел успешно
        if (!dxfData) {
            throw new Error('DXF парсер не смог обработать файл - получен null');
        }

        console.error(`DEBUG: Найдено entities: ${dxfData.entities ? dxfData.entities.length : 0}`);

        // Инициализируем entities если они отсутствуют
        if (!dxfData.entities) {
            dxfData.entities = [];
            console.error('DEBUG: Секция entities отсутствует, создаем пустую');
        }

        // Ограничиваем количество entities для больших файлов
        if (dxfData.entities.length > 10000) {
            console.error(`DEBUG: Ограничиваем entities с ${dxfData.entities.length} до 10000`);
            dxfData.entities = dxfData.entities.slice(0, 10000);
        }

        // Обогащаем данные
        const enrichedData = enrichDxfData(dxfData);

        // Генерируем статистику
        const stats = generateStats(dxfData);

        const result = {
            success: true,
            data: enrichedData,
            stats: stats
        };

        // Ограничиваем размер JSON output
        const jsonString = JSON.stringify(result, null, 2);
        const maxOutputSize = 5 * 1024 * 1024; // 5MB максимум

        if (jsonString.length > maxOutputSize) {
            console.error(`DEBUG: JSON слишком большой (${jsonString.length} символов), обрезаем`);

            // Создаем урезанную версию результата
            const trimmedResult = {
                success: true,
                data: {
                    ...result.data,
                    entities: result.data.entities ? result.data.entities.slice(0, 100) : [], // только первые 100
                    _truncated: true,
                    _original_entities_count: result.data.entities ? result.data.entities.length : 0
                },
                stats: result.stats
            };

            console.log(JSON.stringify(trimmedResult, null, 2));
        } else {
            console.log(jsonString);
        }

    } catch (error) {
        console.error(`DEBUG: Ошибка парсинга DXF: ${error.message}`);

        // Детальная диагностика ошибки
        let detailedMessage = error.message;

        if (error.message.includes('Unexpected end of input')) {
            detailedMessage = 'DXF файл поврежден или неполный. Возможно, проблема с конвертацией из DWG.';
        } else if (error.message.includes('Group code does not have a defined type')) {
            detailedMessage = 'DXF файл содержит некорректные коды групп. Возможно, неподдерживаемая версия формата.';
        } else if (error.message.includes('SECTION') || error.message.includes('ENTITIES')) {
            detailedMessage = 'DXF файл не содержит обязательных секций или имеет некорректную структуру.';
        }

        const errorResult = {
            success: false,
            message: `Ошибка обработки DXF: ${detailedMessage}`,
            originalError: error.message,
            debugging: {
                errorType: error.constructor.name,
                stack: error.stack ? error.stack.split('\n')[0] : 'No stack trace'
            }
        };

        console.log(JSON.stringify(errorResult));
        process.exit(1);
    }
}

function enrichDxfData(dxfData) {
    const enriched = {
        ...dxfData,
        metadata: {
            processedAt: new Date().toISOString(),
            version: dxfData.header ? dxfData.header.$ACADVER : 'unknown',
            entitiesCount: dxfData.entities ? dxfData.entities.length : 0,
            layersCount: dxfData.tables && dxfData.tables.layer ? Object.keys(dxfData.tables.layer.layers).length : 0
        }
    };

    // Добавляем информацию о границах чертежа
    if (dxfData.entities && dxfData.entities.length > 0) {
        enriched.bounds = calculateBounds(dxfData.entities);
    }

    // Группируем entities по типам
    if (dxfData.entities) {
        enriched.entitiesByType = groupEntitiesByType(dxfData.entities);
    }

    return enriched;
}

function calculateBounds(entities) {
    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

    entities.forEach(entity => {
        const points = extractPointsFromEntity(entity);
        points.forEach(point => {
            if (point.x < minX) minX = point.x;
            if (point.x > maxX) maxX = point.x;
            if (point.y < minY) minY = point.y;
            if (point.y > maxY) maxY = point.y;
        });
    });

    return {
        min: { x: minX, y: minY },
        max: { x: maxX, y: maxY },
        width: maxX - minX,
        height: maxY - minY
    };
}

function extractPointsFromEntity(entity) {
    const points = [];

    switch (entity.type) {
        case 'LINE':
            if (entity.startPoint) points.push(entity.startPoint);
            if (entity.endPoint) points.push(entity.endPoint);
            break;

        case 'POLYLINE':
        case 'LWPOLYLINE':
            if (entity.vertices) {
                entity.vertices.forEach(vertex => {
                    points.push({ x: vertex.x, y: vertex.y });
                });
            }
            break;

        case 'CIRCLE':
        case 'ARC':
            if (entity.center) {
                const radius = entity.radius || 0;
                points.push({
                    x: entity.center.x - radius,
                    y: entity.center.y - radius
                });
                points.push({
                    x: entity.center.x + radius,
                    y: entity.center.y + radius
                });
            }
            break;

        case 'TEXT':
        case 'MTEXT':
            if (entity.startPoint) points.push(entity.startPoint);
            break;

        case 'INSERT':
            if (entity.position) points.push(entity.position);
            break;

        default:
            if (entity.startPoint) points.push(entity.startPoint);
            if (entity.endPoint) points.push(entity.endPoint);
            if (entity.center) points.push(entity.center);
            if (entity.position) points.push(entity.position);
            break;
    }

    return points;
}

function groupEntitiesByType(entities) {
    const grouped = {};

    entities.forEach(entity => {
        const type = entity.type || 'UNKNOWN';
        if (!grouped[type]) {
            grouped[type] = [];
        }
        grouped[type].push(entity);
    });

    return grouped;
}

function generateStats(dxfData) {
    const stats = {
        version: dxfData.header ? dxfData.header.$ACADVER : null,
        entitiesCount: dxfData.entities ? dxfData.entities.length : 0,
        layersCount: 0,
        layers: [],
        entityTypes: {},
        units: null
    };

    // Статистика по слоям
    if (dxfData.tables && dxfData.tables.layer) {
        const layers = dxfData.tables.layer.layers;
        stats.layersCount = Object.keys(layers).length;
        stats.layers = Object.keys(layers).map(name => ({
            name,
            color: layers[name].color,
            lineType: layers[name].lineType
        }));
    }

    // Статистика по типам entities
    if (dxfData.entities) {
        dxfData.entities.forEach(entity => {
            const type = entity.type || 'UNKNOWN';
            stats.entityTypes[type] = (stats.entityTypes[type] || 0) + 1;
        });
    }

    // Единицы измерения
    if (dxfData.header && dxfData.header.$INSUNITS) {
        const unitMap = {
            1: 'inches',
            2: 'feet',
            4: 'millimeters',
            5: 'centimeters',
            6: 'meters'
        };
        stats.units = unitMap[dxfData.header.$INSUNITS] || 'unknown';
    }

    return stats;
}

// Запуск скрипта
if (require.main === module) {
    const filePath = process.argv[2];

    if (!filePath) {
        console.log(JSON.stringify({
            success: false,
            message: 'Не указан путь к DXF файлу'
        }));
        process.exit(1);
    }

    if (!fs.existsSync(filePath)) {
        console.log(JSON.stringify({
            success: false,
            message: 'DXF файл не найден'
        }));
        process.exit(1);
    }

    processDxfFile(filePath);
}