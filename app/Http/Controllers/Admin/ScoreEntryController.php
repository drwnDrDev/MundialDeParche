<?php

namespace App\Http\Controllers\Admin;

use App\Events\MatchScoreUpdated;
use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Round;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ScoreEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $rounds = Round::orderBy('order')
            ->where(fn ($q) => $q->where('is_open', true)->orWhere('is_locked', true))
            ->get();

        $selectedRoundId = (int) ($request->query('round_id') ?? $rounds->first()?->id ?? 0);

        $fixtures = Fixture::with(['homeTeam', 'awayTeam'])
            ->where('round_id', $selectedRoundId)
            ->orderBy('match_number')
            ->get();

        $activeRound = $rounds->firstWhere('id', $selectedRoundId);

        return Inertia::render('Admin/ScoreEntry', [
            'rounds'          => $rounds,
            'fixtures'        => $fixtures,
            'activeRound'     => $activeRound,
            'selectedRoundId' => $selectedRoundId,
        ]);
    }

    public function update(Request $request, Fixture $fixture): RedirectResponse
    {
        $data = $request->validate([
            'home_score'     => ['required', 'integer', 'min:0', 'max:30'],
            'away_score'     => ['required', 'integer', 'min:0', 'max:30'],
            'winner_team_id' => [
                'nullable',
                Rule::in(array_filter([$fixture->home_team_id, $fixture->away_team_id])),
            ],
            'status'         => ['required', 'in:scheduled,in_progress,finished'],
        ]);

        // Auto-set winner for non-draws
        if (! isset($data['winner_team_id']) || $data['winner_team_id'] === null) {
            if ($data['home_score'] > $data['away_score']) {
                $data['winner_team_id'] = $fixture->home_team_id;
            } elseif ($data['away_score'] > $data['home_score']) {
                $data['winner_team_id'] = $fixture->away_team_id;
            }
        }

        $fixture->update($data);
        MatchScoreUpdated::dispatch($fixture->fresh());

        return redirect()->route('admin.score-entry', ['round_id' => $fixture->round_id])
            ->with('status', "Partido #{$fixture->match_number} actualizado.");
    }
}
