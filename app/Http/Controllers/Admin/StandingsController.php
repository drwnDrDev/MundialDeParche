<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Round;
use App\Services\GroupStageClassifierService;
use Inertia\Inertia;
use Inertia\Response;

class StandingsController extends Controller
{
    public function __construct(private GroupStageClassifierService $classifier) {}

    public function index(): Response
    {
        $round = Round::where('slug', 'grupos')
            ->with(['fixtures.homeTeam', 'fixtures.awayTeam', 'fixtures.group'])
            ->first();

        $groups = [];

        if ($round) {
            $byGroup = $round->fixtures->groupBy(fn ($f) => $f->group?->name ?? '?');

            foreach ($byGroup->sortKeys() as $groupName => $fixtures) {
                $table = $this->classifier->buildGroupTable($fixtures, fn ($f) => [
                    $f->home_score,
                    $f->away_score,
                ]);

                // Enriquecer con datos del equipo
                $teamMap = $fixtures
                    ->flatMap(fn ($f) => array_filter([$f->homeTeam, $f->awayTeam]))
                    ->keyBy('id');

                $rows = array_map(function ($row) use ($teamMap) {
                    $team = $teamMap[$row['team_id']] ?? null;
                    return array_merge($row, [
                        'team_name' => $team?->name,
                        'flag_url'  => $team?->flag_url,
                        'fifa_code' => $team?->fifa_code,
                    ]);
                }, $table);

                $groups[] = [
                    'name' => $groupName,
                    'rows' => $rows,
                ];
            }
        }

        return Inertia::render('Admin/Standings', [
            'groups'    => $groups,
            'isLocked'  => $round?->is_locked ?? false,
        ]);
    }
}
