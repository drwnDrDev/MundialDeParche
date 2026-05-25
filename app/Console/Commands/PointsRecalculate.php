<?php

namespace App\Console\Commands;

use App\Events\MatchScoreUpdated;
use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Round;
use Illuminate\Console\Command;

class PointsRecalculate extends Command
{
    protected $signature = 'points:recalculate {--match=} {--round=}';
    protected $description = 'Recalculate points for a specific match or all matches in a round';

    public function handle(): int
    {
        $matchId = $this->option('match');
        $roundId = $this->option('round');

        if ($matchId) {
            $fixture = Fixture::findOrFail($matchId);
            if ($fixture->home_score !== null && $fixture->away_score !== null) {
                MatchScoreUpdated::dispatch($fixture);
                $this->info("Recalculated points for match #{$fixture->match_number}.");
            } else {
                $this->warn("Match #{$fixture->match_number} has no score. Skipping.");
            }
        }

        if ($roundId) {
            $round    = Round::findOrFail($roundId);
            $fixtures = Fixture::where('round_id', $round->id)
                ->whereNotNull('home_score')
                ->whereNotNull('away_score')
                ->get();

            foreach ($fixtures as $fixture) {
                MatchScoreUpdated::dispatch($fixture);
            }

            $this->info("Recalculated points for {$fixtures->count()} matches in round '{$round->name}'.");

            if ($round->is_locked) {
                RoundFinalized::dispatch($round);
                $this->info("Dispatched RoundFinalized for round '{$round->name}'.");
            }
        }

        return Command::SUCCESS;
    }
}
