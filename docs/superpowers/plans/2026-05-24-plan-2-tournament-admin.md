# Tournament Admin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the admin panel for managing the FIFA 2026 World Cup tournament — rounds, teams, fixtures, players, and users — so the admin can configure and operate the tournament end-to-end.

**Architecture:** Nine Inertia controllers under `App\Http\Controllers\Admin\`, each rendering a dedicated React page under `resources/js/Pages/Admin/`. All admin routes are grouped under `['auth', 'admin']` middleware. Tests use Pest v3 with `RefreshDatabase` and `$this->withoutVite()` for every GET that renders Inertia.

**Tech Stack:** Laravel 11, Inertia.js v2, React 18, Pest v3, Tailwind CSS, pnpm.

---

## File Map

### New files — Backend
| File | Responsibility |
|---|---|
| `app/Http/Controllers/Admin/DashboardController.php` | Stats summary for admin homepage |
| `app/Http/Controllers/Admin/RoundController.php` | List rounds; open / lock / finalize actions |
| `app/Http/Controllers/Admin/TeamController.php` | List and edit teams |
| `app/Http/Controllers/Admin/FixtureController.php` | Full CRUD for matches; score + status updates |
| `app/Http/Controllers/Admin/PlayerController.php` | CRUD players by team |
| `app/Http/Controllers/Admin/UserController.php` | List/create users; toggle access/pot; reopen predictions |

### New files — Frontend
| File | Responsibility |
|---|---|
| `resources/js/Layouts/AdminLayout.jsx` | Shared nav layout for all admin pages |
| `resources/js/Pages/Admin/Dashboard.jsx` | Stats dashboard *(replace existing stub)* |
| `resources/js/Pages/Admin/Rounds/Index.jsx` | Rounds list with open/lock/finalize buttons |
| `resources/js/Pages/Admin/Teams/Index.jsx` | Teams list grouped by group |
| `resources/js/Pages/Admin/Teams/Edit.jsx` | Team edit form |
| `resources/js/Pages/Admin/Fixtures/Index.jsx` | Fixtures list filtered by round |
| `resources/js/Pages/Admin/Fixtures/Create.jsx` | Create fixture form |
| `resources/js/Pages/Admin/Fixtures/Edit.jsx` | Edit fixture form (scores, status, team assignment) |
| `resources/js/Pages/Admin/Players/Index.jsx` | Players list + inline create/edit/delete |
| `resources/js/Pages/Admin/Users/Index.jsx` | Users list + actions |

### Modified files
| File | Change |
|---|---|
| `routes/web.php` | Expand admin route group with all resource routes |

### New files — Tests
| File | Covers |
|---|---|
| `tests/Feature/Admin/DashboardControllerTest.php` | Dashboard access + stats |
| `tests/Feature/Admin/RoundControllerTest.php` | Round list; open/lock/finalize |
| `tests/Feature/Admin/TeamControllerTest.php` | Team list; edit/update |
| `tests/Feature/Admin/FixtureControllerTest.php` | Fixture CRUD; score update; knockout team assignment |
| `tests/Feature/Admin/PlayerControllerTest.php` | Player CRUD |
| `tests/Feature/Admin/UserControllerTest.php` | User list/create; toggle active/pot; reopen predictions |

---

## Task 1: Admin Layout + Dashboard Controller + Routes

### Files
- Create: `resources/js/Layouts/AdminLayout.jsx`
- Modify: `resources/js/Pages/Admin/Dashboard.jsx`
- Create: `app/Http/Controllers/Admin/DashboardController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Admin/DashboardControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Admin/DashboardControllerTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows admin dashboard with stats', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->withoutVite()->actingAs($admin)->get('/admin');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Dashboard')
        ->has('stats')
    );
});

it('blocks non-admins from the dashboard', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->withoutVite()->actingAs($user)->get('/admin')->assertStatus(403);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/sail test tests/Feature/Admin/DashboardControllerTest.php
```

Expected: FAIL — `stats` prop not found (current route returns Inertia without props).

- [ ] **Step 3: Create DashboardController**

```php
<?php
// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Round;
use App\Models\Team;
use App\Models\Fixture;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'teams'    => Team::count(),
                'fixtures' => Fixture::count(),
                'rounds'   => Round::count(),
                'users'    => User::where('role', 'user')->count(),
                'pot'      => User::where('is_activated', true)->count() * 50,
            ],
        ]);
    }
}
```

- [ ] **Step 4: Register all admin routes**

Replace the existing admin group in `routes/web.php` with:

```php
<?php
// routes/web.php  (full file)

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FixtureController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\RoundController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Rounds
    Route::get('rounds', [RoundController::class, 'index'])->name('rounds.index');
    Route::post('rounds/{round}/open', [RoundController::class, 'open'])->name('rounds.open');
    Route::post('rounds/{round}/lock', [RoundController::class, 'lock'])->name('rounds.lock');
    Route::post('rounds/{round}/finalize', [RoundController::class, 'finalize'])->name('rounds.finalize');

    // Teams
    Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('teams/{team}/edit', [TeamController::class, 'edit'])->name('teams.edit');
    Route::patch('teams/{team}', [TeamController::class, 'update'])->name('teams.update');

    // Fixtures
    Route::get('fixtures', [FixtureController::class, 'index'])->name('fixtures.index');
    Route::get('fixtures/create', [FixtureController::class, 'create'])->name('fixtures.create');
    Route::post('fixtures', [FixtureController::class, 'store'])->name('fixtures.store');
    Route::get('fixtures/{fixture}/edit', [FixtureController::class, 'edit'])->name('fixtures.edit');
    Route::patch('fixtures/{fixture}', [FixtureController::class, 'update'])->name('fixtures.update');
    Route::delete('fixtures/{fixture}', [FixtureController::class, 'destroy'])->name('fixtures.destroy');

    // Players
    Route::get('players', [PlayerController::class, 'index'])->name('players.index');
    Route::post('players', [PlayerController::class, 'store'])->name('players.store');
    Route::patch('players/{player}', [PlayerController::class, 'update'])->name('players.update');
    Route::delete('players/{player}', [PlayerController::class, 'destroy'])->name('players.destroy');

    // Users
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('users/{user}/activate-pot', [UserController::class, 'activatePot'])->name('users.activate-pot');
    Route::post('users/{user}/deactivate-pot', [UserController::class, 'deactivatePot'])->name('users.deactivate-pot');
    Route::post('users/{user}/reopen-predictions', [UserController::class, 'reopenPredictions'])->name('users.reopen-predictions');
});
```

- [ ] **Step 5: Create AdminLayout**

```jsx
// resources/js/Layouts/AdminLayout.jsx

import { Link, usePage } from '@inertiajs/react';

export default function AdminLayout({ header, children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-gray-900 text-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center gap-6">
                            <Link href={route('admin.dashboard')} className="font-bold text-yellow-400">
                                PollaMundial Admin
                            </Link>
                            <Link href={route('admin.rounds.index')} className="text-sm hover:text-yellow-300">
                                Rondas
                            </Link>
                            <Link href={route('admin.teams.index')} className="text-sm hover:text-yellow-300">
                                Equipos
                            </Link>
                            <Link href={route('admin.fixtures.index')} className="text-sm hover:text-yellow-300">
                                Partidos
                            </Link>
                            <Link href={route('admin.players.index')} className="text-sm hover:text-yellow-300">
                                Jugadores
                            </Link>
                            <Link href={route('admin.users.index')} className="text-sm hover:text-yellow-300">
                                Usuarios
                            </Link>
                        </div>
                        <span className="text-sm text-gray-400">{auth.user.name}</span>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}
```

- [ ] **Step 6: Update Dashboard page**

```jsx
// resources/js/Pages/Admin/Dashboard.jsx

import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ stats }) {
    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Dashboard</h2>}>
            <Head title="Admin Dashboard" />

            <div className="grid grid-cols-2 gap-6 sm:grid-cols-5">
                {[
                    { label: 'Equipos', value: stats.teams },
                    { label: 'Partidos', value: stats.fixtures },
                    { label: 'Rondas', value: stats.rounds },
                    { label: 'Usuarios', value: stats.users },
                    { label: 'Pozo (coins)', value: stats.pot },
                ].map(({ label, value }) => (
                    <div key={label} className="rounded-lg bg-white p-6 shadow text-center">
                        <p className="text-3xl font-bold text-gray-900">{value}</p>
                        <p className="mt-1 text-sm text-gray-500">{label}</p>
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/DashboardControllerTest.php
```

Expected: 2 PASS (or DEPR — both OK).

Also verify existing AdminMiddlewareTest still passes:

```bash
./vendor/bin/sail test tests/Feature/AdminMiddlewareTest.php
```

Expected: 4 PASS.

- [ ] **Step 8: Commit**

```bash
git add routes/web.php \
        app/Http/Controllers/Admin/DashboardController.php \
        resources/js/Layouts/AdminLayout.jsx \
        resources/js/Pages/Admin/Dashboard.jsx \
        tests/Feature/Admin/DashboardControllerTest.php
git commit -m "feat: add admin layout, dashboard controller, and full route scaffold"
```

---

## Task 2: Round Admin — List + Open/Lock/Finalize

### Files
- Create: `app/Http/Controllers/Admin/RoundController.php`
- Create: `resources/js/Pages/Admin/Rounds/Index.jsx`
- Create: `tests/Feature/Admin/RoundControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/Admin/RoundControllerTest.php

use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('lists rounds', function () {
    Round::factory()->r1()->create();

    $response = $this->withoutVite()->actingAs(adminUser())->get('/admin/rounds');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Rounds/Index')
        ->has('rounds', 1)
    );
});

it('opens a round', function () {
    $round = Round::factory()->r1()->create(['is_open' => false]);

    $this->actingAs(adminUser())->post("/admin/rounds/{$round->id}/open");

    expect($round->fresh()->is_open)->toBeTrue();
});

it('locks a round', function () {
    $round = Round::factory()->r1()->create(['is_open' => true, 'is_locked' => false]);

    $this->actingAs(adminUser())->post("/admin/rounds/{$round->id}/lock");

    expect($round->fresh()->is_locked)->toBeTrue();
});

it('finalizes a round', function () {
    $round = Round::factory()->r1()->create(['is_locked' => false]);

    $this->actingAs(adminUser())->post("/admin/rounds/{$round->id}/finalize");

    expect($round->fresh()->is_locked)->toBeTrue();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Admin/RoundControllerTest.php
```

Expected: FAIL — RoundController not found.

- [ ] **Step 3: Create RoundController**

```php
<?php
// app/Http/Controllers/Admin/RoundController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Round;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoundController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Rounds/Index', [
            'rounds' => Round::orderBy('order')->get(),
        ]);
    }

    public function open(Round $round): RedirectResponse
    {
        $round->update(['is_open' => true]);

        return back()->with('status', "Ronda '{$round->name}' abierta.");
    }

    public function lock(Round $round): RedirectResponse
    {
        $round->update(['is_open' => false, 'is_locked' => true]);

        return back()->with('status', "Ronda '{$round->name}' bloqueada.");
    }

    public function finalize(Round $round): RedirectResponse
    {
        $round->update(['is_locked' => true]);
        // Plan 4 adds: RoundFinalized::dispatch($round);

        return back()->with('status', "Ronda '{$round->name}' finalizada.");
    }
}
```

- [ ] **Step 4: Create Rounds/Index page**

```jsx
// resources/js/Pages/Admin/Rounds/Index.jsx

import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';

export default function Index({ rounds }) {
    const action = (url) => router.post(url);

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Rondas</h2>}>
            <Head title="Admin — Rondas" />

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['#', 'Ronda', 'Pts Exacto', 'Pts Resultado', 'Pts Clasificado', 'Estado', 'Acciones'].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {rounds.map((round) => (
                            <tr key={round.id}>
                                <td className="px-4 py-3 text-sm text-gray-900">{round.order}</td>
                                <td className="px-4 py-3 text-sm font-medium text-gray-900">{round.name}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_exact}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_result}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_classifier}</td>
                                <td className="px-4 py-3 text-sm">
                                    {round.is_locked
                                        ? <span className="rounded bg-red-100 px-2 py-1 text-xs text-red-700">Bloqueada</span>
                                        : round.is_open
                                            ? <span className="rounded bg-green-100 px-2 py-1 text-xs text-green-700">Abierta</span>
                                            : <span className="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">Cerrada</span>
                                    }
                                </td>
                                <td className="flex gap-2 px-4 py-3">
                                    {!round.is_open && !round.is_locked && (
                                        <button onClick={() => action(route('admin.rounds.open', round.id))}
                                            className="rounded bg-green-600 px-3 py-1 text-xs text-white hover:bg-green-700">
                                            Abrir
                                        </button>
                                    )}
                                    {round.is_open && !round.is_locked && (
                                        <button onClick={() => action(route('admin.rounds.lock', round.id))}
                                            className="rounded bg-yellow-600 px-3 py-1 text-xs text-white hover:bg-yellow-700">
                                            Bloquear
                                        </button>
                                    )}
                                    {round.is_locked && (
                                        <button onClick={() => action(route('admin.rounds.finalize', round.id))}
                                            className="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700">
                                            Finalizar
                                        </button>
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

- [ ] **Step 5: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/RoundControllerTest.php
```

Expected: 4 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/RoundController.php \
        resources/js/Pages/Admin/Rounds/Index.jsx \
        tests/Feature/Admin/RoundControllerTest.php
git commit -m "feat: add admin round management (open/lock/finalize)"
```

---

## Task 3: Team Admin — List + Edit/Update

### Files
- Create: `app/Http/Controllers/Admin/TeamController.php`
- Create: `resources/js/Pages/Admin/Teams/Index.jsx`
- Create: `resources/js/Pages/Admin/Teams/Edit.jsx`
- Create: `tests/Feature/Admin/TeamControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/Admin/TeamControllerTest.php

use App\Models\Group;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('lists teams with their groups', function () {
    $group = Group::factory()->create(['name' => 'A']);
    Team::factory()->create(['group_id' => $group->id, 'name' => 'Argentina', 'fifa_code' => 'ARG']);

    $response = $this->withoutVite()->actingAs(adminUser())->get('/admin/teams');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Teams/Index')
        ->has('teams', 1)
    );
});

it('shows the edit form for a team', function () {
    $group = Group::factory()->create(['name' => 'A']);
    $team  = Team::factory()->create(['group_id' => $group->id]);

    $response = $this->withoutVite()->actingAs(adminUser())->get("/admin/teams/{$team->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Teams/Edit')
        ->has('team')
        ->has('groups')
    );
});

it('updates a team', function () {
    $group = Group::factory()->create(['name' => 'A']);
    $team  = Team::factory()->create(['group_id' => $group->id, 'name' => 'Old Name']);

    $this->actingAs(adminUser())->patch("/admin/teams/{$team->id}", [
        'name'     => 'Argentina',
        'fifa_code' => 'ARG',
        'flag_url' => 'https://example.com/arg.svg',
        'group_id' => $group->id,
    ]);

    expect($team->fresh()->name)->toBe('Argentina');
});

it('requires name and fifa_code to update a team', function () {
    $group = Group::factory()->create(['name' => 'A']);
    $team  = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs(adminUser())
        ->patch("/admin/teams/{$team->id}", ['name' => '', 'fifa_code' => ''])
        ->assertSessionHasErrors(['name', 'fifa_code']);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Admin/TeamControllerTest.php
```

Expected: FAIL — TeamController not found.

- [ ] **Step 3: Check Group factory exists**

```bash
./vendor/bin/sail artisan tinker --execute="echo class_exists(\Database\Factories\GroupFactory::class) ? 'yes' : 'no';"
```

If output is `no`, create the factory:

```php
<?php
// database/factories/GroupFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomLetter(),
        ];
    }
}
```

Also verify `app/Models/Group.php` has `use HasFactory;` and `$fillable = ['name']`.

- [ ] **Step 4: Create TeamController**

```php
<?php
// app/Http/Controllers/Admin/TeamController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Teams/Index', [
            'teams' => Team::with('group')->orderBy('name')->get(),
        ]);
    }

    public function edit(Team $team): Response
    {
        return Inertia::render('Admin/Teams/Edit', [
            'team'   => $team->load('group'),
            'groups' => Group::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'fifa_code' => ['required', 'string', 'size:3'],
            'flag_url' => ['nullable', 'string', 'max:500'],
            'group_id' => ['required', 'exists:groups,id'],
        ]);

        $team->update($data);

        return redirect()->route('admin.teams.index')->with('status', "Equipo '{$team->name}' actualizado.");
    }
}
```

- [ ] **Step 5: Create Teams/Index page**

```jsx
// resources/js/Pages/Admin/Teams/Index.jsx

import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ teams }) {
    const byGroup = teams.reduce((acc, team) => {
        const g = team.group?.name ?? '?';
        if (!acc[g]) acc[g] = [];
        acc[g].push(team);
        return acc;
    }, {});

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Equipos</h2>}>
            <Head title="Admin — Equipos" />

            {Object.entries(byGroup).sort(([a], [b]) => a.localeCompare(b)).map(([group, groupTeams]) => (
                <div key={group} className="mb-6">
                    <h3 className="mb-2 text-sm font-bold uppercase tracking-wider text-gray-500">Grupo {group}</h3>
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    {['Nombre', 'FIFA Code', 'Flag URL', ''].map(h => (
                                        <th key={h} className="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {groupTeams.map(team => (
                                    <tr key={team.id}>
                                        <td className="px-4 py-2 text-sm font-medium text-gray-900">{team.name}</td>
                                        <td className="px-4 py-2 text-sm text-gray-600">{team.fifa_code}</td>
                                        <td className="px-4 py-2 text-sm text-gray-400 truncate max-w-xs">{team.flag_url ?? '—'}</td>
                                        <td className="px-4 py-2 text-sm">
                                            <Link href={route('admin.teams.edit', team.id)}
                                                className="text-indigo-600 hover:text-indigo-800">
                                                Editar
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            ))}
        </AdminLayout>
    );
}
```

- [ ] **Step 6: Create Teams/Edit page**

```jsx
// resources/js/Pages/Admin/Teams/Edit.jsx

import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ team, groups }) {
    const { data, setData, patch, processing, errors } = useForm({
        name:     team.name,
        fifa_code: team.fifa_code,
        flag_url: team.flag_url ?? '',
        group_id: team.group_id,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('admin.teams.update', team.id));
    };

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Editar Equipo</h2>}>
            <Head title={`Editar ${team.name}`} />

            <div className="max-w-xl rounded-lg bg-white p-6 shadow">
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                        {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">FIFA Code</label>
                        <input type="text" maxLength={3} value={data.fifa_code} onChange={e => setData('fifa_code', e.target.value.toUpperCase())}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                        {errors.fifa_code && <p className="mt-1 text-xs text-red-600">{errors.fifa_code}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Flag URL</label>
                        <input type="text" value={data.flag_url} onChange={e => setData('flag_url', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                        {errors.flag_url && <p className="mt-1 text-xs text-red-600">{errors.flag_url}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Grupo</label>
                        <select value={data.group_id} onChange={e => setData('group_id', Number(e.target.value))}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                            {groups.map(g => (
                                <option key={g.id} value={g.id}>Grupo {g.name}</option>
                            ))}
                        </select>
                        {errors.group_id && <p className="mt-1 text-xs text-red-600">{errors.group_id}</p>}
                    </div>

                    <div className="flex items-center gap-4">
                        <button type="submit" disabled={processing}
                            className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Guardar
                        </button>
                        <Link href={route('admin.teams.index')} className="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/TeamControllerTest.php
```

Expected: 4 PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Admin/TeamController.php \
        resources/js/Pages/Admin/Teams/Index.jsx \
        resources/js/Pages/Admin/Teams/Edit.jsx \
        tests/Feature/Admin/TeamControllerTest.php
git commit -m "feat: add admin team management (list + edit)"
```

---

## Task 4: Fixture Admin — Index + Create/Store

### Files
- Create: `app/Http/Controllers/Admin/FixtureController.php`
- Create: `resources/js/Pages/Admin/Fixtures/Index.jsx`
- Create: `resources/js/Pages/Admin/Fixtures/Create.jsx`
- Create: `tests/Feature/Admin/FixtureControllerTest.php` (index + store tests)

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/Admin/FixtureControllerTest.php

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('lists fixtures filtered by round', function () {
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create(['name' => 'A']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    Fixture::factory()->create([
        'round_id'    => $round->id,
        'group_id'    => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs(adminUser())
        ->get('/admin/fixtures?round_id=' . $round->id);

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Fixtures/Index')
        ->has('fixtures', 1)
        ->has('rounds')
    );
});

it('shows the create fixture form', function () {
    Round::factory()->r1()->create();

    $response = $this->withoutVite()->actingAs(adminUser())->get('/admin/fixtures/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Fixtures/Create')
        ->has('rounds')
        ->has('groups')
        ->has('teams')
    );
});

it('creates a group stage fixture', function () {
    $round = Round::factory()->r1()->create();
    $group = Group::factory()->create(['name' => 'A']);
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs(adminUser())->post('/admin/fixtures', [
        'round_id'    => $round->id,
        'group_id'    => $group->id,
        'match_number' => 1,
        'match_date'  => '2026-06-11 12:00:00',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    expect(Fixture::count())->toBe(1);
    expect(Fixture::first()->round_id)->toBe($round->id);
});

it('creates a knockout fixture with placeholders', function () {
    $round = Round::factory()->r2()->create();

    $this->actingAs(adminUser())->post('/admin/fixtures', [
        'round_id'         => $round->id,
        'match_number'     => 73,
        'match_date'       => '2026-06-29 16:00:00',
        'home_placeholder' => 'Ganador Grupo A',
        'away_placeholder' => 'Ganador Grupo B',
    ]);

    $fixture = Fixture::first();
    expect($fixture->home_placeholder)->toBe('Ganador Grupo A');
    expect($fixture->home_team_id)->toBeNull();
});

it('requires round_id and match_number to create a fixture', function () {
    $this->actingAs(adminUser())->post('/admin/fixtures', [])
        ->assertSessionHasErrors(['round_id', 'match_number', 'match_date']);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Admin/FixtureControllerTest.php
```

Expected: FAIL — FixtureController not found.

- [ ] **Step 3: Check FixtureFactory exists**

```bash
ls database/factories/FixtureFactory.php
```

If missing:

```php
<?php
// database/factories/FixtureFactory.php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

class FixtureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'round_id'     => Round::factory()->r1(),
            'group_id'     => Group::factory(),
            'match_number' => fake()->unique()->numberBetween(1, 104),
            'match_date'   => fake()->dateTimeBetween('2026-06-11', '2026-07-19'),
            'status'       => 'scheduled',
        ];
    }
}
```

Also ensure `Fixture` model has `use HasFactory;`.

- [ ] **Step 4: Create FixtureController (index, create, store)**

```php
<?php
// app/Http/Controllers/Admin/FixtureController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FixtureController extends Controller
{
    public function index(Request $request): Response
    {
        $roundId  = $request->query('round_id');
        $fixtures = Fixture::with(['round', 'group', 'homeTeam', 'awayTeam', 'winnerTeam'])
            ->when($roundId, fn ($q) => $q->where('round_id', $roundId))
            ->orderBy('match_number')
            ->get();

        return Inertia::render('Admin/Fixtures/Index', [
            'fixtures'        => $fixtures,
            'rounds'          => Round::orderBy('order')->get(),
            'selectedRoundId' => $roundId ? (int) $roundId : null,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Fixtures/Create', [
            'rounds' => Round::orderBy('order')->get(),
            'groups' => Group::orderBy('name')->get(),
            'teams'  => Team::with('group')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'round_id'         => ['required', 'exists:rounds,id'],
            'group_id'         => ['nullable', 'exists:groups,id'],
            'match_number'     => ['required', 'integer', 'min:1', 'max:104', 'unique:matches,match_number'],
            'match_date'       => ['required', 'date'],
            'home_team_id'     => ['nullable', 'exists:teams,id'],
            'away_team_id'     => ['nullable', 'exists:teams,id'],
            'home_placeholder' => ['nullable', 'string', 'max:100'],
            'away_placeholder' => ['nullable', 'string', 'max:100'],
        ]);

        Fixture::create($data);

        return redirect()->route('admin.fixtures.index', ['round_id' => $data['round_id']])
            ->with('status', 'Partido creado.');
    }

    public function edit(Fixture $fixture): Response
    {
        return Inertia::render('Admin/Fixtures/Edit', [
            'fixture' => $fixture->load(['round', 'group', 'homeTeam', 'awayTeam', 'winnerTeam']),
            'rounds'  => Round::orderBy('order')->get(),
            'groups'  => Group::orderBy('name')->get(),
            'teams'   => Team::with('group')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Fixture $fixture): RedirectResponse
    {
        $data = $request->validate([
            'round_id'           => ['required', 'exists:rounds,id'],
            'group_id'           => ['nullable', 'exists:groups,id'],
            'match_number'       => ['required', 'integer', 'min:1', 'max:104', 'unique:matches,match_number,' . $fixture->id],
            'match_date'         => ['required', 'date'],
            'home_team_id'       => ['nullable', 'exists:teams,id'],
            'away_team_id'       => ['nullable', 'exists:teams,id'],
            'home_placeholder'   => ['nullable', 'string', 'max:100'],
            'away_placeholder'   => ['nullable', 'string', 'max:100'],
            'home_score'         => ['nullable', 'integer', 'min:0'],
            'away_score'         => ['nullable', 'integer', 'min:0'],
            'winner_team_id'     => ['nullable', 'exists:teams,id'],
            'went_to_extra_time' => ['boolean'],
            'status'             => ['required', 'in:scheduled,in_progress,finished'],
        ]);

        $fixture->update($data);

        return redirect()->route('admin.fixtures.index', ['round_id' => $fixture->round_id])
            ->with('status', "Partido #{$fixture->match_number} actualizado.");
    }

    public function destroy(Fixture $fixture): RedirectResponse
    {
        $roundId = $fixture->round_id;
        $fixture->delete();

        return redirect()->route('admin.fixtures.index', ['round_id' => $roundId])
            ->with('status', 'Partido eliminado.');
    }
}
```

- [ ] **Step 5: Create Fixtures/Index page**

```jsx
// resources/js/Pages/Admin/Fixtures/Index.jsx

import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

const STATUS_LABELS = {
    scheduled:   { label: 'Programado', cls: 'bg-gray-100 text-gray-600' },
    in_progress: { label: 'En Juego',   cls: 'bg-green-100 text-green-700' },
    finished:    { label: 'Finalizado', cls: 'bg-blue-100 text-blue-700' },
};

export default function Index({ fixtures, rounds, selectedRoundId }) {
    const filterRound = (id) => router.get('/admin/fixtures', { round_id: id }, { preserveState: true });

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Partidos</h2>}>
            <Head title="Admin — Partidos" />

            <div className="mb-4 flex items-center gap-4">
                <div className="flex gap-2">
                    <button onClick={() => filterRound('')}
                        className={`rounded px-3 py-1 text-sm ${!selectedRoundId ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 shadow'}`}>
                        Todos
                    </button>
                    {rounds.map(r => (
                        <button key={r.id} onClick={() => filterRound(r.id)}
                            className={`rounded px-3 py-1 text-sm ${selectedRoundId === r.id ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 shadow'}`}>
                            {r.name}
                        </button>
                    ))}
                </div>
                <Link href={route('admin.fixtures.create')}
                    className="ml-auto rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    + Nuevo Partido
                </Link>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['#', 'Fecha', 'Local', 'Score', 'Visitante', 'Estado', ''].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {fixtures.map(f => {
                            const { label, cls } = STATUS_LABELS[f.status] ?? STATUS_LABELS.scheduled;
                            const home = f.home_team?.name ?? f.home_placeholder ?? '—';
                            const away = f.away_team?.name ?? f.away_placeholder ?? '—';
                            return (
                                <tr key={f.id}>
                                    <td className="px-4 py-3 text-sm text-gray-500">{f.match_number}</td>
                                    <td className="px-4 py-3 text-sm text-gray-600">{f.match_date ? new Date(f.match_date).toLocaleString('es-CL') : '—'}</td>
                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{home}</td>
                                    <td className="px-4 py-3 text-sm font-mono text-gray-900">
                                        {f.home_score !== null ? `${f.home_score} - ${f.away_score}` : '— - —'}
                                    </td>
                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{away}</td>
                                    <td className="px-4 py-3 text-sm">
                                        <span className={`rounded px-2 py-1 text-xs ${cls}`}>{label}</span>
                                    </td>
                                    <td className="px-4 py-3 text-sm">
                                        <Link href={route('admin.fixtures.edit', f.id)}
                                            className="mr-3 text-indigo-600 hover:text-indigo-800">Editar</Link>
                                        <button onClick={() => {
                                            if (confirm('¿Eliminar partido?')) router.delete(route('admin.fixtures.destroy', f.id));
                                        }} className="text-red-600 hover:text-red-800">Eliminar</button>
                                    </td>
                                </tr>
                            );
                        })}
                        {fixtures.length === 0 && (
                            <tr>
                                <td colSpan={7} className="px-4 py-8 text-center text-sm text-gray-500">
                                    No hay partidos. <Link href={route('admin.fixtures.create')} className="text-indigo-600">Crear uno</Link>.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 6: Create Fixtures/Create page**

```jsx
// resources/js/Pages/Admin/Fixtures/Create.jsx

import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ rounds, groups, teams }) {
    const { data, setData, post, processing, errors } = useForm({
        round_id:         '',
        group_id:         '',
        match_number:     '',
        match_date:       '',
        home_team_id:     '',
        away_team_id:     '',
        home_placeholder: '',
        away_placeholder: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.fixtures.store'));
    };

    const selectedRound = rounds.find(r => r.id === Number(data.round_id));
    const isGroupStage  = selectedRound?.slug === 'grupos';
    const groupTeams    = data.group_id ? teams.filter(t => t.group_id === Number(data.group_id)) : teams;

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Nuevo Partido</h2>}>
            <Head title="Admin — Nuevo Partido" />

            <div className="max-w-xl rounded-lg bg-white p-6 shadow">
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Ronda</label>
                        <select value={data.round_id} onChange={e => setData('round_id', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                            <option value="">Seleccionar ronda…</option>
                            {rounds.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
                        </select>
                        {errors.round_id && <p className="mt-1 text-xs text-red-600">{errors.round_id}</p>}
                    </div>

                    {isGroupStage && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Grupo</label>
                            <select value={data.group_id} onChange={e => setData('group_id', e.target.value)}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                <option value="">Seleccionar grupo…</option>
                                {groups.map(g => <option key={g.id} value={g.id}>Grupo {g.name}</option>)}
                            </select>
                            {errors.group_id && <p className="mt-1 text-xs text-red-600">{errors.group_id}</p>}
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Número de Partido</label>
                            <input type="number" min="1" max="104" value={data.match_number}
                                onChange={e => setData('match_number', e.target.value)}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            {errors.match_number && <p className="mt-1 text-xs text-red-600">{errors.match_number}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Fecha y Hora</label>
                            <input type="datetime-local" value={data.match_date}
                                onChange={e => setData('match_date', e.target.value)}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            {errors.match_date && <p className="mt-1 text-xs text-red-600">{errors.match_date}</p>}
                        </div>
                    </div>

                    {isGroupStage ? (
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Local</label>
                                <select value={data.home_team_id} onChange={e => setData('home_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Equipo —</option>
                                    {groupTeams.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Visitante</label>
                                <select value={data.away_team_id} onChange={e => setData('away_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Equipo —</option>
                                    {groupTeams.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                                </select>
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Placeholder Local</label>
                                <input type="text" value={data.home_placeholder}
                                    onChange={e => setData('home_placeholder', e.target.value)}
                                    placeholder="ej. Ganador Grupo A"
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Placeholder Visitante</label>
                                <input type="text" value={data.away_placeholder}
                                    onChange={e => setData('away_placeholder', e.target.value)}
                                    placeholder="ej. 2do Grupo B"
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            </div>
                        </div>
                    )}

                    <div className="flex items-center gap-4">
                        <button type="submit" disabled={processing}
                            className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Crear Partido
                        </button>
                        <Link href={route('admin.fixtures.index')} className="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/FixtureControllerTest.php
```

Expected: 5 PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Admin/FixtureController.php \
        resources/js/Pages/Admin/Fixtures/Index.jsx \
        resources/js/Pages/Admin/Fixtures/Create.jsx \
        tests/Feature/Admin/FixtureControllerTest.php
git commit -m "feat: add admin fixture management (index + create)"
```

---

## Task 5: Fixture Admin — Edit/Update + Destroy

### Files
- Modify: `tests/Feature/Admin/FixtureControllerTest.php` (add edit/update/destroy tests)
- Create: `resources/js/Pages/Admin/Fixtures/Edit.jsx`

Note: `FixtureController::edit()`, `update()`, and `destroy()` were already written in Task 4 — only the frontend page and tests remain.

- [ ] **Step 1: Add failing tests for edit/update/destroy**

Append to `tests/Feature/Admin/FixtureControllerTest.php`:

```php
it('shows the edit form for a fixture', function () {
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create(['name' => 'A']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'    => $round->id,
        'group_id'    => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs(adminUser())
        ->get("/admin/fixtures/{$fixture->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Fixtures/Edit')
        ->has('fixture')
        ->has('teams')
    );
});

it('updates a fixture score and status', function () {
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create(['name' => 'A']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'    => $round->id,
        'group_id'    => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'      => 'scheduled',
    ]);

    $this->actingAs(adminUser())->patch("/admin/fixtures/{$fixture->id}", [
        'round_id'           => $round->id,
        'group_id'           => $group->id,
        'match_number'       => $fixture->match_number,
        'match_date'         => $fixture->match_date->format('Y-m-d H:i:s'),
        'home_team_id'       => $home->id,
        'away_team_id'       => $away->id,
        'home_score'         => 2,
        'away_score'         => 1,
        'winner_team_id'     => $home->id,
        'went_to_extra_time' => false,
        'status'             => 'finished',
    ]);

    $fresh = $fixture->fresh();
    expect($fresh->home_score)->toBe(2);
    expect($fresh->status)->toBe('finished');
});

it('assigns real teams to a knockout fixture', function () {
    $round   = Round::factory()->r2()->create();
    $groupA  = Group::factory()->create(['name' => 'A']);
    $groupB  = Group::factory()->create(['name' => 'B']);
    $teamA   = Team::factory()->create(['group_id' => $groupA->id]);
    $teamB   = Team::factory()->create(['group_id' => $groupB->id]);
    $fixture = Fixture::factory()->create([
        'round_id'         => $round->id,
        'home_placeholder' => 'Ganador Grupo A',
        'away_placeholder' => 'Ganador Grupo B',
    ]);

    $this->actingAs(adminUser())->patch("/admin/fixtures/{$fixture->id}", [
        'round_id'           => $round->id,
        'match_number'       => $fixture->match_number,
        'match_date'         => $fixture->match_date->format('Y-m-d H:i:s'),
        'home_team_id'       => $teamA->id,
        'away_team_id'       => $teamB->id,
        'went_to_extra_time' => false,
        'status'             => 'scheduled',
    ]);

    $fresh = $fixture->fresh();
    expect($fresh->home_team_id)->toBe($teamA->id);
    expect($fresh->away_team_id)->toBe($teamB->id);
});

it('deletes a fixture', function () {
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create(['name' => 'A']);
    $fixture = Fixture::factory()->create(['round_id' => $round->id, 'group_id' => $group->id]);

    $this->actingAs(adminUser())->delete("/admin/fixtures/{$fixture->id}");

    expect(Fixture::count())->toBe(0);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Admin/FixtureControllerTest.php --filter "edit\|update\|assign\|delete"
```

Expected: FAIL — `Admin/Fixtures/Edit` Inertia component missing.

- [ ] **Step 3: Create Fixtures/Edit page**

```jsx
// resources/js/Pages/Admin/Fixtures/Edit.jsx

import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ fixture, rounds, groups, teams }) {
    const { data, setData, patch, processing, errors } = useForm({
        round_id:           fixture.round_id,
        group_id:           fixture.group_id ?? '',
        match_number:       fixture.match_number,
        match_date:         fixture.match_date ? fixture.match_date.slice(0, 16) : '',
        home_team_id:       fixture.home_team_id ?? '',
        away_team_id:       fixture.away_team_id ?? '',
        home_placeholder:   fixture.home_placeholder ?? '',
        away_placeholder:   fixture.away_placeholder ?? '',
        home_score:         fixture.home_score ?? '',
        away_score:         fixture.away_score ?? '',
        winner_team_id:     fixture.winner_team_id ?? '',
        went_to_extra_time: fixture.went_to_extra_time ?? false,
        status:             fixture.status,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('admin.fixtures.update', fixture.id));
    };

    const bothTeamsSet = data.home_team_id && data.away_team_id;
    const isKnockout   = !fixture.group_id && !fixture.home_team_id;

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Editar Partido #{fixture.match_number}</h2>}>
            <Head title={`Editar Partido #${fixture.match_number}`} />

            <div className="max-w-2xl rounded-lg bg-white p-6 shadow">
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Ronda</label>
                            <select value={data.round_id} onChange={e => setData('round_id', Number(e.target.value))}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                {rounds.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Número de Partido</label>
                            <input type="number" value={data.match_number}
                                onChange={e => setData('match_number', e.target.value)}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            {errors.match_number && <p className="mt-1 text-xs text-red-600">{errors.match_number}</p>}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Fecha y Hora</label>
                        <input type="datetime-local" value={data.match_date}
                            onChange={e => setData('match_date', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                        {errors.match_date && <p className="mt-1 text-xs text-red-600">{errors.match_date}</p>}
                    </div>

                    <fieldset className="rounded border border-gray-200 p-4">
                        <legend className="px-2 text-sm font-medium text-gray-600">Equipos</legend>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Local</label>
                                <select value={data.home_team_id} onChange={e => setData('home_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Por definir —</option>
                                    {teams.map(t => <option key={t.id} value={t.id}>{t.name} ({t.group?.name})</option>)}
                                </select>
                                {!data.home_team_id && (
                                    <input type="text" value={data.home_placeholder}
                                        onChange={e => setData('home_placeholder', e.target.value)}
                                        placeholder="Placeholder"
                                        className="mt-2 block w-full rounded border-gray-300 shadow-sm text-sm" />
                                )}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Visitante</label>
                                <select value={data.away_team_id} onChange={e => setData('away_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Por definir —</option>
                                    {teams.map(t => <option key={t.id} value={t.id}>{t.name} ({t.group?.name})</option>)}
                                </select>
                                {!data.away_team_id && (
                                    <input type="text" value={data.away_placeholder}
                                        onChange={e => setData('away_placeholder', e.target.value)}
                                        placeholder="Placeholder"
                                        className="mt-2 block w-full rounded border-gray-300 shadow-sm text-sm" />
                                )}
                            </div>
                        </div>
                    </fieldset>

                    <fieldset className="rounded border border-gray-200 p-4">
                        <legend className="px-2 text-sm font-medium text-gray-600">Resultado</legend>
                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Goles Local (90')</label>
                                <input type="number" min="0" value={data.home_score}
                                    onChange={e => setData('home_score', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Goles Visitante (90')</label>
                                <input type="number" min="0" value={data.away_score}
                                    onChange={e => setData('away_score', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Ganador Real</label>
                                <select value={data.winner_team_id} onChange={e => setData('winner_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Ninguno —</option>
                                    {data.home_team_id && teams.find(t => t.id === Number(data.home_team_id)) && (
                                        <option value={data.home_team_id}>
                                            {teams.find(t => t.id === Number(data.home_team_id))?.name}
                                        </option>
                                    )}
                                    {data.away_team_id && teams.find(t => t.id === Number(data.away_team_id)) && (
                                        <option value={data.away_team_id}>
                                            {teams.find(t => t.id === Number(data.away_team_id))?.name}
                                        </option>
                                    )}
                                </select>
                            </div>
                        </div>
                        <div className="mt-3 flex items-center gap-2">
                            <input type="checkbox" id="extra_time" checked={data.went_to_extra_time}
                                onChange={e => setData('went_to_extra_time', e.target.checked)} />
                            <label htmlFor="extra_time" className="text-sm text-gray-700">Fue a tiempo extra / penales</label>
                        </div>
                    </fieldset>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Estado</label>
                        <select value={data.status} onChange={e => setData('status', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                            <option value="scheduled">Programado</option>
                            <option value="in_progress">En Juego</option>
                            <option value="finished">Finalizado</option>
                        </select>
                    </div>

                    <div className="flex items-center gap-4">
                        <button type="submit" disabled={processing}
                            className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Guardar Cambios
                        </button>
                        <Link href={route('admin.fixtures.index', { round_id: fixture.round_id })}
                            className="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 4: Run all fixture tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/FixtureControllerTest.php
```

Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Admin/Fixtures/Edit.jsx \
        tests/Feature/Admin/FixtureControllerTest.php
git commit -m "feat: add admin fixture edit/update/destroy with knockout team assignment"
```

---

## Task 6: Player Admin — CRUD

### Files
- Create: `app/Http/Controllers/Admin/PlayerController.php`
- Create: `resources/js/Pages/Admin/Players/Index.jsx`
- Create: `tests/Feature/Admin/PlayerControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/Admin/PlayerControllerTest.php

use App\Models\Group;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('lists players with their teams', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $team   = Team::factory()->create(['group_id' => $group->id, 'name' => 'Argentina']);
    Player::factory()->create(['team_id' => $team->id, 'name' => 'Messi']);

    $response = $this->withoutVite()->actingAs(adminUser())->get('/admin/players');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Players/Index')
        ->has('players', 1)
        ->has('teams')
    );
});

it('creates a player', function () {
    $group = Group::factory()->create(['name' => 'A']);
    $team  = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs(adminUser())->post('/admin/players', [
        'team_id' => $team->id,
        'name'    => 'Lautaro Martínez',
    ]);

    expect(Player::count())->toBe(1);
    expect(Player::first()->name)->toBe('Lautaro Martínez');
});

it('requires team_id and name to create a player', function () {
    $this->actingAs(adminUser())
        ->post('/admin/players', ['name' => '', 'team_id' => ''])
        ->assertSessionHasErrors(['name', 'team_id']);
});

it('updates a player', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $team   = Team::factory()->create(['group_id' => $group->id]);
    $player = Player::factory()->create(['team_id' => $team->id, 'name' => 'Old Name']);

    $this->actingAs(adminUser())->patch("/admin/players/{$player->id}", [
        'team_id' => $team->id,
        'name'    => 'Julián Álvarez',
    ]);

    expect($player->fresh()->name)->toBe('Julián Álvarez');
});

it('deletes a player', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $team   = Team::factory()->create(['group_id' => $group->id]);
    $player = Player::factory()->create(['team_id' => $team->id]);

    $this->actingAs(adminUser())->delete("/admin/players/{$player->id}");

    expect(Player::count())->toBe(0);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Admin/PlayerControllerTest.php
```

Expected: FAIL — PlayerController not found.

- [ ] **Step 3: Check PlayerFactory exists**

```bash
ls database/factories/PlayerFactory.php
```

If missing:

```php
<?php
// database/factories/PlayerFactory.php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name'    => fake()->name(),
        ];
    }
}
```

Also verify `app/Models/Player.php` has `use HasFactory;` and `$fillable = ['team_id', 'name']`.

- [ ] **Step 4: Create PlayerController**

```php
<?php
// app/Http/Controllers/Admin/PlayerController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Players/Index', [
            'players' => Player::with('team.group')->orderBy('name')->get(),
            'teams'   => Team::with('group')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'name'    => ['required', 'string', 'max:100'],
        ]);

        Player::create($data);

        return back()->with('status', 'Jugador creado.');
    }

    public function update(Request $request, Player $player): RedirectResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'name'    => ['required', 'string', 'max:100'],
        ]);

        $player->update($data);

        return back()->with('status', 'Jugador actualizado.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        return back()->with('status', 'Jugador eliminado.');
    }
}
```

- [ ] **Step 5: Create Players/Index page**

```jsx
// resources/js/Pages/Admin/Players/Index.jsx

import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

function PlayerRow({ player, teams, onDeleted }) {
    const [editing, setEditing] = useState(false);
    const { data, setData, patch, processing } = useForm({
        team_id: player.team_id,
        name:    player.name,
    });

    const save = (e) => {
        e.preventDefault();
        patch(route('admin.players.update', player.id), {
            onSuccess: () => setEditing(false),
        });
    };

    if (editing) {
        return (
            <tr>
                <td className="px-4 py-2">
                    <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                        className="w-full rounded border-gray-300 text-sm shadow-sm" />
                </td>
                <td className="px-4 py-2">
                    <select value={data.team_id} onChange={e => setData('team_id', Number(e.target.value))}
                        className="w-full rounded border-gray-300 text-sm shadow-sm">
                        {teams.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                    </select>
                </td>
                <td className="px-4 py-2 flex gap-2">
                    <button onClick={save} disabled={processing}
                        className="rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700">Guardar</button>
                    <button onClick={() => setEditing(false)}
                        className="rounded bg-gray-200 px-2 py-1 text-xs text-gray-700 hover:bg-gray-300">Cancelar</button>
                </td>
            </tr>
        );
    }

    return (
        <tr>
            <td className="px-4 py-2 text-sm text-gray-900">{player.name}</td>
            <td className="px-4 py-2 text-sm text-gray-600">{player.team?.name ?? '—'}</td>
            <td className="px-4 py-2 flex gap-2">
                <button onClick={() => setEditing(true)}
                    className="text-indigo-600 hover:text-indigo-800 text-sm">Editar</button>
                <button onClick={() => { if (confirm('¿Eliminar jugador?')) router.delete(route('admin.players.destroy', player.id)); }}
                    className="text-red-600 hover:text-red-800 text-sm">Eliminar</button>
            </td>
        </tr>
    );
}

export default function Index({ players, teams }) {
    const { data, setData, post, processing, reset } = useForm({ team_id: '', name: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.players.store'), { onSuccess: () => reset() });
    };

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Jugadores</h2>}>
            <Head title="Admin — Jugadores" />

            <div className="mb-6 max-w-lg rounded-lg bg-white p-4 shadow">
                <h3 className="mb-3 text-sm font-medium text-gray-700">Agregar Jugador</h3>
                <form onSubmit={submit} className="flex gap-3">
                    <select value={data.team_id} onChange={e => setData('team_id', e.target.value)}
                        className="flex-1 rounded border-gray-300 text-sm shadow-sm">
                        <option value="">Equipo…</option>
                        {teams.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                    </select>
                    <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                        placeholder="Nombre del jugador"
                        className="flex-1 rounded border-gray-300 text-sm shadow-sm" />
                    <button type="submit" disabled={processing}
                        className="rounded bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700 disabled:opacity-50">
                        Agregar
                    </button>
                </form>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['Jugador', 'Equipo', 'Acciones'].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {players.map(p => (
                            <PlayerRow key={p.id} player={p} teams={teams} />
                        ))}
                        {players.length === 0 && (
                            <tr>
                                <td colSpan={3} className="px-4 py-8 text-center text-sm text-gray-500">
                                    No hay jugadores registrados.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/PlayerControllerTest.php
```

Expected: 5 PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Admin/PlayerController.php \
        resources/js/Pages/Admin/Players/Index.jsx \
        tests/Feature/Admin/PlayerControllerTest.php
git commit -m "feat: add admin player management (CRUD)"
```

---

## Task 7: User Admin — Index + Create

### Files
- Create: `app/Http/Controllers/Admin/UserController.php`
- Create: `resources/js/Pages/Admin/Users/Index.jsx`
- Create: `tests/Feature/Admin/UserControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/Admin/UserControllerTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('lists non-admin users', function () {
    User::factory()->create(['role' => 'user', 'name' => 'Regular User']);

    $response = $this->withoutVite()->actingAs(adminUser())->get('/admin/users');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users', 1)
    );
});

it('creates a new user', function () {
    $this->actingAs(adminUser())->post('/admin/users', [
        'name'                  => 'Juan Pérez',
        'email'                 => 'juan@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    expect(User::where('email', 'juan@example.com')->exists())->toBeTrue();
    expect(User::where('email', 'juan@example.com')->first()->role)->toBe('user');
    expect(User::where('email', 'juan@example.com')->first()->is_active)->toBeTrue();
});

it('requires name, unique email and password to create user', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs(adminUser())
        ->post('/admin/users', [
            'name'     => '',
            'email'    => 'existing@example.com',
            'password' => 'short',
        ])
        ->assertSessionHasErrors(['name', 'email', 'password']);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Admin/UserControllerTest.php
```

Expected: FAIL — UserController not found.

- [ ] **Step 3: Create UserController (index + store)**

```php
<?php
// app/Http/Controllers/Admin/UserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users'  => User::where('role', 'user')->orderBy('name')->get(),
            'rounds' => Round::orderBy('order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'user',
            'is_active' => true,
        ]);

        return back()->with('status', "Usuario '{$data['name']}' creado.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);
        $state = $user->fresh()->is_active ? 'activado' : 'desactivado';

        return back()->with('status', "Usuario {$state}.");
    }

    public function activatePot(User $user): RedirectResponse
    {
        if ($user->is_activated) {
            return back()->with('status', 'El usuario ya está activado en el pozo.');
        }

        $user->update([
            'is_activated'  => true,
            'coins_balance' => $user->coins_balance + 50,
        ]);

        CoinTransaction::create([
            'user_id' => $user->id,
            'type'    => 'credit',
            'amount'  => 50,
            'concept' => 'Activación al pozo del torneo',
        ]);

        return back()->with('status', "Usuario '{$user->name}' activado en el pozo (+50 coins).");
    }

    public function deactivatePot(User $user): RedirectResponse
    {
        if (! $user->is_activated) {
            return back()->with('status', 'El usuario no está activado en el pozo.');
        }

        $user->update([
            'is_activated'  => false,
            'coins_balance' => max(0, $user->coins_balance - 50),
        ]);

        CoinTransaction::create([
            'user_id' => $user->id,
            'type'    => 'debit',
            'amount'  => 50,
            'concept' => 'Baja del pozo del torneo',
        ]);

        return back()->with('status', "Usuario '{$user->name}' dado de baja del pozo (-50 coins).");
    }

    public function reopenPredictions(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'round_id' => ['required', 'exists:rounds,id'],
        ]);

        PredictionSubmission::where('user_id', $user->id)
            ->where('round_id', $data['round_id'])
            ->update(['status' => 'draft', 'submitted_at' => null]);

        return back()->with('status', "Predicciones de '{$user->name}' reabiertas para la ronda seleccionada.");
    }
}
```

- [ ] **Step 4: Create Users/Index page**

```jsx
// resources/js/Pages/Admin/Users/Index.jsx

import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

function CreateUserForm() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name:                  '',
        email:                 '',
        password:              '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.users.store'), { onSuccess: () => reset() });
    };

    return (
        <div className="mb-6 max-w-lg rounded-lg bg-white p-4 shadow">
            <h3 className="mb-3 text-sm font-medium text-gray-700">Crear Usuario</h3>
            <form onSubmit={submit} className="space-y-3">
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                            placeholder="Nombre" className="w-full rounded border-gray-300 text-sm shadow-sm" />
                        {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                    </div>
                    <div>
                        <input type="email" value={data.email} onChange={e => setData('email', e.target.value)}
                            placeholder="Email" className="w-full rounded border-gray-300 text-sm shadow-sm" />
                        {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email}</p>}
                    </div>
                    <div>
                        <input type="password" value={data.password} onChange={e => setData('password', e.target.value)}
                            placeholder="Contraseña" className="w-full rounded border-gray-300 text-sm shadow-sm" />
                        {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
                    </div>
                    <div>
                        <input type="password" value={data.password_confirmation}
                            onChange={e => setData('password_confirmation', e.target.value)}
                            placeholder="Confirmar contraseña" className="w-full rounded border-gray-300 text-sm shadow-sm" />
                    </div>
                </div>
                <button type="submit" disabled={processing}
                    className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                    Crear Usuario
                </button>
            </form>
        </div>
    );
}

function ReopenModal({ user, rounds, onClose }) {
    const { data, setData, post, processing } = useForm({ round_id: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.users.reopen-predictions', user.id), { onSuccess: onClose });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div className="w-96 rounded-lg bg-white p-6 shadow-xl">
                <h3 className="mb-4 text-base font-semibold text-gray-800">Reabrir predicciones — {user.name}</h3>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Ronda</label>
                        <select value={data.round_id} onChange={e => setData('round_id', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                            <option value="">Seleccionar ronda…</option>
                            {rounds.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
                        </select>
                    </div>
                    <div className="flex gap-3">
                        <button type="submit" disabled={processing || !data.round_id}
                            className="rounded bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 disabled:opacity-50">
                            Reabrir
                        </button>
                        <button type="button" onClick={onClose}
                            className="rounded bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function Index({ users, rounds }) {
    const [reopenTarget, setReopenTarget] = useState(null);

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Usuarios</h2>}>
            <Head title="Admin — Usuarios" />

            <CreateUserForm />

            {reopenTarget && (
                <ReopenModal user={reopenTarget} rounds={rounds} onClose={() => setReopenTarget(null)} />
            )}

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['Nombre', 'Email', 'Acceso', 'Pozo', 'Coins', 'Pts', 'Acciones'].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {users.map(user => (
                            <tr key={user.id}>
                                <td className="px-4 py-3 text-sm font-medium text-gray-900">{user.name}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{user.email}</td>
                                <td className="px-4 py-3 text-sm">
                                    <span className={`rounded px-2 py-1 text-xs ${user.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                        {user.is_active ? 'Activo' : 'Inactivo'}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-sm">
                                    <span className={`rounded px-2 py-1 text-xs ${user.is_activated ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'}`}>
                                        {user.is_activated ? 'En pozo' : 'Sin activar'}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-sm text-gray-600">{user.coins_balance}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{user.total_points}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-1">
                                        <button onClick={() => router.post(route('admin.users.toggle-active', user.id))}
                                            className={`rounded px-2 py-1 text-xs text-white ${user.is_active ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'}`}>
                                            {user.is_active ? 'Desactivar' : 'Activar'}
                                        </button>
                                        {!user.is_activated ? (
                                            <button onClick={() => router.post(route('admin.users.activate-pot', user.id))}
                                                className="rounded bg-yellow-500 px-2 py-1 text-xs text-white hover:bg-yellow-600">
                                                + Pozo
                                            </button>
                                        ) : (
                                            <button onClick={() => router.post(route('admin.users.deactivate-pot', user.id))}
                                                className="rounded bg-gray-400 px-2 py-1 text-xs text-white hover:bg-gray-500">
                                                - Pozo
                                            </button>
                                        )}
                                        <button onClick={() => setReopenTarget(user)}
                                            className="rounded bg-orange-500 px-2 py-1 text-xs text-white hover:bg-orange-600">
                                            Reabrir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {users.length === 0 && (
                            <tr>
                                <td colSpan={7} className="px-4 py-8 text-center text-sm text-gray-500">
                                    No hay usuarios registrados.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/UserControllerTest.php
```

Expected: 3 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/UserController.php \
        resources/js/Pages/Admin/Users/Index.jsx \
        tests/Feature/Admin/UserControllerTest.php
git commit -m "feat: add admin user management (index + create)"
```

---

## Task 8: User Admin — Toggle Active + Pot Management + Reopen Predictions

### Files
- Modify: `tests/Feature/Admin/UserControllerTest.php` (add new tests)

Note: `toggleActive()`, `activatePot()`, `deactivatePot()`, `reopenPredictions()` were already written in Task 7. Only the tests remain.

- [ ] **Step 1: Add failing tests**

Append to `tests/Feature/Admin/UserControllerTest.php`:

```php
it('toggles user is_active status', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $this->actingAs(adminUser())->post("/admin/users/{$user->id}/toggle-active");

    expect($user->fresh()->is_active)->toBeFalse();

    $this->actingAs(adminUser())->post("/admin/users/{$user->id}/toggle-active");

    expect($user->fresh()->is_active)->toBeTrue();
});

it('activates a user in the pot and records coin transaction', function () {
    $user = User::factory()->create(['role' => 'user', 'is_activated' => false, 'coins_balance' => 0]);

    $this->actingAs(adminUser())->post("/admin/users/{$user->id}/activate-pot");

    expect($user->fresh()->is_activated)->toBeTrue();
    expect($user->fresh()->coins_balance)->toBe(50);
    expect($user->coinTransactions()->where('type', 'credit')->where('amount', 50)->exists())->toBeTrue();
});

it('does not double-activate a user in the pot', function () {
    $user = User::factory()->create(['role' => 'user', 'is_activated' => true, 'coins_balance' => 50]);

    $this->actingAs(adminUser())->post("/admin/users/{$user->id}/activate-pot");

    expect($user->fresh()->coins_balance)->toBe(50);
    expect($user->coinTransactions()->count())->toBe(0);
});

it('deactivates a user from the pot and records coin transaction', function () {
    $user = User::factory()->create(['role' => 'user', 'is_activated' => true, 'coins_balance' => 50]);

    $this->actingAs(adminUser())->post("/admin/users/{$user->id}/deactivate-pot");

    expect($user->fresh()->is_activated)->toBeFalse();
    expect($user->fresh()->coins_balance)->toBe(0);
    expect($user->coinTransactions()->where('type', 'debit')->where('amount', 50)->exists())->toBeTrue();
});

it('reopens predictions for a user and round', function () {
    $user  = User::factory()->create(['role' => 'user']);
    $round = Round::factory()->r1()->create();

    PredictionSubmission::create([
        'user_id'      => $user->id,
        'round_id'     => $round->id,
        'status'       => 'locked',
        'submitted_at' => now(),
    ]);

    $this->actingAs(adminUser())->post("/admin/users/{$user->id}/reopen-predictions", [
        'round_id' => $round->id,
    ]);

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->status)->toBe('draft');
    expect($submission->submitted_at)->toBeNull();
});

it('requires round_id to reopen predictions', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs(adminUser())
        ->post("/admin/users/{$user->id}/reopen-predictions", [])
        ->assertSessionHasErrors(['round_id']);
});
```

Also add to the use statements at the top of the test file:
```php
use App\Models\PredictionSubmission;
use App\Models\Round;
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Admin/UserControllerTest.php --filter "toggle\|activate\|deactivate\|reopen"
```

Expected: FAIL — missing `Round` import in test file (fix it, then re-run).

- [ ] **Step 3: Run all user tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/UserControllerTest.php
```

Expected: 9 PASS.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Admin/UserControllerTest.php
git commit -m "feat: test admin user toggle-active, pot management, and reopen predictions"
```

---

## Task 9: Full Test Suite + Final Verification

- [ ] **Step 1: Run the full test suite**

```bash
./vendor/bin/sail test
```

Expected: all tests PASS (or DEPR — deprecation warnings are OK). Zero FAIL.

- [ ] **Step 2: If any test fails, read the error and fix the root cause**

Common issues:
- Missing `use` import in a test file → add the import
- `GroupFactory` or `FixtureFactory` missing → create per the instructions in Tasks 3 and 4
- `Player` model missing `HasFactory` → add `use HasFactory;` and verify `$fillable`
- Unique `match_number` constraint fails → verify `FixtureFactory` uses `fake()->unique()`

- [ ] **Step 3: Verify admin layout renders in browser**

With Sail running and Vite running:
```bash
./vendor/bin/sail up -d
./vendor/bin/sail pnpm run dev
```

Log in as `admin@pollamundial.test` / `password` and visit `http://localhost/admin`. Verify the dark nav bar and stats grid render correctly.

- [ ] **Step 4: Final commit if any fixes were needed**

```bash
git add -p   # stage only what was fixed
git commit -m "fix: resolve test suite issues after plan 2 implementation"
```

---

## Self-Review — Spec Coverage

| Spec requirement (§8) | Covered by |
|---|---|
| Cargar 48 equipos con grupo | Teams CRUD (Task 3) — edit existing seeded teams |
| Cargar fixture completo (104 partidos) | Fixtures create (Task 4) |
| Cargar plantillas de jugadores | Players CRUD (Task 6) |
| Abrir ronda (is_open = true) | Round open action (Task 2) |
| Cerrar ronda (is_locked = true) | Round lock action (Task 2) |
| Asignar equipos reales a partidos TBD | Fixture edit/update — team assignment (Task 5) |
| Finalizar ronda | Round finalize action (Task 2) — event dispatch added in Plan 4 |
| Crear usuario | User store (Task 7) |
| Activar acceso (is_active = true) | toggleActive (Task 8) |
| Desactivar acceso (is_active = false) | toggleActive (Task 8) |
| Activar al pozo → +50 coins | activatePot + CoinTransaction (Task 8) |
| Desactivar del pozo → −50 coins | deactivatePot + CoinTransaction (Task 8) |
| Reabrir predicciones | reopenPredictions (Task 8) |
| Admin dashboard/navigation | AdminLayout + DashboardController (Task 1) |

**Out of scope for Plan 2** (handled in later plans):
- Live scoring events (`MatchScoreUpdated`) — Plan 4
- `RoundFinalized` event dispatch — Plan 4
- `TournamentFinalized` event — Plan 4
- Restablecer contraseña (Laravel built-in password reset via email) — Plan 6
- Score update triggering points calculation — Plan 4
