# Phase Restructuring — FIFA 2026 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restructure the 4 tournament phases to match FIFA 2026 format, add bracket propagation when admin enters results, and enable bracket simulation in the user prediction UI for F3/F4.

**Architecture:** Add `winner_feeds_match_id` / `winner_feeds_slot` columns to `matches` to encode the bracket tree. A new `PropagateBracketWinner` listener auto-fills next-match team slots when a winner is recorded. The frontend receives bracket feed info per fixture and simulates downstream opponents in real time as the user fills in predictions.

**Tech Stack:** Laravel 11 · Pest v3 · React 18 · Inertia.js v2 · MySQL 8.4 · Docker/Sail

---

## New Phase Map

| Phase | Slug | Matches | points_exact | points_result | points_classifier |
|---|---|---|---|---|---|
| F1 Fase de Grupos | `grupos` | M1–M72 (72) | 3 | 1 | 2 |
| F2 Round of 32 | `r32` | M73–M88 (16) | 5 | 2 | 3 |
| F3 Octavos + Cuartos | `f3` | M89–M100 (12) | 8 | 3 | 5 |
| F4 Semis + Final | `f4` | M101–M102, M104 (3) | 13 | 5 | 0 |

M103 (3er puesto) **no se siembra** — no existe en la quiniela.

## Bracket Links

```
R32 → Octavos          Octavos → Cuartos     Cuartos → Semis      Semis → Final
M73 → M89 home         M89 → M97 home         M97 → M101 home      M101 → M104 home
M75 → M89 away         M90 → M97 away         M98 → M101 away      M102 → M104 away
M74 → M90 home         M91 → M98 home         M99 → M102 home
M77 → M90 away         M92 → M98 away         M100 → M102 away
M76 → M91 home         M93 → M99 home
M78 → M91 away         M94 → M99 away
M79 → M92 home         M95 → M100 home
M80 → M92 away         M96 → M100 away
M83 → M93 home
M84 → M93 away
M81 → M94 home
M82 → M94 away
M85 → M95 home
M86 → M95 away
M87 → M96 home
M88 → M96 away
```

---

## File Map

| Action | File |
|---|---|
| Create | `database/migrations/XXXX_add_bracket_columns_to_matches_table.php` |
| Modify | `app/Models/Fixture.php` |
| Modify | `database/seeders/RoundSeeder.php` |
| Modify | `database/factories/RoundFactory.php` |
| Modify | `database/seeders/MatchSeeder.php` |
| Create | `app/Listeners/PropagateBracketWinner.php` |
| Modify | `app/Providers/AppServiceProvider.php` |
| Modify | `app/Listeners/CalculateClassifierPoints.php` |
| Modify | `app/Http/Controllers/PredictionController.php` |
| Modify | `resources/js/Pages/Predictions/Round.jsx` |
| Modify | `resources/js/Components/composed/TournamentProgress.jsx` |
| Create | `tests/Feature/BracketPropagationTest.php` |
| Modify | `tests/Feature/CalculateClassifierPointsTest.php` |
| Modify | `database/factories/RoundFactory.php` (rename states r1→f1, r2→f2, r3→f3, r4→f4) |
| Modify | All test files using `->r2()`, `->r3()`, `->r4()` factory states |

---

## Task 1: Migration — bracket columns on `matches`

**Files:**
- Create: `database/migrations/XXXX_add_bracket_columns_to_matches_table.php`

- [ ] **Step 1: Generate migration**

```bash
./vendor/bin/sail artisan make:migration add_bracket_columns_to_matches_table
```

- [ ] **Step 2: Fill migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedBigInteger('winner_feeds_match_id')->nullable()->after('winner_team_id');
            $table->enum('winner_feeds_slot', ['home', 'away'])->nullable()->after('winner_feeds_match_id');

            $table->foreign('winner_feeds_match_id')
                  ->references('id')->on('matches')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['winner_feeds_match_id']);
            $table->dropColumn(['winner_feeds_match_id', 'winner_feeds_slot']);
        });
    }
};
```

- [ ] **Step 3: Run migration**

```bash
./vendor/bin/sail artisan migrate
```

Expected: `Migrated: ... add_bracket_columns_to_matches_table`

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: add winner_feeds_match_id and winner_feeds_slot to matches"
```

---

## Task 2: Update Fixture model

**Files:**
- Modify: `app/Models/Fixture.php`

- [ ] **Step 1: Add fillable fields and cast**

Add `winner_feeds_match_id` and `winner_feeds_slot` to `$fillable` and casts:

```php
protected $fillable = [
    'round_id',
    'group_id',
    'match_number',
    'match_date',
    'home_team_id',
    'away_team_id',
    'home_placeholder',
    'away_placeholder',
    'home_score',
    'away_score',
    'winner_team_id',
    'winner_feeds_match_id',
    'winner_feeds_slot',
    'went_to_extra_time',
    'status',
    'venue',
];
```

In `casts()`, add:

```php
'winner_feeds_match_id' => 'integer',
```

- [ ] **Step 2: Add relationship**

After the `winnerTeam()` method, add:

```php
public function winnerFeedsMatch(): BelongsTo
{
    return $this->belongsTo(Fixture::class, 'winner_feeds_match_id');
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/Fixture.php
git commit -m "feat: add bracket feed fields to Fixture model"
```

---

## Task 3: Update RoundSeeder — new phases

**Files:**
- Modify: `database/seeders/RoundSeeder.php`

- [ ] **Step 1: Replace seeder content**

```php
<?php

namespace Database\Seeders;

use App\Models\Round;
use Illuminate\Database\Seeder;

class RoundSeeder extends Seeder
{
    public function run(): void
    {
        $rounds = [
            [
                'name'               => 'Fase de Grupos',
                'slug'               => 'grupos',
                'order'              => 1,
                'points_exact'       => 3,
                'points_result'      => 1,
                'points_classifier'  => 2,
            ],
            [
                'name'               => 'Round of 32',
                'slug'               => 'r32',
                'order'              => 2,
                'points_exact'       => 5,
                'points_result'      => 2,
                'points_classifier'  => 3,
            ],
            [
                'name'               => 'Octavos + Cuartos',
                'slug'               => 'f3',
                'order'              => 3,
                'points_exact'       => 8,
                'points_result'      => 3,
                'points_classifier'  => 5,
            ],
            [
                'name'               => 'Semis + Final',
                'slug'               => 'f4',
                'order'              => 4,
                'points_exact'       => 13,
                'points_result'      => 5,
                'points_classifier'  => 0,
            ],
        ];

        foreach ($rounds as $round) {
            Round::firstOrCreate(['slug' => $round['slug']], $round);
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add database/seeders/RoundSeeder.php
git commit -m "feat: restructure rounds to F1/F2/F3/F4 with new slugs and points"
```

---

## Task 4: Update RoundFactory — rename states f1/f2/f3/f4

**Files:**
- Modify: `database/factories/RoundFactory.php`
- Modify: all test files that reference `->r2()`, `->r3()`, `->r4()`

- [ ] **Step 1: Replace factory**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'               => fake()->words(3, true),
            'slug'               => fake()->unique()->slug(),
            'order'              => fake()->numberBetween(1, 4),
            'is_open'            => false,
            'is_locked'          => false,
            'points_exact'       => 0,
            'points_result'      => 0,
            'points_classifier'  => 0,
        ];
    }

    public function f1(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'               => 'Fase de Grupos',
            'slug'               => 'grupos',
            'order'              => 1,
            'points_exact'       => 3,
            'points_result'      => 1,
            'points_classifier'  => 2,
        ]);
    }

    public function f2(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'               => 'Round of 32',
            'slug'               => 'r32',
            'order'              => 2,
            'points_exact'       => 5,
            'points_result'      => 2,
            'points_classifier'  => 3,
        ]);
    }

    public function f3(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'               => 'Octavos + Cuartos',
            'slug'               => 'f3',
            'order'              => 3,
            'points_exact'       => 8,
            'points_result'      => 3,
            'points_classifier'  => 5,
        ]);
    }

    public function f4(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'               => 'Semis + Final',
            'slug'               => 'f4',
            'order'              => 4,
            'points_exact'       => 13,
            'points_result'      => 5,
            'points_classifier'  => 0,
        ]);
    }
}
```

- [ ] **Step 2: Update all test references**

Run this search to find every `->r2()`, `->r3()`, `->r4()` call:

```bash
grep -rn "->r2()\|->r3()\|->r4()" tests/
```

For each file found, replace:
- `->r1()` → `->f1()`
- `->r2()` → `->f2()`
- `->r3()` → `->f3()`
- `->r4()` → `->f4()`

Files confirmed to need this change:
- `tests/Feature/CalculateClassifierPointsTest.php`
- `tests/Feature/Admin/FixtureControllerTest.php`
- `tests/Feature/PredictionControllerTest.php`
- `tests/Feature/CalculateMatchPointsTest.php`

- [ ] **Step 3: Run tests to catch any remaining r1/r2/r3/r4 references**

```bash
./vendor/bin/sail test 2>&1 | grep -E "FAIL|Error" | head -20
```

Fix any remaining failures before continuing.

- [ ] **Step 4: Commit**

```bash
git add database/factories/RoundFactory.php tests/
git commit -m "refactor: rename round factory states r1/r2/r3/r4 → f1/f2/f3/f4"
```

---

## Task 5: Rewrite MatchSeeder — new round assignments + bracket links

**Files:**
- Modify: `database/seeders/MatchSeeder.php`

The key changes from the current seeder:
- `$r2 = $rounds['r32']` (was `'r32-r16'`)
- `$r3 = $rounds['f3']` (was `'qf-sf'`)
- `$r4 = $rounds['f4']` (was `'final'`)
- M73–M88: stay in F2 (r32) — same as before
- M89–M96 (octavos): move from F2 to **F3**
- M97–M100 (cuartos): move from F3 to **F3**
- M101–M102 (semis): move from F3 to **F4**
- M103 (3er puesto): **NOT seeded**
- M104 (final): move from F4, stays in **F4**
- After all fixtures created: set `winner_feeds_match_id` and `winner_feeds_slot`

- [ ] **Step 1: Update round variable assignment at top of `run()`**

```php
$r1 = $rounds['grupos'];
$r2 = $rounds['r32'];
$r3 = $rounds['f3'];
$r4 = $rounds['f4'];
```

- [ ] **Step 2: Replace the R2 block (currently labeled "ROUND OF 32 + ROUND OF 16")**

Replace the entire `$r2Matches` block (currently M73–M96) with TWO separate blocks:

```php
// --- F2: ROUND OF 32 (M73–M88) ---
$f2Matches = [
    [73, '2026-06-28 15:00:00', 'Subcampeón A',          'Subcampeón B',          9],
    [74, '2026-06-29 16:30:00', 'Ganador E',             '3° mejor A/B/C/D/F',    12],
    [75, '2026-06-29 21:00:00', 'Ganador F',             'Subcampeón C',          2],
    [76, '2026-06-29 13:00:00', 'Ganador C',             'Subcampeón F',          8],
    [77, '2026-06-30 17:00:00', 'Ganador I',             '3° mejor C/D/F/G/H',   4],
    [78, '2026-06-30 13:00:00', 'Subcampeón E',          'Subcampeón I',          8],
    [79, '2026-06-30 21:00:00', 'Ganador A',             '3° mejor C/E/F/H/I',   1],
    [80, '2026-07-01 12:00:00', 'Ganador L',             '3° mejor E/H/I/J/K',   7],
    [81, '2026-07-01 16:00:00', 'Ganador D',             '3° mejor B/E/F/I/J',   10],
    [82, '2026-07-01 16:00:00', 'Ganador G',             '3° mejor A/E/H/I/J',   11],
    [83, '2026-07-02 13:00:00', 'Subcampeón K',          'Subcampeón L',          5],
    [84, '2026-07-02 17:00:00', 'Ganador H',             'Subcampeón J',          6],
    [85, '2026-07-02 21:00:00', 'Ganador B',             '3° mejor E/F/G/I/J',   3],
    [86, '2026-07-03 13:00:00', 'Ganador J',             'Subcampeón H',          10],
    [87, '2026-07-03 17:00:00', 'Ganador K',             '3° mejor D/E/I/J/L',   7],
    [88, '2026-07-03 21:00:00', 'Subcampeón D',          'Subcampeón G',          8],
];

foreach ($f2Matches as [$num, $date, $homePlaceholder, $awayPlaceholder, $venueId]) {
    Fixture::firstOrCreate(
        ['match_number' => $num, 'round_id' => $r2->id],
        [
            'group_id'         => null,
            'match_date'       => $date,
            'home_team_id'     => null,
            'away_team_id'     => null,
            'home_placeholder' => $homePlaceholder,
            'away_placeholder' => $awayPlaceholder,
            'venue'            => $v[$venueId],
            'status'           => 'scheduled',
        ]
    );
}

// --- F3: OCTAVOS (M89–M96) + CUARTOS (M97–M100) ---
$f3Matches = [
    // Octavos de final
    [89,  '2026-07-04 12:00:00', 'Ganador M73', 'Ganador M75', 4],
    [90,  '2026-07-04 16:00:00', 'Ganador M74', 'Ganador M77', 6],
    [91,  '2026-07-04 20:00:00', 'Ganador M76', 'Ganador M78', 11],
    [92,  '2026-07-05 12:00:00', 'Ganador M79', 'Ganador M80', 7],
    [93,  '2026-07-05 16:00:00', 'Ganador M83', 'Ganador M84', 5],
    [94,  '2026-07-05 20:00:00', 'Ganador M81', 'Ganador M82', 10],
    [95,  '2026-07-06 12:00:00', 'Ganador M85', 'Ganador M86', 3],
    [96,  '2026-07-06 16:00:00', 'Ganador M87', 'Ganador M88', 8],
    // Cuartos de final
    [97,  '2026-07-08 12:00:00', 'Ganador M89', 'Ganador M90', 4],
    [98,  '2026-07-08 16:00:00', 'Ganador M91', 'Ganador M92', 7],
    [99,  '2026-07-09 12:00:00', 'Ganador M93', 'Ganador M94', 6],
    [100, '2026-07-09 16:00:00', 'Ganador M95', 'Ganador M96', 8],
];

foreach ($f3Matches as [$num, $date, $homePlaceholder, $awayPlaceholder, $venueId]) {
    Fixture::firstOrCreate(
        ['match_number' => $num, 'round_id' => $r3->id],
        [
            'group_id'         => null,
            'match_date'       => $date,
            'home_team_id'     => null,
            'away_team_id'     => null,
            'home_placeholder' => $homePlaceholder,
            'away_placeholder' => $awayPlaceholder,
            'venue'            => $v[$venueId],
            'status'           => 'scheduled',
        ]
    );
}

// --- F4: SEMIS (M101–M102) + FINAL (M104) ---
// M103 (3er puesto) is intentionally excluded from this quiniela.
$f4Matches = [
    [101, '2026-07-12 16:00:00', 'Ganador M97',  'Ganador M98',  8],
    [102, '2026-07-13 16:00:00', 'Ganador M99',  'Ganador M100', 7],
    [104, '2026-07-19 15:00:00', 'Ganador M101', 'Ganador M102', 4],
];

foreach ($f4Matches as [$num, $date, $homePlaceholder, $awayPlaceholder, $venueId]) {
    Fixture::firstOrCreate(
        ['match_number' => $num, 'round_id' => $r4->id],
        [
            'group_id'         => null,
            'match_date'       => $date,
            'home_team_id'     => null,
            'away_team_id'     => null,
            'home_placeholder' => $homePlaceholder,
            'away_placeholder' => $awayPlaceholder,
            'venue'            => $v[$venueId],
            'status'           => 'scheduled',
        ]
    );
}
```

- [ ] **Step 3: Add bracket link pass at the end of `run()`**

After all fixture blocks, add:

```php
// --- BRACKET LINKS ---
// winner_feeds_match_id + winner_feeds_slot encodes the bracket tree.
// Keyed by source match_number → [target match_number, slot]
$allKnockout = Fixture::whereIn('match_number', array_merge(
    range(73, 102), [104]
))->get()->keyBy('match_number');

$bracketLinks = [
    // R32 → Octavos
    73  => [89,  'home'],
    75  => [89,  'away'],
    74  => [90,  'home'],
    77  => [90,  'away'],
    76  => [91,  'home'],
    78  => [91,  'away'],
    79  => [92,  'home'],
    80  => [92,  'away'],
    83  => [93,  'home'],
    84  => [93,  'away'],
    81  => [94,  'home'],
    82  => [94,  'away'],
    85  => [95,  'home'],
    86  => [95,  'away'],
    87  => [96,  'home'],
    88  => [96,  'away'],
    // Octavos → Cuartos
    89  => [97,  'home'],
    90  => [97,  'away'],
    91  => [98,  'home'],
    92  => [98,  'away'],
    93  => [99,  'home'],
    94  => [99,  'away'],
    95  => [100, 'home'],
    96  => [100, 'away'],
    // Cuartos → Semis
    97  => [101, 'home'],
    98  => [101, 'away'],
    99  => [102, 'home'],
    100 => [102, 'away'],
    // Semis → Final
    101 => [104, 'home'],
    102 => [104, 'away'],
];

foreach ($bracketLinks as $fromNum => [$toNum, $slot]) {
    $source = $allKnockout[$fromNum] ?? null;
    $target = $allKnockout[$toNum] ?? null;
    if ($source && $target) {
        $source->update([
            'winner_feeds_match_id' => $target->id,
            'winner_feeds_slot'     => $slot,
        ]);
    }
}
```

- [ ] **Step 4: Remove the old R3 and R4 fixture blocks**

Delete the `$r3Matches` block (cuartos + semis) and `$r4Matches` block (3er puesto + final) — they are replaced by `$f3Matches` and `$f4Matches` above.

- [ ] **Step 5: Fresh seed and verify**

```bash
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan tinker --execute="
use App\Models\Fixture;
echo 'F2 count: ' . Fixture::whereHas('round', fn(\$q) => \$q->where('slug','r32'))->count() . PHP_EOL;
echo 'F3 count: ' . Fixture::whereHas('round', fn(\$q) => \$q->where('slug','f3'))->count() . PHP_EOL;
echo 'F4 count: ' . Fixture::whereHas('round', fn(\$q) => \$q->where('slug','f4'))->count() . PHP_EOL;
echo 'Bracket links set: ' . Fixture::whereNotNull('winner_feeds_match_id')->count() . PHP_EOL;
echo 'M103 exists: ' . Fixture::where('match_number', 103)->count() . PHP_EOL;
"
```

Expected output:
```
F2 count: 16
F3 count: 12
F4 count: 3
Bracket links set: 30
M103 exists: 0
```

- [ ] **Step 6: Commit**

```bash
git add database/seeders/MatchSeeder.php
git commit -m "feat: redistribute knockout fixtures to new phases, add bracket links, remove 3rd-place match"
```

---

## Task 6: PropagateBracketWinner listener

When admin sets a `winner_team_id` on a match, the `MatchScoreUpdated` event fires. This listener reads `winner_feeds_match_id` and `winner_feeds_slot` to auto-fill the next match's team slot.

**Files:**
- Create: `app/Listeners/PropagateBracketWinner.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Write failing test first**

Create `tests/Feature/BracketPropagationTest.php`:

```php
<?php

use App\Events\MatchScoreUpdated;
use App\Models\Fixture;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fills home slot of next match when winner is set', function () {
    $brazil = Team::factory()->create(['name' => 'Brazil', 'fifa_code' => 'BRA']);
    $france = Team::factory()->create(['name' => 'France', 'fifa_code' => 'FRA']);

    $f2 = Round::factory()->f2()->create();
    $f3 = Round::factory()->f3()->create();

    $source = Fixture::factory()->create([
        'round_id'     => $f2->id,
        'match_number' => 73,
        'home_team_id' => $brazil->id,
        'away_team_id' => $france->id,
        'home_score'   => 2,
        'away_score'   => 1,
        'winner_team_id' => $brazil->id,
        'status'       => 'finished',
    ]);

    $target = Fixture::factory()->create([
        'round_id'     => $f3->id,
        'match_number' => 89,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $source->update([
        'winner_feeds_match_id' => $target->id,
        'winner_feeds_slot'     => 'home',
    ]);

    MatchScoreUpdated::dispatch($source->fresh());

    expect($target->fresh()->home_team_id)->toBe($brazil->id);
    expect($target->fresh()->away_team_id)->toBeNull(); // untouched
});

it('fills away slot of next match when winner is set', function () {
    $argentina = Team::factory()->create(['name' => 'Argentina', 'fifa_code' => 'ARG']);
    $spain     = Team::factory()->create(['name' => 'Spain',     'fifa_code' => 'ESP']);

    $f2 = Round::factory()->f2()->create();
    $f3 = Round::factory()->f3()->create();

    $source = Fixture::factory()->create([
        'round_id'     => $f2->id,
        'match_number' => 75,
        'home_team_id' => $argentina->id,
        'away_team_id' => $spain->id,
        'home_score'   => 0,
        'away_score'   => 1,
        'winner_team_id' => $spain->id,
        'status'       => 'finished',
    ]);

    $target = Fixture::factory()->create([
        'round_id'     => $f3->id,
        'match_number' => 89,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $source->update([
        'winner_feeds_match_id' => $target->id,
        'winner_feeds_slot'     => 'away',
    ]);

    MatchScoreUpdated::dispatch($source->fresh());

    expect($target->fresh()->away_team_id)->toBe($spain->id);
    expect($target->fresh()->home_team_id)->toBeNull();
});

it('does nothing when winner_feeds_match_id is null', function () {
    $brazil = Team::factory()->create(['fifa_code' => 'BRA']);
    $france = Team::factory()->create(['fifa_code' => 'FRA']);

    $f2 = Round::factory()->f2()->create();

    $source = Fixture::factory()->create([
        'round_id'              => $f2->id,
        'match_number'          => 73,
        'home_team_id'          => $brazil->id,
        'away_team_id'          => $france->id,
        'home_score'            => 2,
        'away_score'            => 1,
        'winner_team_id'        => $brazil->id,
        'winner_feeds_match_id' => null,
        'winner_feeds_slot'     => null,
        'status'                => 'finished',
    ]);

    MatchScoreUpdated::dispatch($source->fresh());

    // No exception thrown — test passes
    expect(true)->toBeTrue();
});

it('does nothing when winner_team_id is null', function () {
    $brazil = Team::factory()->create(['fifa_code' => 'BRA']);
    $france = Team::factory()->create(['fifa_code' => 'FRA']);
    $f2     = Round::factory()->f2()->create();
    $f3     = Round::factory()->f3()->create();

    $source = Fixture::factory()->create([
        'round_id'       => $f2->id,
        'match_number'   => 73,
        'home_team_id'   => $brazil->id,
        'away_team_id'   => $france->id,
        'home_score'     => 2,
        'away_score'     => 1,
        'winner_team_id' => null, // not set yet
        'status'         => 'in_progress',
    ]);

    $target = Fixture::factory()->create([
        'round_id'     => $f3->id,
        'match_number' => 89,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $source->update([
        'winner_feeds_match_id' => $target->id,
        'winner_feeds_slot'     => 'home',
    ]);

    MatchScoreUpdated::dispatch($source->fresh());

    expect($target->fresh()->home_team_id)->toBeNull();
});
```

- [ ] **Step 2: Run tests — confirm they fail**

```bash
./vendor/bin/sail test tests/Feature/BracketPropagationTest.php
```

Expected: all 4 FAIL (listener doesn't exist yet).

- [ ] **Step 3: Create listener**

```php
<?php

namespace App\Listeners;

use App\Events\MatchScoreUpdated;
use App\Models\Fixture;

class PropagateBracketWinner
{
    public function handle(MatchScoreUpdated $event): void
    {
        $fixture = $event->fixture;

        if (! $fixture->winner_team_id || ! $fixture->winner_feeds_match_id) {
            return;
        }

        $target = Fixture::find($fixture->winner_feeds_match_id);
        if (! $target) {
            return;
        }

        $column = $fixture->winner_feeds_slot === 'home' ? 'home_team_id' : 'away_team_id';

        $target->update([$column => $fixture->winner_team_id]);
    }
}
```

- [ ] **Step 4: Register listener in AppServiceProvider**

In `app/Providers/AppServiceProvider.php`, add the second listener binding:

```php
Event::listen(MatchScoreUpdated::class, CalculateMatchPoints::class);
Event::listen(MatchScoreUpdated::class, PropagateBracketWinner::class);
```

Add the import at the top:

```php
use App\Listeners\PropagateBracketWinner;
```

- [ ] **Step 5: Run tests — confirm they pass**

```bash
./vendor/bin/sail test tests/Feature/BracketPropagationTest.php
```

Expected: 4 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Listeners/PropagateBracketWinner.php app/Providers/AppServiceProvider.php tests/Feature/BracketPropagationTest.php
git commit -m "feat: auto-propagate bracket winner to next match team slot"
```

---

## Task 7: Update CalculateClassifierPoints — new slugs and ranges

**Files:**
- Modify: `app/Listeners/CalculateClassifierPoints.php`
- Modify: `tests/Feature/CalculateClassifierPointsTest.php`

The `handle()` method currently branches on `'r32-r16'`. It needs to branch on `'r32'` and add a new `'f3'` branch.

- **F1 (grupos):** unchanged — `calculateR1()` still works.
- **F2 (r32):** classifiers = 16 winners of M73–M88. Derive from user's predictions for those matches (same logic as current `calculateR2` but new match_number range and new slug).
- **F3 (f3):** classifiers = 4 winners of M97–M100 (cuartos → semifinalists). Derive from user's predictions for M97–M100.

- [ ] **Step 1: Write failing tests**

Open `tests/Feature/CalculateClassifierPointsTest.php` and add/replace the R2 test block with F2 and F3 tests. Keep the existing R1/grupos tests intact.

Find the two existing R2 tests (they use `Round::factory()->r2()` with `points_classifier => 4`) and replace them:

```php
// ─── F2 (R32) CLASSIFIERS ────────────────────────────────────────────────────

it('awards F2 classifier points for correctly predicted R32 winners', function () {
    $f2 = Round::factory()->f2()->create(['points_classifier' => 3]);

    $teamA = Team::factory()->create(['fifa_code' => 'AAA']);
    $teamB = Team::factory()->create(['fifa_code' => 'BBB']);
    $teamC = Team::factory()->create(['fifa_code' => 'CCC']);
    $teamD = Team::factory()->create(['fifa_code' => 'DDD']);

    // M73: teamA beats teamB (user predicts correctly)
    $m73 = Fixture::factory()->create([
        'round_id'       => $f2->id,
        'match_number'   => 73,
        'home_team_id'   => $teamA->id,
        'away_team_id'   => $teamB->id,
        'home_score'     => 2,
        'away_score'     => 0,
        'winner_team_id' => $teamA->id,
        'status'         => 'finished',
    ]);

    // M74: teamC beats teamD (user predicts wrong)
    $m74 = Fixture::factory()->create([
        'round_id'       => $f2->id,
        'match_number'   => 74,
        'home_team_id'   => $teamC->id,
        'away_team_id'   => $teamD->id,
        'home_score'     => 0,
        'away_score'     => 1,
        'winner_team_id' => $teamD->id,
        'status'         => 'finished',
    ]);

    $user = User::factory()->create();

    $submission = PredictionSubmission::factory()->create([
        'user_id'  => $user->id,
        'round_id' => $f2->id,
        'status'   => 'submitted',
    ]);

    // User predicts teamA wins M73 (correct)
    Prediction::factory()->create([
        'user_id'        => $user->id,
        'match_id'       => $m73->id,
        'predicted_home' => 2,
        'predicted_away' => 0,
    ]);

    // User predicts teamC wins M74 (wrong — real winner is teamD)
    Prediction::factory()->create([
        'user_id'        => $user->id,
        'match_id'       => $m74->id,
        'predicted_home' => 1,
        'predicted_away' => 0,
    ]);

    RoundFinalized::dispatch($f2);

    expect($submission->fresh()->pts_classifier)->toBe(3); // 1 correct × 3 pts
});

it('awards no F2 classifier points when all predictions are wrong', function () {
    $f2 = Round::factory()->f2()->create(['points_classifier' => 3]);

    $teamA = Team::factory()->create(['fifa_code' => 'EEE']);
    $teamB = Team::factory()->create(['fifa_code' => 'FFF']);

    $m73 = Fixture::factory()->create([
        'round_id'       => $f2->id,
        'match_number'   => 73,
        'home_team_id'   => $teamA->id,
        'away_team_id'   => $teamB->id,
        'home_score'     => 0,
        'away_score'     => 1,
        'winner_team_id' => $teamB->id,
        'status'         => 'finished',
    ]);

    $user = User::factory()->create();

    $submission = PredictionSubmission::factory()->create([
        'user_id'  => $user->id,
        'round_id' => $f2->id,
        'status'   => 'submitted',
    ]);

    // User predicts teamA wins (wrong)
    Prediction::factory()->create([
        'user_id'        => $user->id,
        'match_id'       => $m73->id,
        'predicted_home' => 1,
        'predicted_away' => 0,
    ]);

    RoundFinalized::dispatch($f2);

    expect($submission->fresh()->pts_classifier)->toBe(0);
});

// ─── F3 (OCTAVOS + CUARTOS) CLASSIFIERS ────────────────────────────────────

it('awards F3 classifier points for correctly predicted cuartos winners (semifinalists)', function () {
    $f3 = Round::factory()->f3()->create(['points_classifier' => 5]);

    $teamA = Team::factory()->create(['fifa_code' => 'GGG']);
    $teamB = Team::factory()->create(['fifa_code' => 'HHH']);
    $teamC = Team::factory()->create(['fifa_code' => 'III']);
    $teamD = Team::factory()->create(['fifa_code' => 'JJJ']);

    // M97 (cuarto 1): teamA beats teamB — user predicts correctly
    $m97 = Fixture::factory()->create([
        'round_id'       => $f3->id,
        'match_number'   => 97,
        'home_team_id'   => $teamA->id,
        'away_team_id'   => $teamB->id,
        'home_score'     => 1,
        'away_score'     => 0,
        'winner_team_id' => $teamA->id,
        'status'         => 'finished',
    ]);

    // M98 (cuarto 2): teamC beats teamD — user predicts wrong
    $m98 = Fixture::factory()->create([
        'round_id'       => $f3->id,
        'match_number'   => 98,
        'home_team_id'   => $teamC->id,
        'away_team_id'   => $teamD->id,
        'home_score'     => 0,
        'away_score'     => 2,
        'winner_team_id' => $teamD->id,
        'status'         => 'finished',
    ]);

    $user = User::factory()->create();

    $submission = PredictionSubmission::factory()->create([
        'user_id'  => $user->id,
        'round_id' => $f3->id,
        'status'   => 'submitted',
    ]);

    // User predicts teamA wins M97 (correct)
    Prediction::factory()->create([
        'user_id'        => $user->id,
        'match_id'       => $m97->id,
        'predicted_home' => 1,
        'predicted_away' => 0,
    ]);

    // User predicts teamC wins M98 (wrong)
    Prediction::factory()->create([
        'user_id'        => $user->id,
        'match_id'       => $m98->id,
        'predicted_home' => 1,
        'predicted_away' => 0,
    ]);

    RoundFinalized::dispatch($f3);

    expect($submission->fresh()->pts_classifier)->toBe(5); // 1 correct × 5 pts
});
```

- [ ] **Step 2: Run failing tests**

```bash
./vendor/bin/sail test --filter "CalculateClassifierPointsTest"
```

Expected: F2 and F3 classifier tests FAIL (wrong slug match / method missing).

- [ ] **Step 3: Rewrite CalculateClassifierPoints**

```php
<?php

namespace App\Listeners;

use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\User;
use App\Services\GroupStageClassifierService;

class CalculateClassifierPoints
{
    public function __construct(private GroupStageClassifierService $classifier) {}

    public function handle(RoundFinalized $event): void
    {
        $round = $event->round;

        match ($round->slug) {
            'grupos' => $this->calculateF1($round),
            'r32'    => $this->calculateF2($round),
            'f3'     => $this->calculateF3($round),
            default  => null,
        };
    }

    // ── F1: Fase de Grupos ────────────────────────────────────────────────────
    // Uses GroupStageClassifierService (complex group standings + best-thirds).
    private function calculateF1(\App\Models\Round $round): void
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

    // ── F2: Round of 32 (M73–M88) ────────────────────────────────────────────
    // Classifiers = the 16 teams that win each R32 match.
    // Derived from user's predicted score for each M73–M88 match.
    private function calculateF2(\App\Models\Round $round): void
    {
        $fixtures = Fixture::where('round_id', $round->id)
            ->whereBetween('match_number', [73, 88])
            ->orderBy('match_number')
            ->get();

        $this->calculateKnockoutClassifiers($round, $fixtures);
    }

    // ── F3: Octavos + Cuartos (M97–M100 cuartos winners = semifinalists) ─────
    // Classifiers = the 4 teams that win each cuartos match (semifinalists).
    // Octavos (M89–M96) do NOT count for classifier pts — only cuartos winners do.
    private function calculateF3(\App\Models\Round $round): void
    {
        $fixtures = Fixture::where('round_id', $round->id)
            ->whereBetween('match_number', [97, 100])
            ->orderBy('match_number')
            ->get();

        $this->calculateKnockoutClassifiers($round, $fixtures);
    }

    // ── Shared: derive classifier points from match winner predictions ────────
    private function calculateKnockoutClassifiers(\App\Models\Round $round, $fixtures): void
    {
        $realClassifiers = $fixtures
            ->pluck('winner_team_id')
            ->filter()
            ->values()
            ->toArray();

        $submissions = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('status', ['submitted', 'locked'])
            ->get();

        $fixtureIds = $fixtures->pluck('id');

        foreach ($submissions as $submission) {
            $userPredictions = Prediction::where('user_id', $submission->user_id)
                ->whereIn('match_id', $fixtureIds)
                ->get()
                ->keyBy('match_id');

            $predictedClassifiers = [];
            foreach ($fixtures as $fixture) {
                $pred = $userPredictions->get($fixture->id);
                if (! $pred || ! $fixture->home_team_id || ! $fixture->away_team_id) {
                    continue;
                }

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

- [ ] **Step 4: Run tests — confirm they pass**

```bash
./vendor/bin/sail test --filter "CalculateClassifierPointsTest"
```

Expected: all pass.

- [ ] **Step 5: Run full suite**

```bash
./vendor/bin/sail test 2>&1 | tail -5
```

Expected: all pass (or only DEPR warnings).

- [ ] **Step 6: Commit**

```bash
git add app/Listeners/CalculateClassifierPoints.php tests/Feature/CalculateClassifierPointsTest.php
git commit -m "feat: update classifier points engine for F2/F3 new slugs and match ranges"
```

---

## Task 8: Update PredictionController@show — bracket info + relax TBD guard

**Files:**
- Modify: `app/Http/Controllers/PredictionController.php`

The current guard blocks any round with TBD fixtures. F3/F4 legitimately have TBD cuartos/semis/final when the phase opens. The new guard allows the phase as long as at least one fixture has real teams (octavos will always have teams when admin opens F3).

Also, the controller must pass `home_fed_by_match_number` and `away_fed_by_match_number` for each fixture so the frontend can simulate bracket opponents.

- [ ] **Step 1: Update `show()` method**

Replace the current `show()` body with:

```php
public function show(Round $round): Response|RedirectResponse
{
    if (! $round->is_open) {
        return Inertia::render('Predictions/Locked', [
            'roundName'  => $round->name,
            'roundOrder' => $round->order,
            'isLocked'   => $round->is_locked,
            'opensAt'    => null,
        ]);
    }

    // Block the phase only if EVERY fixture is TBD (admin hasn't set up teams yet).
    // Phases like F3 legitimately have some TBD fixtures (cuartos depend on octavos results).
    $hasAnyRealFixture = $round->fixtures()
        ->whereNotNull('home_team_id')
        ->whereNotNull('away_team_id')
        ->exists();

    if (! $hasAnyRealFixture) {
        return redirect()->route('predictions.index')
            ->with('status', 'Esta fase aún no tiene partidos asignados. Vuelve más tarde.');
    }

    $fixtures = $round->fixtures()
        ->with(['homeTeam', 'awayTeam', 'group'])
        ->orderBy('match_number')
        ->get();

    // Build reverse bracket map: for each fixture, which match feeds its home/away slot?
    $fixtureIds  = $fixtures->pluck('id');
    $feeders     = Fixture::whereIn('winner_feeds_match_id', $fixtureIds)
        ->select(['id', 'match_number', 'winner_feeds_match_id', 'winner_feeds_slot'])
        ->get();

    // bracketMap[target_fixture_id][slot] = source_match_number
    $bracketMap = [];
    foreach ($feeders as $feeder) {
        $bracketMap[$feeder->winner_feeds_match_id][$feeder->winner_feeds_slot] = $feeder->match_number;
    }

    // Annotate each fixture with its bracket feed info
    $fixturesData = $fixtures->map(function ($f) use ($bracketMap) {
        return array_merge($f->toArray(), [
            'home_fed_by_match_number' => $bracketMap[$f->id]['home'] ?? null,
            'away_fed_by_match_number' => $bracketMap[$f->id]['away'] ?? null,
        ]);
    });

    $predictions = Prediction::where('user_id', Auth::id())
        ->whereIn('match_id', $fixtureIds)
        ->get()
        ->keyBy('match_id');

    $submission = PredictionSubmission::where('user_id', Auth::id())
        ->where('round_id', $round->id)
        ->first();

    return Inertia::render('Predictions/Round', [
        'round'       => $round,
        'fixtures'    => $fixturesData,
        'predictions' => $predictions,
        'submission'  => $submission,
    ]);
}
```

- [ ] **Step 2: Run tests**

```bash
./vendor/bin/sail test --filter "PredictionControllerTest"
```

Expected: all pass. If any test creates a round with all-TBD fixtures and tries to access it expecting 200, update the test to also create at least one fixture with real teams.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/PredictionController.php
git commit -m "feat: relax TBD fixture guard and pass bracket feed info to prediction form"
```

---

## Task 9: Round.jsx — bracket simulation UI

**Files:**
- Modify: `resources/js/Pages/Predictions/Round.jsx`

For bracket phases (F3/F4), fixtures with null teams get simulated opponents derived from the user's own predictions in real time. The logic:

1. Detect if it's a bracket phase: `const isBracketPhase = fixtures.some(f => f.home_fed_by_match_number || f.away_fed_by_match_number)`
2. Build lookup: `fixturesByMatchNumber[matchNumber] = fixture`
3. `simulateBracketWinner(matchNumber, fixturesByMatchNumber, scores)` — recursive function returning the team object the user would face
4. `MatchPredRow` receives `simulatedHomeTeam` / `simulatedAwayTeam` props when real teams are null

- [ ] **Step 1: Add bracket simulation helpers after `simulateAllGroups`**

Add these two functions before the `export default function Round` declaration:

```js
// ── Bracket simulation ────────────────────────────────────────────────────────
// Recursively derives which team a user predicts wins a given match,
// based on their score inputs. Returns a team object or null if not yet predicted.

function simulateBracketWinner(matchNumber, fixturesByMatchNumber, scores) {
    const fixture = fixturesByMatchNumber[matchNumber];
    if (!fixture) return null;

    const homeTeam = fixture.home_team
        ?? (fixture.home_fed_by_match_number
            ? simulateBracketWinner(fixture.home_fed_by_match_number, fixturesByMatchNumber, scores)
            : null);

    const awayTeam = fixture.away_team
        ?? (fixture.away_fed_by_match_number
            ? simulateBracketWinner(fixture.away_fed_by_match_number, fixturesByMatchNumber, scores)
            : null);

    if (!homeTeam || !awayTeam) return null;

    const s = scores[fixture.id];
    if (s?.home == null || s?.away == null) return null;

    return Number(s.home) > Number(s.away) ? homeTeam : awayTeam;
}

function getBracketTeam(fixture, slot, fixturesByMatchNumber, scores) {
    const realTeam = slot === 'home' ? fixture.home_team : fixture.away_team;
    if (realTeam) return { team: realTeam, isSimulated: false };

    const fedBy = slot === 'home'
        ? fixture.home_fed_by_match_number
        : fixture.away_fed_by_match_number;
    if (!fedBy) return { team: null, isSimulated: false };

    return { team: simulateBracketWinner(fedBy, fixturesByMatchNumber, scores), isSimulated: true };
}
```

- [ ] **Step 2: Update `MatchPredRow` to accept and display simulated teams**

Replace the `MatchPredRow` component:

```js
function MatchPredRow({ fixture, homeScore, awayScore, onChangeHome, onChangeAway, disabled, last,
                        simulatedHome, simulatedAway }) {
    const filled = homeScore !== null && homeScore !== undefined
                && awayScore !== null && awayScore !== undefined;

    // Prefer real team → simulated team → placeholder string
    const resolvedHome = fixture.home_team ?? simulatedHome;
    const resolvedAway = fixture.away_team ?? simulatedAway;

    const home     = resolvedHome ? (resolvedHome.fifa_code ?? resolvedHome.name) : (fixture.home_placeholder ?? 'TBD');
    const away     = resolvedAway ? (resolvedAway.fifa_code ?? resolvedAway.name) : (fixture.away_placeholder ?? 'TBD');
    const flagHome = resolvedHome?.flag_url ?? null;
    const flagAway = resolvedAway?.flag_url ?? null;
    const homeSimulated = !fixture.home_team && !!simulatedHome;
    const awaySimulated = !fixture.away_team && !!simulatedAway;

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
                    <span className={['font-display text-8', homeSimulated ? 'opacity-50 italic' : ''].join(' ')}>
                        {home}
                    </span>
                    {flagHome && <img src={flagHome} alt={home} className="h-8 w-12 object-cover border border-ink" />}
                </div>
                <div className="flex items-center gap-0.5">
                    <ScoreBoxInput value={homeScore} onChange={onChangeHome} disabled={disabled} />
                    <span className="font-display text-[13px] opacity-55 mx-0.5">—</span>
                    <ScoreBoxInput value={awayScore} onChange={onChangeAway} disabled={disabled} />
                </div>
                <div className="flex items-center gap-1.5">
                    {flagAway && <img src={flagAway} alt={away} className="h-8 w-12 object-cover border border-ink" />}
                    <span className={['font-display text-8', awaySimulated ? 'opacity-50 italic' : ''].join(' ')}>
                        {away}
                    </span>
                </div>
            </div>
            {(homeSimulated || awaySimulated) && (
                <div className="text-center font-mono text-[7.5px] opacity-40 mt-0.5 tracking-[.06em]">
                    rival simulado de tus predicciones
                </div>
            )}
            <div className="flex justify-center mt-2">
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
```

- [ ] **Step 3: Update the knockout rendering section in the main `Round` component**

In the `export default function Round` component, add bracket-phase detection and the `fixturesByMatchNumber` lookup. Find the section where knockout matches are rendered (currently they fall under `'Sin Grupo'` in the grouped display) and update it.

Add after the `scores` state declaration:

```js
// Build lookup map for bracket simulation
const fixturesByMatchNumber = useMemo
    ? useMemo(() => Object.fromEntries(fixtures.map(f => [f.match_number, f])), [fixtures])
    : Object.fromEntries(fixtures.map(f => [f.match_number, f]));

const isBracketPhase = fixtures.some(
    f => f.home_fed_by_match_number !== null || f.away_fed_by_match_number !== null
);
```

Note: import `useMemo` from React at the top of the file if not already imported.

- [ ] **Step 4: Pass simulation props to MatchPredRow in knockout rendering**

In the section where `MatchPredRow` is rendered for knockout matches (the `'Sin Grupo'` section), update the render to include simulation:

```js
// In the knockout fixture map:
{knockoutFixtures.map((f, i) => {
    const simHome = getBracketTeam(f, 'home', fixturesByMatchNumber, scores);
    const simAway = getBracketTeam(f, 'away', fixturesByMatchNumber, scores);
    return (
        <MatchPredRow
            key={f.id}
            fixture={f}
            homeScore={scores[f.id]?.home ?? null}
            awayScore={scores[f.id]?.away ?? null}
            onChangeHome={v => setScores(prev => ({ ...prev, [f.id]: { ...prev[f.id], home: v } }))}
            onChangeAway={v => setScores(prev => ({ ...prev, [f.id]: { ...prev[f.id], away: v } }))}
            disabled={isSubmitted}
            last={i === knockoutFixtures.length - 1}
            simulatedHome={simHome.isSimulated ? simHome.team : null}
            simulatedAway={simAway.isSimulated ? simAway.team : null}
        />
    );
})}
```

Where `knockoutFixtures = fixtures.filter(f => !f.group)` — separate the non-group fixtures for the bracket rendering path.

- [ ] **Step 5: Verify in browser (manual)**

```bash
./vendor/bin/sail pnpm run dev
```

Navigate to a F3 phase (after `migrate:fresh --seed` with F3 open and octavos teams set). Confirm:
- Octavos (M89–M96) show real teams
- Cuartos (M97–M100) show "TBD" initially
- As you fill octavos scores, cuartos opponents update in real time
- Simulated opponents show in italic/muted style

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Predictions/Round.jsx
git commit -m "feat: bracket simulation in Round.jsx — cuartos/semis/final opponents derive from user predictions"
```

---

## Task 10: Update TournamentProgress labels

**Files:**
- Modify: `resources/js/Components/composed/TournamentProgress.jsx`

- [ ] **Step 1: Update SLUG_LABELS map**

```js
const SLUG_LABELS = {
    'grupos': 'GRUPOS',
    'r32':    'R32',
    'f3':     '8VOS+4TOS',
    'f4':     'SEMIS+FINAL',
};
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/composed/TournamentProgress.jsx
git commit -m "fix: update TournamentProgress phase labels for new slugs"
```

---

## Task 11: Fresh seed + full test suite validation

- [ ] **Step 1: Full reset and seed**

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

Expected: no errors, all seeders complete.

- [ ] **Step 2: Verify fixture distribution via tinker**

```bash
./vendor/bin/sail artisan tinker --execute="
use App\Models\Fixture;
use App\Models\Round;

Round::orderBy('order')->each(function(\$r) {
    \$count = Fixture::where('round_id', \$r->id)->count();
    \$links = Fixture::where('round_id', \$r->id)->whereNotNull('winner_feeds_match_id')->count();
    echo \"{$r->slug}: {\$count} fixtures, {\$links} bracket links\" . PHP_EOL;
});

echo 'M103 exists: ' . Fixture::where('match_number', 103)->count() . PHP_EOL;
echo 'Total bracket links: ' . Fixture::whereNotNull('winner_feeds_match_id')->count() . PHP_EOL;
"
```

Expected:
```
grupos: 72 fixtures, 0 bracket links
r32: 16 fixtures, 16 bracket links
f3: 12 fixtures, 12 bracket links
f4: 3 fixtures, 2 bracket links
M103 exists: 0
Total bracket links: 30
```

- [ ] **Step 3: Run full test suite**

```bash
./vendor/bin/sail test 2>&1 | tail -5
```

Expected: all pass (DEPR warnings are normal — they're environmental).

- [ ] **Step 4: Final commit if any loose files remain**

```bash
git status
# commit any remaining uncommitted changes
```

---

## Self-Review

**Spec coverage:**
- ✅ F1 unchanged (72 matches, groups slug, same points, same classifier logic)
- ✅ F2 R32: 16 matches M73–M88, slug 'r32', points_classifier=3
- ✅ F3 Octavos+Cuartos: 12 matches M89–M100, slug 'f3', points_classifier=5
- ✅ F4 Semis+Final: 3 matches M101–M102+M104, slug 'f4', points_classifier=0
- ✅ M103 (3rd place) excluded
- ✅ Bracket propagation: PropagateBracketWinner listener auto-fills team slots
- ✅ User bracket simulation: Round.jsx derives opponents from user's own predictions
- ✅ Admin flow: single open/close per phase (no sub-round open events needed)
- ✅ All factory states renamed, all tests updated

**Placeholder scan:** None found — all code blocks are complete.

**Type consistency:** `winner_feeds_match_id` (integer), `winner_feeds_slot` (string enum 'home'/'away') — consistent across migration, model fillable, listener, and seeder.
