<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Services\GroupStageClassifierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PredictionController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if ($guard = $this->adminGuard()) return $guard;

        $userId  = Auth::id();
        $rounds  = Round::orderBy('order')->withCount('fixtures')->get();

        $submissions = PredictionSubmission::where('user_id', $userId)
            ->whereIn('round_id', $rounds->pluck('id'))
            ->get()
            ->keyBy('round_id');

        return Inertia::render('Predictions/Index', [
            'rounds'      => $rounds,
            'submissions' => $submissions,
            'phasePts'    => $this->buildPhasePts($rounds, $userId, $submissions),
        ]);
    }

    public function show(Round $round): Response|RedirectResponse
    {
        if ($guard = $this->adminGuard()) return $guard;

        if (!$round->is_open) {
            return Inertia::render('Predictions/Locked', [
                'roundName'  => $round->name,
                'roundOrder' => $round->order,
                'isLocked'   => $round->is_locked,
                'opensAt'    => null,
            ]);
        }

        // Block the phase only if EVERY fixture is TBD (admin hasn't set up teams yet).
        // Phases like F3 legitimately have some TBD fixtures (cuartos depend on octavos results).
        $hasAnyRealFixture = $round->fixtures()
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->exists();

        if (! $hasAnyRealFixture) {
            return redirect()->route('predictions.index')
                ->with('status', 'Esta fase aún no tiene partidos asignados. Vuelve más tarde.');
        }

        $fixtures = $round->fixtures()
            ->with(['homeTeam', 'awayTeam', 'group'])
            ->orderBy('match_number')
            ->get();

        // Build reverse bracket map: for each fixture, which match feeds its home/away slot?
        $fixtureIds  = $fixtures->pluck('id');
        $feeders     = Fixture::whereIn('winner_feeds_match_id', $fixtureIds)
            ->select(['id', 'match_number', 'winner_feeds_match_id', 'winner_feeds_slot'])
            ->get();

        // bracketMap[target_fixture_id][slot] = source_match_number
        $bracketMap = [];
        foreach ($feeders as $feeder) {
            $bracketMap[$feeder->winner_feeds_match_id][$feeder->winner_feeds_slot] = $feeder->match_number;
        }

        // Annotate each fixture with its bracket feed info
        $fixturesData = $fixtures->map(function ($f) use ($bracketMap) {
            return array_merge($f->toArray(), [
                'home_fed_by_match_number' => $bracketMap[$f->id]['home'] ?? null,
                'away_fed_by_match_number' => $bracketMap[$f->id]['away'] ?? null,
            ]);
        });

        $predictions = Prediction::where('user_id', Auth::id())
            ->whereIn('match_id', $fixtureIds)
            ->get()
            ->keyBy('match_id');

        $submission = PredictionSubmission::where('user_id', Auth::id())
            ->where('round_id', $round->id)
            ->first();

        return Inertia::render('Predictions/Round', [
            'round'       => $round,
            'fixtures'    => $fixturesData,
            'predictions' => $predictions,
            'submission'  => $submission,
        ]);
    }

    public function save(Request $request, Round $round): RedirectResponse
    {
        if ($guard = $this->adminGuard()) return $guard;

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
            'predictions'                          => ['required', 'array'],
            'predictions.*.predicted_home'         => ['required', 'integer', 'min:0', 'max:20'],
            'predictions.*.predicted_away'         => ['required', 'integer', 'min:0', 'max:20'],
            'predicted_classifiers'                => ['nullable', 'array'],
            'predicted_classifiers.*.team_id'      => ['required', 'integer'],
            'predicted_classifiers.*.group'        => ['required', 'string'],
            'predicted_classifiers.*.position'     => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        if ($round->slug !== 'grupos') {
            foreach ($data['predictions'] as $scores) {
                if ((int) $scores['predicted_home'] === (int) $scores['predicted_away']) {
                    return back()->withErrors(['predictions' => 'En rondas de eliminación debe haber un ganador (no empates).']);
                }
            }
        }

        $fixtureIds = $round->fixtures()->pluck('id');

        return DB::transaction(function () use ($data, $fixtureIds, $round, $submission) {
            foreach ($data['predictions'] as $matchId => $scores) {
                if (! $fixtureIds->contains((int) $matchId)) continue;
                Prediction::updateOrCreate(
                    ['user_id' => Auth::id(), 'match_id' => (int) $matchId],
                    ['predicted_home' => $scores['predicted_home'], 'predicted_away' => $scores['predicted_away']]
                );
            }

            $isGroupStage   = $round->slug === 'grupos';
            $hasClassifiers = ! empty($data['predicted_classifiers'] ?? null);
            $coveredIds     = collect($data['predictions'])->keys()
                ->map(fn ($k) => (int) $k)
                ->filter(fn ($id) => $fixtureIds->contains($id));
            $allCovered     = $fixtureIds->diff($coveredIds)->isEmpty();

            if ($isGroupStage && $hasClassifiers && $allCovered) {
                PredictionSubmission::updateOrCreate(
                    ['user_id' => Auth::id(), 'round_id' => $round->id],
                    [
                        'status'                => 'submitted',
                        'submitted_at'          => now(),
                        'predicted_classifiers' => $data['predicted_classifiers'],
                    ]
                );
                return back()->with('status', '¡Fase de grupos confirmada con tus 32 clasificados!');
            }

            if (! $submission || $submission->status === 'draft') {
                PredictionSubmission::updateOrCreate(
                    ['user_id' => Auth::id(), 'round_id' => $round->id],
                    ['status' => 'draft']
                );
            }
            return back()->with('status', 'Borrador guardado.');
        });
    }

    /**
     * @deprecated Route removed — save() now auto-promotes to submitted when all fixtures covered.
     * Kept for reference only. Do not re-register this route.
     */
    public function submit(Request $request, Round $round): RedirectResponse
    {
        if ($guard = $this->adminGuard()) return $guard;

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

        return DB::transaction(function () use ($data, $fixtureIds, $round) {
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
        });
    }

    public function receipt(Round $round): Response|RedirectResponse
    {
        if ($guard = $this->adminGuard()) return $guard;

        $userId = Auth::id();

        $submission = PredictionSubmission::where('user_id', $userId)
            ->where('round_id', $round->id)
            ->first();

        if (! $submission) {
            return redirect()->route('predictions.index');
        }

        $fixtures = $round->fixtures()
            ->with(['homeTeam', 'awayTeam', 'group'])
            ->orderBy('match_date')
            ->get();

        $predictions = Prediction::where('user_id', $userId)
            ->whereIn('match_id', $fixtures->pluck('id'))
            ->get()
            ->keyBy('match_id');

        // Enriquecer classifiers con nombre y bandera del equipo
        $classifiers = null;
        if ($round->slug === 'grupos' && ! empty($submission->predicted_classifiers)) {
            $teamIds = collect($submission->predicted_classifiers)->pluck('team_id');
            $teams   = \App\Models\Team::whereIn('id', $teamIds)->get()->keyBy('id');

            $classifiers = collect($submission->predicted_classifiers)->map(function ($item) use ($teams) {
                $team = $teams->get($item['team_id']);
                return array_merge($item, [
                    'team_name' => $team?->name,
                    'flag_url'  => $team?->flag_url,
                ]);
            })->values()->all();
        }

        // Cuando la ronda está finalizada, calcular los clasificados reales para comparación
        $realClassifierIds = null;
        // Nota: si la ronda fue bloqueada antes de que todos los fixtures tuvieran marcador,
        // getClassifierIds puede devolver menos de 32 equipos. En ese caso, el frontend
        // simplemente mostrará menos acertados — sin excepción.
        if ($round->is_locked && $round->slug === 'grupos') {
            $service           = app(GroupStageClassifierService::class);
            $realClassifierIds = $service->getClassifierIds(
                $fixtures,
                fn ($f) => [$f->home_score, $f->away_score]
            );
        }

        $props = [
            'round'       => $round,
            'fixtures'    => $fixtures,
            'predictions' => $predictions,
            'submission'  => $submission,
            'isFinalized' => $round->is_locked,
            'classifiers' => $classifiers,
        ];

        if ($realClassifierIds !== null) {
            $props['realClassifierIds'] = $realClassifierIds;
        }

        return Inertia::render('Predictions/Receipt', $props);
    }

    private function adminGuard(): ?\Illuminate\Http\RedirectResponse
    {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return null;
    }

    private function buildPhasePts(Collection $rounds, int $userId, \Illuminate\Support\Collection $submissions): array
    {
        $phasePts = [];

        foreach ($rounds as $round) {
            $fixtureIds    = Fixture::where('round_id', $round->id)->pluck('id');
            $ptsExact      = 0;
            $ptsResult     = 0;
            $predCount     = 0;

            if ($fixtureIds->isNotEmpty()) {
                $agg = Prediction::where('user_id', $userId)
                    ->whereIn('match_id', $fixtureIds)
                    ->selectRaw('COALESCE(SUM(pts_exact),0) as pts_exact, COALESCE(SUM(pts_result),0) as pts_result, COUNT(*) as prediction_count')
                    ->first();

                $ptsExact  = (int) ($agg->pts_exact ?? 0);
                $ptsResult = (int) ($agg->pts_result ?? 0);
                $predCount = (int) ($agg->prediction_count ?? 0);
            }

            $classifierPts = (int) ($submissions[$round->id]?->pts_classifier ?? 0);

            $phasePts[$round->id] = [
                'pts_exact'        => $ptsExact,
                'pts_result'       => $ptsResult,
                'pts_classifier'   => $classifierPts,
                'total'            => $ptsExact + $ptsResult + $classifierPts,
                'prediction_count' => $predCount,
            ];
        }

        return $phasePts;
    }
}
