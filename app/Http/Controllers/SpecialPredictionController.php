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

        $isCustom = $request->filled('top_scorer_custom_name');

        $rules = [
            'champion_team_id'  => ['required', 'exists:teams,id'],
            'runner_up_team_id' => ['required', 'exists:teams,id', 'different:champion_team_id'],
        ];

        if ($isCustom) {
            $rules['top_scorer_custom_name']    = ['required', 'string', 'min:2', 'max:100'];
            $rules['top_scorer_custom_team_id'] = ['required', 'exists:teams,id'];
        } else {
            $rules['top_scorer_player_id'] = ['required', 'exists:players,id'];
        }

        $data = $request->validate($rules);

        if ($isCustom) {
            $player = Player::firstOrCreate([
                'name'    => trim($data['top_scorer_custom_name']),
                'team_id' => (int) $data['top_scorer_custom_team_id'],
            ]);
            $topScorerId = $player->id;
        } else {
            $topScorerId = (int) $data['top_scorer_player_id'];
        }

        SpecialPrediction::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'champion_team_id'     => (int) $data['champion_team_id'],
                'runner_up_team_id'    => (int) $data['runner_up_team_id'],
                'top_scorer_player_id' => $topScorerId,
            ]
        );

        return back()->with('status', 'Predicciones especiales guardadas.');
    }
}
