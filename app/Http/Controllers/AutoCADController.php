<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Exception;

class AutoCADController extends Controller
{
    private $cloudConvertApiKey;
    private $cloudConvertApiUrl = 'https://api.cloudconvert.com/v2/';

    public function __construct()
    {
        $this->cloudConvertApiKey = env('CLOUDCONVERT_API_KEY');
    }

    public function convertToJson(Request $request): JsonResponse
    {
        \Log::info('🚀 НОВЫЙ AutoCAD контроллер запущен!', [
            'timestamp' => now(),
            'method' => 'convertToJson',
            'request_data' => $request->all()
        ]);

        // Тестируем базовый ответ
        if (!$request->hasFile('file')) {
            \Log::error('❌ Файл не найден в запросе');
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден в запросе'
            ], 400);
        }

        try {
            $request->validate([
                'file' => 'required|file|max:102400',
                'filename' => 'required|string'
            ]);

            // Проверяем расширение файла
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension !== 'dxf') {
                return response()->json([
                    'success' => false,
                    'message' => 'Поддерживаются только DXF файлы'
                ], 400);
            }

            $originalName = $request->input('filename');
            $baseFileName = pathinfo($originalName, PATHINFO_FILENAME);


            \Log::info('Файл получен для обработки:', [
                'original_name' => $originalName,
                'base_filename' => $baseFileName,
                'file_size' => $file->getSize()
            ]);

            // Сохраняем DXF файл
            $dxfPath = 'cad-files/dxf/' . $baseFileName . '.dxf';
            $file->storeAs('cad-files/dxf', $baseFileName . '.dxf', 'local');
            \Log::info('📄 DXF файл сохранен:', ['path' => $dxfPath]);

            // Парсим DXF в JSON
            $jsonData = $this->parseDxfToJson($dxfPath);
            \Log::info('JSON данные созданы:', ['entities_count' => count($jsonData['entities'] ?? [])]);

            // Сохраняем JSON файл
            $this->saveJsonFile($baseFileName, $jsonData);

            // Очищаем данные от некорректных UTF-8 символов
            $cleanJsonData = $this->cleanUtf8Data($jsonData);

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно обработан',
                'data' => $cleanJsonData
            ]);

        } catch (Exception $e) {
            \Log::error('Ошибка в convertToJson:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Определяем тип ошибки для более понятного сообщения
            $userMessage = $e->getMessage();
            if (strpos($e->getMessage(), 'CloudConvert не поддерживает версию DWG') !== false) {
                $userMessage = $e->getMessage();
            } elseif (strpos($e->getMessage(), 'File open failed') !== false) {
                $userMessage = 'Файл поврежден или имеет неподдерживаемый формат DWG';
            } elseif (strpos($e->getMessage(), 'не является корректным DWG файлом') !== false) {
                $userMessage = $e->getMessage();
            } else {
                $userMessage = 'Ошибка обработки файла: ' . $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $userMessage
            ], 500);
        }
    }

    private function convertDwgToDxfWithCloudConvert($dwgPath, $baseFileName)
    {
        try {
            \Log::info('🔄 Начинаем конвертацию DWG в DXF через CloudConvert');

            // Нормализуем имя файла для CloudConvert (только ASCII символы)
            $normalizedFileName = $this->normalizeFileName($baseFileName);

            // Проверяем размер файла
            $fullPath = Storage::disk('local')->path($dwgPath);
            $fileSize = filesize($fullPath);
            \Log::info('📏 Размер файла для конвертации:', [
                'file_size_bytes' => $fileSize,
                'file_size_mb' => round($fileSize / 1024 / 1024, 2)
            ]);

            // Проверяем заголовок DWG файла
            if (!$this->validateDwgFile($fullPath)) {
                throw new Exception('Файл не является корректным DWG файлом или поврежден');
            }

            // Проверяем, существует ли уже DXF файл
            $dxfPath = 'cad-files/dxf/' . $normalizedFileName . '.dxf';
            if (Storage::disk('local')->exists($dxfPath)) {
                \Log::info('✅ DXF файл уже существует, используем его', ['dxf_path' => $dxfPath]);
                return $dxfPath;
            }

            // Создаем папку для DXF файлов
            Storage::disk('local')->makeDirectory('cad-files/dxf');

            // Создаем задачу CloudConvert
            $jobResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->cloudConvertApiKey,
                'Content-Type' => 'application/json'
            ])->post($this->cloudConvertApiUrl . 'jobs', [
                'tasks' => [
                    'import-my-file' => [
                        'operation' => 'import/upload'
                    ],
                    'convert-my-file' => [
                        'operation' => 'convert',
                        'input' => 'import-my-file',
                        'output_format' => 'dxf'
                    ],
                    'export-my-file' => [
                        'operation' => 'export/url',
                        'input' => 'convert-my-file'
                    ]
                ]
            ]);

            if (!$jobResponse->successful()) {
                throw new Exception('Ошибка создания задачи CloudConvert: ' . $jobResponse->body());
            }

            $jobData = $jobResponse->json();
            \Log::info('📋 CloudConvert задача создана:', ['job_id' => $jobData['data']['id']]);

            // Находим задачу загрузки файла
            $tasks = $jobData['data']['tasks'];
            $importTask = null;

            foreach ($tasks as $task) {
                if ($task['name'] === 'import-my-file' && isset($task['result']['form'])) {
                    $importTask = $task;
                    break;
                }
            }

            if (!$importTask) {
                throw new Exception('Не найдена задача импорта в CloudConvert');
            }

            // Загружаем файл в CloudConvert
            $uploadUrl = $importTask['result']['form']['url'];
            $uploadFields = $importTask['result']['form']['parameters'];
            $dwgFullPath = Storage::disk('local')->path($dwgPath);

            \Log::info('📤 Загружаем файл в CloudConvert:', [
                'upload_url' => $uploadUrl,
                'file_path' => $dwgFullPath,
                'file_exists' => file_exists($dwgFullPath),
                'file_size' => file_exists($dwgFullPath) ? filesize($dwgFullPath) : 'N/A'
            ]);

            // Формируем данные для multipart загрузки
            $multipartData = [];
            foreach ($uploadFields as $key => $value) {
                $multipartData[] = ['name' => $key, 'contents' => $value];
            }
            $multipartData[] = [
                'name' => 'file',
                'contents' => fopen($dwgFullPath, 'r'),
                'filename' => $baseFileName . '.dwg'
            ];

            $uploadResponse = Http::asMultipart()->post($uploadUrl, $multipartData);

            \Log::info('📤 Результат загрузки:', [
                'status' => $uploadResponse->status(),
                'response_length' => strlen($uploadResponse->body())
            ]);

            if (!$uploadResponse->successful()) {
                throw new Exception('Ошибка загрузки файла в CloudConvert: ' . $uploadResponse->body());
            }

            // Ждем завершения конвертации
            $jobId = $jobData['data']['id'];
            $maxAttempts = 30;
            $attempt = 0;

            \Log::info('⏳ Ожидаем завершения конвертации...');

            while ($attempt < $maxAttempts) {
                sleep(3);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudConvertApiKey,
                ])->get($this->cloudConvertApiUrl . 'jobs/' . $jobId);

                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    $status = $statusData['data']['status'];

                    \Log::info("🔍 Попытка $attempt: статус $status");

                    if ($status === 'finished') {
                        // Находим задачу экспорта и скачиваем результат
                        foreach ($statusData['data']['tasks'] as $task) {
                            if ($task['name'] === 'export-my-file' && $task['status'] === 'finished') {
                                $downloadUrl = $task['result']['files'][0]['url'];

                                \Log::info('📥 Скачиваем DXF файл:', ['url' => $downloadUrl]);

                                $dxfContent = Http::get($downloadUrl)->body();
                                Storage::disk('local')->put($dxfPath, $dxfContent);

                                \Log::info('✅ DXF файл успешно сохранен:', ['path' => $dxfPath]);
                                return $dxfPath;
                            }
                        }
                    } elseif ($status === 'error') {
                        // Детализированное логирование ошибки
                        $errorDetails = [];
                        foreach ($statusData['data']['tasks'] as $task) {
                            if ($task['status'] === 'error') {
                                $errorDetails[] = [
                                    'task_name' => $task['name'],
                                    'message' => $task['message'] ?? 'Неизвестная ошибка',
                                    'code' => $task['code'] ?? null,
                                    'result' => $task['result'] ?? null
                                ];
                            }
                        }
                        \Log::error('💥 Детали ошибки CloudConvert:', $errorDetails);
                        throw new Exception('Ошибка конвертации в CloudConvert: ' . json_encode($errorDetails));
                    }
                }
            }

            throw new Exception('Таймаут ожидания конвертации CloudConvert');

        } catch (Exception $e) {
            \Log::error('❌ Ошибка конвертации CloudConvert:', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function parseDxfToJson($dxfPath)
    {
        \Log::info('📄 Начинаем парсинг DXF файла:', ['path' => $dxfPath]);

        $fullPath = Storage::disk('local')->path($dxfPath);
        if (!file_exists($fullPath)) {
            throw new Exception("DXF файл не найден: $fullPath");
        }

        $content = file_get_contents($fullPath);
        $lines = explode("\n", $content);

        $entities = [];
        $currentEntity = null;
        $entityData = [];

        for ($i = 0; $i < count($lines); $i++) {
            $code = trim($lines[$i] ?? '');
            $value = trim($lines[$i + 1] ?? '');

            if ($code === '0') {
                // Сохраняем предыдущую сущность
                if ($currentEntity && !empty($entityData)) {
                    $processedEntity = $this->processEntity($currentEntity, $entityData);
                    if ($processedEntity) {
                        $entities[] = $processedEntity;
                    }
                }

                // Начинаем новую сущность
                $currentEntity = $value;
                $entityData = [];
                $i++; // Пропускаем следующую строку с значением
                continue;
            }

            if ($currentEntity && is_numeric($code)) {
                $entityData[$code] = $value;
                $i++; // Пропускаем следующую строку с значением
            }
        }

        // Обрабатываем последнюю сущность
        if ($currentEntity && !empty($entityData)) {
            $processedEntity = $this->processEntity($currentEntity, $entityData);
            if ($processedEntity) {
                $entities[] = $processedEntity;
            }
        }

        // Разделяем entities по типам
        $header = [];
        $tables = [];
        $blocks = [];
        $pureEntities = [];

        foreach ($entities as $entity) {
            $type = $entity['type'];

            if (in_array($type, ['TABLE', 'VPORT', 'LTYPE', 'LAYER', 'STYLE', 'APPID', 'VIEW', 'UCS', 'DIMSTYLE', 'BLOCK_RECORD'])) {
                $tables[] = $entity;
            } elseif (in_array($type, ['BLOCK', 'ENDBLK', 'INSERT'])) {
                $blocks[] = $entity;
            } elseif (strpos($type, '$') === 0) {
                $header[] = $entity;
            } else {
                $pureEntities[] = $entity;
            }
        }

        $result = [
            'header' => $header,
            'tables' => $tables,
            'blocks' => $blocks,
            'entities' => $pureEntities
        ];

        \Log::info('✅ Парсинг DXF завершен:', [
            'header_count' => count($result['header']),
            'tables_count' => count($result['tables']),
            'blocks_count' => count($result['blocks']),
            'entities_count' => count($result['entities']),
            'entity_types' => array_count_values(array_column($result['entities'], 'type'))
        ]);

        return $result;
    }

    private function saveJsonFile($baseFileName, $jsonData)
    {
        try {
            // Создаем папку для JSON файлов
            Storage::disk('local')->makeDirectory('cad-files/json');

            $jsonPath = 'cad-files/json/' . $baseFileName . '.json';
            // Дополнительная очистка перед JSON кодированием
            $cleanedData = $this->deepCleanUtf8($jsonData);
            $jsonContent = json_encode($cleanedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($jsonContent === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }

            if (strlen($jsonContent) === 0) {
                throw new Exception('JSON content is empty');
            }

            $result = Storage::disk('local')->put($jsonPath, $jsonContent);

            if (!$result) {
                throw new Exception('Failed to write JSON file to storage');
            }

            \Log::info('💾 JSON файл сохранен:', [
                'path' => $jsonPath,
                'size' => strlen($jsonContent),
                'entities_count' => count($jsonData['entities'] ?? [])
            ]);

            return $jsonPath;
        } catch (Exception $e) {
            \Log::error('❌ Ошибка сохранения JSON файла:', [
                'message' => $e->getMessage(),
                'base_filename' => $baseFileName,
                'json_last_error' => json_last_error_msg()
            ]);
            return null;
        }
    }

    private function cleanUtf8Data($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanUtf8Data($value);
            }
            return $data;
        }

        if (is_string($data)) {
            // Удаляем некорректные UTF-8 символы
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            // Удаляем управляющие символы кроме табуляции, переноса строки и возврата каретки
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
            return $data;
        }

        return $data;
    }

    private function deepCleanUtf8($data)
    {
        if (is_string($data)) {
            // Агрессивная очистка UTF-8
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
            $data = preg_replace('/[^\x20-\x7E\x{00A0}-\x{FFFF}]/u', '', $data);

            // Если все еще есть проблемы, используем filter_var
            if (!mb_check_encoding($data, 'UTF-8')) {
                $data = filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
            }

            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->deepCleanUtf8($value);
            }
        }

        return $data;
    }

    private function normalizeFileName($fileName)
    {
        // Транслитерация русских символов в латиницу
        $translitMap = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
        ];

        // Применяем транслитерацию
        $normalized = strtr($fileName, $translitMap);

        // Удаляем все символы кроме букв, цифр, дефисов и подчеркиваний
        $normalized = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $normalized);

        // Убираем повторяющиеся подчеркивания
        $normalized = preg_replace('/_+/', '_', $normalized);

        // Убираем подчеркивания в начале и конце
        $normalized = trim($normalized, '_');

        \Log::info('📝 Нормализация имени файла:', [
            'original' => $fileName,
            'normalized' => $normalized
        ]);

        return $normalized;
    }

    private function validateDwgFile($filePath)
    {
        try {
            if (!file_exists($filePath)) {
                \Log::error('🚫 Файл не существует:', ['path' => $filePath]);
                return false;
            }

            $handle = fopen($filePath, 'rb');
            if (!$handle) {
                \Log::error('🚫 Не удается открыть файл для чтения:', ['path' => $filePath]);
                return false;
            }

            // Читаем первые 6 байт для проверки заголовка DWG
            $header = fread($handle, 6);
            fclose($handle);

            // DWG файлы начинаются с "AC" и содержат версию
            if (substr($header, 0, 2) !== 'AC') {
                \Log::error('🚫 Неверный заголовок DWG файла:', [
                    'expected' => 'AC****',
                    'actual' => bin2hex($header),
                    'path' => $filePath
                ]);
                return false;
            }

            $version = substr($header, 2, 4);
            $supportedVersions = ['1032', '1027', '1024', '1021', '1018', '1015', '1014', '1012', '1009'];
            $cloudConvertSupportedVersions = ['1027', '1024', '1021', '1018', '1015', '1014', '1012', '1009'];

            if (!in_array($version, $supportedVersions)) {
                \Log::warning('⚠️ Неподдерживаемая версия DWG:', [
                    'version' => $version,
                    'supported_versions' => $supportedVersions,
                    'path' => $filePath
                ]);
                return false;
            }

            if (!in_array($version, $cloudConvertSupportedVersions)) {
                \Log::error('🚫 CloudConvert не поддерживает версию DWG:', [
                    'version' => $version,
                    'cloudconvert_supported' => $cloudConvertSupportedVersions,
                    'path' => $filePath
                ]);
                throw new Exception("DWG версии {$version} не поддерживается CloudConvert. Попробуйте сохранить файл в более старой версии AutoCAD (2021 или ранее).");
            }

            \Log::info('✅ DWG файл прошел базовую валидацию:', [
                'version' => $version,
                'path' => $filePath
            ]);

            return true;

        } catch (Exception $e) {
            \Log::error('🚫 Ошибка валидации DWG файла:', [
                'message' => $e->getMessage(),
                'path' => $filePath
            ]);
            return false;
        }
    }

    private function processEntity($type, $data)
    {
        switch ($type) {
            case 'LINE':
                return [
                    'type' => 'LINE',
                    'properties' => [
                        'layer' => $data['8'] ?? 'Default',
                        'start_point' => [
                            'x' => floatval($data['10'] ?? 0),
                            'y' => floatval($data['20'] ?? 0),
                            'z' => floatval($data['30'] ?? 0)
                        ],
                        'end_point' => [
                            'x' => floatval($data['11'] ?? 0),
                            'y' => floatval($data['21'] ?? 0),
                            'z' => floatval($data['31'] ?? 0)
                        ]
                    ]
                ];

            case 'LWPOLYLINE':
                $vertices = [];
                $x_coords = [];
                $y_coords = [];
                $bulges = [];

                // Собираем координаты вершин
                foreach ($data as $code => $value) {
                    if ($code === '10') $x_coords[] = floatval($value);
                    if ($code === '20') $y_coords[] = floatval($value);
                    if ($code === '42') $bulges[] = floatval($value);
                }

                for ($i = 0; $i < min(count($x_coords), count($y_coords)); $i++) {
                    $vertices[] = [
                        'x' => $x_coords[$i],
                        'y' => $y_coords[$i],
                        'bulge' => $bulges[$i] ?? 0
                    ];
                }

                return [
                    'type' => 'LWPOLYLINE',
                    'properties' => [
                        'layer' => $data['8'] ?? 'Default',
                        'vertices' => $vertices,
                        'is_closed' => isset($data['70']) && ($data['70'] & 1),
                        'vertex_count' => count($vertices)
                    ]
                ];

            case 'CIRCLE':
                return [
                    'type' => 'CIRCLE',
                    'properties' => [
                        'layer' => $data['8'] ?? 'Default',
                        'center' => [
                            'x' => floatval($data['10'] ?? 0),
                            'y' => floatval($data['20'] ?? 0),
                            'z' => floatval($data['30'] ?? 0)
                        ],
                        'radius' => floatval($data['40'] ?? 0)
                    ]
                ];

            case 'ARC':
                return [
                    'type' => 'ARC',
                    'properties' => [
                        'layer' => $data['8'] ?? 'Default',
                        'center' => [
                            'x' => floatval($data['10'] ?? 0),
                            'y' => floatval($data['20'] ?? 0),
                            'z' => floatval($data['30'] ?? 0)
                        ],
                        'radius' => floatval($data['40'] ?? 0),
                        'start_angle' => floatval($data['50'] ?? 0),
                        'end_angle' => floatval($data['51'] ?? 0)
                    ]
                ];

            case 'TEXT':
                return [
                    'type' => 'TEXT',
                    'properties' => [
                        'layer' => $data['8'] ?? 'Default',
                        'position' => [
                            'x' => floatval($data['10'] ?? 0),
                            'y' => floatval($data['20'] ?? 0),
                            'z' => floatval($data['30'] ?? 0)
                        ],
                        'text' => $data['1'] ?? '',
                        'height' => floatval($data['40'] ?? 0),
                        'rotation' => floatval($data['50'] ?? 0)
                    ]
                ];

            case 'INSERT':
                return [
                    'type' => 'INSERT',
                    'properties' => [
                        'layer' => $data['8'] ?? 'Default',
                        'position' => [
                            'x' => floatval($data['10'] ?? 0),
                            'y' => floatval($data['20'] ?? 0),
                            'z' => floatval($data['30'] ?? 0)
                        ],
                        'block_name' => $data['2'] ?? '',
                        'rotation' => floatval($data['50'] ?? 0)
                    ]
                ];

            default:
                // Игнорируем служебные элементы
                if (in_array($type, ['SECTION', 'ENDSEC', 'EOF', 'HEADER', 'TABLES', 'BLOCKS', 'ENTITIES'])) {
                    return null;
                }

                return [
                    'type' => $type,
                    'properties' => [
                        'layer' => $data['8'] ?? 'Default',
                        'raw_data' => $data
                    ]
                ];
        }
    }

    public function getLocalFiles(): JsonResponse
    {
        try {
            $dwgFiles = Storage::disk('local')->files('cad-files/dwg');
            $dxfFiles = Storage::disk('local')->files('cad-files/dxf');
            $jsonFiles = Storage::disk('local')->files('cad-files/json');

            $fileList = [];

            foreach ($dwgFiles as $file) {
                $fileList[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'type' => 'DWG',
                    'size' => Storage::disk('local')->size($file),
                    'modified' => Storage::disk('local')->lastModified($file)
                ];
            }

            foreach ($dxfFiles as $file) {
                $fileList[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'type' => 'DXF',
                    'size' => Storage::disk('local')->size($file),
                    'modified' => Storage::disk('local')->lastModified($file)
                ];
            }

            foreach ($jsonFiles as $file) {
                $fileList[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'type' => 'JSON',
                    'size' => Storage::disk('local')->size($file),
                    'modified' => Storage::disk('local')->lastModified($file)
                ];
            }

            return response()->json([
                'success' => true,
                'files' => $fileList
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function convertLocalFile(Request $request): JsonResponse
    {
        \Log::info('🔄 Конвертация локального файла запущена');

        try {
            $request->validate([
                'filename' => 'required|string'
            ]);

            $filename = $request->input('filename');
            $dwgPath = 'cad-files/dwg/' . $filename;

            if (!Storage::disk('local')->exists($dwgPath)) {
                throw new Exception('Файл не найден: ' . $filename);
            }

            $baseFileName = pathinfo($filename, PATHINFO_FILENAME);

            // Конвертируем в DXF
            $dxfPath = $this->convertDwgToDxfWithCloudConvert($dwgPath, $baseFileName);
            if (!$dxfPath) {
                throw new Exception('Не удалось конвертировать файл');
            }

            // Парсим в JSON
            $jsonData = $this->parseDxfToJson($dxfPath);

            // Сохраняем JSON файл
            $this->saveJsonFile($baseFileName, $jsonData);

            // Очищаем данные от некорректных UTF-8 символов
            $cleanJsonData = $this->cleanUtf8Data($jsonData);

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно конвертирован',
                'data' => $cleanJsonData
            ]);

        } catch (Exception $e) {
            \Log::error('❌ Ошибка конвертации локального файла:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkApiQuota(): JsonResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->cloudConvertApiKey,
            ])->get($this->cloudConvertApiUrl . 'users/me');

            if ($response->successful()) {
                $userData = $response->json();
                return response()->json([
                    'success' => true,
                    'quota' => $userData['data']
                ]);
            } else {
                throw new Exception('Ошибка получения квоты API');
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function forceRefreshCache(): JsonResponse
    {
        try {
            \Log::info('🔄 Принудительная очистка кеша запущена');

            // Очищаем все кеши Laravel
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Кеш успешно очищен'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function testNodeJs(): JsonResponse
    {
        try {
            // Проверяем доступность Node.js
            $output = shell_exec('node --version 2>&1');

            return response()->json([
                'success' => true,
                'node_version' => trim($output ?? 'Не установлен'),
                'message' => 'Node.js проверен'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getNextSectionType($lines, &$i)
    {
        // Ищем код 2 после SECTION
        for ($j = $i + 2; $j < count($lines); $j += 2) {
            $code = trim($lines[$j] ?? '');
            $value = trim($lines[$j + 1] ?? '');
            if ($code === '2') {
                return $value;
            }
            if ($code === '0') break;
        }
        return 'UNKNOWN';
    }

    private function getNextTableType($lines, &$i)
    {
        // Ищем код 2 после TABLE
        for ($j = $i + 2; $j < count($lines); $j += 2) {
            $code = trim($lines[$j] ?? '');
            $value = trim($lines[$j + 1] ?? '');
            if ($code === '2') {
                return $value;
            }
            if ($code === '0') break;
        }
        return 'UNKNOWN';
    }

    private function processDxfEntity($result, $currentSection, $currentSubSection, $currentEntity, $entityData)
    {
        if (!$currentEntity) return;

        switch ($currentSection) {
            case 'HEADER':
                $this->processHeaderVariable($result, $currentEntity, $entityData);
                break;

            case 'TABLES':
                $this->processTableEntity($result, $currentSubSection, $currentEntity, $entityData);
                break;

            case 'BLOCKS':
                $this->processBlockEntity($result, $currentEntity, $entityData);
                break;

            case 'ENTITIES':
                $this->processGeometryEntity($result, $currentEntity, $entityData);
                break;

            default:
                // Неизвестная секция - добавляем как entity
                $processedEntity = $this->processEntity($currentEntity, $entityData);
                if ($processedEntity) {
                    $result['entities'][] = $processedEntity;
                }
        }
    }

    private function processHeaderVariable(&$result, $variable, $data)
    {
        // Переменные заголовка начинаются с $
        if (strpos($variable, '$') === 0) {
            $result['header'][$variable] = [
                'name' => $variable,
                'value' => $data['1'] ?? $data['10'] ?? $data['40'] ?? 'unknown',
                'type' => $this->getHeaderVariableType($variable),
                'raw_data' => $data
            ];
        } else {
            // Для non-$ элементов в заголовке добавляем как есть
            $result['header'][] = [
                'type' => $variable,
                'data' => $data
            ];
        }
    }

    private function processTableEntity(&$result, $tableType, $entity, $data)
    {
        if (!$tableType || $entity === 'TABLE' || $entity === 'ENDTAB') return;

        $table = [
            'table_type' => $tableType,
            'entity_type' => $entity,
            'name' => $data['2'] ?? 'unnamed',
            'properties' => $this->extractTableProperties($tableType, $entity, $data),
            'raw_data' => $data
        ];

        $result['tables'][] = $table;
    }

    private function processBlockEntity(&$result, $entity, $data)
    {
        if ($entity === 'BLOCK') {
            $result['blocks'][] = [
                'type' => 'BLOCK_DEFINITION',
                'name' => $data['2'] ?? 'unnamed',
                'base_point' => [
                    'x' => floatval($data['10'] ?? 0),
                    'y' => floatval($data['20'] ?? 0),
                    'z' => floatval($data['30'] ?? 0)
                ],
                'properties' => $data
            ];
        } elseif ($entity === 'INSERT') {
            $result['blocks'][] = [
                'type' => 'BLOCK_INSERT',
                'block_name' => $data['2'] ?? 'unnamed',
                'position' => [
                    'x' => floatval($data['10'] ?? 0),
                    'y' => floatval($data['20'] ?? 0),
                    'z' => floatval($data['30'] ?? 0)
                ],
                'scale' => [
                    'x' => floatval($data['41'] ?? 1),
                    'y' => floatval($data['42'] ?? 1),
                    'z' => floatval($data['43'] ?? 1)
                ],
                'rotation' => floatval($data['50'] ?? 0),
                'properties' => $data
            ];
        } else {
            // Геометрия внутри блока
            $processedEntity = $this->processEntity($entity, $data);
            if ($processedEntity) {
                $result['blocks'][] = [
                    'type' => 'BLOCK_GEOMETRY',
                    'geometry' => $processedEntity
                ];
            }
        }
    }

    private function processGeometryEntity(&$result, $entity, $data)
    {
        $processedEntity = $this->processEntity($entity, $data);
        if ($processedEntity) {
            $result['entities'][] = $processedEntity;
        }
    }

    private function getHeaderVariableType($variable)
    {
        $intVars = ['$ACADVER', '$DWGCODEPAGE', '$LUNITS', '$LUPREC'];
        $floatVars = ['$EXTMIN', '$EXTMAX', '$LIMMIN', '$LIMMAX'];

        if (in_array($variable, $intVars)) return 'integer';
        if (in_array($variable, $floatVars)) return 'point';
        return 'string';
    }

    private function extractTableProperties($tableType, $entity, $data)
    {
        switch ($tableType) {
            case 'LAYER':
                return [
                    'layer_name' => $data['2'] ?? 'Default',
                    'color' => intval($data['62'] ?? 7),
                    'linetype' => $data['6'] ?? 'CONTINUOUS',
                    'flags' => intval($data['70'] ?? 0),
                    'visible' => !($data['62'] ?? false) || intval($data['62']) >= 0
                ];

            case 'LTYPE':
                return [
                    'linetype_name' => $data['2'] ?? 'CONTINUOUS',
                    'description' => $data['3'] ?? '',
                    'pattern_length' => floatval($data['40'] ?? 0),
                    'flags' => intval($data['70'] ?? 0)
                ];

            case 'STYLE':
                return [
                    'style_name' => $data['2'] ?? 'Standard',
                    'font_file' => $data['3'] ?? '',
                    'text_height' => floatval($data['40'] ?? 0),
                    'width_factor' => floatval($data['41'] ?? 1),
                    'flags' => intval($data['70'] ?? 0)
                ];

            default:
                return $data;
        }
    }
}