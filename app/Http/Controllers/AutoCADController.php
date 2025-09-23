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
     * Конвертация DWG в JSON через Node.js процессор
     */
    public function convertToJson(Request $request)
    {
        try {
            // Валидация запроса
            $request->validate([
                'file' => 'required|file|max:102400', // 100MB
            ]);

            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());

            // Проверка расширения файла
            if (!in_array($extension, ['dwg', 'dxf'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Поддерживаются только файлы DWG и DXF'
                ], 422);
            }

            Log::info('AutoCAD: Начинаем обработку файла', [
                'filename' => $filename,
                'extension' => $extension,
                'size' => $file->getSize()
            ]);

            // Читаем содержимое файла
            $fileContent = file_get_contents($file->getPathname());

            if ($extension === 'dxf') {
                // Обработка DXF файла
                $result = $this->processDxfFile($filename, $fileContent);
            } else {
                // Обработка DWG файла через Aspose.CAD
                $result = $this->processDwgFile($filename, $fileContent);
            }

            Log::info('AutoCAD: Файл успешно обработан', [
                'filename' => $filename,
                'entities_count' => $result['stats']['entitiesCount'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Файл успешно конвертирован в JSON'
            ]);

        } catch (\Exception $e) {
            Log::error('AutoCAD: Ошибка обработки файла', [
                'error' => $e->getMessage(),
                'file' => $request->file('file')?->getClientOriginalName()
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
                '"C:\Program Files\nodejs\node.exe" %s %s',
                escapeshellarg(resource_path('js/plugins/autoCAD/process-dxf.js')),
                escapeshellarg($tempFile)
            );

            $output = shell_exec($command . ' 2>nul');

            if (!$output) {
                throw new \Exception('Не удалось выполнить обработку DXF файла');
            }

            $result = json_decode($output, true);

            if (!$result || !isset($result['success'])) {
                throw new \Exception('Ошибка парсинга DXF: ' . $output);
            }

            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Неизвестная ошибка обработки DXF');
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
                '"C:\Program Files\nodejs\node.exe" %s %s %s',
                escapeshellarg(resource_path('js/plugins/autoCAD/convert-dwg.js')),
                escapeshellarg($tempDwgFile),
                escapeshellarg($tempDxfFile)
            );

            $output = shell_exec($command . ' 2>nul');

            if (!$output) {
                throw new \Exception('Не удалось выполнить конвертацию DWG файла');
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
    public function getLocalFiles()
    {
		$user = app(UserService::class);
        try {
			
            $cadDirectory = $user->getSettings('cad.local_path_to_cad_files');

            if (!is_dir($cadDirectory)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Папка с CAD файлами не найдена: ' . $cadDirectory
                ], 404);
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

            return response()->json([
                'success' => true,
                'files' => $files,
                'count' => count($files),
                'directory' => $cadDirectory
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
            $cadDirectory = $user->getSettings('cad.local_path_to_cad_files');
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

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($extension, ['dwg', 'dxf'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Поддерживаются только файлы DWG и DXF'
                ], 422);
            }

            Log::info('AutoCAD: Начинаем обработку локального файла', [
                'filename' => $filename,
                'extension' => $extension,
                'path' => $filepath
            ]);

            // Читаем содержимое файла
            $fileContent = file_get_contents($filepath);

            if ($extension === 'dxf') {
                $result = $this->processDxfFile($filename, $fileContent);
            } else {
                $result = $this->processDwgFile($filename, $fileContent);
            }

            Log::info('AutoCAD: Локальный файл успешно обработан', [
                'filename' => $filename,
                'entities_count' => $result['stats']['entitiesCount'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
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
                '"C:\Program Files\nodejs\node.exe" %s',
                escapeshellarg(resource_path('js/plugins/autoCAD/check-quota.js'))
            );

            $output = shell_exec($command . ' 2>nul');
            $result = json_decode($output, true);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'available' => false,
                'message' => 'Ошибка проверки API: ' . $e->getMessage()
            ]);
        }
    }
}