<?php

namespace Database\Factories;

use App\Models\Fixture;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PredictionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'match_id' => Fixture::factory(),
            'predicted_home' => fake()->numberBetween(0, 5),
            'predicted_away' => fake()->numberBetween(0, 5),
            'pts_exact' => 0,
            'pts_result' => 0,
            'total_points' => 0,
            'calculated_at' => null,
        ];
    }
}
