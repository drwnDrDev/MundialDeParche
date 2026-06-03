<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class LiveScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int    $matchId,
        public readonly ?int   $homeScore,
        public readonly ?int   $awayScore,
        public readonly bool   $isLive,
        public readonly string $status = 'scheduled',
    ) {}

    public function broadcastAs(): string
    {
        return 'LiveScoreUpdated';
    }

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return [
            'match_id'   => $this->matchId,
            'home_score' => $this->homeScore,
            'away_score' => $this->awayScore,
            'is_live'    => $this->isLive,
            'status'     => $this->status,
        ];
    }
}
