<?php

namespace App\Services\Messenger;

use App\Events\MessengerEvent;
use App\Models\MessengerChat;
use App\Models\MessengerChatMember;
use App\Models\Staff;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * События реального времени (Pusher) и push о сообщениях мессенджера.
 */
class MessengerNotifier
{
    // Pusher принимает событие до 10 КБ; длинное сообщение клиент дозагрузит сам
    private const MAX_EVENT_BYTES = 9000;

    public function __construct(
        private PushNotificationService $push,
        private ChatDirectory $directory,
        private ObjectChats $objects,
    ) {
    }

    /**
     * @param int[] $staffIds
     */
    public function broadcast(array $staffIds, string $event, array $payload): void
    {
        $staffIds = array_values(array_unique(array_filter(array_map('intval', $staffIds))));
        if (!$staffIds) {
            return;
        }

        // Слишком длинное сообщение не влезет в событие Pusher — клиент дозагрузит его по message_id
        if (isset($payload['message']['id']) && strlen(json_encode($payload, JSON_UNESCAPED_UNICODE)) > self::MAX_EVENT_BYTES) {
            $payload = [
                'chat_id'    => $payload['chat_id'],
                'message_id' => $payload['message']['id'],
                'truncated'  => true,
            ];
        }

        try {
            event(new MessengerEvent($staffIds, $event, $payload));
        } catch (\Throwable $error) {
            // Сообщение уже сохранено: участники получат его при следующей загрузке чата
            Log::warning('[Messenger] Broadcast failed', ['event' => $event, 'error' => $error->getMessage()]);
        }
    }

    /**
     * Всем участникам чата, кроме $exceptStaffId
     */
    public function broadcastToChat(MessengerChat $chat, string $event, array $payload, ?int $exceptStaffId = null): void
    {
        $this->broadcast(
            array_filter($chat->memberIds(), fn (int $id) => $id !== $exceptStaffId),
            $event,
            $payload
        );
    }

    /**
     * Новое сообщение: событие всем участникам, кроме автора; push — тем, у кого чат не «без звука».
     * Упомянутым через @ push приходит всегда.
     * В чате объекта состав сначала сверяется с табелем: сообщение получат и мастера, ещё не открывавшие «Чаты».
     *
     * @param int[] $mentionIds
     */
    public function newMessage(MessengerChat $chat, Staff $sender, array $message, string $preview, array $mentionIds = []): void
    {
        $this->objects->syncMembers($chat);

        // Архив, как в Telegram: чат со звуком возвращается в основной список при новом сообщении,
        // «без звука» — остаётся в архиве
        MessengerChatMember::where('chat_id', $chat->id)
            ->where('staff_id', '!=', $sender->id)
            ->whereNotNull('archived_at')
            ->where('muted', false)
            ->update(['archived_at' => null]);

        $members = MessengerChatMember::where('chat_id', $chat->id)
            ->where('staff_id', '!=', $sender->id)
            ->get();

        $this->broadcast($members->pluck('staff_id')->all(), 'message.created', [
            'chat_id' => $chat->id,
            'message' => $message,
        ]);

        $chatTitle = $this->directory->titleOf($chat, $sender);

        foreach ($members as $member) {
            $mentioned = in_array((int) $member->staff_id, $mentionIds, true);
            if ($member->muted && !$mentioned) {
                continue;
            }

            try {
                $recipient = Staff::find($member->staff_id);
                if (!$recipient) {
                    continue;
                }

                [$title, $body] = $this->pushText($chat, $chatTitle, $sender, $preview, $mentioned);

                $this->push->sendChatMessageNotification($recipient, $title, $body, [
                    'chat_id'    => $chat->id,
                    'chat_type'  => $chat->type ?? MessengerChat::TYPE_DIRECT,
                    'chat_title' => $chatTitle,
                    'message_id' => $message['id'] ?? null,
                    'sender_id'  => $sender->id,
                ], $this->directory->totalUnread($recipient->id));
            } catch (\Throwable $error) {
                Log::warning('[Messenger] Message push failed', ['error' => $error->getMessage()]);
            }
        }
    }

    /**
     * Push участникам, которых добавили в группу
     *
     * @param int[] $staffIds
     */
    public function addedToGroup(MessengerChat $chat, Staff $actor, array $staffIds): void
    {
        foreach (Staff::whereIn('id', $staffIds)->get() as $recipient) {
            try {
                $this->push->sendChatMessageNotification(
                    $recipient,
                    (string) $chat->title,
                    ChatDirectory::shortName($actor) . ' добавил(а) вас в группу',
                    [
                        'chat_id'    => $chat->id,
                        'chat_type'  => MessengerChat::TYPE_GROUP,
                        'chat_title' => (string) $chat->title,
                        'sender_id'  => $actor->id,
                    ],
                    $this->directory->totalUnread($recipient->id)
                );
            } catch (\Throwable $error) {
                Log::warning('[Messenger] Group push failed', ['error' => $error->getMessage()]);
            }
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function pushText(MessengerChat $chat, string $chatTitle, Staff $sender, string $preview, bool $mentioned): array
    {
        if ($chat->isDirect()) {
            return [trim("{$sender->sname} {$sender->fname}"), $preview];
        }

        $author = ChatDirectory::shortName($sender);

        return $mentioned
            ? [$chatTitle, "{$author} упомянул(а) вас: {$preview}"]
            : [$chatTitle, "{$author}: {$preview}"];
    }
}
