<?php

namespace App\Services;

use Illuminate\Support\Collection;

class GroupStageClassifierService
{
    /**
     * Calcula los 32 clasificados de la fase de grupos.
     *
     * @param  Collection  $fixtures  Fixtures del round con homeTeam/awayTeam cargados
     * @param  callable    $getScores  fn(Fixture): [home_score, away_score]
     * @return array  Array de team_ids de los 32 clasificados
     */
    public function getClassifierIds(Collection $fixtures, callable $getScores): array
    {
        $byGroup     = $fixtures->groupBy('group_id');
        $classifiers = [];
        $thirds      = [];

        foreach ($byGroup as $groupFixtures) {
            $table = $this->buildGroupTable($groupFixtures, $getScores);

            if (count($table) < 2) continue;

            $classifiers[] = $table[0]['team_id'];
            $classifiers[] = $table[1]['team_id'];

            if (isset($table[2])) {
                $thirds[] = $table[2];
            }
        }

        if (count($thirds) > 0) {
            usort($thirds, fn ($a, $b) =>
                $b['pts'] <=> $a['pts']
                    ?: $b['gd'] <=> $a['gd']
                    ?: $b['gf'] <=> $a['gf']
            );

            foreach (array_slice($thirds, 0, 8) as $third) {
                $classifiers[] = $third['team_id'];
            }
        }

        return $classifiers;
    }

    /**
     * Construye la tabla de posiciones de un grupo.
     *
     * @return array  Filas ordenadas desc por pts → gd → gf.
     *                Cada fila: ['team_id', 'pts', 'gd', 'gf']
     */
    private function buildGroupTable(Collection $fixtures, callable $getScores): array
    {
        $table = [];

        foreach ($fixtures as $f) {
            if ($f->home_team_id) $table[$f->home_team_id] ??= ['team_id' => $f->home_team_id, 'pts' => 0, 'gd' => 0, 'gf' => 0];
            if ($f->away_team_id) $table[$f->away_team_id] ??= ['team_id' => $f->away_team_id, 'pts' => 0, 'gd' => 0, 'gf' => 0];
        }

        foreach ($fixtures as $f) {
            [$h, $a] = $getScores($f);
            if ($h === null || $a === null || !$f->home_team_id || !$f->away_team_id) continue;

            $table[$f->home_team_id]['gf'] += $h;
            $table[$f->home_team_id]['gd'] += $h - $a;
            $table[$f->away_team_id]['gf'] += $a;
            $table[$f->away_team_id]['gd'] += $a - $h;

            if ($h > $a) {
                $table[$f->home_team_id]['pts'] += 3;
            } elseif ($h < $a) {
                $table[$f->away_team_id]['pts'] += 3;
            } else {
                $table[$f->home_team_id]['pts'] += 1;
                $table[$f->away_team_id]['pts'] += 1;
            }
        }

        usort($table, fn ($a, $b) =>
            $b['pts'] <=> $a['pts'] ?: $b['gd'] <=> $a['gd'] ?: $b['gf'] <=> $a['gf']
        );

        return array_values($table);
    }
}
