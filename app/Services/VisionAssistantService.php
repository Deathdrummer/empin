<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class VisionAssistantService
{
	private string $assistantId;
	private array  $referenceFiles;
	private string $instruction;

	private const ASSISTANT_CACHE = 'openai.vision.assistant_id';
	private const THREAD_CACHE    = 'openai.vision.thread_id';

	public function __construct()
	{
		// 1) load config
		$this->referenceFiles = config('openai.image_files', []);
		$this->instruction    = config('openai.vision_instruction', '');

		// 2) get / create assistant (after $instruction is set!)
		$this->assistantId = config('openai.assistant_id');
	}

	/* -----------------------------------------------------------------
	 |  Public API
	 | ---------------------------------------------------------------- */
	/**
	 * Ask the vision assistant a question.
	 *
	 * @throws RequestException
	 */
	public function ask(string $prompt, ?string $imageUrl = null): string
	{
		$threadId   = $this->bootThread();
		$attachments = $imageUrl ? [$this->uploadTempImage($imageUrl)] : [];

		$this->createUserMessage($threadId, $prompt, $attachments);

		$run = OpenAI::threads()->runs()->create($threadId, [
			'assistant_id' => $this->assistantId,
		]);

		// wait for completion
		do {
			usleep(300_000);                                   // 0.3 s
			$run = OpenAI::threads()->runs()->retrieve($threadId, $run->id);
		} while (in_array($run->status, ['queued', 'in_progress']));

		$latest = OpenAI::threads()->messages()->list($threadId, ['limit' => 1]);

		return $latest->data[0]->content[0]->text->value;
	}

	/* -----------------------------------------------------------------
	 |  Thread bootstrap (runs once)
	 | ---------------------------------------------------------------- */
	private function bootThread(): string
	{
		return Cache::rememberForever(self::THREAD_CACHE, function () {
			$thread = OpenAI::threads()->create([]);

			// Send the 20 reference images (max 10 per message)
			foreach (array_chunk($this->referenceFiles, 10) as $chunk) {
				$this->createUserMessage($thread->id, '', $chunk);
			}

			return $thread->id;
		});
	}

	/* -----------------------------------------------------------------
	 |  Helpers
	 | ---------------------------------------------------------------- */
	private function createUserMessage(string $threadId, string $text, array $fileIds): void
	{
		$firstBatchFree = $text === '' ? 10 : 9;
		$chunks         = array_chunk($fileIds, $firstBatchFree);

		foreach ($chunks as $index => $chunk) {
			$content = [];

			if ($index === 0 && $text !== '') {
				$content[] = ['type' => 'text', 'text' => $text];
			}

			foreach ($chunk as $id) {
				$content[] = ['type' => 'image_file', 'image_file' => ['file_id' => $id]];
			}

			OpenAI::threads()->messages()->create($threadId, [
				'role'    => 'user',
				'content' => $content,
			]);
		}
	}

	/**
	 * Download an image from a URL, save it with a valid extension,
	 * upload to OpenAI, return the resulting file_id.
	 *
	 * @throws RequestException
	 */
	private function uploadTempImage(string $url): string
	{
		// ----- download -------------------------------------------------
		$binary   = Http::get($url)->throw()->body();
		$pathInfo = pathinfo(parse_url($url, PHP_URL_PATH) ?? '');
		$ext      = strtolower($pathInfo['extension'] ?? 'png');

		// sanitize / default
		if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
			$ext = 'png';
		}

		// ----- save to a temp file with correct extension ---------------
		$tempPath = tempnam(sys_get_temp_dir(), 'img_');
		$finalPath = "{$tempPath}.{$ext}";
		rename($tempPath, $finalPath);          // ensures extension
		file_put_contents($finalPath, $binary);

		// ----- upload ---------------------------------------------------
		$res = OpenAI::files()->upload([
			'purpose' => 'vision',
			'file'    => fopen($finalPath, 'r'),
		]);

		// cleanup
		unlink($finalPath);

		return $res->id;
	}

	/**
	 * Create the assistant once and return its ID.
	 */
	private function createAssistant(): string
	{
		$assistant = OpenAI::assistants()->create([
			'model'        => 'gpt-4o',
			'instructions' => $this->instruction,
		]);

		return $assistant->id;
	}
}
