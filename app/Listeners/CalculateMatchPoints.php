<?php

namespace App\Listeners;

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\MatchScoreUpdated;
use App\Events\PointsUpdated;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\User;

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

        $affectedUserIds   = [];
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
                if ($fixture->winner_team_id !== null && $prediction->predicted_home !== $prediction->predicted_away) {
                    $predictedWinnerId = $prediction->predicted_home > $prediction->predicted_away
                        ? $fixture->home_team_id
                        : $fixture->away_team_id;
                    if ($predictedWinnerId === $fixture->winner_team_id) {
                        $ptsResult = $round->points_result;
                    }
                }
            }

            $prediction->update([
                'pts_exact'     => $ptsExact,
                'pts_result'    => $ptsResult,
                'total_points'  => $ptsExact + $ptsResult,
                'calculated_at' => now(),
            ]);

            $affectedUserIds[] = $prediction->user_id;

            if ($ptsExact > 0) {
                $exactScoreHitters[] = $prediction->user_id;
            }
        }

        // Phase 1: recalculate all affected users first
        $uniqueAffectedIds = array_unique($affectedUserIds);
        foreach ($uniqueAffectedIds as $userId) {
            User::recalculateTotalPoints($userId);
        }

        // Phase 2: load all affected users in one query + compute positions from snapshot
        $affectedUsers = User::whereIn('id', $uniqueAffectedIds)
            ->select(['id', 'name', 'total_points'])
            ->get()
            ->keyBy('id');

        foreach ($affectedUsers as $user) {
            $position = User::where('total_points', '>', $user->total_points)->count() + 1;
            PointsUpdated::dispatch($user->id, $user->total_points, $position);
        }

        // Broadcast live score once
        LiveScoreUpdated::dispatch(
            $fixture->id,
            $fixture->home_score,
            $fixture->away_score,
            $fixture->status === 'in_progress',
        );

        // Phase 3: dispatch exact score alerts using already-loaded user names
        foreach ($exactScoreHitters as $userId) {
            $userName = $affectedUsers[$userId]->name ?? User::find($userId)?->name ?? 'Unknown';
            ExactScoreAlert::dispatch(
                $userName,
                $fixture->id,
                $fixture->home_score,
                $fixture->away_score,
            );
        }
    }
}
