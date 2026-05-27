<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ExactScoreAlert implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $userName,
        public readonly int    $matchId,
        public readonly int    $homeScore,
        public readonly int    $awayScore,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return [
            'user_name'  => $this->userName,
            'match_id'   => $this->matchId,
            'home_score' => $this->homeScore,
            'away_score' => $this->awayScore,
        ];
    }
}
