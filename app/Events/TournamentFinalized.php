<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TournamentFinalized
{
    use Dispatchable;

    public function __construct(
        public readonly int $championTeamId,
        public readonly int $runnerUpTeamId,
        public readonly int $topScorerPlayerId,
    ) {}
}
