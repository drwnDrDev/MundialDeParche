<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Team;
use Illuminate\Database\Seeder;

class GroupTeamSeeder extends Seeder
{
    /**
     * Approximate group assignments for development.
     * Verify and update with official FIFA assignments before the tournament.
     * Official source: https://www.fifa.com/fifaplus/en/tournaments/mens/worldcup/canadamexicousa2026
     */
    public function run(): void
    {
        $groups = [
            'A' => [
                ['name' => 'México',         'fifa_code' => 'MEX'],
                ['name' => 'Estados Unidos',  'fifa_code' => 'USA'],
                ['name' => 'Uruguay',         'fifa_code' => 'URU'],
                ['name' => 'Panamá',          'fifa_code' => 'PAN'],
            ],
            'B' => [
                ['name' => 'Argentina',       'fifa_code' => 'ARG'],
                ['name' => 'Chile',           'fifa_code' => 'CHI'],
                ['name' => 'Perú',            'fifa_code' => 'PER'],
                ['name' => 'Australia',       'fifa_code' => 'AUS'],
            ],
            'C' => [
                ['name' => 'Brasil',          'fifa_code' => 'BRA'],
                ['name' => 'Colombia',        'fifa_code' => 'COL'],
                ['name' => 'Ecuador',         'fifa_code' => 'ECU'],
                ['name' => 'Alemania',        'fifa_code' => 'GER'],
            ],
            'D' => [
                ['name' => 'Francia',         'fifa_code' => 'FRA'],
                ['name' => 'España',          'fifa_code' => 'ESP'],
                ['name' => 'Portugal',        'fifa_code' => 'POR'],
                ['name' => 'Marruecos',       'fifa_code' => 'MAR'],
            ],
            'E' => [
                ['name' => 'Inglaterra',      'fifa_code' => 'ENG'],
                ['name' => 'Países Bajos',    'fifa_code' => 'NED'],
                ['name' => 'Japón',           'fifa_code' => 'JPN'],
                ['name' => 'Senegal',         'fifa_code' => 'SEN'],
            ],
            'F' => [
                ['name' => 'Italia',          'fifa_code' => 'ITA'],
                ['name' => 'Croacia',         'fifa_code' => 'CRO'],
                ['name' => 'Nigeria',         'fifa_code' => 'NGA'],
                ['name' => 'Venezuela',       'fifa_code' => 'VEN'],
            ],
            'G' => [
                ['name' => 'Bélgica',         'fifa_code' => 'BEL'],
                ['name' => 'Serbia',          'fifa_code' => 'SRB'],
                ['name' => 'Costa Rica',      'fifa_code' => 'CRC'],
                ['name' => 'República Checa', 'fifa_code' => 'CZE'],
            ],
            'H' => [
                ['name' => 'Suiza',           'fifa_code' => 'SUI'],
                ['name' => 'Turquía',         'fifa_code' => 'TUR'],
                ['name' => 'Corea del Sur',   'fifa_code' => 'KOR'],
                ['name' => 'Camerún',         'fifa_code' => 'CMR'],
            ],
            'I' => [
                ['name' => 'Dinamarca',       'fifa_code' => 'DEN'],
                ['name' => 'Austria',         'fifa_code' => 'AUT'],
                ['name' => 'Arabia Saudita',  'fifa_code' => 'KSA'],
                ['name' => 'Ghana',           'fifa_code' => 'GHA'],
            ],
            'J' => [
                ['name' => 'Polonia',         'fifa_code' => 'POL'],
                ['name' => 'Paraguay',        'fifa_code' => 'PAR'],
                ['name' => 'Irán',            'fifa_code' => 'IRN'],
                ['name' => 'Sudáfrica',       'fifa_code' => 'RSA'],
            ],
            'K' => [
                ['name' => 'Canadá',          'fifa_code' => 'CAN'],
                ['name' => 'Escocia',         'fifa_code' => 'SCO'],
                ['name' => 'Bolivia',         'fifa_code' => 'BOL'],
                ['name' => 'Argelia',         'fifa_code' => 'ALG'],
            ],
            'L' => [
                ['name' => 'Ucrania',         'fifa_code' => 'UKR'],
                ['name' => 'Hungría',         'fifa_code' => 'HUN'],
                ['name' => 'Malí',            'fifa_code' => 'MLI'],
                ['name' => 'Nueva Zelanda',   'fifa_code' => 'NZL'],
            ],
        ];

        foreach ($groups as $groupName => $teams) {
            $group = Group::firstOrCreate(['name' => $groupName]);

            foreach ($teams as $teamData) {
                Team::firstOrCreate(
                    ['fifa_code' => $teamData['fifa_code']],
                    array_merge($teamData, ['group_id' => $group->id])
                );
            }
        }
    }
}
