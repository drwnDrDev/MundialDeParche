# Special Predictions UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rediseñar `Predictions/Special.jsx` al design system pop-art con picker visual de equipos, dropdown agrupado de goleador con opción custom (que crea un `Player` real via `firstOrCreate`), y modo read-only con puntos ganados cuando `is_locked`.

**Architecture:** El backend agrega eager-loading de relaciones en `show()` y maneja dos paths en `save()` (player existente o custom → `Player::firstOrCreate`). El frontend reemplaza `AuthenticatedLayout` + selects genéricos con `MobileShell` + `TabBar` + tres componentes inline: `TeamPickerGrid`, `TeamCard`, y `GoalScorerPicker`. No hay nuevas columnas en la BD.

**Tech Stack:** Laravel 11, React 18, Inertia.js v2, Tailwind CSS, Pest v3

---

## File Map

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `app/Http/Controllers/SpecialPredictionController.php` | Modify | Eager-load relaciones en `show()`; path custom en `save()` |
| `tests/Feature/SpecialPredictionControllerTest.php` | Modify | Agregar tests para custom player |
| `resources/js/Pages/Predictions/Special.jsx` | Rewrite | UI pop-art completa |

---

## Task 1: Backend — eager-load relaciones en show()

**Files:**
- Modify: `app/Http/Controllers/SpecialPredictionController.php`
- Test: `tests/Feature/SpecialPredictionControllerTest.php`

- [ ] **Step 1: Agregar test que verifica relaciones en show()**

Abrir `tests/Feature/SpecialPredictionControllerTest.php` y reemplazar el test `shows the special predictions form` con:

```php
it('shows the special predictions form with relations', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create(['team_id' => $champ->id]);

    SpecialPrediction::create([
        'user_id'              => $this->user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
        'is_locked'            => false,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/predictions/special');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Special')
        ->has('teams', 2)
        ->has('players', 1)
        ->where('special.champion_team_id', $champ->id)
        ->where('special.champion.id', $champ->id)
        ->where('special.top_scorer.id', $scorer->id)
        ->where('special.top_scorer.team.id', $champ->id)
    );
});
```

- [ ] **Step 2: Correr el test para verificar que falla**

```bash
./vendor/bin/sail test --filter "shows the special predictions form with relations"
```

Expected: FAIL — `special.champion` key no existe en la respuesta.

- [ ] **Step 3: Actualizar show() con eager-loading**

En `app/Http/Controllers/SpecialPredictionController.php`, reemplazar el método `show()`:

```php
public function show(): Response
{
    $special = SpecialPrediction::with(['champion', 'runnerUp', 'topScorer.team'])
        ->where('user_id', Auth::id())
        ->first();

    return Inertia::render('Predictions/Special', [
        'special' => $special,
        'teams'   => Team::with('group')->orderBy('name')->get(),
        'players' => Player::with('team')->orderBy('name')->get(),
    ]);
}
```

- [ ] **Step 4: Correr el test para verificar que pasa**

```bash
./vendor/bin/sail test --filter "shows the special predictions form with relations"
```

Expected: PASS

- [ ] **Step 5: Correr todos los tests para verificar no hay regresiones**

```bash
./vendor/bin/sail test tests/Feature/SpecialPredictionControllerTest.php
```

Expected: todos pasan (el test original renombrado + los demás existentes).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/SpecialPredictionController.php tests/Feature/SpecialPredictionControllerTest.php
git commit -m "feat: eager-load champion/runnerUp/topScorer relations in special predictions show()"
```

---

## Task 2: Backend — path de goleador custom en save()

**Files:**
- Modify: `app/Http/Controllers/SpecialPredictionController.php`
- Test: `tests/Feature/SpecialPredictionControllerTest.php`

- [ ] **Step 1: Agregar tests para el path custom**

Agregar al final de `tests/Feature/SpecialPredictionControllerTest.php`:

```php
it('saves with custom player via firstOrCreate', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $team   = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'          => $champ->id,
        'runner_up_team_id'         => $runner->id,
        'top_scorer_custom_name'    => 'Lionel Messi',
        'top_scorer_custom_team_id' => $team->id,
    ])->assertRedirect();

    $player = Player::where('name', 'Lionel Messi')->where('team_id', $team->id)->first();
    expect($player)->not->toBeNull();

    $special = SpecialPrediction::where('user_id', $this->user->id)->first();
    expect($special->top_scorer_player_id)->toBe($player->id);
});

it('reuses existing player on duplicate custom save', function () {
    $group    = Group::factory()->create(['name' => 'A']);
    $champ    = Team::factory()->create(['group_id' => $group->id]);
    $runner   = Team::factory()->create(['group_id' => $group->id]);
    $team     = Team::factory()->create(['group_id' => $group->id]);
    $existing = Player::factory()->create(['name' => 'Lionel Messi', 'team_id' => $team->id]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'          => $champ->id,
        'runner_up_team_id'         => $runner->id,
        'top_scorer_custom_name'    => 'Lionel Messi',
        'top_scorer_custom_team_id' => $team->id,
    ])->assertRedirect();

    expect(Player::count())->toBe(1);
    $special = SpecialPrediction::where('user_id', $this->user->id)->first();
    expect($special->top_scorer_player_id)->toBe($existing->id);
});

it('rejects custom player without team', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'       => $champ->id,
        'runner_up_team_id'      => $runner->id,
        'top_scorer_custom_name' => 'Some Player',
    ])->assertSessionHasErrors('top_scorer_custom_team_id');
});

it('rejects save with neither player id nor custom name', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'  => $champ->id,
        'runner_up_team_id' => $runner->id,
    ])->assertSessionHasErrors('top_scorer_player_id');
});
```

- [ ] **Step 2: Correr los nuevos tests para verificar que fallan**

```bash
./vendor/bin/sail test --filter "saves with custom player"
```

Expected: FAIL — validation no conoce `top_scorer_custom_name`.

- [ ] **Step 3: Actualizar save() con el path custom**

Reemplazar el método `save()` completo en `app/Http/Controllers/SpecialPredictionController.php`:

```php
public function save(Request $request): RedirectResponse
{
    $special = SpecialPrediction::where('user_id', Auth::id())->first();

    if ($special && $special->is_locked) {
        return back()->with('status', 'Tus predicciones especiales están bloqueadas.');
    }

    $isCustom = $request->filled('top_scorer_custom_name');

    $rules = [
        'champion_team_id'  => ['required', 'exists:teams,id'],
        'runner_up_team_id' => ['required', 'exists:teams,id', 'different:champion_team_id'],
    ];

    if ($isCustom) {
        $rules['top_scorer_custom_name']    = ['required', 'string', 'max:100'];
        $rules['top_scorer_custom_team_id'] = ['required', 'exists:teams,id'];
    } else {
        $rules['top_scorer_player_id'] = ['required', 'exists:players,id'];
    }

    $data = $request->validate($rules);

    if ($isCustom) {
        $player = Player::firstOrCreate([
            'name'    => $data['top_scorer_custom_name'],
            'team_id' => (int) $data['top_scorer_custom_team_id'],
        ]);
        $topScorerId = $player->id;
    } else {
        $topScorerId = (int) $data['top_scorer_player_id'];
    }

    SpecialPrediction::updateOrCreate(
        ['user_id' => Auth::id()],
        [
            'champion_team_id'     => (int) $data['champion_team_id'],
            'runner_up_team_id'    => (int) $data['runner_up_team_id'],
            'top_scorer_player_id' => $topScorerId,
        ]
    );

    return back()->with('status', 'Predicciones especiales guardadas.');
}
```

- [ ] **Step 4: Correr todos los tests del archivo**

```bash
./vendor/bin/sail test tests/Feature/SpecialPredictionControllerTest.php
```

Expected: todos pasan.

- [ ] **Step 5: Correr suite completa para verificar no hay regresiones**

```bash
./vendor/bin/sail test
```

Expected: mismo número de tests que antes + 4 nuevos = todos pasan.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/SpecialPredictionController.php tests/Feature/SpecialPredictionControllerTest.php
git commit -m "feat: add custom player path to special predictions save via firstOrCreate"
```

---

## Task 3: Frontend — reescribir Special.jsx

**Files:**
- Rewrite: `resources/js/Pages/Predictions/Special.jsx`

- [ ] **Step 1: Reescribir Special.jsx completo**

Reemplazar todo el contenido de `resources/js/Pages/Predictions/Special.jsx` con:

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import MobileShell from '@/Components/MobileShell';
import TabBar from '@/Components/composed/TabBar';
import PtsChip from '@/Components/ui/PtsChip';

// ── TeamCard ──────────────────────────────────────────────────────────────────

function TeamCard({ team, selected, disabled, onSelect }) {
    return (
        <button
            onClick={onSelect}
            disabled={disabled}
            className={[
                'relative border-[2px] border-ink overflow-hidden text-center w-full',
                selected
                    ? 'border-[3px] border-pop-yel'
                    : disabled
                        ? 'opacity-40 cursor-not-allowed'
                        : 'shadow-pop-sm',
            ].join(' ')}
            style={selected ? { boxShadow: '0 0 0 1px var(--c-ink)' } : undefined}
        >
            {team.flag_url
                ? <img src={team.flag_url} alt={team.name} className="w-full aspect-[4/3] object-cover" />
                : <div className="w-full aspect-[4/3] bg-black/10" />
            }
            <div className="font-display text-[9px] leading-tight px-0.5 py-0.5 truncate bg-cream">
                {(team.fifa_code ?? team.name).toUpperCase()}
            </div>
            {selected && (
                <div className="absolute top-0.5 right-0.5 bg-ink text-pop-yel font-display text-[10px] w-5 h-5 flex items-center justify-center border border-pop-yel">
                    ✓
                </div>
            )}
        </button>
    );
}

// ── TeamPickerGrid ────────────────────────────────────────────────────────────

function TeamPickerGrid({ teams, selectedId, disabledId, onSelect, locked }) {
    return (
        <div className="grid grid-cols-4 gap-1.5">
            {teams.map(team => {
                const isSelected = String(team.id) === String(selectedId);
                const isDisabled = locked
                    ? !isSelected
                    : String(team.id) === String(disabledId);
                return (
                    <TeamCard
                        key={team.id}
                        team={team}
                        selected={isSelected}
                        disabled={isDisabled || locked}
                        onSelect={() => !locked && onSelect(String(team.id))}
                    />
                );
            })}
        </div>
    );
}

// ── GoalScorerPicker ──────────────────────────────────────────────────────────

function GoalScorerPicker({ playersByTeam, teams, playerId, customName, customTeamId, onChangeId, onChangeCustomName, onChangeCustomTeamId }) {
    const isCustom = playerId === '__custom__';

    return (
        <div className="space-y-2">
            <div className="relative">
                <select
                    value={playerId}
                    onChange={e => onChangeId(e.target.value)}
                    className="w-full border-[2px] border-ink bg-cream font-mono text-[12px] px-2.5 py-2 appearance-none pr-8"
                    style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                >
                    <option value="">— Elige un jugador —</option>
                    {Object.entries(playersByTeam)
                        .sort(([a], [b]) => a.localeCompare(b))
                        .map(([teamName, players]) => (
                            <optgroup key={teamName} label={teamName.toUpperCase()}>
                                {players.map(p => (
                                    <option key={p.id} value={String(p.id)}>{p.name}</option>
                                ))}
                            </optgroup>
                        ))
                    }
                    <option value="__custom__">➕ Otro jugador...</option>
                </select>
                <div className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 font-display text-[10px]">▼</div>
            </div>

            {isCustom && (
                <div className="space-y-1.5 border-[2px] border-dashed border-ink p-2.5"
                     style={{ background: 'rgba(255,210,63,.1)' }}>
                    <div className="font-mono text-[9px] tracking-[.08em] opacity-60 mb-1">NUEVO JUGADOR</div>
                    <input
                        type="text"
                        value={customName}
                        onChange={e => onChangeCustomName(e.target.value)}
                        placeholder="Nombre del jugador"
                        className="w-full border-[2px] border-ink bg-cream font-mono text-[12px] px-2.5 py-1.5"
                        style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                    />
                    <div className="relative">
                        <select
                            value={customTeamId}
                            onChange={e => onChangeCustomTeamId(e.target.value)}
                            className="w-full border-[2px] border-ink bg-cream font-mono text-[12px] px-2.5 py-1.5 appearance-none pr-8"
                            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                        >
                            <option value="">— Equipo —</option>
                            {teams.map(t => (
                                <option key={t.id} value={String(t.id)}>{t.name}</option>
                            ))}
                        </select>
                        <div className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 font-display text-[10px]">▼</div>
                    </div>
                </div>
            )}
        </div>
    );
}

// ── SectionHeader ─────────────────────────────────────────────────────────────

function SectionHeader({ title, subtitle, pts, ptsType }) {
    return (
        <div className="flex items-start justify-between mb-3">
            <div>
                <div className="font-display text-[15px] leading-tight">{title}</div>
                <div className="font-mono text-[10px] opacity-55 mt-0.5">{subtitle}</div>
            </div>
            <div className="flex-shrink-0 ml-2 mt-0.5">
                <span
                    className="font-display text-[13px] px-2 py-0.5 border-[2px] border-ink text-white"
                    style={{ background: ptsType === 'champion' ? 'var(--c-red)' : ptsType === 'runner' ? 'var(--c-teal)' : 'var(--c-yel)', color: ptsType === 'scorer' ? 'var(--c-ink)' : '#fff' }}
                >
                    +{pts} PTS
                </span>
            </div>
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────────────

export default function Special({ special, teams, players }) {
    const { auth } = usePage().props;
    const flash     = usePage().props.flash ?? {};
    const isLocked  = special?.is_locked ?? false;
    const totalPts  = auth.user?.total_points ?? 0;
    const isActivated = auth.user?.is_activated ?? false;

    const [championId,      setChampionId]      = useState(special?.champion_team_id?.toString()      ?? '');
    const [runnerUpId,      setRunnerUpId]       = useState(special?.runner_up_team_id?.toString()     ?? '');
    const [scorerPlayerId,  setScorerPlayerId]   = useState(special?.top_scorer_player_id?.toString() ?? '');
    const [customName,      setCustomName]       = useState('');
    const [customTeamId,    setCustomTeamId]     = useState('');
    const [processing,      setProcessing]       = useState(false);

    const isCustomScorer = scorerPlayerId === '__custom__';

    const scorerFilled = isCustomScorer
        ? (customName.trim() !== '' && customTeamId !== '')
        : scorerPlayerId !== '';

    const allFilled = championId !== '' && runnerUpId !== '' && scorerFilled;
    const filledCount = [
        championId !== '' ? 1 : 0,
        runnerUpId !== '' ? 1 : 0,
        scorerFilled ? 1 : 0,
    ].reduce((a, b) => a + b, 0);

    // Group players by team name for <optgroup>
    const playersByTeam = players.reduce((acc, p) => {
        const key = p.team?.name ?? '?';
        if (!acc[key]) acc[key] = [];
        acc[key].push(p);
        return acc;
    }, {});

    function handleSave() {
        if (!allFilled || processing) return;
        setProcessing(true);
        const payload = {
            champion_team_id:  championId,
            runner_up_team_id: runnerUpId,
        };
        if (isCustomScorer) {
            payload.top_scorer_custom_name    = customName.trim();
            payload.top_scorer_custom_team_id = customTeamId;
        } else {
            payload.top_scorer_player_id = scorerPlayerId;
        }
        router.post(route('predictions.special.save'), payload, {
            onFinish: () => setProcessing(false),
        });
    }

    // Locked scorer display (relaciones Eloquent → snake_case en JSON)
    const lockedScorerName = special?.top_scorer?.name ?? '—';
    const lockedScorerTeam = special?.top_scorer?.team?.name ?? '';

    const ptsTotal = (special?.pts_champion ?? 0) + (special?.pts_runner_up ?? 0) + (special?.pts_top_scorer ?? 0);

    return (
        <>
            <Head title="Especiales · Mundial de Parche" />
            <MobileShell>
                {/* Header */}
                <div className="px-[18px] pt-4 pb-0">
                    <div className="flex items-start justify-between">
                        <div>
                            <div className="font-mono text-[9px] tracking-[.1em] opacity-50">MUNDIAL 2026</div>
                            <div className="font-display text-[32px] leading-none mt-0.5">MIS ESPECIALES</div>
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

                {/* Status cromo */}
                <div className="px-[18px] pt-3">
                    <div
                        className="border-[3px] border-ink bg-navy text-cream p-[10px_12px] relative overflow-hidden"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="font-mono text-[10px] text-pop-yel tracking-[.12em]">PREDICCIONES ESPECIALES</div>
                                <div className="font-mono text-[10px] opacity-60 mt-0.5">Campeón · Sub-campeón · Goleador</div>
                            </div>
                            {isLocked ? (
                                <span className="bg-pop-teal text-ink border border-ink px-2 py-0.5 font-mono text-[8px] font-bold tracking-[.06em] flex-shrink-0">
                                    🔒 BLOQUEADO
                                </span>
                            ) : (
                                <span className="bg-pop-yel text-ink border border-ink px-2 py-0.5 font-mono text-[8px] font-bold tracking-[.06em] flex-shrink-0">
                                    ABIERTO
                                </span>
                            )}
                        </div>
                        <div className="flex gap-1.5 mt-2.5">
                            <div className="flex-1 bg-white/10 border border-cream/20 px-2 py-1 text-center">
                                <div className="font-display text-[14px] leading-none">+30</div>
                                <div className="font-mono text-[8px] opacity-60 mt-0.5">CAMPEÓN</div>
                            </div>
                            <div className="flex-1 bg-white/10 border border-cream/20 px-2 py-1 text-center">
                                <div className="font-display text-[14px] leading-none">+10</div>
                                <div className="font-mono text-[8px] opacity-60 mt-0.5">SUB</div>
                            </div>
                            <div className="flex-1 bg-white/10 border border-cream/20 px-2 py-1 text-center">
                                <div className="font-display text-[14px] leading-none">+15</div>
                                <div className="font-mono text-[8px] opacity-60 mt-0.5">GOLEADOR</div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Flash status */}
                {flash.status && (
                    <div className="mx-[18px] mt-3 bg-pop-teal text-white border-[2px] border-ink px-3 py-2 font-mono text-[11px] font-bold">
                        ✓ {flash.status}
                    </div>
                )}

                <div className="px-[18px] pt-5 pb-[140px] flex flex-col gap-6">

                    {/* ── Sección 1: Campeón ── */}
                    <div>
                        <div className="h-[3px] bg-ink mb-4" />
                        <SectionHeader
                            title="CAMPEÓN DEL MUNDO"
                            subtitle="Elige el equipo que ganará el torneo"
                            pts={30}
                            ptsType="champion"
                        />
                        {isLocked && special?.pts_champion !== undefined && special.pts_champion !== null && (
                            <div className="mb-2 flex items-center gap-2">
                                <span className="font-mono text-[10px] opacity-60">PUNTOS GANADOS:</span>
                                <PtsChip pts={special.pts_champion} type="exact" />
                            </div>
                        )}
                        <TeamPickerGrid
                            teams={teams}
                            selectedId={championId}
                            disabledId={runnerUpId}
                            onSelect={setChampionId}
                            locked={isLocked || !isActivated}
                        />
                    </div>

                    {/* ── Sección 2: Sub-campeón ── */}
                    <div>
                        <div className="h-[3px] bg-ink mb-4" />
                        <SectionHeader
                            title="SUB-CAMPEÓN"
                            subtitle="Elige el finalista que perderá"
                            pts={10}
                            ptsType="runner"
                        />
                        {isLocked && special?.pts_runner_up !== undefined && special.pts_runner_up !== null && (
                            <div className="mb-2 flex items-center gap-2">
                                <span className="font-mono text-[10px] opacity-60">PUNTOS GANADOS:</span>
                                <PtsChip pts={special.pts_runner_up} type="result" />
                            </div>
                        )}
                        <TeamPickerGrid
                            teams={teams}
                            selectedId={runnerUpId}
                            disabledId={championId}
                            onSelect={setRunnerUpId}
                            locked={isLocked || !isActivated}
                        />
                    </div>

                    {/* ── Sección 3: Goleador ── */}
                    <div>
                        <div className="h-[3px] bg-ink mb-4" />
                        <SectionHeader
                            title="GOLEADOR DEL TORNEO"
                            subtitle="Elige el jugador que más goles meta"
                            pts={15}
                            ptsType="scorer"
                        />
                        {isLocked ? (
                            <div>
                                {special?.pts_top_scorer !== undefined && special.pts_top_scorer !== null && (
                                    <div className="mb-2 flex items-center gap-2">
                                        <span className="font-mono text-[10px] opacity-60">PUNTOS GANADOS:</span>
                                        <PtsChip pts={special.pts_top_scorer} type="classifier" />
                                    </div>
                                )}
                                <div
                                    className="border-[2px] border-ink bg-white px-3 py-2.5 flex items-center gap-3"
                                    style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                                >
                                    <div className="font-display text-[18px]">⚽</div>
                                    <div>
                                        <div className="font-display text-[14px] leading-tight">{lockedScorerName}</div>
                                        {lockedScorerTeam && (
                                            <div className="font-mono text-[10px] opacity-55 mt-0.5">{lockedScorerTeam}</div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <GoalScorerPicker
                                playersByTeam={playersByTeam}
                                teams={teams}
                                playerId={scorerPlayerId}
                                customName={customName}
                                customTeamId={customTeamId}
                                onChangeId={setScorerPlayerId}
                                onChangeCustomName={setCustomName}
                                onChangeCustomTeamId={setCustomTeamId}
                            />
                        )}
                    </div>

                    {/* ── Resumen puntos (modo locked) ── */}
                    {isLocked && (
                        <div>
                            <div className="h-[3px] bg-ink mb-4" />
                            <div
                                className="border-[3px] border-ink bg-navy text-cream p-4"
                                style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                            >
                                <div className="font-display text-[16px] leading-none mb-3">PUNTOS ESPECIALES</div>
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <span className="font-mono text-[11px] opacity-70">CAMPEÓN</span>
                                        <PtsChip pts={special?.pts_champion ?? 0} type="exact" />
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="font-mono text-[11px] opacity-70">SUB-CAMPEÓN</span>
                                        <PtsChip pts={special?.pts_runner_up ?? 0} type="result" />
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="font-mono text-[11px] opacity-70">GOLEADOR</span>
                                        <PtsChip pts={special?.pts_top_scorer ?? 0} type="classifier" />
                                    </div>
                                    <div className="h-[1px] bg-cream/20 my-1" />
                                    <div className="flex items-center justify-between">
                                        <span className="font-display text-[13px]">TOTAL</span>
                                        <div
                                            className="bg-pop-yel text-ink border-[2px] border-ink px-2.5 py-0.5 font-display text-[16px]"
                                            style={{ boxShadow: '2px 2px 0 rgba(0,0,0,.3)' }}
                                        >
                                            +{ptsTotal}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </MobileShell>

            {/* Sticky CTA — solo en modo editable */}
            {!isLocked && (
                <div className="fixed bottom-[72px] left-0 right-0 bg-cream border-t-[3px] border-ink px-[14px] py-2.5 flex items-center gap-3 z-40">
                    <div className="flex-1">
                        <div className="font-mono text-[10px] opacity-70 tracking-[.08em]">
                            {allFilled ? '✓ LISTO PARA GUARDAR' : `FALTAN ${3 - filledCount} PREDICCIONES`}
                        </div>
                        <div className="font-display text-[14px] leading-none mt-0.5 opacity-60">
                            {filledCount} / 3
                        </div>
                    </div>
                    {isActivated ? (
                        <button
                            onClick={handleSave}
                            disabled={!allFilled || processing}
                            className="py-3 px-4 bg-pop-red text-white font-display text-[13px] border-[2.5px] border-ink disabled:opacity-40"
                            style={{ boxShadow: allFilled ? '3px 3px 0 var(--c-ink)' : 'none' }}
                        >
                            {processing ? 'GUARDANDO...' : 'GUARDAR →'}
                        </button>
                    ) : (
                        <a
                            href={route('activation')}
                            className="py-3 px-4 bg-ink text-cream font-display text-[13px] border-[2.5px] border-ink"
                            style={{ boxShadow: '3px 3px 0 var(--c-yel)' }}
                        >
                            ACTIVAR CUENTA →
                        </a>
                    )}
                </div>
            )}

            <TabBar active="home" />
        </>
    );
}
```

- [ ] **Step 2: Verificar que la página carga sin errores de consola**

```bash
./vendor/bin/sail pnpm run build
```

Expected: build exitoso sin errores.

- [ ] **Step 3: Correr tests de feature para Special**

```bash
./vendor/bin/sail test tests/Feature/SpecialPredictionControllerTest.php
```

Expected: todos pasan.

- [ ] **Step 4: Correr suite completa**

```bash
./vendor/bin/sail test
```

Expected: todos los tests pasan.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Predictions/Special.jsx
git commit -m "feat: redesign Special.jsx with pop-art design system and custom player picker"
```
