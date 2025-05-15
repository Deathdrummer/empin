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

	private string $assistantId;
	private string $dir;
	private string $instructionFile;
	private \Illuminate\Contracts\Filesystem\Filesystem $disk;
	private \OpenAI\Client $openai;

	public function __construct(
		?string $assistantId = null,
		?string $instructionFile = null,
		?string $dir = null
	) {
		$this->assistantId = $assistantId ?? config('openai.assistant_id');
		$this->instructionFile = $instructionFile ?? 'prompts/plan.txt';
		$this->dir = $dir ?? 'assistent';
		$this->disk = Storage::disk('local');

		$this->openai = \OpenAI::factory()
			->withApiKey(config('openai.api_key'))
			->withHttpClient(new GuzzleClient([
				'headers' => [
					'OpenAI-Beta' => 'assistants=v2',
				],
				'proxy' => [
					'http' => config('openai.proxy_url'),
					'https' => config('openai.proxy_url'),
				],
				'verify' => false,
				'timeout' => 60,
			]))
			->make();
	}

	public function use(string $assistantId, ?string $instructionFile = null, ?string $dir = null): static
	{
		$this->assistantId = $assistantId;
		if ($instructionFile) {
			$this->instructionFile = $instructionFile;
		}
		if ($dir) {
			$this->dir = $dir;
		}
		Cache::forget(self::THREAD_CACHE);
		Cache::forget(self::FILE_MAP_CACHE);
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

	public function ask(?string $prompt = null, UploadedFile|string|array|null $files = null): string
	{
		$this->syncReferenceFiles();
		$this->syncInstruction();

		$threadId = $this->bootThread();

		$attachments = [];
		if ($files !== null) {
			$files = is_array($files) ? $files : [$files];
			foreach ($files as $file) {
				$kind = $this->detectFileKind($file);
				$attachments[] = [
					'id' => $this->uploadFile($file, $kind),
					'kind' => $kind,
				];
			}
		}

		$this->createUserMessage($threadId, $prompt ?? '', $attachments);

		$run = $this->openai->threads()->runs()->create($threadId, ['assistant_id' => $this->assistantId]);

		do {
			usleep(300_000);
			$run = $this->openai->threads()->runs()->retrieve($threadId, $run->id);
		} while (in_array($run->status, ['queued', 'in_progress']));

		$latest = $this->openai->threads()->messages()->list($threadId, ['limit' => 1]);

		return $latest->data[0]->content[0]->text->value;
	}

	private function syncReferenceFiles(): void
	{
		$current = Cache::get(self::FILE_MAP_CACHE, []);
		$updated = [];

		foreach ($this->disk->files($this->dir) as $path) {
			if (basename($path) === basename($this->instructionFile)) {
				continue;
			}

			$full = $this->disk->path($path);
			$hash = md5_file($full);
			$kind = $this->detectFileKind($full);
			$entry = $current[$path] ?? null;

			if (!$entry || $entry['hash'] !== $hash) {
				$id = $this->openai->files()->upload([
					'purpose' => $kind === 'image_file' ? 'vision' : 'assistants',
					'file' => fopen($full, 'r'),
				])->id;
				$entry = ['hash' => $hash, 'id' => $id, 'kind' => $kind];
				Cache::forget(self::THREAD_CACHE);
			}

			$updated[$path] = $entry;
		}

		$deleted = array_diff_key($current, $updated);
		foreach ($deleted as $old) {
			try {
				$this->openai->files()->delete($old['id']);
			} catch (\Throwable) {
			}
		}

		if ($deleted) {
			Cache::forget(self::THREAD_CACHE);
		}

		Cache::forever(self::FILE_MAP_CACHE, $updated);
	}

	private function syncInstruction(): void
	{
		if (!Storage::exists($this->instructionFile)) {
			return;
		}

		$local = str_replace(["\r\n", "\r"], "\n", Storage::get($this->instructionFile));

		$remote = $this->openai->assistants()->retrieve($this->assistantId)->instructions ?? '';

		if ($local !== $remote) {
			$this->openai->assistants()->modify($this->assistantId, [
				'instructions' => $local,
			]);
			Cache::forget(self::THREAD_CACHE);
		}
	}

	private function bootThread(): string
	{
		return Cache::rememberForever(self::THREAD_CACHE, function () {
			$thread = $this->openai->threads()->create([]);
			$entries = array_values(Cache::get(self::FILE_MAP_CACHE, []));
			foreach (array_chunk($entries, 10) as $chunk) {
				$this->createUserMessage($thread->id, '', $chunk, 'assistant');
			}
			return $thread->id;
		});
	}

	private function createUserMessage(
		string $threadId,
		string $text,
		array  $attachments,
		string $role = 'user'
	): void {
		$content   = [];
		$attaches  = [];

		if ($text !== '') {
			$content[] = ['type' => 'text', 'text' => $text];
		}

		foreach ($attachments as $att) {
			if ($att['kind'] === 'image_file') {
				$content[] = [
					'type'       => 'image_file',
					'image_file' => ['file_id' => $att['id']],
				];
			} else {
				$attaches[] = [
					'file_id' => $att['id'],
					'tools'   => [['type' => 'file_search']],
				];
			}
		}

		if ($content === []) {
			$content[] = ['type' => 'text', 'text' => 'Ответь по вложенным файлам'];
		}

		$this->openai->threads()->messages()->create($threadId, [
			'role'        => $role,
			'content'     => $content,
			'attachments' => $attaches ?: null,
		]);
	}


	private function uploadFile(string|UploadedFile $file, string $kind): string
	{
		if ($file instanceof UploadedFile || is_file($file)) {
			$path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
			return $this->openai->files()->upload([
				'purpose' => $kind === 'image_file' ? 'vision' : 'assistants',
				'file' => fopen($path, 'r'),
			])->id;
		}

		$binary = Http::get($file)->throw()->body();
		$ext = pathinfo(parse_url($file, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'dat';
		$tmp = tempnam(sys_get_temp_dir(), 'file_') . ".$ext";
		file_put_contents($tmp, $binary);

		$id = $this->openai->files()->upload([
			'purpose' => $kind === 'image_file' ? 'vision' : 'assistants',
			'file' => fopen($tmp, 'r'),
		])->id;
		unlink($tmp);

		return $id;
	}

	private function detectFileKind(string|UploadedFile $file): string
	{
		$path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
		$mime = is_file($path) ? mime_content_type($path) : Http::head($file)->header('Content-Type');
		return str_starts_with($mime, 'image/') ? 'image_file' : 'file';
	}
}
