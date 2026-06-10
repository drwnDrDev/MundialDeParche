<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ExactScoreAlert implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param array<int, string> $userNames
     */
    public function __construct(
        public readonly array $userNames,
        public readonly int   $matchId,
        public readonly int   $homeScore,
        public readonly int   $awayScore,
    ) {}

    public function broadcastAs(): string
    {
        return 'ExactScoreAlert';
    }

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return [
            'user_names' => $this->userNames,
            'match_id'   => $this->matchId,
            'home_score' => $this->homeScore,
            'away_score' => $this->awayScore,
        ];
    }
}
