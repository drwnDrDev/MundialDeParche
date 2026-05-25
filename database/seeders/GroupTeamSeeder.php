<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Team;
use Illuminate\Database\Seeder;

class GroupTeamSeeder extends Seeder
{
    /**
     * Official FIFA World Cup 2026 group assignments.
     * Source: FIFA official draw, December 5, 2025.
     * Flags via flagcdn.com (ISO 3166-1 alpha-2 codes).
     */
    public function run(): void
    {
        $groups = [
            'A' => [
                ['name' => 'México',              'fifa_code' => 'MEX', 'flag_url' => 'https://flagcdn.com/w80/mx.png'],
                ['name' => 'Sudáfrica',            'fifa_code' => 'RSA', 'flag_url' => 'https://flagcdn.com/w80/za.png'],
                ['name' => 'Corea del Sur',        'fifa_code' => 'KOR', 'flag_url' => 'https://flagcdn.com/w80/kr.png'],
                ['name' => 'República Checa',      'fifa_code' => 'CZE', 'flag_url' => 'https://flagcdn.com/w80/cz.png'],
            ],
            'B' => [
                ['name' => 'Canadá',               'fifa_code' => 'CAN', 'flag_url' => 'https://flagcdn.com/w80/ca.png'],
                ['name' => 'Suiza',                'fifa_code' => 'SUI', 'flag_url' => 'https://flagcdn.com/w80/ch.png'],
                ['name' => 'Catar',                'fifa_code' => 'QAT', 'flag_url' => 'https://flagcdn.com/w80/qa.png'],
                ['name' => 'Bosnia y Herzegovina', 'fifa_code' => 'BIH', 'flag_url' => 'https://flagcdn.com/w80/ba.png'],
            ],
            'C' => [
                ['name' => 'Brasil',               'fifa_code' => 'BRA', 'flag_url' => 'https://flagcdn.com/w80/br.png'],
                ['name' => 'Marruecos',            'fifa_code' => 'MAR', 'flag_url' => 'https://flagcdn.com/w80/ma.png'],
                ['name' => 'Haití',                'fifa_code' => 'HTI', 'flag_url' => 'https://flagcdn.com/w80/ht.png'],
                ['name' => 'Escocia',              'fifa_code' => 'SCO', 'flag_url' => 'https://flagcdn.com/w80/gb-sct.png'],
            ],
            'D' => [
                ['name' => 'Estados Unidos',       'fifa_code' => 'USA', 'flag_url' => 'https://flagcdn.com/w80/us.png'],
                ['name' => 'Paraguay',             'fifa_code' => 'PAR', 'flag_url' => 'https://flagcdn.com/w80/py.png'],
                ['name' => 'Australia',            'fifa_code' => 'AUS', 'flag_url' => 'https://flagcdn.com/w80/au.png'],
                ['name' => 'Turquía',              'fifa_code' => 'TUR', 'flag_url' => 'https://flagcdn.com/w80/tr.png'],
            ],
            'E' => [
                ['name' => 'Alemania',             'fifa_code' => 'GER', 'flag_url' => 'https://flagcdn.com/w80/de.png'],
                ['name' => 'Curazao',              'fifa_code' => 'CUW', 'flag_url' => 'https://flagcdn.com/w80/cw.png'],
                ['name' => 'Costa de Marfil',      'fifa_code' => 'CIV', 'flag_url' => 'https://flagcdn.com/w80/ci.png'],
                ['name' => 'Ecuador',              'fifa_code' => 'ECU', 'flag_url' => 'https://flagcdn.com/w80/ec.png'],
            ],
            'F' => [
                ['name' => 'Países Bajos',         'fifa_code' => 'NED', 'flag_url' => 'https://flagcdn.com/w80/nl.png'],
                ['name' => 'Japón',                'fifa_code' => 'JPN', 'flag_url' => 'https://flagcdn.com/w80/jp.png'],
                ['name' => 'Túnez',                'fifa_code' => 'TUN', 'flag_url' => 'https://flagcdn.com/w80/tn.png'],
                ['name' => 'Suecia',               'fifa_code' => 'SWE', 'flag_url' => 'https://flagcdn.com/w80/se.png'],
            ],
            'G' => [
                ['name' => 'Bélgica',              'fifa_code' => 'BEL', 'flag_url' => 'https://flagcdn.com/w80/be.png'],
                ['name' => 'Egipto',               'fifa_code' => 'EGY', 'flag_url' => 'https://flagcdn.com/w80/eg.png'],
                ['name' => 'Irán',                 'fifa_code' => 'IRN', 'flag_url' => 'https://flagcdn.com/w80/ir.png'],
                ['name' => 'Nueva Zelanda',        'fifa_code' => 'NZL', 'flag_url' => 'https://flagcdn.com/w80/nz.png'],
            ],
            'H' => [
                ['name' => 'España',               'fifa_code' => 'ESP', 'flag_url' => 'https://flagcdn.com/w80/es.png'],
                ['name' => 'Cabo Verde',           'fifa_code' => 'CPV', 'flag_url' => 'https://flagcdn.com/w80/cv.png'],
                ['name' => 'Arabia Saudita',       'fifa_code' => 'KSA', 'flag_url' => 'https://flagcdn.com/w80/sa.png'],
                ['name' => 'Uruguay',              'fifa_code' => 'URU', 'flag_url' => 'https://flagcdn.com/w80/uy.png'],
            ],
            'I' => [
                ['name' => 'Francia',              'fifa_code' => 'FRA', 'flag_url' => 'https://flagcdn.com/w80/fr.png'],
                ['name' => 'Senegal',              'fifa_code' => 'SEN', 'flag_url' => 'https://flagcdn.com/w80/sn.png'],
                ['name' => 'Noruega',              'fifa_code' => 'NOR', 'flag_url' => 'https://flagcdn.com/w80/no.png'],
                ['name' => 'Irak',                 'fifa_code' => 'IRQ', 'flag_url' => 'https://flagcdn.com/w80/iq.png'],
            ],
            'J' => [
                ['name' => 'Argentina',            'fifa_code' => 'ARG', 'flag_url' => 'https://flagcdn.com/w80/ar.png'],
                ['name' => 'Argelia',              'fifa_code' => 'ALG', 'flag_url' => 'https://flagcdn.com/w80/dz.png'],
                ['name' => 'Austria',              'fifa_code' => 'AUT', 'flag_url' => 'https://flagcdn.com/w80/at.png'],
                ['name' => 'Jordania',             'fifa_code' => 'JOR', 'flag_url' => 'https://flagcdn.com/w80/jo.png'],
            ],
            'K' => [
                ['name' => 'Portugal',             'fifa_code' => 'POR', 'flag_url' => 'https://flagcdn.com/w80/pt.png'],
                ['name' => 'Colombia',             'fifa_code' => 'COL', 'flag_url' => 'https://flagcdn.com/w80/co.png'],
                ['name' => 'Uzbekistán',           'fifa_code' => 'UZB', 'flag_url' => 'https://flagcdn.com/w80/uz.png'],
                ['name' => 'Congo DR',             'fifa_code' => 'COD', 'flag_url' => 'https://flagcdn.com/w80/cd.png'],
            ],
            'L' => [
                ['name' => 'Inglaterra',           'fifa_code' => 'ENG', 'flag_url' => 'https://flagcdn.com/w80/gb-eng.png'],
                ['name' => 'Croacia',              'fifa_code' => 'CRO', 'flag_url' => 'https://flagcdn.com/w80/hr.png'],
                ['name' => 'Ghana',                'fifa_code' => 'GHA', 'flag_url' => 'https://flagcdn.com/w80/gh.png'],
                ['name' => 'Panamá',               'fifa_code' => 'PAN', 'flag_url' => 'https://flagcdn.com/w80/pa.png'],
            ],
        ];

        foreach ($groups as $groupName => $teams) {
            $group = Group::firstOrCreate(['name' => $groupName]);

            foreach ($teams as $teamData) {
                Team::updateOrCreate(
                    ['fifa_code' => $teamData['fifa_code']],
                    array_merge($teamData, ['group_id' => $group->id])
                );
            }
        }
    }
}
