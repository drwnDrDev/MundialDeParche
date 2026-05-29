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
     * Venues sourced from wc2026_matches_complete.csv.
     */

    // Display names for UI (short, uppercase, Spanish-friendly)
    private const VENUES = [
        1  => 'AZTECA',        // Estadio Azteca, Mexico City
        2  => 'MONTERREY',     // Estadio BBVA, Monterrey
        3  => 'GUADALAJARA',   // Estadio Akron, Guadalajara
        4  => 'NEW YORK',      // MetLife Stadium, East Rutherford NJ
        5  => 'FILADELFIA',    // Lincoln Financial Field, Philadelphia
        6  => 'MIAMI',         // Hard Rock Stadium, Miami
        7  => 'ATLANTA',       // Mercedes-Benz Stadium, Atlanta
        8  => 'DALLAS',        // AT&T Stadium, Arlington TX
        9  => 'L. ÁNGELES',    // SoFi Stadium, Inglewood CA
        10 => 'SANTA CLARA',   // Levi's Stadium, Santa Clara CA
        11 => 'SEATTLE',       // Lumen Field, Seattle
        12 => 'BOSTON',        // Gillette Stadium, Foxborough MA
        13 => 'TORONTO',       // BMO Field, Toronto
        14 => 'VANCOUVER',     // BC Place, Vancouver
        15 => 'EDMONTON',      // Commonwealth Stadium
        16 => 'MONTREAL',      // Olympic Stadium
    ];

    public function run(): void
    {
        $rounds = Round::all()->keyBy('slug');
        $teams  = Team::all()->keyBy('fifa_code');
        $groups = Group::all()->keyBy('name');

        $r1 = $rounds['grupos'];
        $r2 = $rounds['r32'];
        $r3 = $rounds['f3'];
        $r4 = $rounds['f4'];

        $v = self::VENUES;

        // --- FASE DE GRUPOS (M1–M72) ---
        // [match_number, group, date, home, away, venue_id]
        $groupMatches = [
            // Grupo A
            [1,  'A', '2026-06-11 15:00:00', 'MEX', 'RSA', 1],
            [2,  'A', '2026-06-11 22:00:00', 'KOR', 'CZE', 3],
            [3,  'A', '2026-06-18 12:00:00', 'CZE', 'RSA', 7],
            [4,  'A', '2026-06-18 21:00:00', 'MEX', 'KOR', 3],
            [5,  'A', '2026-06-24 21:00:00', 'CZE', 'MEX', 1],
            [6,  'A', '2026-06-24 21:00:00', 'RSA', 'KOR', 2],
            // Grupo B
            [7,  'B', '2026-06-12 15:00:00', 'CAN', 'BIH', 13],
            [8,  'B', '2026-06-13 15:00:00', 'QAT', 'SUI', 4],
            [9,  'B', '2026-06-18 15:00:00', 'SUI', 'BIH', 6],
            [10, 'B', '2026-06-18 18:00:00', 'CAN', 'QAT', 13],
            [11, 'B', '2026-06-24 15:00:00', 'SUI', 'CAN', 13],
            [12, 'B', '2026-06-24 15:00:00', 'BIH', 'QAT', 4],
            // Grupo C
            [13, 'C', '2026-06-13 18:00:00', 'BRA', 'MAR', 5],
            [14, 'C', '2026-06-13 21:00:00', 'HTI', 'SCO', 8],
            [15, 'C', '2026-06-19 18:00:00', 'SCO', 'MAR', 7],
            [16, 'C', '2026-06-19 21:00:00', 'BRA', 'HTI', 5],
            [17, 'C', '2026-06-24 18:00:00', 'SCO', 'BRA', 5],
            [18, 'C', '2026-06-24 18:00:00', 'MAR', 'HTI', 8],
            // Grupo D
            [19, 'D', '2026-06-12 21:00:00', 'USA', 'PAR', 6],
            [20, 'D', '2026-06-13 00:00:00', 'AUS', 'TUR', 4],
            [21, 'D', '2026-06-19 15:00:00', 'USA', 'AUS', 8],
            [22, 'D', '2026-06-19 00:00:00', 'TUR', 'PAR', 5],
            [23, 'D', '2026-06-25 22:00:00', 'TUR', 'USA', 4],
            [24, 'D', '2026-06-25 22:00:00', 'PAR', 'AUS', 6],
            // Grupo E
            [25, 'E', '2026-06-14 13:00:00', 'GER', 'CUW', 12],
            [26, 'E', '2026-06-14 19:00:00', 'CIV', 'ECU', 11],
            [27, 'E', '2026-06-20 16:00:00', 'GER', 'CIV', 7],
            [28, 'E', '2026-06-20 20:00:00', 'ECU', 'CUW', 5],
            [29, 'E', '2026-06-25 16:00:00', 'ECU', 'GER', 8],
            [30, 'E', '2026-06-25 16:00:00', 'CUW', 'CIV', 4],
            // Grupo F
            [31, 'F', '2026-06-14 16:00:00', 'NED', 'JPN', 10],
            [32, 'F', '2026-06-14 22:00:00', 'SWE', 'TUN', 9],
            [33, 'F', '2026-06-20 13:00:00', 'NED', 'SWE', 11],
            [34, 'F', '2026-06-20 00:00:00', 'TUN', 'JPN', 6],
            [35, 'F', '2026-06-25 19:00:00', 'JPN', 'SWE', 10],
            [36, 'F', '2026-06-25 19:00:00', 'TUN', 'NED', 12],
            // Grupo G
            [37, 'G', '2026-06-15 15:00:00', 'BEL', 'EGY', 7],
            [38, 'G', '2026-06-15 21:00:00', 'IRN', 'NZL', 5],
            [39, 'G', '2026-06-21 15:00:00', 'BEL', 'IRN', 4],
            [40, 'G', '2026-06-21 21:00:00', 'NZL', 'EGY', 12],
            [41, 'G', '2026-06-26 23:00:00', 'EGY', 'IRN', 11],
            [42, 'G', '2026-06-26 23:00:00', 'NZL', 'BEL', 10],
            // Grupo H
            [43, 'H', '2026-06-15 12:00:00', 'ESP', 'CPV', 6],
            [44, 'H', '2026-06-15 18:00:00', 'KSA', 'URU', 8],
            [45, 'H', '2026-06-21 12:00:00', 'ESP', 'KSA', 4],
            [46, 'H', '2026-06-21 18:00:00', 'URU', 'CPV', 5],
            [47, 'H', '2026-06-26 20:00:00', 'CPV', 'KSA', 12],
            [48, 'H', '2026-06-26 20:00:00', 'URU', 'ESP', 4],
            // Grupo I
            [49, 'I', '2026-06-16 15:00:00', 'FRA', 'SEN', 5],
            [50, 'I', '2026-06-16 18:00:00', 'IRQ', 'NOR', 10],
            [51, 'I', '2026-06-22 17:00:00', 'FRA', 'IRQ', 7],
            [52, 'I', '2026-06-22 20:00:00', 'NOR', 'SEN', 12],
            [53, 'I', '2026-06-26 15:00:00', 'NOR', 'FRA', 11],
            [54, 'I', '2026-06-26 15:00:00', 'SEN', 'IRQ', 8],
            // Grupo J
            [55, 'J', '2026-06-16 21:00:00', 'ARG', 'ALG', 6],
            [56, 'J', '2026-06-16 00:00:00', 'AUT', 'JOR', 4],
            [57, 'J', '2026-06-22 13:00:00', 'ARG', 'AUT', 5],
            [58, 'J', '2026-06-22 23:00:00', 'JOR', 'ALG', 8],
            [59, 'J', '2026-06-27 22:00:00', 'ALG', 'AUT', 12],
            [60, 'J', '2026-06-27 22:00:00', 'JOR', 'ARG', 6],
            // Grupo K
            [61, 'K', '2026-06-17 13:00:00', 'POR', 'COD', 7],
            [62, 'K', '2026-06-17 22:00:00', 'UZB', 'COL', 9],
            [63, 'K', '2026-06-23 13:00:00', 'POR', 'UZB', 10],
            [64, 'K', '2026-06-23 22:00:00', 'COL', 'COD', 11],
            [65, 'K', '2026-06-27 19:30:00', 'COL', 'POR', 5],
            [66, 'K', '2026-06-27 19:30:00', 'COD', 'UZB', 8],
            // Grupo L
            [67, 'L', '2026-06-17 16:00:00', 'ENG', 'CRO', 4],
            [68, 'L', '2026-06-17 19:00:00', 'GHA', 'PAN', 5],
            [69, 'L', '2026-06-23 16:00:00', 'ENG', 'GHA', 12],
            [70, 'L', '2026-06-23 19:00:00', 'PAN', 'CRO', 6],
            [71, 'L', '2026-06-27 17:00:00', 'PAN', 'ENG', 4],
            [72, 'L', '2026-06-27 17:00:00', 'CRO', 'GHA', 11],
        ];

        foreach ($groupMatches as [$num, $group, $date, $home, $away, $venueId]) {
            Fixture::firstOrCreate(
                ['match_number' => $num, 'round_id' => $r1->id],
                [
                    'group_id'     => $groups[$group]->id,
                    'match_date'   => $date,
                    'home_team_id' => $teams[$home]->id,
                    'away_team_id' => $teams[$away]->id,
                    'venue'        => $v[$venueId],
                    'status'       => 'scheduled',
                ]
            );
        }

        // --- F2: ROUND OF 32 (M73–M88) ---
        $f2Matches = [
            [73, '2026-06-28 15:00:00', 'Subcampeón A',          'Subcampeón B',          9],
            [74, '2026-06-29 16:30:00', 'Ganador E',             '3° mejor A/B/C/D/F',    12],
            [75, '2026-06-29 21:00:00', 'Ganador F',             'Subcampeón C',          2],
            [76, '2026-06-29 13:00:00', 'Ganador C',             'Subcampeón F',          8],
            [77, '2026-06-30 17:00:00', 'Ganador I',             '3° mejor C/D/F/G/H',   4],
            [78, '2026-06-30 13:00:00', 'Subcampeón E',          'Subcampeón I',          8],
            [79, '2026-06-30 21:00:00', 'Ganador A',             '3° mejor C/E/F/H/I',   1],
            [80, '2026-07-01 12:00:00', 'Ganador L',             '3° mejor E/H/I/J/K',   7],
            [81, '2026-07-01 16:00:00', 'Ganador D',             '3° mejor B/E/F/I/J',   10],
            [82, '2026-07-01 16:00:00', 'Ganador G',             '3° mejor A/E/H/I/J',   11],
            [83, '2026-07-02 13:00:00', 'Subcampeón K',          'Subcampeón L',          5],
            [84, '2026-07-02 17:00:00', 'Ganador H',             'Subcampeón J',          6],
            [85, '2026-07-02 21:00:00', 'Ganador B',             '3° mejor E/F/G/I/J',   3],
            [86, '2026-07-03 13:00:00', 'Ganador J',             'Subcampeón H',          10],
            [87, '2026-07-03 17:00:00', 'Ganador K',             '3° mejor D/E/I/J/L',   7],
            [88, '2026-07-03 21:00:00', 'Subcampeón D',          'Subcampeón G',          8],
        ];

        foreach ($f2Matches as [$num, $date, $homePlaceholder, $awayPlaceholder, $venueId]) {
            Fixture::firstOrCreate(
                ['match_number' => $num, 'round_id' => $r2->id],
                [
                    'group_id'         => null,
                    'match_date'       => $date,
                    'home_team_id'     => null,
                    'away_team_id'     => null,
                    'home_placeholder' => $homePlaceholder,
                    'away_placeholder' => $awayPlaceholder,
                    'venue'            => $v[$venueId],
                    'status'           => 'scheduled',
                ]
            );
        }

        // --- F3: OCTAVOS (M89–M96) + CUARTOS (M97–M100) ---
        $f3Matches = [
            // Octavos de final
            [89,  '2026-07-04 12:00:00', 'Ganador M73', 'Ganador M75', 4],
            [90,  '2026-07-04 16:00:00', 'Ganador M74', 'Ganador M77', 6],
            [91,  '2026-07-04 20:00:00', 'Ganador M76', 'Ganador M78', 11],
            [92,  '2026-07-05 12:00:00', 'Ganador M79', 'Ganador M80', 7],
            [93,  '2026-07-05 16:00:00', 'Ganador M83', 'Ganador M84', 5],
            [94,  '2026-07-05 20:00:00', 'Ganador M81', 'Ganador M82', 10],
            [95,  '2026-07-06 12:00:00', 'Ganador M85', 'Ganador M86', 3],
            [96,  '2026-07-06 16:00:00', 'Ganador M87', 'Ganador M88', 8],
            // Cuartos de final
            [97,  '2026-07-08 12:00:00', 'Ganador M89', 'Ganador M90', 4],
            [98,  '2026-07-08 16:00:00', 'Ganador M91', 'Ganador M92', 7],
            [99,  '2026-07-09 12:00:00', 'Ganador M93', 'Ganador M94', 6],
            [100, '2026-07-09 16:00:00', 'Ganador M95', 'Ganador M96', 8],
        ];

        foreach ($f3Matches as [$num, $date, $homePlaceholder, $awayPlaceholder, $venueId]) {
            Fixture::firstOrCreate(
                ['match_number' => $num, 'round_id' => $r3->id],
                [
                    'group_id'         => null,
                    'match_date'       => $date,
                    'home_team_id'     => null,
                    'away_team_id'     => null,
                    'home_placeholder' => $homePlaceholder,
                    'away_placeholder' => $awayPlaceholder,
                    'venue'            => $v[$venueId],
                    'status'           => 'scheduled',
                ]
            );
        }

        // --- F4: SEMIS (M101–M102) + FINAL (M104) ---
        // M103 (3er puesto) is intentionally excluded from this quiniela.
        $f4Matches = [
            [101, '2026-07-12 16:00:00', 'Ganador M97',  'Ganador M98',  8],
            [102, '2026-07-13 16:00:00', 'Ganador M99',  'Ganador M100', 7],
            [104, '2026-07-19 15:00:00', 'Ganador M101', 'Ganador M102', 4],
        ];

        foreach ($f4Matches as [$num, $date, $homePlaceholder, $awayPlaceholder, $venueId]) {
            Fixture::firstOrCreate(
                ['match_number' => $num, 'round_id' => $r4->id],
                [
                    'group_id'         => null,
                    'match_date'       => $date,
                    'home_team_id'     => null,
                    'away_team_id'     => null,
                    'home_placeholder' => $homePlaceholder,
                    'away_placeholder' => $awayPlaceholder,
                    'venue'            => $v[$venueId],
                    'status'           => 'scheduled',
                ]
            );
        }

        // --- BRACKET LINKS ---
        // winner_feeds_match_id + winner_feeds_slot encodes the bracket tree.
        // Keyed by source match_number => [target match_number, slot]
        $allKnockout = Fixture::whereIn('match_number', array_merge(
            range(73, 102), [104]
        ))->get()->keyBy('match_number');

        $bracketLinks = [
            // R32 → Octavos
            73  => [89,  'home'],
            75  => [89,  'away'],
            74  => [90,  'home'],
            77  => [90,  'away'],
            76  => [91,  'home'],
            78  => [91,  'away'],
            79  => [92,  'home'],
            80  => [92,  'away'],
            83  => [93,  'home'],
            84  => [93,  'away'],
            81  => [94,  'home'],
            82  => [94,  'away'],
            85  => [95,  'home'],
            86  => [95,  'away'],
            87  => [96,  'home'],
            88  => [96,  'away'],
            // Octavos → Cuartos
            89  => [97,  'home'],
            90  => [97,  'away'],
            91  => [98,  'home'],
            92  => [98,  'away'],
            93  => [99,  'home'],
            94  => [99,  'away'],
            95  => [100, 'home'],
            96  => [100, 'away'],
            // Cuartos → Semis
            97  => [101, 'home'],
            98  => [101, 'away'],
            99  => [102, 'home'],
            100 => [102, 'away'],
            // Semis → Final
            101 => [104, 'home'],
            102 => [104, 'away'],
        ];

        foreach ($bracketLinks as $fromNum => [$toNum, $slot]) {
            $source = $allKnockout[$fromNum] ?? null;
            $target = $allKnockout[$toNum] ?? null;
            if ($source && $target) {
                $source->update([
                    'winner_feeds_match_id' => $target->id,
                    'winner_feeds_slot'     => $slot,
                ]);
            }
        }
    }
}
