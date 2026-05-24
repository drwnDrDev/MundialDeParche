<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PredictionController extends Controller
{
    public function index(): Response
    {
        $rounds = Round::orderBy('order')->get();

        $submissions = PredictionSubmission::where('user_id', Auth::id())
            ->whereIn('round_id', $rounds->pluck('id'))
            ->get()
            ->keyBy('round_id');

        return Inertia::render('Predictions/Index', [
            'rounds'      => $rounds,
            'submissions' => $submissions,
        ]);
    }

    public function show(Round $round): Response|RedirectResponse
    {
        if ($round->is_open) {
            $hasUnassigned = $round->fixtures()
                ->where(function ($q) {
                    $q->whereNull('home_team_id')->orWhereNull('away_team_id');
                })
                ->exists();

            if ($hasUnassigned) {
                return redirect()->route('predictions.index')
                    ->with('status', 'Esta ronda aún tiene partidos sin equipos asignados.');
            }
        }

        $fixtures = $round->fixtures()
            ->with(['homeTeam', 'awayTeam', 'group'])
            ->orderBy('match_number')
            ->get();

        $predictions = Prediction::where('user_id', Auth::id())
            ->whereIn('match_id', $fixtures->pluck('id'))
            ->get()
            ->keyBy('match_id');

        $submission = PredictionSubmission::where('user_id', Auth::id())
            ->where('round_id', $round->id)
            ->first();

        return Inertia::render('Predictions/Round', [
            'round'       => $round,
            'fixtures'    => $fixtures,
            'predictions' => $predictions,
            'submission'  => $submission,
        ]);
    }

    public function save(Request $request, Round $round): RedirectResponse
    {
        if (! $round->is_open) {
            return back()->with('status', 'Esta ronda no está abierta para predicciones.');
        }

        $submission = PredictionSubmission::where('user_id', Auth::id())
            ->where('round_id', $round->id)
            ->first();

        if ($submission && $submission->status === 'locked') {
            return back()->with('status', 'Tus predicciones para esta ronda están bloqueadas.');
        }

        $data = $request->validate([
            'predictions'                       => ['required', 'array'],
            'predictions.*.predicted_home'      => ['required', 'integer', 'min:0', 'max:20'],
            'predictions.*.predicted_away'      => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $fixtureIds = $round->fixtures()->pluck('id');

        foreach ($data['predictions'] as $matchId => $scores) {
            if (! $fixtureIds->contains((int) $matchId)) {
                continue;
            }
            Prediction::updateOrCreate(
                ['user_id' => Auth::id(), 'match_id' => (int) $matchId],
                ['predicted_home' => $scores['predicted_home'], 'predicted_away' => $scores['predicted_away']]
            );
        }

        PredictionSubmission::updateOrCreate(
            ['user_id' => Auth::id(), 'round_id' => $round->id],
            ['status' => 'draft']
        );

        return back()->with('status', 'Borrador guardado.');
    }

    public function submit(Request $request, Round $round): RedirectResponse
    {
        if (! $round->is_open) {
            return back()->with('status', 'Esta ronda no está abierta.');
        }

        $submission = PredictionSubmission::where('user_id', Auth::id())
            ->where('round_id', $round->id)
            ->first();

        if ($submission && $submission->status === 'locked') {
            return back()->with('status', 'Tus predicciones están bloqueadas.');
        }

        $data = $request->validate([
            'predictions'                       => ['required', 'array'],
            'predictions.*.predicted_home'      => ['required', 'integer', 'min:0', 'max:20'],
            'predictions.*.predicted_away'      => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $fixtures   = $round->fixtures()->get();
        $fixtureIds = $fixtures->pluck('id');
        $isKnockout = $round->slug !== 'grupos';

        // All fixtures must be covered
        $coveredIds = collect($data['predictions'])->keys()->map(fn ($k) => (int) $k);
        if ($fixtureIds->diff($coveredIds)->isNotEmpty()) {
            return back()->withErrors(['predictions' => 'Debes completar todos los partidos de la ronda.']);
        }

        // Knockout rounds cannot have ties
        if ($isKnockout) {
            foreach ($data['predictions'] as $scores) {
                if ((int) $scores['predicted_home'] === (int) $scores['predicted_away']) {
                    return back()->withErrors(['predictions' => 'En rondas de eliminación debe haber un ganador (no empates).']);
                }
            }
        }

        foreach ($data['predictions'] as $matchId => $scores) {
            if (! $fixtureIds->contains((int) $matchId)) {
                continue;
            }
            Prediction::updateOrCreate(
                ['user_id' => Auth::id(), 'match_id' => (int) $matchId],
                ['predicted_home' => $scores['predicted_home'], 'predicted_away' => $scores['predicted_away']]
            );
        }

        PredictionSubmission::updateOrCreate(
            ['user_id' => Auth::id(), 'round_id' => $round->id],
            ['status' => 'submitted', 'submitted_at' => now()]
        );

        return redirect()->route('predictions.index')
            ->with('status', "¡Predicciones de {$round->name} confirmadas!");
    }
}
