<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие мессенджера в личные каналы сотрудников (те же каналы user.{staffId}, что у звонков):
 * message.created, message.updated, message.deleted, chat.read, chat.typing, chat.pinned, chat.updated.
 * Одно событие уходит сразу всем получателям — Pusher рассылает до 100 каналов за запрос.
 */
class MessengerEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var int[] */
    public readonly array $recipientStaffIds;

    /**
     * @param int|int[] $recipients
     */
    public function __construct(
        int|array $recipients,
        public readonly string $eventName,
        public readonly array $payload,
    ) {
        $this->recipientStaffIds = array_values(array_unique(array_map('intval', (array) $recipients)));
    }

    public function broadcastOn(): array
    {
        return array_map(fn (int $id) => new PrivateChannel('user.' . $id), $this->recipientStaffIds);
    }

    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
