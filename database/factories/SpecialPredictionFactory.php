<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialPredictionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'champion_team_id' => null,
            'runner_up_team_id' => null,
            'top_scorer_player_id' => null,
            'is_locked' => false,
            'pts_champion' => 0,
            'pts_runner_up' => 0,
            'pts_top_scorer' => 0,
            'calculated_at' => null,
        ];
    }
}
