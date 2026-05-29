<?php

namespace App\Listeners;

use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\User;
use App\Services\GroupStageClassifierService;
use Illuminate\Support\Collection;

class CalculateClassifierPoints
{
    public function __construct(private GroupStageClassifierService $classifier) {}

    public function handle(RoundFinalized $event): void
    {
        $round = $event->round;

        if ($round->slug === 'grupos') {
            $this->calculateR1($round);
        } elseif ($round->slug === 'r32') {
            $this->calculateR2($round);
        }
    }

    private function calculateR1(\App\Models\Round $round): void
    {
        $fixtures = Fixture::where('round_id', $round->id)
            ->whereNotNull('group_id')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        $realClassifiers = $this->classifier->getClassifierIds(
            $fixtures,
            fn ($f) => [$f->home_score, $f->away_score]
        );

        $submissions = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('status', ['submitted', 'locked'])
            ->get();

        foreach ($submissions as $submission) {
            // Use saved classifiers if available; fallback to derivation for old submissions
            if (! empty($submission->predicted_classifiers)) {
                $predictedClassifiers = collect($submission->predicted_classifiers)
                    ->pluck('team_id')
                    ->toArray();
            } else {
                $userPredictions = Prediction::where('user_id', $submission->user_id)
                    ->whereIn('match_id', $fixtures->pluck('id'))
                    ->get()
                    ->keyBy('match_id');

                $predictedClassifiers = $this->classifier->getClassifierIds(
                    $fixtures,
                    function ($f) use ($userPredictions) {
                        $pred = $userPredictions->get($f->id);
                        return $pred ? [$pred->predicted_home, $pred->predicted_away] : [null, null];
                    }
                );
            }

            $correct = count(array_intersect($predictedClassifiers, $realClassifiers));
            $pts     = $correct * $round->points_classifier;

            $submission->update(['pts_classifier' => $pts]);
            User::recalculateTotalPoints($submission->user_id);
        }
    }

    private function calculateR2(\App\Models\Round $round): void
    {
        // R16 matches are M89–M96 (the final 8 matches of the R32+R16 round).
        // We filter by match_number instead of slicing by position so the query
        // is resilient to any extra fixtures the admin might create in this round.
        $r16Fixtures = Fixture::where('round_id', $round->id)
            ->whereBetween('match_number', [89, 96])
            ->orderBy('match_number')
            ->get();

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

                $predictedClassifiers[] = $pred->predicted_home > $pred->predicted_away
                    ? $fixture->home_team_id
                    : $fixture->away_team_id;
            }

            $correct = count(array_intersect($predictedClassifiers, $realClassifiers));
            $pts     = $correct * $round->points_classifier;

            $submission->update(['pts_classifier' => $pts]);
            User::recalculateTotalPoints($submission->user_id);
        }
    }
}
