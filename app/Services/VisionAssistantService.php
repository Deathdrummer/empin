<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\{Cache, Http, Storage};
use OpenAI\Laravel\Facades\OpenAI;
use GuzzleHttp\Client as GuzzleClient;
/**
 * Vision‑assistant gateway с авто‑синком инструкций и референс‑файлов.
 *
 * Алгоритм:
 *   • При первом обращении сканируем storage/openai/vision для инструкций и медиа.
 *   • Для изменившихся файлов считаем hash, перезаливаем на OpenAI и кешируем id.
 *   • Если текст инструкции изменён — апдейтим ассистента.
 *   • Поток перезапускаем, когда что‑то меняется.
 *   • ask() принимает prompt и либо URL‑картинку, либо UploadedFile|path.
 */
class VisionAssistantService
{
	public const THREAD_CACHE           = 'openai.vision.thread_id';
	private const FILE_MAP_CACHE        = 'openai.vision.file_map';
	private const INSTRUCTION_HASH_CACHE = 'openai.vision.instruction_hash';

	private string $assistantId;
	private string $dir;
	private string $instructionFile;
	private \Illuminate\Contracts\Filesystem\Filesystem $disk;
	private \OpenAI\Client $openai;
	
	public function __construct()
	{
		$this->assistantId     = config('openai.assistant_id');
		$this->disk = Storage::disk('local');
		$this->dir  = 'assistent';
		$this->instructionFile = 'prompts/plan.txt';

		$this->openai = \OpenAI::factory()
			->withApiKey(config('openai.api_key'))
			->withHttpClient(new GuzzleClient([
				'headers' => [
					'OpenAI-Beta' => 'assistants=v2',
				],
				'proxy'  => [
					'http'  => config('openai.proxy_url'),
					'https' => config('openai.proxy_url'),
				],
				'verify'  => false,
				'timeout' => 60,
			]))
			->make();
	}

	/**
	 * Запрашивает ответ ассистента.
	 *
	 * @throws RequestException
	 */
	public function ask(string $prompt, string|UploadedFile|null $image = null): string
	{
		$this->syncReferenceFiles();
		$this->syncInstruction();

		$threadId   = $this->bootThread();
		$attachments = $image ? [$this->uploadImage($image)] : [];

		$this->createUserMessage($threadId, $prompt, $attachments);

		$run = $this->openai->threads()->runs()->create($threadId, ['assistant_id' => $this->assistantId]);

		do {
			usleep(300_000);
			$run = $this->openai->threads()->runs()->retrieve($threadId, $run->id);
		} while (in_array($run->status, ['queued', 'in_progress']));

		$latest = $this->openai->threads()->messages()->list($threadId, ['limit' => 1]);

		return $latest->data[0]->content[0]->text->value;
	}
	
	/**
	 * Синхронизирует reference‑файлы: добавляет новые, обновляет изменённые, удаляет старые.
	 */
	private function syncReferenceFiles(): void
	{
		$current = Cache::get(self::FILE_MAP_CACHE, []);
		$updated = [];

		foreach ($this->disk->files($this->dir) as $path) {
			if (basename($path) === 'instruction.txt') {
				continue;
			}

			$full  = $this->disk->path($path);
			$hash  = md5_file($full);
			$entry = $current[$path] ?? null;

			if (!$entry || $entry['hash'] !== $hash) {
				$id    = $this->openai->files()->upload([
					'purpose' => 'vision',
					'file'    => fopen($full, 'r'),
				])->id;
				$entry = ['hash' => $hash, 'id' => $id];
				Cache::forget(self::THREAD_CACHE);
			}

			$updated[$path] = $entry;
		}

		$deleted = array_diff_key($current, $updated);
		if ($deleted) {
			foreach ($deleted as $old) {
				try {
					$this->openai->files()->delete($old['id']);
				} catch (\Throwable) {
					// если файл уже удалён вручную в панели, молча игнорируем
				}
			}
			Cache::forget(self::THREAD_CACHE);
		}

		Cache::forever(self::FILE_MAP_CACHE, $updated);
	}

	
	private function syncInstruction(): void
	{
		if (!is_file($this->instructionFile)) {
			return;
		}

		$local = Storage::exists($this->instructionFile) ? Storage::get($this->instructionFile) : '';
		$local = str_replace(["\r\n", "\r"], "\n", $local);

		$remote = $this->openai->assistants()
			->retrieve($this->assistantId)
			->instructions ?? '';
		if ($local !== $remote) {
			$this->openai->assistants()->modify($this->assistantId, [
				'instructions' => $local,
			]);

			Cache::forget(self::THREAD_CACHE);
		}
	}

	/**
	 * Создаёт поток (или возвращает кешированный) и прикрепляет reference‑файлы.
	 */
	private function bootThread(): string
	{
		return Cache::rememberForever(self::THREAD_CACHE, function () {
			$thread = $this->openai->threads()->create([]);
			$ids    = array_column(Cache::get(self::FILE_MAP_CACHE, []), 'id');
			foreach (array_chunk($ids, 10) as $chunk) {
				$this->createUserMessage($thread->id, '', $chunk);
			}
			return $thread->id;
		});
	}

	/**
	 * Пушит message в поток.
	 */
	private function createUserMessage(string $threadId, string $text, array $fileIds): void
	{
		$firstBatchFree = $text === '' ? 10 : 9;
		foreach (array_chunk($fileIds, $firstBatchFree) as $index => $chunk) {
			$content = [];
			if ($index === 0 && $text !== '') {
				$content[] = ['type' => 'text', 'text' => $text];
			}
			foreach ($chunk as $id) {
				$content[] = ['type' => 'image_file', 'image_file' => ['file_id' => $id]];
			}
			$this->openai->threads()->messages()->create($threadId, ['role' => 'user', 'content' => $content]);
		}
	}

	/**
	 * Заливает картинку (URL или локальную) и возвращает file_id.
	 *
	 * @throws RequestException
	 */
	private function uploadImage(string|UploadedFile $image): string
	{
		if ($image instanceof UploadedFile || is_file($image)) {
			$path = $image instanceof UploadedFile ? $image->getRealPath() : $image;
			return $this->openai->files()->upload(['purpose' => 'vision', 'file' => fopen($path, 'r')])->id;
		}

		$binary    = Http::get($image)->throw()->body();
		$extension = pathinfo(parse_url($image, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'png';
		$tempPath  = tempnam(sys_get_temp_dir(), 'img_') . ".{$extension}";
		file_put_contents($tempPath, $binary);

		$id = $this->openai->files()->upload(['purpose' => 'vision', 'file' => fopen($tempPath, 'r')])->id;
		unlink($tempPath);

		return $id;
	}
}
