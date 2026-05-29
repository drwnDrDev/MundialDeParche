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
                'name'               => 'Fase de Grupos',
                'slug'               => 'grupos',
                'order'              => 1,
                'points_exact'       => 3,
                'points_result'      => 1,
                'points_classifier'  => 2,
            ],
            [
                'name'               => 'Round of 32',
                'slug'               => 'r32',
                'order'              => 2,
                'points_exact'       => 5,
                'points_result'      => 2,
                'points_classifier'  => 3,
            ],
            [
                'name'               => 'Octavos + Cuartos',
                'slug'               => 'f3',
                'order'              => 3,
                'points_exact'       => 8,
                'points_result'      => 3,
                'points_classifier'  => 5,
            ],
            [
                'name'               => 'Semis + Final',
                'slug'               => 'f4',
                'order'              => 4,
                'points_exact'       => 13,
                'points_result'      => 5,
                'points_classifier'  => 0,
            ],
        ];

        foreach ($rounds as $round) {
            Round::firstOrCreate(['slug' => $round['slug']], $round);
        }
    }
}
