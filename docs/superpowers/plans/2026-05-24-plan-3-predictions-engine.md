# Plan 3: Predictions Engine

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow authenticated users to save draft predictions and confirm them for each open round, plus enter pre-tournament special predictions (champion, runner-up, top scorer).

**Architecture:** A `PredictionController` handles the per-round flow (index, show, save draft, submit). A `SpecialPredictionController` handles the one-time special picks. Both use `updateOrCreate` for idempotent upserts. The save endpoint accepts a flat map of `{ [matchId]: {predicted_home, predicted_away} }` from the React form. Knockout no-tie validation runs only on submit, not on save.

**Tech Stack:** Laravel 11 · Inertia.js v2 · React 18 · Pest v3 · pnpm · `useForm` from `@inertiajs/react`

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `routes/web.php` | Modify | Add user prediction routes |
| `app/Http/Controllers/PredictionController.php` | Create | index, show, save, submit |
| `app/Http/Controllers/SpecialPredictionController.php` | Create | show, save |
| `resources/js/Pages/Predictions/Index.jsx` | Create | List rounds with status badges + action buttons |
| `resources/js/Pages/Predictions/Round.jsx` | Create | Full prediction form for a single round |
| `resources/js/Pages/Predictions/Special.jsx` | Create | Champion / runner-up / top-scorer form |
| `tests/Feature/PredictionControllerTest.php` | Create | Feature tests for all prediction routes |
| `tests/Feature/SpecialPredictionControllerTest.php` | Create | Feature tests for special prediction routes |

---

## Codebase context (read before starting)

- `app/Models/Fixture.php` — uses `protected $table = 'matches'`, FK is `match_id` in predictions
- `app/Models/Round.php` — `is_open`, `is_locked`, `slug` (valores: 'grupos', 'r32-r16', 'qf-sf', 'final')
- `app/Models/Prediction.php` — `user_id`, `match_id`, `predicted_home`, `predicted_away`, pts fields
- `app/Models/PredictionSubmission.php` — `user_id`, `round_id`, `status` (draft/submitted/locked), `submitted_at`
- `app/Models/SpecialPrediction.php` — `user_id`, `champion_team_id`, `runner_up_team_id`, `top_scorer_player_id`, `is_locked`
- `database/factories/RoundFactory.php` — states: `r1()`, `r2()`, `r3()`, `r4()`
- `database/factories/PredictionSubmissionFactory.php` — states: `submitted()`, `locked()`
- Admin route pattern: `Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')`
- Tests use `uses(RefreshDatabase::class)`, `beforeEach(function () { $this->user = ... })`, `$this->withoutVite()` on Inertia GETs

---

## Task 1: Routes + PredictionController::index + Predictions/Index.jsx

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/PredictionController.php`
- Create: `resources/js/Pages/Predictions/Index.jsx`
- Create: `tests/Feature/PredictionControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/PredictionControllerTest.php
<?php

use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user', 'is_active' => true]);
});

it('lists rounds with user submission status', function () {
    $open   = Round::factory()->r1()->create(['is_open' => true]);
    $closed = Round::factory()->r2()->create(['is_open' => false, 'order' => 2]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/predictions');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Index')
        ->has('rounds', 2)
        ->has('submissions')
    );
});

it('blocks guests from predictions index', function () {
    $this->get('/predictions')->assertRedirect('/login');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/sail test tests/Feature/PredictionControllerTest.php
```
Expected: FAIL — controller and route don't exist yet.

- [ ] **Step 3: Add routes to `routes/web.php`**

Add these imports at the top:
```php
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\SpecialPredictionController;
```

Add this route group after the existing `auth` middleware group (before `require __DIR__.'/auth.php'`):
```php
Route::middleware(['auth'])->prefix('predictions')->name('predictions.')->group(function () {
    Route::get('/', [PredictionController::class, 'index'])->name('index');
    Route::get('/special', [SpecialPredictionController::class, 'show'])->name('special');
    Route::post('/special', [SpecialPredictionController::class, 'save'])->name('special.save');
    Route::get('/{round}', [PredictionController::class, 'show'])->name('show');
    Route::post('/{round}/save', [PredictionController::class, 'save'])->name('save');
    Route::post('/{round}/submit', [PredictionController::class, 'submit'])->name('submit');
});
```

- [ ] **Step 4: Create `app/Http/Controllers/PredictionController.php`**

```php
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
```

- [ ] **Step 5: Create `resources/js/Pages/Predictions/Index.jsx`**

```jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const STATUS_LABELS = {
    draft:     { label: 'Borrador',    className: 'bg-yellow-100 text-yellow-800' },
    submitted: { label: 'Confirmado',  className: 'bg-green-100 text-green-800' },
    locked:    { label: 'Bloqueado',   className: 'bg-red-100 text-red-800' },
};

export default function Index({ rounds, submissions }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Mis Predicciones</h2>}>
            <Head title="Predicciones" />
            <div className="py-12">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-4">
                    {rounds.map((round) => {
                        const submission = submissions[round.id];
                        const status     = submission?.status;
                        const badge      = STATUS_LABELS[status];
                        const canPredict = round.is_open && status !== 'locked';

                        return (
                            <div key={round.id} className="bg-white shadow rounded-lg p-5 flex items-center justify-between">
                                <div>
                                    <h3 className="font-semibold text-gray-900">{round.name}</h3>
                                    <div className="mt-1 flex items-center gap-2 text-sm text-gray-500">
                                        {round.is_locked && <span className="text-red-600">Cerrada</span>}
                                        {round.is_open && !round.is_locked && <span className="text-green-600">Abierta</span>}
                                        {!round.is_open && !round.is_locked && <span>No disponible</span>}
                                        {badge && (
                                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${badge.className}`}>
                                                {badge.label}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                {(canPredict || status) && (
                                    <Link
                                        href={route('predictions.show', round.id)}
                                        className="ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                                    >
                                        {canPredict ? 'Predecir' : 'Ver'}
                                    </Link>
                                )}
                            </div>
                        );
                    })}

                    <div className="bg-white shadow rounded-lg p-5 flex items-center justify-between">
                        <div>
                            <h3 className="font-semibold text-gray-900">Predicciones Especiales</h3>
                            <p className="text-sm text-gray-500">Campeón · Sub-campeón · Goleador</p>
                        </div>
                        <Link
                            href={route('predictions.special')}
                            className="ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                        >
                            Completar
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/PredictionControllerTest.php --filter "lists rounds"
```
Expected: DEPR (2 tests passing).

- [ ] **Step 7: Commit**

```bash
git add routes/web.php \
    app/Http/Controllers/PredictionController.php \
    resources/js/Pages/Predictions/Index.jsx \
    tests/Feature/PredictionControllerTest.php
git commit -m "feat: add predictions index route, controller, and page"
```

---

## Task 2: PredictionController::show — round prediction form (backend)

**Files:**
- Modify: `tests/Feature/PredictionControllerTest.php`

Note: `PredictionController::show` is already written in Task 1. This task adds tests and verifies it.

- [ ] **Step 1: Add tests for show to `tests/Feature/PredictionControllerTest.php`**

```php
it('shows a round prediction page when round is open with teams assigned', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get("/predictions/{$round->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Round')
        ->has('round')
        ->has('fixtures', 1)
        ->has('predictions')
        ->has('submission')
    );
});

it('redirects from round show when fixtures have unassigned teams', function () {
    $round = Round::factory()->r2()->create(['is_open' => true]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $this->actingAs($this->user)->get("/predictions/{$round->id}")
        ->assertRedirect(route('predictions.index'));
});

it('shows round even when closed (read-only)', function () {
    $round   = Round::factory()->r1()->create(['is_open' => false, 'is_locked' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get("/predictions/{$round->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Predictions/Round'));
});
```

- [ ] **Step 2: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/PredictionControllerTest.php --filter "shows a round\|redirects from round\|shows round even"
```
Expected: DEPR (3 tests passing).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/PredictionControllerTest.php
git commit -m "test: add show route tests for PredictionController"
```

---

## Task 3: PredictionController::save + submit tests

**Files:**
- Modify: `tests/Feature/PredictionControllerTest.php`

Note: `save` and `submit` methods are already written in Task 1. This task adds tests to verify them.

- [ ] **Step 1: Add save tests to `tests/Feature/PredictionControllerTest.php`**

```php
it('saves predictions as draft', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 2, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(1);
    expect(\App\Models\Prediction::first()->predicted_home)->toBe(2);
    expect(\App\Models\PredictionSubmission::first()->status)->toBe('draft');
});

it('updates existing prediction on save', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);
    \App\Models\Prediction::factory()->create([
        'user_id' => $this->user->id, 'match_id' => $fixture->id,
        'predicted_home' => 0, 'predicted_away' => 0,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 3, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(1);
    expect(\App\Models\Prediction::first()->predicted_home)->toBe(3);
});

it('rejects save when round is not open', function () {
    $round   = Round::factory()->r1()->create(['is_open' => false]);
    $group   = \App\Models\Group::factory()->create();
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/save", [
        'predictions' => [(string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 0]],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(0);
});

it('rejects save when submission is locked', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true, 'is_locked' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);
    \App\Models\PredictionSubmission::factory()->locked()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/save", [
        'predictions' => [(string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 0]],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(0);
});
```

- [ ] **Step 2: Add submit tests to `tests/Feature/PredictionControllerTest.php`**

```php
it('submits predictions when all fixtures are covered', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/submit", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 2, 'predicted_away' => 2],
        ],
    ])->assertRedirect(route('predictions.index'));

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('submitted');
    expect(\App\Models\PredictionSubmission::first()->submitted_at)->not->toBeNull();
});

it('rejects submit when not all fixtures covered', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/submit", [
        'predictions' => [], // no predictions
    ])->assertSessionHasErrors('predictions');

    expect(\App\Models\PredictionSubmission::count())->toBe(0);
});

it('rejects submit with tie in knockout round', function () {
    $round   = Round::factory()->r2()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/submit", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 1],
        ],
    ])->assertSessionHasErrors('predictions');

    expect(\App\Models\PredictionSubmission::count())->toBe(0);
});

it('allows ties in group stage (R1) submit', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/submit", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 1],
        ],
    ])->assertRedirect(route('predictions.index'));

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('submitted');
});
```

- [ ] **Step 3: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/PredictionControllerTest.php
```
Expected: DEPR (all tests in file passing).

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/PredictionControllerTest.php
git commit -m "test: add save and submit tests for PredictionController"
```

---

## Task 4: SpecialPredictionController + Predictions/Special.jsx

**Files:**
- Create: `app/Http/Controllers/SpecialPredictionController.php`
- Create: `resources/js/Pages/Predictions/Special.jsx`
- Create: `tests/Feature/SpecialPredictionControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```php
// tests/Feature/SpecialPredictionControllerTest.php
<?php

use App\Models\Player;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user', 'is_active' => true]);
});

it('shows the special predictions form', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    Team::factory()->create(['group_id' => $group->id]);
    Player::factory()->create();

    $response = $this->withoutVite()->actingAs($this->user)->get('/predictions/special');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Special')
        ->has('teams', 1)
        ->has('players', 1)
        ->has('special')
    );
});

it('saves special predictions', function () {
    $group   = Group::factory()->create(['name' => 'A']);
    $champ   = Team::factory()->create(['group_id' => $group->id]);
    $runner  = Team::factory()->create(['group_id' => $group->id]);
    $scorer  = Player::factory()->create();

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertRedirect();

    $special = SpecialPrediction::where('user_id', $this->user->id)->first();
    expect($special)->not->toBeNull();
    expect($special->champion_team_id)->toBe($champ->id);
    expect($special->runner_up_team_id)->toBe($runner->id);
});

it('updates existing special prediction on re-save', function () {
    $group   = Group::factory()->create(['name' => 'A']);
    $champ   = Team::factory()->create(['group_id' => $group->id]);
    $runner  = Team::factory()->create(['group_id' => $group->id]);
    $newChamp = Team::factory()->create(['group_id' => $group->id]);
    $scorer  = Player::factory()->create();

    SpecialPrediction::create([
        'user_id'              => $this->user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
        'is_locked'            => false,
    ]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'     => $newChamp->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertRedirect();

    expect(SpecialPrediction::count())->toBe(1);
    expect(SpecialPrediction::first()->champion_team_id)->toBe($newChamp->id);
});

it('rejects save when champion equals runner-up', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $team   = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'     => $team->id,
        'runner_up_team_id'    => $team->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertSessionHasErrors('runner_up_team_id');
});

it('blocks save when special predictions are locked', function () {
    $group   = Group::factory()->create(['name' => 'A']);
    $champ   = Team::factory()->create(['group_id' => $group->id]);
    $runner  = Team::factory()->create(['group_id' => $group->id]);
    $scorer  = Player::factory()->create();

    SpecialPrediction::create([
        'user_id'              => $this->user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
        'is_locked'            => true,
    ]);

    $newChamp = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'     => $newChamp->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertRedirect();

    expect(SpecialPrediction::first()->champion_team_id)->toBe($champ->id); // unchanged
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/SpecialPredictionControllerTest.php
```
Expected: FAIL — controller doesn't exist yet.

- [ ] **Step 3: Create `app/Http/Controllers/SpecialPredictionController.php`**

```php
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
        $special = SpecialPrediction::where('user_id', Auth::id())->first();

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
```

- [ ] **Step 4: Create `resources/js/Pages/Predictions/Special.jsx`**

```jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Special({ special, teams, players }) {
    const { data, setData, post, processing, errors } = useForm({
        champion_team_id:     special?.champion_team_id    ?? '',
        runner_up_team_id:    special?.runner_up_team_id   ?? '',
        top_scorer_player_id: special?.top_scorer_player_id ?? '',
    });

    const isLocked = special?.is_locked ?? false;

    function handleSubmit(e) {
        e.preventDefault();
        post(route('predictions.special.save'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Predicciones Especiales</h2>}>
            <Head title="Predicciones Especiales" />
            <div className="py-12">
                <div className="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white shadow rounded-lg p-6">
                        {isLocked && (
                            <p className="mb-4 text-sm text-red-600 font-medium">
                                Tus predicciones especiales están bloqueadas.
                            </p>
                        )}
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Campeón (30 pts)</label>
                                <select
                                    value={data.champion_team_id}
                                    onChange={e => setData('champion_team_id', e.target.value)}
                                    disabled={isLocked}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">— Seleccionar equipo —</option>
                                    {teams.map(t => (
                                        <option key={t.id} value={t.id}>
                                            {t.name} ({t.group?.name ?? '?'})
                                        </option>
                                    ))}
                                </select>
                                {errors.champion_team_id && <p className="mt-1 text-xs text-red-600">{errors.champion_team_id}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Sub-campeón (10 pts)</label>
                                <select
                                    value={data.runner_up_team_id}
                                    onChange={e => setData('runner_up_team_id', e.target.value)}
                                    disabled={isLocked}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">— Seleccionar equipo —</option>
                                    {teams.map(t => (
                                        <option key={t.id} value={t.id}>
                                            {t.name} ({t.group?.name ?? '?'})
                                        </option>
                                    ))}
                                </select>
                                {errors.runner_up_team_id && <p className="mt-1 text-xs text-red-600">{errors.runner_up_team_id}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Goleador (15 pts)</label>
                                <select
                                    value={data.top_scorer_player_id}
                                    onChange={e => setData('top_scorer_player_id', e.target.value)}
                                    disabled={isLocked}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">— Seleccionar jugador —</option>
                                    {players.map(p => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} ({p.team?.name ?? '?'})
                                        </option>
                                    ))}
                                </select>
                                {errors.top_scorer_player_id && <p className="mt-1 text-xs text-red-600">{errors.top_scorer_player_id}</p>}
                            </div>

                            {!isLocked && (
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full inline-flex justify-center py-2 px-4 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Guardar predicciones especiales
                                </button>
                            )}
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/SpecialPredictionControllerTest.php
```
Expected: DEPR (5 tests passing).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/SpecialPredictionController.php \
    resources/js/Pages/Predictions/Special.jsx \
    tests/Feature/SpecialPredictionControllerTest.php
git commit -m "feat: add special predictions controller and form"
```

---

## Task 5: Predictions/Round.jsx — Full prediction UI

**Files:**
- Create: `resources/js/Pages/Predictions/Round.jsx`

This is a pure frontend task. No new backend code. The backend methods are already tested.

- [ ] **Step 1: Create `resources/js/Pages/Predictions/Round.jsx`**

```jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

// ---- helpers ----

function groupFixtures(fixtures) {
    return fixtures.reduce((acc, f) => {
        const key = f.group?.name ?? 'Sin Grupo';
        if (!acc[key]) acc[key] = [];
        acc[key].push(f);
        return acc;
    }, {});
}

function teamName(team, placeholder) {
    return team ? team.name : (placeholder ?? 'TBD');
}

// ---- sub-components ----

function ScoreInput({ value, onChange, disabled }) {
    return (
        <input
            type="number"
            min="0"
            max="20"
            value={value}
            onChange={e => onChange(parseInt(e.target.value, 10) || 0)}
            disabled={disabled}
            className="w-14 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm disabled:bg-gray-100"
        />
    );
}

function FixtureRow({ fixture, home, away, onChange, disabled }) {
    return (
        <div className="flex items-center gap-3 py-2 border-b last:border-0">
            <span className="flex-1 text-right text-sm font-medium text-gray-800 truncate">
                {teamName(fixture.home_team, fixture.home_placeholder)}
            </span>
            <ScoreInput value={home} onChange={v => onChange(fixture.id, 'home', v)} disabled={disabled} />
            <span className="text-gray-400 text-sm">-</span>
            <ScoreInput value={away} onChange={v => onChange(fixture.id, 'away', v)} disabled={disabled} />
            <span className="flex-1 text-left text-sm font-medium text-gray-800 truncate">
                {teamName(fixture.away_team, fixture.away_placeholder)}
            </span>
        </div>
    );
}

// ---- main page ----

export default function Round({ round, fixtures, predictions, submission }) {
    const isLocked   = submission?.status === 'locked';
    const isSubmitted = submission?.status === 'submitted';
    const isGroupStage = round.slug === 'grupos';

    // Initialize scores from existing predictions
    const initialScores = {};
    fixtures.forEach(f => {
        const pred = predictions[f.id];
        initialScores[f.id] = {
            home: pred ? pred.predicted_home : '',
            away: pred ? pred.predicted_away : '',
        };
    });

    const [scores, setScores] = useState(initialScores);

    function handleChange(fixtureId, side, value) {
        setScores(prev => ({
            ...prev,
            [fixtureId]: { ...prev[fixtureId], [side]: value },
        }));
    }

    function buildPayload() {
        const predictions = {};
        fixtures.forEach(f => {
            const s = scores[f.id];
            if (s.home !== '' && s.away !== '') {
                predictions[String(f.id)] = {
                    predicted_home: Number(s.home),
                    predicted_away: Number(s.away),
                };
            }
        });
        return { predictions };
    }

    // Validation for submit button
    const allFilled = fixtures.every(f => scores[f.id]?.home !== '' && scores[f.id]?.away !== '');

    const knockoutTieError = !isGroupStage && fixtures.some(f => {
        const s = scores[f.id];
        return s?.home !== '' && s?.away !== '' && Number(s.home) === Number(s.away);
    });

    const canSubmit = allFilled && !knockoutTieError && !isLocked && round.is_open;

    function handleSave() {
        router.post(route('predictions.save', round.id), buildPayload());
    }

    function handleSubmit() {
        router.post(route('predictions.submit', round.id), buildPayload());
    }

    const groups = isGroupStage ? groupFixtures(fixtures) : null;

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-800">{round.name}</h2>
                {submission && (
                    <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                        isLocked    ? 'bg-red-100 text-red-700' :
                        isSubmitted ? 'bg-green-100 text-green-700' :
                                      'bg-yellow-100 text-yellow-700'
                    }`}>
                        {isLocked ? 'Bloqueado' : isSubmitted ? 'Confirmado' : 'Borrador'}
                    </span>
                )}
            </div>
        }>
            <Head title={`Predecir — ${round.name}`} />
            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">
                    {isGroupStage ? (
                        Object.entries(groups).sort(([a], [b]) => a.localeCompare(b)).map(([groupName, gFixtures]) => (
                            <div key={groupName} className="bg-white shadow rounded-lg p-4">
                                <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">
                                    Grupo {groupName}
                                </h3>
                                {gFixtures.map(f => (
                                    <FixtureRow
                                        key={f.id}
                                        fixture={f}
                                        home={scores[f.id]?.home ?? ''}
                                        away={scores[f.id]?.away ?? ''}
                                        onChange={handleChange}
                                        disabled={isLocked}
                                    />
                                ))}
                            </div>
                        ))
                    ) : (
                        <div className="bg-white shadow rounded-lg p-4">
                            {fixtures.map(f => (
                                <FixtureRow
                                    key={f.id}
                                    fixture={f}
                                    home={scores[f.id]?.home ?? ''}
                                    away={scores[f.id]?.away ?? ''}
                                    onChange={handleChange}
                                    disabled={isLocked}
                                />
                            ))}
                        </div>
                    )}

                    {knockoutTieError && (
                        <p className="text-sm text-red-600 font-medium">
                            En rondas de eliminación no puede haber empates — debe haber un ganador.
                        </p>
                    )}

                    {!isLocked && round.is_open && (
                        <div className="flex gap-3 justify-end">
                            <button
                                onClick={handleSave}
                                className="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300"
                            >
                                Guardar borrador
                            </button>
                            <button
                                onClick={handleSubmit}
                                disabled={!canSubmit}
                                className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Confirmar predicciones
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
```

- [ ] **Step 2: Verify no build errors**

```bash
./vendor/bin/sail pnpm run build 2>&1 | tail -20
```
Expected: build succeeds (no JSX errors).

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Predictions/Round.jsx
git commit -m "feat: add round prediction UI with group stage and knockout layouts"
```

---

## Task 6: Full Test Suite + Final Verification

**Files:** None (verification only)

- [ ] **Step 1: Run the full test suite**

```bash
./vendor/bin/sail test
```
Expected: all tests PASS or DEPR (zero FAIL).

- [ ] **Step 2: If any tests fail, fix them**

Read the failing test output carefully. Common issues:
- Missing `use` imports in test files
- Fixture factory `match_number` uniqueness — if multiple fixtures are created in one test, pass explicit `match_number` values: `Fixture::factory()->create(['match_number' => 1, ...])` and `Fixture::factory()->create(['match_number' => 2, ...])`.
- `group_id` null constraint — `Fixture::factory()->create(['round_id' => ..., 'group_id' => null])` is fine for knockout fixtures since the migration allows null.

- [ ] **Step 3: Confirm final count**

Expected output ends with something like:
```
Tests:    N deprecated (M assertions)
Duration: Xs
```
Zero failures.

- [ ] **Step 4: Commit if any fixes were needed**

```bash
git add -p   # review each change
git commit -m "fix: address full suite failures in prediction tests"
```

---

## Self-Review

**Spec coverage check:**

| Spec requirement | Covered by |
|---|---|
| Draft save (Guardar progreso) | Task 1 — `save` method + Task 3 tests |
| Submit (Confirmar) | Task 1 — `submit` method + Task 3 tests |
| Status lifecycle: draft → submitted → locked | Task 1 controller + Task 3 tests |
| Knockout no-ties validation (frontend + backend) | Task 1 `submit`, Task 3 tests, Task 5 Round.jsx |
| All fixtures must be covered before submit | Task 1 `submit` — `$fixtureIds->diff($coveredIds)` |
| Guard: round must be open to save/submit | Task 1 — `is_open` check |
| Guard: locked submission cannot be modified | Task 1 — `status === 'locked'` check |
| Guard: unassigned teams block round access | Task 1 `show` — `whereNull(home/away_team_id)` |
| Read-only access to closed/locked round | Task 2 test — "shows round even when closed" |
| Special predictions: champion/runner-up/scorer | Task 4 |
| Special predictions locked guard | Task 4 — `is_locked` check |
| Champion ≠ runner-up validation | Task 4 — `different:champion_team_id` |
| Predictions Index page with round status | Task 1 — `Predictions/Index.jsx` |
| Group-stage fixtures grouped by group | Task 5 — `groupFixtures()` helper |
| Knockout fixtures as flat list | Task 5 — `isGroupStage` branching |

**Placeholder scan:** None found.

**Type consistency:**
- `predictions[fixture.id]` in Round.jsx matches `->keyBy('match_id')` serialized as `{[matchId]: {...}}` — correct.
- Route names: `predictions.index`, `predictions.show`, `predictions.save`, `predictions.submit`, `predictions.special`, `predictions.special.save` — consistent with `routes/web.php` definition.
- `round.slug === 'grupos'` — matches `RoundFactory::r1()` slug value `'grupos'`.
