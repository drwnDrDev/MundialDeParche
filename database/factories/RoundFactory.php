<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'order' => fake()->numberBetween(1, 4),
            'is_open' => false,
            'is_locked' => false,
            'points_exact' => 0,
            'points_result' => 0,
            'points_classifier' => 0,
        ];
    }

    public function r1(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Fase de Grupos',
            'slug' => 'grupos',
            'order' => 1,
            'points_exact' => 3,
            'points_result' => 1,
            'points_classifier' => 2,
        ]);
    }

    public function r2(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Round of 32 + Round of 16',
            'slug' => 'r32-r16',
            'order' => 2,
            'points_exact' => 5,
            'points_result' => 2,
            'points_classifier' => 4,
        ]);
    }

    public function r3(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cuartos + Semis',
            'slug' => 'qf-sf',
            'order' => 3,
            'points_exact' => 8,
            'points_result' => 3,
            'points_classifier' => 0,
        ]);
    }

    public function r4(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Final + 3er Puesto',
            'slug' => 'final',
            'order' => 4,
            'points_exact' => 13,
            'points_result' => 5,
            'points_classifier' => 0,
        ]);
    }
}
