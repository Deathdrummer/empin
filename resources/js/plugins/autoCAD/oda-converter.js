const { spawn, exec, execFile } = require('child_process');
const fs = require('fs');
const path = require('path');
const DxfParser = require('dxf-parser');

class ODAConverter {
    constructor() {
        // Возможные пути к ODA File Converter
        this.possiblePaths = [
            'C:\\Program Files\\ODA\\ODAFileConverter\\ODAFileConverter.exe',
            'C:\\Program Files (x86)\\ODA\\ODAFileConverter\\ODAFileConverter.exe',
            'ODAFileConverter', // если в PATH
            '/usr/bin/ODAFileConverter', // Linux
            '/opt/ODA/ODAFileConverter', // Linux альтернативный
        ];
        this.converterPath = null;
    }

    async findConverter() {
        for (const path of this.possiblePaths) {
            try {
                await this.testConverter(path);
                this.converterPath = path;
                console.log('✅ Найден ODA File Converter:', path);
                return true;
            } catch (error) {
                continue;
            }
        }

        console.log('❌ ODA File Converter не найден');
        return false;
    }

    testConverter(path) {
        return new Promise((resolve, reject) => {
            const test = spawn(path, ['--help'], { shell: true });

            test.on('close', (code) => {
                if (code === 0 || code === 1) { // код 1 тоже может быть нормальным для --help
                    resolve();
                } else {
                    reject(new Error(`Exit code: ${code}`));
                }
            });

            test.on('error', reject);

            // Таймаут для тестирования
            setTimeout(() => {
                test.kill();
                reject(new Error('Timeout'));
            }, 5000);
        });
    }

    async convertDwgToDxf(inputPath, outputPath) {
        if (!this.converterPath) {
            throw new Error('ODA File Converter не найден. Установите с opendesign.com');
        }

        const outputDir = path.dirname(outputPath);

        // Создаем выходную папку если не существует
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }

        return new Promise((resolve, reject) => {
            // Аргументы для ODA File Converter
            const args = [
                path.dirname(inputPath),  // источник папка
                outputDir,                // выходная папка
                'ACAD2018',              // версия DXF
                'DXF',                   // формат
                '0',                     // не рекурсивно
                '1',                     // аудит
                `*${path.basename(inputPath)}`  // фильтр файлов
            ];

            console.log('🔄 Запуск ODA File Converter...');
            console.log('Путь:', this.converterPath);
            console.log('Аргументы:', args);

            const converter = execFile(this.converterPath, args, {
                encoding: 'utf8',
                timeout: 30000
            }, (error, stdout, stderr) => {
                console.log('📋 ODA File Converter завершен');
                console.log('stdout:', stdout);
                console.log('stderr:', stderr);

                if (error) {
                    console.error('❌ Ошибка execFile:', error.message);
                    reject(error);
                    return;
                }

                // Проверяем существование выходного файла
                const expectedOutput = path.join(outputDir, path.basename(inputPath, '.dwg') + '.dxf');

                if (fs.existsSync(expectedOutput)) {
                    resolve({
                        success: true,
                        outputPath: expectedOutput,
                        stdout,
                        stderr
                    });
                } else {
                    resolve({
                        success: true,
                        outputPath: expectedOutput,
                        stdout,
                        stderr
                    });
                }
            });
        });
    }

    async convertDwgToJson(inputPath, outputJsonPath) {
        try {
            console.log('🚀 Начинаем конвертацию DWG → JSON');
            console.log('📂 Входной файл:', inputPath);

            // Временный DXF файл
            const tempDxfPath = path.join(
                path.dirname(outputJsonPath),
                'temp_' + path.basename(inputPath, '.dwg') + '.dxf'
            );

            // Шаг 1: DWG → DXF
            console.log('🔄 Этап 1: DWG → DXF...');
            const convertResult = await this.convertDwgToDxf(inputPath, tempDxfPath);

            if (!convertResult.success) {
                throw new Error('Не удалось конвертировать DWG в DXF');
            }

            console.log('✅ DWG успешно конвертирован в DXF');

            // Шаг 2: DXF → JSON
            console.log('🔄 Этап 2: DXF → JSON...');

            const dxfContent = fs.readFileSync(convertResult.outputPath, 'utf8');
            console.log('📄 DXF файл прочитан, размер:', dxfContent.length, 'символов');

            const parser = new DxfParser();
            const dxfData = parser.parseSync(dxfContent);

            console.log('✅ DXF успешно распарсен');

            // Сохраняем JSON
            const jsonContent = JSON.stringify(dxfData, null, 2);
            fs.writeFileSync(outputJsonPath, jsonContent);

            console.log('💾 JSON сохранен:', outputJsonPath);

            // Удаляем временный DXF (опционально)
            if (fs.existsSync(tempDxfPath)) {
                fs.unlinkSync(tempDxfPath);
                console.log('🗑️ Временный DXF удален');
            }

            // Статистика
            console.log('\n📊 Статистика конвертации:');
            console.log('- Версия DXF:', dxfData.header ? dxfData.header.$ACADVER : 'неизвестно');
            console.log('- Количество entities:', dxfData.entities ? dxfData.entities.length : 0);
            console.log('- Количество слоев:', dxfData.tables && dxfData.tables.layer ? Object.keys(dxfData.tables.layer.layers).length : 0);

            return {
                success: true,
                jsonPath: outputJsonPath,
                data: dxfData,
                stats: {
                    version: dxfData.header ? dxfData.header.$ACADVER : null,
                    entitiesCount: dxfData.entities ? dxfData.entities.length : 0,
                    layersCount: dxfData.tables && dxfData.tables.layer ? Object.keys(dxfData.tables.layer.layers).length : 0
                }
            };

        } catch (error) {
            console.error('❌ Ошибка конвертации:', error.message);
            throw error;
        }
    }
}

module.exports = ODAConverter;