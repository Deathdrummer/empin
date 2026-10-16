<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие мессенджера в личный канал сотрудника (тот же канал user.{staffId}, что у звонков):
 * message.created, message.updated, message.deleted, chat.read, chat.typing.
 */
class MessengerEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $recipientStaffId,
        public readonly string $eventName,
        public readonly array $payload,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.' . $this->recipientStaffId);
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
