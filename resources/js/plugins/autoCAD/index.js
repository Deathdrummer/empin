const DxfParser = require('dxf-parser');

class AutoCADProcessor {
    constructor() {
        this.supportedFormats = ['dxf'];
        this.maxFileSize = 10 * 1024 * 1024; // 10MB для DXF
    }

    // @>25@:0 ?>445@68205<>3> D>@<0B0
    isFormatSupported(filename) {
        const ext = filename.toLowerCase().split('.').pop();
        return this.supportedFormats.includes(ext);
    }

    // 0@A8=3 DXF D09;0 2 JSON
    async parseDxfToJson(fileContent) {
        try {
            console.log('= 0G8=05< ?0@A8=3 DXF D09;0...');

            const parser = new DxfParser();
            const dxfData = parser.parseSync(fileContent);

            console.log(' DXF D09; CA?5H=> @0A?0@A5=');

            // 1>30I05< 40==K5 4>?>;=8B5;L=>9 8=D>@<0F859
            const enrichedData = this.enrichDxfData(dxfData);

            return {
                success: true,
                data: enrichedData,
                stats: this.generateStats(dxfData)
            };

        } catch (error) {
            console.error('L H81:0 ?0@A8=30 DXF:', error.message);
            throw new Error(`H81:0 ?0@A8=30 DXF D09;0: ${error.message}`);
        }
    }

    // 1>30I5=85 40==KE DXF
    enrichDxfData(dxfData) {
        const enriched = {
            ...dxfData,
            metadata: {
                processedAt: new Date().toISOString(),
                version: dxfData.header ? dxfData.header.$ACADVER : 'unknown',
                entitiesCount: dxfData.entities ? dxfData.entities.length : 0,
                layersCount: dxfData.tables && dxfData.tables.layer ? Object.keys(dxfData.tables.layer.layers).length : 0
            }
        };

        // >102;O5< 8=D>@<0F8N > 3@0=8F0E G5@B560
        if (dxfData.entities && dxfData.entities.length > 0) {
            enriched.bounds = this.calculateBounds(dxfData.entities);
        }

        // @C??8@C5< entities ?> B8?0<
        if (dxfData.entities) {
            enriched.entitiesByType = this.groupEntitiesByType(dxfData.entities);
        }

        return enriched;
    }

    // KG8A;5=85 3@0=8F G5@B560
    calculateBounds(entities) {
        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

        entities.forEach(entity => {
            const points = this.extractPointsFromEntity(entity);
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

    // 72;5G5=85 B>G5: 87 entity
    extractPointsFromEntity(entity) {
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
                // >?KB:0 =09B8 ;N1K5 :>>@48=0BK 2 entity
                if (entity.startPoint) points.push(entity.startPoint);
                if (entity.endPoint) points.push(entity.endPoint);
                if (entity.center) points.push(entity.center);
                if (entity.position) points.push(entity.position);
                break;
        }

        return points;
    }

    // @C??8@>2:0 entities ?> B8?0<
    groupEntitiesByType(entities) {
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

    // 5=5@0F8O AB0B8AB8:8
    generateStats(dxfData) {
        const stats = {
            version: dxfData.header ? dxfData.header.$ACADVER : null,
            entitiesCount: dxfData.entities ? dxfData.entities.length : 0,
            layersCount: 0,
            layers: [],
            entityTypes: {},
            units: null
        };

        // !B0B8AB8:0 ?> A;>O<
        if (dxfData.tables && dxfData.tables.layer) {
            const layers = dxfData.tables.layer.layers;
            stats.layersCount = Object.keys(layers).length;
            stats.layers = Object.keys(layers).map(name => ({
                name,
                color: layers[name].color,
                lineType: layers[name].lineType
            }));
        }

        // !B0B8AB8:0 ?> B8?0< entities
        if (dxfData.entities) {
            dxfData.entities.forEach(entity => {
                const type = entity.type || 'UNKNOWN';
                stats.entityTypes[type] = (stats.entityTypes[type] || 0) + 1;
            });
        }

        // 48=8FK 87<5@5=8O
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

    // 0;840F8O D09;0
    validateFile(filename, fileSize) {
        if (!this.isFormatSupported(filename)) {
            throw new Error(`$>@<0B D09;0 =5 ?>445@68205BAO. >445@68205<K5 D>@<0BK: ${this.supportedFormats.join(', ')}`);
        }

        if (fileSize > this.maxFileSize) {
            throw new Error(`$09; A;8H:>< 1>;LH>9. 0:A8<0;L=K9 @07<5@: ${this.maxFileSize / 1024 / 1024}MB`);
        }

        return true;
    }

    // A=>2=>9 <5B>4 >1@01>B:8 D09;0
    async processFile(filename, fileContent) {
        try {
            console.log('=� 0G8=05< >1@01>B:C D09;0:', filename);

            // 0;840F8O
            this.validateFile(filename, fileContent.length);

            // ?@545;5=85 B8?0 D09;0 8 >1@01>B:0
            const ext = filename.toLowerCase().split('.').pop();

            let result;
            switch (ext) {
                case 'dxf':
                    result = await this.parseDxfToJson(fileContent);
                    break;
                default:
                    throw new Error(`1@01>BG8: 4;O D>@<0B0 .${ext} =5 @50;87>20=`);
            }

            console.log('<� $09; CA?5H=> >1@01>B0=');
            return result;

        } catch (error) {
            console.error('L H81:0 >1@01>B:8 D09;0:', error.message);
            throw error;
        }
    }

}

// -:A?>@B 4;O 8A?>;L7>20=8O 2 Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AutoCADProcessor;
}

// ;>10;L=0O ?5@5<5==0O 4;O 1@0C75@0
if (typeof window !== 'undefined') {
    window.AutoCADProcessor = AutoCADProcessor;
}