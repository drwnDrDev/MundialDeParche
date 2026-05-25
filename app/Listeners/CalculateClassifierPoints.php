<?php

namespace App\Listeners;

use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\User;
use Illuminate\Support\Collection;

class CalculateClassifierPoints
{
    public function handle(RoundFinalized $event): void
    {
        $round = $event->round;

        if ($round->slug === 'grupos') {
            $this->calculateR1($round);
        } elseif ($round->slug === 'r32-r16') {
            $this->calculateR2($round);
        }
    }

    private function calculateR1(\App\Models\Round $round): void
    {
        $fixtures = Fixture::where('round_id', $round->id)
            ->whereNotNull('group_id')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        $realClassifiers = $this->getR1Classifiers($fixtures, fn ($f) => [$f->home_score, $f->away_score]);

        $submissions = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('status', ['submitted', 'locked'])
            ->get();

        foreach ($submissions as $submission) {
            $userPredictions = Prediction::where('user_id', $submission->user_id)
                ->whereIn('match_id', $fixtures->pluck('id'))
                ->get()
                ->keyBy('match_id');

            $predictedClassifiers = $this->getR1Classifiers(
                $fixtures,
                function ($f) use ($userPredictions) {
                    $pred = $userPredictions->get($f->id);
                    return $pred ? [$pred->predicted_home, $pred->predicted_away] : [null, null];
                }
            );

            $correct = count(array_intersect($predictedClassifiers, $realClassifiers));
            $pts     = $correct * $round->points_classifier;

            $submission->update(['pts_classifier' => $pts]);
            User::recalculateTotalPoints($submission->user_id);
        }
    }

    private function getR1Classifiers(Collection $fixtures, callable $getScores): array
    {
        $byGroup = $fixtures->groupBy('group_id');
        $classifiers = [];
        $thirds = [];

        foreach ($byGroup as $groupId => $groupFixtures) {
            $table = $this->buildGroupTable($groupFixtures, $getScores);

            if (count($table) < 2) continue;

            $classifiers[] = $table[0]['team_id'];
            $classifiers[] = $table[1]['team_id'];

            if (isset($table[2])) {
                $thirds[] = $table[2];
            }
        }

        if (count($thirds) >= 8) {
            usort($thirds, fn ($a, $b) =>
                $b['pts'] <=> $a['pts']
                ?: $b['gd'] <=> $a['gd']
                ?: $b['gf'] <=> $a['gf']
            );

            foreach (array_slice($thirds, 0, 8) as $third) {
                $classifiers[] = $third['team_id'];
            }
        }

        return $classifiers;
    }

    private function buildGroupTable(Collection $fixtures, callable $getScores): array
    {
        $table = [];

        foreach ($fixtures as $f) {
            if ($f->home_team_id) $table[$f->home_team_id] ??= ['team_id' => $f->home_team_id, 'pts' => 0, 'gd' => 0, 'gf' => 0];
            if ($f->away_team_id) $table[$f->away_team_id] ??= ['team_id' => $f->away_team_id, 'pts' => 0, 'gd' => 0, 'gf' => 0];
        }

        foreach ($fixtures as $f) {
            [$h, $a] = $getScores($f);
            if ($h === null || $a === null || !$f->home_team_id || !$f->away_team_id) continue;

            $table[$f->home_team_id]['gf'] += $h;
            $table[$f->home_team_id]['gd'] += $h - $a;
            $table[$f->away_team_id]['gf'] += $a;
            $table[$f->away_team_id]['gd'] += $a - $h;

            if ($h > $a) {
                $table[$f->home_team_id]['pts'] += 3;
            } elseif ($h < $a) {
                $table[$f->away_team_id]['pts'] += 3;
            } else {
                $table[$f->home_team_id]['pts'] += 1;
                $table[$f->away_team_id]['pts'] += 1;
            }
        }

        usort($table, fn ($a, $b) =>
            $b['pts'] <=> $a['pts'] ?: $b['gd'] <=> $a['gd'] ?: $b['gf'] <=> $a['gf']
        );

        return array_values($table);
    }

    private function calculateR2(\App\Models\Round $round): void
    {
        $r2Fixtures = Fixture::where('round_id', $round->id)
            ->orderBy('match_number')
            ->get();

        $r16Fixtures = $r2Fixtures->slice(16)->values();

        $realClassifiers = $r16Fixtures
            ->pluck('winner_team_id')
            ->filter()
            ->values()
            ->toArray();

        $submissions = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('status', ['submitted', 'locked'])
            ->get();

        $r16FixtureIds = $r16Fixtures->pluck('id');

        foreach ($submissions as $submission) {
            $userR16Predictions = Prediction::where('user_id', $submission->user_id)
                ->whereIn('match_id', $r16FixtureIds)
                ->get()
                ->keyBy('match_id');

            $predictedClassifiers = [];
            foreach ($r16Fixtures as $fixture) {
                $pred = $userR16Predictions->get($fixture->id);
                if (!$pred || !$fixture->home_team_id || !$fixture->away_team_id) continue;

                $predictedWinnerId = $pred->predicted_home > $pred->predicted_away
                    ? $fixture->home_team_id
                    : $fixture->away_team_id;
                $predictedClassifiers[] = $predictedWinnerId;
            }

            $correct = count(array_intersect($predictedClassifiers, $realClassifiers));
            $pts     = $correct * $round->points_classifier;

            $submission->update(['pts_classifier' => $pts]);
            User::recalculateTotalPoints($submission->user_id);
        }
    }
}
