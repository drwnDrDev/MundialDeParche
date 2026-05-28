<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\SpecialPrediction;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SpecialPredictionController extends Controller
{
    public function show(): Response
    {
        $special = SpecialPrediction::with(['champion', 'runnerUp', 'topScorer.team'])
            ->where('user_id', Auth::id())
            ->first();

        return Inertia::render('Predictions/Special', [
            'special' => $special,
            'teams'   => Team::with('group')->orderBy('name')->get(),
            'players' => Player::with('team')->orderBy('name')->get(),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $special = SpecialPrediction::where('user_id', Auth::id())->first();

        if ($special && $special->is_locked) {
            return back()->with('status', 'Tus predicciones especiales están bloqueadas.');
        }

        $data = $request->validate([
            'champion_team_id'     => ['required', 'exists:teams,id'],
            'runner_up_team_id'    => ['required', 'exists:teams,id', 'different:champion_team_id'],
            'top_scorer_player_id' => ['required', 'exists:players,id'],
        ]);

        SpecialPrediction::updateOrCreate(
            ['user_id' => Auth::id()],
            $data
        );

        return back()->with('status', 'Predicciones especiales guardadas.');
    }
}
