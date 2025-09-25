#!/usr/bin/env node

/**
 * CAD-Share.exe - Монолитное приложение для расшаривания CAD файлов
 * Версия: 1.0 Production Ready
 *
 * Проектировщики просто скачивают этот файл в папку с CAD файлами и запускают
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');
const os = require('os');

class CADShare {
    constructor() {
        this.server = null;
        this.tunnelProcess = null;
        this.port = 8000;
        this.publicUrl = null;

        // Показать заголовок
        this.showHeader();
        this.init();
    }

    showHeader() {
        console.clear();
        console.log('╔══════════════════════════════════════════════╗');
        console.log('║              🚀 CAD-Share v1.0               ║');
        console.log('║         Расшаривание CAD файлов             ║');
        console.log('╚══════════════════════════════════════════════╝');
        console.log('');
    }

    async init() {
        try {
            // Найти свободный порт
            this.port = await this.findFreePort(8000);

            // Запустить HTTP сервер
            await this.startHTTPServer();

            // Показать инструкции
            this.showInstructions();

            // Опционально попробовать создать туннель
            this.tryCreateTunnel();

        } catch (error) {
            console.log(`❌ Ошибка инициализации: ${error.message}`);
        }
    }

    async findFreePort(start = 8000) {
        return new Promise((resolve) => {
            const server = http.createServer();
            server.listen(start, (err) => {
                if (err) {
                    resolve(this.findFreePort(start + 1));
                } else {
                    const port = server.address().port;
                    server.close(() => resolve(port));
                }
            });
        });
    }

    async startHTTPServer() {
        return new Promise((resolve, reject) => {
            this.server = http.createServer((req, res) => {
                // CORS заголовки
                res.setHeader('Access-Control-Allow-Origin', '*');
                res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
                res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

                if (req.method === 'OPTIONS') {
                    res.writeHead(200);
                    res.end();
                    return;
                }

                let filePath = path.join(process.cwd(), req.url === '/' ? '' : decodeURIComponent(req.url));

                try {
                    const stats = fs.statSync(filePath);

                    if (stats.isDirectory() || req.url === '/') {
                        this.serveDirectory(filePath, res);
                    } else {
                        this.serveFile(filePath, res);
                    }
                } catch (error) {
                    res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
                    res.end('Файл не найден');
                }
            });

            this.server.listen(this.port, () => {
                console.log(`✅ HTTP сервер запущен на http://localhost:${this.port}`);
                console.log(`📁 Рабочая папка: ${process.cwd()}`);
                resolve();
            });

            this.server.on('error', (err) => {
                reject(err);
            });
        });
    }

    serveDirectory(dirPath, res) {
        fs.readdir(dirPath, (err, files) => {
            if (err) {
                res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
                res.end('Ошибка чтения директории');
                return;
            }

            const cadFiles = files.filter(file => /\.(dwg|dxf)$/i.test(file));
            const otherFiles = files.filter(file => !/\.(dwg|dxf)$/i.test(file) && !file.startsWith('.'));

            let html = `
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAD Files - ${path.basename(dirPath)}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .files-section { background: white; padding: 20px; border-radius: 5px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .file-item { padding: 10px; margin: 5px 0; background: #ecf0f1; border-radius: 3px; display: flex; justify-content: space-between; align-items: center; }
        .file-item:hover { background: #bdc3c7; }
        .file-link { text-decoration: none; color: #2c3e50; font-weight: bold; }
        .file-size { color: #7f8c8d; font-size: 0.9em; }
        .cad-file { background: #e8f5e8; border-left: 4px solid #27ae60; }
        .status { background: #3498db; color: white; padding: 10px; border-radius: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 CAD-Share - Файлы готовы к использованию</h1>
        <p>Сервер работает на порту ${this.port}</p>
    </div>

    <div class="status">
        ✅ Сервер активен | 📁 Папка: ${path.basename(process.cwd())} | 🔗 Файлы доступны через API
    </div>

    <div class="files-section">
        <h2>🎯 CAD файлы (${cadFiles.length})</h2>
            `;

            if (cadFiles.length > 0) {
                cadFiles.forEach(file => {
                    try {
                        const stats = fs.statSync(path.join(dirPath, file));
                        const size = this.formatFileSize(stats.size);
                        html += `
                        <div class="file-item cad-file">
                            <a href="${encodeURIComponent(file)}" class="file-link">${file}</a>
                            <span class="file-size">${size}</span>
                        </div>`;
                    } catch (e) {
                        html += `
                        <div class="file-item cad-file">
                            <a href="${encodeURIComponent(file)}" class="file-link">${file}</a>
                            <span class="file-size">Размер недоступен</span>
                        </div>`;
                    }
                });
            } else {
                html += '<p>❌ CAD файлы не найдены в этой папке</p>';
            }

            html += '</div>';

            // Показать другие файлы если есть
            if (otherFiles.length > 0 && otherFiles.length < 20) {
                html += `
                <div class="files-section">
                    <h3>📄 Другие файлы (${otherFiles.length})</h3>
                `;

                otherFiles.slice(0, 10).forEach(file => {
                    try {
                        const stats = fs.statSync(path.join(dirPath, file));
                        const size = this.formatFileSize(stats.size);
                        html += `
                        <div class="file-item">
                            <a href="${encodeURIComponent(file)}" class="file-link">${file}</a>
                            <span class="file-size">${size}</span>
                        </div>`;
                    } catch (e) {
                        // пропустить
                    }
                });

                html += '</div>';
            }

            html += `
    <div class="files-section">
        <h3>💡 Инструкции</h3>
        <p><strong>Для настройки сайта:</strong></p>
        <ol>
            <li>Скопируйте адрес: <code>http://localhost:${this.port}</code></li>
            <li>Вставьте в настройки договоров сайта</li>
            <li>Держите это окно открытым во время работы</li>
        </ol>
        <p><strong>Сетевой доступ:</strong> Если нужен доступ из интернета, используйте Tailscale или localhost.run</p>
    </div>

</body>
</html>`;

            res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
            res.end(html);
        });
    }

    serveFile(filePath, res) {
        fs.readFile(filePath, (err, data) => {
            if (err) {
                res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
                res.end('Файл не найден');
                return;
            }

            // Определить MIME тип
            const ext = path.extname(filePath).toLowerCase();
            let contentType = 'application/octet-stream';

            if (ext === '.dwg') contentType = 'application/acad';
            if (ext === '.dxf') contentType = 'application/dxf';

            res.writeHead(200, {
                'Content-Type': contentType,
                'Content-Disposition': `attachment; filename="${path.basename(filePath)}"`
            });
            res.end(data);
        });
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showInstructions() {
        console.log('');
        console.log('📋 ИНСТРУКЦИИ ДЛЯ ПРОЕКТИРОВЩИКА:');
        console.log('═══════════════════════════════════════════════');
        console.log(`🏠 Локальный сервер: http://localhost:${this.port}`);
        console.log('🌐 Ожидаем создания публичной ссылки...');
        console.log('');
        console.log('💡 ЧТО ДЕЛАТЬ:');
        console.log('   1. Дождитесь публичной ссылки (https://...)');
        console.log('   2. Скопируйте её в настройки договоров сайта');
        console.log('   3. Держите это окно открытым во время работы');
        console.log('');
        console.log('⚠️  ВАЖНО: Ссылка изменится при перезапуске!');
        console.log('⏹️  Остановка: Ctrl+C или закрыть окно');
        console.log('═══════════════════════════════════════════════');
        console.log('');
    }

    tryCreateTunnel() {
        // Опциональная попытка создать localhost.run туннель
        console.log('🔍 Поиск SSH для создания туннеля...');

        const sshCommands = ['ssh', 'C:\\Windows\\System32\\OpenSSH\\ssh.exe'];
        let sshFound = false;

        for (const sshCmd of sshCommands) {
            try {
                const testProcess = spawn(sshCmd, ['-V'], { stdio: 'pipe' });
                testProcess.on('close', (code) => {
                    if (!sshFound && (code === 0 || code === 255)) { // SSH может возвращать 255 при -V
                        sshFound = true;
                        console.log('✅ SSH найден, создаю туннель localhost.run...');
                        this.createTunnel(sshCmd);
                    }
                });
                break;
            } catch (e) {
                // продолжаем поиск
            }
        }

        setTimeout(() => {
            if (!sshFound) {
                console.log('⚠️  SSH не найден - туннель недоступен');
                console.log('   Используйте http://localhost:' + this.port + ' в локальной сети');
            }
        }, 2000);
    }

    createTunnel(sshCommand) {
        try {
            console.log('🔗 Создание localhost.run туннеля...');

            this.tunnelProcess = spawn(sshCommand, [
                '-R', `80:localhost:${this.port}`,
                '-o', 'StrictHostKeyChecking=no',
                '-o', 'UserKnownHostsFile=/dev/null',
                '-o', 'LogLevel=QUIET',
                'nokey@localhost.run'
            ], {
                stdio: ['pipe', 'pipe', 'pipe']
            });

            this.tunnelProcess.stdout.on('data', (data) => {
                const output = data.toString();
                console.log('SSH stdout:', output.trim());

                // Ищем URL в выводе stdout
                const urlMatch = output.match(/(https:\/\/[a-zA-Z0-9\-]+\.lhr\.life|https:\/\/[a-zA-Z0-9\-]+\.localhost\.run)/);
                if (urlMatch && !this.publicUrl) {
                    this.publicUrl = urlMatch[0];
                    this.displayPublicUrl();
                }
            });

            this.tunnelProcess.stderr.on('data', (data) => {
                const output = data.toString();

                // Ищем URL в выводе stderr
                const urlMatch = output.match(/(https:\/\/[a-zA-Z0-9\-]+\.lhr\.life|https:\/\/[a-zA-Z0-9\-]+\.localhost\.run)/);
                if (urlMatch && !this.publicUrl) {
                    this.publicUrl = urlMatch[0];
                    this.displayPublicUrl();
                }

                // Проверяем на подключение
                if (output.includes('tunneled with tls termination')) {
                    const match = output.match(/(https:\/\/[a-zA-Z0-9\-]+\.lhr\.life)/);
                    if (match && !this.publicUrl) {
                        this.publicUrl = match[0];
                        this.displayPublicUrl();
                    }
                }
            });

            this.tunnelProcess.on('error', (err) => {
                console.log('⚠️  SSH туннель недоступен:', err.message);
                console.log('💡 Используйте Tailscale для удаленного доступа');
            });

            this.tunnelProcess.on('close', (code) => {
                if (code !== 0) {
                    console.log('⚠️  SSH туннель отключен');
                }
            });

            // Timeout для обнаружения URL
            setTimeout(() => {
                if (!this.publicUrl) {
                    console.log('⚠️  Публичный URL не получен за 30 сек');
                    console.log('💡 Проверьте интернет-соединение или используйте Tailscale');
                }
            }, 30000);

        } catch (error) {
            console.log('⚠️  Ошибка создания туннеля:', error.message);
        }
    }

    displayPublicUrl() {
        console.log('');
        console.log('🎉 ПУБЛИЧНЫЙ ДОСТУП АКТИВЕН!');
        console.log('══════════════════════════════════════════════════════');
        console.log(`🌍 Публичная ссылка: ${this.publicUrl}`);
        console.log('📋 СКОПИРУЙТЕ ЭТУ ССЫЛКУ В НАСТРОЙКИ САЙТА!');
        console.log('⚠️  Ссылка изменится при перезапуске программы');
        console.log('══════════════════════════════════════════════════════');
        console.log('');
    }

    stop() {
        console.log('');
        console.log('⏹️  Останавливаю CAD-Share...');

        if (this.server) {
            this.server.close();
            console.log('✅ HTTP сервер остановлен');
        }

        if (this.tunnelProcess) {
            this.tunnelProcess.kill();
            console.log('✅ Туннель закрыт');
        }

        console.log('👋 CAD-Share завершен. До свидания!');
        process.exit(0);
    }
}

// Запуск приложения
const cadShare = new CADShare();

// Обработка сигналов завершения
process.on('SIGINT', () => cadShare.stop());
process.on('SIGTERM', () => cadShare.stop());

// Обработка ошибок
process.on('uncaughtException', (err) => {
    console.log('❌ Критическая ошибка:', err.message);
    console.log('   Перезапустите приложение');
});

// Windows specific
if (os.platform() === 'win32') {
    process.on('SIGBREAK', () => cadShare.stop());
}

console.log('ℹ️  CAD-Share запускается...');