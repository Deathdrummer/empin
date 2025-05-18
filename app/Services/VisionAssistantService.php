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

	private string $assistantId;
	private string $dir;
	private string $instructionFile;
	private ?string $contextInstructionFile;
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

	public function __construct(
		?string $assistantId = null,
		?string $instructionFile = null,
		?string $dir = null,
		?string $contextInstructionFile = null,
	)
	{
		$this->assistantId = $assistantId ?? config('openai.assistant_id');
		$this->instructionFile = $instructionFile ?? 'prompts/plan.txt';
		$this->dir = $dir ?? 'assistent';
		$this->contextInstructionFile = $contextInstructionFile;
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
				'timeout' => 60,
			]))
			->make();
	}

	public function use(
		string  $assistantId,
		?string $instructionFile = null,
		?string $dir = null,
		?string $contextInstructionFile = null,
	): static
	{
		$this->assistantId = $assistantId;
		$this->instructionFile = $instructionFile ?? $this->instructionFile;
		$this->dir = $dir ?? $this->dir;
		$this->contextInstructionFile = $contextInstructionFile ?? $this->contextInstructionFile;

		Cache::forget(self::THREAD_CACHE);
		Cache::forget(self::FILE_MAP_CACHE);
		Cache::forget(self::VECTOR_STORE_CACHE);

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

		$run = $this->openai->threads()->runs()->create($threadId, ['assistant_id' => $this->assistantId]);
		do {
			usleep(300_000);
			$run = $this->openai->threads()->runs()->retrieve($threadId, $run->id);
		} while ($run->status !== 'completed');

		$answer = $this->fetchLastAssistantText($threadId);

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

		foreach ($this->disk->files($this->dir) as $path) {
			if (basename($path) === basename($this->instructionFile)) {
				continue;
			}

			$hash = md5_file($this->disk->path($path));
			$mime = mime_content_type($this->disk->path($path));
			$cat = $this->classifyMime($mime);

			$entry = $cached[$path] ?? null;
			if (!$entry || $entry['hash'] !== $hash) {
				$purpose = $cat === 'other' && str_starts_with($mime, 'image/') ? 'vision' : 'assistants';
				$fileId = $this->openai->files()->upload([
					'purpose' => $purpose,
					'file' => fopen($this->disk->path($path), 'r'),
				])->id;

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

		if ($vector) {
			$this->ensureVectorStore($vector);
		}
		if ($code) {
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

			$context = $this->contextInstructionFile && Storage::exists($this->contextInstructionFile)
				? trim(Storage::get($this->contextInstructionFile))
				: '';

			if ($other || $context !== '') {
				$attachments = [];
				foreach ($other as $o) {
					if (str_starts_with($o['mime'], 'image/')) {
						$attachments[] = ['kind' => 'image_file', 'id' => $o['id'], 'mime' => $o['mime']];
					} else {
						$attachments[] = ['kind' => 'file', 'id' => $o['id'], 'mime' => $o['mime']];
					}
				}
				$this->attachOther($thread->id, $context, $attachments);
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
			->list($threadId, ['limit' => 20, 'order' => 'desc'])
			->data;

		foreach ($list as $msg) {
			if ($msg->role !== 'assistant') {
				continue;
			}

			foreach ($msg->content as $part) {
				if ($part->type === 'text' && trim($part->text->value) !== '') {
					return trim($part->text->value);
				}
			}
		}

		return 'Ассистент не вернул текстового ответа.';
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
		if (!$vsId) {
			$asst = $this->openai->assistants()->retrieve($this->assistantId);
			$existing = $asst->tool_resources['file_search']['vector_store_ids'] ?? [];
			$vsId = $existing[0] ?? null;
		}

		if (!$vsId) {
			$vs = $this->openai->vectorStores()->create(['file_ids' => $fileIds, 'name' => 'VS_' . $this->assistantId]);
			$vsId = $vs->id;
			$this->openai->assistants()->modify($this->assistantId, [
				'tool_resources' => ['file_search' => ['vector_store_ids' => [$vsId]]],
			]);
		} else {
			foreach ($fileIds as $fid) {
				try {
					$this->openai->vectorStores()->files()->create($vsId, ['file_id' => $fid]);
				} catch (\Throwable) {
				}
			}
		}

		Cache::forever(self::VECTOR_STORE_CACHE, $vsId);
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
