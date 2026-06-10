<?php

namespace App\Listeners;

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\MatchScoreUpdated;
use App\Events\RankingUpdated;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\SpecialPrediction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalculateMatchPoints
{
    public function handle(MatchScoreUpdated $event): void
    {
        $fixture = $event->fixture;

        if ($fixture->home_score === null || $fixture->away_score === null) {
            return;
        }

        $round = $fixture->round;

        $submittedUserIds = PredictionSubmission::where('round_id', $fixture->round_id)
            ->whereIn('status', ['submitted', 'locked'])
            ->pluck('user_id');

        $predictions = Prediction::where('match_id', $fixture->id)
            ->whereIn('user_id', $submittedUserIds)
            ->get();

        $idsByPoints       = []; // "exact|result" => [prediction ids]
        $exactScoreHitters = []; // user IDs who got pts_exact

        foreach ($predictions as $prediction) {
            $ptsExact  = 0;
            $ptsResult = 0;

            // Exact score (always 90-min)
            if ($prediction->predicted_home === $fixture->home_score
                && $prediction->predicted_away === $fixture->away_score) {
                $ptsExact = $round->points_exact;
            }

            if ($fixture->isGroupStage()) {
                // Group stage: result = 1 / X / 2 by sign comparison
                $realSign = $fixture->home_score <=> $fixture->away_score;
                $predSign = $prediction->predicted_home <=> $prediction->predicted_away;
                if ($realSign === $predSign) {
                    $ptsResult = $round->points_result;
                }
            } else {
                // Knockout: result = acertar el ganador real (winner_team_id)
                if ($fixture->winner_team_id !== null) {
                    if ($prediction->predicted_home !== $prediction->predicted_away) {
                        // Predicción directa (sin empate): ganador = equipo con más goles
                        $predictedWinnerId = $prediction->predicted_home > $prediction->predicted_away
                            ? $fixture->home_team_id
                            : $fixture->away_team_id;
                    } else {
                        // Predicción de empate a 90': ganador definido por predicted_winner_id (ET/penales)
                        $predictedWinnerId = $prediction->predicted_winner_id;
                    }

                    if ($predictedWinnerId !== null && $predictedWinnerId === $fixture->winner_team_id) {
                        $ptsResult = $round->points_result;
                    }
                }
            }

            $idsByPoints["{$ptsExact}|{$ptsResult}"][] = $prediction->id;

            if ($ptsExact > 0) {
                $exactScoreHitters[] = $prediction->user_id;
            }
        }

        // Una query por combinación de puntos (máx. 4 combos), no una por predicción
        $now = now();
        foreach ($idsByPoints as $key => $ids) {
            [$ptsExact, $ptsResult] = array_map('intval', explode('|', $key));
            Prediction::whereIn('id', $ids)->update([
                'pts_exact'     => $ptsExact,
                'pts_result'    => $ptsResult,
                'total_points'  => $ptsExact + $ptsResult,
                'calculated_at' => $now,
            ]);
        }

        $affectedUserIds = $predictions->pluck('user_id')->unique()->values();

        if ($affectedUserIds->isNotEmpty()) {
            $this->recalculateTotals($affectedUserIds);

            $updates = $this->rankingUpdates($affectedUserIds);
            if ($updates !== []) {
                rescue(fn () => RankingUpdated::dispatch($updates));
            }
        }

        rescue(fn () => LiveScoreUpdated::dispatch(
            $fixture->id,
            $fixture->home_score,
            $fixture->away_score,
            $fixture->status === 'in_progress',
            $fixture->status,
        ));

        if ($exactScoreHitters !== []) {
            $names = User::whereIn('id', array_unique($exactScoreHitters))->pluck('name')->all();

            rescue(fn () => ExactScoreAlert::dispatch(
                $names,
                $fixture->id,
                $fixture->home_score,
                $fixture->away_score,
            ));
        }
    }

    /**
     * Recalcula users.total_points para todos los afectados:
     * 3 SELECT agregados + 1 UPDATE, en vez de ~4 queries por usuario.
     */
    private function recalculateTotals(Collection $userIds): void
    {
        $matchPts = Prediction::whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, COALESCE(SUM(total_points), 0) AS pts')
            ->pluck('pts', 'user_id');

        $classifierPts = PredictionSubmission::whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, COALESCE(SUM(pts_classifier), 0) AS pts')
            ->pluck('pts', 'user_id');

        $specialPts = SpecialPrediction::whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(COALESCE(pts_champion,0) + COALESCE(pts_runner_up,0) + COALESCE(pts_top_scorer,0)) AS pts')
            ->pluck('pts', 'user_id');

        $cases    = '';
        $bindings = [];

        foreach ($userIds as $id) {
            $total = (int) ($matchPts[$id] ?? 0)
                + (int) ($classifierPts[$id] ?? 0)
                + (int) ($specialPts[$id] ?? 0);

            $cases .= ' WHEN ? THEN ?';
            $bindings[] = $id;
            $bindings[] = $total;
        }

        $placeholders = implode(',', array_fill(0, $userIds->count(), '?'));

        DB::update(
            "UPDATE users SET total_points = CASE id{$cases} END WHERE id IN ({$placeholders})",
            array_merge($bindings, $userIds->all()),
        );
    }

    /**
     * Posiciones con dense rank entre participantes activados
     * (mismo criterio que RankingController), en una sola query.
     *
     * @return array<int, array{user_id: int, total_points: int, position: int}>
     */
    private function rankingUpdates(Collection $affectedUserIds): array
    {
        $ranking = User::where('is_activated', true)
            ->where('role', 'user')
            ->orderByDesc('total_points')
            ->get(['id', 'total_points']);

        $affected = $affectedUserIds->flip();
        $updates  = [];

        $position = 0;
        $lastPts  = null;
        $counter  = 0;

        foreach ($ranking as $user) {
            $counter++;
            if ($user->total_points !== $lastPts) {
                $position = $counter;
                $lastPts  = $user->total_points;
            }

            if ($affected->has($user->id)) {
                $updates[] = [
                    'user_id'      => $user->id,
                    'total_points' => $user->total_points,
                    'position'     => $position,
                ];
            }
        }

        return $updates;
    }
}
