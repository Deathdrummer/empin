<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Сервис для взаимодействия с ассистентом видения OpenAI.
 *
 * Класс предоставляет функциональность для создания потоков, отправки сообщений
 * от имени пользователя, прикрепления изображений и выполнения задач с использованием
 * ассистента видения OpenAI. Реализована обработка файлов и управление идентификатором
 * текущего потока через кэш.
 */
class VisionAssistantService
{
	private string $assistantId;
	private array $referenceFiles;
	private string $instruction;

	private const THREAD_CACHE = 'openai.vision.thread_id';

	public function __construct()
	{
		$this->referenceFiles = config('openai.image_files', []);
		$this->instruction = config('openai.vision_instruction', '');
		$this->assistantId = config('openai.assistant_id');
	}

	/**
	 * Отправляет запрос на выполнение задачи и возвращает результат.
	 *
	 * Метод использует идентификатор потока для создания сообщения пользователя,
	 * добавляет временные вложения, если передан URL изображения, а затем
	 * инициализирует выполнение задачи с указанным ассистентом. После выполнения
	 * задачи ожидает завершения обработки и возвращает текст последнего сообщения.
	 *
	 * @param string $prompt Текст запроса, который нужно отправить.
	 * @param string|null $imageUrl URL изображения для временного вложения (опционально).
	 *
	 * @return string Возвращает текст ответа из последнего сообщения потока.
	 * @throws RequestException
	 */
	public function ask(string $prompt, ?string $imageUrl = null): string
	{
		$threadId = $this->bootThread();
		$attachments = $imageUrl ? [$this->uploadTempImage($imageUrl)] : [];

		$this->createUserMessage($threadId, $prompt, $attachments);

		$run = OpenAI::threads()->runs()->create($threadId, [
			'assistant_id' => $this->assistantId,
		]);

		do {
			usleep(300_000);
			$run = OpenAI::threads()->runs()->retrieve($threadId, $run->id);
		} while (in_array($run->status, ['queued', 'in_progress']));

		$latest = OpenAI::threads()->messages()->list($threadId, ['limit' => 1]);

		return $latest->data[0]->content[0]->text->value;
	}


	/**
	 * Инициализирует поток и сохраняет его идентификатор в кэше.
	 *
	 * Метод создает новый поток с использованием OpenAI, разбивает
	 * массив ссылочных файлов на части и вызывает метод для
	 * создания пользовательских сообщений в соответствующем потоке.
	 *
	 * @return string Возвращает идентификатор созданного потока.
	 */
	private function bootThread(): string
	{
		return Cache::rememberForever(self::THREAD_CACHE, function () {
			$thread = OpenAI::threads()->create([]);

			foreach (array_chunk($this->referenceFiles, 10) as $chunk) {
				$this->createUserMessage($thread->id, '', $chunk);
			}

			return $thread->id;
		});
	}

	/**
	 * Создает сообщение пользователя с текстом и прикрепленными файлами в указанной теме.
	 *
	 * @param string $threadId Идентификатор темы, в которую создается сообщение.
	 * @param string $text Текст сообщения. Может быть пустым.
	 * @param array $fileIds Массив идентификаторов файлов, которые необходимо прикрепить к сообщению.
	 *
	 * @return void
	 */
	private function createUserMessage(string $threadId, string $text, array $fileIds): void
	{
		$firstBatchFree = $text === '' ? 10 : 9;
		$chunks = array_chunk($fileIds, $firstBatchFree);

		foreach ($chunks as $index => $chunk) {
			$content = [];

			if ($index === 0 && $text !== '') {
				$content[] = ['type' => 'text', 'text' => $text];
			}

			foreach ($chunk as $id) {
				$content[] = ['type' => 'image_file', 'image_file' => ['file_id' => $id]];
			}

			OpenAI::threads()->messages()->create($threadId, [
				'role' => 'user',
				'content' => $content,
			]);
		}
	}

	/**
	 * Загружает временное изображение из указанного URL и отправляет его на сервер.
	 *
	 * @param string $url URL изображения, которое необходимо обработать.
	 *
	 * @return string Идентификатор загруженного файла.
	 *
	 * @throws RequestException Если запрос на получение изображения завершился неудачей.
	 */
	private function uploadTempImage(string $url): string
	{
		$binary = Http::get($url)->throw()->body();
		$pathInfo = pathinfo(parse_url($url, PHP_URL_PATH) ?? '');
		$ext = strtolower($pathInfo['extension'] ?? 'png');

		if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
			$ext = 'png';
		}

		$tempPath = tempnam(sys_get_temp_dir(), 'img_');
		$finalPath = "{$tempPath}.{$ext}";
		rename($tempPath, $finalPath);
		file_put_contents($finalPath, $binary);

		$res = OpenAI::files()->upload([
			'purpose' => 'vision',
			'file' => fopen($finalPath, 'r'),
		]);

		unlink($finalPath);

		return $res->id;
	}
}
