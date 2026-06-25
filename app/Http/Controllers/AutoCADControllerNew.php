<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Exception;

class AutoCADControllerNew extends Controller
{
    private $cloudConvertApiKey;
    private $cloudConvertApiUrl = 'https://api.cloudconvert.com/v2/';

    public function __construct()
    {
        $this->cloudConvertApiKey = env('CLOUDCONVERT_API_KEY');
    }

    public function convertDwgToJson(Request $request): JsonResponse
    {
        // ПОЛНОСТЬЮ НОВЫЙ ФАЙЛ КОНТРОЛЛЕРА!
        \Log::info('===== ПОЛНОСТЬЮ НОВЫЙ АВТОКАД КОНТРОЛЛЕР РАБОТАЕТ! =====', [
            'timestamp' => now(),
            'request_data' => $request->all()
        ]);

        try {
            $request->validate([
                'file' => 'required|file|mimes:dwg|max:102400',
                'filename' => 'required|string'
            ]);

            $file = $request->file('file');
            $originalName = $request->input('filename');
            $baseFileName = pathinfo($originalName, PATHINFO_FILENAME);

            \Log::info('Файл получен для обработки:', [
                'original_name' => $originalName,
                'base_filename' => $baseFileName,
                'file_size' => $file->getSize()
            ]);

            // Сохраняем DWG файл
            $dwgPath = 'cad-files/dwg/' . $baseFileName . '.dwg';
            $file->storeAs('cad-files/dwg', $baseFileName . '.dwg', 'local');

            \Log::info('DWG файл сохранен:', ['path' => $dwgPath]);

            // Конвертируем в DXF через CloudConvert
            $dxfPath = $this->convertDwgToDxfWithCloudConvert($dwgPath, $baseFileName);

            if (!$dxfPath) {
                throw new Exception('Не удалось конвертировать DWG в DXF');
            }

            \Log::info('DXF файл получен:', ['path' => $dxfPath]);

            // Парсим DXF в JSON
            $jsonData = $this->parseDxfToJson($dxfPath);

            \Log::info('JSON данные созданы:', ['entities_count' => count($jsonData['entities'] ?? [])]);

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно обработан',
                'data' => $jsonData
            ]);

        } catch (Exception $e) {
            \Log::error('Ошибка в convertDwgToJson:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обработки файла: ' . $e->getMessage()
            ], 500);
        }
    }

    private function convertDwgToDxfWithCloudConvert($dwgPath, $baseFileName)
    {
        try {
            \Log::info('Начинаем конвертацию через CloudConvert', ['dwg_path' => $dwgPath]);

            // Проверяем, существует ли уже DXF файл
            $dxfPath = 'cad-files/dxf/' . $baseFileName . '.dxf';
            if (Storage::disk('local')->exists($dxfPath)) {
                \Log::info('DXF файл уже существует, возвращаем его', ['dxf_path' => $dxfPath]);
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
                        'output_format' => 'dxf',
                        'some_other_option' => 'value'
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
            \Log::info('CloudConvert задача создана:', ['job_id' => $jobData['data']['id']]);

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

            \Log::info('Найдена задача импорта, начинаем загрузку файла');

            // Загружаем файл в CloudConvert
            $uploadUrl = $importTask['result']['form']['url'];
            $uploadFields = $importTask['result']['form']['parameters'];

            $dwgFullPath = Storage::disk('local')->path($dwgPath);

            \Log::info('НАЧИНАЕМ ЗАГРУЗКУ ФАЙЛА В CLOUDCONVERT:', [
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

            \Log::info('Результат загрузки в CloudConvert:', [
                'status' => $uploadResponse->status(),
                'body' => $uploadResponse->body()
            ]);

            if (!$uploadResponse->successful()) {
                throw new Exception('Ошибка загрузки файла в CloudConvert: ' . $uploadResponse->body());
            }

            // Ждем завершения конвертации
            $jobId = $jobData['data']['id'];
            $maxAttempts = 30;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(2);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudConvertApiKey,
                ])->get($this->cloudConvertApiUrl . 'jobs/' . $jobId);

                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    $status = $statusData['data']['status'];

                    \Log::info("Попытка $attempt: статус задачи CloudConvert: $status");

                    if ($status === 'finished') {
                        // Находим задачу экспорта и скачиваем результат
                        foreach ($statusData['data']['tasks'] as $task) {
                            if ($task['name'] === 'export-my-file' && $task['status'] === 'finished') {
                                $downloadUrl = $task['result']['files'][0]['url'];

                                \Log::info('Скачиваем конвертированный DXF файл:', ['url' => $downloadUrl]);

                                $dxfContent = Http::get($downloadUrl)->body();
                                Storage::disk('local')->put($dxfPath, $dxfContent);

                                \Log::info('DXF файл успешно сохранен:', ['path' => $dxfPath]);
                                return $dxfPath;
                            }
                        }
                    } elseif ($status === 'error') {
                        throw new Exception('Ошибка конвертации в CloudConvert');
                    }
                }
            }

            throw new Exception('Таймаут ожидания конвертации CloudConvert');

        } catch (Exception $e) {
            \Log::error('Ошибка конвертации CloudConvert:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function parseDxfToJson($dxfPath)
    {
        \Log::info('Начинаем парсинг DXF файла:', ['path' => $dxfPath]);

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
                    $entities[] = $this->processEntity($currentEntity, $entityData);
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
            $entities[] = $this->processEntity($currentEntity, $entityData);
        }

        $result = [
            'header' => [],
            'tables' => [],
            'blocks' => [],
            'entities' => array_filter($entities)
        ];

        \Log::info('Парсинг DXF завершен:', [
            'total_entities' => count($result['entities']),
            'entity_types' => array_count_values(array_column($result['entities'], 'type'))
        ]);

        return $result;
    }

    private function processEntity($type, $data)
    {
        switch ($type) {
            case 'LINE':
                return [
                    'type' => 'LINE',
                    'properties' => [
                        'raw' => $data,
                        'layer' => $data['8'] ?? 'Default',
                        'x1' => floatval($data['10'] ?? 0),
                        'y1' => floatval($data['20'] ?? 0),
                        'z1' => floatval($data['30'] ?? 0),
                        'x2' => floatval($data['11'] ?? 0),
                        'y2' => floatval($data['21'] ?? 0),
                        'z2' => floatval($data['31'] ?? 0)
                    ]
                ];

            case 'LWPOLYLINE':
                $vertices = [];
                $x_coords = [];
                $y_coords = [];

                // Собираем координаты вершин
                foreach ($data as $code => $value) {
                    if ($code === '10') $x_coords[] = floatval($value);
                    if ($code === '20') $y_coords[] = floatval($value);
                }

                for ($i = 0; $i < min(count($x_coords), count($y_coords)); $i++) {
                    $vertices[] = [$x_coords[$i], $y_coords[$i]];
                }

                return [
                    'type' => 'LWPOLYLINE',
                    'properties' => [
                        'raw' => $data,
                        'layer' => $data['8'] ?? 'Default',
                        'vertices' => $vertices,
                        'is_closed' => isset($data['70']) && ($data['70'] & 1)
                    ]
                ];

            case 'INSERT':
                return [
                    'type' => 'INSERT',
                    'properties' => [
                        'raw' => $data,
                        'layer' => $data['8'] ?? 'Default',
                        'x1' => floatval($data['10'] ?? 0),
                        'y1' => floatval($data['20'] ?? 0),
                        'z1' => floatval($data['30'] ?? 0)
                    ]
                ];

            default:
                if (in_array($type, ['SECTION', 'ENDSEC', 'EOF', 'HEADER', 'TABLES', 'BLOCKS', 'ENTITIES'])) {
                    return null; // Игнорируем служебные элементы
                }

                return [
                    'type' => $type,
                    'properties' => [
                        'raw' => $data,
                        'layer' => $data['8'] ?? 'Default'
                    ]
                ];
        }
    }

    public function saveJson(Request $request): JsonResponse
    {
        \Log::info('МЕТОД SAVE JSON ВЫЗВАН!');

        try {
            $request->validate([
                'filename' => 'required|string',
                'data' => 'required|array'
            ]);

            $filename = $request->input('filename');
            $data = $request->input('data');

            $jsonPath = 'cad-files/json/' . pathinfo($filename, PATHINFO_FILENAME) . '.json';
            Storage::disk('local')->makeDirectory('cad-files/json');
            Storage::disk('local')->put($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            \Log::info('JSON файл сохранен:', ['path' => $jsonPath]);

            return response()->json([
                'success' => true,
                'message' => 'JSON файл успешно сохранен',
                'path' => $jsonPath
            ]);

        } catch (Exception $e) {
            \Log::error('Ошибка сохранения JSON:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка сохранения JSON: ' . $e->getMessage()
            ], 500);
        }
    }
}