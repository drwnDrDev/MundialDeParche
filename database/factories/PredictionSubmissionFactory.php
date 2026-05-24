<?php

namespace Database\Factories;

use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PredictionSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'round_id' => Round::factory(),
            'status' => 'draft',
            'submitted_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'locked',
            'submitted_at' => now(),
        ]);
    }
}
