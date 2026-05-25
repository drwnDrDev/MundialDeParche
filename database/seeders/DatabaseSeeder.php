<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoundSeeder::class,
            GroupTeamSeeder::class,
            MatchSeeder::class,
            TopScorerSeeder::class,
            DevelopmentUserSeeder::class,
        ]);
    }
}
