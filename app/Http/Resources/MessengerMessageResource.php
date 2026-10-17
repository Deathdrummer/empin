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

        $mediaWithDefaults = self::normalizeMedia($this->media, $this->id);

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
            // text | poll | system
            'type'        => $this->type ?? 'text',
            'poll'        => $this->when($this->type === 'poll' && is_array($this->poll), fn () => self::pollPayload($this->resource)),
            'mentions'    => array_map('intval', $this->mentions ?? []),
            'pinned'      => $this->pinned_at !== null,
            'self'        => $isSelf,
            'created_at'  => $this->created_at->toIso8601String(),
            'updated_at'  => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }

    /**
     * Опрос: варианты и кто за какой голосовал (staff id) — «мой» голос клиент находит сам
     */
    public static function pollPayload($message): array {
        $votes = $message->relationLoaded('votes') ? $message->votes : $message->votes()->get();
        $poll = $message->poll ?? [];

        $options = [];
        foreach (array_values($poll['options'] ?? []) as $index => $text) {
            $options[] = [
                'text'   => (string) $text,
                'voters' => $votes->where('option_index', $index)->pluck('staff_id')->map(fn ($id) => (int) $id)->values()->all(),
            ];
        }

        return [
            'question'     => (string) ($poll['question'] ?? ''),
            'multiple'     => (bool) ($poll['multiple'] ?? false),
            'options'      => $options,
            'total_voters' => $votes->pluck('staff_id')->unique()->count(),
        ];
    }

    /**
     * Вложения: недостающие имя и размер файла (у старых сообщений их нет)
     */
    public static function normalizeMedia($media, $ownerId = null): ?array {
        if (!$media || !is_array($media)) {
            return null;
        }

        // Старый формат — одно вложение объектом
        if (isset($media['path'])) {
            $media = [$media];
        }

        return array_map(function ($m) use ($ownerId) {
            if (empty($m['name'])) {
                if (!empty($m['filename'])) {
                    $m['name'] = $m['filename'];
                } elseif (!empty($m['path'])) {
                    $pathWithoutQuery = explode('?', $m['path'])[0];
                    $m['name'] = basename($pathWithoutQuery) ?: null;
                }
            }

            if (!empty($m['name']) && strpos($m['name'], '%') !== false) {
                $m['name'] = urldecode($m['name']);
            }

            if (empty($m['size']) && !empty($m['path'])) {
                $fullPath = storage_path('app/public/' . str_replace('/storage/', '', $m['path']));
                if (file_exists($fullPath)) {
                    $m['size'] = filesize($fullPath);
                } else {
                    \Log::warning('[Messenger] File not found', ['owner_id' => $ownerId, 'path' => $fullPath]);
                }
            }

            return $m;
        }, $media);
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
