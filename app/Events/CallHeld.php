<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Собеседник удержал звонок или вернулся к нему */
class CallHeld implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $callId,
        public readonly int $otherPartyStaffId,
        public readonly bool $onHold,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.' . $this->otherPartyStaffId);
    }

    public function broadcastAs(): string
    {
        return 'call.held';
    }

    public function broadcastWith(): array
    {
        return ['call_id' => $this->callId, 'on_hold' => $this->onHold];
    }
}
