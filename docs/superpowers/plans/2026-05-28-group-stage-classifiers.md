# Group Stage Classifiers — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Guardar los 32 clasificados predichos por el usuario (24 primeros/segundos + 8 mejores terceros) al momento de hacer submit de la fase de grupos, y mostrarlos en el comprobante con marcas ✓/✗ al finalizar.

**Architecture:** El frontend simula la tabla de posiciones de los 12 grupos con los marcadores predichos por el usuario, deriva los 32 clasificados y los envía junto con las predicciones en el mismo request. El backend persiste el resultado como JSON en `prediction_submissions.predicted_classifiers`. El `CalculateClassifierPoints` listener usa ese JSON guardado (en lugar de re-derivar) para otorgar puntos. El comprobante muestra los 32 equipos y, al finalizar la fase, marca cuáles acertó el usuario.

**Tech Stack:** Laravel 11, Pest v3, React 18 + Inertia.js v2, MySQL 8.4 (JSON column)

---

## Archivos a crear/modificar

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `database/migrations/XXXX_add_predicted_classifiers_to_prediction_submissions.php` | CREATE | Agrega columna JSON `predicted_classifiers` |
| `app/Models/PredictionSubmission.php` | MODIFY | Agrega cast + fillable para nueva columna |
| `app/Services/GroupStageClassifierService.php` | CREATE | Lógica pura de tabla de posiciones y selección de 8 mejores terceros, reutilizable por controller y listener |
| `app/Http/Controllers/PredictionController.php` | MODIFY | `save` auto-promueve a `submitted` con classifiers cuando todos los fixtures están cubiertos en R1; `receipt` pasa classifiers enriquecidos + reales para comprobante |
| `app/Listeners/CalculateClassifierPoints.php` | MODIFY | `calculateR1` usa `predicted_classifiers` guardados en lugar de re-derivar |
| `resources/js/Pages/Predictions/Round.jsx` | MODIFY | `simulateAllGroups` calcula los 32 clasificados; los envía en el payload cuando todos los 72 partidos están predichos |
| `resources/js/Pages/Predictions/Receipt.jsx` | MODIFY | Muestra sección de 32 clasificados con posición y grupo; ✓/✗ cuando la fase está finalizada |
| `tests/Feature/PredictionControllerTest.php` | MODIFY | Tests actualizados: save auto-promueve a submitted con classifiers; tests del receipt con classifiers |
| `tests/Feature/CalculateClassifierPointsTest.php` | MODIFY | Tests actualizados: listener usa classifiers guardados |

---

## Task 1: Migración — columna `predicted_classifiers`

**Files:**
- Create: `database/migrations/2026_05_28_000001_add_predicted_classifiers_to_prediction_submissions.php`

- [ ] **Step 1: Crear la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prediction_submissions', function (Blueprint $table) {
            $table->json('predicted_classifiers')->nullable()->after('pts_classifier');
        });
    }

    public function down(): void
    {
        Schema::table('prediction_submissions', function (Blueprint $table) {
            $table->dropColumn('predicted_classifiers');
        });
    }
};
```

- [ ] **Step 2: Correr la migración**

```bash
./vendor/bin/sail artisan migrate
```

Esperado: `Migrating: 2026_05_28_000001_add_predicted_classifiers...` → `Migrated`

- [ ] **Step 3: Actualizar el modelo `PredictionSubmission`**

Modifica `app/Models/PredictionSubmission.php`:

```php
protected $fillable = [
    'user_id', 'round_id', 'status', 'submitted_at',
    'pts_classifier', 'predicted_classifiers',
];

protected function casts(): array
{
    return [
        'submitted_at'          => 'datetime',
        'predicted_classifiers' => 'array',
    ];
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_28_000001_add_predicted_classifiers_to_prediction_submissions.php \
        app/Models/PredictionSubmission.php
git commit -m "feat: add predicted_classifiers JSON column to prediction_submissions"
```

---

## Task 2: `GroupStageClassifierService` — lógica de tabla de posiciones

**Files:**
- Create: `app/Services/GroupStageClassifierService.php`

Esta clase encapsula la lógica que hoy vive como métodos privados en `CalculateClassifierPoints`. Extraerla permite que el controller y el listener la reutilicen sin duplicar código.

- [ ] **Step 1: Crear el servicio**

```php
<?php

namespace App\Services;

use Illuminate\Support\Collection;

class GroupStageClassifierService
{
    /**
     * Calcula los 32 clasificados de la fase de grupos.
     *
     * @param  Collection  $fixtures  Fixtures del round con homeTeam/awayTeam cargados
     * @param  callable    $getScores  fn(Fixture): [home_score, away_score] — puede ser real o predicho
     * @return array  Array de team_ids de los 32 clasificados
     */
    public function getClassifierIds(Collection $fixtures, callable $getScores): array
    {
        $byGroup     = $fixtures->groupBy('group_id');
        $classifiers = [];
        $thirds      = [];

        foreach ($byGroup as $groupFixtures) {
            $table = $this->buildGroupTable($groupFixtures, $getScores);

            if (count($table) < 2) continue;

            $classifiers[] = $table[0]['team_id'];
            $classifiers[] = $table[1]['team_id'];

            if (isset($table[2])) {
                $thirds[] = $table[2];
            }
        }

        if (count($thirds) >= 8) {
            usort($thirds, fn ($a, $b) =>
                $b['pts'] <=> $a['pts']
                    ?: $b['gd'] <=> $a['gd']
                    ?: $b['gf'] <=> $a['gf']
            );

            foreach (array_slice($thirds, 0, 8) as $third) {
                $classifiers[] = $third['team_id'];
            }
        }

        return $classifiers;
    }

    /**
     * Construye la tabla de posiciones de un grupo.
     *
     * @return array  Filas ordenadas desc por pts → gd → gf.
     *                Cada fila: ['team_id', 'pts', 'gd', 'gf']
     */
    public function buildGroupTable(Collection $fixtures, callable $getScores): array
    {
        $table = [];

        foreach ($fixtures as $f) {
            if ($f->home_team_id) $table[$f->home_team_id] ??= ['team_id' => $f->home_team_id, 'pts' => 0, 'gd' => 0, 'gf' => 0];
            if ($f->away_team_id) $table[$f->away_team_id] ??= ['team_id' => $f->away_team_id, 'pts' => 0, 'gd' => 0, 'gf' => 0];
        }

        foreach ($fixtures as $f) {
            [$h, $a] = $getScores($f);
            if ($h === null || $a === null || !$f->home_team_id || !$f->away_team_id) continue;

            $table[$f->home_team_id]['gf'] += $h;
            $table[$f->home_team_id]['gd'] += $h - $a;
            $table[$f->away_team_id]['gf'] += $a;
            $table[$f->away_team_id]['gd'] += $a - $h;

            if ($h > $a) {
                $table[$f->home_team_id]['pts'] += 3;
            } elseif ($h < $a) {
                $table[$f->away_team_id]['pts'] += 3;
            } else {
                $table[$f->home_team_id]['pts'] += 1;
                $table[$f->away_team_id]['pts'] += 1;
            }
        }

        usort($table, fn ($a, $b) =>
            $b['pts'] <=> $a['pts'] ?: $b['gd'] <=> $a['gd'] ?: $b['gf'] <=> $a['gf']
        );

        return array_values($table);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/GroupStageClassifierService.php
git commit -m "feat: extract GroupStageClassifierService for group table simulation"
```

---

## Task 3: Actualizar `CalculateClassifierPoints` para usar classifiers guardados

**Files:**
- Modify: `app/Listeners/CalculateClassifierPoints.php`

El listener usará `predicted_classifiers` guardados si existen. Si no (submissions antiguas en draft que nunca llegaron a submitted), hace fallback a la derivación. Esto preserva compatibilidad con datos existentes.

- [ ] **Step 1: Escribir el test que falla**

En `tests/Feature/CalculateClassifierPointsTest.php`, localiza el test `it('awards classifier pts when user correctly predicts R1 top-2 classifiers')` y agrega un test nuevo **antes** de los existentes:

```php
it('uses saved predicted_classifiers when available instead of re-deriving', function () {
    $round = Round::factory()->r1()->create(['is_open' => false, 'is_locked' => true]);
    $group = \App\Models\Group::factory()->create(['name' => 'A']);
    $home  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away  = \App\Models\Team::factory()->create(['group_id' => $group->id]);

    \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
        'home_score' => 2, 'away_score' => 0, // real: home wins
    ]);

    $user = \App\Models\User::factory()->create(['is_activated' => true]);

    // El usuario guardó $away como clasificado (incorrecto según scores reales)
    // pero si usamos los scores predichos habría acertado — queremos que use el JSON guardado
    $submission = \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id'  => $user->id,
        'round_id' => $round->id,
        'predicted_classifiers' => [
            ['team_id' => $away->id, 'group' => 'A', 'position' => 1],
        ],
    ]);

    event(new \App\Events\RoundFinalized($round));

    // Solo 1 grupo con 2 equipos, real classifier es $home.
    // Usuario guardó $away → 0 aciertos → 0 puntos.
    expect($submission->fresh()->pts_classifier)->toBe(0);
});
```

- [ ] **Step 2: Correr el test para verificar que falla**

```bash
./vendor/bin/sail test --filter "uses saved predicted_classifiers"
```

Esperado: FAIL (el listener todavía re-deriva desde scores, no usa el JSON).

- [ ] **Step 3: Actualizar el listener**

Reemplaza el método `calculateR1` y agrega el import del servicio:

```php
<?php

namespace App\Listeners;

use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\User;
use App\Services\GroupStageClassifierService;
use Illuminate\Support\Collection;

class CalculateClassifierPoints
{
    public function __construct(private GroupStageClassifierService $classifier) {}

    public function handle(RoundFinalized $event): void
    {
        $round = $event->round;

        if ($round->slug === 'grupos') {
            $this->calculateR1($round);
        } elseif ($round->slug === 'r32-r16') {
            $this->calculateR2($round);
        }
    }

    private function calculateR1(\App\Models\Round $round): void
    {
        $fixtures = Fixture::where('round_id', $round->id)
            ->whereNotNull('group_id')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        $realClassifiers = $this->classifier->getClassifierIds(
            $fixtures,
            fn ($f) => [$f->home_score, $f->away_score]
        );

        $submissions = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('status', ['submitted', 'locked'])
            ->get();

        foreach ($submissions as $submission) {
            // Usar classifiers guardados si existen; si no, derivar desde scores predichos
            if (! empty($submission->predicted_classifiers)) {
                $predictedClassifiers = collect($submission->predicted_classifiers)
                    ->pluck('team_id')
                    ->toArray();
            } else {
                $userPredictions = Prediction::where('user_id', $submission->user_id)
                    ->whereIn('match_id', $fixtures->pluck('id'))
                    ->get()
                    ->keyBy('match_id');

                $predictedClassifiers = $this->classifier->getClassifierIds(
                    $fixtures,
                    function ($f) use ($userPredictions) {
                        $pred = $userPredictions->get($f->id);
                        return $pred ? [$pred->predicted_home, $pred->predicted_away] : [null, null];
                    }
                );
            }

            $correct = count(array_intersect($predictedClassifiers, $realClassifiers));
            $pts     = $correct * $round->points_classifier;

            $submission->update(['pts_classifier' => $pts]);
            User::recalculateTotalPoints($submission->user_id);
        }
    }

    // calculateR2 y buildGroupTable se eliminan — buildGroupTable ahora vive en el servicio.
    // calculateR2 no usa classifiers guardados (R16 se deriva de predicted_home/away).
    private function calculateR2(\App\Models\Round $round): void
    {
        $r16Fixtures = Fixture::where('round_id', $round->id)
            ->whereBetween('match_number', [89, 96])
            ->orderBy('match_number')
            ->get();

        $realClassifiers = $r16Fixtures
            ->pluck('winner_team_id')
            ->filter()
            ->values()
            ->toArray();

        $submissions = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('status', ['submitted', 'locked'])
            ->get();

        $r16FixtureIds = $r16Fixtures->pluck('id');

        foreach ($submissions as $submission) {
            $userR16Predictions = Prediction::where('user_id', $submission->user_id)
                ->whereIn('match_id', $r16FixtureIds)
                ->get()
                ->keyBy('match_id');

            $predictedClassifiers = [];
            foreach ($r16Fixtures as $fixture) {
                $pred = $userR16Predictions->get($fixture->id);
                if (!$pred || !$fixture->home_team_id || !$fixture->away_team_id) continue;

                $predictedClassifiers[] = $pred->predicted_home > $pred->predicted_away
                    ? $fixture->home_team_id
                    : $fixture->away_team_id;
            }

            $correct = count(array_intersect($predictedClassifiers, $realClassifiers));
            $pts     = $correct * $round->points_classifier;

            $submission->update(['pts_classifier' => $pts]);
            User::recalculateTotalPoints($submission->user_id);
        }
    }
}
```

- [ ] **Step 4: Correr todos los tests del listener**

```bash
./vendor/bin/sail test --filter CalculateClassifierPoints
```

Esperado: todos los tests existentes pasan + el nuevo pasa.

- [ ] **Step 5: Commit**

```bash
git add app/Listeners/CalculateClassifierPoints.php app/Services/GroupStageClassifierService.php
git commit -m "refactor: CalculateClassifierPoints usa GroupStageClassifierService y predicted_classifiers guardados"
```

---

## Task 4: Modificar `PredictionController@save` — auto-promoción a submitted con classifiers

**Files:**
- Modify: `app/Http/Controllers/PredictionController.php`

**Regla de negocio:**
- Si `round.slug === 'grupos'` Y todos los fixtures del round tienen predicción en el payload Y se recibe `predicted_classifiers` → guardar predicciones + classifiers, status = `submitted`, `submitted_at = now()`
- En cualquier otro caso → guardar lo que llegue, status = `draft`
- El submit route separado se elimina de las rutas (las rutas POST de submit → dejar el método pero sacar la ruta pública)

- [ ] **Step 1: Escribir los tests que fallan**

En `tests/Feature/PredictionControllerTest.php`, agrega estos tests **después** del test `'saves predictions as draft'`:

```php
it('auto-promotes to submitted with classifiers when all R1 fixtures are covered', function () {
    $round = Round::factory()->r1()->create(['is_open' => true]);
    $group = \App\Models\Group::factory()->create(['name' => 'A']);
    $home  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $f1    = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $classifiers = [['team_id' => $home->id, 'group' => 'A', 'position' => 1]];

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $f1->id => ['predicted_home' => 2, 'predicted_away' => 0],
        ],
        'predicted_classifiers' => $classifiers,
    ])->assertRedirect();

    $submission = \App\Models\PredictionSubmission::first();
    expect($submission->status)->toBe('submitted');
    expect($submission->submitted_at)->not->toBeNull();
    expect($submission->predicted_classifiers)->toBe($classifiers);
});

it('stays draft when predicted_classifiers is missing even if all fixtures covered', function () {
    $round = Round::factory()->r1()->create(['is_open' => true]);
    $group = \App\Models\Group::factory()->create(['name' => 'A']);
    $home  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $f1    = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $f1->id => ['predicted_home' => 2, 'predicted_away' => 0],
        ],
        // sin predicted_classifiers
    ])->assertRedirect();

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('draft');
    expect(\App\Models\PredictionSubmission::first()->predicted_classifiers)->toBeNull();
});

it('stays draft for non-group rounds regardless of classifiers', function () {
    $round = Round::factory()->r2()->create(['is_open' => true]);
    $f1    = \App\Models\Fixture::factory()->create(['round_id' => $round->id]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $f1->id => ['predicted_home' => 2, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('draft');
});
```

- [ ] **Step 2: Correr los tests para verificar que fallan**

```bash
./vendor/bin/sail test --filter "auto-promotes to submitted|stays draft when|stays draft for non"
```

Esperado: FAIL (save siempre guarda draft, no recibe classifiers).

- [ ] **Step 3: Actualizar el método `save` en `PredictionController`**

Reemplaza el método `save` completo:

```php
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

    return DB::transaction(function () use ($data, $fixtureIds, $round) {
        foreach ($data['predictions'] as $matchId => $scores) {
            if (! $fixtureIds->contains((int) $matchId)) continue;
            Prediction::updateOrCreate(
                ['user_id' => Auth::id(), 'match_id' => (int) $matchId],
                ['predicted_home' => $scores['predicted_home'], 'predicted_away' => $scores['predicted_away']]
            );
        }

        // Auto-promoción a submitted si es fase de grupos y se recibieron classifiers
        $isGroupStage    = $round->slug === 'grupos';
        $hasClassifiers  = ! empty($data['predicted_classifiers']);
        $allCovered      = $fixtureIds->diff(collect($data['predictions'])->keys()->map(fn ($k) => (int) $k))->isEmpty();

        if ($isGroupStage && $hasClassifiers && $allCovered) {
            PredictionSubmission::updateOrCreate(
                ['user_id' => Auth::id(), 'round_id' => $round->id],
                [
                    'status'                 => 'submitted',
                    'submitted_at'           => now(),
                    'predicted_classifiers'  => $data['predicted_classifiers'],
                ]
            );
            return back()->with('status', '¡Fase de grupos confirmada con tus 32 clasificados!');
        }

        PredictionSubmission::updateOrCreate(
            ['user_id' => Auth::id(), 'round_id' => $round->id],
            ['status' => 'draft']
        );
        return back()->with('status', 'Borrador guardado.');
    });
}
```

- [ ] **Step 4: Eliminar la ruta `submit` de `routes/web.php`**

En `routes/web.php`, dentro del grupo de predictions, elimina la línea:

```php
// Eliminar esta línea:
Route::post('/{round}/submit', [PredictionController::class, 'submit'])->name('submit');
```

- [ ] **Step 5: Correr todos los tests**

```bash
./vendor/bin/sail test tests/Feature/PredictionControllerTest.php
```

Esperado: todos pasan. Los tests que usaban `/submit` directamente fallarán — continuar al siguiente paso.

- [ ] **Step 6: Actualizar tests que usaban la ruta `/submit`**

Busca en `PredictionControllerTest.php` todos los tests que hacen `post("/predictions/{round->slug}/submit", ...)` y cámbialos a `post("/predictions/{round->slug}/save", ...)`. Los tests que verificaban `status === 'submitted'` ahora deben también enviar `predicted_classifiers` para alcanzar ese estado.

Tests a actualizar (busca por `->submit`):

**`'submits predictions when all fixtures are covered'`** → cambiar a save + enviar classifiers:
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

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 2, 'predicted_away' => 1],
        ],
        'predicted_classifiers' => [
            ['team_id' => $home->id, 'group' => 'A', 'position' => 1],
            ['team_id' => $away->id, 'group' => 'A', 'position' => 2],
        ],
    ])->assertRedirect();

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('submitted');
    expect(\App\Models\PredictionSubmission::first()->submitted_at)->not->toBeNull();
});
```

**`'rejects submit when not all fixtures covered'`** → ya cubierto por `'stays draft when predicted_classifiers is missing'`; eliminar este test o reescribir:
```php
it('rejects submit when not all fixtures covered', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
    ]);

    // Sin predictions ni classifiers → queda en draft
    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [],
    ])->assertRedirect();

    // No se crea submission porque predictions está vacío (required array falla en validate)
    // O se crea en draft si llegan algunas predictions. Verificar que no hay submitted.
    $sub = \App\Models\PredictionSubmission::first();
    expect($sub?->status)->not->toBe('submitted');
});
```

**`'rejects submit with tie in knockout round'`** — el save ya valida empates en knockout. Cambia la URL de `/submit` a `/save`:
```php
$this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [...])
```

**`'allows ties in group stage (R1) submit'`** → cambiar URL a `/save`.

**`'rejects save (draft) with tie in knockout round'`** → ya estaba en save, sin cambios.

**`'allows ties in group stage (R1) save'`** → sin cambios.

- [ ] **Step 7: Correr todos los tests del controller**

```bash
./vendor/bin/sail test tests/Feature/PredictionControllerTest.php
```

Esperado: todos pasan.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/PredictionController.php \
        routes/web.php \
        tests/Feature/PredictionControllerTest.php
git commit -m "feat: save auto-promueve a submitted con predicted_classifiers en fase de grupos"
```

---

## Task 5: `PredictionController@receipt` — pasar classifiers enriquecidos

**Files:**
- Modify: `app/Http/Controllers/PredictionController.php`

El comprobante necesita:
- `classifiers`: array de `{team_id, group, position, team_name, flag_url}` — las predicciones del usuario
- `realClassifierIds`: array de team_ids que realmente clasificaron — solo cuando `isFinalized`

- [ ] **Step 1: Escribir el test que falla**

En `tests/Feature/PredictionControllerTest.php`, agrega al final:

```php
it('receipt includes predicted_classifiers enriched with team data for R1', function () {
    $round = Round::factory()->r1()->create(['is_open' => false, 'is_locked' => false]);
    $group = \App\Models\Group::factory()->create(['name' => 'A']);
    $home  = \App\Models\Team::factory()->create(['group_id' => $group->id, 'name' => 'Colombia', 'flag_url' => '/flags/col.png']);
    $away  = \App\Models\Team::factory()->create(['group_id' => $group->id, 'name' => 'Brasil']);

    $submission = \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id'  => $this->user->id,
        'round_id' => $round->id,
        'predicted_classifiers' => [
            ['team_id' => $home->id, 'group' => 'A', 'position' => 1],
            ['team_id' => $away->id, 'group' => 'A', 'position' => 2],
        ],
    ]);

    $response = $this->actingAs($this->user)
        ->get("/predictions/{$round->slug}/receipt");

    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Receipt')
        ->has('classifiers', 2)
        ->where('classifiers.0.team_name', 'Colombia')
        ->where('classifiers.0.flag_url', '/flags/col.png')
        ->where('classifiers.0.position', 1)
        ->missing('realClassifierIds') // solo cuando is_locked=true
    );
});

it('receipt includes realClassifierIds when round is finalized', function () {
    $round = Round::factory()->r1()->create(['is_open' => false, 'is_locked' => true]);
    $group = \App\Models\Group::factory()->create(['name' => 'A']);
    $home  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away  = \App\Models\Team::factory()->create(['group_id' => $group->id]);

    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => 2,
        'away_score'   => 0,
    ]);

    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id'  => $this->user->id,
        'round_id' => $round->id,
        'predicted_classifiers' => [
            ['team_id' => $home->id, 'group' => 'A', 'position' => 1],
        ],
    ]);

    $response = $this->actingAs($this->user)
        ->get("/predictions/{$round->slug}/receipt");

    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Receipt')
        ->has('realClassifierIds')
        ->where('realClassifierIds', fn ($ids) => in_array($home->id, $ids))
    );
});
```

- [ ] **Step 2: Correr los tests para verificar que fallan**

```bash
./vendor/bin/sail test --filter "receipt includes predicted_classifiers|receipt includes realClassifierIds"
```

Esperado: FAIL.

- [ ] **Step 3: Actualizar el método `receipt` en `PredictionController`**

Agrega el import al inicio del archivo:
```php
use App\Services\GroupStageClassifierService;
```

Reemplaza el método `receipt`:

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
    if ($round->is_locked && $round->slug === 'grupos') {
        $service           = app(GroupStageClassifierService::class);
        $realClassifierIds = $service->getClassifierIds(
            $fixtures,
            fn ($f) => [$f->home_score, $f->away_score]
        );
    }

    return Inertia::render('Predictions/Receipt', [
        'round'             => $round,
        'fixtures'          => $fixtures,
        'predictions'       => $predictions,
        'submission'        => $submission,
        'isFinalized'       => $round->is_locked,
        'classifiers'       => $classifiers,
        'realClassifierIds' => $realClassifierIds,
    ]);
}
```

- [ ] **Step 4: Correr los tests**

```bash
./vendor/bin/sail test --filter "receipt"
```

Esperado: todos los tests de receipt pasan.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PredictionController.php \
        tests/Feature/PredictionControllerTest.php
git commit -m "feat: receipt pasa classifiers enriquecidos y realClassifierIds para comparación"
```

---

## Task 6: Frontend — `Round.jsx` — simulación de 32 clasificados y envío en payload

**Files:**
- Modify: `resources/js/Pages/Predictions/Round.jsx`

Agregar la función `simulateAllGroups` que opera sobre todos los fixtures/scores (no solo el grupo activo), derivar los 32 clasificados, y enviarlos en el payload cuando todos los 72 partidos tienen predicciones.

- [ ] **Step 1: Agregar `simulateAllGroups` después de `simulateStandings`**

Agrega esta función después de la función `simulateStandings` existente en Round.jsx:

```javascript
// ── simulateAllGroups — simula los 32 clasificados de toda la fase de grupos

function simulateAllGroups(fixtures, scores) {
    // Agrupar por nombre de grupo
    const byGroup = {};
    fixtures.forEach(f => {
        const key = f.group?.name ?? 'Sin Grupo';
        if (!byGroup[key]) byGroup[key] = [];
        byGroup[key].push(f);
    });

    const classifiers = [];
    const thirdsPool  = [];

    Object.entries(byGroup).forEach(([groupName, groupFixtures]) => {
        const standings = simulateStandings(groupFixtures, scores);
        standings.forEach((row, i) => {
            const entry = { team_id: row.team.id, group: groupName, position: i + 1 };
            if (i < 2) {
                classifiers.push(entry); // 1° y 2° clasifican directo
            } else if (i === 2) {
                thirdsPool.push({ ...entry, pts: row.pts, gf: row.gf, ga: row.ga });
            }
        });
    });

    // Seleccionar los 8 mejores terceros
    thirdsPool.sort((a, b) => {
        if (b.pts !== a.pts) return b.pts - a.pts;
        const gdDiff = (b.gf - b.ga) - (a.gf - a.ga);
        if (gdDiff !== 0) return gdDiff;
        return b.gf - a.gf;
    });

    thirdsPool.slice(0, 8).forEach(({ team_id, group, position }) => {
        classifiers.push({ team_id, group, position });
    });

    return classifiers; // 32 entradas: {team_id, group, position}
}
```

- [ ] **Step 2: Actualizar la función `submit` para incluir classifiers en el payload**

Reemplaza la función `submit` dentro del componente `Round`:

```javascript
function buildPayload() {
    const preds = {};
    fixtures.forEach(f => {
        const s = scores[f.id];
        if (s && s.home !== null && s.away !== null) {
            preds[String(f.id)] = {
                predicted_home: Number(s.home),
                predicted_away: Number(s.away),
            };
        }
    });
    return { predictions: preds };
}

function submit() {
    const payload = buildPayload();

    // Para fase de grupos: incluir classifiers cuando todos los partidos están predichos
    if (isGroupStage && filledCount === totalFixtures) {
        payload.predicted_classifiers = simulateAllGroups(fixtures, scores);
    }

    router.post(route('predictions.save', round.slug), payload);
}
```

- [ ] **Step 3: Agregar el panel "TUS 32 CLASIFICADOS" al final del contenido principal**

En Round.jsx, agrega el panel después del bloque `{/* GroupPanel */}` y antes del cierre del div principal. Agregar dentro del div con `pb-[128px]`:

```jsx
{/* Panel TUS 32 CLASIFICADOS — visible solo cuando todos los partidos están predichos */}
{isGroupStage && filledCount === totalFixtures && (() => {
    const allClassifiers = simulateAllGroups(fixtures, scores);
    const byGroup = {};
    allClassifiers.forEach(c => {
        if (!byGroup[c.group]) byGroup[c.group] = [];
        byGroup[c.group].push(c);
    });
    const bestThirds = allClassifiers.filter(c => c.position === 3);

    return (
        <div className="px-[14px] pt-4">
            <div className="border-[3px] border-ink bg-navy text-cream p-3.5 relative overflow-hidden"
                 style={{ boxShadow: '5px 5px 0 var(--c-yel)' }}>
                <div className="font-mono text-[9px] tracking-[.1em] text-pop-yel opacity-90 mb-1">
                    SEGÚN TUS PREDICCIONES
                </div>
                <div className="font-display text-[18px] leading-none mb-3">
                    TUS 32 CLASIFICADOS
                </div>

                {/* Grid por grupo: primero y segundo */}
                <div className="grid grid-cols-2 gap-x-2 gap-y-1 mb-3">
                    {Object.entries(byGroup)
                        .sort(([a], [b]) => a.localeCompare(b))
                        .map(([groupName, entries]) => {
                            const first  = entries.find(e => e.position === 1);
                            const second = entries.find(e => e.position === 2);
                            const third  = entries.find(e => e.position === 3);
                            const team1  = first  ? fixtures.find(f => f.home_team?.id === first.team_id  || f.away_team?.id === first.team_id) : null;
                            const team2  = second ? fixtures.find(f => f.home_team?.id === second.team_id || f.away_team?.id === second.team_id) : null;
                            const t1     = team1?.home_team?.id === first?.team_id  ? team1?.home_team  : team1?.away_team;
                            const t2     = team2?.home_team?.id === second?.team_id ? team2?.home_team  : team2?.away_team;
                            return (
                                <div key={groupName} className="bg-white/10 px-2 py-1.5 border border-cream/20">
                                    <div className="font-mono text-[8px] opacity-60 mb-1">GRUPO {groupName}</div>
                                    {[t1, t2].map((t, i) => t && (
                                        <div key={i} className="flex items-center gap-1 mb-0.5">
                                            <span className="font-mono text-[8px] opacity-50 w-3">{i + 1}°</span>
                                            {t.flag_url && <img src={t.flag_url} alt="" className="h-2.5 w-3.5 object-cover" />}
                                            <span className="font-display text-[9px] leading-none truncate">{(t.fifa_code ?? t.name).toUpperCase()}</span>
                                        </div>
                                    ))}
                                </div>
                            );
                        })
                    }
                </div>

                {/* 8 mejores terceros */}
                {bestThirds.length > 0 && (
                    <div className="border-t border-cream/20 pt-2.5">
                        <div className="font-mono text-[8px] opacity-60 mb-1.5">8 MEJORES TERCEROS</div>
                        <div className="flex flex-wrap gap-1">
                            {bestThirds.map(c => {
                                const fix = fixtures.find(f => f.home_team?.id === c.team_id || f.away_team?.id === c.team_id);
                                const t   = fix?.home_team?.id === c.team_id ? fix?.home_team : fix?.away_team;
                                return t ? (
                                    <div key={c.team_id} className="flex items-center gap-1 bg-white/10 px-1.5 py-0.5 border border-cream/20">
                                        {t.flag_url && <img src={t.flag_url} alt="" className="h-2.5 w-3.5 object-cover" />}
                                        <span className="font-display text-[9px]">{(t.fifa_code ?? t.name).toUpperCase()}</span>
                                        <span className="font-mono text-[7px] opacity-50">({c.group})</span>
                                    </div>
                                ) : null;
                            })}
                        </div>
                    </div>
                )}

                <div className="mt-3 font-mono text-[9px] opacity-60 leading-[1.4]">
                    Estos se guardarán cuando confirmes tus predicciones →
                </div>
            </div>
        </div>
    );
})()}
```

- [ ] **Step 4: Verificar en el browser que el panel aparece al completar todos los partidos de un grupo de prueba**

```bash
./vendor/bin/sail pnpm run dev
```

Navegar a la pantalla de predicciones de fase de grupos, completar todos los partidos de un grupo de prueba y verificar que el panel TUS 32 CLASIFICADOS aparece.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Predictions/Round.jsx
git commit -m "feat: Round.jsx simula 32 clasificados y los envía al guardar fase de grupos"
```

---

## Task 7: Frontend — `Receipt.jsx` — sección de clasificados con ✓/✗

**Files:**
- Modify: `resources/js/Pages/Predictions/Receipt.jsx`

Mostrar la lista de 32 equipos predichos. Cuando `isFinalized`, marcar con ✓ (verde) o ✗ (rojo) según si el equipo realmente clasificó.

- [ ] **Step 1: Actualizar `Receipt.jsx`**

Reemplaza el bloque `{/* Bloque de clasificados */}` existente con esta sección:

```jsx
{/* Bloque de clasificados — solo si hay predicted_classifiers */}
{classifiers && classifiers.length > 0 && (() => {
    const realIds = new Set(realClassifierIds ?? []);

    // Agrupar por grupo
    const byGroup = {};
    classifiers.forEach(c => {
        if (!byGroup[c.group]) byGroup[c.group] = [];
        byGroup[c.group].push(c);
    });
    const bestThirds = classifiers.filter(c => c.position === 3);

    return (
        <div className="mx-[18px] my-3 border-[2.5px] border-ink overflow-hidden"
             style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
            {/* Header */}
            <div className="bg-navy text-cream px-3.5 py-2.5 flex items-center justify-between">
                <div>
                    <div className="font-mono text-[8px] tracking-[.1em] opacity-70">FASE DE GRUPOS</div>
                    <div className="font-display text-[15px] leading-none mt-0.5">TUS 32 CLASIFICADOS</div>
                </div>
                {isFinalized && (
                    <div className="font-mono text-[8px] opacity-70 text-right leading-[1.4]">
                        <div className="text-pop-teal font-bold">
                            {classifiers.filter(c => realIds.has(c.team_id)).length} acertados
                        </div>
                        <div className="opacity-60">de {classifiers.length}</div>
                    </div>
                )}
            </div>

            {/* Grid por grupo */}
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
                                                 hit === true  ? 'opacity-100' :
                                                 hit === false ? 'opacity-40'  : '',
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

                {/* 8 mejores terceros */}
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
                                             hit === false ? 'bg-red/5 opacity-40' : 'bg-black/5',
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
```

Actualiza también las props que recibe el componente:

```jsx
export default function Receipt({ round, fixtures, predictions, submission, isFinalized, classifiers, realClassifierIds }) {
```

- [ ] **Step 2: Verificar en el browser que el comprobante muestra los clasificados**

Con Vite corriendo, navegar al comprobante de la fase de grupos (estado submitted). Verificar que aparece el panel con los 32 equipos. Si la ronda está locked, verificar que aparecen los ✓/✗.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Predictions/Receipt.jsx
git commit -m "feat: Receipt.jsx muestra los 32 clasificados predichos con acertados/fallados"
```

---

## Task 8: Correr todos los tests y commit final

- [ ] **Step 1: Correr toda la suite**

```bash
./vendor/bin/sail test
```

Esperado: todos los tests pasan (mismo número que antes + los tests nuevos).

- [ ] **Step 2: Si hay fallos, diagnosticar y corregir**

Revisar el output. Problemas comunes:
- Tests de `CalculateClassifierPointsTest` que usaban `buildGroupTable` privado directamente → ya no accesible, reescribir usando el servicio público
- Tests de receipt que no pasan `classifiers` → el controller ahora retorna `null` cuando no hay classifiers, verificar que el prop se maneja bien en el frontend

- [ ] **Step 3: Commit de cierre**

```bash
git add -A
git commit -m "test: suite completa post-feature classifiers — todos los tests pasan"
```

---

## Self-Review

### Spec coverage

| Requisito | Task |
|---|---|
| Guardar 32 clasificados en `prediction_submissions` | Task 1, 4 |
| Simulación frontend de tabla de posiciones por grupo | Ya existe (`simulateStandings`), Task 6 agrega `simulateAllGroups` |
| 8 mejores terceros con mismo criterio FIFA (pts → GD → GF) | Task 2 (`GroupStageClassifierService`) + Task 6 |
| Se guarda solo cuando todos los 72 partidos tienen predicción | Task 4 (`allCovered` check) |
| Un solo botón "GUARDAR MIS GOLES" (sin submit separado) | Task 4 (remove submit route) |
| Comprobante muestra los 32 clasificados predichos | Task 5, 7 |
| Comprobante muestra ✓/✗ cuando fase finalizada | Task 5, 7 |
| `CalculateClassifierPoints` usa el JSON guardado (auditable) | Task 3 |
| Fallback a derivación para submissions antiguas sin classifiers | Task 3 |

### Notas importantes para el implementador

1. La función `simulateAllGroups` en el frontend llama a `simulateStandings` que fue definida en el mismo archivo en una sesión anterior. Verificar que `simulateStandings` está presente antes de implementar Task 6.

2. En Task 4, `allCovered` verifica que los IDs en el payload cubren todos los fixtures del round. Para la fase de grupos con 72 partidos esto es estricto — si el usuario solo mandó 71, queda en draft. El frontend previene esto solo mostrando `simulateAllGroups` cuando `filledCount === totalFixtures`.

3. El `submit` method en `PredictionController` se deja en el archivo (no se elimina del PHP) pero se quita de `routes/web.php`. Esto permite tests de regresión si fuera necesario.

4. El factory `PredictionSubmissionFactory` necesita actualizar `definition()` para incluir `predicted_classifiers: null` como default si los tests fallan por ese campo.
