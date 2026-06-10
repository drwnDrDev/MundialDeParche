<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class RankingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param array<int, array{user_id: int, total_points: int, position: int}> $updates
     */
    public function __construct(
        public readonly array $updates,
    ) {}

    public function broadcastAs(): string
    {
        return 'RankingUpdated';
    }

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return ['updates' => $this->updates];
    }
}
