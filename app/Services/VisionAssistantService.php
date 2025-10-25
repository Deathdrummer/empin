<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Cache, Http, Storage};
use OpenAI\Laravel\Facades\OpenAI;
use GuzzleHttp\Client as GuzzleClient;

class VisionAssistantService
{
	public const THREAD_CACHE = 'openai.vision.thread_id';
	private const FILE_MAP_CACHE = 'openai.vision.file_map';
	private const VECTOR_STORE_CACHE = 'openai.vision.vector_store_id';

	/**
	 * Очистить весь кеш OpenAI Assistant (файлы, треды, Vector Store).
	 * Используется при загрузке/удалении файлов для принудительной синхронизации.
	 *
	 * @return void
	 */
	public static function clearCache(): void
	{
		Cache::forget(self::THREAD_CACHE);
		Cache::forget(self::FILE_MAP_CACHE);
		Cache::forget(self::VECTOR_STORE_CACHE);
	}

	private string $assistantId;
	private string $dir;
	private string $instructionFile;
	private \Illuminate\Contracts\Filesystem\Filesystem $disk;
	private \OpenAI\Client $openai;

	/** @var string[] MIME‑типы, которые можно класть в Vector Store */
	private const VECTOR_MIME = [
		'text/x-c', 'text/x-c++', 'text/x-csharp', 'text/css', 'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'text/x-golang', 'text/html', 'text/x-java', 'text/javascript', 'application/json',
		'text/markdown', 'application/pdf', 'text/x-php',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'text/x-python', 'text/x-script.python', 'text/x-ruby', 'application/x-sh',
		'text/x-tex', 'application/typescript', 'text/plain',
	];

	/** @var string[] MIME‑типы, которые поддерживает Code Interpreter */
	private const CODE_MIME = [
		// дублируем VECTOR_MIME, потому что текстовые файлы CI тоже кушает
		...self::VECTOR_MIME,
		'text/csv', 'application/csv', 'image/jpeg', 'image/png', 'image/gif',
		'application/octet-stream', 'application/x-tar',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/xml', 'text/xml', 'application/zip',
	];

	/**
	 * Конструктор сервиса ассистента.
	 *
	 * @param array{
	 *     assistant_id?: string,
	 *     instruction_file?: string,
	 *     dir?: string,
	 *     model?: string,
	 *     temperature?: float,
	 *     top_p?: float,
	 *     response_format?: string|array,
	 *     reasoning_effort?: string,
	 * } $config Массив конфигурации сервиса
	 */
	public function __construct(array $config = [])
	{
		$this->assistantId = $config['assistant_id'] ?? config('openai.assistant_id');
		$this->instructionFile = $config['instruction_file'] ?? 'prompts/plan.txt';
		$this->dir = $config['dir'] ?? 'assistent';
		$this->disk = Storage::disk();
		$this->openai = \OpenAI::factory()
			->withApiKey(config('openai.api_key'))
			->withHttpClient(new GuzzleClient([
				'headers' => ['OpenAI-Beta' => 'assistants=v2'],
				'proxy' => [
					'http' => config('openai.proxy_url'),
					'https' => config('openai.proxy_url'),
				],
				'verify' => false,
				'timeout' => 3600,
			]))
			->make();

		// Если переданы параметры модели при инициализации, обновляем ассистента
		$updateParams = [];
		if (isset($config['model'])) {
			$updateParams['model'] = $config['model'];
		}
		if (isset($config['temperature'])) {
			$updateParams['temperature'] = $config['temperature'];
		}
		if (isset($config['top_p'])) {
			$updateParams['top_p'] = $config['top_p'];
		}
		if (isset($config['response_format'])) {
			$updateParams['response_format'] = $config['response_format'];
		}
		if (isset($config['reasoning_effort'])) {
			$updateParams['reasoning_effort'] = $config['reasoning_effort'];
		}

		if (!empty($updateParams)) {
			$this->updateAssistant($updateParams);
		}
	}

	/**
	 * Переключиться на другого ассистента с новой конфигурацией.
	 *
	 * @param array{
	 *     assistant_id: string,
	 *     instruction_file?: string,
	 *     dir?: string,
	 *     model?: string,
	 *     temperature?: float,
	 *     top_p?: float,
	 *     response_format?: string|array,
	 *     reasoning_effort?: string,
	 *     file_search?: bool,
	 * } $config Массив конфигурации (assistant_id обязателен)
	 *
	 * @return static
	 */
	public function use(array $config): static
	{
		if (!isset($config['assistant_id'])) {
			throw new \InvalidArgumentException("assistant_id is required in config array");
		}

		$this->assistantId = $config['assistant_id'];
		$this->instructionFile = $config['instruction_file'] ?? $this->instructionFile;
		$this->dir = $config['dir'] ?? $this->dir;

		// Управление File Search через параметр
		if (isset($config['file_search'])) {
			if ($config['file_search']) {
				$this->enableFileSearch();
			} else {
				$this->disableFileSearch();
			}
		}

		Cache::forget(self::THREAD_CACHE);
		Cache::forget(self::FILE_MAP_CACHE);
		Cache::forget(self::VECTOR_STORE_CACHE);

		// Если переданы параметры модели, обновляем ассистента
		$updateParams = [];
		if (isset($config['model'])) {
			$updateParams['model'] = $config['model'];
		}
		if (isset($config['temperature'])) {
			$updateParams['temperature'] = $config['temperature'];
		}
		if (isset($config['top_p'])) {
			$updateParams['top_p'] = $config['top_p'];
		}
		if (isset($config['response_format'])) {
			$updateParams['response_format'] = $config['response_format'];
		}
		if (isset($config['reasoning_effort'])) {
			$updateParams['reasoning_effort'] = $config['reasoning_effort'];
		}

		if (!empty($updateParams)) {
			$this->updateAssistant($updateParams);
		}

		return $this;
	}

	/**
	 * Возвращает список всех ассистентов вашей организации.
	 *
	 * @param array{
	 *     limit?:   int,
	 *     order?:   'asc'|'desc',
	 *     after?:   string,
	 *     before?:  string,
	 * } $params
	 *
	 * @return array Массив в формате, возвращаемом OpenAI API.
	 */
	public function listAssistants(array $params = []): array
	{
		return $this->openai->assistants()->list($params)->toArray();
	}

	/**
	 * Получить полную информацию о текущем ассистенте.
	 *
	 * @return array Данные ассистента (id, model, instructions, tools, etc.)
	 */
	public function getAssistant(): array
	{
		return $this->openai->assistants()->retrieve($this->assistantId)->toArray();
	}

	/**
	 * Получить системные инструкции (system prompt) текущего ассистента.
	 *
	 * @return string Текст инструкций
	 */
	public function getInstructions(): string
	{
		return $this->openai->assistants()->retrieve($this->assistantId)->instructions ?? '';
	}

	/**
	 * Получить текущую модель ассистента.
	 *
	 * @return string ID модели (например, 'gpt-4', 'gpt-4-turbo-preview', 'o1-preview')
	 */
	public function getModel(): string
	{
		return $this->openai->assistants()->retrieve($this->assistantId)->model;
	}

	/**
	 * Получить формат ответа (response_format) ассистента.
	 *
	 * @return string|array 'auto', 'text', 'json_object' или массив с детальной конфигурацией
	 */
	public function getResponseFormat(): string|array
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		return $assistant->response_format ?? 'auto';
	}

	/**
	 * Получить уровень reasoning effort для reasoning моделей (o1, o3).
	 *
	 * @return string|null 'low', 'medium', 'high' или null если не установлено
	 */
	public function getReasoningEffort(): ?string
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		return $assistant->reasoning_effort ?? null;
	}

	/**
	 * Получить значение temperature ассистента.
	 *
	 * @return float|null Значение от 0 до 2, или null если не установлено
	 */
	public function getTemperature(): ?float
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		return $assistant->temperature ?? null;
	}

	/**
	 * Получить значение top_p (nucleus sampling) ассистента.
	 *
	 * @return float|null Значение от 0 до 1, или null если не установлено
	 */
	public function getTopP(): ?float
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		return $assistant->top_p ?? null;
	}

	/**
	 * Получить список инструментов (tools) ассистента.
	 *
	 * @return array Массив tools: [['type' => 'file_search'], ['type' => 'code_interpreter'], ...]
	 */
	public function getTools(): array
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		return $assistant->tools ?? [];
	}

	/**
	 * Создаёт нового ассистента.
	 *
	 * @param array{
	 *     model: string,
	 *     instructions: string,
	 *     name?: string,
	 *     description?: string,
	 *     tools?: array<array{type: string, function?: array, tool_resources?: array}>,
	 *     tool_resources?: array,
	 *     metadata?: array<string, scalar>,
	 *     temperature?: float,
	 *     top_p?: float,
	 *     format?: 'auto'|'text'|'json_object',
	 *     response_format?: 'auto'|'text'|'json_object',
	 * } $params
	 *
	 * @return array Данные нового ассистента в оригинальном формате OpenAI.
	 */
	public function createAssistant(array $params): array
	{
		return $this->openai->assistants()->create($params)->toArray();
	}

	/**
	 * Универсальный метод обновления параметров ассистента.
	 *
	 * @param array{
	 *     model?: string,
	 *     instructions?: string,
	 *     name?: string,
	 *     description?: string,
	 *     tools?: array,
	 *     tool_resources?: array,
	 *     metadata?: array<string, scalar>,
	 *     temperature?: float|null,
	 *     top_p?: float|null,
	 *     response_format?: string|array,
	 *     reasoning_effort?: string|null,
	 * } $params
	 *
	 * @return array Обновленные данные ассистента
	 */
	public function updateAssistant(array $params): array
	{
		$result = $this->openai->assistants()->modify($this->assistantId, $params);
		Cache::forget(self::THREAD_CACHE);
		return $result->toArray();
	}

	/**
	 * Обновить системные инструкции (system prompt) ассистента.
	 *
	 * @param string $instructions Новый текст инструкций
	 * @return array Обновленные данные ассистента
	 */
	public function updateInstructions(string $instructions): array
	{
		return $this->updateAssistant(['instructions' => $instructions]);
	}

	/**
	 * Обновить модель ассистента.
	 *
	 * @param string $model ID модели (например, 'gpt-4-turbo-preview', 'gpt-4', 'o1-preview')
	 * @return array Обновленные данные ассистента
	 */
	public function updateModel(string $model): array
	{
		return $this->updateAssistant(['model' => $model]);
	}

	/**
	 * Обновить формат ответа ассистента.
	 *
	 * @param string|array $format 'auto', 'text', 'json_object' или детальная конфигурация
	 * @return array Обновленные данные ассистента
	 */
	public function updateResponseFormat(string|array $format): array
	{
		return $this->updateAssistant(['response_format' => $format]);
	}

	/**
	 * Обновить уровень reasoning effort для reasoning моделей (o1, o3).
	 *
	 * @param string $effort 'low', 'medium' или 'high'
	 * @return array Обновленные данные ассистента
	 */
	public function updateReasoningEffort(string $effort): array
	{
		if (!in_array($effort, ['low', 'medium', 'high'], true)) {
			throw new \InvalidArgumentException("Reasoning effort must be 'low', 'medium', or 'high'");
		}
		return $this->updateAssistant(['reasoning_effort' => $effort]);
	}

	/**
	 * Обновить температуру (temperature) ассистента.
	 *
	 * @param float $temperature Значение от 0 до 2 (высокие значения = более случайный вывод)
	 * @return array Обновленные данные ассистента
	 */
	public function updateTemperature(float $temperature): array
	{
		if ($temperature < 0 || $temperature > 2) {
			throw new \InvalidArgumentException("Temperature must be between 0 and 2");
		}
		return $this->updateAssistant(['temperature' => $temperature]);
	}

	/**
	 * Обновить top_p (nucleus sampling) ассистента.
	 *
	 * @param float $topP Значение от 0 до 1
	 * @return array Обновленные данные ассистента
	 */
	public function updateTopP(float $topP): array
	{
		if ($topP < 0 || $topP > 1) {
			throw new \InvalidArgumentException("Top P must be between 0 and 1");
		}
		return $this->updateAssistant(['top_p' => $topP]);
	}

	/**
	 * Получить список всех доступных моделей OpenAI.
	 *
	 * @return array Массив моделей с их характеристиками
	 */
	public function listModels(): array
	{
		return $this->openai->models()->list()->toArray();
	}

	/* ---------- УПРАВЛЕНИЕ TOOLS ---------- */

	/**
	 * Включить File Search tool для ассистента.
	 *
	 * @return array Обновленные данные ассистента
	 */
	public function enableFileSearch(): array
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		$tools = $assistant->tools ?? [];

		$hasFileSearch = collect($tools)->contains(fn($t) => ($t['type'] ?? null) === 'file_search');
		if (!$hasFileSearch) {
			$tools[] = ['type' => 'file_search'];
		}

		return $this->updateAssistant(['tools' => $tools]);
	}

	/**
	 * Отключить File Search tool для ассистента.
	 *
	 * @return array Обновленные данные ассистента
	 */
	public function disableFileSearch(): array
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		$tools = $assistant->tools ?? [];

		$tools = collect($tools)->filter(fn($t) => ($t['type'] ?? null) !== 'file_search')->values()->all();

		return $this->updateAssistant(['tools' => $tools]);
	}

	/**
	 * Включить Code Interpreter tool для ассистента.
	 *
	 * @return array Обновленные данные ассистента
	 */
	public function enableCodeInterpreter(): array
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		$tools = $assistant->tools ?? [];

		$hasCodeInterpreter = collect($tools)->contains(fn($t) => ($t['type'] ?? null) === 'code_interpreter');
		if (!$hasCodeInterpreter) {
			$tools[] = ['type' => 'code_interpreter'];
		}

		return $this->updateAssistant(['tools' => $tools]);
	}

	/**
	 * Отключить Code Interpreter tool для ассистента.
	 *
	 * @return array Обновленные данные ассистента
	 */
	public function disableCodeInterpreter(): array
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		$tools = $assistant->tools ?? [];

		$tools = collect($tools)->filter(fn($t) => ($t['type'] ?? null) !== 'code_interpreter')->values()->all();

		return $this->updateAssistant(['tools' => $tools]);
	}

	/* ---------- УПРАВЛЕНИЕ ФАЙЛАМИ ---------- */

	/**
	 * Получить информацию о файле по его ID.
	 *
	 * @param string $fileId ID файла в OpenAI
	 * @return array{id: string, filename: string, bytes: int, created_at: int, purpose: string, status: string}
	 */
	public function getFileInfo(string $fileId): array
	{
		return $this->openai->files()->retrieve($fileId)->toArray();
	}

	/**
	 * Получить список всех загруженных файлов в OpenAI.
	 *
	 * @param string|null $purpose Фильтр по назначению ('assistants', 'vision', etc.)
	 * @return array Массив файлов с полной информацией
	 */
	public function listFiles(?string $purpose = null): array
	{
		$params = $purpose ? ['purpose' => $purpose] : [];
		return $this->openai->files()->list($params)->toArray();
	}

	/**
	 * Удалить файл из OpenAI по ID.
	 *
	 * @param string $fileId ID файла
	 * @return array Результат удаления
	 */
	public function deleteFile(string $fileId): array
	{
		return $this->openai->files()->delete($fileId)->toArray();
	}

	/* ---------- УПРАВЛЕНИЕ ФАЙЛАМИ VECTOR STORE ---------- */

	/**
	 * Получить список файлов в Vector Store ассистента с полной информацией.
	 *
	 * @return array Массив файлов с названиями, размерами и метаданными
	 */
	public function getVectorStoreFiles(): array
	{
		$vsId = Cache::get(self::VECTOR_STORE_CACHE);
		if (!$vsId) {
			$asst = $this->openai->assistants()->retrieve($this->assistantId);
			$existing = $asst->tool_resources['file_search']['vector_store_ids'] ?? [];
			$vsId = $existing[0] ?? null;
		}

		if (!$vsId) {
			return [];
		}

		Cache::forever(self::VECTOR_STORE_CACHE, $vsId);

		// Получаем список файлов в Vector Store
		$vsFiles = $this->openai->vectorStores()->files()->list($vsId)->toArray();

		// Обогащаем данными о каждом файле
		$enrichedFiles = [];
		foreach (($vsFiles['data'] ?? []) as $vsFile) {
			$fileId = $vsFile['id'];

			try {
				$fileInfo = $this->getFileInfo($fileId);
				$enrichedFiles[] = [
					'id' => $fileId,
					'filename' => $fileInfo['filename'] ?? 'unknown',
					'size' => $fileInfo['bytes'] ?? 0,
					'size_mb' => round(($fileInfo['bytes'] ?? 0) / 1024 / 1024, 2),
					'created_at' => $fileInfo['created_at'] ?? null,
					'purpose' => $fileInfo['purpose'] ?? 'assistants',
					'status' => $vsFile['status'] ?? 'unknown',
					'vector_store_status' => $vsFile['status'] ?? null,
				];
			} catch (\Throwable $e) {
				// Если не удалось получить информацию о файле, добавляем базовые данные
				$enrichedFiles[] = [
					'id' => $fileId,
					'filename' => 'unknown',
					'size' => 0,
					'size_mb' => 0,
					'status' => $vsFile['status'] ?? 'unknown',
					'error' => 'Failed to retrieve file info',
				];
			}
		}

		return $enrichedFiles;
	}

	/**
	 * Добавить файл в Vector Store ассистента.
	 * Принимает файл, загружает его в OpenAI и добавляет в Vector Store.
	 *
	 * @param UploadedFile|string $file Загруженный файл или путь к файлу
	 * @return array{file_id: string, filename: string, size: int, status: string}
	 */
	public function addFileToVectorStore(UploadedFile|string $file): array
	{
		// Загружаем файл в OpenAI
		$fileId = $this->uploadFile($file, 'assistants');

		// Получаем информацию о загруженном файле
		$fileInfo = $this->getFileInfo($fileId);

		// Добавляем в Vector Store
		$vsId = Cache::get(self::VECTOR_STORE_CACHE);
		if (!$vsId) {
			$asst = $this->openai->assistants()->retrieve($this->assistantId);
			$existing = $asst->tool_resources['file_search']['vector_store_ids'] ?? [];
			$vsId = $existing[0] ?? null;
		}

		if (!$vsId) {
			// Создаем новый Vector Store если его нет
			$this->ensureVectorStore([$fileId]);
			Cache::forget(self::VECTOR_STORE_CACHE); // Очистим cache чтобы получить новый ID

			return [
				'file_id' => $fileId,
				'filename' => $fileInfo['filename'] ?? 'unknown',
				'size' => $fileInfo['bytes'] ?? 0,
				'size_mb' => round(($fileInfo['bytes'] ?? 0) / 1024 / 1024, 2),
				'status' => 'created_new_vector_store',
			];
		}

		Cache::forever(self::VECTOR_STORE_CACHE, $vsId);

		// Добавляем файл в существующий Vector Store
		$this->openai->vectorStores()->files()->create($vsId, ['file_id' => $fileId]);

		return [
			'file_id' => $fileId,
			'filename' => $fileInfo['filename'] ?? 'unknown',
			'size' => $fileInfo['bytes'] ?? 0,
			'size_mb' => round(($fileInfo['bytes'] ?? 0) / 1024 / 1024, 2),
			'status' => 'added',
		];
	}

	/**
	 * Добавить файл в Vector Store по ID (для обратной совместимости).
	 *
	 * @param string $fileId ID файла в OpenAI
	 * @return array Результат добавления файла
	 */
	public function addFileToVectorStoreById(string $fileId): array
	{
		$vsId = Cache::get(self::VECTOR_STORE_CACHE);
		if (!$vsId) {
			$asst = $this->openai->assistants()->retrieve($this->assistantId);
			$existing = $asst->tool_resources['file_search']['vector_store_ids'] ?? [];
			$vsId = $existing[0] ?? null;
		}

		if (!$vsId) {
			$this->ensureVectorStore([$fileId]);
			return ['status' => 'created_new_vector_store', 'file_id' => $fileId];
		}

		Cache::forever(self::VECTOR_STORE_CACHE, $vsId);

		$result = $this->openai->vectorStores()->files()->create($vsId, ['file_id' => $fileId])->toArray();

		// Получаем информацию о файле
		try {
			$fileInfo = $this->getFileInfo($fileId);
			$result['filename'] = $fileInfo['filename'] ?? 'unknown';
			$result['size'] = $fileInfo['bytes'] ?? 0;
		} catch (\Throwable) {
			// Игнорируем ошибки получения информации
		}

		return $result;
	}

	/**
	 * Удалить файл из Vector Store ассистента по ID.
	 *
	 * @param string $fileId ID файла в Vector Store
	 * @return array Результат удаления
	 */
	public function removeFileFromVectorStore(string $fileId): array
	{
		$vsId = Cache::get(self::VECTOR_STORE_CACHE);
		if (!$vsId) {
			$asst = $this->openai->assistants()->retrieve($this->assistantId);
			$existing = $asst->tool_resources['file_search']['vector_store_ids'] ?? [];
			$vsId = $existing[0] ?? null;
		}

		if (!$vsId) {
			throw new \RuntimeException("Vector Store not found");
		}

		Cache::forever(self::VECTOR_STORE_CACHE, $vsId);

		return $this->openai->vectorStores()->files()->delete($vsId, $fileId)->toArray();
	}

	/* ---------- УПРАВЛЕНИЕ ФАЙЛАМИ CODE INTERPRETER ---------- */

	/**
	 * Получить список файлов в Code Interpreter ассистента с полной информацией.
	 *
	 * @return array Массив файлов с названиями, размерами и метаданными
	 */
	public function getCodeInterpreterFiles(): array
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		$fileIds = $assistant->tool_resources['code_interpreter']['file_ids'] ?? [];

		if (empty($fileIds)) {
			return [];
		}

		// Обогащаем данными о каждом файле
		$enrichedFiles = [];
		foreach ($fileIds as $fileId) {
			try {
				$fileInfo = $this->getFileInfo($fileId);
				$enrichedFiles[] = [
					'id' => $fileId,
					'filename' => $fileInfo['filename'] ?? 'unknown',
					'size' => $fileInfo['bytes'] ?? 0,
					'size_mb' => round(($fileInfo['bytes'] ?? 0) / 1024 / 1024, 2),
					'created_at' => $fileInfo['created_at'] ?? null,
					'purpose' => $fileInfo['purpose'] ?? 'assistants',
					'status' => $fileInfo['status'] ?? 'unknown',
				];
			} catch (\Throwable $e) {
				// Если не удалось получить информацию о файле, добавляем базовые данные
				$enrichedFiles[] = [
					'id' => $fileId,
					'filename' => 'unknown',
					'size' => 0,
					'size_mb' => 0,
					'error' => 'Failed to retrieve file info',
				];
			}
		}

		return $enrichedFiles;
	}

	/**
	 * Добавить файл в Code Interpreter ассистента.
	 * Принимает файл, загружает его в OpenAI и добавляет в Code Interpreter.
	 *
	 * @param UploadedFile|string $file Загруженный файл или путь к файлу
	 * @return array{file_id: string, filename: string, size: int, status: string}
	 */
	public function addFileToCodeInterpreter(UploadedFile|string $file): array
	{
		// Загружаем файл в OpenAI
		$fileId = $this->uploadFile($file, 'assistants');

		// Получаем информацию о загруженном файле
		$fileInfo = $this->getFileInfo($fileId);

		// Добавляем в Code Interpreter
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		$currentIds = $assistant->tool_resources['code_interpreter']['file_ids'] ?? [];

		if (in_array($fileId, $currentIds, true)) {
			return [
				'file_id' => $fileId,
				'filename' => $fileInfo['filename'] ?? 'unknown',
				'size' => $fileInfo['bytes'] ?? 0,
				'size_mb' => round(($fileInfo['bytes'] ?? 0) / 1024 / 1024, 2),
				'status' => 'already_exists',
			];
		}

		$newIds = array_values(array_unique([...$currentIds, $fileId]));

		$this->updateAssistant([
			'tool_resources' => ['code_interpreter' => ['file_ids' => $newIds]],
		]);

		return [
			'file_id' => $fileId,
			'filename' => $fileInfo['filename'] ?? 'unknown',
			'size' => $fileInfo['bytes'] ?? 0,
			'size_mb' => round(($fileInfo['bytes'] ?? 0) / 1024 / 1024, 2),
			'status' => 'added',
		];
	}

	/**
	 * Добавить файл в Code Interpreter по ID (для обратной совместимости).
	 *
	 * @param string $fileId ID файла в OpenAI
	 * @return array Обновленные данные ассистента
	 */
	public function addFileToCodeInterpreterById(string $fileId): array
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		$currentIds = $assistant->tool_resources['code_interpreter']['file_ids'] ?? [];

		if (in_array($fileId, $currentIds, true)) {
			return $assistant->toArray(); // Файл уже добавлен
		}

		$newIds = array_values(array_unique([...$currentIds, $fileId]));

		return $this->updateAssistant([
			'tool_resources' => ['code_interpreter' => ['file_ids' => $newIds]],
		]);
	}

	/**
	 * Удалить файл из Code Interpreter ассистента по ID.
	 *
	 * @param string $fileId ID файла для удаления
	 * @return array Обновленные данные ассистента
	 */
	public function removeFileFromCodeInterpreter(string $fileId): array
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		$currentIds = $assistant->tool_resources['code_interpreter']['file_ids'] ?? [];

		$newIds = array_values(array_filter($currentIds, fn($id) => $id !== $fileId));

		return $this->updateAssistant([
			'tool_resources' => ['code_interpreter' => ['file_ids' => $newIds]],
		]);
	}

	/* ---------- ДИАЛОГ ---------- */

	/**
	 * Главная точка входа для диалога.
	 * @param UploadedFile|string|array<UploadedFile|string>|null $files
	 */
	public function ask(?string $prompt = null, UploadedFile|string|array|null $files = null, bool $saveThread = false): string
	{
		if (!$saveThread) {
			Cache::forget(self::THREAD_CACHE);
		}

		$this->syncReferenceFiles();
		$this->syncInstruction();

		$threadId   = $this->bootThread();
		$attachments = [];

		if ($files !== null) {
			foreach ((array) $files as $f) {
				$mime    = $this->getMime($f);
				$purpose = str_starts_with($mime, 'image/') ? 'vision' : 'assistants';
				$fileId  = $this->uploadFile($f, $purpose);
				$attachments[] = [$fileId, $mime];
			}
		}

		$this->attachOther($threadId, $prompt ?? '', $attachments);

		$startTime = microtime(true);
		toLog("🚀 Starting assistant run...");

		$run = $this->openai->threads()->runs()->create($threadId, ['assistant_id' => $this->assistantId]);

		$iteration = 0;
		do {
			usleep(1_000_000); // 1 секунда между проверками
			$iteration++;

			$run = $this->openai->threads()->runs()->retrieve($threadId, $run->id);
			$elapsed = round(microtime(true) - $startTime, 2);

			toLog("⏱️  [{$iteration}] Status: {$run->status} | Elapsed: {$elapsed}s");

			// Если run завершился с ошибкой
			if (in_array($run->status, ['failed', 'cancelled', 'expired'], true)) {
				$error = $run->last_error->message ?? 'Unknown error';
				throw new \RuntimeException("Assistant run failed: {$error}");
			}
		} while (!in_array($run->status, ['completed'], true));

		$totalTime = round(microtime(true) - $startTime, 2);
		toLog("✅ Run completed in {$totalTime}s. Fetching messages...");

		$answer = $this->fetchLastAssistantText($threadId);

		toLog("📝 Answer length: " . strlen($answer) . " chars");

		if (!$saveThread) {
			Cache::forget(self::THREAD_CACHE);
			try {
				$this->openai->threads()->delete($threadId);
			} catch (\Throwable) {
			}
		}

		return $answer;
	}

	/* ---------- ВНУТРЕННЯЯ РАБОТА ---------- */

	private function syncReferenceFiles(): void
	{
		$cached = Cache::get(self::FILE_MAP_CACHE, []);
		$new = [];
		$vector = [];
		$code = [];

		toLog("📂 Scanning directory: {$this->dir}");
		$files = $this->disk->files($this->dir);
		toLog("📄 Found files: " . json_encode($files));

		foreach ($files as $path) {
			if (basename($path) === basename($this->instructionFile)) {
				toLog("⏭️  Skipping instruction file: " . basename($path));
				continue;
			}

			$hash = md5_file($this->disk->path($path));
			$mime = mime_content_type($this->disk->path($path));
			$cat = $this->classifyMime($mime);

			toLog("🔍 Processing file: {$path} | MIME: {$mime} | Category: {$cat}");

			$entry = $cached[$path] ?? null;
			if (!$entry || $entry['hash'] !== $hash) {
				$purpose = $cat === 'other' && str_starts_with($mime, 'image/') ? 'vision' : 'assistants';

				// Получаем оригинальное имя файла из БД
				$systemFilename = basename($path);
				$originalFilename = \DB::table('assistent_files')
					->where('filename_sys', $systemFilename)
					->value('filename_orig') ?? $systemFilename;

				// Создаем временный файл с оригинальным именем для загрузки в OpenAI
				$tmpDir = sys_get_temp_dir();
				$tmpPath = $tmpDir . DIRECTORY_SEPARATOR . $originalFilename;
				copy($this->disk->path($path), $tmpPath);

				// Логируем содержимое файла перед загрузкой
				$fileContent = file_get_contents($tmpPath);
				toLog("📄 Uploading file '{$originalFilename}' | Size: " . strlen($fileContent) . " bytes | Content: " . substr($fileContent, 0, 200));

				$fileId = $this->openai->files()->upload([
					'purpose' => $purpose,
					'file' => fopen($tmpPath, 'r'),
				])->id;

				toLog("✅ File uploaded to OpenAI: {$fileId}");

				unlink($tmpPath);

				$entry = ['id' => $fileId, 'hash' => $hash, 'mime' => $mime, 'cat' => $cat];
				Cache::forget(self::THREAD_CACHE);
			}

			match ($cat) {
				'vector' => $vector[] = $entry['id'],
				'code' => $code[] = $entry['id'],
				default => null,
			};

			$new[$path] = $entry;
		}

		$deleted = array_diff_key($cached, $new);
		foreach ($deleted as $d) {
			try {
				$this->openai->files()->delete($d['id']);
			} catch (\Throwable) {
			}
		}

		Cache::forever(self::FILE_MAP_CACHE, $new);

		toLog("📊 Summary - Vector files: " . count($vector) . " | Code files: " . count($code));

		if ($vector) {
			toLog("📦 Adding files to Vector Store: " . json_encode($vector));
			$this->ensureVectorStore($vector);
		}
		if ($code) {
			toLog("💻 Adding files to Code Interpreter: " . json_encode($code));
			$this->ensureCodeInterpreter($code);
		}
	}

	private function syncInstruction(): void
	{
		if (!Storage::exists($this->instructionFile)) {
			return;
		}
		$local = str_replace(["\r\n", "\r"], "\n", Storage::get($this->instructionFile));
		$remote = $this->openai->assistants()->retrieve($this->assistantId)->instructions ?? '';
		if ($local !== $remote) {
			$this->openai->assistants()->modify($this->assistantId, ['instructions' => $local]);
			Cache::forget(self::THREAD_CACHE);
		}
	}

	private function bootThread(): string
	{
		return Cache::rememberForever(self::THREAD_CACHE, function () {
			$thread = $this->openai->threads()->create([]);

			$entries = array_values(Cache::get(self::FILE_MAP_CACHE, []));
			$other = array_filter($entries, fn($e) => $e['cat'] === 'other');

			if ($other) {
				$attachments = [];
				foreach ($other as $o) {
					if (str_starts_with($o['mime'], 'image/')) {
						$attachments[] = ['kind' => 'image_file', 'id' => $o['id'], 'mime' => $o['mime']];
					} else {
						$attachments[] = ['kind' => 'file', 'id' => $o['id'], 'mime' => $o['mime']];
					}
				}
				$this->attachOther($thread->id, '', $attachments);
			}

			return $thread->id;
		});
	}

	/**
	 * @param array<int, array{0|string,1|string}|array{id:string,mime:string}|array> $attachments
	 */
	private function attachOther(string $threadId, string $text, array $attachments): void
	{
		$content = [];
		$attachArr = [];

		if ($text !== '') {
			$content[] = ['type' => 'text', 'text' => $text];
		}

		foreach ($attachments as $att) {
			[$fileId, $mime] = is_array($att) && isset($att['id']) ? [$att['id'], $att['mime']] : $att;
			if (str_starts_with($mime, 'image/')) {
				$content[] = ['type' => 'image_file', 'image_file' => ['file_id' => $fileId]];
			} else {
				$attachArr[] = ['file_id' => $fileId];
			}
		}

		if ($content === [] && $attachArr === []) {
			return;
		}

		$this->openai->threads()->messages()->create($threadId, [
			'role' => 'user',
			'content' => $content ?: [['type' => 'text', 'text' => 'Посмотри вложения']],
			'attachments' => $attachArr ?: null,
		]);
	}

	/* ---------- FILE HELPERS ---------- */

	private function fetchLastAssistantText(string $threadId): string
	{
		$list = $this->openai->threads()
			->messages()
			->list($threadId, ['limit' => 100, 'order' => 'desc'])
			->data;

		$allTexts = [];

		foreach ($list as $msg) {
			if ($msg->role !== 'assistant') {
				continue;
			}

			toLog(['Raw message content' => $msg->content]);

			foreach ($msg->content as $part) {
				if ($part->type === 'text' && trim($part->text->value) !== '') {
					$allTexts[] = trim($part->text->value);
				}
			}
		}

		// Логируем все сообщения для отладки
		toLog(['All assistant messages' => $allTexts]);

		// Возвращаем последнее (самое свежее) сообщение
		// Если нужно вернуть ВСЕ - используй implode("\n\n", array_reverse($allTexts))
		return $allTexts[0] ?? 'Ассистент не вернул текстового ответа.';
	}

	private function classifyMime(string $mime): string
	{
		return in_array($mime, self::VECTOR_MIME, true) ? 'vector'
			: (in_array($mime, self::CODE_MIME, true) ? 'code' : 'other');
	}

	private function getMime(string|UploadedFile $file): string
	{
		$path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
		return is_file($path) ? mime_content_type($path) : Http::head($file)->header('Content-Type');
	}

	private function uploadFile(string|UploadedFile $file, string $purpose = 'assistants'): string
	{
		if ($file instanceof UploadedFile || is_file($file)) {
			$real = $file instanceof UploadedFile ? $file->getRealPath() : $file;
			return $this->openai->files()->upload([
				'purpose' => $purpose,
				'file' => fopen($real, 'r'),
			])->id;
		}

		$binary = Http::get($file)->throw()->body();
		$ext = pathinfo(parse_url($file, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'dat';
		$tmp = tempnam(sys_get_temp_dir(), 'file_') . ".{$ext}";
		file_put_contents($tmp, $binary);
		$id = $this->openai->files()->upload([
			'purpose' => $purpose,
			'file' => fopen($tmp, 'r'),
		])->id;
		toLog($id);
		unlink($tmp);
		return $id;
	}

	/* ---------- VECTOR STORE / CODE INTERPRETER ---------- */

	private function ensureVectorStore(array $fileIds): void
	{
		$vsId = Cache::get(self::VECTOR_STORE_CACHE);
		$cacheWasEmpty = !$vsId;

		if (!$vsId) {
			$asst = $this->openai->assistants()->retrieve($this->assistantId);
			$existing = $asst->tool_resources['file_search']['vector_store_ids'] ?? [];
			$vsId = $existing[0] ?? null;
		}

		if (!$vsId) {
			// Создаем новый Vector Store
			toLog("🆕 Creating new Vector Store");
			$vs = $this->openai->vectorStores()->create(['file_ids' => $fileIds, 'name' => 'VS_' . $this->assistantId]);
			$vsId = $vs->id;
			$this->openai->assistants()->modify($this->assistantId, [
				'tool_resources' => ['file_search' => ['vector_store_ids' => [$vsId]]],
			]);

			// Ждем завершения индексации
			$this->waitVectorStoreReady($vsId);
		} elseif ($cacheWasEmpty) {
			// Кеш был очищен принудительно - пересоздаем Vector Store с новыми файлами
			toLog("♻️  Cache was cleared - recreating Vector Store");

			// Удаляем старый Vector Store
			try {
				$this->openai->vectorStores()->delete($vsId);
				toLog("🗑️  Old Vector Store deleted: {$vsId}");
			} catch (\Throwable $e) {
				toLog("⚠️  Failed to delete old Vector Store: " . $e->getMessage());
			}

			// Создаем новый
			$vs = $this->openai->vectorStores()->create(['file_ids' => $fileIds, 'name' => 'VS_' . $this->assistantId]);
			$vsId = $vs->id;
			toLog("✅ New Vector Store created: {$vsId}");

			$this->openai->assistants()->modify($this->assistantId, [
				'tool_resources' => ['file_search' => ['vector_store_ids' => [$vsId]]],
			]);

			// Ждем завершения индексации
			$this->waitVectorStoreReady($vsId);
		} else {
			// Кеш есть - просто добавляем файлы
			toLog("➕ Adding files to existing Vector Store");
			foreach ($fileIds as $fid) {
				try {
					$this->openai->vectorStores()->files()->create($vsId, ['file_id' => $fid]);
				} catch (\Throwable) {
				}
			}

			// Ждем завершения индексации новых файлов
			$this->waitVectorStoreReady($vsId);
		}

		Cache::forever(self::VECTOR_STORE_CACHE, $vsId);
	}

	/**
	 * Ожидает завершения индексации файлов в Vector Store.
	 *
	 * @param string $vsId ID Vector Store
	 * @return void
	 */
	private function waitVectorStoreReady(string $vsId): void
	{
		$maxAttempts = 30; // 30 секунд максимум
		$attempt = 0;

		toLog("⏳ Waiting for Vector Store indexing...");

		while ($attempt < $maxAttempts) {
			$vs = $this->openai->vectorStores()->retrieve($vsId);
			$status = $vs->status ?? 'unknown';

			toLog("📊 Vector Store status: {$status} (attempt {$attempt}/{$maxAttempts})");

			if ($status === 'completed') {
				toLog("✅ Vector Store is ready!");
				return;
			}

			if (in_array($status, ['failed', 'cancelled', 'expired'], true)) {
				toLog("❌ Vector Store indexing failed with status: {$status}");
				throw new \RuntimeException("Vector Store indexing failed: {$status}");
			}

			sleep(1);
			$attempt++;
		}

		toLog("⚠️  Vector Store indexing timeout after {$maxAttempts} seconds");
	}

	private function ensureCodeInterpreter(array $fileIds): void
	{
		$assistant = $this->openai->assistants()->retrieve($this->assistantId);
		$tools = $assistant->tools;
		$hasCI = collect($tools)->contains(fn($t) => ($t['type'] ?? null) === 'code_interpreter');
		if (!$hasCI) {
			$tools[] = ['type' => 'code_interpreter'];
		}

		$currentIds = $assistant->tool_resources['code_interpreter']['file_ids'] ?? [];
		$newIds = array_values(array_unique(array_merge($currentIds, $fileIds)));

		$this->openai->assistants()->modify($this->assistantId, [
			'tools' => $tools,
			'tool_resources' => ['code_interpreter' => ['file_ids' => $newIds]],
		]);
	}
}
