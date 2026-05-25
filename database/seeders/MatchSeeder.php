<?php

namespace Database\Seeders;

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Database\Seeder;

class MatchSeeder extends Seeder
{
    /**
     * Official FIFA World Cup 2026 complete calendar (104 matches).
     * Times in Eastern Time (ET). Source: FIFA official calendar.
     * Knockout matches have null team IDs — updated as tournament progresses.
     */
    public function run(): void
    {
        $rounds = Round::all()->keyBy('slug');
        $teams  = Team::all()->keyBy('fifa_code');
        $groups = Group::all()->keyBy('name');

        $r1   = $rounds['grupos'];
        $r2   = $rounds['r32-r16'];
        $r3   = $rounds['qf-sf'];
        $r4   = $rounds['final'];

        // --- FASE DE GRUPOS (M1–M72) ---
        $groupMatches = [
            // Grupo A
            [1,  'A', '2026-06-11 15:00:00', 'MEX', 'RSA'],
            [2,  'A', '2026-06-11 22:00:00', 'KOR', 'CZE'],
            [3,  'A', '2026-06-18 12:00:00', 'CZE', 'RSA'],
            [4,  'A', '2026-06-18 21:00:00', 'MEX', 'KOR'],
            [5,  'A', '2026-06-24 21:00:00', 'CZE', 'MEX'],
            [6,  'A', '2026-06-24 21:00:00', 'RSA', 'KOR'],
            // Grupo B
            [7,  'B', '2026-06-12 15:00:00', 'CAN', 'BIH'],
            [8,  'B', '2026-06-13 15:00:00', 'QAT', 'SUI'],
            [9,  'B', '2026-06-18 15:00:00', 'SUI', 'BIH'],
            [10, 'B', '2026-06-18 18:00:00', 'CAN', 'QAT'],
            [11, 'B', '2026-06-24 15:00:00', 'SUI', 'CAN'],
            [12, 'B', '2026-06-24 15:00:00', 'BIH', 'QAT'],
            // Grupo C
            [13, 'C', '2026-06-13 18:00:00', 'BRA', 'MAR'],
            [14, 'C', '2026-06-13 21:00:00', 'HTI', 'SCO'],
            [15, 'C', '2026-06-19 18:00:00', 'SCO', 'MAR'],
            [16, 'C', '2026-06-19 21:00:00', 'BRA', 'HTI'],
            [17, 'C', '2026-06-24 18:00:00', 'SCO', 'BRA'],
            [18, 'C', '2026-06-24 18:00:00', 'MAR', 'HTI'],
            // Grupo D
            [19, 'D', '2026-06-12 21:00:00', 'USA', 'PAR'],
            [20, 'D', '2026-06-13 00:00:00', 'AUS', 'TUR'],
            [21, 'D', '2026-06-19 15:00:00', 'USA', 'AUS'],
            [22, 'D', '2026-06-19 00:00:00', 'TUR', 'PAR'],
            [23, 'D', '2026-06-25 22:00:00', 'TUR', 'USA'],
            [24, 'D', '2026-06-25 22:00:00', 'PAR', 'AUS'],
            // Grupo E
            [25, 'E', '2026-06-14 13:00:00', 'GER', 'CUW'],
            [26, 'E', '2026-06-14 19:00:00', 'CIV', 'ECU'],
            [27, 'E', '2026-06-20 16:00:00', 'GER', 'CIV'],
            [28, 'E', '2026-06-20 20:00:00', 'ECU', 'CUW'],
            [29, 'E', '2026-06-25 16:00:00', 'ECU', 'GER'],
            [30, 'E', '2026-06-25 16:00:00', 'CUW', 'CIV'],
            // Grupo F
            [31, 'F', '2026-06-14 16:00:00', 'NED', 'JPN'],
            [32, 'F', '2026-06-14 22:00:00', 'SWE', 'TUN'],
            [33, 'F', '2026-06-20 13:00:00', 'NED', 'SWE'],
            [34, 'F', '2026-06-20 00:00:00', 'TUN', 'JPN'],
            [35, 'F', '2026-06-25 19:00:00', 'JPN', 'SWE'],
            [36, 'F', '2026-06-25 19:00:00', 'TUN', 'NED'],
            // Grupo G
            [37, 'G', '2026-06-15 15:00:00', 'BEL', 'EGY'],
            [38, 'G', '2026-06-15 21:00:00', 'IRN', 'NZL'],
            [39, 'G', '2026-06-21 15:00:00', 'BEL', 'IRN'],
            [40, 'G', '2026-06-21 21:00:00', 'NZL', 'EGY'],
            [41, 'G', '2026-06-26 23:00:00', 'EGY', 'IRN'],
            [42, 'G', '2026-06-26 23:00:00', 'NZL', 'BEL'],
            // Grupo H
            [43, 'H', '2026-06-15 12:00:00', 'ESP', 'CPV'],
            [44, 'H', '2026-06-15 18:00:00', 'KSA', 'URU'],
            [45, 'H', '2026-06-21 12:00:00', 'ESP', 'KSA'],
            [46, 'H', '2026-06-21 18:00:00', 'URU', 'CPV'],
            [47, 'H', '2026-06-26 20:00:00', 'CPV', 'KSA'],
            [48, 'H', '2026-06-26 20:00:00', 'URU', 'ESP'],
            // Grupo I
            [49, 'I', '2026-06-16 15:00:00', 'FRA', 'SEN'],
            [50, 'I', '2026-06-16 18:00:00', 'IRQ', 'NOR'],
            [51, 'I', '2026-06-22 17:00:00', 'FRA', 'IRQ'],
            [52, 'I', '2026-06-22 20:00:00', 'NOR', 'SEN'],
            [53, 'I', '2026-06-26 15:00:00', 'NOR', 'FRA'],
            [54, 'I', '2026-06-26 15:00:00', 'SEN', 'IRQ'],
            // Grupo J
            [55, 'J', '2026-06-16 21:00:00', 'ARG', 'ALG'],
            [56, 'J', '2026-06-16 00:00:00', 'AUT', 'JOR'],
            [57, 'J', '2026-06-22 13:00:00', 'ARG', 'AUT'],
            [58, 'J', '2026-06-22 23:00:00', 'JOR', 'ALG'],
            [59, 'J', '2026-06-27 22:00:00', 'ALG', 'AUT'],
            [60, 'J', '2026-06-27 22:00:00', 'JOR', 'ARG'],
            // Grupo K
            [61, 'K', '2026-06-17 13:00:00', 'POR', 'COD'],
            [62, 'K', '2026-06-17 22:00:00', 'UZB', 'COL'],
            [63, 'K', '2026-06-23 13:00:00', 'POR', 'UZB'],
            [64, 'K', '2026-06-23 22:00:00', 'COL', 'COD'],
            [65, 'K', '2026-06-27 19:30:00', 'COL', 'POR'],
            [66, 'K', '2026-06-27 19:30:00', 'COD', 'UZB'],
            // Grupo L
            [67, 'L', '2026-06-17 16:00:00', 'ENG', 'CRO'],
            [68, 'L', '2026-06-17 19:00:00', 'GHA', 'PAN'],
            [69, 'L', '2026-06-23 16:00:00', 'ENG', 'GHA'],
            [70, 'L', '2026-06-23 19:00:00', 'PAN', 'CRO'],
            [71, 'L', '2026-06-27 17:00:00', 'PAN', 'ENG'],
            [72, 'L', '2026-06-27 17:00:00', 'CRO', 'GHA'],
        ];

        foreach ($groupMatches as [$num, $group, $date, $home, $away]) {
            Fixture::firstOrCreate(
                ['match_number' => $num, 'round_id' => $r1->id],
                [
                    'group_id'     => $groups[$group]->id,
                    'match_date'   => $date,
                    'home_team_id' => $teams[$home]->id,
                    'away_team_id' => $teams[$away]->id,
                    'status'       => 'scheduled',
                ]
            );
        }

        // --- ROUND OF 32 + ROUND OF 16 (M73–M96) ---
        $r2Matches = [
            // Round of 32
            [73, '2026-06-28 15:00:00', 'Subcampeón A',          'Subcampeón B'],
            [74, '2026-06-29 16:30:00', 'Ganador E',             '3° mejor A/B/C/D/F'],
            [75, '2026-06-29 21:00:00', 'Ganador F',             'Subcampeón C'],
            [76, '2026-06-29 13:00:00', 'Ganador C',             'Subcampeón F'],
            [77, '2026-06-30 17:00:00', 'Ganador I',             '3° mejor C/D/F/G/H'],
            [78, '2026-06-30 13:00:00', 'Subcampeón E',          'Subcampeón I'],
            [79, '2026-06-30 21:00:00', 'Ganador A',             '3° mejor C/E/F/H/I'],
            [80, '2026-07-01 12:00:00', 'Ganador L',             '3° mejor E/H/I/J/K'],
            [81, '2026-07-01 16:00:00', 'Ganador D',             '3° mejor B/E/F/I/J'],
            [82, '2026-07-01 16:00:00', 'Ganador G',             '3° mejor A/E/H/I/J'],
            [83, '2026-07-02 13:00:00', 'Subcampeón K',          'Subcampeón L'],
            [84, '2026-07-02 17:00:00', 'Ganador H',             'Subcampeón J'],
            [85, '2026-07-02 21:00:00', 'Ganador B',             '3° mejor E/F/G/I/J'],
            [86, '2026-07-03 13:00:00', 'Ganador J',             'Subcampeón H'],
            [87, '2026-07-03 17:00:00', 'Ganador K',             '3° mejor D/E/I/J/L'],
            [88, '2026-07-03 21:00:00', 'Subcampeón D',          'Subcampeón G'],
            // Round of 16
            [89, '2026-07-04 12:00:00', 'Ganador M73',           'Ganador M75'],
            [90, '2026-07-04 16:00:00', 'Ganador M74',           'Ganador M77'],
            [91, '2026-07-04 20:00:00', 'Ganador M76',           'Ganador M78'],
            [92, '2026-07-05 12:00:00', 'Ganador M79',           'Ganador M80'],
            [93, '2026-07-05 16:00:00', 'Ganador M83',           'Ganador M84'],
            [94, '2026-07-05 20:00:00', 'Ganador M81',           'Ganador M82'],
            [95, '2026-07-06 12:00:00', 'Ganador M85',           'Ganador M86'],
            [96, '2026-07-06 16:00:00', 'Ganador M87',           'Ganador M88'],
        ];

        foreach ($r2Matches as [$num, $date, $homePlaceholder, $awayPlaceholder]) {
            Fixture::firstOrCreate(
                ['match_number' => $num, 'round_id' => $r2->id],
                [
                    'group_id'         => null,
                    'match_date'       => $date,
                    'home_team_id'     => null,
                    'away_team_id'     => null,
                    'home_placeholder' => $homePlaceholder,
                    'away_placeholder' => $awayPlaceholder,
                    'status'           => 'scheduled',
                ]
            );
        }

        // --- CUARTOS + SEMIS (M97–M102) ---
        $r3Matches = [
            // Cuartos de final
            [97,  '2026-07-08 12:00:00', 'Ganador M89', 'Ganador M90'],
            [98,  '2026-07-08 16:00:00', 'Ganador M91', 'Ganador M92'],
            [99,  '2026-07-09 12:00:00', 'Ganador M93', 'Ganador M94'],
            [100, '2026-07-09 16:00:00', 'Ganador M95', 'Ganador M96'],
            // Semifinales
            [101, '2026-07-12 16:00:00', 'Ganador M97', 'Ganador M98'],
            [102, '2026-07-13 16:00:00', 'Ganador M99', 'Ganador M100'],
        ];

        foreach ($r3Matches as [$num, $date, $homePlaceholder, $awayPlaceholder]) {
            Fixture::firstOrCreate(
                ['match_number' => $num, 'round_id' => $r3->id],
                [
                    'group_id'         => null,
                    'match_date'       => $date,
                    'home_team_id'     => null,
                    'away_team_id'     => null,
                    'home_placeholder' => $homePlaceholder,
                    'away_placeholder' => $awayPlaceholder,
                    'status'           => 'scheduled',
                ]
            );
        }

        // --- TERCER PUESTO + FINAL (M103–M104) ---
        $r4Matches = [
            [103, '2026-07-18 15:00:00', 'Perdedor M101', 'Perdedor M102'],
            [104, '2026-07-19 15:00:00', 'Ganador M101',  'Ganador M102'],
        ];

        foreach ($r4Matches as [$num, $date, $homePlaceholder, $awayPlaceholder]) {
            Fixture::firstOrCreate(
                ['match_number' => $num, 'round_id' => $r4->id],
                [
                    'group_id'         => null,
                    'match_date'       => $date,
                    'home_team_id'     => null,
                    'away_team_id'     => null,
                    'home_placeholder' => $homePlaceholder,
                    'away_placeholder' => $awayPlaceholder,
                    'status'           => 'scheduled',
                ]
            );
        }
    }
}
