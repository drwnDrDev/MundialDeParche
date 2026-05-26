# Paso 5: Screens/Views — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Portar 7 pantallas al design system pop-art usando los compuestos del Paso 4, conservando toda la lógica de negocio existente.

**Architecture:** Shell compartido `MobileShell` reemplaza `AuthenticatedLayout`; `TabBar` maneja navegación Inertia internamente. Pantallas nuevas: `Matches`, `Splash`, `Predictions/Locked`. Pantallas refactorizadas: `Ranking`, `Chat`, `Auth/Login`, `Predictions/Round`. Nuevo `MatchesController` con ruta `/matches`.

**Tech Stack:** React 18, Inertia.js v2, Tailwind CSS v3 (tokens ya configurados), Laravel 11, Pest v3. CSS utilities (`scanlines`, `speedlines`, `halftone-*`) ya existen en `resources/css/app.css`. pnpm.

**Spec:** `docs/superpowers/specs/2026-05-26-paso5-screens-design.md`

---

## File Map

| Acción | Archivo | Responsabilidad |
|---|---|---|
| Crear | `resources/js/Components/MobileShell.jsx` | Shell base para todas las pantallas |
| Modificar | `resources/js/Components/composed/TabBar.jsx` | Agregar navegación Inertia |
| Crear | `app/Http/Controllers/MatchesController.php` | Props para pantalla Partidos |
| Modificar | `app/Http/Controllers/RankingController.php` | Agregar pozo, avatarColor, posición correcta |
| Modificar | `app/Http/Controllers/ChatController.php` | Agregar liveMatch prop |
| Modificar | `app/Http/Controllers/PredictionController.php` | Redirect a Locked si !is_open |
| Modificar | `routes/web.php` | Ruta Splash en `/`, ruta `/matches` |
| Crear | `tests/Feature/MatchesTest.php` | Feature tests del nuevo controller |
| Crear | `resources/js/Pages/Splash.jsx` | Pantalla splash pública |
| Modificar | `resources/js/Pages/Auth/Login.jsx` | Refactor UI (mantener lógica Breeze) |
| Modificar | `resources/js/Pages/Ranking.jsx` | Refactor UI completo |
| Modificar | `resources/js/Pages/Chat.jsx` | Refactor UI (mantener Echo) |
| Crear | `resources/js/Pages/Matches.jsx` | Pantalla partidos nueva |
| Crear | `resources/js/Pages/Predictions/Locked.jsx` | Pantalla fase bloqueada |
| Modificar | `resources/js/Pages/Predictions/Round.jsx` | Refactor UI (mantener form logic) |
| Copiar | `public/assets/fifa_cover.png`, `public/assets/wc26_logo.avif` | Assets del handoff |

---

## Task 1: MobileShell + TabBar navigation

**Files:**
- Create: `resources/js/Components/MobileShell.jsx`
- Modify: `resources/js/Components/composed/TabBar.jsx`

- [ ] **Step 1.1: Crear MobileShell**

Crear `resources/js/Components/MobileShell.jsx`:

```jsx
export default function MobileShell({ children }) {
    return (
        <div className="bg-cream min-h-screen overflow-x-hidden pb-28 relative">
            {children}
        </div>
    );
}
```

- [ ] **Step 1.2: Actualizar TabBar con navegación Inertia**

Reemplazar el contenido completo de `resources/js/Components/composed/TabBar.jsx`:

```jsx
import { router } from '@inertiajs/react';
import { NavStadium, NavVS, NavTrophy, NavFire } from '@/Components/icons/NavIcons';

const TABS = [
    { id: 'home',    label: 'PARCHE',   Icon: NavStadium, url: '/dashboard' },
    { id: 'matches', label: 'PARTIDOS', Icon: NavVS,       url: '/matches'   },
    { id: 'rank',    label: 'RANKING',  Icon: NavTrophy,   url: '/ranking'   },
    { id: 'chat',    label: 'CHAT',     Icon: NavFire,     url: '/chat'      },
];

export default function TabBar({ active = 'home' }) {
    return (
        <nav className="fixed bottom-0 left-0 right-0 bg-cream border-t-[3px] border-ink px-3 pt-2.5 pb-[22px] flex justify-between gap-1.5 z-50">
            {TABS.map((tab) => {
                const isActive = tab.id === active;
                return (
                    <button
                        key={tab.id}
                        aria-label={tab.label}
                        aria-current={isActive ? 'page' : undefined}
                        onClick={() => { if (!isActive) router.visit(tab.url); }}
                        className={[
                            'flex-1 flex items-center justify-center py-2 px-1 border-[2.5px]',
                            isActive
                                ? 'bg-ink border-ink shadow-[3px_3px_0_var(--c-red)]'
                                : 'bg-transparent border-transparent',
                        ].join(' ')}
                    >
                        <tab.Icon active={isActive} />
                    </button>
                );
            })}
        </nav>
    );
}
```

- [ ] **Step 1.3: Copiar assets del handoff a public/assets/**

```bash
mkdir -p /home/dwndz/Projects/PollaMundial/public/assets
cp "/mnt/c/Users/dwndz/OneDrive/Escritorio/Mundial de parche_/design_handoff_mundial_parche/assets/fifa_cover.png" \
   /home/dwndz/Projects/PollaMundial/public/assets/
cp "/mnt/c/Users/dwndz/OneDrive/Escritorio/Mundial de parche_/design_handoff_mundial_parche/assets/wc26_logo.avif" \
   /home/dwndz/Projects/PollaMundial/public/assets/
ls /home/dwndz/Projects/PollaMundial/public/assets/
```

Salida esperada: `fifa_cover.png  wc26_logo.avif`

- [ ] **Step 1.4: Verificar tests existentes no rompieron**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: todos los tests JS pasando (los composed tests del Paso 4).

- [ ] **Step 1.5: Commit**

```bash
git add resources/js/Components/MobileShell.jsx \
        resources/js/Components/composed/TabBar.jsx \
        public/assets/
git commit -m "feat: add MobileShell shell + Inertia navigation in TabBar"
```

---

## Task 2: MatchesController + ruta + feature test

**Files:**
- Create: `app/Http/Controllers/MatchesController.php`
- Create: `tests/Feature/MatchesTest.php`
- Modify: `routes/web.php`

- [ ] **Step 2.1: Escribir feature test**

Crear `tests/Feature/MatchesTest.php`:

```php
<?php

use App\Models\User;
use App\Models\Round;
use App\Models\Group;

it('authenticated user can view matches page', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->component('Matches')
            ->has('matchDays')
            ->has('groups')
            ->has('currentRound')
        );
});

it('guest is redirected from matches page', function () {
    $this->get('/matches')->assertRedirect('/login');
});
```

- [ ] **Step 2.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail test --filter MatchesTest
```

Salida esperada: FAIL — ruta `/matches` no existe.

- [ ] **Step 2.3: Agregar ruta /matches en web.php**

En `routes/web.php`, agregar dentro del grupo `Route::middleware('auth')` existente (junto a `/chat` y `/ranking`):

```php
use App\Http\Controllers\MatchesController;

// dentro del grupo middleware('auth'):
Route::get('/matches', [MatchesController::class, 'index'])->name('matches');
```

También agregar el `use` al principio del archivo junto a los otros imports.

- [ ] **Step 2.4: Crear MatchesController**

Crear `app/Http/Controllers/MatchesController.php`:

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
                'teams' => $this->buildStandings($g),
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
        $status = $f->status ?? ($f->home_score !== null ? 'ft' : 'upcoming');
        $pts    = $pred ? ($pred->pts_exact + $pred->pts_result) : null;

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
            'myPts'    => ($status === 'ft' && $pts > 0) ? $pts : null,
        ];
    }

    private function buildStandings(Group $group): array
    {
        $teams    = $group->teams;
        $fixtures = Fixture::where('group_id', $group->id)
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->get();

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

- [ ] **Step 2.5: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail test --filter MatchesTest
```

Salida esperada: 2 tests pasando.

- [ ] **Step 2.6: Correr suite completa para verificar no hay regresiones**

```bash
./vendor/bin/sail test
```

Salida esperada: todos los tests anteriores siguen pasando + 2 nuevos.

- [ ] **Step 2.7: Commit**

```bash
git add app/Http/Controllers/MatchesController.php \
        tests/Feature/MatchesTest.php \
        routes/web.php
git commit -m "feat: add MatchesController with calendar and standings data"
```

---

## Task 3: RankingController — agregar pozo, avatarColor, posición correcta

**Files:**
- Modify: `app/Http/Controllers/RankingController.php`

- [ ] **Step 3.1: Actualizar RankingController**

Reemplazar el contenido completo de `app/Http/Controllers/RankingController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(): Response
    {
        $avatarColors = ['yel', 'teal', 'red', 'cream'];

        $position = 0;
        $lastPts  = null;
        $counter  = 0;

        $users = User::where('is_active', true)
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
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'total_points'=> $user->total_points,
                    'position'    => $position,
                    'avatarColor' => $avatarColors[$user->id % 4],
                    'delta'       => '+0',
                ];
            });

        $activated = User::where('is_activated', true)->count();
        $total     = $activated * 50000;

        $fmt = fn ($n) => number_format($n / 1000, 0, '.', '.') . 'K';

        return Inertia::render('Ranking', [
            'users' => $users,
            'pozo'  => [
                'total'   => $fmt($total) ?: '0K',
                'players' => $activated,
                'prize1'  => $fmt((int) ($total * 0.70)),
                'prize2'  => $fmt((int) ($total * 0.30)),
            ],
        ]);
    }
}
```

- [ ] **Step 3.2: Correr tests**

```bash
./vendor/bin/sail test
```

Si algún test de Ranking falla porque ahora espera `position` como campo del array, actualizar el test para incluirlo. La salida esperada es que todos los tests sigan pasando.

- [ ] **Step 3.3: Commit**

```bash
git add app/Http/Controllers/RankingController.php
git commit -m "feat: update RankingController with pozo, avatarColor and tied positions"
```

---

## Task 4: ChatController + PredictionController — props adicionales

**Files:**
- Modify: `app/Http/Controllers/ChatController.php`
- Modify: `app/Http/Controllers/PredictionController.php`

- [ ] **Step 4.1: Agregar liveMatch a ChatController**

En `app/Http/Controllers/ChatController.php`, actualizar el método `index()` agregando el prop `liveMatch`:

```php
public function index(): Response
{
    $messages = Message::with('user:id,name,avatar')
        ->latest()
        ->limit(50)
        ->get()
        ->reverse()
        ->values();

    $liveMatch = \App\Models\Fixture::with(['homeTeam', 'awayTeam'])
        ->where('status', 'live')
        ->first();

    $liveMatchData = $liveMatch ? [
        'teamA'  => $liveMatch->homeTeam?->fifa_code ?? 'TBD',
        'teamB'  => $liveMatch->awayTeam?->fifa_code ?? 'TBD',
        'scoreA' => $liveMatch->home_score,
        'scoreB' => $liveMatch->away_score,
        'minute' => null,
    ] : null;

    return Inertia::render('Chat', [
        'messages'  => $messages,
        'liveMatch' => $liveMatchData,
    ]);
}
```

- [ ] **Step 4.2: Agregar redirect a Locked en PredictionController**

En `app/Http/Controllers/PredictionController.php`, en el método `show(Round $round)`, agregar al inicio del método (antes de la lógica existente):

```php
public function show(Round $round): Response|RedirectResponse
{
    if (!$round->is_open) {
        return Inertia::render('Predictions/Locked', [
            'roundName'  => $round->name,
            'roundOrder' => $round->order,
            'isLocked'   => $round->is_locked,
            'opensAt'    => null,
        ]);
    }

    // ... existing code below (if ($round->is_open) block becomes just the body)
```

**Nota:** el bloque `if ($round->is_open) { ... check unassigned ... }` que ya existe queda dentro del flujo normal (ya que ahora se llega aquí solo cuando `is_open` es true). Eliminar el `if ($round->is_open)` wrapper y dejar solo el cuerpo del check de unassigned.

- [ ] **Step 4.3: Correr tests**

```bash
./vendor/bin/sail test
```

Si algún test de Predictions usa un round con `is_open=false` para `show`, actualizarlo para usar `is_open=true` o para esperar el componente `Predictions/Locked`. La salida esperada es todos los tests pasando.

- [ ] **Step 4.4: Commit**

```bash
git add app/Http/Controllers/ChatController.php \
        app/Http/Controllers/PredictionController.php
git commit -m "feat: add liveMatch to ChatController, Locked redirect to PredictionController"
```

---

## Task 5: Ruta Splash en `/`

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 5.1: Actualizar ruta `/`**

En `routes/web.php`, reemplazar la ruta `/` actual:

```php
// Reemplazar:
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

// Por:
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Splash');
})->name('home');
```

También remover el `use Illuminate\Foundation\Application;` del imports si ya no se usa en ningún otro lugar del archivo.

- [ ] **Step 5.2: Correr tests**

```bash
./vendor/bin/sail test
```

Si hay un test que verifica que `/` renderiza `Welcome`, actualizarlo para esperar `Splash`. La salida esperada es todos los tests pasando.

- [ ] **Step 5.3: Commit**

```bash
git add routes/web.php
git commit -m "feat: update / route to render Splash for guests"
```

---

## Task 6: Splash page

**Files:**
- Create: `resources/js/Pages/Splash.jsx`

- [ ] **Step 6.1: Crear Splash.jsx**

Crear `resources/js/Pages/Splash.jsx`:

```jsx
import { Head, Link } from '@inertiajs/react';
import { Trophy, SoccerBall } from '@/Components/icons/football';

export default function Splash() {
    return (
        <>
            <Head title="Mundial de Parche" />
            <div className="bg-navy text-cream min-h-screen overflow-hidden relative">

                {/* Halftone cream overlay */}
                <div
                    className="absolute inset-0 pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(var(--c-cream) 1.4px, transparent 1.8px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.08,
                    }}
                />

                {/* Speedlines */}
                <div className="speedlines absolute inset-0 pointer-events-none" style={{ opacity: .22 }} />

                {/* FIFA cover circle */}
                <div className="absolute top-[70px] left-1/2 -translate-x-1/2">
                    <div
                        className="w-[360px] h-[360px] rounded-full border-[5px] border-ink overflow-hidden bg-cover bg-center"
                        style={{
                            backgroundImage: "url('/assets/fifa_cover.png')",
                            boxShadow: '8px 8px 0 var(--c-ink)',
                        }}
                    />
                </div>

                {/* Trophy top-left */}
                <div className="absolute top-[84px] left-[18px]" style={{ transform: 'rotate(-10deg)' }}>
                    <Trophy size={56} />
                </div>

                {/* ¡GOOOL! burst top-right */}
                <div
                    className="absolute top-[78px] right-[14px] bg-pop-teal border-[2.5px] border-ink px-3 py-2 font-display text-[16px] text-ink"
                    style={{ transform: 'rotate(12deg)', boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    ¡GOOOL!
                </div>

                {/* Main title */}
                <div className="absolute top-[390px] left-0 right-0 text-center px-7">
                    <div
                        className="font-display text-[30px] leading-none text-cream"
                        style={{ textShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        MUNDIAL DE
                    </div>
                    <div
                        className="font-display text-[68px] leading-none mt-0.5 text-pop-yel"
                        style={{
                            WebkitTextStroke: '2.5px var(--c-ink)',
                            textShadow: '5px 5px 0 var(--c-ink)',
                        }}
                    >
                        PARCHE
                    </div>
                    <div className="font-pixel text-[20px] text-cream tracking-[.05em] mt-3">
                        ★ EL JUEGO DEL MUNDIAL ★
                    </div>
                </div>

                {/* Host strip */}
                <div className="absolute bottom-[188px] left-0 right-0 flex justify-center">
                    <div className="flex items-center gap-3 font-mono text-[11px] tracking-[.06em] text-cream opacity-80">
                        <span>🇺🇸 USA</span>
                        <span className="opacity-50">·</span>
                        <span>🇨🇦 CAN</span>
                        <span className="opacity-50">·</span>
                        <span>🇲🇽 MEX</span>
                    </div>
                </div>

                {/* CTAs */}
                <div className="absolute bottom-[90px] left-6 right-6 flex flex-col gap-3">
                    <Link
                        href="/login"
                        className="block w-full py-4 bg-pop-yel text-ink font-display text-[18px] text-center border-[2.5px] border-ink tracking-[.02em]"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        ENTRÁ AL PARCHE →
                    </Link>
                    <Link href="/login" className="text-center font-mono text-[12px] text-cream opacity-85">
                        ¿Ya estás dentro? <u>Iniciá sesión</u>
                    </Link>
                </div>

                {/* Accents */}
                <div className="absolute bottom-7 left-3 opacity-70">
                    <SoccerBall size={36} />
                </div>
                <div
                    className="absolute bottom-9 right-3.5 bg-pop-yel text-ink border-[2px] border-ink font-mono text-[10px] px-2 py-0.5"
                    style={{ transform: 'rotate(-12deg)' }}
                >
                    v1.0 · BETA
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 6.2: Verificar ruta en el browser**

```bash
./vendor/bin/sail up -d
./vendor/bin/sail pnpm run dev
```

Abrir `http://localhost` sin sesión activa. Debe mostrar la pantalla Splash (navy, círculo, título "MUNDIAL DE PARCHE").

- [ ] **Step 6.3: Commit**

```bash
git add resources/js/Pages/Splash.jsx
git commit -m "feat: add Splash page"
```

---

## Task 7: Login refactor

**Files:**
- Modify: `resources/js/Pages/Auth/Login.jsx`

- [ ] **Step 7.1: Reemplazar UI de Login manteniendo lógica Breeze**

Reemplazar el contenido completo de `resources/js/Pages/Auth/Login.jsx`:

```jsx
import { Head, Link, useForm } from '@inertiajs/react';
import { SoccerBall } from '@/Components/icons/football';
import { Mark26, PitchSwoosh } from '@/Components/icons/football';

function Field({ label, id, error, ...inputProps }) {
    return (
        <div>
            <div className="font-mono text-[11px] font-bold tracking-[.1em] mb-1.5 text-ink">
                {label}
            </div>
            <input
                id={id}
                className="w-full border-[2.5px] border-ink bg-white px-[14px] py-[12px] font-mono font-bold text-[14px] focus:outline-none focus:border-pop-red"
                style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                {...inputProps}
            />
            {error && (
                <div className="font-mono text-[11px] text-pop-red mt-1">{error}</div>
            )}
        </div>
    );
}

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email:    '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <>
            <Head title="Entrar al parche" />
            <div className="min-h-screen bg-cream relative overflow-hidden">

                {/* Halftone corners */}
                <div className="halftone halftone-red absolute top-0 left-0 w-[220px] h-[220px] pointer-events-none" style={{ opacity: .35 }} />
                <div className="halftone halftone-teal absolute bottom-0 right-0 w-[260px] h-[260px] pointer-events-none" style={{ opacity: .35 }} />

                {/* Pitch swoosh at bottom */}
                <div className="absolute bottom-0 left-0 right-0 opacity-85 pointer-events-none">
                    <PitchSwoosh width={390} height={120} />
                </div>

                {/* Header */}
                <div className="relative px-6 pt-3">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2.5">
                            <Link
                                href="/"
                                className="w-8 h-8 border-[2px] border-ink rounded-[6px] flex items-center justify-center font-display text-[14px]"
                            >
                                ←
                            </Link>
                            <div className="font-pixel text-[18px]">PASO 1 / 2</div>
                        </div>
                        <div style={{ transform: 'rotate(8deg)' }}>
                            <Mark26 size={32} fill="var(--c-red)" accent="var(--c-yel)" />
                        </div>
                    </div>

                    <div className="mt-5">
                        <div className="font-display text-[36px] leading-none">
                            ¡HOLA,<br />
                            <span className="text-pop-red" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                                PARCERO!
                            </span>
                        </div>
                        <div className="font-body text-[14px] mt-2 opacity-80">
                            Metete con tu cuenta y agarrá los puntos que te ganaste.
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={submit} className="relative z-10 px-6 pt-6 flex flex-col gap-3.5">
                    {status && (
                        <div className="font-mono text-[12px] text-pop-teal">{status}</div>
                    )}

                    <Field
                        label="EMAIL"
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        autoComplete="username"
                        autoFocus
                        onChange={e => setData('email', e.target.value)}
                        error={errors.email}
                    />

                    <Field
                        label="CONTRASEÑA"
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoComplete="current-password"
                        onChange={e => setData('password', e.target.value)}
                        error={errors.password}
                    />

                    <div className="flex items-center justify-between font-mono text-[11px] mt-1">
                        <label className="flex items-center gap-1.5 cursor-pointer select-none">
                            <span
                                className="w-4 h-4 border-[2px] border-ink flex items-center justify-center flex-shrink-0"
                                style={{ background: data.remember ? 'var(--c-yel)' : '#fff' }}
                                onClick={() => setData('remember', !data.remember)}
                            >
                                {data.remember && <span className="text-[11px] font-bold">✓</span>}
                            </span>
                            Recordame
                        </label>
                        {canResetPassword && (
                            <Link href={route('password.request')} className="underline opacity-70">
                                ¿Se te olvidó?
                            </Link>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-2 w-full py-4 bg-pop-red text-white font-display text-[18px] border-[2.5px] border-ink tracking-[.02em] disabled:opacity-60"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        DALE, ENTRAR
                    </button>
                </form>

                {/* Burst +500K */}
                <div
                    className="absolute top-[80px] right-[18px] z-10 bg-pop-yel border-[2.5px] border-ink px-3 py-2 font-display text-[12px] text-ink text-center"
                    style={{ transform: 'rotate(12deg)', boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    +500K<br />BIENVE-<br />NIDA
                </div>

                {/* Ghost ball */}
                <div className="absolute top-[270px] right-[-8px] opacity-15">
                    <SoccerBall size={120} />
                </div>

                {/* Footer */}
                <div className="absolute bottom-[18px] left-0 right-0 text-center font-mono text-[12px] z-10">
                    ¿Nuevo en el parche?{' '}
                    <Link href={route('register')} className="font-bold underline">
                        Creá cuenta
                    </Link>
                </div>
            </div>
        </>
    );
}
```

**Nota sobre Mark26 y PitchSwoosh:** verificar que estos íconos existen en `@/Components/icons/football`. Si `Mark26` o `PitchSwoosh` no están exportados desde ese barrel, importarlos directamente desde sus archivos o remover la decoración y usar solo `SoccerBall`. Revisar `resources/js/Components/icons/football/index.js` antes de correr.

- [ ] **Step 7.2: Verificar en browser**

Con `sail pnpm run dev` activo, abrir `http://localhost/login`. Debe mostrar la pantalla de login pop-art.

- [ ] **Step 7.3: Correr tests**

```bash
./vendor/bin/sail test
```

Salida esperada: todos los tests pasando (los tests de auth usan la lógica del controller, no el JSX).

- [ ] **Step 7.4: Commit**

```bash
git add resources/js/Pages/Auth/Login.jsx
git commit -m "feat: redesign Login page with pop-art style"
```

---

## Task 8: Ranking page refactor

**Files:**
- Modify: `resources/js/Pages/Ranking.jsx`

- [ ] **Step 8.1: Reemplazar Ranking.jsx**

Reemplazar el contenido completo de `resources/js/Pages/Ranking.jsx`:

```jsx
import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import MobileShell from '@/Components/MobileShell';
import TabBar from '@/Components/composed/TabBar';
import PodiumStep from '@/Components/composed/PodiumStep';
import RankRow from '@/Components/composed/RankRow';
import PozoCard from '@/Components/composed/PozoCard';
import { Trophy, SoccerBall } from '@/Components/icons/football';

const AVATAR_COLORS = {
    yel:   'var(--c-yel)',
    teal:  'var(--c-teal)',
    red:   'var(--c-red)',
    cream: 'var(--c-cream)',
};

function SectionHead({ title }) {
    return (
        <div className="flex items-center gap-2 py-2">
            <span className="w-3 h-3 flex-shrink-0 bg-pop-teal border-2 border-ink" />
            <div className="font-display text-[13px]">{title}</div>
            <div className="flex-1 h-0.5 bg-ink" />
        </div>
    );
}

function AllTiedHero({ users }) {
    const count = users.length;
    return (
        <div className="px-[14px] pt-[18px]">
            <div
                className="bg-pop-yel border-[3px] border-ink p-[14px_16px] relative overflow-hidden"
                style={{ boxShadow: '5px 5px 0 var(--c-ink)' }}
            >
                <div className="halftone halftone-red absolute top-0 right-0 w-40 h-40" style={{ opacity: .35 }} />
                <div className="flex items-center mb-2.5 relative">
                    {users.slice(0, 6).map((u, i) => (
                        <div
                            key={u.id}
                            className="w-9 h-9 rounded-full border-[2.5px] border-ink shadow-pop-sm font-display text-[14px] text-ink flex items-center justify-center flex-shrink-0"
                            style={{
                                background: AVATAR_COLORS[u.avatarColor] ?? 'var(--c-teal)',
                                marginLeft: i > 0 ? -12 : 0,
                                zIndex: 10 - i,
                            }}
                        >
                            {u.name[0]}
                        </div>
                    ))}
                    {count > 6 && (
                        <div
                            className="w-9 h-9 rounded-full bg-ink text-pop-yel border-[2.5px] border-ink font-display text-[11px] flex items-center justify-center flex-shrink-0"
                            style={{ marginLeft: -12, zIndex: 0 }}
                        >
                            +{count - 6}
                        </div>
                    )}
                </div>
                <div className="font-display text-[24px] leading-none text-ink">
                    {count} ARRANCAN<br />
                    <span className="text-pop-red" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                        EMPATADOS
                    </span>
                </div>
                <p className="font-body text-[11px] mt-1.5 leading-snug opacity-85 relative">
                    Nadie ha sumado puntos todavía. El podio se llena cuando arranquen los partidos.
                </p>
                <div className="absolute bottom-[-8px] right-[-6px] opacity-70" style={{ transform: 'rotate(-12deg)' }}>
                    <SoccerBall size={42} />
                </div>
            </div>
        </div>
    );
}

export default function Ranking({ users: initialUsers, pozo }) {
    const { auth } = usePage().props;
    const me = auth.user.id;
    const [users, setUsers] = useState(initialUsers);

    // Real-time: update points via Echo
    useEffect(() => {
        const channel = window.Echo.join('quinela');
        channel.listen('.PointsUpdated', (event) => {
            setUsers(prev => {
                const updated = prev.map(u =>
                    u.id === event.user_id ? { ...u, total_points: event.total_points } : u
                );
                const sorted = [...updated].sort((a, b) => b.total_points - a.total_points);
                const avatarColors = ['yel', 'teal', 'red', 'cream'];
                let pos = 0, lastPts = null, counter = 0;
                return sorted.map(u => {
                    counter++;
                    if (u.total_points !== lastPts) { pos = counter; lastPts = u.total_points; }
                    return { ...u, position: pos };
                });
            });
        });
        return () => { window.Echo.leave('quinela'); };
    }, []);

    const allTied = users.every(u => u.total_points === 0);

    // Count per points value for tiedCount display
    const ptsCounts = {};
    users.forEach(u => { ptsCounts[u.total_points] = (ptsCounts[u.total_points] || 0) + 1; });

    // Build podium: positions 1, 2, 3 by unique pts
    const uniquePts = [...new Set(users.map(u => u.total_points))].slice(0, 3);
    const podiumData = {};
    if (!allTied) {
        [1, 2, 3].forEach(place => {
            const pts = uniquePts[place - 1];
            if (pts !== undefined) {
                podiumData[place] = {
                    pts: String(pts),
                    tied: users
                        .filter(u => u.total_points === pts)
                        .map(u => ({
                            name:  u.name.split(' ')[0].toUpperCase(),
                            color: AVATAR_COLORS[u.avatarColor] ?? 'var(--c-teal)',
                        })),
                };
            }
        });
    }

    const listUsers = allTied ? users : users.filter(u => u.position > 3);

    return (
        <>
            <Head title="Ranking" />
            <MobileShell>
                {/* Halftone decoration */}
                <div
                    className="halftone halftone-red absolute top-[60px] right-0 w-[220px] h-[200px] pointer-events-none"
                    style={{ opacity: .25 }}
                />

                {/* Header */}
                <div className="relative px-[18px] pt-1.5 flex items-start justify-between">
                    <div>
                        <div
                            className="font-display text-[36px] leading-none mt-1.5 text-pop-yel"
                            style={{
                                WebkitTextStroke: '1.5px var(--c-ink)',
                                textShadow: '3px 3px 0 var(--c-red)',
                            }}
                        >
                            RANKING
                        </div>
                        <div className="font-mono text-[11px] opacity-70 tracking-[.08em] mt-0.5">
                            POR PUNTOS · {users.length} JUGADORES
                        </div>
                    </div>
                    <div className="mt-1.5" style={{ transform: 'rotate(8deg)' }}>
                        <Trophy size={40} />
                    </div>
                </div>

                {/* Pozo */}
                <div className="px-[14px] pt-2.5">
                    <PozoCard
                        total={pozo.total}
                        players={pozo.players}
                        amountPerPlayer="50K"
                        prize1={pozo.prize1}
                        prize2={pozo.prize2}
                    />
                </div>

                {/* Podium or AllTied */}
                {allTied ? (
                    <AllTiedHero users={users} />
                ) : (
                    <div className="px-[14px] pt-[18px] flex items-end justify-center gap-2">
                        {podiumData[2] && (
                            <PodiumStep place={2} pts={podiumData[2].pts} tied={podiumData[2].tied} />
                        )}
                        {podiumData[1] && (
                            <PodiumStep place={1} pts={podiumData[1].pts} tied={podiumData[1].tied} crown />
                        )}
                        {podiumData[3] && (
                            <PodiumStep place={3} pts={podiumData[3].pts} tied={podiumData[3].tied} />
                        )}
                    </div>
                )}

                {/* List */}
                <div className="px-[14px] pt-[14px] pb-4">
                    <SectionHead title={allTied ? 'TODOS EN CERO' : 'LOS DEMÁS'} />
                    <div className="flex flex-col gap-2 mt-1">
                        {listUsers.map(u => (
                            <RankRow
                                key={u.id}
                                position={u.position}
                                name={u.name.split(' ')[0].toUpperCase()}
                                pts={String(u.total_points)}
                                delta={u.delta ?? '+0'}
                                isYou={u.id === me}
                                tiedCount={ptsCounts[u.total_points] > 1 ? ptsCounts[u.total_points] : undefined}
                            />
                        ))}
                    </div>
                </div>
            </MobileShell>
            <TabBar active="rank" />
        </>
    );
}
```

- [ ] **Step 8.2: Correr tests**

```bash
./vendor/bin/sail test
```

Salida esperada: todos los tests pasando.

- [ ] **Step 8.3: Commit**

```bash
git add resources/js/Pages/Ranking.jsx
git commit -m "feat: redesign Ranking page with pop-art style"
```

---

## Task 9: Chat page refactor

**Files:**
- Modify: `resources/js/Pages/Chat.jsx`

- [ ] **Step 9.1: Reemplazar Chat.jsx**

Reemplazar el contenido completo de `resources/js/Pages/Chat.jsx`:

```jsx
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import TabBar from '@/Components/composed/TabBar';
import ChatBubble from '@/Components/composed/ChatBubble';
import { SoccerBall } from '@/Components/icons/football';

const COLORS = ['yel', 'teal', 'red', 'cream'];

function mapMessage(msg, myId) {
    return {
        id:    msg.id,
        name:  msg.user.name.split(' ')[0].toUpperCase(),
        color: `var(--c-${COLORS[msg.user.id % 4]})`,
        text:  msg.content,
        time:  new Date(msg.created_at).toLocaleTimeString('es', {
            hour: '2-digit', minute: '2-digit', hour12: false,
        }),
        isMe: msg.user.id === myId,
    };
}

export default function Chat({ messages: initialMessages, liveMatch }) {
    const { auth } = usePage().props;
    const myId = auth.user.id;

    const [messages, setMessages]       = useState(initialMessages.map(m => mapMessage(m, myId)));
    const [onlineCount, setOnlineCount] = useState(0);
    const bottomRef = useRef(null);

    const { data, setData, post, processing, reset } = useForm({ content: '' });

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    useEffect(() => {
        const channel = window.Echo.join('quinela');

        channel
            .here(users => setOnlineCount(users.length))
            .joining(() => setOnlineCount(c => c + 1))
            .leaving(() => setOnlineCount(c => Math.max(0, c - 1)))
            .listen('.MessageSent', (event) => {
                const raw = {
                    id:         event.id,
                    user:       { id: event.user_id, name: event.user_name },
                    content:    event.content,
                    created_at: event.created_at,
                };
                setMessages(prev => [...prev, mapMessage(raw, myId)]);
            });

        return () => { window.Echo.leave('quinela'); };
    }, [myId]);

    const send = (e) => {
        e.preventDefault();
        if (!data.content.trim() || processing) return;
        post(route('chat.store'), { onSuccess: () => reset() });
    };

    return (
        <>
            <Head title="El Parche" />
            <div className="flex flex-col bg-cream overflow-hidden" style={{ height: 'calc(100vh - 80px)' }}>

                {/* Header */}
                <div className="flex-shrink-0 px-4 pb-2.5 pt-1.5 border-b-[3px] border-ink bg-pop-yel relative overflow-hidden">
                    <div className="halftone absolute inset-0 pointer-events-none" style={{ opacity: .15 }} />
                    <div className="flex items-center gap-3 relative">
                        <div className="w-9 h-9 rounded-[6px] border-[2.5px] border-ink bg-pop-red flex items-center justify-center flex-shrink-0">
                            <SoccerBall size={22} />
                        </div>
                        <div className="flex-1">
                            <div className="font-display text-[18px] leading-none">EL PARCHE</div>
                            <div className="font-mono text-[10px] opacity-75 mt-0.5">
                                ● {onlineCount > 0 ? `${onlineCount} en línea` : 'conectando…'}
                            </div>
                        </div>
                        <div className="w-8 h-8 border-[2.5px] border-ink bg-white flex items-center justify-center font-display text-[16px]">
                            ⋯
                        </div>
                    </div>
                </div>

                {/* Live match banner */}
                {liveMatch && (
                    <div className="flex-shrink-0 px-4 py-2 bg-navy text-cream border-b-[3px] border-ink flex items-center gap-2.5 relative overflow-hidden">
                        <div className="absolute right-[-12px] top-[-8px] opacity-25 pointer-events-none">
                            <SoccerBall size={50} />
                        </div>
                        <span className="w-2 h-2 rounded-full bg-pop-red animate-[blink_1.2s_infinite]" />
                        <span className="font-pixel text-[16px] text-pop-yel">EN VIVO</span>
                        <span className="font-mono text-[13px] font-bold">
                            {liveMatch.teamA} {liveMatch.scoreA} - {liveMatch.scoreB} {liveMatch.teamB}
                        </span>
                        {liveMatch.minute && (
                            <span className="font-mono text-[11px] opacity-70 ml-auto z-10">
                                {liveMatch.minute}
                            </span>
                        )}
                    </div>
                )}

                {/* Messages */}
                <div className="flex-1 overflow-y-auto px-3.5 py-3.5 flex flex-col gap-3 min-h-0">
                    {messages.map(msg => (
                        <ChatBubble key={msg.id} {...msg} />
                    ))}
                    <div ref={bottomRef} />
                </div>

                {/* Input bar */}
                <div className="flex-shrink-0 flex items-center gap-2 px-3 py-2.5 bg-cream border-t-[3px] border-ink">
                    <button
                        type="button"
                        className="w-10 h-10 border-[2.5px] border-ink bg-pop-yel font-display text-[18px] shadow-pop-sm flex items-center justify-center flex-shrink-0"
                    >
                        +
                    </button>
                    <form onSubmit={send} className="flex-1 flex gap-2">
                        <input
                            value={data.content}
                            onChange={e => setData('content', e.target.value)}
                            placeholder="Escribí algo, parcero…"
                            className="flex-1 border-[2.5px] border-ink bg-white px-3.5 py-2.5 font-body text-[13px] shadow-pop-sm focus:outline-none"
                        />
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-11 h-11 border-[2.5px] border-ink bg-pop-red text-white font-display text-[18px] shadow-pop-sm flex items-center justify-center flex-shrink-0 disabled:opacity-60"
                        >
                            ▶
                        </button>
                    </form>
                </div>
            </div>
            <TabBar active="chat" />
        </>
    );
}
```

- [ ] **Step 9.2: Correr tests**

```bash
./vendor/bin/sail test
```

Salida esperada: todos los tests pasando.

- [ ] **Step 9.3: Commit**

```bash
git add resources/js/Pages/Chat.jsx
git commit -m "feat: redesign Chat page with pop-art style"
```

---

## Task 10: Matches page

**Files:**
- Create: `resources/js/Pages/Matches.jsx`

- [ ] **Step 10.1: Crear Matches.jsx**

Crear `resources/js/Pages/Matches.jsx`:

```jsx
import { Head } from '@inertiajs/react';
import { useState } from 'react';
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

function DateChip({ label, date, active, onClick }) {
    return (
        <button
            onClick={onClick}
            className={[
                'flex-shrink-0 px-2.5 py-1.5 border-[2.5px] border-ink text-center min-w-[56px]',
                active ? 'bg-pop-red text-white shadow-pop' : 'bg-white text-ink shadow-pop-sm',
            ].join(' ')}
        >
            <div className="font-display text-[13px] leading-none">{label}</div>
            <div className="font-mono text-[9px] font-bold opacity-80 mt-0.5 tracking-[.06em]">{date}</div>
        </button>
    );
}

function DayBlock({ day }) {
    return (
        <div className="mb-3">
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
}

export default function Matches({ matchDays, groups, currentRound }) {
    const today = new Date().toISOString().split('T')[0];
    const defaultDate = matchDays.find(d => d.dateKey === today)?.dateKey
        ?? matchDays[0]?.dateKey
        ?? null;

    const [view, setView]               = useState('calendar');
    const [selectedDate, setSelectedDate] = useState(defaultDate);

    const visibleDays = selectedDate
        ? matchDays.filter(d => d.dateKey === selectedDate)
        : matchDays;

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
                    <div className="flex flex-col items-end gap-1 mt-2">
                        {currentRound && (
                            <div className="bg-navy text-cream border-[2px] border-ink px-2 py-0.5 font-display text-[9px] tracking-[.04em]">
                                {currentRound.name.toUpperCase()}
                            </div>
                        )}
                        <div className="font-mono text-[9px] opacity-65 tracking-[.06em]">
                            {currentRound?.totalMatches ?? '—'} partidos
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
                        {/* Date strip */}
                        <div className="pt-3 pl-[14px]">
                            <div className="flex gap-1.5 overflow-x-auto pr-[14px] pb-1">
                                {matchDays.map(day => {
                                    const isToday = day.dateKey === today;
                                    const parts   = day.date.split(' ');
                                    return (
                                        <DateChip
                                            key={day.dateKey}
                                            label={isToday ? 'HOY' : parts[0]}
                                            date={parts.slice(1).join(' ')}
                                            active={selectedDate === day.dateKey}
                                            onClick={() => setSelectedDate(day.dateKey)}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                        <div className="px-[14px] pt-2.5 pb-4">
                            {visibleDays.length > 0 ? (
                                visibleDays.map(day => (
                                    <DayBlock key={day.dateKey} day={day} />
                                ))
                            ) : (
                                <div className="text-center font-mono text-[11px] opacity-50 py-8">
                                    No hay partidos para esta fecha
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

- [ ] **Step 10.2: Correr tests**

```bash
./vendor/bin/sail test
```

Salida esperada: todos los tests pasando.

- [ ] **Step 10.3: Commit**

```bash
git add resources/js/Pages/Matches.jsx
git commit -m "feat: add Matches page with calendar and standings views"
```

---

## Task 11: Predictions/Locked page

**Files:**
- Create: `resources/js/Pages/Predictions/Locked.jsx`

- [ ] **Step 11.1: Crear Predictions/Locked.jsx**

Crear `resources/js/Pages/Predictions/Locked.jsx`:

```jsx
import { Head, Link } from '@inertiajs/react';

export default function Locked({ roundName, roundOrder, isLocked, opensAt }) {
    return (
        <>
            <Head title="Fase bloqueada" />
            <div
                className="bg-navy text-cream min-h-screen overflow-hidden flex flex-col relative"
            >
                {/* Scanlines */}
                <div className="scanlines absolute inset-0 pointer-events-none" />

                {/* Halftone corner */}
                <div
                    className="halftone halftone-yel absolute top-0 right-0 w-[200px] h-[200px] pointer-events-none"
                    style={{ opacity: .2 }}
                />

                {/* Content */}
                <div className="flex-1 flex flex-col justify-center items-center px-7 text-center relative">

                    {/* Lock graphic */}
                    <div className="relative flex justify-center mb-3.5">
                        <div
                            className="halftone halftone-red absolute w-[200px] h-[200px] rounded-full"
                            style={{ opacity: .4 }}
                        />
                        <div
                            className="relative w-[140px] h-[140px] bg-navy border-[4px] border-pop-yel flex items-center justify-center"
                            style={{
                                transform: 'rotate(-4deg)',
                                boxShadow: '6px 6px 0 var(--c-red)',
                            }}
                        >
                            <svg width="80" height="80" viewBox="0 0 60 60" fill="none">
                                <rect x="10" y="26" width="40" height="28" fill="var(--c-yel)" stroke="var(--c-ink)" strokeWidth="3" />
                                <path
                                    d="M16 26 V18 C 16 10, 24 6, 30 6 C 36 6, 44 10, 44 18 V26"
                                    stroke="var(--c-yel)"
                                    strokeWidth="4"
                                    fill="none"
                                />
                                <rect x="26" y="36" width="8" height="12" fill="var(--c-ink)" />
                            </svg>
                        </div>
                    </div>

                    <div className="font-display text-[14px] text-pop-yel tracking-[.08em] mb-1">
                        ESPERÁ UN TOQUE —
                    </div>
                    <div
                        className="font-display text-[38px] leading-none text-cream"
                        style={{ textShadow: '3px 3px 0 var(--c-red)' }}
                    >
                        {isLocked ? (
                            <>FASE {roundOrder}<br />CERRADA</>
                        ) : (
                            <>FASE {roundOrder}<br />BLOQUEADA</>
                        )}
                    </div>

                    <p className="font-body text-[14px] text-cream opacity-85 leading-snug mt-4 max-w-[280px]">
                        {isLocked
                            ? `La ${roundName} ya está cerrada. Las predicciones son definitivas.`
                            : `Esta fase se abre cuando se cierre la fase anterior.`}
                    </p>

                    {/* Countdown */}
                    {opensAt && !isLocked && (
                        <div className="mt-5 px-3 py-2.5 border-2 border-dashed border-pop-yel" style={{ background: 'rgba(0,0,0,.4)' }}>
                            <div className="font-mono text-[10px] text-pop-yel tracking-[.08em]">SE ABRE EN</div>
                            <div className="font-display text-[24px] text-cream mt-0.5">{opensAt}</div>
                        </div>
                    )}
                </div>

                {/* CTAs */}
                <div className="flex-shrink-0 px-6 pb-8 flex flex-col gap-2">
                    <Link
                        href="/chat"
                        className="block w-full py-4 bg-pop-yel text-ink font-display text-[16px] text-center border-[2.5px] border-ink tracking-[.02em]"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        MIENTRAS, AL CHAT
                    </Link>
                    <Link
                        href="/ranking"
                        className="block w-full py-3 text-center font-mono text-[12px] text-cream opacity-80 underline"
                    >
                        VER RANKING
                    </Link>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 11.2: Correr tests**

```bash
./vendor/bin/sail test
```

Salida esperada: todos los tests pasando.

- [ ] **Step 11.3: Commit**

```bash
git add resources/js/Pages/Predictions/Locked.jsx
git commit -m "feat: add Predictions/Locked page for blocked phases"
```

---

## Task 12: Predictions/Round refactor

**Files:**
- Modify: `resources/js/Pages/Predictions/Round.jsx`

- [ ] **Step 12.1: Reemplazar Round.jsx**

Reemplazar el contenido completo de `resources/js/Pages/Predictions/Round.jsx`:

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import MobileShell from '@/Components/MobileShell';
import TabBar from '@/Components/composed/TabBar';
import { Mark26 } from '@/Components/icons/football';

// ── helpers (preserved from original) ──────────────────────────────────────

function groupFixtures(fixtures) {
    return fixtures.reduce((acc, f) => {
        const key = f.group?.name ?? 'Sin Grupo';
        if (!acc[key]) acc[key] = [];
        acc[key].push(f);
        return acc;
    }, {});
}

function teamName(team, placeholder) {
    return team ? (team.fifa_code ?? team.name) : (placeholder ?? 'TBD');
}

// ── ScoreBoxInput — same visual as ScoreBox but editable ───────────────────

function ScoreBoxInput({ value, onChange, disabled }) {
    return (
        <div className="relative w-[30px] h-[34px] border-[2.5px] border-ink shadow-pop-sm flex items-center justify-center font-display text-[18px]"
            style={{ background: value !== null && value !== undefined ? 'var(--c-yel)' : '#fff',
                     color: value !== null && value !== undefined ? 'var(--c-ink)' : 'rgba(0,0,0,.25)' }}
        >
            {value !== null && value !== undefined ? value : '—'}
            <input
                type="number"
                min="0"
                max="20"
                value={value ?? ''}
                onChange={e => onChange(parseInt(e.target.value, 10))}
                disabled={disabled}
                className="absolute inset-0 opacity-0 cursor-pointer disabled:cursor-default w-full h-full"
                aria-label="goles"
            />
        </div>
    );
}

// ── PhaseLadder ────────────────────────────────────────────────────────────

function PhaseLadder({ rounds, currentRoundId }) {
    return (
        <div className="grid grid-cols-4 gap-1.5">
            {rounds.map(r => {
                const active = r.id === currentRoundId;
                return (
                    <div
                        key={r.id}
                        className={[
                            'border-[2.5px] border-ink p-[8px_6px_6px] relative overflow-hidden',
                            active ? 'bg-pop-yel shadow-pop' : 'bg-white shadow-pop-sm opacity-55',
                        ].join(' ')}
                    >
                        {active && (
                            <div
                                className="halftone halftone-red absolute top-0 right-0 w-10 h-10"
                                style={{ opacity: .4 }}
                            />
                        )}
                        <div className="font-display text-[9px] opacity-80">FASE {r.order}</div>
                        <div className="font-display text-[10px] mt-0.5 leading-none">{r.name.toUpperCase()}</div>
                        {!active && (
                            <div className="absolute top-1.5 right-1.5 text-[10px]">🔒</div>
                        )}
                        {active && r.progress !== undefined && (
                            <div className="mt-1.5 h-[5px] bg-black/15 border border-ink">
                                <div
                                    className="h-full bg-pop-red"
                                    style={{ width: `${r.progress * 100}%` }}
                                />
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

// ── PointChip ──────────────────────────────────────────────────────────────

function PointChip({ label, pts, color }) {
    return (
        <div className="flex-1 bg-white border-[2.5px] border-ink shadow-pop-sm p-[4px_6px] flex items-center justify-between gap-1">
            <div className="font-mono text-[9px] font-bold tracking-[.06em]">{label}</div>
            <div
                className="font-display text-[12px] px-[6px] py-[1px] border-[1.5px] border-ink text-white"
                style={{ background: color }}
            >
                {pts}
            </div>
        </div>
    );
}

// ── GroupChip ──────────────────────────────────────────────────────────────

function GroupChip({ groupKey, active, done, teams, onClick }) {
    return (
        <button
            onClick={onClick}
            className={[
                'flex-shrink-0 min-w-[78px] px-2.5 py-1.5 border-[2.5px] border-ink text-center relative',
                active
                    ? 'bg-ink text-pop-yel shadow-[3px_3px_0_var(--c-red)]'
                    : done
                        ? 'bg-pop-teal text-white shadow-pop-sm'
                        : 'bg-white text-ink shadow-pop-sm',
            ].join(' ')}
        >
            <div className="font-display text-[20px] leading-none">{groupKey}</div>
            <div className="flex justify-center gap-0.5 mt-1">
                {(teams ?? []).slice(0, 4).map((t, i) => (
                    t.flagUrl
                        ? <img key={i} src={t.flagUrl} alt="" className="h-2.5 w-4 object-cover" />
                        : <span key={i} className="w-4 h-2.5 bg-black/20 inline-block" />
                ))}
            </div>
            {done && (
                <div className="absolute -top-1.5 -right-1.5 w-[18px] h-[18px] rounded-full bg-pop-yel border-2 border-ink text-ink font-display text-[10px] flex items-center justify-center">
                    ✓
                </div>
            )}
        </button>
    );
}

// ── MatchPredRow ──────────────────────────────────────────────────────────

function MatchPredRow({ fixture, homeScore, awayScore, onChangeHome, onChangeAway, disabled, last }) {
    const filled = homeScore !== null && homeScore !== undefined
                && awayScore !== null && awayScore !== undefined;
    const home = teamName(fixture.home_team, fixture.home_placeholder);
    const away = teamName(fixture.away_team, fixture.away_placeholder);
    const flagHome = fixture.home_team?.flag_url;
    const flagAway = fixture.away_team?.flag_url;

    return (
        <div className={['px-2.5 py-2 relative', !last ? 'border-b border-dashed border-black/20' : ''].join(' ')}>
            <div className="font-mono text-[8.5px] opacity-55 tracking-[.08em] mb-1">
                {fixture.match_date
                    ? new Date(fixture.match_date).toLocaleString('es', {
                        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
                      })
                    : '--'
                }
                {fixture.venue ? ` · ${fixture.venue}` : ''}
            </div>
            <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                <div className="flex items-center justify-end gap-1.5">
                    <span className="font-display text-[12px]">{home}</span>
                    {flagHome && <img src={flagHome} alt={home} className="h-4 w-6 object-cover border border-ink" />}
                </div>
                <div className="flex items-center gap-0.5">
                    <ScoreBoxInput value={homeScore} onChange={onChangeHome} disabled={disabled} />
                    <span className="font-display text-[13px] opacity-55 mx-0.5">—</span>
                    <ScoreBoxInput value={awayScore} onChange={onChangeAway} disabled={disabled} />
                </div>
                <div className="flex items-center gap-1.5">
                    {flagAway && <img src={flagAway} alt={away} className="h-4 w-6 object-cover border border-ink" />}
                    <span className="font-display text-[12px]">{away}</span>
                </div>
            </div>
            <div className="flex justify-center mt-1">
                {filled ? (
                    <span className="inline-flex items-center gap-1 font-mono text-[8.5px] font-bold tracking-[.08em] bg-pop-teal text-white px-1.5 py-0.5 border-[1.5px] border-ink">
                        ✓ GUARDADO
                    </span>
                ) : (
                    <span className="inline-flex items-center gap-1 font-mono text-[8.5px] font-bold tracking-[.08em] bg-white text-pop-red px-1.5 py-0.5 border-[1.5px] border-dashed border-pop-red">
                        ! FALTAN TUS GOLES
                    </span>
                )}
            </div>
        </div>
    );
}

// ── GroupPanel ─────────────────────────────────────────────────────────────

function GroupPanel({ groupKey, fixtures, scores, isLocked, onChange }) {
    // Get unique teams for "TUS CLASIFICADOS"
    const teamMap = {};
    fixtures.forEach(f => {
        if (f.home_team) teamMap[f.home_team.id] = f.home_team;
        if (f.away_team) teamMap[f.away_team.id] = f.away_team;
    });
    const teams = Object.values(teamMap).slice(0, 4);
    const filled = fixtures.filter(f => {
        const s = scores[f.id];
        return s && s.home !== null && s.home !== undefined
                 && s.away !== null && s.away !== undefined;
    }).length;

    return (
        <div className="border-[3px] border-ink bg-white relative overflow-hidden" style={{ boxShadow: '5px 5px 0 var(--c-ink)' }}>
            {/* corner banner */}
            <div className="absolute top-0 left-0 bg-pop-red text-white px-3 py-1.5 font-display text-[14px] border-r-[3px] border-b-[3px] border-ink">
                GRUPO {groupKey}
            </div>
            <div className="absolute top-1.5 right-2.5 font-mono text-[10px] opacity-70">
                {filled} / {fixtures.length} GOLES METIDOS
            </div>

            {/* TUS CLASIFICADOS */}
            {teams.length > 0 && (
                <div className="mt-10 px-2.5 pb-2 border-b-2 border-dashed border-ink">
                    <div className="flex justify-between items-baseline mb-1.5">
                        <div className="font-mono text-[9px] font-bold tracking-[.08em] opacity-70">TUS CLASIFICADOS</div>
                        <div className="font-mono text-[8.5px] opacity-55">+3 PTS C/U</div>
                    </div>
                    <div className="grid grid-cols-2 gap-1.5">
                        {teams.map((t, i) => {
                            const advances = i < 2;
                            return (
                                <div
                                    key={t.id}
                                    className={[
                                        'flex items-center gap-1.5 px-1.5 py-1 border-[1.5px] border-ink',
                                        advances ? 'bg-pop-yel' : 'bg-black/4',
                                    ].join(' ')}
                                >
                                    <span className="font-mono text-[9px] font-bold opacity-60 w-3.5">{i + 1}°</span>
                                    {t.flag_url && <img src={t.flag_url} alt="" className="h-3 w-4 object-cover" />}
                                    <span className="font-display text-[10px] flex-1 leading-none truncate">{t.name.toUpperCase()}</span>
                                    {advances && (
                                        <span className="font-mono text-[8px] font-bold text-pop-teal">→R32</span>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Matches */}
            <div>
                {fixtures.map((f, i) => (
                    <MatchPredRow
                        key={f.id}
                        fixture={f}
                        homeScore={scores[f.id]?.home ?? null}
                        awayScore={scores[f.id]?.away ?? null}
                        onChangeHome={v => onChange(f.id, 'home', v)}
                        onChangeAway={v => onChange(f.id, 'away', v)}
                        disabled={isLocked}
                        last={i === fixtures.length - 1}
                    />
                ))}
            </div>
        </div>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────

export default function Round({ round, fixtures, predictions, submission }) {
    const { auth } = usePage().props;
    const isLocked    = submission?.status === 'locked';
    const isSubmitted = submission?.status === 'submitted';
    const isGroupStage = round.slug === 'grupos';

    // Initialize scores from existing predictions
    const initialScores = {};
    fixtures.forEach(f => {
        const pred = predictions[f.id];
        initialScores[f.id] = {
            home: pred ? pred.predicted_home : null,
            away: pred ? pred.predicted_away : null,
        };
    });

    const [scores, setScores] = useState(initialScores);

    const grouped    = groupFixtures(fixtures);
    const groupKeys  = Object.keys(grouped).sort();
    const [activeGroup, setActiveGroup] = useState(groupKeys[0] ?? null);

    function handleChange(fixtureId, side, value) {
        if (isLocked || isSubmitted) return;
        setScores(prev => ({
            ...prev,
            [fixtureId]: { ...prev[fixtureId], [side]: isNaN(value) ? null : value },
        }));
    }

    function isGroupDone(key) {
        return (grouped[key] ?? []).every(f => {
            const s = scores[f.id];
            return s && s.home !== null && s.away !== null;
        });
    }

    const totalFixtures = fixtures.length;
    const filledCount   = fixtures.filter(f => {
        const s = scores[f.id];
        return s && s.home !== null && s.away !== null;
    }).length;
    const progressPct = totalFixtures > 0 ? filledCount / totalFixtures : 0;

    function submit() {
        const payload = Object.entries(scores).map(([matchId, s]) => ({
            match_id:       parseInt(matchId),
            predicted_home: s.home,
            predicted_away: s.away,
        }));
        router.post(route('predictions.save', round.slug), { predictions: payload });
    }

    const activeFixtures = activeGroup ? (grouped[activeGroup] ?? []) : [];

    return (
        <>
            <Head title="Mis Goles" />
            <div className="bg-cream min-h-screen overflow-x-hidden flex flex-col relative" style={{ paddingBottom: '128px' }}>

                {/* Halftone decoration */}
                <div
                    className="halftone halftone-yel absolute top-[60px] right-[-20px] w-[220px] h-[200px] pointer-events-none"
                    style={{ opacity: .25 }}
                />

                {/* Header */}
                <div className="relative px-[18px] pt-1.5 flex items-start justify-between">
                    <div>
                        <div className="font-mono text-[10px] opacity-70 tracking-[.1em] mt-1.5">MUNDIAL 2026</div>
                        <div className="font-display text-[32px] leading-none mt-0.5">
                            MIS{' '}
                            <span className="text-pop-red" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                                GOLES
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-col items-end gap-1.5 mt-1.5">
                        <div
                            className="inline-flex items-center gap-1.5 bg-pop-teal text-white border-2 border-ink px-2 py-1 font-mono text-[10px] font-bold tracking-[.06em]"
                            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                        >
                            ✓ ENTRADA 50K PAGA
                        </div>
                        <div style={{ transform: 'rotate(6deg)' }}>
                            <Mark26 size={26} fill="var(--c-red)" accent="var(--c-yel)" />
                        </div>
                    </div>
                </div>

                {/* Phase card navy */}
                <div className="px-[18px] pt-3">
                    <div className="border-[3px] border-ink bg-navy text-cream p-[10px_12px] relative overflow-hidden" style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="font-mono text-[10px] text-pop-yel tracking-[.12em]">FASE EN CURSO</div>
                                <div className="font-display text-[18px] leading-none mt-0.5">{round.name.toUpperCase()}</div>
                            </div>
                            <div className="text-right font-mono text-[11px]">
                                <div className="text-pop-yel font-bold">{filledCount} / {totalFixtures}</div>
                                <div className="opacity-70">goles metidos</div>
                            </div>
                        </div>
                        <div className="mt-2 h-[5px] bg-black/25 border border-ink">
                            <div className="h-full bg-pop-yel transition-all" style={{ width: `${progressPct * 100}%` }} />
                        </div>
                    </div>
                </div>

                {/* Point chips */}
                <div className="px-[14px] pt-2.5 flex gap-1.5">
                    <PointChip label="EXACTO"   pts="+5" color="var(--c-red)"  />
                    <PointChip label="GANADOR"  pts="+2" color="var(--c-teal)" />
                    <PointChip label="CLASIFICA" pts="+3" color="var(--c-yel)" />
                </div>

                {/* Group chips — only for group stage */}
                {isGroupStage && groupKeys.length > 0 && (
                    <div className="pt-3 pl-[14px]">
                        <div className="flex items-center justify-between px-1 pb-2">
                            <div className="font-display text-[14px]">ELEGÍ GRUPO</div>
                            <div className="font-mono text-[10px] opacity-65">{groupKeys.length} grupos</div>
                        </div>
                        <div className="flex gap-1.5 overflow-x-auto pr-[14px] pb-1.5">
                            {groupKeys.map(key => (
                                <GroupChip
                                    key={key}
                                    groupKey={key}
                                    active={key === activeGroup}
                                    done={isGroupDone(key) && key !== activeGroup}
                                    teams={(grouped[key] ?? []).flatMap(f => [
                                        f.home_team ? { flagUrl: f.home_team.flag_url } : null,
                                        f.away_team ? { flagUrl: f.away_team.flag_url } : null,
                                    ]).filter(Boolean).slice(0, 4)}
                                    onClick={() => setActiveGroup(key)}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* GroupPanel */}
                <div className="px-[14px] pt-3">
                    {activeGroup && (
                        <GroupPanel
                            groupKey={activeGroup}
                            fixtures={activeFixtures}
                            scores={scores}
                            isLocked={isLocked || isSubmitted}
                            onChange={handleChange}
                        />
                    )}
                    {!isGroupStage && (
                        <div className="text-center font-mono text-[11px] opacity-50 py-4">
                            Pasá los chips para ver otros grupos ↑
                        </div>
                    )}
                </div>
            </div>

            {/* Sticky CTA */}
            <div className="fixed bottom-[72px] left-0 right-0 bg-cream border-t-[3px] border-ink px-[14px] py-2.5 flex items-center gap-3 z-40">
                <div className="flex-1">
                    <div className="font-mono text-[10px] opacity-70 tracking-[.08em]">TU PUNTAJE ACTUAL</div>
                    <div className="font-display text-[18px] leading-none mt-0.5">
                        {auth.user.total_points ?? 0} PTS
                    </div>
                </div>
                <button
                    onClick={submit}
                    disabled={isLocked || isSubmitted}
                    className="py-3 px-4 bg-pop-red text-white font-display text-[13px] border-[2.5px] border-ink disabled:opacity-50"
                    style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    {isLocked ? 'BLOQUEADO 🔒' : isSubmitted ? 'ENVIADO ✓' : 'GUARDAR MIS GOLES →'}
                </button>
            </div>

            <TabBar active="matches" />
        </>
    );
}
```

**Nota:** `auth.user.total_points` solo está disponible si el middleware `HandleInertiaRequests` comparte el modelo completo del usuario. Si `total_points` no llega, agregar al `PredictionController@show`:

```php
// Al final del return Inertia::render(...)
'userStats' => [
    'totalPoints' => Auth::user()->total_points,
    'position'    => User::where('total_points', '>', Auth::user()->total_points)
                         ->where('is_active', true)->count() + 1,
],
```

Y usar `userStats.totalPoints` en el JSX en lugar de `auth.user.total_points`.

- [ ] **Step 12.2: Correr tests**

```bash
./vendor/bin/sail test
```

Si algún test de Predictions falla por el cambio en el render del Round (ahora siempre muestra el componente), verificar que el round usado en el test tiene `is_open=true`. La salida esperada es todos los tests pasando.

- [ ] **Step 12.3: Commit**

```bash
git add resources/js/Pages/Predictions/Round.jsx
git commit -m "feat: redesign Predictions/Round with pop-art style and group selector"
```

---

## Self-Review

**Spec coverage check:**
- ✅ MobileShell + TabBar navegacional → Task 1
- ✅ Ruta Splash en `/` → Task 5
- ✅ Ruta `/matches` → Task 2
- ✅ PredictionController redirect a Locked → Task 4
- ✅ RankingController: pozo, avatarColor, posición correcta → Task 3
- ✅ ChatController: liveMatch → Task 4
- ✅ Splash.jsx → Task 6
- ✅ Login refactor → Task 7
- ✅ Ranking.jsx con allTied/podium/list → Task 8
- ✅ Chat.jsx con Echo preservado → Task 9
- ✅ Matches.jsx con calendar/standings → Task 10
- ✅ Predictions/Locked.jsx → Task 11
- ✅ Predictions/Round.jsx refactor → Task 12
- ✅ Assets copiados a public/assets/ → Task 1

**Notas para el implementador:**
- `Mark26` y `PitchSwoosh` deben existir en `@/Components/icons/football`. Verificar el barrel export `index.js` antes de Task 7. Si no existen, omitir esas decoraciones.
- `total_points` en `auth.user` depende de qué campos comparte `HandleInertiaRequests`. Si no está disponible, usar el prop `userStats` adicional descrito en Task 12.
- Los tests PHP no cubren el JSX — verificar visualmente en el browser con `sail pnpm run dev`.
