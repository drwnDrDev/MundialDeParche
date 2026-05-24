<?php

namespace Database\Seeders;

use App\Models\Round;
use Illuminate\Database\Seeder;

class RoundSeeder extends Seeder
{
    public function run(): void
    {
        $rounds = [
            [
                'name' => 'Fase de Grupos',
                'slug' => 'grupos',
                'order' => 1,
                'points_exact' => 3,
                'points_result' => 1,
                'points_classifier' => 2,
            ],
            [
                'name' => 'Round of 32 + Round of 16',
                'slug' => 'r32-r16',
                'order' => 2,
                'points_exact' => 5,
                'points_result' => 2,
                'points_classifier' => 4,
            ],
            [
                'name' => 'Cuartos + Semis',
                'slug' => 'qf-sf',
                'order' => 3,
                'points_exact' => 8,
                'points_result' => 3,
                'points_classifier' => 0,
            ],
            [
                'name' => 'Final + 3er Puesto',
                'slug' => 'final',
                'order' => 4,
                'points_exact' => 13,
                'points_result' => 5,
                'points_classifier' => 0,
            ],
        ];

        foreach ($rounds as $round) {
            Round::firstOrCreate(['slug' => $round['slug']], $round);
        }
    }
}
