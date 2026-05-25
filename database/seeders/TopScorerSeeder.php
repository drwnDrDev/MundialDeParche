<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TopScorerSeeder extends Seeder
{
    /**
     * Top 10 predicted Golden Boot candidates for FIFA World Cup 2026.
     * Based on betting odds analysis. NOT official FIFA data.
     * Update with real squad data when available.
     */
    public function run(): void
    {
        $players = [
            ['name' => 'Kylian Mbappé',      'team' => 'FRA'],
            ['name' => 'Harry Kane',           'team' => 'ENG'],
            ['name' => 'Erling Haaland',       'team' => 'NOR'],
            ['name' => 'Lamine Yamal',         'team' => 'ESP'],
            ['name' => 'Lionel Messi',         'team' => 'ARG'],
            ['name' => 'Cristiano Ronaldo',    'team' => 'POR'],
            ['name' => 'Vinícius Júnior',      'team' => 'BRA'],
            ['name' => 'Romelu Lukaku',        'team' => 'BEL'],
            ['name' => 'Neymar',               'team' => 'BRA'],
            ['name' => 'Lautaro Martínez',     'team' => 'ARG'],
        ];

        $teams = Team::all()->keyBy('fifa_code');

        foreach ($players as $player) {
            $team = $teams[$player['team']] ?? null;

            if (! $team) {
                continue;
            }

            Player::firstOrCreate(
                ['name' => $player['name'], 'team_id' => $team->id],
            );
        }
    }
}
