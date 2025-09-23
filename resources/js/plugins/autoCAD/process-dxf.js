#!/usr/bin/env node

const fs = require('fs');
const DxfParser = require('dxf-parser');

/**
 * Серверный скрипт для обработки DXF файлов
 * Использование: node process-dxf.js <путь_к_dxf_файлу>
 */

function processDxfFile(filePath) {
    try {
        // Читаем DXF файл
        const dxfContent = fs.readFileSync(filePath, 'utf8');

        // Парсим DXF
        const parser = new DxfParser();
        const dxfData = parser.parseSync(dxfContent);

        // Обогащаем данные
        const enrichedData = enrichDxfData(dxfData);

        // Генерируем статистику
        const stats = generateStats(dxfData);

        const result = {
            success: true,
            data: enrichedData,
            stats: stats
        };

        // Выводим результат в JSON
        console.log(JSON.stringify(result, null, 2));

    } catch (error) {
        const errorResult = {
            success: false,
            message: `Ошибка обработки DXF: ${error.message}`
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