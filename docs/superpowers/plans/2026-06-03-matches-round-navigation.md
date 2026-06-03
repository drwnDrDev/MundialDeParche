# Matches — Round Navigation + Knockout UI — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace per-day date chips with 6 FIFA round chips in the Matches view, auto-position to the current round, and improve knockout MatchCards with match number footer and ET/PEN winner badge.

**Architecture:** FIFA rounds are derived from `match_number` ranges in `MatchesController` (no schema change). `formatFixture` gains `fifaRound`, `matchNumber`, `wentToET`, `winner`. The frontend filter key changes from `dateKey` to `fifaRound`. `MatchCard` gains footer/badge logic.

**Tech Stack:** Laravel 11, React 18, Inertia.js v2, Pest v3, Tailwind CSS (pop-art design system).

**Spec:** `docs/superpowers/specs/2026-06-03-matches-round-navigation.md`

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `app/Http/Controllers/MatchesController.php` | Modify | Add `fifaRoundSlug()`, update `formatFixture`, add `fifaRounds`+`defaultFifaRound` props |
| `tests/Feature/MatchesTest.php` | Modify | Add assertions for new props + fifaRound field shape |
| `resources/js/Components/composed/MatchCard.jsx` | Modify | New props, footer logic, ET/PEN badge |
| `resources/js/Pages/Matches.jsx` | Modify | Replace DateChip→RoundChip, selectedDate→selectedFifaRound, auto-scroll, LiveScoreUpdated fix |

---

## Task 1: Backend — MatchesController + tests

**Files:**
- Modify: `app/Http/Controllers/MatchesController.php`
- Modify: `tests/Feature/MatchesTest.php`

- [ ] **Step 1.1: Write the failing tests**

Open `tests/Feature/MatchesTest.php` and replace its entire contents:

```php
<?php

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('authenticated user can view matches page', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->component('Matches')
            ->has('matchDays')
            ->has('groups')
            ->has('fifaRounds')
            ->has('defaultFifaRound')
        );
});

it('guest is redirected from matches page', function () {
    $this->get('/matches')->assertRedirect('/login');
});

it('formatFixture includes fifaRound matchNumber wentToET winner', function () {
    $user  = User::factory()->create(['is_active' => true]);
    $round = Round::factory()->create(['slug' => 'grupos', 'order' => 1]);
    $group = Group::factory()->create(['name' => 'A']);
    $teamA = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'MEX']);
    $teamB = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'USA']);

    Fixture::factory()->create([
        'round_id'           => $round->id,
        'group_id'           => $group->id,
        'match_number'       => 1,
        'home_team_id'       => $teamA->id,
        'away_team_id'       => $teamB->id,
        'home_score'         => 2,
        'away_score'         => 1,
        'winner_team_id'     => $teamA->id,
        'went_to_extra_time' => false,
        'status'             => 'finished',
    ]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->component('Matches')
            ->where('matchDays.0.matches.0.fifaRound', 'grupos')
            ->where('matchDays.0.matches.0.matchNumber', 1)
            ->where('matchDays.0.matches.0.wentToET', false)
            ->where('matchDays.0.matches.0.winner', 'MEX')
        );
});

it('fifaRoundSlug derives correct slug from match_number ranges', function () {
    $user  = User::factory()->create(['is_active' => true]);
    $round = Round::factory()->create(['slug' => 'r32', 'order' => 2]);
    $teamA = Team::factory()->create(['group_id' => null, 'fifa_code' => 'ARG']);
    $teamB = Team::factory()->create(['group_id' => null, 'fifa_code' => 'BRA']);

    Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => null,
        'match_number' => 73,
        'home_team_id' => $teamA->id,
        'away_team_id' => $teamB->id,
        'status'       => 'scheduled',
    ]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->where('matchDays.0.matches.0.fifaRound', 'r32')
        );
});

it('fifaRounds prop contains only rounds with fixtures', function () {
    $user  = User::factory()->create(['is_active' => true]);
    $round = Round::factory()->create(['slug' => 'grupos', 'order' => 1]);
    $group = Group::factory()->create(['name' => 'A']);
    $teamA = Team::factory()->create(['group_id' => $group->id]);
    $teamB = Team::factory()->create(['group_id' => $group->id]);

    Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'match_number' => 1,
        'home_team_id' => $teamA->id,
        'away_team_id' => $teamB->id,
        'status'       => 'scheduled',
    ]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->has('fifaRounds', 1)
            ->where('fifaRounds.0.slug', 'grupos')
            ->where('fifaRounds.0.label', 'GRUPOS')
            ->where('fifaRounds.0.matchCount', 1)
        );
});

it('defaultFifaRound points to earliest non-finished fixture', function () {
    $user   = User::factory()->create(['is_active' => true]);
    $round1 = Round::factory()->create(['slug' => 'grupos', 'order' => 1]);
    $round2 = Round::factory()->create(['slug' => 'r32', 'order' => 2]);
    $group  = Group::factory()->create(['name' => 'A']);
    $teamA  = Team::factory()->create(['group_id' => $group->id]);
    $teamB  = Team::factory()->create(['group_id' => $group->id]);

    // Finished group match
    Fixture::factory()->create([
        'round_id' => $round1->id, 'group_id' => $group->id,
        'match_number' => 1, 'home_team_id' => $teamA->id,
        'away_team_id' => $teamB->id, 'status' => 'finished',
        'home_score' => 1, 'away_score' => 0,
    ]);

    // Scheduled R32 match
    Fixture::factory()->create([
        'round_id' => $round2->id, 'group_id' => null,
        'match_number' => 73, 'home_team_id' => $teamA->id,
        'away_team_id' => $teamB->id, 'status' => 'scheduled',
    ]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->where('defaultFifaRound', 'r32')
        );
});
```

- [ ] **Step 1.2: Run tests to confirm they fail**

```bash
./vendor/bin/sail test tests/Feature/MatchesTest.php
```

Expected: failures on `has('fifaRounds')`, `has('defaultFifaRound')`, `fifaRound` field missing.

- [ ] **Step 1.3: Implement the changes in MatchesController**

Replace the full contents of `app/Http/Controllers/MatchesController.php`:

```php
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

        // Build FIFA rounds with match counts
        $fifaRoundDefs = self::FIFA_ROUNDS;
        $fifaRounds = collect($fifaRoundDefs)
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
            'matchDays'       => $matchDays,
            'groups'          => $groups,
            'fifaRounds'      => $fifaRounds,
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
            // New fields
            'matchNumber' => $f->match_number,
            'fifaRound'   => $this->fifaRoundSlug($f->match_number),
            'wentToET'    => (bool) $f->went_to_extra_time,
            'winner'      => $f->winnerTeam?->fifa_code,
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
```

- [ ] **Step 1.4: Run tests to confirm they pass**

```bash
./vendor/bin/sail test tests/Feature/MatchesTest.php
```

Expected: all 6 tests pass (DEPR warnings are normal — PDO SSL env issue).

- [ ] **Step 1.5: Commit**

```bash
git add app/Http/Controllers/MatchesController.php tests/Feature/MatchesTest.php
git commit -m "feat: MatchesController — fifaRounds + fifaRound field derivado de match_number"
```

---

## Task 2: MatchCard — footer + ET/PEN badge

**Files:**
- Modify: `resources/js/Components/composed/MatchCard.jsx`

No new test file — MatchCard is a pure presentational component; verified visually via the full page in Task 3.

- [ ] **Step 2.1: Replace MatchCard.jsx**

```jsx
export default function MatchCard({
    status,
    time,
    teamA, teamB,
    flagUrlA, flagUrlB,
    scoreA, scoreB,
    minute,
    group, venue,
    myPick, myPts,
    matchNumber, fifaRound, wentToET, winner,
}) {
    const isLive = status === 'in_progress' || status === 'live';
    const isFT   = status === 'finished' || status === 'ft';
    const isUp   = !isLive && !isFT;

    const matchInfo = fifaRound === 'grupos'
        ? `GRUPO ${group} · ${venue}`
        : `M${matchNumber} · ${venue}`;

    return (
        <div className={[
            'border-[2.5px] border-ink shadow-pop p-[10px_12px] relative overflow-hidden',
            isLive ? 'bg-navy text-cream' : 'bg-white text-ink',
        ].join(' ')}>
            {isLive && (
                <div className="halftone halftone-red absolute inset-0 opacity-15 pointer-events-none" />
            )}

            <div className="flex items-center gap-2 relative">
                {/* Status indicator */}
                <div className="w-[52px] text-center flex-shrink-0">
                    {isLive && (
                        <div>
                            <div className="font-display text-[11px] text-pop-red flex items-center gap-1 justify-center">
                                <span className="w-1.5 h-1.5 rounded-full bg-pop-red animate-pulse" />
                                LIVE
                            </div>
                            <div className="font-display text-[13px] text-pop-yel mt-0.5">{minute}</div>
                        </div>
                    )}
                    {isFT && (
                        <div>
                            <div className="font-display text-[13px] text-pop-teal">FT</div>
                            <div className="font-mono text-[9px] opacity-55 mt-0.5">{time}</div>
                        </div>
                    )}
                    {isUp && (
                        <div className="font-display text-[13px]">{time}</div>
                    )}
                </div>

                {/* Teams + score */}
                <div className="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-1.5">
                    <div className="flex items-center gap-1.5 justify-end">
                        <span className="font-display text-[13px]">{teamA}</span>
                        {flagUrlA && <img src={flagUrlA} alt={teamA} className="h-4 w-6 object-cover border border-ink" />}
                    </div>
                    <div className="flex items-center gap-1 px-1">
                        {isUp ? (
                            <span className="font-display text-[14px] opacity-50">VS</span>
                        ) : (
                            <>
                                <span className={`font-display text-[20px] ${isLive ? 'text-pop-yel' : 'text-ink'}`}>{scoreA}</span>
                                <span className="opacity-50 mx-0.5">—</span>
                                <span className={`font-display text-[20px] ${isLive ? 'text-cream' : 'text-ink'}`}>{scoreB}</span>
                            </>
                        )}
                    </div>
                    <div className="flex items-center gap-1.5">
                        {flagUrlB && <img src={flagUrlB} alt={teamB} className="h-4 w-6 object-cover border border-ink" />}
                        <span className="font-display text-[13px]">{teamB}</span>
                    </div>
                </div>
            </div>

            {/* Footer */}
            <div className={[
                'mt-2 pt-2 flex items-center justify-between gap-1.5',
                'font-mono text-[9px] font-bold tracking-[.06em]',
                isLive
                    ? 'border-t border-dashed border-cream/30'
                    : 'border-t border-dashed border-black/20',
            ].join(' ')}>
                <div className="flex flex-col gap-0.5">
                    <span className={isLive ? 'opacity-80' : 'opacity-65'}>{matchInfo}</span>
                    {isFT && wentToET && winner && (
                        <span className="inline-flex items-center gap-1 px-1.5 py-0.5 border-[1.5px] border-ink bg-pop-yel text-ink">
                            {winner} · ET/PEN
                        </span>
                    )}
                </div>
                {myPick ? (
                    <span className={[
                        'inline-flex items-center gap-1 px-1.5 py-0.5 border-[1.5px] border-ink flex-shrink-0',
                        isFT && myPts != null
                            ? 'bg-pop-teal text-white'
                            : 'bg-pop-yel text-ink',
                    ].join(' ')}>
                        TUS GOLES: {myPick}{isFT && myPts != null ? ` · +${myPts} PTS` : ''}
                    </span>
                ) : (
                    <span className={[
                        'px-1.5 py-0.5 border-[1.5px] border-dashed flex-shrink-0',
                        isLive
                            ? 'border-cream/60 text-cream'
                            : 'border-pop-red text-pop-red',
                    ].join(' ')}>
                        ! FALTAN TUS GOLES
                    </span>
                )}
            </div>
        </div>
    );
}
```

- [ ] **Step 2.2: Commit**

```bash
git add resources/js/Components/composed/MatchCard.jsx
git commit -m "feat: MatchCard — footer por ronda FIFA + badge ET/PEN ganador"
```

---

## Task 3: Matches.jsx — round chips + auto-scroll + LiveScoreUpdated fix

**Files:**
- Modify: `resources/js/Pages/Matches.jsx`

- [ ] **Step 3.1: Replace Matches.jsx**

```jsx
import { useState, useEffect, useRef, forwardRef } from 'react';
import { Head } from '@inertiajs/react';
import MobileShell from '@/Components/MobileShell';
import TabBar from '@/Components/composed/TabBar';
import MatchCard from '@/Components/composed/MatchCard';
import GroupStandingCard from '@/Components/composed/GroupStandingCard';

function ViewTab({ label, active, last, onClick }) {
    return (
        <button
            onClick={onClick}
            className={[
                'flex-1 py-2 font-display text-[11px] text-center border-0',
                active ? 'bg-ink text-pop-yel' : 'bg-white text-ink',
                !last ? 'border-r-[2.5px] border-ink' : '',
            ].join(' ')}
        >
            {label}
        </button>
    );
}

const RoundChip = forwardRef(function RoundChip({ label, count, active, onClick }, ref) {
    return (
        <button
            ref={ref}
            onClick={onClick}
            className={[
                'flex-shrink-0 px-3 py-1.5 border-[2.5px] border-ink text-center',
                active ? 'bg-pop-red text-white shadow-pop' : 'bg-white text-ink shadow-pop-sm',
            ].join(' ')}
        >
            <div className="font-display text-[13px] leading-none">{label}</div>
            <div className="font-mono text-[9px] font-bold opacity-80 mt-0.5 tracking-[.06em]">{count}P</div>
        </button>
    );
});

const DayBlock = forwardRef(function DayBlock({ day }, ref) {
    return (
        <div ref={ref} className="mb-3">
            <div className="flex items-center gap-2 mb-1.5">
                <span
                    className={`w-3 h-3 border-2 border-ink flex-shrink-0 ${
                        day.live ? 'bg-pop-red' : 'bg-pop-teal'
                    }`}
                />
                <div className="font-display text-[14px]">{day.date}</div>
                <div className="flex-1 h-0.5 bg-ink" />
                <div className="font-mono text-[9px] opacity-65">{day.matches.length} partidos</div>
            </div>
            <div className="flex flex-col gap-2">
                {day.matches.map(m => (
                    <MatchCard key={m.id} {...m} />
                ))}
            </div>
        </div>
    );
});

export default function Matches({ matchDays: initialMatchDays, groups, fifaRounds, defaultFifaRound }) {
    const today = new Date().toISOString().split('T')[0];

    const [matchDays, setMatchDays]               = useState(initialMatchDays);
    const [view, setView]                          = useState('calendar');
    const [selectedFifaRound, setSelectedFifaRound] = useState(defaultFifaRound);

    const activeChipRef = useRef(null);
    const todayRef      = useRef(null);

    // Auto-scroll active chip into view
    useEffect(() => {
        activeChipRef.current?.scrollIntoView({ inline: 'center', behavior: 'instant', block: 'nearest' });
    }, [selectedFifaRound]);

    // Auto-scroll to today's DayBlock
    useEffect(() => {
        todayRef.current?.scrollIntoView({ behavior: 'instant', block: 'start' });
    }, [selectedFifaRound]);

    // Real-time score updates
    useEffect(() => {
        const channel = window.Echo.join('quinela');
        channel.listen('.LiveScoreUpdated', (event) => {
            setMatchDays(prev => prev.map(day => ({
                ...day,
                matches: day.matches.map(m =>
                    m.id === event.match_id
                        ? { ...m, home_score: event.home_score, away_score: event.away_score, status: event.status }
                        : m
                ),
            })));
        });
        return () => { window.Echo.leave('quinela'); };
    }, []);

    const visibleDays = matchDays
        .map(day => ({
            ...day,
            matches: day.matches.filter(m => m.fifaRound === selectedFifaRound),
        }))
        .filter(day => day.matches.length > 0);

    return (
        <>
            <Head title="Partidos" />
            <MobileShell>
                {/* Halftone decoration */}
                <div
                    className="halftone halftone-teal absolute top-[60px] right-0 w-[220px] h-[200px] pointer-events-none"
                    style={{ opacity: .2 }}
                />

                {/* Header */}
                <div className="relative px-[18px] pt-1.5 flex items-start justify-between">
                    <div>
                        <div className="font-mono text-[10px] opacity-70 tracking-[.1em] mt-1.5">WC 2026</div>
                        <div
                            className="font-display text-[32px] leading-none mt-0.5 text-pop-red"
                            style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}
                        >
                            PARTIDOS
                        </div>
                    </div>
                    <div className="mt-2">
                        <div className="font-mono text-[9px] opacity-65 tracking-[.06em]">
                            {matchDays.reduce((s, d) => s + d.matches.length, 0)} partidos
                        </div>
                    </div>
                </div>

                {/* View toggle */}
                <div className="px-[14px] pt-3">
                    <div className="flex border-[2.5px] border-ink shadow-pop">
                        <ViewTab
                            label="CALENDARIO"
                            active={view === 'calendar'}
                            onClick={() => setView('calendar')}
                        />
                        <ViewTab
                            label="POSICIONES"
                            active={view === 'standings'}
                            last
                            onClick={() => setView('standings')}
                        />
                    </div>
                </div>

                {view === 'calendar' ? (
                    <>
                        {/* Round chips */}
                        <div className="pt-3 pl-[14px]">
                            <div className="flex gap-1.5 overflow-x-auto pr-[14px] pb-1">
                                {fifaRounds.map(round => (
                                    <RoundChip
                                        key={round.slug}
                                        label={round.label}
                                        count={round.matchCount}
                                        active={selectedFifaRound === round.slug}
                                        ref={selectedFifaRound === round.slug ? activeChipRef : null}
                                        onClick={() => setSelectedFifaRound(round.slug)}
                                    />
                                ))}
                            </div>
                        </div>

                        {/* Match list */}
                        <div className="px-[14px] pt-2.5 pb-4">
                            {visibleDays.length > 0 ? (
                                visibleDays.map(day => (
                                    <DayBlock
                                        key={day.dateKey}
                                        day={day}
                                        ref={day.dateKey === today ? todayRef : null}
                                    />
                                ))
                            ) : (
                                <div className="text-center font-mono text-[11px] opacity-50 py-8">
                                    No hay partidos para esta ronda
                                </div>
                            )}
                            <div className="pt-2 text-center font-mono text-[10px] opacity-40 tracking-[.08em]">
                                · · · fin · · ·
                            </div>
                        </div>
                    </>
                ) : (
                    <>
                        {/* Group chips */}
                        <div className="pt-3 pl-[14px]">
                            <div className="flex gap-1.5 overflow-x-auto pr-[14px] pb-2">
                                {'ABCDEFGHIJKL'.split('').map(letter => (
                                    <div
                                        key={letter}
                                        className="flex-shrink-0 w-[42px] py-1.5 border-[2.5px] border-ink bg-white shadow-pop-sm text-center font-display text-[16px] leading-none"
                                    >
                                        {letter}
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="px-[14px] pb-4 flex flex-col gap-3">
                            {groups.map(group => (
                                <GroupStandingCard
                                    key={group.id}
                                    group={group.id}
                                    played={`${Math.floor(group.teams.reduce((s, t) => s + t.pj, 0) / 2)} / 6 JUGADOS`}
                                    teams={group.teams}
                                />
                            ))}
                        </div>
                    </>
                )}
            </MobileShell>
            <TabBar active="matches" />
        </>
    );
}
```

- [ ] **Step 3.2: Build**

```bash
./vendor/bin/sail pnpm run build 2>&1 | tail -5
```

Expected: `✓ built in X.XXs` with no errors.

- [ ] **Step 3.3: Run all tests to confirm nothing regressed**

```bash
./vendor/bin/sail test tests/Feature/MatchesTest.php
```

Expected: all 6 tests pass.

- [ ] **Step 3.4: Commit**

```bash
git add resources/js/Pages/Matches.jsx public/build/
git commit -m "feat: Matches — chips de ronda FIFA + auto-scroll + fix LiveScoreUpdated status"
```

---

## Self-Review

**Spec coverage:**
- ✅ `fifaRoundSlug()` helper with correct ranges — Task 1
- ✅ `fifaRounds` prop with slug/label/matchCount — Task 1
- ✅ `defaultFifaRound` points to earliest non-finished fixture — Task 1
- ✅ `formatFixture` gains `fifaRound`, `matchNumber`, `wentToET`, `winner` — Task 1
- ✅ `winnerTeam` eager-loaded — Task 1 (with `['homeTeam', 'awayTeam', 'group', 'winnerTeam']`)
- ✅ `round` relation no longer needed in eager-load — Task 1
- ✅ `RoundChip` replaces `DateChip`, uses `forwardRef` — Task 3
- ✅ `DayBlock` wrapped with `forwardRef` — Task 3
- ✅ `selectedFifaRound` replaces `selectedDate` — Task 3
- ✅ `visibleDays` filtered by `fifaRound` — Task 3
- ✅ Auto-scroll chip — Task 3
- ✅ Auto-scroll today's DayBlock — Task 3
- ✅ LiveScoreUpdated uses `e.status` — Task 3
- ✅ Header removes `currentRound` badge — Task 3
- ✅ `MatchCard` footer: grupo vs knockout — Task 2
- ✅ ET/PEN badge when `isFT && wentToET && winner` — Task 2
- ✅ Unassigned knockout slots: handled by existing `home_placeholder` fallback — Task 1 (no change needed)

**Type consistency:**
- `fifaRound` slug values: `'grupos'`, `'r32'`, `'octavos'`, `'cuartos'`, `'semis'`, `'final'` — consistent across `FIFA_ROUNDS` constant, `fifaRoundSlug()`, `formatFixture`, `MatchCard` footer check, `visibleDays` filter
- `activeChipRef` passed via `ref` prop to `RoundChip` which uses `forwardRef` ✅
- `todayRef` passed via `ref` prop to `DayBlock` which uses `forwardRef` ✅
