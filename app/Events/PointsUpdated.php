<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class PointsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $userId,
        public readonly int $totalPoints,
        public readonly int $position,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('quinela'),
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'      => $this->userId,
            'total_points' => $this->totalPoints,
            'position'     => $this->position,
        ];
    }
}
