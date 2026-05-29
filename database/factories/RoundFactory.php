<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'               => fake()->words(3, true),
            'slug'               => fake()->unique()->slug(),
            'order'              => fake()->numberBetween(1, 4),
            'is_open'            => false,
            'is_locked'          => false,
            'points_exact'       => 0,
            'points_result'      => 0,
            'points_classifier'  => 0,
        ];
    }

    public function f1(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'               => 'Fase de Grupos',
            'slug'               => 'grupos',
            'order'              => 1,
            'points_exact'       => 3,
            'points_result'      => 1,
            'points_classifier'  => 2,
        ]);
    }

    public function f2(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'               => 'Round of 32',
            'slug'               => 'r32',
            'order'              => 2,
            'points_exact'       => 5,
            'points_result'      => 2,
            'points_classifier'  => 3,
        ]);
    }

    public function f3(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'               => 'Octavos + Cuartos',
            'slug'               => 'f3',
            'order'              => 3,
            'points_exact'       => 8,
            'points_result'      => 3,
            'points_classifier'  => 5,
        ]);
    }

    public function f4(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'               => 'Semis + Final',
            'slug'               => 'f4',
            'order'              => 4,
            'points_exact'       => 13,
            'points_result'      => 5,
            'points_classifier'  => 0,
        ]);
    }
}
