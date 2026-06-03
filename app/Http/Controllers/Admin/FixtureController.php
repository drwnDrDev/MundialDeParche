<?php

namespace App\Http\Controllers\Admin;

use App\Events\LiveScoreUpdated;
use App\Events\MatchScoreUpdated;
use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FixtureController extends Controller
{
    public function index(Request $request): Response
    {
        $roundId  = $request->query('round_id');
        $fixtures = Fixture::with(['round', 'group', 'homeTeam', 'awayTeam', 'winnerTeam'])
            ->when($roundId, fn ($q) => $q->where('round_id', $roundId))
            ->orderBy('match_date')
            ->get();

        return Inertia::render('Admin/Fixtures/Index', [
            'fixtures'        => $fixtures,
            'rounds'          => Round::orderBy('order')->get(),
            'selectedRoundId' => $roundId ? (int) $roundId : null,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Fixtures/Create', [
            'rounds' => Round::orderBy('order')->get(),
            'groups' => Group::orderBy('name')->get(),
            'teams'  => Team::with('group')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'round_id'         => ['required', 'exists:rounds,id'],
            'group_id'         => ['nullable', 'exists:groups,id'],
            'match_number'     => ['required', 'integer', 'min:1', 'max:104', 'unique:matches,match_number'],
            'match_date'       => ['required', 'date'],
            'home_team_id'     => ['nullable', 'exists:teams,id'],
            'away_team_id'     => ['nullable', 'exists:teams,id'],
            'home_placeholder' => ['nullable', 'string', 'max:100'],
            'away_placeholder' => ['nullable', 'string', 'max:100'],
        ]);

        Fixture::create($data);

        return redirect()->route('admin.fixtures.index', ['round_id' => $data['round_id']])
            ->with('status', 'Partido creado.');
    }

    public function edit(Fixture $fixture): Response
    {
        return Inertia::render('Admin/Fixtures/Edit', [
            'fixture' => $fixture->load(['round', 'group', 'homeTeam', 'awayTeam', 'winnerTeam']),
            'rounds'  => Round::orderBy('order')->get(),
            'groups'  => Group::orderBy('name')->get(),
            'teams'   => Team::with('group')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Fixture $fixture): RedirectResponse
    {
        $data = $request->validate([
            'round_id'           => ['required', 'exists:rounds,id'],
            'group_id'           => ['nullable', 'exists:groups,id'],
            'match_number'       => ['required', 'integer', 'min:1', 'max:104', 'unique:matches,match_number,' . $fixture->id],
            'match_date'         => ['required', 'date'],
            'home_team_id'       => ['nullable', 'exists:teams,id'],
            'away_team_id'       => ['nullable', 'exists:teams,id'],
            'home_placeholder'   => ['nullable', 'string', 'max:100'],
            'away_placeholder'   => ['nullable', 'string', 'max:100'],
            'home_score'         => ['nullable', 'integer', 'min:0'],
            'away_score'         => ['nullable', 'integer', 'min:0'],
            'winner_team_id'     => ['nullable', Rule::in(array_filter([$request->home_team_id, $request->away_team_id]))],
            'went_to_extra_time' => ['boolean'],
            'status'             => ['required', 'in:scheduled,in_progress,finished'],
        ]);

        $fixture->update($data);
        $fresh = $fixture->fresh();

        if ($fresh->home_score !== null && $fresh->away_score !== null) {
            MatchScoreUpdated::dispatch($fresh);

            if (in_array($fresh->status, ['in_progress', 'finished'])) {
                LiveScoreUpdated::dispatch(
                    $fresh->id,
                    $fresh->home_score,
                    $fresh->away_score,
                    $fresh->isLive(),
                    $fresh->status,
                );
            }
        }

        return redirect()->route('admin.fixtures.index', ['round_id' => $data['round_id']])
            ->with('status', "Partido #{$fixture->match_number} actualizado.");
    }

    public function destroy(Fixture $fixture): RedirectResponse
    {
        $roundId = $fixture->round_id;
        $fixture->delete();

        return redirect()->route('admin.fixtures.index', ['round_id' => $roundId])
            ->with('status', 'Partido eliminado.');
    }
}
