# Pre-launch Polish Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Corregir 4 bugs/features pre-deploy (especiales, comprobante público, premios, real-time) y agregar una suite de simulación en 2 capas.

**Architecture:** Cambios incrementales en controllers existentes + frontend React. Sin nuevos modelos ni migraciones. La suite de simulación añade tests Pest y un seeder de datos de prueba.

**Tech Stack:** Laravel 11, Pest v3, React 18 + Inertia.js, Laravel Echo + Reverb, pnpm

---

## Archivos afectados

| Archivo | Tipo | Cambio |
|---------|------|--------|
| `app/Http/Controllers/Admin/RoundController.php` | Modify | Lock R1 → bloquear todas las special_predictions |
| `app/Http/Controllers/SpecialPredictionController.php` | Modify | Guard: rechazar si ronda grupos está bloqueada |
| `app/Http/Controllers/PredictionController.php` | Modify | `receipt()` acepta `user_id`, carga `specialPrediction`, `usersWithSubmission` |
| `app/Http/Controllers/Admin/FixtureController.php` | Modify | Dispatch `LiveScoreUpdated` en `update()` |
| `app/Http/Controllers/RankingController.php` | Modify | 70%/20%, agregar `amountPerPlayer` |
| `resources/js/Components/composed/PozoCard.jsx` | Modify | Cambiar 30% → 20% |
| `resources/js/Pages/Predictions/Receipt.jsx` | Modify | Selector de usuario + sección especiales para R1 |
| `resources/js/Pages/Matches.jsx` | Modify | `useState` para matchDays + listener Echo `LiveScoreUpdated` |
| `resources/js/Pages/Rules.jsx` | Modify | Premio 2° = 20%, nota sobre comisión 10% |
| `tests/Feature/SpecialPredictionLockTest.php` | Create | Tests de bloqueo de especiales |
| `tests/Feature/PredictionReceiptTest.php` | Create | Tests de comprobante público y especiales en R1 |
| `tests/Feature/Simulation/TournamentFlowTest.php` | Create | Tests de flujo cronológico del torneo |
| `database/seeders/SimulationSeeder.php` | Create | Usuarios de prueba con credenciales conocidas |
| `docs/superpowers/simulations/run-simulation.md` | Create | Instrucciones para correr sub-agentes Layer 2 |

---

## Task 1: Bloqueo de predicciones especiales al cerrar R1

**Files:**
- Modify: `app/Http/Controllers/Admin/RoundController.php`
- Modify: `app/Http/Controllers/SpecialPredictionController.php`
- Create: `tests/Feature/SpecialPredictionLockTest.php`

- [ ] **Step 1: Escribir los tests fallidos**

Crear `tests/Feature/SpecialPredictionLockTest.php`:

```php
<?php

use App\Models\Round;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('locking the grupos round sets is_locked=true on all special predictions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user1 = User::factory()->create(['role' => 'user']);
    $user2 = User::factory()->create(['role' => 'user']);
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);

    SpecialPrediction::factory()->create(['user_id' => $user1->id, 'is_locked' => false]);
    SpecialPrediction::factory()->create(['user_id' => $user2->id, 'is_locked' => false]);

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");

    expect(SpecialPrediction::where('is_locked', false)->count())->toBe(0);
    expect(SpecialPrediction::where('is_locked', true)->count())->toBe(2);
});

it('locking a non-grupos round does not affect special predictions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user  = User::factory()->create(['role' => 'user']);
    $r1    = Round::factory()->f1()->create(['is_locked' => true]);  // grupos already locked
    $r2    = Round::factory()->f2()->create(['is_open' => true, 'is_locked' => false]);

    SpecialPrediction::factory()->create(['user_id' => $user->id, 'is_locked' => true]);

    $this->actingAs($admin)->post("/admin/rounds/{$r2->slug}/lock");

    // Still locked (was already true), count stays 1
    expect(SpecialPrediction::where('is_locked', true)->count())->toBe(1);
});

it('cannot save special predictions when grupos round is locked and no record exists', function () {
    $round  = Round::factory()->f1()->create(['is_locked' => true]);
    $user   = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $team1  = Team::factory()->create();
    $team2  = Team::factory()->create();
    $player = Player::factory()->for($team1)->create();

    $this->actingAs($user)
        ->post(route('predictions.special.save'), [
            'champion_team_id'  => $team1->id,
            'runner_up_team_id' => $team2->id,
            'top_scorer_player_id' => $player->id,
        ])
        ->assertSessionHas('status');

    expect(SpecialPrediction::where('user_id', $user->id)->count())->toBe(0);
});

it('can save special predictions when grupos round is open', function () {
    Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);
    $user   = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $team1  = Team::factory()->create();
    $team2  = Team::factory()->create();
    $player = Player::factory()->for($team1)->create();

    $this->actingAs($user)
        ->post(route('predictions.special.save'), [
            'champion_team_id'  => $team1->id,
            'runner_up_team_id' => $team2->id,
            'top_scorer_player_id' => $player->id,
        ])
        ->assertSessionHas('status');

    expect(SpecialPrediction::where('user_id', $user->id)->count())->toBe(1);
});
```

- [ ] **Step 2: Correr tests — deben fallar**

```bash
./vendor/bin/sail test tests/Feature/SpecialPredictionLockTest.php
```

Esperado: FAIL — los primeros 2 tests fallan porque `RoundController::lock()` no bloquea especiales.

- [ ] **Step 3: Modificar `RoundController::lock()`**

En `app/Http/Controllers/Admin/RoundController.php`, reemplazar el método `lock()`:

```php
public function lock(Round $round): RedirectResponse
{
    $round->update(['is_open' => false, 'is_locked' => true]);

    if ($round->slug === 'grupos') {
        \App\Models\SpecialPrediction::query()->update(['is_locked' => true]);
    }

    RoundLocked::dispatch($round->name);

    return back()->with('status', "Ronda '{$round->name}' bloqueada.");
}
```

- [ ] **Step 4: Modificar `SpecialPredictionController::save()`**

En `app/Http/Controllers/SpecialPredictionController.php`, agregar el guard al inicio del método `save()`, justo después de la redirección de admin:

```php
public function save(Request $request): RedirectResponse
{
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    // Guard: si la ronda de grupos está bloqueada, rechazar aunque no exista registro previo
    $gruposRound = \App\Models\Round::where('slug', 'grupos')->first();
    if ($gruposRound?->is_locked) {
        return back()->with('status', 'Las predicciones especiales están bloqueadas.');
    }

    $special = SpecialPrediction::where('user_id', Auth::id())->first();

    if ($special && $special->is_locked) {
        return back()->with('status', 'Tus predicciones especiales están bloqueadas.');
    }

    // ... resto del método sin cambios ...
```

- [ ] **Step 5: Correr tests — deben pasar**

```bash
./vendor/bin/sail test tests/Feature/SpecialPredictionLockTest.php
```

Esperado: 4 PASS

- [ ] **Step 6: Correr suite completa para verificar no hay regresiones**

```bash
./vendor/bin/sail test
```

Esperado: todos los tests existentes pasan.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Admin/RoundController.php \
        app/Http/Controllers/SpecialPredictionController.php \
        tests/Feature/SpecialPredictionLockTest.php
git commit -m "feat: lock special predictions when grupos round is locked"
```

---

## Task 2: Comprobante público + especiales en R1 (backend)

**Files:**
- Modify: `app/Http/Controllers/PredictionController.php`
- Create: `tests/Feature/PredictionReceiptTest.php`

- [ ] **Step 1: Escribir los tests fallidos**

Crear `tests/Feature/PredictionReceiptTest.php`:

```php
<?php

use App\Models\Fixture;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Especiales en comprobante de R1 ──────────────────────────────────────────

it('includes specialPrediction prop in R1 receipt when it exists', function () {
    $this->withoutVite();
    $round  = Round::factory()->f1()->create(['is_locked' => true]);
    $user   = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $team1  = Team::factory()->create();
    $team2  = Team::factory()->create();
    $player = Player::factory()->for($team1)->create();

    SpecialPrediction::factory()->create([
        'user_id'              => $user->id,
        'champion_team_id'     => $team1->id,
        'runner_up_team_id'    => $team2->id,
        'top_scorer_player_id' => $player->id,
        'is_locked'            => true,
    ]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $user->id,
        'round_id' => $round->id,
    ]);

    $this->actingAs($user)
        ->get(route('predictions.receipt', $round))
        ->assertInertia(fn ($page) => $page
            ->component('Predictions/Receipt')
            ->has('specialPrediction')
            ->where('specialPrediction.champion_team_id', $team1->id)
        );
});

it('specialPrediction prop is null for non-grupos rounds', function () {
    $this->withoutVite();
    $round = Round::factory()->f2()->create(['is_locked' => true]);
    $user  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $user->id,
        'round_id' => $round->id,
    ]);

    $this->actingAs($user)
        ->get(route('predictions.receipt', $round))
        ->assertInertia(fn ($page) => $page
            ->component('Predictions/Receipt')
            ->where('specialPrediction', null)
        );
});

// ── Comprobante público ───────────────────────────────────────────────────────

it('includes usersWithSubmission when round is locked', function () {
    $this->withoutVite();
    $round = Round::factory()->f1()->create(['is_locked' => true]);
    $user  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $user->id,
        'round_id' => $round->id,
    ]);

    $this->actingAs($user)
        ->get(route('predictions.receipt', $round))
        ->assertInertia(fn ($page) => $page
            ->has('usersWithSubmission')
            ->has('viewedUserId')
            ->has('authUserId')
        );
});

it('usersWithSubmission is null when round is not locked', function () {
    $this->withoutVite();
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);
    $user  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->create([
        'user_id'  => $user->id,
        'round_id' => $round->id,
        'status'   => 'submitted',
    ]);

    $this->actingAs($user)
        ->get(route('predictions.receipt', $round))
        ->assertInertia(fn ($page) => $page
            ->where('usersWithSubmission', null)
        );
});

it('can view another users receipt when round is locked', function () {
    $this->withoutVite();
    $round  = Round::factory()->f2()->create(['is_locked' => true]);
    $viewer = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $owner  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $viewer->id,
        'round_id' => $round->id,
    ]);
    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $owner->id,
        'round_id' => $round->id,
    ]);

    $this->actingAs($viewer)
        ->get(route('predictions.receipt', $round) . '?user_id=' . $owner->id)
        ->assertInertia(fn ($page) => $page
            ->where('viewedUserId', $owner->id)
            ->where('authUserId', $viewer->id)
        );
});

it('ignores user_id param when round is not locked', function () {
    $this->withoutVite();
    $round  = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);
    $viewer = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $owner  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->create([
        'user_id'  => $viewer->id,
        'round_id' => $round->id,
        'status'   => 'submitted',
    ]);

    $this->actingAs($viewer)
        ->get(route('predictions.receipt', $round) . '?user_id=' . $owner->id)
        ->assertInertia(fn ($page) => $page
            ->where('viewedUserId', $viewer->id)
        );
});

it('falls back to auth user if requested user_id has no submission', function () {
    $this->withoutVite();
    $round  = Round::factory()->f1()->create(['is_locked' => true]);
    $viewer = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $other  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $viewer->id,
        'round_id' => $round->id,
    ]);
    // $other has no submission for this round

    $this->actingAs($viewer)
        ->get(route('predictions.receipt', $round) . '?user_id=' . $other->id)
        ->assertInertia(fn ($page) => $page
            ->where('viewedUserId', $viewer->id)
        );
});
```

- [ ] **Step 2: Correr tests — deben fallar**

```bash
./vendor/bin/sail test tests/Feature/PredictionReceiptTest.php
```

Esperado: FAIL — `receipt()` no tiene los props nuevos.

- [ ] **Step 3: Actualizar `PredictionController::receipt()`**

Reemplazar el método completo en `app/Http/Controllers/PredictionController.php`:

```php
public function receipt(Request $request, Round $round): Response|RedirectResponse
{
    if ($guard = $this->adminGuard()) return $guard;

    // Determinar qué usuario mostrar
    $viewedUserId = Auth::id();
    if ($round->is_locked && $request->filled('user_id')) {
        $requestedId = (int) $request->query('user_id');
        $exists = PredictionSubmission::where('user_id', $requestedId)
            ->where('round_id', $round->id)
            ->exists();
        if ($exists) {
            $viewedUserId = $requestedId;
        }
    }

    $submission = PredictionSubmission::where('user_id', $viewedUserId)
        ->where('round_id', $round->id)
        ->first();

    if (! $submission) {
        return redirect()->route('predictions.index');
    }

    $fixtures = $round->fixtures()
        ->with(['homeTeam', 'awayTeam', 'group'])
        ->orderBy('match_date')
        ->get();

    $predictions = Prediction::where('user_id', $viewedUserId)
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

    // Clasificados reales para comparación (solo ronda finalizada)
    $realClassifierIds = null;
    if ($round->is_locked && $round->slug === 'grupos') {
        $service           = app(GroupStageClassifierService::class);
        $realClassifierIds = $service->getClassifierIds(
            $fixtures,
            fn ($f) => [$f->home_score, $f->away_score]
        );
    }

    // Predicciones especiales (solo ronda grupos)
    $specialPrediction = null;
    if ($round->slug === 'grupos') {
        $specialPrediction = \App\Models\SpecialPrediction::with(['champion', 'runnerUp', 'topScorer.team'])
            ->where('user_id', $viewedUserId)
            ->first();
    }

    // Lista de usuarios con submission (solo cuando bloqueada, para el selector)
    $usersWithSubmission = null;
    if ($round->is_locked) {
        $submittedUserIds = PredictionSubmission::where('round_id', $round->id)
            ->pluck('user_id');
        $usersWithSubmission = \App\Models\User::whereIn('id', $submittedUserIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->select(['id', 'name'])
            ->get();
    }

    $props = [
        'round'              => $round,
        'fixtures'           => $fixtures,
        'predictions'        => $predictions,
        'submission'         => $submission,
        'isFinalized'        => $round->is_locked,
        'classifiers'        => $classifiers,
        'viewedUserId'       => $viewedUserId,
        'authUserId'         => Auth::id(),
        'usersWithSubmission'=> $usersWithSubmission,
        'specialPrediction'  => $specialPrediction,
    ];

    if ($realClassifierIds !== null) {
        $props['realClassifierIds'] = $realClassifierIds;
    }

    return Inertia::render('Predictions/Receipt', $props);
}
```

- [ ] **Step 4: Correr tests — deben pasar**

```bash
./vendor/bin/sail test tests/Feature/PredictionReceiptTest.php
```

Esperado: 7 PASS

- [ ] **Step 5: Correr suite completa**

```bash
./vendor/bin/sail test
```

Esperado: todos pasan.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PredictionController.php \
        tests/Feature/PredictionReceiptTest.php
git commit -m "feat: public receipt with user selector and specials in R1"
```

---

## Task 3: Bug real-time — FixtureController no dispara LiveScoreUpdated

**Files:**
- Modify: `app/Http/Controllers/Admin/FixtureController.php`
- Test: `tests/Feature/Admin/FixtureControllerTest.php` (agregar test)

- [ ] **Step 1: Agregar test fallido en `FixtureControllerTest.php`**

Abrir `tests/Feature/Admin/FixtureControllerTest.php`. El `beforeEach` ya hace `Event::fake([LiveScoreUpdated::class, ...])`. Agregar al final del archivo:

```php
it('dispatches LiveScoreUpdated when fixture updated to in_progress with scores', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = Group::factory()->create(['name' => 'A']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'       => 'scheduled',
        'match_number' => 1,
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/fixtures/{$fixture->id}", [
            'round_id'           => $round->id,
            'match_number'       => 1,
            'match_date'         => '2026-06-11 12:00:00',
            'home_team_id'       => $home->id,
            'away_team_id'       => $away->id,
            'home_score'         => 2,
            'away_score'         => 1,
            'status'             => 'in_progress',
            'went_to_extra_time' => false,
        ]);

    Event::assertDispatched(LiveScoreUpdated::class, fn ($e) =>
        $e->matchId === $fixture->id &&
        $e->homeScore === 2 &&
        $e->awayScore === 1
    );
});

it('does not dispatch LiveScoreUpdated when fixture status is scheduled', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = Group::factory()->create(['name' => 'B']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'match_number' => 2,
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/fixtures/{$fixture->id}", [
            'round_id'           => $round->id,
            'match_number'       => 2,
            'match_date'         => '2026-06-12 15:00:00',
            'home_team_id'       => $home->id,
            'away_team_id'       => $away->id,
            'status'             => 'scheduled',
            'went_to_extra_time' => false,
        ]);

    Event::assertNotDispatched(LiveScoreUpdated::class);
});
```

- [ ] **Step 2: Correr tests — deben fallar**

```bash
./vendor/bin/sail test --filter "dispatches LiveScoreUpdated when fixture updated"
```

Esperado: FAIL — `FixtureController` no dispara el evento.

- [ ] **Step 3: Modificar `FixtureController::update()`**

En `app/Http/Controllers/Admin/FixtureController.php`, agregar el import al inicio:

```php
use App\Events\LiveScoreUpdated;
```

Reemplazar el bloque de dispatch en `update()` (las últimas líneas antes del `return`):

```php
$fixture->update($data);
$fresh = $fixture->fresh();

if ($fresh->home_score !== null && $fresh->away_score !== null) {
    MatchScoreUpdated::dispatch($fresh);

    if (in_array($fresh->status, ['in_progress', 'finished'])) {
        LiveScoreUpdated::dispatch(
            $fresh->id,
            $fresh->home_score,
            $fresh->away_score,
            $fresh->isLive(),
        );
    }
}

return redirect()->route('admin.fixtures.index', ['round_id' => $data['round_id']])
    ->with('status', "Partido #{$fixture->match_number} actualizado.");
```

- [ ] **Step 4: Correr los nuevos tests**

```bash
./vendor/bin/sail test tests/Feature/Admin/FixtureControllerTest.php
```

Esperado: todos pasan, incluyendo los 2 nuevos.

- [ ] **Step 5: Correr suite completa**

```bash
./vendor/bin/sail test
```

Esperado: todos pasan.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/FixtureController.php \
        tests/Feature/Admin/FixtureControllerTest.php
git commit -m "fix: dispatch LiveScoreUpdated from FixtureController on score update"
```

---

## Task 4: Premio 70%/20% + nota de comisión en Rules

**Files:**
- Modify: `app/Http/Controllers/RankingController.php`
- Modify: `resources/js/Components/composed/PozoCard.jsx`
- Modify: `resources/js/Pages/Rules.jsx`

No se escriben tests para este task — los cambios son de presentación y cálculo de porcentajes. El `RankingController` ya tiene test implícito (se verifica en el E2E de simulación).

- [ ] **Step 1: Actualizar `RankingController`**

En `app/Http/Controllers/RankingController.php`, reemplazar el bloque del pozo:

```php
$activated = User::where('is_activated', true)->where('role', 'user')->count();
$total     = $activated * 50000;

$fmt = fn ($n) => number_format($n / 1000, 0, '.', '.') . 'K';

return Inertia::render('Ranking', [
    'users' => $users,
    'pozo'  => [
        'total'           => $fmt($total) ?: '0K',
        'players'         => $activated,
        'amountPerPlayer' => $fmt(50000),
        'prize1'          => $fmt((int) ($total * 0.70)),
        'prize2'          => $fmt((int) ($total * 0.20)),
    ],
]);
```

- [ ] **Step 2: Actualizar `PozoCard.jsx`**

En `resources/js/Components/composed/PozoCard.jsx`, reemplazar el componente completo:

```jsx
import Cromo from '@/Components/ui/Cromo';
import { Trophy } from '@/Components/icons/football';

function PrizeSlot({ place, pct, amount, color }) {
    return (
        <div className="bg-black/35 border-2 border-ink p-[6px_8px]">
            <div className="flex items-center gap-1.5">
                <span className="font-display text-[18px]" style={{ color }}>{place}</span>
                <span className="font-mono text-[9px] opacity-70 tracking-[.08em]">{pct}</span>
            </div>
            <div className="font-mono font-bold text-[14px] mt-0.5" style={{ color }}>{amount}</div>
        </div>
    );
}

export default function PozoCard({ total, players, amountPerPlayer, prize1, prize2 }) {
    return (
        <Cromo className="bg-navy text-cream p-[10px_12px]">
            <div className="halftone halftone-yel absolute inset-0 opacity-35" />
            <div className="absolute right-[-6px] bottom-[-10px] -rotate-[8deg] opacity-95">
                <Trophy size={62} color="var(--c-yel)" />
            </div>
            <div className="relative">
                <div className="font-mono text-[9px] text-pop-yel tracking-[.12em]">POZO TOTAL</div>
                <div className="font-display text-[30px] leading-none mt-0.5 text-pop-yel">{total}</div>
                <div className="font-mono text-[10px] opacity-75 mt-0.5">
                    {players} jugadores · {amountPerPlayer} c/u
                </div>
            </div>
            <div className="grid grid-cols-2 gap-1.5 mt-2.5 relative">
                <PrizeSlot place="1°" pct="70%" amount={prize1} color="var(--c-yel)" />
                <PrizeSlot place="2°" pct="20%" amount={prize2} color="var(--c-cream)" />
            </div>
        </Cromo>
    );
}
```

- [ ] **Step 3: Actualizar `Rules.jsx` — Rule n="5"**

En `resources/js/Pages/Rules.jsx`, reemplazar el bloque de Rule n="5":

```jsx
<Rule n="5" title="PREMIOS">
    El pozo se forma con los 50K de cada parche que entra. Se reparte así al final:
    <RuleList items={[
        '1° lugar · 70% del pozo',
        '2° lugar · 20% del pozo',
    ]} />
    <span className="inline-block mt-1.5 font-mono text-[10px] opacity-70 leading-[1.5]">
        El 10% restante cubre los costos de operación de la plataforma.
        El admin coordina el pago dentro de los 7 días siguientes a la final.
    </span>
</Rule>
```

- [ ] **Step 4: Build frontend para verificar sin errores**

```bash
./vendor/bin/sail pnpm run build
```

Esperado: build exitoso sin errores.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/RankingController.php \
        resources/js/Components/composed/PozoCard.jsx \
        resources/js/Pages/Rules.jsx
git commit -m "feat: update prize distribution to 70/20 and add commission note in rules"
```

---

## Task 5: Receipt.jsx — selector de usuario + sección especiales

**Files:**
- Modify: `resources/js/Pages/Predictions/Receipt.jsx`

- [ ] **Step 1: Reemplazar `Receipt.jsx` completo**

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import MobileShell from '@/Components/MobileShell';
import ReceiptMatchRow from '@/Components/composed/ReceiptMatchRow';
import PtsChip from '@/Components/ui/PtsChip';

export default function Receipt({
    round,
    fixtures,
    predictions,
    submission,
    isFinalized,
    classifiers,
    realClassifierIds,
    viewedUserId,
    authUserId,
    usersWithSubmission,
    specialPrediction,
}) {
    const ptsExact      = Object.values(predictions).reduce((s, p) => s + (p.pts_exact  ?? 0), 0);
    const ptsResult     = Object.values(predictions).reduce((s, p) => s + (p.pts_result ?? 0), 0);
    const ptsClassifier = submission.pts_classifier ?? 0;
    const totalPts      = ptsExact + ptsResult + ptsClassifier;

    const isViewingOther = viewedUserId !== authUserId;

    function handleUserChange(e) {
        router.visit(route('predictions.receipt', { round: round.slug }), {
            data: { user_id: e.target.value },
            preserveScroll: false,
        });
    }

    return (
        <MobileShell>
            <Head title={`Comprobante · ${round.name}`} />

            {/* Header */}
            <div className="px-[18px] pt-3 pb-2.5 flex items-center gap-3 border-b-[3px] border-ink bg-cream sticky top-0 z-10">
                <button
                    onClick={() => router.visit(route('predictions.index'))}
                    className="w-8 h-8 border-[2.5px] border-ink flex items-center justify-center font-display text-[14px] flex-shrink-0"
                    style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                >
                    ←
                </button>
                <div className="flex-1 min-w-0">
                    <div className="font-mono text-[9px] opacity-50 tracking-[.06em]">COMPROBANTE</div>
                    <div className="font-display text-[18px] leading-tight truncate">{round.name.toUpperCase()}</div>
                </div>
                {isFinalized && (
                    <span
                        className="flex-shrink-0 bg-pop-teal text-ink border-[2px] border-ink px-2 py-0.5 font-mono text-[8px] font-bold tracking-[.06em]"
                        style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                    >
                        BLOQUEADA
                    </span>
                )}
            </div>

            {/* Selector de usuario — solo cuando la fase está bloqueada */}
            {usersWithSubmission && usersWithSubmission.length > 1 && (
                <div className="px-[18px] py-2.5 border-b-[2px] border-ink bg-white flex items-center gap-2.5">
                    <span className="font-mono text-[9px] opacity-50 flex-shrink-0 tracking-[.06em]">VER:</span>
                    <div className="relative flex-1">
                        <select
                            value={viewedUserId}
                            onChange={handleUserChange}
                            className="w-full border-[2px] border-ink bg-cream font-mono text-[11px] px-2.5 py-1.5 appearance-none pr-8"
                            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                        >
                            {usersWithSubmission.map(u => (
                                <option key={u.id} value={u.id}>
                                    {u.id === authUserId ? `${u.name} (tú)` : u.name}
                                </option>
                            ))}
                        </select>
                        <div className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 font-display text-[10px]">▼</div>
                    </div>
                    {isViewingOther && (
                        <button
                            onClick={() => router.visit(route('predictions.receipt', { round: round.slug }), {
                                data: { user_id: authUserId },
                                preserveScroll: false,
                            })}
                            className="flex-shrink-0 font-mono text-[9px] underline opacity-60"
                        >
                            mis goles
                        </button>
                    )}
                </div>
            )}

            {/* Banner de puntos o aviso */}
            {isFinalized ? (
                <div className="bg-navy text-cream px-[18px] py-3 flex items-center justify-between border-b-[3px] border-ink">
                    <div className="flex flex-col gap-1 font-mono text-[10px]">
                        {ptsExact      > 0 && <span>EXACTO   <b className="text-pop-red">  +{ptsExact}</b></span>}
                        {ptsResult     > 0 && <span>RESULTADO <b className="text-pop-teal">+{ptsResult}</b></span>}
                        {ptsClassifier > 0 && <span>CLASIF    <b className="text-pop-yel"> +{ptsClassifier}</b></span>}
                        {totalPts === 0    && <span className="opacity-50">Sin puntos esta fase</span>}
                    </div>
                    <div
                        className="bg-pop-yel text-ink border-[2.5px] border-ink px-3 py-1.5 font-display text-[26px] leading-none flex-shrink-0"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        +{totalPts}
                    </div>
                </div>
            ) : (
                <div className="bg-pop-yel text-ink border-b-[3px] border-ink px-[18px] py-2.5 flex items-center gap-2.5">
                    <span className="text-[18px]">⏳</span>
                    <span className="font-mono text-[10px] leading-[1.4]">
                        Los puntos se calculan cuando finalicen los partidos
                    </span>
                </div>
            )}

            {/* Lista de partidos */}
            <div className="overflow-y-auto">
                {fixtures.map(fixture => (
                    <ReceiptMatchRow
                        key={fixture.id}
                        fixture={fixture}
                        prediction={predictions[fixture.id] ?? null}
                        isFinalized={isFinalized}
                    />
                ))}

                {/* Bloque de clasificados */}
                {classifiers && classifiers.length > 0 && (() => {
                    const realIds = new Set(realClassifierIds ?? []);

                    const byGroup = {};
                    classifiers.forEach(c => {
                        if (!byGroup[c.group]) byGroup[c.group] = [];
                        byGroup[c.group].push(c);
                    });
                    const bestThirds = classifiers.filter(c => c.position === 3);

                    const hitCount = isFinalized
                        ? classifiers.filter(c => realIds.has(c.team_id)).length
                        : null;

                    return (
                        <div className="mx-[18px] my-3 border-[2.5px] border-ink overflow-hidden"
                             style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                            <div className="bg-navy text-cream px-3.5 py-2.5 flex items-center justify-between">
                                <div>
                                    <div className="font-mono text-[8px] tracking-[.1em] opacity-70">FASE DE GRUPOS</div>
                                    <div className="font-display text-[15px] leading-none mt-0.5">TUS 32 CLASIFICADOS</div>
                                </div>
                                {isFinalized && (
                                    <div className="font-mono text-[8px] opacity-70 text-right leading-[1.4]">
                                        <div className="text-pop-teal font-bold">{hitCount} acertados</div>
                                        <div className="opacity-60">de {classifiers.length}</div>
                                    </div>
                                )}
                            </div>
                            <div className="bg-white px-3 pt-2 pb-1">
                                <div className="grid grid-cols-2 gap-x-3 gap-y-0.5 mb-2">
                                    {Object.entries(byGroup)
                                        .sort(([a], [b]) => a.localeCompare(b))
                                        .flatMap(([groupName, entries]) =>
                                            entries
                                                .filter(c => c.position <= 2)
                                                .sort((a, b) => a.position - b.position)
                                                .map(c => {
                                                    const hit = isFinalized ? realIds.has(c.team_id) : null;
                                                    return (
                                                        <div key={c.team_id}
                                                             className={[
                                                                 'flex items-center gap-1.5 py-1 border-b border-dashed border-black/10',
                                                                 hit === false ? 'opacity-40' : '',
                                                             ].join(' ')}>
                                                            {isFinalized && (
                                                                <span className={`font-mono text-[10px] font-bold w-3.5 flex-shrink-0 ${hit ? 'text-pop-teal' : 'text-pop-red'}`}>
                                                                    {hit ? '✓' : '✗'}
                                                                </span>
                                                            )}
                                                            {c.flag_url && <img src={c.flag_url} alt="" className="h-3 w-4 object-cover flex-shrink-0" />}
                                                            <span className="font-display text-[10px] truncate leading-none">{(c.team_name ?? '?').toUpperCase()}</span>
                                                            <span className="font-mono text-[8px] opacity-40 ml-auto flex-shrink-0">{c.group}{c.position}°</span>
                                                        </div>
                                                    );
                                                })
                                        )
                                    }
                                </div>
                                {bestThirds.length > 0 && (
                                    <div className="border-t-[2px] border-dashed border-ink/20 pt-2 pb-2">
                                        <div className="font-mono text-[8px] opacity-50 mb-1.5 tracking-[.06em]">8 MEJORES TERCEROS</div>
                                        <div className="flex flex-wrap gap-1">
                                            {bestThirds.map(c => {
                                                const hit = isFinalized ? realIds.has(c.team_id) : null;
                                                return (
                                                    <div key={c.team_id}
                                                         className={[
                                                             'flex items-center gap-1 border border-ink/20 px-1.5 py-0.5',
                                                             hit === true  ? 'bg-pop-teal/15' :
                                                             hit === false ? 'bg-pop-red/15 opacity-40' : 'bg-black/5',
                                                         ].join(' ')}>
                                                        {isFinalized && (
                                                            <span className={`font-mono text-[9px] font-bold ${hit ? 'text-pop-teal' : 'text-pop-red'}`}>
                                                                {hit ? '✓' : '✗'}
                                                            </span>
                                                        )}
                                                        {c.flag_url && <img src={c.flag_url} alt="" className="h-2.5 w-3.5 object-cover" />}
                                                        <span className="font-display text-[9px]">{(c.team_name ?? '?').toUpperCase()}</span>
                                                        <span className="font-mono text-[7px] opacity-40">({c.group})</span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })()}

                {/* Sección de predicciones especiales — solo en R1 */}
                {specialPrediction && (
                    <div className="mx-[18px] my-3 border-[2.5px] border-ink overflow-hidden"
                         style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                        <div className="bg-navy text-cream px-3.5 py-2.5">
                            <div className="font-mono text-[8px] tracking-[.1em] opacity-70">ANTES DEL TORNEO</div>
                            <div className="font-display text-[15px] leading-none mt-0.5">PREDICCIONES ESPECIALES</div>
                        </div>
                        <div className="bg-white divide-y divide-dashed divide-ink/10">
                            {/* Campeón */}
                            <div className="flex items-center justify-between px-3.5 py-2.5">
                                <div className="flex items-center gap-2">
                                    {specialPrediction.champion?.flag_url && (
                                        <img src={specialPrediction.champion.flag_url} alt="" className="h-4 w-6 object-cover border border-ink/20" />
                                    )}
                                    <div>
                                        <div className="font-mono text-[8px] opacity-50 tracking-[.06em]">CAMPEÓN</div>
                                        <div className="font-display text-[13px] leading-tight">
                                            {specialPrediction.champion?.name ?? '—'}
                                        </div>
                                    </div>
                                </div>
                                {isFinalized && specialPrediction.pts_champion !== null && (
                                    <PtsChip pts={specialPrediction.pts_champion} type="exact" />
                                )}
                            </div>
                            {/* Sub-campeón */}
                            <div className="flex items-center justify-between px-3.5 py-2.5">
                                <div className="flex items-center gap-2">
                                    {specialPrediction.runner_up?.flag_url && (
                                        <img src={specialPrediction.runner_up.flag_url} alt="" className="h-4 w-6 object-cover border border-ink/20" />
                                    )}
                                    <div>
                                        <div className="font-mono text-[8px] opacity-50 tracking-[.06em]">SUB-CAMPEÓN</div>
                                        <div className="font-display text-[13px] leading-tight">
                                            {specialPrediction.runner_up?.name ?? '—'}
                                        </div>
                                    </div>
                                </div>
                                {isFinalized && specialPrediction.pts_runner_up !== null && (
                                    <PtsChip pts={specialPrediction.pts_runner_up} type="result" />
                                )}
                            </div>
                            {/* Goleador */}
                            <div className="flex items-center justify-between px-3.5 py-2.5">
                                <div>
                                    <div className="font-mono text-[8px] opacity-50 tracking-[.06em]">GOLEADOR</div>
                                    <div className="font-display text-[13px] leading-tight">
                                        {specialPrediction.top_scorer?.name ?? '—'}
                                    </div>
                                    {specialPrediction.top_scorer?.team?.name && (
                                        <div className="font-mono text-[9px] opacity-50">
                                            {specialPrediction.top_scorer.team.name}
                                        </div>
                                    )}
                                </div>
                                {isFinalized && specialPrediction.pts_top_scorer !== null && (
                                    <PtsChip pts={specialPrediction.pts_top_scorer} type="classifier" />
                                )}
                            </div>
                        </div>
                    </div>
                )}

                <div className="pb-10" />
            </div>
        </MobileShell>
    );
}
```

- [ ] **Step 2: Build frontend**

```bash
./vendor/bin/sail pnpm run build
```

Esperado: sin errores.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Predictions/Receipt.jsx
git commit -m "feat: receipt public user selector and specials section for R1"
```

---

## Task 6: Matches.jsx — listener real-time de marcadores

**Files:**
- Modify: `resources/js/Pages/Matches.jsx`

- [ ] **Step 1: Agregar useState y Echo listener**

En `resources/js/Pages/Matches.jsx`, aplicar estos cambios:

**Cambio 1** — agregar `useEffect` al import:
```js
import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
```

**Cambio 2** — en el componente `Matches`, convertir `matchDays` de prop a estado y agregar el listener:

```js
export default function Matches({ matchDays: initialMatchDays, groups, currentRound }) {
    const today = new Date().toISOString().split('T')[0];

    const [matchDays, setMatchDays] = useState(initialMatchDays);

    const defaultDate = matchDays.find(d => d.dateKey === today)?.dateKey
        ?? matchDays[0]?.dateKey
        ?? null;

    const [view, setView]                 = useState('calendar');
    const [selectedDate, setSelectedDate] = useState(defaultDate);

    useEffect(() => {
        const channel = window.Echo.join('quinela');
        channel.listen('.LiveScoreUpdated', (event) => {
            setMatchDays(prev => prev.map(day => ({
                ...day,
                matches: day.matches.map(m =>
                    m.id === event.match_id
                        ? {
                            ...m,
                            home_score: event.home_score,
                            away_score: event.away_score,
                            status: event.is_live ? 'in_progress' : m.status,
                          }
                        : m
                ),
            })));
        });
        return () => { window.Echo.leave('quinela'); };
    }, []);

    const visibleDays = selectedDate
        ? matchDays.filter(d => d.dateKey === selectedDate)
        : matchDays;

    // ... resto del componente sin cambios
```

- [ ] **Step 2: Build y verificar**

```bash
./vendor/bin/sail pnpm run build
```

Esperado: sin errores.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Matches.jsx
git commit -m "fix: subscribe to LiveScoreUpdated in Matches for real-time score updates"
```

---

## Task 7: Suite de simulación Capa 1 — TournamentFlowTest

**Files:**
- Create: `tests/Feature/Simulation/TournamentFlowTest.php`
- Create: `database/seeders/SimulationSeeder.php`

- [ ] **Step 1: Crear `SimulationSeeder`**

Crear `database/seeders/SimulationSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Fixture;
use App\Models\Player;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SimulationSeeder extends Seeder
{
    /**
     * Siembra usuarios de prueba con credenciales conocidas para
     * la simulación Layer 2 (sub-agentes HTTP).
     * Rondas y fixtures los crea el admin a través de la app.
     */
    public function run(): void
    {
        // Admin de simulación
        User::firstOrCreate(
            ['email' => 'admin@sim.test'],
            [
                'name'     => 'Admin Sim',
                'password' => Hash::make('simpassword'),
                'role'     => 'admin',
                'is_active'=> true,
            ]
        );

        // Jugadores de simulación
        $players = [
            ['name' => 'Alice Sim',   'email' => 'alice@sim.test'],
            ['name' => 'Bob Sim',     'email' => 'bob@sim.test'],
            ['name' => 'Carlos Sim',  'email' => 'carlos@sim.test'],
            ['name' => 'Diana Sim',   'email' => 'diana@sim.test'],
            ['name' => 'Ernesto Sim', 'email' => 'ernesto@sim.test'],
        ];

        foreach ($players as $p) {
            User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name'         => $p['name'],
                    'password'     => Hash::make('simpassword'),
                    'role'         => 'user',
                    'is_active'    => true,
                    'is_activated' => true,
                    'coins_balance'=> 0,
                ]
            );
        }
    }
}
```

- [ ] **Step 2: Registrar SimulationSeeder en DatabaseSeeder**

En `database/seeders/DatabaseSeeder.php`, agregar al final del método `run()`:

```php
if (app()->environment('local', 'testing')) {
    $this->call(SimulationSeeder::class);
}
```

- [ ] **Step 3: Crear `TournamentFlowTest.php`**

Crear `tests/Feature/Simulation/TournamentFlowTest.php`:

```php
<?php

use App\Events\MatchScoreUpdated;
use App\Events\RoundFinalized;
use App\Events\RoundLocked;
use App\Events\RoundOpened;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeSimUsers(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $user1 = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $user2 = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $user3 = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    return compact('admin', 'user1', 'user2', 'user3');
}

function makeR1WithFixtures(int $count = 2): array
{
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => false]);
    $group = Group::factory()->create(['name' => 'A']);
    $fixtures = [];
    for ($i = 1; $i <= $count; $i++) {
        $home = Team::factory()->create(['group_id' => $group->id]);
        $away = Team::factory()->create(['group_id' => $group->id]);
        $fixtures[] = Fixture::factory()->create([
            'round_id'     => $round->id,
            'group_id'     => $group->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'match_number' => $i,
            'match_date'   => now()->addDays($i),
        ]);
    }
    return compact('round', 'fixtures', 'group');
}

// ── Apertura de ronda ─────────────────────────────────────────────────────────

it('users cannot predict before round is open', function () {
    ['admin' => $admin, 'user1' => $user1] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeR1WithFixtures(1);

    $this->actingAs($user1)
        ->post(route('predictions.save', $round), [
            'predictions' => [$fixtures[0]->id => ['predicted_home' => 2, 'predicted_away' => 1]],
        ])
        ->assertSessionHas('status');

    expect(Prediction::count())->toBe(0);
});

it('admin can open a round and users can then predict', function () {
    Event::fake([RoundOpened::class]);
    ['admin' => $admin, 'user1' => $user1] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeR1WithFixtures(1);

    // Admin abre la ronda
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    expect($round->fresh()->is_open)->toBeTrue();
    Event::assertDispatched(RoundOpened::class);

    // user1 puede predecir
    $this->actingAs($user1)
        ->post(route('predictions.save', $round), [
            'predictions' => [$fixtures[0]->id => ['predicted_home' => 2, 'predicted_away' => 1]],
        ]);

    expect(Prediction::where('user_id', $user1->id)->count())->toBe(1);
});

// ── Bloqueo de ronda ──────────────────────────────────────────────────────────

it('admin can lock round and users cannot predict after lock', function () {
    Event::fake([RoundLocked::class]);
    ['admin' => $admin, 'user1' => $user1, 'user2' => $user2] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeR1WithFixtures(1);

    // Abrir y predecir
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($user1)->post(route('predictions.save', $round), [
        'predictions' => [$fixtures[0]->id => ['predicted_home' => 2, 'predicted_away' => 1]],
    ]);

    // Admin bloquea
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");
    expect($round->fresh()->is_locked)->toBeTrue();
    Event::assertDispatched(RoundLocked::class);

    // user2 intenta predecir después del bloqueo
    $this->actingAs($user2)
        ->post(route('predictions.save', $round), [
            'predictions' => [$fixtures[0]->id => ['predicted_home' => 3, 'predicted_away' => 0]],
        ])
        ->assertSessionHas('status');

    expect(Prediction::where('user_id', $user2->id)->count())->toBe(0);
});

it('locking grupos round also locks all special predictions', function () {
    ['admin' => $admin, 'user1' => $user1, 'user2' => $user2] = makeSimUsers();
    ['round' => $round] = makeR1WithFixtures(1);

    SpecialPrediction::factory()->create(['user_id' => $user1->id, 'is_locked' => false]);
    SpecialPrediction::factory()->create(['user_id' => $user2->id, 'is_locked' => false]);

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");

    expect(SpecialPrediction::where('is_locked', false)->count())->toBe(0);
});

// ── Carga de marcadores y puntos ──────────────────────────────────────────────

it('points are calculated after admin enters scores via score entry', function () {
    Event::fake([MatchScoreUpdated::class]);
    ['admin' => $admin, 'user1' => $user1, 'user2' => $user2] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeR1WithFixtures(1);
    $fixture = $fixtures[0];

    // Flujo: abrir → predecir → bloquear
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");

    $this->actingAs($user1)->post(route('predictions.save', $round), [
        'predictions' => [$fixture->id => ['predicted_home' => 2, 'predicted_away' => 1]],
    ]);
    $this->actingAs($user2)->post(route('predictions.save', $round), [
        'predictions' => [$fixture->id => ['predicted_home' => 3, 'predicted_away' => 1]],
    ]);

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");

    // Admin carga marcador: 2-1 (exacto para user1, resultado correcto para user2)
    $this->actingAs($admin)->patch(route('admin.score-entry.update', $fixture), [
        'home_score'     => 2,
        'away_score'     => 1,
        'status'         => 'finished',
        'winner_team_id' => $fixture->home_team_id,
    ]);

    Event::assertDispatched(MatchScoreUpdated::class);

    // user1 tiene pts_exact (3 pts para R1)
    $pred1 = Prediction::where('user_id', $user1->id)->where('match_id', $fixture->id)->first();
    expect($pred1->pts_exact)->toBe(3);
    expect($pred1->pts_result)->toBe(0);

    // user2 tiene pts_result (1 pt para R1)
    $pred2 = Prediction::where('user_id', $user2->id)->where('match_id', $fixture->id)->first();
    expect($pred2->pts_exact)->toBe(0);
    expect($pred2->pts_result)->toBe(1);
});

it('user total_points reflects predictions after score entry', function () {
    Event::fake([MatchScoreUpdated::class]);
    ['admin' => $admin, 'user1' => $user1] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeR1WithFixtures(1);
    $fixture = $fixtures[0];

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($user1)->post(route('predictions.save', $round), [
        'predictions' => [$fixture->id => ['predicted_home' => 2, 'predicted_away' => 1]],
    ]);
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");
    $this->actingAs($admin)->patch(route('admin.score-entry.update', $fixture), [
        'home_score'     => 2,
        'away_score'     => 1,
        'status'         => 'finished',
        'winner_team_id' => $fixture->home_team_id,
    ]);

    expect($user1->fresh()->total_points)->toBe(3);
});

// ── Finalización de ronda ─────────────────────────────────────────────────────

it('admin can finalize round after locking', function () {
    Event::fake([RoundFinalized::class, RoundLocked::class]);
    ['admin' => $admin] = makeSimUsers();
    ['round' => $round] = makeR1WithFixtures(1);

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/finalize");

    expect($round->fresh()->is_finalized)->toBeTrue();
    Event::assertDispatched(RoundFinalized::class);
});

it('admin cannot finalize an already finalized round', function () {
    ['admin' => $admin] = makeSimUsers();
    ['round' => $round] = makeR1WithFixtures(1);

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/finalize");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/finalize")
        ->assertSessionHas('status');

    expect(Round::where('id', $round->id)->where('is_finalized', true)->count())->toBe(1);
});

// ── Flujo multi-ronda ─────────────────────────────────────────────────────────

it('two consecutive rounds each score independently', function () {
    Event::fake([MatchScoreUpdated::class, RoundFinalized::class, RoundLocked::class]);
    ['admin' => $admin, 'user1' => $user1] = makeSimUsers();

    // R1
    $r1    = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => false]);
    $group = Group::factory()->create(['name' => 'A']);
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);
    $f1    = Fixture::factory()->create([
        'round_id' => $r1->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id, 'match_number' => 1,
    ]);

    // R2
    $r2 = Round::factory()->f2()->create(['is_open' => false, 'is_locked' => false]);
    $f2 = Fixture::factory()->create([
        'round_id' => $r2->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id, 'match_number' => 73,
    ]);

    // R1 flow
    $this->actingAs($admin)->post("/admin/rounds/{$r1->slug}/open");
    $this->actingAs($user1)->post(route('predictions.save', $r1), [
        'predictions' => [$f1->id => ['predicted_home' => 1, 'predicted_away' => 0]],
    ]);
    $this->actingAs($admin)->post("/admin/rounds/{$r1->slug}/lock");
    $this->actingAs($admin)->post(route('admin.score-entry.update', $f1), [
        'home_score' => 1, 'away_score' => 0, 'status' => 'finished',
        'winner_team_id' => $f1->home_team_id,
    ]);
    $this->actingAs($admin)->post("/admin/rounds/{$r1->slug}/finalize");

    // R2 flow
    $this->actingAs($admin)->post("/admin/rounds/{$r2->slug}/open");
    $this->actingAs($user1)->post(route('predictions.save', $r2), [
        'predictions' => [$f2->id => ['predicted_home' => 2, 'predicted_away' => 0]],
    ]);
    $this->actingAs($admin)->post("/admin/rounds/{$r2->slug}/lock");
    $this->actingAs($admin)->post(route('admin.score-entry.update', $f2), [
        'home_score' => 2, 'away_score' => 0, 'status' => 'finished',
        'winner_team_id' => $f2->home_team_id,
    ]);

    // R2 pts_exact = 5 (por ser R2), R1 pts_exact = 3 (por ser R1)
    $predR1 = Prediction::where('user_id', $user1->id)->where('match_id', $f1->id)->first();
    $predR2 = Prediction::where('user_id', $user1->id)->where('match_id', $f2->id)->first();

    expect($predR1->pts_exact)->toBe(3);
    expect($predR2->pts_exact)->toBe(5);
    expect($user1->fresh()->total_points)->toBe(8);
});
```

- [ ] **Step 4: Correr los tests de simulación**

```bash
./vendor/bin/sail test tests/Feature/Simulation/TournamentFlowTest.php
```

Esperado: todos pasan.

- [ ] **Step 5: Correr suite completa**

```bash
./vendor/bin/sail test
```

Esperado: todos pasan.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Simulation/TournamentFlowTest.php \
        database/seeders/SimulationSeeder.php \
        database/seeders/DatabaseSeeder.php
git commit -m "test: add tournament flow simulation tests and SimulationSeeder"
```

---

## Task 8: Suite de simulación Capa 2 — Sub-agentes HTTP

**Files:**
- Create: `docs/superpowers/simulations/run-simulation.md`

- [ ] **Step 1: Crear el documento de simulación**

Crear `docs/superpowers/simulations/run-simulation.md`:

````markdown
# Simulación Layer 2 — Sub-agentes HTTP contra servidor real

## Prerequisitos

```bash
# 1. Contenedores corriendo
./vendor/bin/sail up -d

# 2. Base de datos fresca con datos de simulación
./vendor/bin/sail artisan migrate:fresh --seed

# 3. Vite dev server (opcional, para verificar UI)
./vendor/bin/sail pnpm run dev
```

## Credenciales de sub-agentes

| Agente | Email | Password | Rol |
|--------|-------|----------|-----|
| admin-agent | admin@sim.test | simpassword | admin |
| user-alice | alice@sim.test | simpassword | user |
| user-bob | bob@sim.test | simpassword | user |
| user-carlos | carlos@sim.test | simpassword | user |
| user-diana | diana@sim.test | simpassword | user |
| observer | alice@sim.test | simpassword | user (solo GET) |

## Flujo cronológico de la simulación

```
[T+0]  admin-agent:  GET /admin/rounds → verificar rondas existentes
[T+0]  admin-agent:  POST /admin/rounds/{grupos}/open
[T+0]  user-alice:   POST /predictions/grupos/save (predice 48 partidos)
[T+0]  user-bob:     POST /predictions/grupos/save (predice partidos)
[T+0]  user-carlos:  POST /predictions/grupos/save (predice partidos)
[T+0]  admin-agent:  POST /predictions/special/save (predicciones especiales del admin — no aplica)
[T+0]  user-alice:   POST /predictions/special/save (campeón + sub + goleador)
[T+1]  admin-agent:  POST /admin/rounds/{grupos}/lock
[T+1]  user-diana:   intenta POST /predictions/grupos/save → debe ser rechazado
[T+1]  user-alice:   intenta POST /predictions/special/save → debe ser rechazado
[T+2]  admin-agent:  POST /admin/score-entry/{fixture_id} × N partidos
[T+2]  observer:     GET /ranking → verificar puntos actualizados en tiempo real
[T+3]  admin-agent:  POST /admin/rounds/{grupos}/finalize
[T+3]  observer:     GET /predictions/{grupos}/receipt → verificar puntos de clasificados
[T+4]  admin-agent:  POST /admin/rounds/{r32}/open
[T+4]  user-alice:   POST /predictions/r32/save
[T+5]  admin-agent:  POST /admin/rounds/{r32}/lock
[T+5]  admin-agent:  POST /admin/score-entry/{fixture_id} × partidos R2
[T+5]  admin-agent:  POST /admin/rounds/{r32}/finalize
[T+5]  observer:     GET /ranking → verificar ranking final R1+R2
```

## Cómo lanzar la simulación con sub-agentes Claude

Desde Claude Code, con el servidor corriendo, usar `superpowers:dispatching-parallel-agents`.

### Instrucción para el admin-agent

```
Eres el admin del torneo PollaMundial corriendo en http://localhost.
Credenciales: admin@sim.test / simpassword
URL base: http://localhost

Pasos a ejecutar con curl:

1. Obtener CSRF token y hacer login:
   TOKEN=$(curl -s -c /tmp/admin-cookies.txt http://localhost/login | grep -oP '(?<="_token" value=")[^"]+')
   curl -s -c /tmp/admin-cookies.txt -b /tmp/admin-cookies.txt \
     -X POST http://localhost/login \
     -d "email=admin@sim.test&password=simpassword&_token=$TOKEN" \
     -L

2. Abrir ronda grupos:
   XSRF=$(cat /tmp/admin-cookies.txt | grep XSRF | awk '{print $7}' | python3 -c "import sys,urllib.parse; print(urllib.parse.unquote(sys.stdin.read().strip()))")
   curl -s -b /tmp/admin-cookies.txt \
     -X POST http://localhost/admin/rounds/grupos/open \
     -H "X-XSRF-TOKEN: $XSRF" \
     -H "Accept: application/json"

3. Verificar que la ronda quedó abierta:
   curl -s -b /tmp/admin-cookies.txt http://localhost/admin/rounds \
     -H "Accept: application/json"

[Continúa con el resto del flujo cronológico...]

Reporta en cada paso: qué hiciste, el status code de la respuesta, y si el resultado es el esperado.
```

### Instrucción para user-alice

```
Eres el usuario Alice en PollaMundial corriendo en http://localhost.
Credenciales: alice@sim.test / simpassword
URL base: http://localhost

Espera a que el admin-agent confirme que la ronda grupos está abierta antes de predecir.

Pasos:
1. Login y guardar cookie en /tmp/alice-cookies.txt
2. GET /predictions/grupos → obtener fixture IDs disponibles
3. POST /predictions/grupos/save con predicciones para todos los fixtures visibles
4. POST /predictions/special/save con:
   - champion_team_id: (primer equipo que encuentres)
   - runner_up_team_id: (segundo equipo diferente)
   - top_scorer_player_id: (primer jugador que encuentres)
5. Verificar que tus predicciones quedaron guardadas
6. Cuando el admin bloquee la ronda, intenta modificar una predicción y reporta si fue rechazado

Reporta cada acción y su resultado.
```

## Notas de implementación

- Los cookies de XSRF-TOKEN de Laravel usan URL-encoding; necesitan decodificarse antes de usar como header
- Si el servidor responde 419 (CSRF mismatch), re-obtener el token y reintentar
- Las respuestas Inertia retornan JSON cuando el header `X-Inertia: true` está presente; útil para parsear props
- El observer-agent puede usar `curl -H "X-Inertia: true" -H "X-Inertia-Version: 1"` para obtener props JSON

## Verificaciones del observer-agent

```bash
# Ver ranking actual (requiere login)
curl -s -b /tmp/alice-cookies.txt \
  -H "X-Inertia: true" \
  -H "X-Inertia-Version: 1" \
  http://localhost/ranking | python3 -m json.tool | grep -A5 '"users"'

# Ver comprobante propio
curl -s -b /tmp/alice-cookies.txt \
  -H "X-Inertia: true" \
  -H "X-Inertia-Version: 1" \
  "http://localhost/predictions/grupos/receipt" | python3 -m json.tool
```
````

- [ ] **Step 2: Commit**

```bash
git add docs/superpowers/simulations/run-simulation.md
git commit -m "docs: add Layer 2 simulation runner with sub-agent instructions"
```

---

## Verificación final

- [ ] **Correr suite completa una última vez**

```bash
./vendor/bin/sail test
```

Esperado: todos los tests pasan (número anterior + nuevos de esta sesión).

- [ ] **Verificar build de producción**

```bash
./vendor/bin/sail pnpm run build
```

Esperado: sin errores ni warnings críticos.
