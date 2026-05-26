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
    public function index(): Response
    {
        $user = Auth::user();

        $currentRound = Round::where('is_open', true)->first()
                     ?? Round::orderBy('order')->first();

        $fixtures = Fixture::with(['homeTeam', 'awayTeam', 'group'])
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
                    'live'    => $dayFixtures->contains(fn ($f) => $f->status === 'live'),
                    'matches' => $dayFixtures
                        ->map(fn ($f) => $this->formatFixture($f, $myPredictions->get($f->id)))
                        ->values(),
                ];
            })
            ->values();

        $groups = Group::with('teams')->orderBy('name')->get()
            ->map(fn ($g) => [
                'id'    => $g->name,
                'teams' => $this->buildStandings($g, $fixtures),
            ]);

        return Inertia::render('Matches', [
            'matchDays'    => $matchDays,
            'groups'       => $groups,
            'currentRound' => $currentRound ? [
                'name'         => $currentRound->name,
                'totalMatches' => $currentRound->fixtures()->count(),
            ] : null,
        ]);
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
        $pts    = $pred ? ($pred->pts_exact + $pred->pts_result + $pred->pts_classifier) : null;

        return [
            'id'       => $f->id,
            'time'     => $f->match_date?->format('H:i') ?? '--:--',
            'teamA'    => $f->homeTeam?->fifa_code ?? $f->home_placeholder ?? 'TBD',
            'teamB'    => $f->awayTeam?->fifa_code ?? $f->away_placeholder ?? 'TBD',
            'flagUrlA' => $f->homeTeam?->flag_url,
            'flagUrlB' => $f->awayTeam?->flag_url,
            'scoreA'   => $f->home_score,
            'scoreB'   => $f->away_score,
            'status'   => $status,
            'minute'   => null,
            'group'    => $f->group?->name ?? '—',
            'venue'    => $f->venue ?? '—',
            'myPick'   => $pred ? "{$pred->predicted_home}-{$pred->predicted_away}" : null,
            'myPts'    => (in_array($status, ['ft', 'finished']) && $pts > 0) ? $pts : null,
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
