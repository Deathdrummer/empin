<?php
// app/Services/OpenAIChatService.php
namespace App\Services;

use App\Models\OpenAIThread;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIChatService
{
	private string $assistantId;
	private array  $staticImages;
	private string $staticInstruction;

	public function __construct()
	{
		$this->assistantId       = config('openai.assistant_id');
		$this->staticImages      = config('openai.images');
		$this->staticInstruction = config('openai.instruction');
	}

	public function ask(string $prompt, ?string $imageUrl = null): string
	{
		$threadId = $this->getThreadId();

		$content = [
			['type' => 'text', 'text' => $prompt],
		];

		if ($imageUrl) {
			$response = Http::get($imageUrl);
			$tmpPath  = sys_get_temp_dir().'/openai_'.uniqid().'.png';
			file_put_contents($tmpPath, $response->body());

			$stream = fopen($tmpPath, 'rb');
			if ($stream === false) {
				throw new \RuntimeException("Unable to open temporary file: {$tmpPath}");
			}

			$file = OpenAI::files()->upload([
				'purpose' => 'vision',
				'file'    => $stream,
			]);


			$content[] = [
				'type'       => 'image_file',
				'image_file' => ['file_id' => $file->id],
			];
		}

		// send the user message
		OpenAI::threads()->messages()->create($threadId, [
			'role'    => 'user',
			'content' => $content,
		]);

		// run the assistant
		$run = OpenAI::threads()->runs()->create($threadId, [
			'assistant_id' => $this->assistantId,
		]);

		// simple polling loop
		while (($status = $run->status) !== 'completed') {
			sleep(1);
			if ($status === 'failed' || $status === 'requires_action') {
				// Проверяем наличие информации об ошибке перед ее использованием
				$errorMessage = 'Unknown error during run execution.'; // Сообщение по умолчанию
				if (isset($run->last_error)) {
					// Пытаемся получить сообщение, если оно есть
					if (isset($run->last_error->message) && $run->last_error->message !== null) {
						$errorMessage = $run->last_error->message;
					}
					// Иначе пытаемся получить код ошибки, если он есть
					elseif (isset($run->last_error->code) && $run->last_error->code !== null) {
						$errorMessage = 'Error code: ' . $run->last_error->code;
					}
				}
				// Добавляем статус к сообщению для большей информативности
				throw new \RuntimeException("Run status: {$status}. Error: {$errorMessage}");
			}
			$run = OpenAI::threads()->runs()->retrieve($threadId, $run->id);
		}


		$messages = OpenAI::threads()->messages()->list($threadId, ['limit' => 1]);
		return $messages->data[0]->content[0]->text->value ?? '';
	}

	/** Create or fetch the single persistent vision thread */
	private function getThreadId(): string
	{
		if ($existing = OpenAIThread::first()) {
			return $existing->thread_id;
		}

		$thread = OpenAI::threads()->create([]);

		// 1️⃣ instruction message
		OpenAI::threads()->messages()->create($thread->id, [
			'role'    => 'user',
			'content' => [
				['type' => 'text', 'text' => $this->staticInstruction],
			],
		]);

		// 2️⃣ reference images, max 10 per message
		foreach (array_chunk($this->staticImages, 10) as $chunk) {
			$content = array_map(
				fn(string $fileId) => [
					'type'       => 'image_file',
					'image_file' => ['file_id' => $fileId],
				],
				$chunk
			);

			OpenAI::threads()->messages()->create($thread->id, [
				'role'    => 'user',
				'content' => $content,
			]);
		}

		OpenAIThread::create(['thread_id' => $thread->id]);

		return $thread->id;
	}
}
