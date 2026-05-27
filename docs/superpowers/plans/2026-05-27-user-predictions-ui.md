# User Predictions UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reemplazar el Predictions/Index genérico con un Phases Index pop-art, agregar una pantalla de comprobante por fase, y corregir los valores de puntuación hardcodeados en HowTo, Rules y Round.

**Architecture:** Backend extiende `PredictionController` con datos de puntos por fase (`phasePts`) en `index()` y una nueva acción `receipt()`. Frontend construye componentes nuevos (`PhaseCard`, `TournamentProgress`, `ReceiptMatchRow`, `PtsChip`) ensamblados en dos páginas Inertia usando `MobileShell`. Las pantallas de info (`HowTo`, `Rules`) y el editor de predicciones (`Round`) se corrigen para usar el scoring progresivo real.

**Tech Stack:** Laravel 11 · Inertia.js v2 · React 18 · Pest v3 · pnpm · Laravel Sail

---

## File Map

**Crear:**
- `resources/js/Components/ui/PtsChip.jsx`
- `resources/js/Components/composed/TournamentProgress.jsx`
- `resources/js/Components/composed/PhaseCard.jsx`
- `resources/js/Components/composed/ReceiptMatchRow.jsx`
- `resources/js/Pages/Predictions/Receipt.jsx`

**Modificar:**
- `routes/web.php` — agregar ruta receipt
- `app/Http/Controllers/PredictionController.php` — index() + receipt()
- `tests/Feature/PredictionControllerTest.php` — tests nuevos
- `resources/js/Pages/Predictions/Index.jsx` — reescribir completo
- `resources/js/Pages/Predictions/Round.jsx` — corregir 3 valores hardcodeados
- `resources/js/Pages/HowTo.jsx` — reemplazar sección scoring
- `resources/js/Pages/Rules.jsx` — corregir texto de scoring

---

## Task 1: Ruta receipt + stub del controller

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/PredictionController.php`

- [ ] **Agregar la ruta receipt en `routes/web.php`** (antes de `/{round}` para evitar ambigüedad):

```php
// Dentro del grupo predictions, ANTES de Route::get('/{round}', ...)
Route::get('/{round}/receipt', [PredictionController::class, 'receipt'])->name('receipt');
```

El bloque completo del grupo queda:
```php
Route::middleware(['auth'])->prefix('predictions')->name('predictions.')->group(function () {
    Route::get('/', [PredictionController::class, 'index'])->name('index');
    Route::get('/special', [SpecialPredictionController::class, 'show'])->name('special');
    Route::post('/special', [SpecialPredictionController::class, 'save'])->name('special.save');
    Route::get('/{round}/receipt', [PredictionController::class, 'receipt'])->name('receipt');
    Route::get('/{round}', [PredictionController::class, 'show'])->name('show');
    Route::post('/{round}/save', [PredictionController::class, 'save'])->name('save');
    Route::post('/{round}/submit', [PredictionController::class, 'submit'])->name('submit');
});
```

- [ ] **Agregar import de `Fixture` al controller** y el stub de `receipt()`:

En `app/Http/Controllers/PredictionController.php`, agregar al bloque de imports:
```php
use App\Models\Fixture;
use Illuminate\Support\Collection;
```

Agregar el método stub al final de la clase (antes del cierre `}`):
```php
public function receipt(Round $round): Response|RedirectResponse
{
    // TODO: implementar en Task 4
    return redirect()->route('predictions.index');
}
```

- [ ] **Verificar que las rutas no colisionan:**
```bash
./vendor/bin/sail artisan route:list --name=predictions
```
Esperado: aparecen `predictions.receipt` y `predictions.show` como rutas separadas.

- [ ] **Commit:**
```bash
git add routes/web.php app/Http/Controllers/PredictionController.php
git commit -m "feat: add predictions receipt route and controller stub"
```

---

## Task 2: Tests del controller (index phasePts + receipt)

**Files:**
- Modify: `tests/Feature/PredictionControllerTest.php`

- [ ] **Agregar los tests al final de `PredictionControllerTest.php`:**

```php
// ── phasePts en index ─────────────────────────────────────────────────────

it('index includes phasePts with zeros for rounds with no predictions', function () {
    $round = Round::factory()->r1()->create();

    $this->actingAs($this->user)
        ->get(route('predictions.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Predictions/Index')
            ->has('phasePts')
            ->where("phasePts.{$round->id}.pts_exact", 0)
            ->where("phasePts.{$round->id}.pts_result", 0)
            ->where("phasePts.{$round->id}.pts_classifier", 0)
            ->where("phasePts.{$round->id}.total", 0)
            ->where("phasePts.{$round->id}.prediction_count", 0)
        );
});

it('index phasePts sums pts_exact and pts_result from predictions', function () {
    $round   = Round::factory()->r1()->create(['points_classifier' => 2]);
    $group   = \App\Models\Group::factory()->create();
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
    ]);

    \App\Models\Prediction::factory()->create([
        'user_id'      => $this->user->id,
        'match_id'     => $fixture->id,
        'pts_exact'    => 3,
        'pts_result'   => 1,
        'total_points' => 4,
    ]);

    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id'        => $this->user->id,
        'round_id'       => $round->id,
        'pts_classifier' => 6,
    ]);

    $this->actingAs($this->user)
        ->get(route('predictions.index'))
        ->assertInertia(fn ($page) => $page
            ->where("phasePts.{$round->id}.pts_exact", 3)
            ->where("phasePts.{$round->id}.pts_result", 1)
            ->where("phasePts.{$round->id}.pts_classifier", 6)
            ->where("phasePts.{$round->id}.total", 10)
            ->where("phasePts.{$round->id}.prediction_count", 1)
        );
});

it('index includes fixtures_count on each round', function () {
    $round = Round::factory()->r1()->create();
    $group = \App\Models\Group::factory()->create();
    \App\Models\Fixture::factory(3)->create(['round_id' => $round->id, 'group_id' => $group->id]);

    $this->actingAs($this->user)
        ->get(route('predictions.index'))
        ->assertInertia(fn ($page) => $page
            ->where("rounds.0.fixtures_count", 3)
        );
});

// ── receipt ───────────────────────────────────────────────────────────────

it('receipt renders when submission exists', function () {
    $round = Round::factory()->r1()->create(['is_open' => true]);
    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertInertia(fn ($page) => $page->component('Predictions/Receipt'));
});

it('receipt redirects to index when no submission exists', function () {
    $round = Round::factory()->r1()->create();

    $this->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertRedirect(route('predictions.index'));
});

it('receipt sets isFinalized true when round is locked', function () {
    $round = Round::factory()->r1()->create(['is_locked' => true, 'is_open' => false]);
    \App\Models\PredictionSubmission::factory()->locked()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertInertia(fn ($page) => $page->where('isFinalized', true));
});

it('receipt sets isFinalized false when round is not locked', function () {
    $round = Round::factory()->r1()->create(['is_open' => true, 'is_locked' => false]);
    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertInertia(fn ($page) => $page->where('isFinalized', false));
});

it('receipt includes fixtures and user predictions keyed by match_id', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create();
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
    ]);

    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    \App\Models\Prediction::factory()->create([
        'user_id'        => $this->user->id,
        'match_id'       => $fixture->id,
        'predicted_home' => 2,
        'predicted_away' => 1,
    ]);

    $this->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertInertia(fn ($page) => $page
            ->has('fixtures', 1)
            ->has("predictions.{$fixture->id}")
            ->where("predictions.{$fixture->id}.predicted_home", 2)
            ->where("predictions.{$fixture->id}.predicted_away", 1)
        );
});
```

- [ ] **Correr los tests — deben fallar:**
```bash
./vendor/bin/sail test --filter PredictionController 2>&1 | grep -E "FAIL|PASS|✓|⨯"
```
Esperado: los 6 tests nuevos fallan (phasePts no existe, receipt redirige siempre).

---

## Task 3: Implementar `phasePts` en `PredictionController@index`

**Files:**
- Modify: `app/Http/Controllers/PredictionController.php`

- [ ] **Reemplazar el método `index()` y agregar `buildPhasePts()` privado:**

```php
public function index(): Response
{
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
```

- [ ] **Correr los tests de index:**
```bash
./vendor/bin/sail test --filter "index includes phasePts|index phasePts sums|index includes fixtures_count" 2>&1
```
Esperado: 3 tests pasan.

- [ ] **Commit:**
```bash
git add app/Http/Controllers/PredictionController.php
git commit -m "feat: add phasePts and fixtures_count to predictions index payload"
```

---

## Task 4: Implementar `PredictionController@receipt`

**Files:**
- Modify: `app/Http/Controllers/PredictionController.php`

- [ ] **Reemplazar el stub de `receipt()` con la implementación completa:**

```php
public function receipt(Round $round): Response|RedirectResponse
{
    $userId = Auth::id();

    $submission = PredictionSubmission::where('user_id', $userId)
        ->where('round_id', $round->id)
        ->first();

    if (! $submission) {
        return redirect()->route('predictions.index');
    }

    $fixtures = $round->fixtures()
        ->with(['homeTeam', 'awayTeam', 'group'])
        ->orderBy('match_number')
        ->get();

    $predictions = Prediction::where('user_id', $userId)
        ->whereIn('match_id', $fixtures->pluck('id'))
        ->get()
        ->keyBy('match_id');

    return Inertia::render('Predictions/Receipt', [
        'round'       => $round,
        'fixtures'    => $fixtures,
        'predictions' => $predictions,
        'submission'  => $submission,
        'isFinalized' => $round->is_locked,
    ]);
}
```

- [ ] **Correr todos los tests de receipt:**
```bash
./vendor/bin/sail test --filter "receipt" 2>&1
```
Esperado: 6 tests pasan.

- [ ] **Suite completa:**
```bash
./vendor/bin/sail test 2>&1 | tail -3
```
Esperado: todos los tests pasan.

- [ ] **Commit:**
```bash
git add app/Http/Controllers/PredictionController.php
git commit -m "feat: implement PredictionController receipt action"
```

---

## Task 5: PtsChip + ReceiptMatchRow

**Files:**
- Create: `resources/js/Components/ui/PtsChip.jsx`
- Create: `resources/js/Components/composed/ReceiptMatchRow.jsx`

- [ ] **Crear `resources/js/Components/ui/PtsChip.jsx`:**

```jsx
/**
 * PtsChip — chip de puntos por tipo de acierto.
 * type: 'exact' | 'result' | 'classifier'
 * pts: número entero
 */
export default function PtsChip({ pts, type }) {
    if (!pts) {
        return (
            <span className="px-1.5 py-0.5 font-mono text-[10px] border border-ink/25 text-ink/35 leading-none">
                +0
            </span>
        );
    }

    const colors = {
        exact:      'bg-pop-red text-white border-ink',
        result:     'bg-pop-teal text-ink border-ink',
        classifier: 'bg-pop-yel text-ink border-ink',
    };

    return (
        <span
            className={`px-1.5 py-0.5 font-mono text-[10px] font-bold border leading-none ${colors[type] ?? 'bg-ink text-cream border-ink'}`}
            style={{ boxShadow: '1px 1px 0 var(--c-ink)' }}
        >
            +{pts}
        </span>
    );
}
```

- [ ] **Crear `resources/js/Components/composed/ReceiptMatchRow.jsx`:**

```jsx
import PtsChip from '@/Components/ui/PtsChip';

export default function ReceiptMatchRow({ fixture, prediction, isFinalized }) {
    const homeCode = fixture.homeTeam?.fifa_code ?? fixture.home_placeholder ?? 'TBD';
    const awayCode = fixture.awayTeam?.fifa_code ?? fixture.away_placeholder ?? 'TBD';
    const homeFlag = fixture.homeTeam?.flag_url;
    const awayFlag = fixture.awayTeam?.flag_url;

    const realScore = fixture.home_score !== null && fixture.away_score !== null
        ? `${fixture.home_score}–${fixture.away_score}`
        : '–';

    const predScore = prediction
        ? `${prediction.predicted_home}–${prediction.predicted_away}`
        : '—';

    return (
        <div className="flex items-center gap-2 px-[18px] py-2.5 border-b border-ink/10">
            {/* Resultado real */}
            <div className="flex items-center gap-1 flex-1 min-w-0 overflow-hidden">
                {homeFlag
                    ? <img src={homeFlag} className="w-5 h-3.5 object-cover border border-ink/20 flex-shrink-0" alt={homeCode} />
                    : <span className="w-5 h-3.5 bg-ink/10 border border-ink/20 flex-shrink-0" />
                }
                <span className="font-mono text-[10px] font-bold truncate">{homeCode}</span>
                <span className="font-display text-[13px] mx-1 flex-shrink-0">{realScore}</span>
                <span className="font-mono text-[10px] font-bold truncate">{awayCode}</span>
                {awayFlag
                    ? <img src={awayFlag} className="w-5 h-3.5 object-cover border border-ink/20 flex-shrink-0" alt={awayCode} />
                    : <span className="w-5 h-3.5 bg-ink/10 border border-ink/20 flex-shrink-0" />
                }
            </div>

            {/* Predicción + chips */}
            <div className="flex items-center gap-1.5 flex-shrink-0">
                <span className="font-mono text-[10px] opacity-40">→</span>
                <span className="font-mono text-[12px] font-bold">{predScore}</span>

                {isFinalized && prediction && (
                    <div className="flex gap-1">
                        <PtsChip pts={prediction.pts_exact}  type="exact"  />
                        <PtsChip pts={prediction.pts_result} type="result" />
                    </div>
                )}
            </div>
        </div>
    );
}
```

- [ ] **Commit:**
```bash
git add resources/js/Components/ui/PtsChip.jsx resources/js/Components/composed/ReceiptMatchRow.jsx
git commit -m "feat: add PtsChip and ReceiptMatchRow components"
```

---

## Task 6: TournamentProgress

**Files:**
- Create: `resources/js/Components/composed/TournamentProgress.jsx`

- [ ] **Crear `resources/js/Components/composed/TournamentProgress.jsx`:**

```jsx
const SLUG_LABELS = {
    'grupos':  'GRUPOS',
    'r32-r16': 'R32+R16',
    'qf-sf':   '8VOS+4TOS',
    'final':   'FINAL',
};

function nodeState(round, submission) {
    if (round.is_locked && submission) return 'finalized';
    if (round.is_open || round.is_locked)  return 'active';
    return 'upcoming';
}

export default function TournamentProgress({ rounds, submissions }) {
    return (
        <div className="flex items-start px-[18px] py-3">
            {rounds.map((round, i) => {
                const state      = nodeState(round, submissions[round.id]);
                const isLast     = i === rounds.length - 1;
                const isFirst    = i === 0;
                const isUpcoming = state === 'upcoming';

                const nodeClass = {
                    finalized: 'bg-ink border-ink',
                    active:    'bg-pop-red border-ink',
                    upcoming:  'bg-cream border-ink/30',
                }[state];

                return (
                    <div key={round.id} className="flex flex-col items-center flex-1">
                        <div className="flex items-center w-full">
                            {/* Línea izquierda */}
                            {!isFirst && (
                                <div className={`flex-1 h-[3px] ${state === 'finalized' ? 'bg-ink' : 'bg-ink/15'}`} />
                            )}

                            {/* Nodo */}
                            <div className={`w-7 h-7 rounded-full border-[2.5px] flex items-center justify-center flex-shrink-0 ${nodeClass}`}>
                                {state === 'finalized' && (
                                    <span className="text-cream font-mono text-[11px] font-bold">✓</span>
                                )}
                                {state === 'active' && (
                                    <span className="w-2 h-2 rounded-full bg-cream" />
                                )}
                                {state === 'upcoming' && (
                                    <span className="text-ink/30 text-[10px]">🔒</span>
                                )}
                            </div>

                            {/* Línea derecha */}
                            {!isLast && (
                                <div className={`flex-1 h-[3px] ${state === 'finalized' ? 'bg-ink' : 'bg-ink/15'}`} />
                            )}
                        </div>

                        <div className={`font-mono text-[7.5px] mt-1.5 tracking-[.04em] text-center leading-none ${isUpcoming ? 'opacity-30' : 'opacity-80'}`}>
                            {SLUG_LABELS[round.slug] ?? round.name.toUpperCase()}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
```

- [ ] **Commit:**
```bash
git add resources/js/Components/composed/TournamentProgress.jsx
git commit -m "feat: add TournamentProgress component"
```

---

## Task 7: PhaseCard

**Files:**
- Create: `resources/js/Components/composed/PhaseCard.jsx`

- [ ] **Crear `resources/js/Components/composed/PhaseCard.jsx`:**

```jsx
import { Link } from '@inertiajs/react';

function deriveState(round, submission) {
    if (!round.is_open && !round.is_locked)                          return 'upcoming';
    if (round.is_open  && !submission)                               return 'open';
    if (round.is_open  && submission?.status === 'draft')            return 'draft';
    if (round.is_open  && submission?.status === 'submitted')        return 'submitted';
    if (round.is_locked && !submission)                              return 'locked';
    return 'finalized'; // is_locked && submission exists
}

function ProgressBar({ value, max }) {
    const pct = max > 0 ? Math.min((value / max) * 100, 100) : 0;
    return (
        <div className="h-[5px] bg-black/15 border border-ink/20 mt-2">
            <div className="h-full bg-pop-teal transition-all" style={{ width: `${pct}%` }} />
        </div>
    );
}

export default function PhaseCard({ round, submission, phasePts }) {
    const state        = deriveState(round, submission);
    const fixtureCount = round.fixtures_count ?? 0;
    const predCount    = phasePts?.prediction_count ?? 0;

    const wrapperBase = 'border-[2.5px] border-ink p-3.5 relative overflow-hidden';

    // ── upcoming ──────────────────────────────────────────────────────────────
    if (state === 'upcoming') {
        return (
            <div className={`${wrapperBase} bg-cream opacity-50`}
                 style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between">
                    <div>
                        <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5">{fixtureCount} partidos</div>
                    </div>
                    <span className="text-[20px]">🔒</span>
                </div>
            </div>
        );
    }

    // ── open ─────────────────────────────────────────────────────────────────
    if (state === 'open') {
        return (
            <div className={`${wrapperBase} bg-cream`}
                 style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between mb-2">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-pop-teal border border-ink" />
                            <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        </div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5 ml-[18px]">{fixtureCount} partidos · abierta</div>
                    </div>
                </div>
                <Link
                    href={route('predictions.show', round.slug)}
                    className="block w-full py-2.5 bg-pop-red text-white font-display text-[13px] tracking-[.01em] border-[2px] border-ink text-center"
                    style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    METER GOLES →
                </Link>
            </div>
        );
    }

    // ── draft ─────────────────────────────────────────────────────────────────
    if (state === 'draft') {
        return (
            <div className={`${wrapperBase} bg-cream`}
                 style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-pop-yel border border-ink" />
                            <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        </div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5 ml-[18px]">
                            {predCount} / {fixtureCount} goles metidos
                        </div>
                    </div>
                </div>
                <ProgressBar value={predCount} max={fixtureCount} />
                <Link
                    href={route('predictions.show', round.slug)}
                    className="block w-full py-2.5 mt-3 bg-pop-yel text-ink font-display text-[13px] tracking-[.01em] border-[2px] border-ink text-center"
                    style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    CONTINUAR →
                </Link>
            </div>
        );
    }

    // ── submitted ─────────────────────────────────────────────────────────────
    if (state === 'submitted') {
        return (
            <div className={`${wrapperBase} bg-cream`}
                 style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-pop-teal border border-ink" />
                            <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        </div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5 ml-[18px]">{fixtureCount} goles confirmados</div>
                    </div>
                    <span className="bg-pop-teal text-ink border border-ink px-2 py-0.5 font-mono text-[8px] font-bold tracking-[.06em]">
                        CONFIRMADA
                    </span>
                </div>
                <Link
                    href={route('predictions.receipt', round.slug)}
                    className="block w-full py-2 mt-3 bg-white text-ink font-display text-[12px] tracking-[.01em] border-[2px] border-ink text-center"
                    style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                >
                    VER COMPROBANTE →
                </Link>
            </div>
        );
    }

    // ── locked ────────────────────────────────────────────────────────────────
    if (state === 'locked') {
        return (
            <div className={`${wrapperBase} bg-cream`}
                 style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between">
                    <div>
                        <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5">Fase cerrada</div>
                    </div>
                    <span className="bg-pop-red text-white border border-ink px-2 py-0.5 font-mono text-[8px] font-bold tracking-[.06em] animate-pulse">
                        EN JUEGO
                    </span>
                </div>
            </div>
        );
    }

    // ── finalized ─────────────────────────────────────────────────────────────
    const total = phasePts?.total ?? 0;
    return (
        <div className={`${wrapperBase} bg-navy text-cream`}
             style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}>
            <div className="flex items-center justify-between">
                <div>
                    <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                    <div className="flex gap-2 mt-1.5 text-[9px] font-mono opacity-70">
                        {phasePts?.pts_exact    > 0 && <span>exacto +{phasePts.pts_exact}</span>}
                        {phasePts?.pts_result   > 0 && <span>result +{phasePts.pts_result}</span>}
                        {phasePts?.pts_classifier > 0 && <span>clasif +{phasePts.pts_classifier}</span>}
                        {total === 0 && <span className="opacity-50">sin puntos</span>}
                    </div>
                </div>
                <div className="bg-pop-yel text-ink border-[2px] border-ink px-2.5 py-1 font-display text-[18px] flex-shrink-0"
                     style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}>
                    +{total}
                </div>
            </div>
            <Link
                href={route('predictions.receipt', round.slug)}
                className="block w-full py-2 mt-3 bg-white/10 text-cream font-display text-[12px] tracking-[.01em] border-[2px] border-cream/30 text-center hover:bg-white/20"
            >
                VER COMPROBANTE →
            </Link>
        </div>
    );
}
```

- [ ] **Commit:**
```bash
git add resources/js/Components/composed/PhaseCard.jsx
git commit -m "feat: add PhaseCard component with 6 states"
```

---

## Task 8: Reescribir Predictions/Index.jsx

**Files:**
- Modify: `resources/js/Pages/Predictions/Index.jsx`

- [ ] **Reemplazar todo el contenido de `resources/js/Pages/Predictions/Index.jsx`:**

```jsx
import { Head, Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import MobileShell from '@/Components/MobileShell';
import TabBar from '@/Components/composed/TabBar';
import TournamentProgress from '@/Components/composed/TournamentProgress';
import PhaseCard from '@/Components/composed/PhaseCard';

export default function Index({ rounds, submissions, phasePts }) {
    const { auth } = usePage().props;
    const totalPts = auth.user?.total_points ?? 0;

    return (
        <MobileShell>
            <Head title="Mis Fases · Mundial de Parche" />

            {/* Header */}
            <div className="px-[18px] pt-4 pb-0">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="font-mono text-[9px] tracking-[.1em] opacity-50">MUNDIAL 2026</div>
                        <div className="font-display text-[32px] leading-none mt-0.5">MIS FASES</div>
                    </div>
                    <div
                        className="bg-pop-yel text-ink border-[2.5px] border-ink px-2.5 py-1.5 text-right flex-shrink-0"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        <div className="font-display text-[22px] leading-none">{totalPts}</div>
                        <div className="font-mono text-[8px] tracking-[.06em] opacity-70">PTS TOTALES</div>
                    </div>
                </div>
            </div>

            {/* Progress bar del torneo */}
            <TournamentProgress rounds={rounds} submissions={submissions} />

            {/* Divisor */}
            <div className="h-[3px] bg-ink mx-[18px]" />

            {/* Stack de phase cards */}
            <div className="px-[18px] py-4 flex flex-col gap-3">
                {rounds.map(round => (
                    <PhaseCard
                        key={round.id}
                        round={round}
                        submission={submissions[round.id] ?? null}
                        phasePts={phasePts[round.id] ?? null}
                    />
                ))}

                {/* Bloque especiales */}
                <SpecialsCard />
            </div>

            <div className="pb-6" />
            <TabBar active="home" />
        </MobileShell>
    );
}

function SpecialsCard() {
    return (
        <div
            className="border-[2.5px] border-dashed border-ink p-3.5 flex items-center justify-between"
            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
        >
            <div>
                <div className="font-display text-[13px] leading-tight">PREDICCIONES ESPECIALES</div>
                <div className="font-mono text-[10px] opacity-60 mt-0.5">Campeón · Sub-campeón · Goleador</div>
            </div>
            <Link
                href={route('predictions.special')}
                className="ml-3 px-3 py-1.5 bg-ink text-cream font-display text-[11px] tracking-[.01em] border-[2px] border-ink flex-shrink-0"
                style={{ boxShadow: '2px 2px 0 var(--c-yel)' }}
            >
                VER →
            </Link>
        </div>
    );
}
```

- [ ] **Verificar que el build no tiene errores:**
```bash
./vendor/bin/sail pnpm run build 2>&1 | tail -10
```
Esperado: build exitoso sin errores de importación.

- [ ] **Commit:**
```bash
git add resources/js/Pages/Predictions/Index.jsx
git commit -m "feat: rewrite Predictions/Index with MobileShell, PhaseCard, TournamentProgress"
```

---

## Task 9: Predictions/Receipt.jsx

**Files:**
- Create: `resources/js/Pages/Predictions/Receipt.jsx`

- [ ] **Crear `resources/js/Pages/Predictions/Receipt.jsx`:**

```jsx
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import MobileShell from '@/Components/MobileShell';
import ReceiptMatchRow from '@/Components/composed/ReceiptMatchRow';

export default function Receipt({ round, fixtures, predictions, submission, isFinalized }) {
    const ptsExact      = Object.values(predictions).reduce((s, p) => s + (p.pts_exact  ?? 0), 0);
    const ptsResult     = Object.values(predictions).reduce((s, p) => s + (p.pts_result ?? 0), 0);
    const ptsClassifier = submission.pts_classifier ?? 0;
    const totalPts      = ptsExact + ptsResult + ptsClassifier;

    const isGroupsOrR2 = round.slug === 'grupos' || round.slug === 'r32-r16';

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
                        FINALIZADA
                    </span>
                )}
            </div>

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

                {/* Bloque de clasificados (R1 y R2 finalizadas) */}
                {isFinalized && isGroupsOrR2 && ptsClassifier > 0 && (
                    <div
                        className="mx-[18px] my-3 px-3.5 py-3 bg-ink text-cream border-[2.5px] border-ink"
                        style={{ boxShadow: '3px 3px 0 var(--c-yel)' }}
                    >
                        <div className="font-mono text-[9px] tracking-[.08em] opacity-60">CLASIFICADOS</div>
                        <div className="font-display text-[20px] mt-0.5">+{ptsClassifier} PTS</div>
                        <div className="font-mono text-[9px] opacity-50 mt-0.5">
                            Equipos que predijiste que avanzaban
                        </div>
                    </div>
                )}

                <div className="pb-10" />
            </div>
        </MobileShell>
    );
}
```

- [ ] **Verificar build:**
```bash
./vendor/bin/sail pnpm run build 2>&1 | tail -10
```
Esperado: sin errores.

- [ ] **Suite de tests:**
```bash
./vendor/bin/sail test 2>&1 | tail -3
```
Esperado: todos pasan.

- [ ] **Commit:**
```bash
git add resources/js/Pages/Predictions/Receipt.jsx
git commit -m "feat: add Predictions/Receipt page (phase comprobante)"
```

---

## Task 10: Corregir Round.jsx — points hardcodeados

**Files:**
- Modify: `resources/js/Pages/Predictions/Round.jsx`

Hay 3 valores hardcodeados. El prop `round` ya está disponible y contiene `points_exact`, `points_result`, `points_classifier`.

- [ ] **Corregir línea ~175 — hint de clasificados:**

Buscar:
```jsx
<div className="font-mono text-[8.5px] opacity-55">+3 PTS C/U</div>
```
Reemplazar con:
```jsx
<div className="font-mono text-[8.5px] opacity-55">+{round.points_classifier} PTS C/U</div>
```

- [ ] **Corregir líneas ~344-346 — PointChips hardcodeados:**

Buscar:
```jsx
<PointChip label="EXACTO"    pts="+5" color="var(--c-red)"  />
<PointChip label="GANADOR"   pts="+2" color="var(--c-teal)" />
<PointChip label="CLASIFICA" pts="+3" color="var(--c-yel)" />
```
Reemplazar con:
```jsx
<PointChip label="EXACTO"    pts={`+${round.points_exact}`}      color="var(--c-red)"  />
<PointChip label="GANADOR"   pts={`+${round.points_result}`}     color="var(--c-teal)" />
<PointChip label="CLASIFICA" pts={`+${round.points_classifier}`} color="var(--c-yel)" />
```

> Nota: el PointChip de "CLASIFICA" solo aparece en R1 y R2 (`points_classifier > 0`). En R3 y R4 `round.points_classifier === 0` — el chip mostrará "+0", que es honesto. Si se quiere ocultarlo, agregar `{round.points_classifier > 0 && <PointChip ... />}`. Incluir esta condición:

```jsx
<PointChip label="EXACTO"    pts={`+${round.points_exact}`}  color="var(--c-red)"  />
<PointChip label="GANADOR"   pts={`+${round.points_result}`} color="var(--c-teal)" />
{round.points_classifier > 0 && (
    <PointChip label="CLASIFICA" pts={`+${round.points_classifier}`} color="var(--c-yel)" />
)}
```

- [ ] **Verificar build:**
```bash
./vendor/bin/sail pnpm run build 2>&1 | tail -5
```

- [ ] **Commit:**
```bash
git add resources/js/Pages/Predictions/Round.jsx
git commit -m "fix: use round.points_* props instead of hardcoded scoring values in Round.jsx"
```

---

## Task 11: Corregir HowTo.jsx + Rules.jsx — scoring progresivo

**Files:**
- Modify: `resources/js/Pages/HowTo.jsx`
- Modify: `resources/js/Pages/Rules.jsx`

### HowTo.jsx

La sección "LA PUNTUACIÓN" actual tiene 3 `ScoreLine` con valores planos (+5, +2, +3). Reemplazarla con una tabla por ronda.

- [ ] **En `HowTo.jsx`, reemplazar el bloque de ScoreLines:**

Buscar (líneas ~150-154):
```jsx
<SectionHead title="LA PUNTUACIÓN" accent="red" />
<div className="flex flex-col gap-2">
    <ScoreLine pts="+5" label="MARCADOR EXACTO" sub="Le pegaste al 2-1 clavado" color="var(--c-red)" />
    <ScoreLine pts="+2" label="GANADOR" sub="Acertaste quién gana (sin el score exacto)" color="var(--c-teal)" />
    <ScoreLine pts="+3" label="CLASIFICA A LA SIGUIENTE" sub="Adivinaste qué equipo avanza" color="var(--c-yel)" dark />
</div>
```

Reemplazar con:
```jsx
<SectionHead title="LA PUNTUACIÓN" accent="red" />
<div
    className="bg-white border-[2.5px] border-ink overflow-hidden"
    style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
>
    {/* Header de columnas */}
    <div className="grid grid-cols-4 border-b-[2px] border-ink bg-ink text-cream">
        <div className="col-span-1 px-2 py-1.5 font-mono text-[8px] tracking-[.06em]">FASE</div>
        <div className="px-2 py-1.5 font-mono text-[8px] tracking-[.06em] text-center text-pop-red">EXACTO</div>
        <div className="px-2 py-1.5 font-mono text-[8px] tracking-[.06em] text-center text-pop-teal">GANADOR</div>
        <div className="px-2 py-1.5 font-mono text-[8px] tracking-[.06em] text-center text-pop-yel">CLASIF</div>
    </div>
    {[
        { fase: 'Grupos',    exacto: 3,  ganador: 1, clasif: 2 },
        { fase: 'R32+R16',  exacto: 5,  ganador: 2, clasif: 4 },
        { fase: '8vos+SF',  exacto: 8,  ganador: 3, clasif: null },
        { fase: 'Final',    exacto: 13, ganador: 5, clasif: null },
    ].map(({ fase, exacto, ganador, clasif }, i) => (
        <div key={fase} className={`grid grid-cols-4 ${i < 3 ? 'border-b border-ink/15' : ''}`}>
            <div className="col-span-1 px-2 py-2 font-mono text-[9px] font-bold opacity-70">{fase}</div>
            <div className="px-2 py-2 font-display text-[14px] text-center text-pop-red">+{exacto}</div>
            <div className="px-2 py-2 font-display text-[14px] text-center text-pop-teal">+{ganador}</div>
            <div className="px-2 py-2 font-display text-[14px] text-center text-pop-yel">
                {clasif ? `+${clasif}` : <span className="text-ink/20 text-[12px]">—</span>}
            </div>
        </div>
    ))}
</div>
<div className="font-mono text-[10px] opacity-60 mt-2 leading-[1.4]">
    Los puntos aumentan cada fase — vale más acertar en la final.
</div>
```

### Rules.jsx

- [ ] **En `Rules.jsx`, buscar el texto de scoring (líneas ~115-121):**

```jsx
'+5 pts · marcador exacto (ej: si pusiste 2-1 y queda 2-1)',
'+2 pts · ganador correcto (si pusiste 2-1 y queda 3-0, igual sumás)',
'+3 pts · clasificado correcto a la siguiente ronda',
```

Reemplazar con:
```jsx
'Los puntos son progresivos por fase: Grupos (exacto +3, ganador +1, clasif +2) · R32+R16 (exacto +5, ganador +2, clasif +4) · 8vos+SF (exacto +8, ganador +3) · Final (exacto +13, ganador +5).',
'En rondas de eliminación, el marcador exacto es siempre a 90 minutos. Tiempos extra y penales no cuentan para el score exacto, pero sí para determinar el ganador.',
```

- [ ] **Verificar build:**
```bash
./vendor/bin/sail pnpm run build 2>&1 | tail -5
```
Esperado: sin errores.

- [ ] **Suite completa de tests:**
```bash
./vendor/bin/sail test 2>&1 | tail -3
```
Esperado: todos pasan.

- [ ] **Commit final:**
```bash
git add resources/js/Pages/HowTo.jsx resources/js/Pages/Rules.jsx
git commit -m "fix: update HowTo and Rules with progressive per-round scoring table"
```

---

## Self-Review

**Cobertura del spec:**
- ✅ Phases Index con MobileShell y 6 estados de PhaseCard
- ✅ TournamentProgress (4 nodos)
- ✅ Phase Receipt con predicción vs resultado y chips de puntos
- ✅ Bloque de clasificados en Receipt (R1 y R2)
- ✅ isFinalized = round.is_locked
- ✅ phasePts (pts_exact, pts_result, pts_classifier, total, prediction_count) en index payload
- ✅ Redirect a index si no hay submission en receipt
- ✅ Sin TabBar en Receipt (vista de detalle)
- ✅ HowTo con tabla progresiva por ronda
- ✅ Rules con texto de scoring actualizado
- ✅ Round.jsx usa round.points_* en lugar de hardcoded
- ✅ Tests: index phasePts (3), receipt (5), todos existentes siguen pasando
- ✅ Ruta receipt antes de /{round} en web.php

**Consistencia de tipos:**
- `phasePts[round.id]` en PHP genera clave integer; en JS el acceso `phasePts[round.id]` funciona porque JS coerce a string tanto el key del objeto como el value de `round.id`. ✅
- `submissions[round.id]` — mismo patrón, ya funciona en el código existente. ✅
- `PhaseCard` recibe `submission` como objeto o `null`. `deriveState` usa optional chaining. ✅
- `ReceiptMatchRow` recibe `prediction` como objeto o `null`. ✅
- `PtsChip` recibe `pts` como integer. 0 muestra el chip gris. ✅
