<?php

namespace App\Services\Messenger;

use App\Http\Resources\MessengerMessageResource;
use App\Models\MessengerChat;
use App\Models\MessengerHiddenMessage;
use App\Models\MessengerMessage;

/**
 * «Медиа, файлы, голосовые, ссылки» чата — как раздел в сведениях о чате Telegram.
 * Сообщения просматриваются от новых к старым пачками; вложения одного сообщения (альбом) не делятся между
 * страницами. В чате объекта — комментарии табеля этого объекта.
 */
class ChatMedia
{
    public const KINDS = ['media', 'files', 'voice', 'links'];

    private const BATCH = 300;
    // За один запрос просматривается не больше стольких сообщений: дальше — следующая страница
    private const MAX_SCAN = 3000;

    // Как в приложении (src/utils/richText.js): ссылки со схемой, www и домены с привычными окончаниями
    private const URL_PATTERN = '~(?:https?://[^\s<>"«»]+|www\.[^\s<>"«»]+|(?<![@\w.-])(?:[a-zа-яё0-9-]+\.)+'
        . '(?:ru|рф|su|com|net|org|info|biz|io|me|pro|online|site|store|app|dev|by|kz|ua|uz|de|eu|us|uk)'
        . '(?![\wа-яё-])(?:/[^\s<>"«»]*)?)~iu';

    public function __construct(private ObjectChats $objects)
    {
    }

    /**
     * @param ?int $hiddenFor без сообщений, удалённых этим сотрудником «у себя»
     * @return array{items: array<int, array>, next_before_id: ?int}
     */
    public function list(MessengerChat $chat, string $kind, ?int $beforeId, int $limit, ?int $hiddenFor = null): array
    {
        $base = $chat->isObject()
            ? $this->objects->commentsQuery((int) $chat->contract_id)
            : MessengerMessage::where('chat_id', $chat->id)->where('type', '!=', MessengerMessage::TYPE_SYSTEM);
        if ($kind === 'links') {
            $base->whereNotNull('message')->where('message', '!=', '');
        } else {
            $base->whereNotNull('media');
        }
        if ($hiddenFor !== null) {
            $base->whereNotIn('id', MessengerHiddenMessage::idsQuery($hiddenFor, $chat->id));
        }

        $items = [];
        $cursor = $beforeId;
        $scanned = 0;
        $exhausted = false;

        while (count($items) < $limit && $scanned < self::MAX_SCAN) {
            $rows = (clone $base)->when($cursor, fn ($q) => $q->where('id', '<', $cursor))
                ->orderByDesc('id')->limit(self::BATCH)->with('profile')->get();

            foreach ($rows as $row) {
                $cursor = (int) $row->id;
                $scanned++;
                array_push($items, ...$this->itemsOf($row, $kind));
                if (count($items) >= $limit) {
                    break;
                }
            }

            // Последняя пачка прочитана целиком — старше ничего нет
            if ($rows->count() < self::BATCH && count($items) < $limit) {
                $exhausted = true;
                break;
            }
            if ($rows->isEmpty()) {
                $exhausted = true;
                break;
            }
        }

        return [
            'items'          => $items,
            'next_before_id' => $exhausted ? null : $cursor,
        ];
    }

    private function itemsOf($row, string $kind): array
    {
        $base = [
            'message_id' => (int) $row->id,
            'created_at' => $row->created_at?->toIso8601String(),
            'from'       => $row->profile ? ['id' => (int) $row->profile->id, 'name' => ChatDirectory::shortName($row->profile)] : null,
        ];

        if ($kind === 'links') {
            $text = MessengerMessageResource::plainText($row->message);
            preg_match_all(self::URL_PATTERN, $text, $matches);
            $urls = array_values(array_unique(array_filter(array_map([self::class, 'trimUrl'], $matches[0]))));
            $preview = mb_strimwidth(trim(preg_replace('/\s+/u', ' ', $text)), 0, 200, '…');

            return array_map(fn (string $url) => $base + ['url' => $url, 'text' => $preview], $urls);
        }

        $media = $row->media;
        if (!is_array($media)) {
            return [];
        }
        // Старый формат — одно вложение объектом
        $list = isset($media['path']) ? [$media] : $media;

        $result = [];
        foreach ($list as $item) {
            if (!is_array($item) || empty($item['path'])) {
                continue;
            }
            $item = MediaStorage::fixLegacyType($item);
            $type = $item['type'] ?? 'file';
            $matches = match ($kind) {
                'media' => in_array($type, ['image', 'video'], true),
                'voice' => $type === 'audio',
                default => !in_array($type, ['image', 'video', 'audio'], true),
            };
            if ($matches) {
                $result[] = $base + ['media' => $item];
            }
        }

        return $result;
    }

    /** Знаки препинания в конце принадлежат предложению: «см. site.ru.» / «(site.ru)» */
    private static function trimUrl(string $url): string
    {
        while (preg_match('/[.,;:!?…»"\')\]]$/u', $url)) {
            if (str_ends_with($url, ')') && substr_count($url, '(') >= substr_count($url, ')')) {
                break;
            }
            $url = mb_substr($url, 0, -1);
        }

        return $url;
    }
}
