<?php

namespace App\Listeners;

use App\Events\MatchScoreUpdated;
use App\Models\Fixture;

class PropagateBracketWinner
{
    public function handle(MatchScoreUpdated $event): void
    {
        $fixture = $event->fixture;

        if (! $fixture->winner_team_id || ! $fixture->winner_feeds_match_id) {
            return;
        }

        $target = Fixture::find($fixture->winner_feeds_match_id);
        if (! $target) {
            return;
        }

        $column = $fixture->winner_feeds_slot === 'home' ? 'home_team_id' : 'away_team_id';

        $target->update([$column => $fixture->winner_team_id]);
    }
}
