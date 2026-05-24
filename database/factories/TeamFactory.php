<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'name' => fake()->country(),
            'fifa_code' => strtoupper(fake()->lexify('???')),
            'flag_url' => null,
        ];
    }
}
