#!/usr/bin/env node

/**
 * CAD-Share Test Version - Node.js
 * Простой HTTP сервер для тестирования
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

class CADShareTest {
    constructor() {
        this.server = null;
        this.sshProcess = null;
        this.port = 8000;
        this.publicUrl = null;

        this.init();
    }

    init() {
        console.log('🚀 CAD-Share Test запущен');
        this.findFreePort().then(port => {
            this.port = port;
            this.startServer();
        });
    }

    async findFreePort(start = 8000) {
        return new Promise((resolve) => {
            const server = http.createServer();
            server.listen(start, (err) => {
                if (err) {
                    resolve(this.findFreePort(start + 1));
                } else {
                    server.close(() => resolve(start));
                }
            });
        });
    }

    startServer() {
        this.server = http.createServer((req, res) => {
            let filePath = path.join(process.cwd(), req.url === '/' ? '' : req.url);

            // Если это директория, показываем список файлов
            if (fs.statSync(filePath).isDirectory() || req.url === '/') {
                this.serveDirectory(filePath, res);
            } else {
                this.serveFile(filePath, res);
            }
        });

        this.server.listen(this.port, () => {
            console.log(`✅ HTTP сервер запущен на http://localhost:${this.port}`);
            console.log(`📁 Папка: ${process.cwd()}`);

            // Создаем SSH туннель
            this.createSSHTunnel();
        });
    }

    serveDirectory(dirPath, res) {
        fs.readdir(dirPath, (err, files) => {
            if (err) {
                res.writeHead(500);
                res.end('Ошибка чтения директории');
                return;
            }

            const cadFiles = files.filter(file => /\.(dwg|dxf)$/i.test(file));

            let html = `
                <html>
                <head><title>CAD Files - ${path.basename(dirPath)}</title></head>
                <body>
                    <h1>CAD файлы в папке</h1>
                    <p>Найдено файлов: ${cadFiles.length}</p>
                    <ul>
            `;

            cadFiles.forEach(file => {
                html += `<li><a href="${file}">${file}</a></li>`;
            });

            html += `</ul></body></html>`;

            res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
            res.end(html);
        });
    }

    serveFile(filePath, res) {
        fs.readFile(filePath, (err, data) => {
            if (err) {
                res.writeHead(404);
                res.end('Файл не найден');
                return;
            }

            res.writeHead(200);
            res.end(data);
        });
    }

    createSSHTunnel() {
        console.log('🌐 Создаем SSH туннель через localhost.run...');

        this.sshProcess = spawn('ssh', [
            '-R', `80:localhost:${this.port}`,
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'UserKnownHostsFile=/dev/null',
            '-o', 'LogLevel=ERROR',
            'nokey@localhost.run'
        ], {
            stdio: ['pipe', 'pipe', 'pipe']
        });

        this.sshProcess.stderr.on('data', (data) => {
            const output = data.toString();
            console.log(`SSH: ${output.trim()}`);

            // Ищем URL
            const urlMatch = output.match(/https:\/\/[a-zA-Z0-9\-]+\.localhost\.run/);
            if (urlMatch) {
                this.publicUrl = urlMatch[0];
                console.log(`🎉 Публичный URL: ${this.publicUrl}`);
                console.log('📋 Скопируйте эту ссылку в настройки сайта!');
            }
        });

        this.sshProcess.on('error', (err) => {
            console.log(`❌ Ошибка SSH: ${err.message}`);
        });
    }

    stop() {
        console.log('⏹️ Останавливаем сервер...');

        if (this.server) {
            this.server.close();
        }

        if (this.sshProcess) {
            this.sshProcess.kill();
        }

        console.log('✅ Остановлено');
    }
}

// Запуск
const cadShare = new CADShareTest();

// Обработка Ctrl+C
process.on('SIGINT', () => {
    cadShare.stop();
    process.exit();
});