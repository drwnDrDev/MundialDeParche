<?php

namespace App\Http\Controllers\Admin;

use App\Events\TournamentFinalized;
use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function show(): Response
    {
        // Derivar campeón/sub-campeón del fixture M104 (la final)
        $final = Fixture::where('match_number', 104)
            ->with(['homeTeam', 'awayTeam'])
            ->first();

        $derivedChampion  = null;
        $derivedRunnerUp  = null;

        if ($final && $final->winner_team_id) {
            $derivedChampion = $final->winner_team_id === $final->home_team_id
                ? $final->homeTeam
                : $final->awayTeam;
            $derivedRunnerUp = $final->winner_team_id === $final->home_team_id
                ? $final->awayTeam
                : $final->homeTeam;
        }

        $savedResults = Cache::get('tournament_results');

        return Inertia::render('Admin/Tournament', [
            'teams'            => Team::with('group')->orderBy('name')->get(),
            'players'          => Player::with('team')->orderBy('name')->get(),
            'derivedChampion'  => $derivedChampion,
            'derivedRunnerUp'  => $derivedRunnerUp,
            'savedResults'     => $savedResults,
        ]);
    }

    public function finalize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'champion_team_id'     => ['required', 'exists:teams,id'],
            'runner_up_team_id'    => ['required', 'exists:teams,id', 'different:champion_team_id'],
            'top_scorer_player_id' => ['required', 'exists:players,id'],
        ]);

        TournamentFinalized::dispatch(
            $data['champion_team_id'],
            $data['runner_up_team_id'],
            $data['top_scorer_player_id']
        );

        return redirect()->route('admin.dashboard')
            ->with('status', 'Torneo finalizado. Puntos especiales calculados.');
    }
}
