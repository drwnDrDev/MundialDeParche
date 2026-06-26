<?php

namespace App\Listeners;

use App\Events\TournamentFinalized;
use App\Models\SpecialPrediction;
use App\Models\TournamentResult;
use App\Models\User;

class CalculateSpecialPredictions
{
    public function handle(TournamentFinalized $event): void
    {
        $specials = SpecialPrediction::all();

        foreach ($specials as $special) {
            $ptsChampion  = $special->champion_team_id === $event->championTeamId ? 30 : 0;
            $ptsRunnerUp  = $special->runner_up_team_id === $event->runnerUpTeamId ? 10 : 0;
            $ptsTopScorer = $special->top_scorer_player_id === $event->topScorerPlayerId ? 15 : 0;

            $special->update([
                'pts_champion'   => $ptsChampion,
                'pts_runner_up'  => $ptsRunnerUp,
                'pts_top_scorer' => $ptsTopScorer,
                'is_locked'      => true,
                'calculated_at'  => now(),
            ]);

            User::recalculateTotalPoints($special->user_id);
        }

        TournamentResult::updateOrCreate([], [
            'champion_team_id'     => $event->championTeamId,
            'runner_up_team_id'    => $event->runnerUpTeamId,
            'top_scorer_player_id' => $event->topScorerPlayerId,
        ]);
    }
}
