<?php

namespace App\Listeners;

use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\User;
use App\Services\GroupStageClassifierService;

class CalculateClassifierPoints
{
    public function __construct(private GroupStageClassifierService $classifier) {}

    public function handle(RoundFinalized $event): void
    {
        $round = $event->round;

        match ($round->slug) {
            'grupos' => $this->calculateF1($round),
            'r32'    => $this->calculateF2($round),
            'f3'     => $this->calculateF3($round),
            default  => null,
        };
    }

    // ── F1: Fase de Grupos ────────────────────────────────────────────────────
    // Uses GroupStageClassifierService (complex group standings + best-thirds).
    private function calculateF1(\App\Models\Round $round): void
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

    // ── F2: Round of 32 (M73–M88) ────────────────────────────────────────────
    // Classifiers = the 16 teams that win each R32 match.
    // Derived from user's predicted score for each M73–M88 match.
    private function calculateF2(\App\Models\Round $round): void
    {
        $fixtures = Fixture::where('round_id', $round->id)
            ->whereBetween('match_number', [73, 88])
            ->orderBy('match_number')
            ->get();

        $this->calculateKnockoutClassifiers($round, $fixtures);
    }

    // ── F3: Octavos + Cuartos (M97–M100 cuartos winners = semifinalists) ─────
    // Classifiers = the 4 teams that win each cuartos match (semifinalists).
    // Octavos (M89–M96) do NOT count for classifier pts — only cuartos winners do.
    private function calculateF3(\App\Models\Round $round): void
    {
        $fixtures = Fixture::where('round_id', $round->id)
            ->whereBetween('match_number', [97, 100])
            ->orderBy('match_number')
            ->get();

        $this->calculateKnockoutClassifiers($round, $fixtures);
    }

    // ── Shared: derive classifier points from match winner predictions ────────
    private function calculateKnockoutClassifiers(\App\Models\Round $round, $fixtures): void
    {
        $realClassifiers = $fixtures
            ->pluck('winner_team_id')
            ->filter()
            ->values()
            ->toArray();

        $submissions = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('status', ['submitted', 'locked'])
            ->get();

        $fixtureIds = $fixtures->pluck('id');

        foreach ($submissions as $submission) {
            $userPredictions = Prediction::where('user_id', $submission->user_id)
                ->whereIn('match_id', $fixtureIds)
                ->get()
                ->keyBy('match_id');

            $predictedClassifiers = [];
            foreach ($fixtures as $fixture) {
                $pred = $userPredictions->get($fixture->id);
                if (! $pred || ! $fixture->home_team_id || ! $fixture->away_team_id) {
                    continue;
                }

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
