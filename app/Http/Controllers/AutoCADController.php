<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Business\User as UserService;

class AutoCADController extends Controller
{
    /**
     * Получение пути к Node.js в зависимости от ОС
     */
    private function getNodeJsPath()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return '"C:\Program Files\nodejs\node.exe"';
        } else {
            // Linux/Unix - пробуем разные варианты
            $possiblePaths = ['/usr/bin/node', '/usr/local/bin/node', 'node'];

            foreach ($possiblePaths as $path) {
                if ($path === 'node') {
                    // Проверяем через which
                    $result = shell_exec('which node 2>/dev/null');
                    if (!empty(trim($result))) {
                        return 'node';
                    }
                } else {
                    // Проверяем существование файла
                    if (file_exists($path)) {
                        return $path;
                    }
                }
            }

            // По умолчанию просто node (надеемся что в PATH)
            return 'node';
        }
    }

    /**
     * Проверка доступности Aspose.CAD модуля
     */
    private function checkAsposeCadAvailability()
    {
        $command = sprintf(
            '%s -e "try { require(\'@asposecloud/aspose-cad-cloud\'); console.log(\'OK\'); } catch(e) { process.exit(1); }"',
            $this->getNodeJsPath()
        );

        $output = shell_exec($command . ' 2>/dev/null');
        return trim($output ?? '') === 'OK';
    }

    /**
     * Конвертация DWG в JSON через Node.js процессор
     */
    public function convertToJson(Request $request)
    {
        // Принудительное логирование начала выполнения
        Log::info('AutoCAD: convertToJson START', [
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'request_size' => $request->header('content-length', 'unknown')
        ]);

        try {
            // Валидация запроса
            $request->validate([
                'file' => 'required|file|max:102400', // 100MB
            ]);

            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());

            Log::info('AutoCAD: File info', [
                'filename' => $filename,
                'extension' => $extension,
                'size' => $file->getSize()
            ]);

            // Проверка расширения файла
            if (!in_array($extension, ['dwg', 'dxf'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Поддерживаются только файлы DWG и DXF'
                ], 422);
            }


            // Читаем содержимое файла
            $fileContent = file_get_contents($file->getPathname());

            if ($extension === 'dxf') {
                // Обработка DXF файла
                $result = $this->processDxfFile($filename, $fileContent);
            } else {
                // Обработка DWG файла через Aspose.CAD
                // Сначала проверяем доступность модулей
                if (!$this->checkAsposeCadAvailability()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Конвертация DWG файлов недоступна: не установлены необходимые Node.js модули. Попробуйте загрузить файл в формате DXF или обратитесь к администратору.',
                        'error_type' => 'missing_dependencies',
                        'suggestion' => 'Используйте формат DXF вместо DWG для быстрой обработки.'
                    ], 503);
                }

                $result = $this->processDwgFile($filename, $fileContent);
            }


            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Файл успешно конвертирован в JSON'
            ]);

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка обработки файла', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $request->file('file')?->getClientOriginalName(),
                'line' => $e->getLine(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обработки файла: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обработка DXF файла
     */
    private function processDxfFile($filename, $fileContent)
    {
        // Создаем временный файл
        $tempFile = tempnam(sys_get_temp_dir(), 'dxf_');
        file_put_contents($tempFile, $fileContent);

        try {
            // Запускаем Node.js скрипт для обработки DXF
            $command = sprintf(
                '%s %s %s',
                $this->getNodeJsPath(),
                escapeshellarg(resource_path('js/plugins/autoCAD/process-dxf.js')),
                escapeshellarg($tempFile)
            );

            Log::info('AutoCAD: Executing DXF processing command', [
                'command' => $command,
                'temp_file' => $tempFile,
                'temp_file_exists' => file_exists($tempFile),
                'temp_file_size' => file_exists($tempFile) ? filesize($tempFile) : 0
            ]);

            // Выполняем команду и захватываем STDERR с таймаутом
            // Динамический таймаут в зависимости от размера файла
            $fileSize = filesize($tempFile);
            if ($fileSize > 5 * 1024 * 1024) { // файлы больше 5MB
                set_time_limit(600); // 10 минут для больших файлов
                ini_set('memory_limit', '512M'); // увеличиваем память
            } else {
                set_time_limit(300); // 5 минут для обычных файлов
            }

            $output = '';
            $returnCode = 0;
            exec($command . ' 2>&1', $outputArray, $returnCode);
            $output = implode("\n", $outputArray);

            Log::info('AutoCAD: Node.js script output', [
                'output' => $output,
                'output_length' => strlen($output ?? ''),
                'return_code' => $returnCode,
                'output_lines' => count($outputArray)
            ]);

            if ($returnCode !== 0) {
                throw new \Exception('Node.js скрипт завершился с ошибкой (код: ' . $returnCode . '): ' . $output);
            }

            if (!$output) {
                throw new \Exception('Не удалось выполнить обработку DXF файла - нет вывода от Node.js скрипта');
            }

            // Проверяем на ошибки в выводе
            if (strpos($output, 'Error:') !== false || strpos($output, 'ERROR') !== false) {
                throw new \Exception('Node.js скрипт завершился с ошибкой: ' . $output);
            }

            // Извлекаем JSON из вывода (последние строки после DEBUG сообщений)
            $lines = explode("\n", $output);
            $jsonLines = [];
            $jsonStarted = false;

            foreach ($lines as $line) {
                // Пропускаем DEBUG сообщения в STDERR
                if (str_starts_with($line, 'DEBUG:')) {
                    continue;
                }

                // Начинаем сбор JSON когда встретим {
                if (!$jsonStarted && str_starts_with(trim($line), '{')) {
                    $jsonStarted = true;
                }

                if ($jsonStarted) {
                    $jsonLines[] = $line;
                }
            }

            $jsonOutput = implode("\n", $jsonLines);

            Log::info('AutoCAD: Extracted JSON', [
                'json_output' => $jsonOutput,
                'json_length' => strlen($jsonOutput)
            ]);

            if (empty($jsonOutput)) {
                throw new \Exception('Не удалось извлечь JSON из вывода Node.js. Полный вывод: ' . $output);
            }

            $result = json_decode($jsonOutput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON от Node.js: ' . json_last_error_msg() . '. JSON: ' . $jsonOutput);
            }

            if (!$result || !isset($result['success'])) {
                throw new \Exception('Некорректный формат ответа от Node.js скрипта. Вывод: ' . $output);
            }

            if (!$result['success']) {
                $errorMessage = $result['message'] ?? 'Неизвестная ошибка обработки DXF';
                $debugging = isset($result['debugging']) ? json_encode($result['debugging']) : '';
                throw new \Exception($errorMessage . ($debugging ? ' Debug: ' . $debugging : ''));
            }

            return $result;

        } finally {
            // Удаляем временный файл
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Обработка DWG файла через Aspose.CAD
     */
    private function processDwgFile($filename, $fileContent)
    {
        // Создаем временные файлы
        $tempDwgFile = tempnam(sys_get_temp_dir(), 'dwg_');
        $tempDxfFile = tempnam(sys_get_temp_dir(), 'dxf_');

        file_put_contents($tempDwgFile, $fileContent);

        try {
            // Запускаем Node.js скрипт для конвертации DWG → DXF
            $command = sprintf(
                '%s %s %s %s',
                $this->getNodeJsPath(),
                escapeshellarg(resource_path('js/plugins/autoCAD/convert-dwg.js')),
                escapeshellarg($tempDwgFile),
                escapeshellarg($tempDxfFile)
            );

            Log::info('AutoCAD: Executing DWG conversion command', [
                'command' => $command,
                'dwg_file' => $tempDwgFile,
                'dxf_file' => $tempDxfFile,
                'dwg_size' => filesize($tempDwgFile)
            ]);

            // Устанавливаем таймаут для длительных операций с Aspose API
            set_time_limit(120); // 2 минуты для конвертации DWG

            $startTime = microtime(true);
            exec($command . ' 2>&1', $outputArray, $returnCode);
            $executionTime = microtime(true) - $startTime;

            $output = implode("\n", $outputArray);

            Log::info('AutoCAD: DWG conversion output', [
                'output' => $output,
                'output_length' => strlen($output ?? ''),
                'return_code' => $returnCode,
                'execution_time' => $executionTime . 's'
            ]);

            if ($returnCode !== 0) {
                throw new \Exception("Ошибка выполнения Node.js скрипта конвертации DWG (код: {$returnCode}). Вывод: " . $output);
            }

            if (!$output) {
                throw new \Exception('Не удалось выполнить конвертацию DWG файла - нет вывода от Node.js скрипта');
            }

            // Извлекаем только JSON из вывода (последняя строка)
            $lines = explode("\n", trim($output));
            $jsonLine = end($lines);

            $convertResult = json_decode($jsonLine, true);

            if (!$convertResult || !$convertResult['success']) {
                throw new \Exception($convertResult['message'] ?? 'Ошибка конвертации DWG: ' . $output);
            }

            // Читаем получившийся DXF файл
            if (!file_exists($tempDxfFile)) {
                throw new \Exception('DXF файл не был создан после конвертации');
            }

            $dxfContent = file_get_contents($tempDxfFile);

            // Обрабатываем DXF как обычно
            $result = $this->processDxfFile($filename, $dxfContent);

            // Добавляем информацию о конвертации
            $result['metadata']['convertedFrom'] = 'dwg';
            $result['metadata']['converter'] = 'aspose-cad-cloud';

            return $result;

        } finally {
            // Удаляем временные файлы
            if (file_exists($tempDwgFile)) {
                unlink($tempDwgFile);
            }
            if (file_exists($tempDxfFile)) {
                unlink($tempDxfFile);
            }
        }
    }

    /**
     * Получение списка dwg файлов из локальной папки
     */
    public function getLocalFiles(Request $request)
    {
        try {
            // Используем переданный directory из запроса
            $cadDirectory = $request->get('directory');

            // Если directory не передан, пытаемся получить из настроек пользователя
            if (!$cadDirectory) {
                try {
                    $user = app(UserService::class);
                    $cadDirectory = $user->getSettings('contracts.local_path_to_cad_files');

                } catch (\Exception $e) {
                    Log::error('AutoCAD: Ошибка получения настроек пользователя', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
			
            if (!$cadDirectory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не настроена папка с CAD файлами. Обратитесь к администратору.'
                ], 404);
            }


            // Проверяем тип источника файлов
            if (str_starts_with($cadDirectory, 'https://disk.yandex.ru/')) {
                // Это Yandex.Disk - работаем через API
                $files = $this->parseYandexDiskFolder($cadDirectory);
                $sourceType = 'yandex_disk';
                $directory = $cadDirectory;
            } elseif (str_starts_with($cadDirectory, 'https://drive.google.com/')) {
                // Это Google Drive - работаем через API
                $files = $this->parseGoogleDriveFolder($cadDirectory);
                $sourceType = 'google_drive';
                $directory = $cadDirectory;
            } elseif (str_starts_with($cadDirectory, 'https://my.pcloud.com/') || str_starts_with($cadDirectory, 'https://u.pcloud.link/') || str_starts_with($cadDirectory, 'https://e.pcloud.link/') || str_starts_with($cadDirectory, 'http://e.pc.cd/')) {
                // Это pCloud - работаем через API
                $files = $this->parsePCloudFolder($cadDirectory);
                $sourceType = 'pcloud';
                $directory = $cadDirectory;
            } elseif (preg_match('/^https?:\/\/[\d\.\w\-]+:\d+\/?$/', $cadDirectory) || preg_match('/^https:\/\/[\w\d\-]+\.lhr\.life\/?$/', $cadDirectory)) {
                // Это локальный CAD Share сервер или localhost.run туннель
                $apiUrl = rtrim($cadDirectory, '/') . '/ajax/autocad/files';
                $files = $this->parseLocalCADServer($apiUrl);
                $sourceType = 'local_cad_server';
                $directory = $cadDirectory;
            } elseif (preg_match('/^https?:\/\/[\d\.]+:\d+\/(?!ajax)/', $cadDirectory)) {
                // Это Tiny Web Server или простой HTTP сервер
                $files = $this->parseTinyWebServer($cadDirectory);
                $sourceType = 'tiny_web_server';
                $directory = $cadDirectory;
            } elseif (str_starts_with($cadDirectory, '\\\\') || str_starts_with($cadDirectory, '//')) {
                // Это сетевая папка (UNC path)
                $files = $this->getNetworkShareFiles($cadDirectory);
                $sourceType = 'network_share';
                $directory = $cadDirectory;
            } else {
                // Это локальная папка
                if (!is_dir($cadDirectory)) {
                    // Попытаемся создать папку автоматически
                    if (!mkdir($cadDirectory, 0755, true)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Папка с CAD файлами не найдена и не может быть создана: ' . $cadDirectory
                        ], 404);
                    }

                    Log::info('AutoCAD: Создана папка для CAD файлов', ['path' => $cadDirectory]);
                }

                $files = [];
                $iterator = new \DirectoryIterator($cadDirectory);

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isDot()) continue;

                    $extension = strtolower($fileInfo->getExtension());
                    if (!in_array($extension, ['dwg', 'dxf'])) continue;

                    $filepath = $fileInfo->getPathname();
                    $filesize = $fileInfo->getSize();

                    $files[] = [
                        'name' => $fileInfo->getFilename(),
                        'basename' => $fileInfo->getBasename('.' . $extension),
                        'extension' => $extension,
                        'size' => $filesize,
                        'size_human' => $this->formatFileSize($filesize),
                        'modified' => date('Y-m-d H:i:s', $fileInfo->getMTime()),
                        'path' => $filepath
                    ];
                }

                // Сортировка по имени
                usort($files, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });

                $sourceType = 'local_directory';
                $directory = $cadDirectory;
            }


            return response()->json([
                'success' => true,
                'files' => $files,
                'count' => count($files),
                'directory' => $directory,
                'source_type' => $sourceType
            ]);

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка получения списка файлов', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения списка файлов: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Конвертация файла из локальной папки
     */
    public function convertLocalFile(Request $request)
    {
        try {
            $request->validate([
                'filename' => 'required|string'
            ]);
			$user = app(UserService::class);
            $filename = $request->input('filename');
            $cadDirectory = $user->getSettings('contracts.local_path_to_cad_files');

			
            if (!$cadDirectory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не настроена папка с CAD файлами. Обратитесь к администратору.'
                ], 404);
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($extension, ['dwg', 'dxf'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Поддерживаются только файлы DWG и DXF'
                ], 422);
            }


            // Проверяем тип источника и получаем содержимое файла
            $sourceType = $this->detectSourceType($cadDirectory);


            switch ($sourceType) {
                case 'yandex_disk':
                    // Это Yandex.Disk - скачиваем файл
                    $fileContent = $this->downloadFileFromYandexDisk($cadDirectory, $filename);
                    if (!$fileContent) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Не удалось скачать файл из Yandex.Disk: ' . $filename
                        ], 404);
                    }
                    break;

                case 'google_drive':
                    // Это Google Drive - скачиваем файл
                    $fileContent = $this->downloadFileFromGoogleDrive($cadDirectory, $filename);
                    if (!$fileContent) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Не удалось скачать файл из Google Drive: ' . $filename
                        ], 404);
                    }
                    break;

                case 'pcloud':
                    // Это pCloud - скачиваем файл
                    $fileContent = $this->downloadFileFromPCloud($cadDirectory, $filename);
                    if (!$fileContent) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Не удалось скачать файл из pCloud: ' . $filename
                        ], 404);
                    }
                    break;

                case 'local_cad_server':
                    // Это локальный CAD Share сервер - скачиваем файл
                    $apiUrl = rtrim($cadDirectory, '/') . '/ajax/autocad/files';
                    $files = $this->parseLocalCADServer($apiUrl);
                    $targetFile = null;

                    foreach ($files as $file) {
                        if ($file['name'] === $filename) {
                            $targetFile = $file;
                            break;
                        }
                    }

                    if (!$targetFile) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Файл не найден на локальном сервере: ' . $filename
                        ], 404);
                    }

                    $downloadUrl = rtrim($cadDirectory, '/') . '/' . $targetFile['download_url'];
                    $fileContent = $this->downloadFileFromLocalCADServer($filename, $downloadUrl);
                    if (!$fileContent) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Не удалось скачать файл с локального сервера: ' . $filename
                        ], 404);
                    }
                    break;

                case 'tiny_web_server':
                    // Это Tiny Web Server - скачиваем файл
                    $files = $this->parseTinyWebServer($cadDirectory);
                    $targetFile = null;

                    foreach ($files as $file) {
                        if ($file['name'] === $filename) {
                            $targetFile = $file;
                            break;
                        }
                    }

                    if (!$targetFile) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Файл не найден на Tiny Web Server: ' . $filename
                        ], 404);
                    }

                    $fileContent = $this->downloadFileFromTinyWebServer($filename, $targetFile['download_url']);
                    if (!$fileContent) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Не удалось скачать файл с Tiny Web Server: ' . $filename
                        ], 404);
                    }
                    break;

                case 'network_share':
                    // Это сетевая папка - читаем файл напрямую
                    $normalizedPath = str_replace('/', '\\', $cadDirectory);
                    $filepath = $normalizedPath . '\\' . $filename;

                    if (!file_exists($filepath)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Файл не найден в сетевой папке: ' . $filename
                        ], 404);
                    }

                    $fileContent = file_get_contents($filepath);
                    if ($fileContent === false) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Не удалось прочитать файл из сетевой папки: ' . $filename
                        ], 500);
                    }
                    break;

                default:
                    // Это локальная папка
                    $filepath = $cadDirectory . DIRECTORY_SEPARATOR . $filename;

                    // Проверка безопасности пути
                    if (!str_starts_with(realpath($filepath), realpath($cadDirectory))) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Недопустимый путь к файлу'
                        ], 403);
                    }

                    if (!file_exists($filepath)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Файл не найден: ' . $filename
                        ], 404);
                    }

                    $fileContent = file_get_contents($filepath);
                    if ($fileContent === false) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Не удалось прочитать файл: ' . $filename
                        ], 500);
                    }
                    break;
            }

            // Если мы дошли до этого места, файл успешно загружен
            if (empty($fileContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пустой файл или ошибка загрузки: ' . $filename
                ], 400);
            }

            $processStartTime = microtime(true);
            if ($extension === 'dxf') {
                $result = $this->processDxfFile($filename, $fileContent);
            } else {
                $result = $this->processDwgFile($filename, $fileContent);
            }
            $processTime = microtime(true) - $processStartTime;


            // Ограничиваем размер данных для JSON ответа - сохраняем исходную структуру
            $responseData = $result;

            // Ограничиваем количество entities
            if (isset($result['entities']) && count($result['entities']) > 10) {
                $responseData['entities'] = array_slice($result['entities'], 0, 10); // Только первые 10 объектов
                $responseData['metadata']['entities_truncated'] = true;
                $responseData['metadata']['total_entities'] = count($result['entities']);
            }

            // Удаляем массивные данные которые могут быть слишком большими
            if (isset($responseData['entitiesByType'])) {
                unset($responseData['entitiesByType']); // Это дублирует entities
            }

            // Ограничиваем длину JSON до 1MB
            $jsonString = json_encode($responseData);
            if (strlen($jsonString) > 1024 * 1024) { // 1MB
                // Еще больше урезаем данные
                $responseData['entities'] = array_slice($responseData['entities'], 0, 5);
                $responseData['metadata']['entities_truncated'] = true;
                $responseData['metadata']['response_size_limited'] = true;

                // Убираем детальные данные
                foreach ($responseData['entities'] as &$entity) {
                    if (isset($entity['vertices']) && count($entity['vertices']) > 20) {
                        $entity['vertices'] = array_slice($entity['vertices'], 0, 20);
                        $entity['vertices_truncated'] = true;
                    }
                }
            }


            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Файл успешно конвертирован в JSON'
            ]);

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка обработки локального файла', [
                'error' => $e->getMessage(),
                'filename' => $request->input('filename')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обработки файла: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Парсинг файлов из Yandex.Disk публичной папки
     */
    private function parseYandexDiskFolder($publicUrl)
    {
        try {
            // Попробуем веб-скрапинг для обхода API кеша
            return $this->scrapeYandexDiskPage($publicUrl);
        } catch (\Exception $e) {
            Log::warning('AutoCAD: Веб-скрапинг не удался, используем API', [
                'error' => $e->getMessage()
            ]);

            // Fallback на API с анти-кеш мерами
            return $this->parseYandexDiskAPI($publicUrl);
        }
    }

    private function scrapeYandexDiskPage($publicUrl)
    {
        // Используем прямую ссылку с антикеш параметрами
        $webUrl = $publicUrl . '?_t=' . time() . '&_r=' . mt_rand(1000, 9999);

        Log::info('AutoCAD: Веб-скрапинг Yandex.Disk', [
            'original_url' => $publicUrl,
            'scrape_url' => $webUrl
        ]);

        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
            'Cache-Control: no-cache, no-store, must-revalidate',
            'Pragma: no-cache',
            'Expires: 0'
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers) . "\r\n",
                'timeout' => 30,
                'follow_location' => true,
                'max_redirects' => 5
            ]
        ]);

        $html = file_get_contents($webUrl, false, $context);

        if ($html === false) {
            throw new \Exception('Не удалось получить HTML страницу: ' . error_get_last()['message']);
        }

        Log::info('AutoCAD: HTML получен', [
            'html_size' => strlen($html),
            'contains_data' => strpos($html, '__DATA__') !== false,
            'contains_resources' => strpos($html, 'resources') !== false
        ]);

        // Ищем JSON данные в HTML
        $files = [];

        // Основной паттерн для window.__DATA__
        if (preg_match('/window\.__DATA__\s*=\s*({.+?});/s', $html, $matches)) {
            Log::info('AutoCAD: Найден __DATA__', ['data_length' => strlen($matches[1])]);

            $jsonData = json_decode($matches[1], true);
            if ($jsonData && isset($jsonData['resources'])) {
                foreach ($jsonData['resources'] as $resource) {
                    if ($resource['type'] === 'file') {
                        $extension = strtolower(pathinfo($resource['name'], PATHINFO_EXTENSION));
                        if (in_array($extension, ['dwg', 'dxf'])) {
                            $files[] = [
                                'name' => $resource['name'],
                                'basename' => pathinfo($resource['name'], PATHINFO_FILENAME),
                                'extension' => $extension,
                                'size' => $resource['size'],
                                'size_human' => $this->formatFileSize($resource['size']),
                                'modified' => $resource['mtime'] ?? null,
                                'download_url' => $resource['download_url'] ?? null,
                                'path' => $publicUrl
                            ];
                        }
                    }
                }
            }
        }

        // Альтернативный поиск данных в скриптах
        if (empty($files)) {
            preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $html, $scripts);
            foreach ($scripts[1] as $script) {
                if (strpos($script, 'resources') !== false && strpos($script, '"type":"file"') !== false) {
                    // Ищем JSON объекты в скрипте
                    if (preg_match('/\{[^}]*"resources"[^}]*\[[^\]]*\][^}]*\}/', $script, $jsonMatch)) {
                        $jsonData = json_decode($jsonMatch[0], true);
                        if ($jsonData && isset($jsonData['resources'])) {
                            foreach ($jsonData['resources'] as $resource) {
                                if ($resource['type'] === 'file') {
                                    $extension = strtolower(pathinfo($resource['name'], PATHINFO_EXTENSION));
                                    if (in_array($extension, ['dwg', 'dxf'])) {
                                        $files[] = [
                                            'name' => $resource['name'],
                                            'basename' => pathinfo($resource['name'], PATHINFO_FILENAME),
                                            'extension' => $extension,
                                            'size' => $resource['size'],
                                            'size_human' => $this->formatFileSize($resource['size']),
                                            'modified' => $resource['mtime'] ?? null,
                                            'download_url' => $resource['download_url'] ?? null,
                                            'path' => $publicUrl
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        Log::info('AutoCAD: Результат веб-скрапинга', [
            'files_found' => count($files),
            'html_size' => strlen($html)
        ]);

        if (empty($files)) {
            throw new \Exception('Не удалось найти файлы через веб-скрапинг');
        }

        // Сортировка по имени
        usort($files, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $files;
    }

    private function parseYandexDiskAPI($publicUrl)
    {
        // Более агрессивные антикеш параметры
        $timestamp = microtime(true); // Используем микросекунды
        $random1 = mt_rand(10000, 99999);
        $random2 = uniqid();
        $apiUrl = 'https://cloud-api.yandex.net/v1/disk/public/resources?public_key=' . urlencode($publicUrl)
                . '&force_async=true'
                . '&_t=' . $timestamp
                . '&_r1=' . $random1
                . '&_r2=' . $random2
                . '&_cache_bust=' . time();

        // Радикальные меры против кеша
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) Firefox/120.0',
            'AutoCAD-API-Client/' . time(),
            'YandexDisk-PHP-Client/2.0 (no-cache)'
        ];

        $randomUA = $userAgents[array_rand($userAgents)];

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => $randomUA,
                'header' => [
                    'Cache-Control: no-cache, no-store, must-revalidate, max-age=0, proxy-revalidate',
                    'Pragma: no-cache',
                    'Expires: 0',
                    'If-Modified-Since: Thu, 01 Jan 1970 00:00:00 GMT',
                    'If-None-Match: "never-match-' . time() . '"',
                    'Accept: application/json, */*',
                    'Accept-Encoding: identity',
                    'Connection: close',
                    'X-Requested-With: XMLHttpRequest',
                    'X-Force-Refresh: ' . time(),
                    'X-No-Cache: 1'
                ]
            ]
        ]);

        // Попробуем через cURL для большего контроля
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => $randomUA,
                CURLOPT_HTTPHEADER => [
                    'Cache-Control: no-cache, no-store, must-revalidate, max-age=0',
                    'Pragma: no-cache',
                    'Expires: 0',
                    'If-Modified-Since: Thu, 01 Jan 1970 00:00:00 GMT',
                    'If-None-Match: "never-match-' . time() . '"',
                    'Accept: application/json, */*',
                    'Connection: close',
                    'X-Force-Refresh: ' . time()
                ],
                CURLOPT_FRESH_CONNECT => true, // Принудительное новое соединение
                CURLOPT_FORBID_REUSE => true,  // Запрет переиспользования соединения
                CURLOPT_DNS_CACHE_TIMEOUT => 0, // Отключаем DNS кеш
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $httpCode !== 200) {
                throw new \Exception('cURL запрос не удался. HTTP код: ' . $httpCode);
            }
        } else {
            // Fallback на file_get_contents
            $response = file_get_contents($apiUrl, false, $context);
            if (!$response) {
                throw new \Exception('Не удалось получить данные из Yandex.Disk');
            }
        }

        $data = json_decode($response, true);

        // DEBUG: Логируем ответ от Yandex.Disk API
        Log::info('AutoCAD: Ответ Yandex.Disk API', [
            'url' => $apiUrl,
            'items_count' => isset($data['_embedded']['items']) ? count($data['_embedded']['items']) : 0,
            'response_size' => strlen($response),
            'response_sample' => substr($response, 0, 200), // Первые 200 символов ответа
            'has_embedded' => isset($data['_embedded']),
            'has_items' => isset($data['_embedded']['items']),
            'api_timestamp' => time()
        ]);

        if (!$data || !isset($data['_embedded']['items'])) {
            throw new \Exception('Неверный формат ответа от Yandex.Disk API');
        }

        $files = [];
        foreach ($data['_embedded']['items'] as $item) {
            // Пропускаем папки
            if ($item['type'] !== 'file') {
                continue;
            }

            $extension = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));

            // Фильтруем только CAD файлы
            if (!in_array($extension, ['dwg', 'dxf'])) {
                continue;
            }

            $files[] = [
                'name' => $item['name'],
                'basename' => pathinfo($item['name'], PATHINFO_FILENAME),
                'extension' => $extension,
                'size' => $item['size'],
                'size_human' => $this->formatFileSize($item['size']),
                'modified' => $item['modified'],
                'download_url' => $item['file'], // Прямая ссылка на скачивание
                'path' => $publicUrl // Сохраняем исходную ссылку
            ];
        }

        // Сортировка по имени
        usort($files, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $files;
    }

    /**
     * Скачивание файла из Yandex.Disk
     */
    private function downloadFileFromYandexDisk($publicUrl, $filename)
    {
        try {
            // Сначала получаем список файлов чтобы найти прямую ссылку на скачивание
            $files = $this->parseYandexDiskFolder($publicUrl);

            $downloadUrl = null;
            foreach ($files as $file) {
                if ($file['name'] === $filename) {
                    $downloadUrl = $file['download_url'];
                    break;
                }
            }

            if (!$downloadUrl) {
                throw new \Exception('Файл не найден в Yandex.Disk: ' . $filename);
            }

            // Скачиваем файл по прямой ссылке с антикеш параметрами
            $downloadUrlWithCache = $downloadUrl . (strpos($downloadUrl, '?') !== false ? '&' : '?') . '_t=' . time();

            $context = stream_context_create([
                'http' => [
                    'timeout' => 60, // Увеличенный таймаут для больших файлов
                    'user_agent' => 'AutoCAD Converter/1.0',
                    'header' => [
                        'Cache-Control: no-cache, no-store, must-revalidate',
                        'Pragma: no-cache',
                        'Expires: 0'
                    ]
                ]
            ]);

            $fileContent = file_get_contents($downloadUrlWithCache, false, $context);

            if (!$fileContent) {
                throw new \Exception('Не удалось скачать файл с Yandex.Disk');
            }

            Log::info('AutoCAD: Файл успешно скачан с Yandex.Disk', [
                'filename' => $filename,
                'size' => strlen($fileContent)
            ]);

            return $fileContent;

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка скачивания файла с Yandex.Disk', [
                'filename' => $filename,
                'url' => $publicUrl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Форматирование размера файла
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * Проверка квоты API
     */
    public function checkApiQuota()
    {
        try {
            // Запускаем Node.js скрипт для проверки квоты
            $command = sprintf(
                '%s %s',
                $this->getNodeJsPath(),
                escapeshellarg(resource_path('js/plugins/autoCAD/check-quota.js'))
            );

            $errorRedirect = (PHP_OS_FAMILY === 'Windows') ? '2>nul' : '2>/dev/null';
            $output = shell_exec($command . ' ' . $errorRedirect);
            $result = json_decode($output, true);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'available' => false,
                'message' => 'Ошибка проверки API: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Принудительное обновление кеша файлов
     */
    public function forceRefreshCache()
    {
        try {
            $user = app(UserService::class);
            $cadDirectory = $user->getSettings('contracts.local_path_to_cad_files');

            if (!$cadDirectory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не настроена папка с CAD файлами. Обратитесь к администратору.'
                ], 404);
            }

            Log::info('AutoCAD: Принудительное обновление кеша', [
                'directory' => $cadDirectory,
                'timestamp' => time()
            ]);

            // Принудительный сброс внутреннего кеша приложения (если есть)
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            // Очистка PHP file cache
            clearstatcache(true);

            // Попытка принудительного запроса с максимальными анти-кеш мерами
            if (str_starts_with($cadDirectory, 'https://disk.yandex.ru/')) {
                // Используем timestamp в качестве cache buster
                $forceTimestamp = time() * 1000 + rand(1, 999);

                // Ждем секунду чтобы timestamp точно изменился
                sleep(1);

                $files = $this->parseYandexDiskAPI($cadDirectory . '?force_refresh=' . $forceTimestamp);
            } else {
                $files = $this->getLocalDirectoryFiles($cadDirectory);
            }

            return response()->json([
                'success' => true,
                'files' => $files,
                'message' => 'Кеш успешно сброшен. Если файлы не обновились - это ограничение Yandex.Disk API.',
                'count' => count($files),
                'cache_cleared_at' => date('Y-m-d H:i:s'),
                'note' => 'Yandex.Disk API может кешировать результаты до нескольких часов.'
            ]);

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка принудительного обновления кеша', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка сброса кеша: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Определение типа источника файлов
     */
    private function detectSourceType($path)
    {
        if (str_starts_with($path, 'https://disk.yandex.ru/')) {
            return 'yandex_disk';
        }

        if (str_starts_with($path, 'https://drive.google.com/')) {
            return 'google_drive';
        }

        if (str_starts_with($path, 'https://my.pcloud.com/') || str_starts_with($path, 'https://u.pcloud.link/')) {
            return 'pcloud';
        }

        if (preg_match('/^https?:\/\/[\d\.\w\-]+:\d+\/?$/', $path) || preg_match('/^https:\/\/[\w\d\-]+\.lhr\.life\/?$/', $path)) {
            return 'local_cad_server';
        }

        if (preg_match('/^https?:\/\/[\d\.]+:\d+\/(?!ajax)/', $path)) {
            return 'tiny_web_server';
        }

        if (str_starts_with($path, '\\\\') || str_starts_with($path, '//')) {
            return 'network_share';
        }

        return 'local_directory';
    }

    /**
     * Получение файлов из сетевой папки (UNC path)
     */
    private function getNetworkShareFiles($uncPath)
    {
        try {
            Log::info('AutoCAD: Доступ к сетевой папке', [
                'unc_path' => $uncPath
            ]);

            // Нормализуем путь для Windows
            $normalizedPath = str_replace('/', '\\', $uncPath);

            if (!is_dir($normalizedPath)) {
                throw new \Exception('Сетевая папка не найдена или недоступна: ' . $uncPath);
            }

            $files = [];
            $handle = opendir($normalizedPath);

            if (!$handle) {
                throw new \Exception('Не удалось открыть сетевую папку: ' . $uncPath);
            }

            while (($filename = readdir($handle)) !== false) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }

                $fullPath = $normalizedPath . '\\' . $filename;

                if (!is_file($fullPath)) {
                    continue;
                }

                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!in_array($extension, ['dwg', 'dxf'])) {
                    continue;
                }

                $fileSize = filesize($fullPath);
                $lastModified = filemtime($fullPath);

                $files[] = [
                    'name' => $filename,
                    'basename' => pathinfo($filename, PATHINFO_FILENAME),
                    'extension' => $extension,
                    'size' => $fileSize,
                    'size_human' => $this->formatFileSize($fileSize),
                    'modified' => date('c', $lastModified),
                    'path' => $fullPath
                ];
            }

            closedir($handle);

            // Сортировка по имени
            usort($files, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            Log::info('AutoCAD: Сетевая папка просканирована', [
                'files_count' => count($files),
                'unc_path' => $uncPath
            ]);

            return $files;

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка доступа к сетевой папке', [
                'unc_path' => $uncPath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получение файлов из Google Drive публичной папки
     */
    private function parseGoogleDriveFolder($driveUrl)
    {
        try {
            // Извлекаем ID папки из Google Drive URL
            $folderId = $this->extractGoogleDriveFolderId($driveUrl);

            Log::info('AutoCAD: Запрос к Google Drive API', [
                'url' => $driveUrl,
                'folder_id' => $folderId
            ]);

            // Получаем API ключ из настроек
            $user = app(UserService::class);
            $apiKey = $user->getSettings('contracts.google_drive_api_key');

            if (!$apiKey) {
                throw new \Exception('Не настроен Google Drive API ключ. Обратитесь к администратору.');
            }

            // Формируем запрос к Google Drive API
            $apiUrl = 'https://www.googleapis.com/drive/v3/files'
                    . '?q=' . urlencode("'{$folderId}' in parents")
                    . '&key=' . $apiKey
                    . '&fields=files(id,name,size,modifiedTime,mimeType)';

            $headers = [
                'Accept: application/json',
                'User-Agent: AutoCAD-Converter/1.0'
            ];

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers) . "\r\n",
                    'timeout' => 30
                ]
            ]);

            $response = file_get_contents($apiUrl, false, $context);

            if ($response === false) {
                throw new \Exception('Не удалось получить данные от Google Drive API');
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Неверный формат ответа от Google Drive API');
            }

            if (isset($data['error'])) {
                throw new \Exception('Ошибка Google Drive API: ' . $data['error']['message']);
            }

            if (!isset($data['files'])) {
                return [];
            }

            $files = [];
            foreach ($data['files'] as $item) {
                // Пропускаем папки - обрабатываем только файлы
                if (strpos($item['mimeType'], 'folder') !== false) {
                    continue;
                }

                $filename = $item['name'];
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                // Фильтруем только CAD файлы
                if (!in_array($extension, ['dwg', 'dxf'])) {
                    continue;
                }

                $files[] = [
                    'name' => $filename,
                    'basename' => pathinfo($filename, PATHINFO_FILENAME),
                    'extension' => $extension,
                    'size' => isset($item['size']) ? (int)$item['size'] : 0,
                    'size_human' => isset($item['size']) ? $this->formatFileSize((int)$item['size']) : 'N/A',
                    'modified' => $item['modifiedTime'] ?? null,
                    'download_url' => 'https://www.googleapis.com/drive/v3/files/' . $item['id'] . '?alt=media&key=' . $apiKey,
                    'google_file_id' => $item['id'],
                    'path' => $driveUrl
                ];
            }

            // Сортировка по имени
            usort($files, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            Log::info('AutoCAD: Google Drive папка просканирована', [
                'files_count' => count($files),
                'folder_id' => $folderId
            ]);

            return $files;

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка доступа к Google Drive', [
                'url' => $driveUrl,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Извлечение ID папки из Google Drive URL
     */
    private function extractGoogleDriveFolderId($url)
    {
        // Поддерживаемые форматы URL:
        // https://drive.google.com/drive/folders/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OlympiadG
        // https://drive.google.com/drive/u/0/folders/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OlympiadG

        if (preg_match('/\/folders\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        throw new \Exception('Не удалось извлечь ID папки из Google Drive URL: ' . $url);
    }

    /**
     * Скачивание файла из Google Drive
     */
    private function downloadFileFromGoogleDrive($driveUrl, $filename)
    {
        try {
            // Сначала получаем список файлов чтобы найти нужный файл
            $files = $this->parseGoogleDriveFolder($driveUrl);
            $downloadUrl = null;

            foreach ($files as $file) {
                if ($file['name'] === $filename) {
                    $downloadUrl = $file['download_url'];
                    break;
                }
            }

            if (!$downloadUrl) {
                throw new \Exception('Файл не найден в Google Drive: ' . $filename);
            }

            Log::info('AutoCAD: Скачивание файла из Google Drive', [
                'filename' => $filename,
                'download_url' => $downloadUrl
            ]);

            // Скачиваем файл по прямой ссылке
            $context = stream_context_create([
                'http' => [
                    'timeout' => 60,
                    'user_agent' => 'AutoCAD-Converter/1.0',
                    'header' => [
                        'Accept: application/octet-stream'
                    ]
                ]
            ]);

            $fileContent = file_get_contents($downloadUrl, false, $context);

            if ($fileContent === false) {
                throw new \Exception('Не удалось скачать файл с Google Drive');
            }

            Log::info('AutoCAD: Файл успешно скачан с Google Drive', [
                'filename' => $filename,
                'size' => strlen($fileContent)
            ]);

            return $fileContent;

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка скачивания с Google Drive', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получение файлов из pCloud публичной папки
     */
    private function parsePCloudFolder($pcloudUrl)
    {
        try {
            // Извлекаем код публичной ссылки из URL
            $linkCode = $this->extractPCloudLinkCode($pcloudUrl);

            Log::info('AutoCAD: Запрос к pCloud API', [
                'url' => $pcloudUrl,
                'link_code' => $linkCode
            ]);

            // Используем европейский API pCloud для получения содержимого папки
            // API endpoint для публичных ссылок: https://eapi.pcloud.com/showpublink
            $apiUrl = 'https://eapi.pcloud.com/showpublink?code=' . urlencode($linkCode);

            $headers = [
                'Accept: application/json',
                'User-Agent: AutoCAD-Converter/1.0'
            ];

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers) . "\r\n",
                    'timeout' => 30
                ]
            ]);

            $response = file_get_contents($apiUrl, false, $context);

            if ($response === false) {
                throw new \Exception('Не удалось получить данные от pCloud API');
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Неверный формат ответа от pCloud API');
            }

            if (isset($data['error'])) {
                throw new \Exception('Ошибка pCloud API: ' . $data['error']);
            }

            if (!isset($data['metadata']['contents'])) {
                return [];
            }

            $files = [];
            foreach ($data['metadata']['contents'] as $item) {
                // Пропускаем папки - обрабатываем только файлы
                if ($item['isfolder']) {
                    continue;
                }

                $filename = $item['name'];
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                // Фильтруем только CAD файлы
                if (!in_array($extension, ['dwg', 'dxf'])) {
                    continue;
                }

                // Формируем прямую ссылку для скачивания
                $downloadUrl = 'https://api.pcloud.com/getpubfile?code=' . urlencode($linkCode) . '&fileid=' . $item['fileid'];

                $files[] = [
                    'name' => $filename,
                    'basename' => pathinfo($filename, PATHINFO_FILENAME),
                    'extension' => $extension,
                    'size' => isset($item['size']) ? (int)$item['size'] : 0,
                    'size_human' => isset($item['size']) ? $this->formatFileSize((int)$item['size']) : 'N/A',
                    'modified' => isset($item['modified']) ? date('c', strtotime($item['modified'])) : null,
                    'download_url' => $downloadUrl,
                    'pcloud_file_id' => $item['fileid'],
                    'path' => $pcloudUrl
                ];
            }

            // Сортировка по имени
            usort($files, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            Log::info('AutoCAD: pCloud папка просканирована', [
                'files_count' => count($files),
                'link_code' => $linkCode
            ]);

            return $files;

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка доступа к pCloud', [
                'url' => $pcloudUrl,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Извлечение кода публичной ссылки из pCloud URL
     */
    private function extractPCloudLinkCode($url)
    {
        // Поддерживаемые форматы URL:
        // https://my.pcloud.com/#page=publink&code=XYZ
        // https://u.pcloud.link/publink/show?code=XYZ
        // https://e.pcloud.link/publink/show?code=XYZ
        // http://e.pc.cd/shortCode

        // Для коротких ссылок http://e.pc.cd/CODE
        if (preg_match('/e\.pc\.cd\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Для обычных ссылок с параметром code
        if (preg_match('/[?&]code=([^&]+)/', $url, $matches)) {
            return $matches[1];
        }

        throw new \Exception('Не удалось извлечь код публичной ссылки из pCloud URL: ' . $url);
    }

    /**
     * Скачивание файла из pCloud
     */
    private function downloadFileFromPCloud($pcloudUrl, $filename)
    {
        try {
            // Сначала получаем список файлов чтобы найти нужный файл
            $files = $this->parsePCloudFolder($pcloudUrl);
            $downloadUrl = null;

            foreach ($files as $file) {
                if ($file['name'] === $filename) {
                    $downloadUrl = $file['download_url'];
                    break;
                }
            }

            if (!$downloadUrl) {
                throw new \Exception('Файл не найден в pCloud: ' . $filename);
            }

            Log::info('AutoCAD: Скачивание файла из pCloud', [
                'filename' => $filename,
                'download_url' => $downloadUrl
            ]);

            // Скачиваем файл по прямой ссылке
            $context = stream_context_create([
                'http' => [
                    'timeout' => 60,
                    'user_agent' => 'AutoCAD-Converter/1.0',
                    'header' => [
                        'Accept: application/octet-stream'
                    ]
                ]
            ]);

            $fileContent = file_get_contents($downloadUrl, false, $context);

            if ($fileContent === false) {
                throw new \Exception('Не удалось скачать файл с pCloud');
            }

            Log::info('AutoCAD: Файл успешно скачан с pCloud', [
                'filename' => $filename,
                'size' => strlen($fileContent)
            ]);

            return $fileContent;

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка скачивания с pCloud', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получение списка файлов с локального CAD Share сервера
     */
    private function parseLocalCADServer($apiUrl)
    {
        try {
            Log::info('AutoCAD: Запрос к локальному CAD серверу', [
                'url' => $apiUrl
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Accept: application/json',
                        'User-Agent: AutoCAD-Converter/1.0'
                    ],
                    'timeout' => 10
                ]
            ]);

            $response = file_get_contents($apiUrl, false, $context);

            if ($response === false) {
                throw new \Exception('Не удалось получить данные от локального сервера');
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Неверный формат ответа от локального сервера');
            }

            if (!$data['success']) {
                throw new \Exception('Ошибка локального сервера: ' . ($data['message'] ?? 'Unknown error'));
            }

            Log::info('AutoCAD: Локальный CAD сервер ответил', [
                'files_count' => $data['count'] ?? 0,
                'source_type' => $data['source_type'] ?? 'unknown'
            ]);

            // Возвращаем файлы в том же формате что и другие источники
            return $data['files'] ?? [];

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка доступа к локальному CAD серверу', [
                'url' => $apiUrl,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Скачивание файла с локального CAD сервера
     */
    private function downloadFileFromLocalCADServer($filename, $downloadUrl)
    {
        try {
            Log::info('AutoCAD: Скачивание файла с локального CAD сервера', [
                'filename' => $filename,
                'url' => $downloadUrl
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 30
                ]
            ]);

            $fileContent = file_get_contents($downloadUrl, false, $context);

            if ($fileContent === false) {
                throw new \Exception('Не удалось скачать файл с локального сервера');
            }

            Log::info('AutoCAD: Файл успешно скачан с локального CAD сервера', [
                'filename' => $filename,
                'size' => strlen($fileContent)
            ]);

            return $fileContent;

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка скачивания с локального CAD сервера', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получение списка файлов с Tiny Web Server
     */
    private function parseTinyWebServer($baseUrl)
    {
        try {
            // Убираем завершающий слеш если есть
            $baseUrl = rtrim($baseUrl, '/');


            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Accept: text/html',
                        'User-Agent: AutoCAD-Converter/1.0'
                    ],
                    'timeout' => 10
                ]
            ]);

            $html = file_get_contents($baseUrl, false, $context);

            if ($html === false) {
                throw new \Exception('Не удалось получить данные от Tiny Web Server');
            }

            // Парсим HTML для поиска ссылок на CAD файлы
            $files = [];
            preg_match_all('/<a[^>]+href="([^"]+\.(?:dwg|dxf))"[^>]*>([^<]+)<\/a>/i', $html, $matches);


            if (!empty($matches[1])) {
                foreach ($matches[1] as $index => $href) {
                    $filename = basename($href);
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    // Пропускаем, если не CAD файл
                    if (!in_array($extension, ['dwg', 'dxf'])) {
                        continue;
                    }

                    // Формируем полную ссылку для скачивания
                    if (str_starts_with($href, 'http')) {
                        $downloadUrl = $href;
                    } else {
                        $downloadUrl = $baseUrl . '/' . ltrim($href, '/');
                    }

                    // Пытаемся получить размер файла через HEAD запрос
                    $fileSize = $this->getFileSize($downloadUrl);

                    $files[] = [
                        'name' => $filename,
                        'basename' => pathinfo($filename, PATHINFO_FILENAME),
                        'extension' => $extension,
                        'size' => $fileSize,
                        'size_human' => $this->formatFileSize($fileSize),
                        'modified' => null, // HTML не содержит даты изменения
                        'download_url' => $downloadUrl,
                        'path' => $baseUrl
                    ];
                }
            }


            return $files;

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка доступа к Tiny Web Server', [
                'url' => $baseUrl,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Получение размера файла через HEAD запрос
     */
    private function getFileSize($url)
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 5
                ]
            ]);

            $headers = get_headers($url, 1, $context);

            if ($headers && isset($headers['Content-Length'])) {
                return (int)$headers['Content-Length'];
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки получения размера
        }

        return 0;
    }

    /**
     * Скачивание файла с Tiny Web Server
     */
    private function downloadFileFromTinyWebServer($filename, $downloadUrl)
    {
        try {

            // Используем cURL для быстрого скачивания
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $downloadUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_USERAGENT => 'AutoCAD-Converter/1.0',
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $fileContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($fileContent === false || $httpCode !== 200) {
                throw new \Exception("Не удалось скачать файл с Tiny Web Server. HTTP код: {$httpCode}");
            }


            return $fileContent;

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка скачивания с Tiny Web Server', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Диагностика работы Node.js и зависимостей
     */
    public function testNodeJs(Request $request)
    {
        try {
            $results = [
                'environment' => [],
                'node_test' => [],
                'dependencies' => [],
                'scripts' => []
            ];

            // 1. Проверка окружения
            $results['environment']['php_version'] = phpversion();
            $results['environment']['os'] = php_uname();
            $results['environment']['temp_dir'] = sys_get_temp_dir();

            // 2. Тест Node.js
            $nodeCommand = $this->getNodeJsPath() . ' --version';
            $nodeVersion = shell_exec($nodeCommand . ' 2>&1');
            $results['node_test']['version_command'] = $nodeCommand;
            $results['node_test']['version_output'] = trim($nodeVersion ?? 'No output');
            $results['node_test']['version_available'] = !empty($nodeVersion) && strpos($nodeVersion, 'v') === 0;

            // 3. Тест npm зависимостей
            $checkDeps = [
                'dxf-parser' => 'require("dxf-parser")',
                'aspose-cad' => 'require("@asposecloud/aspose-cad-cloud")'
            ];

            foreach ($checkDeps as $name => $requireCode) {
                $testCommand = sprintf(
                    '%s -e "%s; console.log(\'OK\')"',
                    $this->getNodeJsPath(),
                    addslashes($requireCode)
                );

                $testOutput = shell_exec($testCommand . ' 2>&1');
                $results['dependencies'][$name] = [
                    'command' => $testCommand,
                    'output' => trim($testOutput ?? 'No output'),
                    'available' => strpos($testOutput ?? '', 'OK') !== false
                ];
            }

            // 4. Тест скриптов
            $scripts = [
                'process-dxf.js' => resource_path('js/plugins/autoCAD/process-dxf.js'),
                'convert-dwg.js' => resource_path('js/plugins/autoCAD/convert-dwg.js')
            ];

            foreach ($scripts as $name => $path) {
                $results['scripts'][$name] = [
                    'path' => $path,
                    'exists' => file_exists($path),
                    'readable' => is_readable($path),
                    'size' => file_exists($path) ? filesize($path) : 0
                ];
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}