<?php namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessengerMessageResource extends JsonResource {
    /**
     * Отключаем обертку "data" для одиночных ресурсов
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        $currentUser = $request->user();

        // Определяем является ли текущий пользователь автором сообщения
        $isSelf = $currentUser && $this->from_id ? ($this->from_id === $currentUser->staff_id) : false;

        // Обрабатываем media - добавляем отсутствующие поля
        $mediaWithDefaults = null;
        if ($this->media) {
            $mediaWithDefaults = array_map(function($m) {
                // Если name отсутствует - извлекаем из filename или path
                if (empty($m['name'])) {
                    // Проверяем старое поле filename
                    if (!empty($m['filename'])) {
                        $m['name'] = $m['filename'];
                    }
                    // Извлекаем из path
                    elseif (!empty($m['path'])) {
                        $pathWithoutQuery = explode('?', $m['path'])[0];
                        $fileName = basename($pathWithoutQuery);
                        $m['name'] = $fileName ?: null;
                    }
                }

                // Декодируем URL-encoded имя
                if (!empty($m['name']) && strpos($m['name'], '%') !== false) {
                    $m['name'] = urldecode($m['name']);
                }

                // Если size отсутствует - пытаемся получить из файла
                if (empty($m['size']) && !empty($m['path'])) {
                    $storagePath = str_replace('/storage/', '', $m['path']);
                    $fullPath = storage_path('app/public/' . $storagePath);
                    if (file_exists($fullPath)) {
                        $m['size'] = filesize($fullPath);
                    } else {
                        \Log::warning('[MessengerMessageResource] File not found', [
                            'message_id' => $this->id,
                            'path' => $fullPath
                        ]);
                    }
                }

                return $m;
            }, $this->media);
        }

        return [
            'id'          => $this->id,
            'chat_id'     => $this->chat_id,
            // В событиях реального времени текущего пользователя нет — «своё» сообщение клиент определяет по from_id
            'from_id'     => $this->from_id,
            'from'        => $this->profile ? [
                                'sname' => $this->profile->sname,
                                'fname' => $this->profile->fname,
                                'mname' => $this->profile->mname,
                            ] : null,
            'message'     => $this->message,
            'reply_to_id' => $this->reply_to_id,
            // Цитата для ответа: исходное сообщение может быть за пределами загруженной страницы истории
            'reply_to'    => $this->when($this->relationLoaded('replyTo') && $this->replyTo, fn () => [
                'id'      => $this->replyTo->id,
                'from_id' => $this->replyTo->from_id,
                'from'    => $this->replyTo->relationLoaded('profile') && $this->replyTo->profile ? [
                    'sname' => $this->replyTo->profile->sname,
                    'fname' => $this->replyTo->profile->fname,
                ] : null,
                'message' => mb_substr((string) $this->replyTo->message, 0, 200),
                'type'    => self::previewType($this->replyTo->media),
            ]),
            'reactions'   => $this->reactions ?? [],
            'media'       => $mediaWithDefaults,
            'self'        => $isSelf,
            'created_at'  => $this->created_at->toIso8601String(),
            'updated_at'  => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }

    /**
     * Тип вложения для превью в списке чатов, цитате и push: text | image | video | audio | document | archive | file
     */
    public static function previewType($media): string {
        if (empty($media) || !is_array($media)) {
            return 'text';
        }

        // Старый формат — одно вложение объектом
        $first = isset($media['path']) ? $media : ($media[0] ?? null);

        return is_array($first) ? ($first['type'] ?? 'file') : 'text';
    }

    /**
     * Короткий текст сообщения для списка чатов и push: сам текст или название вложения
     */
    public static function previewText(?string $text, $media): string {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
        if ($text !== '') {
            return mb_strimwidth($text, 0, 120, '…');
        }

        return match (self::previewType($media)) {
            'image'    => 'Фото',
            'video'    => 'Видео',
            'audio'    => 'Голосовое сообщение',
            'document' => 'Документ',
            'archive'  => 'Архив',
            'file'     => 'Файл',
            default    => '',
        };
    }
}
