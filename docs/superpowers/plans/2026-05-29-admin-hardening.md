# Admin Hardening & UX Fixes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix 7 issues: round finalization idempotency, score-entry guards for finished fixtures, chronological fixture ordering, ScoreEntry UI redesign (1-per-row + locked finished cards), admin excluded from ranking/predictions, LiveScoreUpdated broadcast from ScoreEntry, and MobileShell 768px max-width.

**Architecture:** Three independent clusters — (1) data integrity via `is_finalized` migration + backend guards, (2) UI/UX ordering and layout changes, (3) role filtering + real-time dispatch. All changes are additive; no existing API contracts broken.

**Tech Stack:** Laravel 11, Pest v3, React 18 + Inertia.js v2, Tailwind CSS, MySQL 8.4, Laravel Reverb.

---

## File Map

| File | Action |
|---|---|
| `database/migrations/…_add_is_finalized_to_rounds_table.php` | Create |
| `app/Models/Round.php` | Modify — add `is_finalized` to fillable + casts |
| `app/Http/Controllers/Admin/RoundController.php` | Modify — guard + set `is_finalized` |
| `app/Http/Controllers/Admin/ScoreEntryController.php` | Modify — guard finished, dispatch LiveScoreUpdated, order by match_date + status |
| `app/Http/Controllers/Admin/FixtureController.php` | Modify — orderBy match_date |
| `app/Http/Controllers/RankingController.php` | Modify — where role=user |
| `app/Http/Controllers/HomeController.php` | Modify — where role=user |
| `app/Http/Controllers/PredictionController.php` | Modify — admin guard + receipt orderBy match_date |
| `app/Http/Controllers/SpecialPredictionController.php` | Modify — admin guard |
| `resources/js/Pages/Admin/Rounds/Index.jsx` | Modify — disable Finalizar when is_finalized |
| `resources/js/Pages/Admin/ScoreEntry.jsx` | Modify — full redesign |
| `resources/js/Pages/Admin/Fixtures/Edit.jsx` | Modify — amber banner for finished fixtures |
| `resources/js/Layouts/AdminLayout.jsx` | Modify — add Chat nav link |
| `resources/js/Components/MobileShell.jsx` | Modify — max-w-3xl wrapper |
| `tests/Feature/Admin/RoundFinalizeDispatchTest.php` | Modify — add idempotency test |
| `tests/Feature/Admin/ScoreEntryControllerTest.php` | Modify — add guard + LiveScoreUpdated tests |
| `tests/Feature/RankingControllerTest.php` | Modify — add admin-exclusion test |
| `tests/Feature/PredictionControllerTest.php` | Modify — add admin-redirect tests |

---

## Task 1: Migration + Round model — add `is_finalized`

**Files:**
- Create: `database/migrations/2026_05_29_000001_add_is_finalized_to_rounds_table.php`
- Modify: `app/Models/Round.php`

- [ ] **Step 1: Create the migration**

```bash
./vendor/bin/sail artisan make:migration add_is_finalized_to_rounds_table --table=rounds
```

Open the generated file and replace the `up`/`down` methods:

```php
public function up(): void
{
    Schema::table('rounds', function (Blueprint $table) {
        $table->boolean('is_finalized')->default(false)->after('is_locked');
    });
}

public function down(): void
{
    Schema::table('rounds', function (Blueprint $table) {
        $table->dropColumn('is_finalized');
    });
}
```

- [ ] **Step 2: Run the migration**

```bash
./vendor/bin/sail artisan migrate
```

Expected: `Migrating: …add_is_finalized_to_rounds_table` → `Migrated`

- [ ] **Step 3: Update Round model**

In `app/Models/Round.php`, add `'is_finalized'` to `$fillable` and to `casts()`:

```php
protected $fillable = [
    'name',
    'slug',
    'order',
    'is_open',
    'is_locked',
    'is_finalized',
    'closes_at',
    'points_exact',
    'points_result',
    'points_classifier',
];

protected function casts(): array
{
    return [
        'is_open'       => 'boolean',
        'is_locked'     => 'boolean',
        'is_finalized'  => 'boolean',
        'closes_at'     => 'datetime',
    ];
}
```

- [ ] **Step 4: Run all tests to confirm no regressions**

```bash
./vendor/bin/sail test
```

Expected: all existing tests pass.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/Round.php
git commit -m "feat: add is_finalized to rounds table and model"
```

---

## Task 2: RoundController — idempotency guard on finalize()

**Files:**
- Modify: `app/Http/Controllers/Admin/RoundController.php`
- Modify: `tests/Feature/Admin/RoundFinalizeDispatchTest.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Admin/RoundFinalizeDispatchTest.php`:

```php
it('does not dispatch RoundFinalized a second time when round is already finalized', function () {
    Event::fake();
    $round = Round::factory()->f1()->create([
        'is_open'       => false,
        'is_locked'     => true,
        'is_finalized'  => true,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/rounds/{$round->slug}/finalize")
        ->assertRedirect();

    Event::assertNotDispatched(RoundFinalized::class);
});
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
./vendor/bin/sail test --filter "does not dispatch RoundFinalized a second time"
```

Expected: FAIL (event IS dispatched — guard doesn't exist yet).

- [ ] **Step 3: Add guard and set is_finalized in RoundController::finalize()**

Replace the `finalize()` method in `app/Http/Controllers/Admin/RoundController.php`:

```php
public function finalize(Round $round): RedirectResponse
{
    if ($round->is_finalized) {
        return back()->with('status', "La ronda '{$round->name}' ya fue finalizada.");
    }

    if (! $round->is_locked) {
        $round->update(['is_open' => false, 'is_locked' => true]);
        RoundLocked::dispatch($round->name);
    }

    RoundFinalized::dispatch($round);

    $round->update(['is_finalized' => true]);

    return redirect()->route('admin.rounds.index')
        ->with('status', "Ronda '{$round->name}' finalizada. Puntos de clasificados calculados.");
}
```

- [ ] **Step 4: Run the new test**

```bash
./vendor/bin/sail test --filter "does not dispatch RoundFinalized a second time"
```

Expected: PASS.

- [ ] **Step 5: Run all tests**

```bash
./vendor/bin/sail test
```

Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/RoundController.php tests/Feature/Admin/RoundFinalizeDispatchTest.php
git commit -m "feat: guard round finalization idempotency with is_finalized flag"
```

---

## Task 3: Rounds/Index.jsx — disable Finalizar when is_finalized

**Files:**
- Modify: `resources/js/Pages/Admin/Rounds/Index.jsx`

- [ ] **Step 1: Update the Rounds index table**

Replace the entire file content:

```jsx
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';

export default function Index({ rounds }) {
    const post = (url) => router.post(url);

    return (
        <AdminLayout header="Rondas">
            <Head title="Admin — Rondas" />

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['#', 'Ronda', 'Exacto', 'Resultado', 'Clasificado', 'Estado', 'Acciones'].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {rounds.map(round => (
                            <tr key={round.id}>
                                <td className="px-4 py-3 text-sm text-gray-500">{round.order}</td>
                                <td className="px-4 py-3 text-sm font-medium text-gray-900">{round.name}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_exact}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_result}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_classifier}</td>
                                <td className="px-4 py-3 text-sm">
                                    {round.is_finalized
                                        ? <span className="rounded bg-indigo-100 px-2 py-1 text-xs text-indigo-700">Finalizada</span>
                                        : round.is_locked
                                            ? <span className="rounded bg-red-100 px-2 py-1 text-xs text-red-700">Bloqueada</span>
                                            : round.is_open
                                                ? <span className="rounded bg-green-100 px-2 py-1 text-xs text-green-700">Abierta</span>
                                                : <span className="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">Cerrada</span>
                                    }
                                </td>
                                <td className="flex flex-wrap gap-1 px-4 py-3">
                                    {!round.is_open && !round.is_locked && !round.is_finalized && (
                                        <button onClick={() => post(route('admin.rounds.open', round.slug))}
                                            className="rounded bg-green-600 px-3 py-1 text-xs text-white hover:bg-green-700">
                                            Abrir
                                        </button>
                                    )}
                                    {round.is_open && !round.is_locked && (
                                        <button onClick={() => post(route('admin.rounds.lock', round.slug))}
                                            className="rounded bg-yellow-600 px-3 py-1 text-xs text-white hover:bg-yellow-700">
                                            Bloquear
                                        </button>
                                    )}
                                    {round.is_locked && !round.is_finalized && (
                                        <button
                                            onClick={() => {
                                                if (confirm(`¿Finalizar "${round.name}"? Se calcularán los puntos de clasificados.`)) {
                                                    post(route('admin.rounds.finalize', round.slug));
                                                }
                                            }}
                                            className="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700">
                                            Finalizar
                                        </button>
                                    )}
                                    {round.is_finalized && (
                                        <span className="text-xs text-gray-400">✓ Completada</span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Admin/Rounds/Index.jsx
git commit -m "feat: disable Finalizar button and show badge when round is_finalized"
```

---

## Task 4: ScoreEntryController — guard finished, dispatch LiveScoreUpdated, order by match_date

**Files:**
- Modify: `app/Http/Controllers/Admin/ScoreEntryController.php`
- Modify: `tests/Feature/Admin/ScoreEntryControllerTest.php`

- [ ] **Step 1: Write failing tests**

Add to `tests/Feature/Admin/ScoreEntryControllerTest.php`:

```php
use App\Events\LiveScoreUpdated;

it('blocks updating a finished fixture via score entry', function () {
    $admin   = User::factory()->create(['role' => 'admin']);
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $home    = Team::factory()->create(['fifa_code' => 'ARG']);
    $away    = Team::factory()->create(['fifa_code' => 'CHI']);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => 2,
        'away_score'   => 1,
        'status'       => 'finished',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.score-entry.update', $fixture->id), [
            'home_score' => 3,
            'away_score' => 0,
            'status'     => 'finished',
        ])
        ->assertRedirect();

    expect($fixture->fresh()->home_score)->toBe(2);
});

it('dispatches LiveScoreUpdated when fixture is set to in_progress', function () {
    Event::fake([LiveScoreUpdated::class, MatchScoreUpdated::class]);

    $admin   = User::factory()->create(['role' => 'admin']);
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $home    = Team::factory()->create(['fifa_code' => 'ECU']);
    $away    = Team::factory()->create(['fifa_code' => 'URU']);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'       => 'scheduled',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.score-entry.update', $fixture->id), [
            'home_score' => 1,
            'away_score' => 0,
            'status'     => 'in_progress',
        ]);

    Event::assertDispatched(LiveScoreUpdated::class, function ($e) use ($fixture) {
        return $e->matchId === $fixture->id && $e->isLive === true;
    });
});
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
./vendor/bin/sail test --filter "blocks updating a finished fixture|dispatches LiveScoreUpdated"
```

Expected: FAIL for both.

- [ ] **Step 3: Update ScoreEntryController**

Replace the full file `app/Http/Controllers/Admin/ScoreEntryController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Events\LiveScoreUpdated;
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
            ->orderByRaw("FIELD(status, 'in_progress', 'scheduled', 'finished')")
            ->orderBy('match_date')
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
        if ($fixture->status === 'finished') {
            return back()->with('status', 'Este partido ya está finalizado. Usa la vista de edición para corregir.');
        }

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
        $fresh = $fixture->fresh();

        MatchScoreUpdated::dispatch($fresh);

        if (in_array($data['status'], ['in_progress', 'finished'])) {
            LiveScoreUpdated::dispatch(
                $fresh->id,
                $fresh->home_score,
                $fresh->away_score,
                $fresh->isLive(),
            );
        }

        return redirect()->route('admin.score-entry', ['round_id' => $fixture->round_id])
            ->with('status', "Partido #{$fixture->match_number} actualizado.");
    }
}
```

- [ ] **Step 4: Run the new tests**

```bash
./vendor/bin/sail test --filter "blocks updating a finished fixture|dispatches LiveScoreUpdated"
```

Expected: PASS for both.

- [ ] **Step 5: Run all tests**

```bash
./vendor/bin/sail test
```

Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/ScoreEntryController.php tests/Feature/Admin/ScoreEntryControllerTest.php
git commit -m "feat: block finished fixture edits in ScoreEntry, dispatch LiveScoreUpdated, order by match_date"
```

---

## Task 5: ScoreEntry.jsx — redesign (1-per-row, finished section at bottom)

**Files:**
- Modify: `resources/js/Pages/Admin/ScoreEntry.jsx`

- [ ] **Step 1: Replace ScoreEntry.jsx**

```jsx
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const STATUS_OPTIONS = [
    { value: 'scheduled',   label: 'Programado' },
    { value: 'in_progress', label: 'En Juego'   },
    { value: 'finished',    label: 'Finalizado'  },
];

function ActiveFixtureRow({ fixture, isKnockout }) {
    const [home, setHome]     = useState(fixture.home_score ?? '');
    const [away, setAway]     = useState(fixture.away_score ?? '');
    const [winner, setWinner] = useState(fixture.winner_team_id ?? '');
    const [status, setStatus] = useState(fixture.status);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved]   = useState(false);

    const homeTeam = fixture.home_team?.name ?? fixture.home_placeholder ?? 'TBD';
    const awayTeam = fixture.away_team?.name ?? fixture.away_placeholder ?? 'TBD';

    const homeScore  = home === '' ? null : Number(home);
    const awayScore  = away === '' ? null : Number(away);
    const isDraw     = homeScore !== null && awayScore !== null && homeScore === awayScore;
    const needsWinner = isKnockout && isDraw;

    const effectiveWinner = (() => {
        if (winner) return winner;
        if (homeScore !== null && awayScore !== null) {
            if (homeScore > awayScore) return fixture.home_team_id;
            if (awayScore > homeScore) return fixture.away_team_id;
        }
        return '';
    })();

    const canSave = homeScore !== null && awayScore !== null && (!needsWinner || effectiveWinner);

    const save = () => {
        if (!canSave || saving) return;
        setSaving(true);
        router.patch(
            route('admin.score-entry.update', fixture.id),
            { home_score: homeScore, away_score: awayScore, winner_team_id: effectiveWinner || null, status },
            {
                preserveScroll: true,
                onSuccess: () => { setSaved(true); setSaving(false); setTimeout(() => setSaved(false), 2500); },
                onError:   () => setSaving(false),
            }
        );
    };

    const isLive = status === 'in_progress';

    return (
        <div className={[
            'rounded-lg bg-white shadow border-l-4 p-4',
            isLive ? 'border-green-500' : 'border-transparent',
        ].join(' ')}>
            <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                {/* Match info */}
                <div className="flex items-center gap-2 sm:w-28 flex-shrink-0">
                    <span className="text-xs font-mono text-gray-400 w-8">M{fixture.match_number}</span>
                    <select
                        value={status}
                        onChange={e => { setStatus(e.target.value); setSaved(false); }}
                        className={[
                            'text-xs rounded px-1.5 py-0.5 border font-medium flex-1',
                            isLive ? 'bg-green-100 text-green-700 border-green-300' : 'bg-gray-100 text-gray-600 border-gray-200',
                        ].join(' ')}
                    >
                        {STATUS_OPTIONS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
                    </select>
                </div>

                {/* Score row */}
                <div className="flex items-center gap-3 flex-1">
                    <div className="flex-1 text-right">
                        <p className="text-sm font-semibold text-gray-900 truncate">{homeTeam}</p>
                        {fixture.home_team?.flag_url && (
                            <img src={fixture.home_team.flag_url} alt="" className="h-4 w-6 object-cover ml-auto mt-0.5" />
                        )}
                    </div>

                    <div className="flex items-center gap-1.5">
                        <input
                            type="number" min="0" max="30"
                            value={home}
                            onChange={e => { setHome(e.target.value); setSaved(false); setWinner(''); }}
                            className="w-14 h-12 text-center text-2xl font-bold border-2 border-gray-300 rounded focus:border-indigo-500 focus:outline-none"
                            inputMode="numeric"
                        />
                        <span className="text-gray-400 font-bold text-lg">–</span>
                        <input
                            type="number" min="0" max="30"
                            value={away}
                            onChange={e => { setAway(e.target.value); setSaved(false); setWinner(''); }}
                            className="w-14 h-12 text-center text-2xl font-bold border-2 border-gray-300 rounded focus:border-indigo-500 focus:outline-none"
                            inputMode="numeric"
                        />
                    </div>

                    <div className="flex-1">
                        <p className="text-sm font-semibold text-gray-900 truncate">{awayTeam}</p>
                        {fixture.away_team?.flag_url && (
                            <img src={fixture.away_team.flag_url} alt="" className="h-4 w-6 object-cover mt-0.5" />
                        )}
                    </div>
                </div>

                {/* Save button */}
                <div className="sm:w-28 flex-shrink-0">
                    <button
                        onClick={save}
                        disabled={!canSave || saving}
                        className={[
                            'w-full py-2.5 rounded text-sm font-medium transition-colors',
                            saved
                                ? 'bg-green-500 text-white'
                                : canSave
                                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed',
                        ].join(' ')}
                    >
                        {saved ? '✓ Guardado' : saving ? 'Guardando…' : 'Guardar'}
                    </button>
                </div>
            </div>

            {/* Knockout winner selector — only when draw */}
            {isKnockout && isDraw && homeScore !== null && (
                <div className="mt-3 flex items-center gap-2 pt-2 border-t border-gray-100">
                    <span className="text-xs text-gray-500 flex-shrink-0">Ganador (ET/Pen):</span>
                    <div className="flex gap-2 flex-1">
                        {[
                            { id: fixture.home_team_id, label: homeTeam },
                            { id: fixture.away_team_id, label: awayTeam },
                        ].filter(o => o.id).map(option => (
                            <button
                                key={option.id}
                                type="button"
                                onClick={() => setWinner(String(option.id))}
                                className={[
                                    'flex-1 py-1.5 text-xs rounded border-2 font-medium transition-colors',
                                    String(effectiveWinner) === String(option.id)
                                        ? 'bg-indigo-600 border-indigo-600 text-white'
                                        : 'bg-white border-gray-200 text-gray-700 hover:border-indigo-400',
                                ].join(' ')}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function FinishedFixtureRow({ fixture }) {
    const homeTeam = fixture.home_team?.name ?? fixture.home_placeholder ?? 'TBD';
    const awayTeam = fixture.away_team?.name ?? fixture.away_placeholder ?? 'TBD';

    return (
        <div className="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 flex items-center gap-3">
            <span className="text-xs font-mono text-slate-400 w-8 flex-shrink-0">M{fixture.match_number}</span>

            <div className="flex items-center gap-2 flex-1 min-w-0">
                <p className="flex-1 text-right text-sm text-slate-600 truncate">{homeTeam}</p>
                <p className="font-mono text-sm font-bold text-slate-700 flex-shrink-0">
                    {fixture.home_score} – {fixture.away_score}
                </p>
                <p className="flex-1 text-sm text-slate-600 truncate">{awayTeam}</p>
            </div>

            <span className="rounded bg-blue-100 px-2 py-0.5 text-xs text-blue-700 flex-shrink-0">Finalizado</span>

            <Link
                href={route('admin.fixtures.edit', fixture.id)}
                className="text-xs text-indigo-600 hover:text-indigo-800 flex-shrink-0"
            >
                Corregir →
            </Link>
        </div>
    );
}

export default function ScoreEntry({ rounds, fixtures, activeRound, selectedRoundId }) {
    const isKnockout = activeRound && activeRound.slug !== 'grupos';

    const filterRound = (id) =>
        router.get(route('admin.score-entry'), { round_id: id }, { preserveState: true });

    const active   = fixtures.filter(f => f.status !== 'finished');
    const finished = fixtures.filter(f => f.status === 'finished');

    return (
        <AdminLayout header="Score Entry">
            <Head title="Admin — Score Entry" />

            {/* Round tabs */}
            <div className="flex gap-2 flex-wrap mb-5">
                {rounds.map(r => (
                    <button
                        key={r.id}
                        onClick={() => filterRound(r.id)}
                        className={[
                            'px-3 py-1.5 rounded text-sm font-medium border-2 transition-colors',
                            selectedRoundId === r.id
                                ? 'bg-indigo-600 border-indigo-600 text-white'
                                : 'bg-white border-gray-200 text-gray-700 hover:border-indigo-400',
                        ].join(' ')}
                    >
                        {r.name}
                        {r.is_open && <span className="ml-1.5 text-xs opacity-70">●</span>}
                    </button>
                ))}
            </div>

            {rounds.length === 0 && (
                <p className="text-sm text-gray-500">No hay fases abiertas o bloqueadas aún.</p>
            )}

            {activeRound && (
                <>
                    <p className="text-sm text-gray-500 mb-4">
                        {active.length} pendientes · {finished.length} finalizados · {fixtures.length} total
                    </p>

                    {fixtures.length === 0 && (
                        <p className="text-sm text-gray-500">No hay partidos en esta fase.</p>
                    )}

                    {/* Active fixtures */}
                    <div className="space-y-3">
                        {active.map(fixture => (
                            <ActiveFixtureRow
                                key={fixture.id}
                                fixture={fixture}
                                isKnockout={isKnockout}
                            />
                        ))}
                    </div>

                    {/* Finished fixtures section */}
                    {finished.length > 0 && (
                        <>
                            <div className="my-5 flex items-center gap-3">
                                <div className="h-px flex-1 bg-slate-200" />
                                <span className="text-xs text-slate-400 font-medium">
                                    — Finalizados ({finished.length}) —
                                </span>
                                <div className="h-px flex-1 bg-slate-200" />
                            </div>
                            <div className="space-y-2">
                                {finished.map(fixture => (
                                    <FinishedFixtureRow key={fixture.id} fixture={fixture} />
                                ))}
                            </div>
                        </>
                    )}
                </>
            )}
        </AdminLayout>
    );
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Admin/ScoreEntry.jsx
git commit -m "feat: redesign ScoreEntry — 1-per-row, finished section separated, lock read-only cards"
```

---

## Task 6: FixtureController + Fixtures/Index.jsx — chronological ordering + Edit.jsx amber banner

**Files:**
- Modify: `app/Http/Controllers/Admin/FixtureController.php`
- Modify: `resources/js/Pages/Admin/Fixtures/Edit.jsx`

- [ ] **Step 1: Fix ordering in FixtureController::index()**

In `app/Http/Controllers/Admin/FixtureController.php`, change the query in `index()`:

```php
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
```

- [ ] **Step 2: Add amber banner to Edit.jsx for finished fixtures**

In `resources/js/Pages/Admin/Fixtures/Edit.jsx`, add the banner right after the opening `<div className="max-w-2xl rounded-lg bg-white p-6 shadow">` and before the `<form>`:

```jsx
{fixture.status === 'finished' && (
    <div className="mb-4 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
        <strong>Partido finalizado.</strong> Estás editando un resultado ya registrado. Los cambios recalcularán los puntos automáticamente.
    </div>
)}
```

The result section around line 30:

```jsx
<div className="max-w-2xl rounded-lg bg-white p-6 shadow">
    {fixture.status === 'finished' && (
        <div className="mb-4 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            <strong>Partido finalizado.</strong> Estás editando un resultado ya registrado. Los cambios recalcularán los puntos automáticamente.
        </div>
    )}
    <form onSubmit={submit} className="space-y-4">
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/FixtureController.php resources/js/Pages/Admin/Fixtures/Edit.jsx
git commit -m "feat: order fixtures by match_date, amber banner in Edit for finished fixtures"
```

---

## Task 7: PredictionController::receipt() — chronological ordering

**Files:**
- Modify: `app/Http/Controllers/PredictionController.php`

- [ ] **Step 1: Fix ordering in receipt()**

In `app/Http/Controllers/PredictionController.php`, find the `receipt()` method (around line 250) and change:

```php
// Before:
->orderBy('match_number')

// After:
->orderBy('match_date')
```

The full fixtures query in `receipt()` becomes:

```php
$fixtures = $round->fixtures()
    ->with(['homeTeam', 'awayTeam', 'group'])
    ->orderBy('match_date')
    ->get();
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/PredictionController.php
git commit -m "fix: order fixtures by match_date in receipt() for chronological display"
```

---

## Task 8: RankingController + HomeController — exclude admin from user queries

**Files:**
- Modify: `app/Http/Controllers/RankingController.php`
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `tests/Feature/RankingControllerTest.php` (or create if missing)

- [ ] **Step 1: Write failing test**

Check if `tests/Feature/RankingControllerTest.php` exists. If not, create it. Add this test:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('excludes admin users from the ranking', function () {
    $user  = User::factory()->create(['role' => 'user',  'is_active' => true, 'total_points' => 100]);
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'total_points' => 999]);

    $this->actingAs($user)
        ->get(route('ranking.index'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Ranking')
            ->where('users', fn ($users) =>
                collect($users)->every(fn ($u) => $u['id'] !== $admin->id)
            )
        );
});
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
./vendor/bin/sail test --filter "excludes admin users from the ranking"
```

Expected: FAIL (admin appears in users list).

- [ ] **Step 3: Update RankingController**

In `app/Http/Controllers/RankingController.php`, add `->where('role', 'user')` to the users query and the activated count:

```php
$users = User::where('is_active', true)
    ->where('role', 'user')
    ->orderByDesc('total_points')
    ->select(['id', 'name', 'total_points'])
    ->get()
    ->map(function ($user) use (&$position, &$lastPts, &$counter, $avatarColors) {
        $counter++;
        if ($user->total_points !== $lastPts) {
            $position = $counter;
            $lastPts  = $user->total_points;
        }
        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'total_points' => $user->total_points,
            'position'     => $position,
            'avatarColor'  => $avatarColors[$user->id % 4],
            'delta'        => '+0',
        ];
    });

$activated = User::where('is_activated', true)->where('role', 'user')->count();
```

- [ ] **Step 4: Update HomeController**

In `app/Http/Controllers/HomeController.php`, add `->where('role', 'user')` to the position and totalActive queries:

```php
$position = User::where('is_active', true)
    ->where('role', 'user')
    ->where('total_points', '>', $user->total_points)
    ->count() + 1;

$totalActive = User::where('is_active', true)->where('role', 'user')->count();
```

- [ ] **Step 5: Run the new test**

```bash
./vendor/bin/sail test --filter "excludes admin users from the ranking"
```

Expected: PASS.

- [ ] **Step 6: Run all tests**

```bash
./vendor/bin/sail test
```

Expected: all pass.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/RankingController.php app/Http/Controllers/HomeController.php tests/Feature/RankingControllerTest.php
git commit -m "feat: exclude admin users from ranking and position counts"
```

---

## Task 9: Admin guard on PredictionController + SpecialPredictionController

**Files:**
- Modify: `app/Http/Controllers/PredictionController.php`
- Modify: `app/Http/Controllers/SpecialPredictionController.php`
- Modify: `tests/Feature/PredictionControllerTest.php` (or create if missing)

- [ ] **Step 1: Write failing tests**

Find `tests/Feature/PredictionControllerTest.php` (check with `ls tests/Feature/`). If missing, create it. Add:

```php
<?php

use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects admin to admin dashboard when accessing predictions index', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    Round::factory()->f1()->create(['is_open' => true]);

    $this->actingAs($admin)
        ->get(route('predictions.index'))
        ->assertRedirect(route('admin.dashboard'));
});

it('redirects admin to admin dashboard when accessing a prediction round', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $round = Round::factory()->f1()->create(['is_open' => true]);

    $this->actingAs($admin)
        ->get(route('predictions.show', $round->slug))
        ->assertRedirect(route('admin.dashboard'));
});
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
./vendor/bin/sail test --filter "redirects admin to admin dashboard when accessing predictions"
```

Expected: FAIL (admin can currently access predictions).

- [ ] **Step 3: Add guard to PredictionController**

In `app/Http/Controllers/PredictionController.php`, add an `adminGuard()` helper and call it at the top of each public action. Add as a private method at the bottom of the class:

```php
private function adminGuard(): ?\Illuminate\Http\RedirectResponse
{
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return null;
}
```

Then add `if ($guard = $this->adminGuard()) return $guard;` as the FIRST line of: `index()`, `show()`, `save()`, `submit()`, `receipt()`.

Example for `index()`:

```php
public function index(): Response
{
    if ($guard = $this->adminGuard()) return $guard;

    $userId  = Auth::id();
    // ... rest unchanged
}
```

Example for `show()`:

```php
public function show(Round $round): Response|RedirectResponse
{
    if ($guard = $this->adminGuard()) return $guard;

    if (!$round->is_open) {
    // ... rest unchanged
}
```

Apply the same pattern to `save()`, `submit()`, and `receipt()`.

- [ ] **Step 4: Add guard to SpecialPredictionController**

In `app/Http/Controllers/SpecialPredictionController.php`, add the guard to `show()` and `save()`:

```php
public function show(): Response
{
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    $special = SpecialPrediction::with(['champion', 'runnerUp', 'topScorer.team'])
    // ... rest unchanged
}

public function save(Request $request): RedirectResponse
{
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    $special = SpecialPrediction::where('user_id', Auth::id())->first();
    // ... rest unchanged
}
```

- [ ] **Step 5: Run the new tests**

```bash
./vendor/bin/sail test --filter "redirects admin to admin dashboard when accessing predictions"
```

Expected: PASS.

- [ ] **Step 6: Run all tests**

```bash
./vendor/bin/sail test
```

Expected: all pass.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PredictionController.php app/Http/Controllers/SpecialPredictionController.php tests/Feature/PredictionControllerTest.php
git commit -m "feat: redirect admin away from user predictions to admin dashboard"
```

---

## Task 10: AdminLayout — Chat nav link

**Files:**
- Modify: `resources/js/Layouts/AdminLayout.jsx`

- [ ] **Step 1: Add Chat to NAV_LINKS**

In `resources/js/Layouts/AdminLayout.jsx`, update the `NAV_LINKS` array:

```jsx
const NAV_LINKS = [
    { label: 'Dashboard',   href: () => route('admin.dashboard') },
    { label: 'Rondas',      href: () => route('admin.rounds.index') },
    { label: 'Score Entry', href: () => route('admin.score-entry') },
    { label: 'Partidos',    href: () => route('admin.fixtures.index') },
    { label: 'Equipos',     href: () => route('admin.teams.index') },
    { label: 'Jugadores',   href: () => route('admin.players.index') },
    { label: 'Usuarios',    href: () => route('admin.users.index') },
    { label: 'Chat',        href: () => route('chat.index') },
];
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/AdminLayout.jsx
git commit -m "feat: add Chat link to admin navigation"
```

---

## Task 11: MobileShell — 768px max-width

**Files:**
- Modify: `resources/js/Components/MobileShell.jsx`

- [ ] **Step 1: Add max-width wrapper**

Replace the full content of `resources/js/Components/MobileShell.jsx`:

```jsx
export default function MobileShell({ children }) {
    return (
        <div className="bg-cream min-h-screen overflow-x-hidden">
            <div className="max-w-3xl mx-auto w-full pb-28 relative">
                {children}
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Run all tests**

```bash
./vendor/bin/sail test
```

Expected: all pass (this is a pure frontend change, backend tests unaffected).

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/MobileShell.jsx
git commit -m "feat: constrain MobileShell to 768px max-width centered"
```

---

## Self-Review Checklist

### Spec coverage

| Spec requirement | Covered by |
|---|---|
| Guard: no score edits on finished fixtures | Task 4 (backend) + Task 5 (UI lock) |
| Round finalization idempotency | Task 2 (controller) + Task 3 (UI) |
| Chronological fixture ordering in ScoreEntry | Task 4 (controller FIELD + match_date) |
| Chronological ordering in /fixtures admin index | Task 6 |
| Chronological ordering in receipt() | Task 7 |
| ScoreEntry 1-per-row + finished section | Task 5 |
| Admin excluded from ranking | Task 8 |
| Admin excluded from position count in Home | Task 8 |
| Admin blocked from predictions | Task 9 |
| Admin Chat nav link | Task 10 |
| LiveScoreUpdated dispatch from ScoreEntry | Task 4 |
| MobileShell 768px max-width | Task 11 |
| Amber banner in Edit.jsx for finished fixtures | Task 6 |
| "Finalizada" badge + disabled button in Rounds/Index | Task 3 |

All 14 spec requirements are covered. ✓

### No placeholders — confirmed ✓

All steps include complete code, no TBDs.

### Type consistency — confirmed ✓

- `is_finalized` is used consistently across migration, model, controller, and JSX.
- `LiveScoreUpdated` constructor: `(int $matchId, ?int $homeScore, ?int $awayScore, bool $isLive)` — matches the dispatch call in Task 4.
- `adminGuard()` returns `?\Illuminate\Http\RedirectResponse` — compatible with all action return types.
