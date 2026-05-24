<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

class FixtureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'round_id' => Round::factory(),
            'group_id' => null,
            'match_number' => fake()->numberBetween(1, 104),
            'match_date' => fake()->dateTimeBetween('2026-06-11', '2026-07-19'),
            'home_team_id' => null,
            'away_team_id' => null,
            'home_placeholder' => null,
            'away_placeholder' => null,
            'home_score' => null,
            'away_score' => null,
            'winner_team_id' => null,
            'went_to_extra_time' => false,
            'status' => 'scheduled',
        ];
    }

    public function groupStage(): static
    {
        return $this->state(fn (array $attributes) => ['group_id' => Group::factory()]);
    }

    public function finished(int $homeScore, int $awayScore): static
    {
        return $this->state(fn (array $attributes) => [
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'status' => 'finished',
        ]);
    }

    public function live(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'in_progress']);
    }
}
