<?php

namespace App\Events;

class TournamentFinalized
{
    public function __construct(
        public readonly int $championTeamId,
        public readonly int $runnerUpTeamId,
        public readonly int $topScorerPlayerId,
    ) {}
}
