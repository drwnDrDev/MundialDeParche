<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Prediction;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MatchesController extends Controller
{
    private const FIFA_ROUNDS = [
        'grupos'  => ['label' => 'GRUPOS',  'max' => 72],
        'r32'     => ['label' => 'R32',     'max' => 88],
        'octavos' => ['label' => 'OCTAVOS', 'max' => 96],
        'cuartos' => ['label' => 'CUARTOS', 'max' => 100],
        'semis'   => ['label' => 'SEMIS',   'max' => 102],
        'final'   => ['label' => 'FINAL',   'max' => PHP_INT_MAX],
    ];

    public function index(): Response
    {
        $user = Auth::user();

        $fixtures = Fixture::with(['homeTeam', 'awayTeam', 'group', 'winnerTeam'])
            ->orderBy('match_date')
            ->orderBy('match_number')
            ->get();

        $myPredictions = Prediction::where('user_id', $user->id)
            ->whereIn('match_id', $fixtures->pluck('id'))
            ->get()
            ->keyBy('match_id');

        $matchDays = $fixtures
            ->groupBy(fn ($f) => $f->match_date?->format('Y-m-d') ?? 'sin-fecha')
            ->map(function ($dayFixtures, $dateKey) use ($myPredictions) {
                $date = $dayFixtures->first()->match_date;
                return [
                    'date'    => $date ? $this->formatDate($date) : 'SIN FECHA',
                    'dateKey' => $dateKey,
                    'live'    => $dayFixtures->contains(fn ($f) => $f->status === 'in_progress'),
                    'matches' => $dayFixtures
                        ->map(fn ($f) => $this->formatFixture($f, $myPredictions->get($f->id)))
                        ->values(),
                ];
            })
            ->values();

        // Build FIFA rounds with match counts (only those with fixtures)
        $fifaRounds = collect(self::FIFA_ROUNDS)
            ->map(fn ($def, $slug) => [
                'slug'       => $slug,
                'label'      => $def['label'],
                'matchCount' => $fixtures->filter(fn ($f) => $this->fifaRoundSlug($f->match_number) === $slug)->count(),
            ])
            ->filter(fn ($r) => $r['matchCount'] > 0)
            ->values();

        // Default: earliest non-finished fixture's FIFA round; fallback to last round
        $firstPending = $fixtures
            ->filter(fn ($f) => $f->status !== 'finished')
            ->sortBy('match_number')
            ->first();

        $defaultFifaRound = $firstPending
            ? $this->fifaRoundSlug($firstPending->match_number)
            : $this->fifaRoundSlug($fixtures->max('match_number') ?? 1);

        $groups = Group::with('teams')->orderBy('name')->get()
            ->map(fn ($g) => [
                'id'    => $g->name,
                'teams' => $this->buildStandings($g, $fixtures),
            ]);

        return Inertia::render('Matches', [
            'matchDays'        => $matchDays,
            'groups'           => $groups,
            'fifaRounds'       => $fifaRounds,
            'defaultFifaRound' => $defaultFifaRound,
        ]);
    }

    private function fifaRoundSlug(int $n): string
    {
        foreach (self::FIFA_ROUNDS as $slug => $def) {
            if ($n <= $def['max']) return $slug;
        }
        return 'final';
    }

    private function formatDate(\Carbon\Carbon $date): string
    {
        $days   = ['Mon' => 'LUN', 'Tue' => 'MAR', 'Wed' => 'MIÉ', 'Thu' => 'JUE',
                   'Fri' => 'VIE', 'Sat' => 'SÁB', 'Sun' => 'DOM'];
        $months = ['Jan' => 'ENE', 'Feb' => 'FEB', 'Mar' => 'MAR', 'Apr' => 'ABR',
                   'May' => 'MAY', 'Jun' => 'JUN', 'Jul' => 'JUL', 'Aug' => 'AGO',
                   'Sep' => 'SEP', 'Oct' => 'OCT', 'Nov' => 'NOV', 'Dec' => 'DIC'];

        return $days[$date->format('D')] . ' ' . $date->format('d') . ' ' . $months[$date->format('M')];
    }

    private function formatFixture(Fixture $f, ?Prediction $pred): array
    {
        $status = $f->status ?? ($f->home_score !== null ? 'finished' : 'upcoming');
        $pts    = $pred ? ($pred->pts_exact + $pred->pts_result) : null;

        return [
            'id'          => $f->id,
            'time'        => $f->match_date?->format('H:i') ?? '--:--',
            'teamA'       => $f->homeTeam?->fifa_code ?? $f->home_placeholder ?? 'TBD',
            'teamB'       => $f->awayTeam?->fifa_code ?? $f->away_placeholder ?? 'TBD',
            'flagUrlA'    => $f->homeTeam?->flag_url,
            'flagUrlB'    => $f->awayTeam?->flag_url,
            'scoreA'      => $f->home_score,
            'scoreB'      => $f->away_score,
            'status'      => $status,
            'minute'      => null,
            'group'       => $f->group?->name ?? '—',
            'venue'       => $f->venue ?? '—',
            'myPick'      => $pred ? "{$pred->predicted_home}-{$pred->predicted_away}" : null,
            'myPts'       => (in_array($status, ['ft', 'finished']) && $pts > 0) ? $pts : null,
            'matchNumber'    => $f->match_number,
            'fifaRound'      => $this->fifaRoundSlug($f->match_number),
            'winner'         => $f->winnerTeam?->fifa_code,
            'winnerFlagUrl'  => $f->winnerTeam?->flag_url,
        ];
    }

    private function buildStandings(Group $group, \Illuminate\Support\Collection $allFixtures): array
    {
        $teams    = $group->teams;
        $fixtures = $allFixtures->filter(fn ($f) =>
            $f->group_id === $group->id
            && $f->home_score !== null
            && $f->away_score !== null
        );

        return $teams->map(function (Team $team) use ($fixtures) {
            $home = $fixtures->filter(fn ($f) => $f->home_team_id === $team->id);
            $away = $fixtures->filter(fn ($f) => $f->away_team_id === $team->id);

            $g  = $home->filter(fn ($f) => $f->home_score > $f->away_score)->count()
                + $away->filter(fn ($f) => $f->away_score > $f->home_score)->count();
            $e  = $home->filter(fn ($f) => $f->home_score === $f->away_score)->count()
                + $away->filter(fn ($f) => $f->home_score === $f->away_score)->count();
            $pj = $home->count() + $away->count();
            $p  = $pj - $g - $e;
            $gf = $home->sum('home_score') + $away->sum('away_score');
            $gc = $home->sum('away_score') + $away->sum('home_score');

            return [
                'name'    => strtoupper($team->name),
                'flagUrl' => $team->flag_url,
                'pj' => $pj, 'g' => $g, 'e' => $e, 'p' => $p,
                'gf' => $gf, 'gc' => $gc,
                'pts'  => $g * 3 + $e,
                'live' => false,
            ];
        })
        ->sortByDesc(fn ($t) => $t['pts'] * 1000 + ($t['gf'] - $t['gc']) * 100 + $t['gf'])
        ->values()
        ->toArray();
    }
}
