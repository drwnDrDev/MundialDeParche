<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Round;
use Illuminate\Http\RedirectResponse;

class BracketController extends Controller
{
    // Mapa de slots R32: match_number => [home_type, home_source, away_type, away_source]
    // type: 'winner' | 'runner_up' | 'third'
    // source: letra del grupo (winner/runner_up) | array de grupos elegibles (third)
    private const SLOT_MAP = [
        73 => ['runner_up', 'A', 'runner_up', 'B'],
        74 => ['winner',    'E', 'third',     ['A','B','C','D','F']],
        75 => ['winner',    'F', 'runner_up', 'C'],
        76 => ['winner',    'C', 'runner_up', 'F'],
        77 => ['winner',    'I', 'third',     ['C','D','F','G','H']],
        78 => ['runner_up', 'E', 'runner_up', 'I'],
        79 => ['winner',    'A', 'third',     ['C','E','F','H','I']],
        80 => ['winner',    'L', 'third',     ['E','H','I','J','K']],
        81 => ['winner',    'D', 'third',     ['B','E','F','I','J']],
        82 => ['winner',    'G', 'third',     ['A','E','H','I','J']],
        83 => ['runner_up', 'K', 'runner_up', 'L'],
        84 => ['winner',    'H', 'runner_up', 'J'],
        85 => ['winner',    'B', 'third',     ['E','F','G','I','J']],
        86 => ['winner',    'J', 'runner_up', 'H'],
        87 => ['winner',    'K', 'third',     ['D','E','I','J','L']],
        88 => ['runner_up', 'D', 'runner_up', 'G'],
    ];

    public function populateR32(): RedirectResponse
    {
        $gruposRound = Round::where('slug', 'grupos')->firstOrFail();

        if (! $gruposRound->is_finalized) {
            return back()->with('error', 'La ronda de grupos debe estar finalizada antes de poblar el bracket R32.');
        }

        // Cargar todos los fixtures de grupos con su grupo asociado
        $fixtures = Fixture::where('round_id', $gruposRound->id)
            ->whereNotNull('group_id')
            ->with('group')
            ->get();

        // Calcular standings por grupo
        $standings = [];  // ['A' => ['winner' => id, 'runner_up' => id], ...]
        $allThirds  = [];  // [['team_id' => id, 'group' => 'A', 'pts' => n, 'gd' => n, 'gf' => n], ...]

        foreach ($fixtures->groupBy('group_id') as $groupFixtures) {
            $groupName = $groupFixtures->first()->group->name;
            $table     = $this->buildTable($groupFixtures);

            if (count($table) < 2) continue;

            $standings[$groupName] = [
                'winner'    => $table[0]['team_id'],
                'runner_up' => $table[1]['team_id'],
            ];

            if (isset($table[2])) {
                $allThirds[] = array_merge($table[2], ['group' => $groupName]);
            }
        }

        // Ordenar terceros y tomar los 8 mejores
        usort($allThirds, fn ($a, $b) =>
            $b['pts'] <=> $a['pts'] ?: $b['gd'] <=> $a['gd'] ?: $b['gf'] <=> $a['gf']
        );
        $qualifiedThirds = array_slice($allThirds, 0, 8);

        // Asignar terceros a slots con backtracking para garantizar solución válida
        $thirdSlotNumbers = [74, 77, 79, 80, 81, 82, 85, 87];
        $thirdEligible    = [];
        foreach (self::SLOT_MAP as $matchNum => [, , $awayType, $awaySource]) {
            if ($awayType === 'third') {
                $thirdEligible[$matchNum] = $awaySource;
            }
        }

        $thirdAssignment = [];
        $this->assignThirds(0, $thirdSlotNumbers, $thirdEligible, $qualifiedThirds, $thirdAssignment, []);

        // Poblar fixtures R32
        $r32Fixtures = Fixture::whereBetween('match_number', [73, 88])
            ->whereIn('status', ['scheduled'])
            ->get()
            ->keyBy('match_number');

        $updated = 0;

        foreach (self::SLOT_MAP as $matchNum => [$homeType, $homeSource, $awayType, $awaySource]) {
            $fixture = $r32Fixtures[$matchNum] ?? null;
            if (! $fixture) continue;

            $homeId = $this->resolveSlot($homeType, $homeSource, $standings, $thirdAssignment, $matchNum);
            $awayId = $this->resolveSlot($awayType, $awaySource, $standings, $thirdAssignment, $matchNum);

            if ($homeId || $awayId) {
                $fixture->update([
                    'home_team_id' => $homeId ?? $fixture->home_team_id,
                    'away_team_id' => $awayId ?? $fixture->away_team_id,
                ]);
                $updated++;
            }
        }

        return back()->with('status', "Bracket R32 poblado correctamente: {$updated} partidos actualizados.");
    }

    private function resolveSlot(string $type, mixed $source, array $standings, array $thirds, int $matchNum): ?int
    {
        return match ($type) {
            'winner'    => $standings[$source]['winner']    ?? null,
            'runner_up' => $standings[$source]['runner_up'] ?? null,
            'third'     => $thirds[$matchNum]               ?? null,
            default     => null,
        };
    }

    // Backtracking: garantiza asignación válida de los 8 terceros a los 8 slots
    private function assignThirds(
        int   $idx,
        array $slots,
        array $eligible,
        array $thirds,
        array &$result,
        array $used
    ): bool {
        if ($idx >= count($slots)) return true;

        $matchNum      = $slots[$idx];
        $eligibleGroups = $eligible[$matchNum];

        foreach ($thirds as $third) {
            if (in_array($third['team_id'], $used)) continue;
            if (! in_array($third['group'], $eligibleGroups)) continue;

            $result[$matchNum] = $third['team_id'];
            $used[]            = $third['team_id'];

            if ($this->assignThirds($idx + 1, $slots, $eligible, $thirds, $result, $used)) {
                return true;
            }

            unset($result[$matchNum]);
            array_pop($used);
        }

        return false;
    }

    private function buildTable(\Illuminate\Support\Collection $fixtures): array
    {
        $table = [];

        foreach ($fixtures as $f) {
            if ($f->home_team_id) $table[$f->home_team_id] ??= ['team_id' => $f->home_team_id, 'pts' => 0, 'gd' => 0, 'gf' => 0];
            if ($f->away_team_id) $table[$f->away_team_id] ??= ['team_id' => $f->away_team_id, 'pts' => 0, 'gd' => 0, 'gf' => 0];
        }

        foreach ($fixtures as $f) {
            $h = $f->home_score;
            $a = $f->away_score;

            if ($h === null || $a === null || ! $f->home_team_id || ! $f->away_team_id) continue;

            $table[$f->home_team_id]['gf'] += $h;
            $table[$f->home_team_id]['gd'] += ($h - $a);
            $table[$f->away_team_id]['gf'] += $a;
            $table[$f->away_team_id]['gd'] += ($a - $h);

            if ($h > $a)      { $table[$f->home_team_id]['pts'] += 3; }
            elseif ($h < $a)  { $table[$f->away_team_id]['pts'] += 3; }
            else               { $table[$f->home_team_id]['pts'] += 1; $table[$f->away_team_id]['pts'] += 1; }
        }

        usort($table, fn ($a, $b) =>
            $b['pts'] <=> $a['pts'] ?: $b['gd'] <=> $a['gd'] ?: $b['gf'] <=> $a['gf']
        );

        return array_values($table);
    }
}
