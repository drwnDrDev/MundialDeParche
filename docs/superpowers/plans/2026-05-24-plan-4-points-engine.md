# Plan 4: Points Engine

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement an event-driven points calculation engine that awards pts_exact, pts_result, and pts_classifier to predictions after admin updates match scores, finalizes rounds, and closes the tournament.

**Architecture:** Three event classes (MatchScoreUpdated, RoundFinalized, TournamentFinalized) are dispatched from existing admin controllers. Three listeners handle point calculation. Classifier points are stored on `prediction_submissions` (round-level, not per-match). A `User::recalculateTotalPoints()` method keeps `users.total_points` in sync after every calculation. An Artisan command allows correcting points after admin edits results.

**Tech Stack:** Laravel 11 · Pest v3 · Event/Listener pattern (synchronous, no ShouldQueue) · AppServiceProvider for event registration

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `database/migrations/..._add_pts_classifier_to_prediction_submissions.php` | Create | Add pts_classifier column to prediction_submissions |
| `app/Models/PredictionSubmission.php` | Modify | Add pts_classifier to fillable |
| `app/Models/User.php` | Modify | Add recalculateTotalPoints() static method |
| `app/Events/MatchScoreUpdated.php` | Create | Event carrying a Fixture |
| `app/Events/RoundFinalized.php` | Create | Event carrying a Round |
| `app/Events/TournamentFinalized.php` | Create | Event carrying champion/runner_up/top_scorer IDs |
| `app/Listeners/CalculateMatchPoints.php` | Create | pts_exact + pts_result per prediction |
| `app/Listeners/CalculateClassifierPoints.php` | Create | pts_classifier for R1 (group sim) and R2 (R16 winners) |
| `app/Listeners/CalculateSpecialPredictions.php` | Create | pts_champion + pts_runner_up + pts_top_scorer |
| `app/Providers/AppServiceProvider.php` | Modify | Register event→listener bindings |
| `app/Http/Controllers/Admin/FixtureController.php` | Modify | Dispatch MatchScoreUpdated on update |
| `app/Http/Controllers/Admin/RoundController.php` | Modify | Dispatch RoundFinalized on finalize |
| `app/Http/Controllers/Admin/TournamentController.php` | Create | Admin finalize-tournament form action |
| `resources/js/Pages/Admin/Tournament.jsx` | Create | Simple form: champion / runner-up / top scorer |
| `routes/web.php` | Modify | Add admin tournament routes |
| `app/Console/Commands/PointsRecalculate.php` | Create | php artisan points:recalculate --round= --match= |
| `tests/Feature/CalculateMatchPointsTest.php` | Create | Listener unit tests |
| `tests/Feature/CalculateClassifierPointsTest.php` | Create | R1 group simulation + R2 R16 tests |
| `tests/Feature/CalculateSpecialPredictionsTest.php` | Create | Special predictions listener tests |
| `tests/Feature/Admin/TournamentControllerTest.php` | Create | Admin tournament endpoint tests |
| `tests/Feature/PointsRecalculateCommandTest.php` | Create | Artisan command tests |

---

## Key design decisions

- **pts_classifier lives on prediction_submissions** — Classifier points are round-level (not per-match). Storing them on the `prediction_submissions` table is the right semantic fit. `predictions.pts_classifier` stays 0 and is never written.
- **predictions.total_points = pts_exact + pts_result** — No classifier pts mixed in.
- **users.total_points = SUM(predictions.total_points) + SUM(prediction_submissions.pts_classifier) + SpecialPrediction pts** — Recalculated from scratch on every event.
- **R2 R16 identification** — R2 has 24 fixtures (16 R32 + 8 R16). R16 matches are the last 8 by `match_number`. This is the fixed World Cup 2026 structure.
- **Synchronous listeners** — No ShouldQueue for MVP. All calculation happens inline.

---

## Codebase context (read before starting)

- `Fixture` model: `$table = 'matches'`, FK in predictions is `match_id`, `isGroupStage()` returns `$this->group_id !== null`
- `Fixture::$fillable`: includes home_score, away_score, winner_team_id, status
- `FixtureController::update()`: validates and saves score fields, no event dispatch yet
- `RoundController::finalize()`: sets is_open=false, is_locked=true, has TODO comment for Plan 4
- `PredictionSubmission` factory states: `submitted()`, `locked()`
- `FixtureFactory` states: `finished(int $home, int $away)`, `live()`
- `Round::$points_exact`, `$points_result`, `$points_classifier` — per-round point values
- Round slug for R1: `'grupos'` (group stage, ties allowed)
- Round slug for R2: `'r32-r16'`, R3: `'qf-sf'`, R4: `'final'`
- Admin route pattern: `Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')`
- Tests: `uses(RefreshDatabase::class)`, `$this->withoutVite()` for Inertia GETs
- No Co-Authored-By in commits

---

## Task 1: Migration + PredictionSubmission.pts_classifier + User::recalculateTotalPoints

**Files:**
- Create: `database/migrations/..._add_pts_classifier_to_prediction_submissions.php`
- Modify: `app/Models/PredictionSubmission.php`
- Modify: `app/Models/User.php`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/CalculateMatchPointsTest.php
<?php

use App\Models\PredictionSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prediction_submission has pts_classifier column', function () {
    $sub = PredictionSubmission::factory()->submitted()->create(['pts_classifier' => 6]);
    expect($sub->fresh()->pts_classifier)->toBe(6);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/sail test tests/Feature/CalculateMatchPointsTest.php
```
Expected: FAIL — column doesn't exist.

- [ ] **Step 3: Create migration**

```bash
./vendor/bin/sail artisan make:migration add_pts_classifier_to_prediction_submissions
```

Open the generated file and fill it:

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
            $table->unsignedSmallInteger('pts_classifier')->default(0)->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('prediction_submissions', function (Blueprint $table) {
            $table->dropColumn('pts_classifier');
        });
    }
};
```

- [ ] **Step 4: Run migration**

```bash
./vendor/bin/sail artisan migrate
```

- [ ] **Step 5: Update PredictionSubmission model**

Add `'pts_classifier'` to `$fillable` in `app/Models/PredictionSubmission.php`:

```php
protected $fillable = ['user_id', 'round_id', 'status', 'submitted_at', 'pts_classifier'];
```

- [ ] **Step 6: Add User::recalculateTotalPoints to User model**

In `app/Models/User.php`, add these imports and method:

```php
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\SpecialPrediction;
```

```php
public static function recalculateTotalPoints(int $userId): void
{
    $matchPts = Prediction::where('user_id', $userId)->sum('total_points');

    $classifierPts = PredictionSubmission::where('user_id', $userId)->sum('pts_classifier');

    $specialPts = SpecialPrediction::where('user_id', $userId)
        ->selectRaw('COALESCE(pts_champion,0) + COALESCE(pts_runner_up,0) + COALESCE(pts_top_scorer,0) AS t')
        ->value('t') ?? 0;

    static::where('id', $userId)->update([
        'total_points' => $matchPts + $classifierPts + $specialPts,
    ]);
}
```

- [ ] **Step 7: Run test to verify it passes**

```bash
./vendor/bin/sail test tests/Feature/CalculateMatchPointsTest.php
```
Expected: DEPR (1 test passing).

- [ ] **Step 8: Commit**

```bash
git add database/migrations/ app/Models/PredictionSubmission.php app/Models/User.php \
    tests/Feature/CalculateMatchPointsTest.php
git commit -m "feat: add pts_classifier to prediction_submissions, add User::recalculateTotalPoints"
```

---

## Task 2: Events + AppServiceProvider registration

**Files:**
- Create: `app/Events/MatchScoreUpdated.php`
- Create: `app/Events/RoundFinalized.php`
- Create: `app/Events/TournamentFinalized.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Create `app/Events/MatchScoreUpdated.php`**

```php
<?php

namespace App\Events;

use App\Models\Fixture;

class MatchScoreUpdated
{
    public function __construct(public readonly Fixture $fixture) {}
}
```

- [ ] **Step 2: Create `app/Events/RoundFinalized.php`**

```php
<?php

namespace App\Events;

use App\Models\Round;

class RoundFinalized
{
    public function __construct(public readonly Round $round) {}
}
```

- [ ] **Step 3: Create `app/Events/TournamentFinalized.php`**

```php
<?php

namespace App\Events;

class TournamentFinalized
{
    public function __construct(
        public readonly int $championTeamId,
        public readonly int $runnerUpTeamId,
        public readonly int $topScorerPlayerId,
    ) {}
}
```

- [ ] **Step 4: Update AppServiceProvider to register listeners**

Replace the boot() method in `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Events\MatchScoreUpdated;
use App\Events\RoundFinalized;
use App\Events\TournamentFinalized;
use App\Listeners\CalculateClassifierPoints;
use App\Listeners\CalculateMatchPoints;
use App\Listeners\CalculateSpecialPredictions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(MatchScoreUpdated::class, CalculateMatchPoints::class);
        Event::listen(RoundFinalized::class, CalculateClassifierPoints::class);
        Event::listen(TournamentFinalized::class, CalculateSpecialPredictions::class);
    }
}
```

- [ ] **Step 5: Verify no class-not-found errors (listeners don't exist yet, but event classes do)**

```bash
./vendor/bin/sail artisan route:list 2>&1 | tail -5
```
Expected: routes listed (no fatal PHP errors from imports that don't exist yet — listener classes are referenced as strings and only resolved when events fire).

- [ ] **Step 6: Commit**

```bash
git add app/Events/ app/Providers/AppServiceProvider.php
git commit -m "feat: add MatchScoreUpdated, RoundFinalized, TournamentFinalized events"
```

---

## Task 3: CalculateMatchPoints listener

**Files:**
- Create: `app/Listeners/CalculateMatchPoints.php`
- Modify: `tests/Feature/CalculateMatchPointsTest.php`

**Logic:**
- Skip if home_score or away_score is null.
- Get all predictions for the fixture where the user's PredictionSubmission for that round has status `submitted` or `locked`.
- Group stage (isGroupStage()): pts_exact = round.points_exact if exact match; pts_result = round.points_result if sign(home-away) matches.
- Knockout: pts_exact same; pts_result = round.points_result if the team the user predicted to win matches winner_team_id.
- Update prediction.pts_exact, pts_result, total_points (= pts_exact + pts_result), calculated_at.
- Call User::recalculateTotalPoints for each affected user.

- [ ] **Step 1: Add tests to `tests/Feature/CalculateMatchPointsTest.php`**

Add after the existing test:

```php
use App\Events\MatchScoreUpdated;
use App\Listeners\CalculateMatchPoints;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;

// Helper: create a group-stage fixture with score
function groupFixtureWithScore(Round $round, int $homeScore, int $awayScore): array
{
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => $homeScore,
        'away_score'   => $awayScore,
        'status'       => 'finished',
        'match_number' => 1,
    ]);
    return ['fixture' => $fixture, 'home' => $home, 'away' => $away];
}

it('awards pts_exact and pts_result for exact group stage score', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 1,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe($round->points_exact);
    expect($prediction->pts_result)->toBe($round->points_result);
    expect($prediction->total_points)->toBe($round->points_exact + $round->points_result);
    expect($prediction->calculated_at)->not->toBeNull();
});

it('awards only pts_result for correct group stage result (not exact)', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 3, 'predicted_away' => 0, // home wins, different score
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
    expect($prediction->pts_result)->toBe($round->points_result);
});

it('awards pts_result for a group stage draw', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 1, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 2, // draw, different score
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
    expect($prediction->pts_result)->toBe($round->points_result);
});

it('awards zero points for wrong group stage prediction', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 0);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 0, 'predicted_away' => 2, // wrong winner
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
    expect($prediction->pts_result)->toBe(0);
    expect($prediction->total_points)->toBe(0);
});

it('awards pts_result for correct knockout winner (via winner_team_id)', function () {
    $round = Round::factory()->r2()->create();
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'       => $round->id,
        'group_id'       => null,
        'home_team_id'   => $home->id,
        'away_team_id'   => $away->id,
        'home_score'     => 1,
        'away_score'     => 1,
        'winner_team_id' => $home->id, // won by ET/penalties
        'status'         => 'finished',
        'match_number'   => 1,
    ]);
    $user = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 0, // user picked home to win
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0); // predicted 1-0, actual 90-min was 1-1
    expect($prediction->pts_result)->toBe($round->points_result); // home won ✓
});

it('awards pts_exact for exact 90-min score in knockout, even if winner differs via ET', function () {
    $round = Round::factory()->r2()->create();
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'       => $round->id,
        'group_id'       => null,
        'home_team_id'   => $home->id,
        'away_team_id'   => $away->id,
        'home_score'     => 1,
        'away_score'     => 1,
        'winner_team_id' => $home->id, // won by ET/penalties
        'went_to_extra_time' => true,
        'status'         => 'finished',
        'match_number'   => 1,
    ]);
    $user = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 1, // predicted 1-1 (exact 90-min score)
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe($round->points_exact); // exact 90-min ✓
    expect($prediction->pts_result)->toBe(0); // no winner predicted from 1-1 in knockout
});

it('does not calculate points for draft predictions', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->create(['user_id' => $user->id, 'round_id' => $round->id, 'status' => 'draft']);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 1,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
    expect($prediction->total_points)->toBe(0);
});

it('skips calculation when score is null', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    $group = Group::factory()->create();
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_score' => null, 'away_score' => null,
        'match_number' => 1,
    ]);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 0,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
});

it('updates user total_points after match calculation', function () {
    $round = Round::factory()->r1()->create(['points_exact' => 3, 'points_result' => 1]);
    $user  = User::factory()->create(['total_points' => 0]);
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 1, // exact
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    expect($user->fresh()->total_points)->toBe(4); // 3 exact + 1 result
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/CalculateMatchPointsTest.php
```
Expected: FAIL — listener doesn't exist.

- [ ] **Step 3: Create `app/Listeners/CalculateMatchPoints.php`**

```php
<?php

namespace App\Listeners;

use App\Events\MatchScoreUpdated;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\User;

class CalculateMatchPoints
{
    public function handle(MatchScoreUpdated $event): void
    {
        $fixture = $event->fixture;

        if ($fixture->home_score === null || $fixture->away_score === null) {
            return;
        }

        $round = $fixture->round;

        $submittedUserIds = PredictionSubmission::where('round_id', $fixture->round_id)
            ->whereIn('status', ['submitted', 'locked'])
            ->pluck('user_id');

        $predictions = Prediction::where('match_id', $fixture->id)
            ->whereIn('user_id', $submittedUserIds)
            ->get();

        $affectedUserIds = [];

        foreach ($predictions as $prediction) {
            $ptsExact  = 0;
            $ptsResult = 0;

            // Exact score (always 90-min)
            if ($prediction->predicted_home === $fixture->home_score
                && $prediction->predicted_away === $fixture->away_score) {
                $ptsExact = $round->points_exact;
            }

            if ($fixture->isGroupStage()) {
                // Group stage: result = 1 / X / 2 by sign comparison
                $realSign = $fixture->home_score <=> $fixture->away_score;
                $predSign = $prediction->predicted_home <=> $prediction->predicted_away;
                if ($realSign === $predSign) {
                    $ptsResult = $round->points_result;
                }
            } else {
                // Knockout: result = acertar el ganador real (winner_team_id)
                if ($fixture->winner_team_id !== null) {
                    $predictedWinnerId = $prediction->predicted_home > $prediction->predicted_away
                        ? $fixture->home_team_id
                        : $fixture->away_team_id;
                    if ($predictedWinnerId === $fixture->winner_team_id) {
                        $ptsResult = $round->points_result;
                    }
                }
            }

            $prediction->update([
                'pts_exact'     => $ptsExact,
                'pts_result'    => $ptsResult,
                'total_points'  => $ptsExact + $ptsResult,
                'calculated_at' => now(),
            ]);

            $affectedUserIds[] = $prediction->user_id;
        }

        foreach (array_unique($affectedUserIds) as $userId) {
            User::recalculateTotalPoints($userId);
        }
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/CalculateMatchPointsTest.php
```
Expected: DEPR (9 tests passing).

- [ ] **Step 5: Commit**

```bash
git add app/Listeners/CalculateMatchPoints.php tests/Feature/CalculateMatchPointsTest.php
git commit -m "feat: add CalculateMatchPoints listener"
```

---

## Task 4: Dispatch MatchScoreUpdated from FixtureController

**Files:**
- Modify: `app/Http/Controllers/Admin/FixtureController.php`
- Create: `tests/Feature/Admin/FixtureScoreDispatchTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Admin/FixtureScoreDispatchTest.php
<?php

use App\Events\MatchScoreUpdated;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
});

it('dispatches MatchScoreUpdated when fixture is updated with a score', function () {
    Event::fake();
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create();
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'match_number' => 1,
    ]);

    $this->actingAs($this->admin)->patch("/admin/fixtures/{$fixture->id}", [
        'round_id'           => $round->id,
        'group_id'           => $group->id,
        'match_number'       => 1,
        'match_date'         => '2026-06-15 18:00:00',
        'home_team_id'       => $home->id,
        'away_team_id'       => $away->id,
        'home_placeholder'   => null,
        'away_placeholder'   => null,
        'home_score'         => 2,
        'away_score'         => 1,
        'winner_team_id'     => null,
        'went_to_extra_time' => false,
        'status'             => 'finished',
    ]);

    Event::assertDispatched(MatchScoreUpdated::class, function ($event) use ($fixture) {
        return $event->fixture->id === $fixture->id;
    });
});

it('does not dispatch MatchScoreUpdated when score is still null', function () {
    Event::fake();
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create();
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id, 'match_number' => 1,
    ]);

    $this->actingAs($this->admin)->patch("/admin/fixtures/{$fixture->id}", [
        'round_id'           => $round->id,
        'group_id'           => $group->id,
        'match_number'       => 1,
        'match_date'         => '2026-06-15 18:00:00',
        'home_team_id'       => null,
        'away_team_id'       => null,
        'home_placeholder'   => null,
        'away_placeholder'   => null,
        'home_score'         => null,
        'away_score'         => null,
        'winner_team_id'     => null,
        'went_to_extra_time' => false,
        'status'             => 'scheduled',
    ]);

    Event::assertNotDispatched(MatchScoreUpdated::class);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Admin/FixtureScoreDispatchTest.php
```
Expected: FAIL — event not dispatched.

- [ ] **Step 3: Modify FixtureController::update to dispatch the event**

Add import at top of `app/Http/Controllers/Admin/FixtureController.php`:

```php
use App\Events\MatchScoreUpdated;
```

Replace the `update` method body's last 3 lines:

```php
        $fixture->update($data);

        if ($fixture->home_score !== null && $fixture->away_score !== null) {
            MatchScoreUpdated::dispatch($fixture->fresh());
        }

        return redirect()->route('admin.fixtures.index', ['round_id' => $data['round_id']])
            ->with('status', "Partido #{$fixture->match_number} actualizado.");
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/FixtureScoreDispatchTest.php
```
Expected: DEPR (2 tests passing).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/FixtureController.php \
    tests/Feature/Admin/FixtureScoreDispatchTest.php
git commit -m "feat: dispatch MatchScoreUpdated from FixtureController on score update"
```

---

## Task 5: CalculateClassifierPoints listener — R1 (group stage)

**Files:**
- Create: `app/Listeners/CalculateClassifierPoints.php`
- Create: `tests/Feature/CalculateClassifierPointsTest.php`

**R1 logic:**
1. For each group, build the real group table from actual match scores (pts → GD → GF, FIFA criteria).
2. Identify real top-2 classifiers per group (24 teams) + real 8 best third-place teams.
3. For each user with a submitted/locked R1 submission:
   a. Build the predicted group table from the user's predictions.
   b. Identify predicted top-2 per group + predicted 8 best thirds.
   c. `pts_classifier` = `round.points_classifier` × count(predicted ∩ real classifiers).
4. Save pts_classifier to PredictionSubmission, call User::recalculateTotalPoints.

**8 best thirds:** collect the third-place team from each group (position index 2 in sorted table), rank by pts → GD → GF, take top 8.

- [ ] **Step 1: Write the failing tests**

```php
// tests/Feature/CalculateClassifierPointsTest.php
<?php

use App\Events\RoundFinalized;
use App\Listeners\CalculateClassifierPoints;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper: create a full 4-team group with 6 fixtures
// Returns ['group', 'teams' => [t1,t2,t3,t4], 'fixtures' => [...]]
function makeGroup(Round $round, string $name, int $matchStart): array
{
    $group = Group::factory()->create(['name' => $name]);
    $teams = Team::factory(4)->create(['group_id' => $group->id]);
    [$t1, $t2, $t3, $t4] = $teams;

    $pairs = [
        [$t1, $t2], [$t1, $t3], [$t1, $t4],
        [$t2, $t3], [$t2, $t4], [$t3, $t4],
    ];
    $fixtures = collect($pairs)->map(function ($pair, $i) use ($round, $group, $matchStart) {
        [$home, $away] = $pair;
        return Fixture::factory()->create([
            'round_id'     => $round->id,
            'group_id'     => $group->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'match_number' => $matchStart + $i,
        ]);
    });

    return ['group' => $group, 'teams' => $teams, 'fixtures' => $fixtures];
}

// Helper: set actual scores on fixtures
// $results: array indexed by fixture index → [home, away]
function setActualScores(iterable $fixtures, array $results): void
{
    foreach ($fixtures as $i => $fixture) {
        [$h, $a] = $results[$i];
        $fixture->update(['home_score' => $h, 'away_score' => $a, 'status' => 'finished']);
    }
}

// Helper: create user predictions for a set of fixtures
function createUserPredictions(User $user, iterable $fixtures, array $predictions): void
{
    foreach ($fixtures as $i => $fixture) {
        [$ph, $pa] = $predictions[$i];
        Prediction::factory()->create([
            'user_id'        => $user->id,
            'match_id'       => $fixture->id,
            'predicted_home' => $ph,
            'predicted_away' => $pa,
        ]);
    }
}

it('awards classifier pts when user correctly predicts R1 top-2 classifiers', function () {
    $round = Round::factory()->r1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    // Group A: real results — T1 wins all, T2 beats T3+T4, T3 beats T4
    ['fixtures' => $fixtures, 'teams' => $teams] = makeGroup($round, 'A', 1);
    [$t1, $t2, $t3, $t4] = $teams;

    // Real: T1=9pts, T2=6pts, T3=3pts, T4=0pts → classifiers: T1, T2
    setActualScores($fixtures, [
        [3,0],[3,0],[3,0], // T1 beats T2, T3, T4
        [1,0],[1,0],       // T2 beats T3, T4
        [1,0],             // T3 beats T4
    ]);

    // User predicts same outcomes → correctly predicts T1, T2 to classify
    createUserPredictions($user, $fixtures, [
        [3,0],[3,0],[3,0],
        [1,0],[1,0],
        [1,0],
    ]);

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(4); // 2 correct classifiers × 2 pts
});

it('awards zero pts when user predicts wrong R1 classifiers', function () {
    $round = Round::factory()->r1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    ['fixtures' => $fixtures, 'teams' => $teams] = makeGroup($round, 'A', 1);
    [$t1, $t2, $t3, $t4] = $teams;

    // Real: T1, T2 classify
    setActualScores($fixtures, [
        [3,0],[3,0],[3,0],
        [1,0],[1,0],
        [1,0],
    ]);

    // User predicts T3, T4 win everything → wrong classifiers
    createUserPredictions($user, $fixtures, [
        [0,1],[0,1],[0,1], // T2 beats T1, T3 beats T1, T4 beats T1
        [0,1],[0,1],       // T3 beats T2, T4 beats T2
        [1,0],             // T3 beats T4
    ]);

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(0);
});

it('does not award classifier pts to draft submissions', function () {
    $round = Round::factory()->r1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->create(['user_id' => $user->id, 'round_id' => $round->id, 'status' => 'draft']);

    ['fixtures' => $fixtures] = makeGroup($round, 'A', 1);
    setActualScores($fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);
    createUserPredictions($user, $fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(0);
});

it('updates user total_points after R1 classifier calculation', function () {
    $round = Round::factory()->r1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create(['total_points' => 0]);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    ['fixtures' => $fixtures] = makeGroup($round, 'A', 1);
    setActualScores($fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);
    createUserPredictions($user, $fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    expect($user->fresh()->total_points)->toBe(4);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/CalculateClassifierPointsTest.php
```
Expected: FAIL — listener doesn't exist.

- [ ] **Step 3: Create `app/Listeners/CalculateClassifierPoints.php`**

```php
<?php

namespace App\Listeners;

use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\User;
use Illuminate\Support\Collection;

class CalculateClassifierPoints
{
    public function handle(RoundFinalized $event): void
    {
        $round = $event->round;

        if ($round->slug === 'grupos') {
            $this->calculateR1($round);
        } elseif ($round->slug === 'r32-r16') {
            $this->calculateR2($round);
        }
        // R3 and R4 have no classifier points (points_classifier = 0 in those rounds)
    }

    // ------------------------------------------------------------------ R1

    private function calculateR1(\App\Models\Round $round): void
    {
        $fixtures = Fixture::where('round_id', $round->id)
            ->whereNotNull('group_id')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        // Real classifiers from actual scores
        $realClassifiers = $this->getR1Classifiers($fixtures, fn ($f) => [$f->home_score, $f->away_score]);

        // Get all submitted/locked users for this round
        $submissions = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('status', ['submitted', 'locked'])
            ->get();

        foreach ($submissions as $submission) {
            $userPredictions = Prediction::where('user_id', $submission->user_id)
                ->whereIn('match_id', $fixtures->pluck('id'))
                ->get()
                ->keyBy('match_id');

            $predictedClassifiers = $this->getR1Classifiers(
                $fixtures,
                function ($f) use ($userPredictions) {
                    $pred = $userPredictions->get($f->id);
                    return $pred ? [$pred->predicted_home, $pred->predicted_away] : [null, null];
                }
            );

            $correct = count(array_intersect($predictedClassifiers, $realClassifiers));
            $pts     = $correct * $round->points_classifier;

            $submission->update(['pts_classifier' => $pts]);
            User::recalculateTotalPoints($submission->user_id);
        }
    }

    /**
     * Build a set of classifier team IDs for R1.
     * Returns: array of team IDs (top 2 from each group + 8 best thirds).
     *
     * $getScores: fn(Fixture) => [int|null $home, int|null $away]
     */
    private function getR1Classifiers(Collection $fixtures, callable $getScores): array
    {
        $byGroup = $fixtures->groupBy('group_id');
        $classifiers = [];
        $thirds = [];

        foreach ($byGroup as $groupId => $groupFixtures) {
            $table = $this->buildGroupTable($groupFixtures, $getScores);

            if (count($table) < 2) continue;

            $classifiers[] = $table[0]['team_id']; // 1st
            $classifiers[] = $table[1]['team_id']; // 2nd

            if (isset($table[2])) {
                $thirds[] = $table[2]; // 3rd place
            }
        }

        // Sort thirds by pts → gd → gf, take top 8
        usort($thirds, fn ($a, $b) =>
            $b['pts'] <=> $a['pts']
            ?: $b['gd'] <=> $a['gd']
            ?: $b['gf'] <=> $a['gf']
        );

        foreach (array_slice($thirds, 0, 8) as $third) {
            $classifiers[] = $third['team_id'];
        }

        return $classifiers;
    }

    /**
     * Simulate a group table.
     * Returns array of ['team_id', 'pts', 'gd', 'gf'] sorted by pts → gd → gf.
     */
    private function buildGroupTable(Collection $fixtures, callable $getScores): array
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

    // ------------------------------------------------------------------ R2

    private function calculateR2(\App\Models\Round $round): void
    {
        // R2 has 24 matches: first 16 = R32, last 8 = R16.
        // Real R2 classifiers = winner_team_id of the 8 R16 fixtures.
        $r2Fixtures = Fixture::where('round_id', $round->id)
            ->orderBy('match_number')
            ->get();

        $r16Fixtures = $r2Fixtures->slice(16)->values(); // last 8 = R16

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

                $predictedWinnerId = $pred->predicted_home > $pred->predicted_away
                    ? $fixture->home_team_id
                    : $fixture->away_team_id;
                $predictedClassifiers[] = $predictedWinnerId;
            }

            $correct = count(array_intersect($predictedClassifiers, $realClassifiers));
            $pts     = $correct * $round->points_classifier;

            $submission->update(['pts_classifier' => $pts]);
            User::recalculateTotalPoints($submission->user_id);
        }
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/CalculateClassifierPointsTest.php
```
Expected: DEPR (4 tests passing).

- [ ] **Step 5: Commit**

```bash
git add app/Listeners/CalculateClassifierPoints.php \
    tests/Feature/CalculateClassifierPointsTest.php
git commit -m "feat: add CalculateClassifierPoints listener (R1 group stage simulation)"
```

---

## Task 6: R2 classifier tests + dispatch RoundFinalized from RoundController

**Files:**
- Modify: `tests/Feature/CalculateClassifierPointsTest.php`
- Modify: `app/Http/Controllers/Admin/RoundController.php`
- Create: `tests/Feature/Admin/RoundFinalizeDispatchTest.php`

- [ ] **Step 1: Add R2 classifier tests to `tests/Feature/CalculateClassifierPointsTest.php`**

Append:

```php
it('awards R2 classifier pts for correctly predicted R16 QF teams', function () {
    $round = Round::factory()->r2()->create(['points_classifier' => 4]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    // Create 24 R2 fixtures: first 16 = R32 (match_number 1–16), last 8 = R16 (17–24)
    // For R32 (1–16): create minimal fixtures, no scores needed for classifier calculation
    $group = Group::factory()->create();
    $r32Teams = Team::factory(32)->create(['group_id' => $group->id]);

    for ($i = 0; $i < 16; $i++) {
        Fixture::factory()->create([
            'round_id'     => $round->id,
            'group_id'     => null,
            'home_team_id' => $r32Teams[$i * 2]->id,
            'away_team_id' => $r32Teams[$i * 2 + 1]->id,
            'match_number' => $i + 1,
        ]);
    }

    // R16 fixtures (17–24): 8 matches, real winner_team_id set
    $r16Teams = Team::factory(16)->create(['group_id' => $group->id]);
    $r16Fixtures = collect();
    for ($i = 0; $i < 8; $i++) {
        $home = $r16Teams[$i * 2];
        $away = $r16Teams[$i * 2 + 1];
        $r16Fixtures->push(Fixture::factory()->create([
            'round_id'       => $round->id,
            'group_id'       => null,
            'home_team_id'   => $home->id,
            'away_team_id'   => $away->id,
            'winner_team_id' => $home->id, // home wins R16
            'match_number'   => $i + 17,
        ]));
    }

    // User correctly predicts all 8 R16 home teams to win
    foreach ($r16Fixtures as $fixture) {
        Prediction::factory()->create([
            'user_id'        => $user->id,
            'match_id'       => $fixture->id,
            'predicted_home' => 2,
            'predicted_away' => 0, // home wins
        ]);
    }

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(32); // 8 correct × 4 pts
});

it('awards partial R2 classifier pts when only some QF teams predicted correctly', function () {
    $round = Round::factory()->r2()->create(['points_classifier' => 4]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    $group = Group::factory()->create();
    $r32Teams = Team::factory(32)->create(['group_id' => $group->id]);
    for ($i = 0; $i < 16; $i++) {
        Fixture::factory()->create([
            'round_id' => $round->id, 'group_id' => null,
            'home_team_id' => $r32Teams[$i * 2]->id,
            'away_team_id' => $r32Teams[$i * 2 + 1]->id,
            'match_number' => $i + 1,
        ]);
    }

    $r16Teams = Team::factory(16)->create(['group_id' => $group->id]);
    $r16Fixtures = collect();
    for ($i = 0; $i < 8; $i++) {
        $home = $r16Teams[$i * 2];
        $away = $r16Teams[$i * 2 + 1];
        $r16Fixtures->push(Fixture::factory()->create([
            'round_id'       => $round->id, 'group_id' => null,
            'home_team_id'   => $home->id,
            'away_team_id'   => $away->id,
            'winner_team_id' => $home->id, // home always wins
            'match_number'   => $i + 17,
        ]));
    }

    // User correctly predicts only first 3 home teams, predicts away for the rest
    foreach ($r16Fixtures as $i => $fixture) {
        Prediction::factory()->create([
            'user_id'  => $user->id,
            'match_id' => $fixture->id,
            'predicted_home' => $i < 3 ? 2 : 0, // first 3 correct, rest wrong
            'predicted_away' => $i < 3 ? 0 : 2,
        ]);
    }

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(12); // 3 correct × 4 pts
});
```

- [ ] **Step 2: Run R2 tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/CalculateClassifierPointsTest.php
```
Expected: DEPR (6 tests passing).

- [ ] **Step 3: Write dispatch test**

```php
// tests/Feature/Admin/RoundFinalizeDispatchTest.php
<?php

use App\Events\RoundFinalized;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
});

it('dispatches RoundFinalized when admin finalizes a round', function () {
    Event::fake();
    $round = Round::factory()->r1()->create(['is_open' => true, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->id}/finalize");

    Event::assertDispatched(RoundFinalized::class, function ($event) use ($round) {
        return $event->round->id === $round->id;
    });
});
```

- [ ] **Step 4: Run dispatch test to verify it fails**

```bash
./vendor/bin/sail test tests/Feature/Admin/RoundFinalizeDispatchTest.php
```
Expected: FAIL — event not dispatched.

- [ ] **Step 5: Modify RoundController::finalize to dispatch the event**

Add import to `app/Http/Controllers/Admin/RoundController.php`:

```php
use App\Events\RoundFinalized;
```

Replace `finalize` method:

```php
    public function finalize(Round $round): RedirectResponse
    {
        $round->update(['is_open' => false, 'is_locked' => true]);

        RoundFinalized::dispatch($round);

        return back()->with('status', "Ronda '{$round->name}' finalizada.");
    }
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Admin/RoundFinalizeDispatchTest.php
```
Expected: DEPR (1 test passing).

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/CalculateClassifierPointsTest.php \
    app/Http/Controllers/Admin/RoundController.php \
    tests/Feature/Admin/RoundFinalizeDispatchTest.php
git commit -m "feat: add R2 classifier tests, dispatch RoundFinalized from RoundController"
```

---

## Task 7: Admin Tournament Finalization + CalculateSpecialPredictions

**Files:**
- Create: `app/Http/Controllers/Admin/TournamentController.php`
- Create: `resources/js/Pages/Admin/Tournament.jsx`
- Modify: `routes/web.php`
- Create: `app/Listeners/CalculateSpecialPredictions.php`
- Create: `tests/Feature/CalculateSpecialPredictionsTest.php`
- Create: `tests/Feature/Admin/TournamentControllerTest.php`

- [ ] **Step 1: Write failing tests**

```php
// tests/Feature/CalculateSpecialPredictionsTest.php
<?php

use App\Events\TournamentFinalized;
use App\Listeners\CalculateSpecialPredictions;
use App\Models\Group;
use App\Models\Player;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('awards pts_champion when user correctly predicted the champion', function () {
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();
    $user   = User::factory()->create();

    SpecialPrediction::factory()->create([
        'user_id'              => $user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ]);

    (new CalculateSpecialPredictions)->handle(
        new TournamentFinalized($champ->id, $runner->id, $scorer->id)
    );

    $sp = SpecialPrediction::where('user_id', $user->id)->first();
    expect($sp->pts_champion)->toBe(30);
    expect($sp->pts_runner_up)->toBe(10);
    expect($sp->pts_top_scorer)->toBe(15);
    expect($sp->calculated_at)->not->toBeNull();
    expect($sp->is_locked)->toBeTrue();
});

it('awards zero pts for wrong special predictions', function () {
    $group   = Group::factory()->create();
    $champ   = Team::factory()->create(['group_id' => $group->id]);
    $runner  = Team::factory()->create(['group_id' => $group->id]);
    $wrong1  = Team::factory()->create(['group_id' => $group->id]);
    $wrong2  = Team::factory()->create(['group_id' => $group->id]);
    $scorer  = Player::factory()->create();
    $wrong3  = Player::factory()->create();
    $user    = User::factory()->create();

    SpecialPrediction::factory()->create([
        'user_id'              => $user->id,
        'champion_team_id'     => $wrong1->id,
        'runner_up_team_id'    => $wrong2->id,
        'top_scorer_player_id' => $wrong3->id,
    ]);

    (new CalculateSpecialPredictions)->handle(
        new TournamentFinalized($champ->id, $runner->id, $scorer->id)
    );

    $sp = SpecialPrediction::where('user_id', $user->id)->first();
    expect($sp->pts_champion)->toBe(0);
    expect($sp->pts_runner_up)->toBe(0);
    expect($sp->pts_top_scorer)->toBe(0);
});

it('updates user total_points after special prediction calculation', function () {
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();
    $user   = User::factory()->create(['total_points' => 100]);

    SpecialPrediction::factory()->create([
        'user_id'              => $user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ]);

    (new CalculateSpecialPredictions)->handle(
        new TournamentFinalized($champ->id, $runner->id, $scorer->id)
    );

    // user had 100 match pts, now also gets 55 special pts
    expect($user->fresh()->total_points)->toBe(155); // 100 + 30 + 10 + 15
});

it('locks all special predictions after calculation', function () {
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();

    $users = User::factory(3)->create();
    foreach ($users as $u) {
        SpecialPrediction::factory()->create([
            'user_id'              => $u->id,
            'champion_team_id'     => $champ->id,
            'runner_up_team_id'    => $runner->id,
            'top_scorer_player_id' => $scorer->id,
        ]);
    }

    (new CalculateSpecialPredictions)->handle(
        new TournamentFinalized($champ->id, $runner->id, $scorer->id)
    );

    expect(SpecialPrediction::where('is_locked', true)->count())->toBe(3);
});
```

```php
// tests/Feature/Admin/TournamentControllerTest.php
<?php

use App\Events\TournamentFinalized;
use App\Models\Group;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
});

it('shows the tournament finalization form', function () {
    $group = Group::factory()->create();
    Team::factory()->create(['group_id' => $group->id]);
    Player::factory()->create();

    $response = $this->withoutVite()->actingAs($this->admin)->get('/admin/tournament');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Tournament')
        ->has('teams')
        ->has('players')
    );
});

it('dispatches TournamentFinalized on admin form submission', function () {
    Event::fake();
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();

    $this->actingAs($this->admin)->post('/admin/tournament/finalize', [
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertRedirect();

    Event::assertDispatched(TournamentFinalized::class, function ($event) use ($champ, $runner, $scorer) {
        return $event->championTeamId === $champ->id
            && $event->runnerUpTeamId === $runner->id
            && $event->topScorerPlayerId === $scorer->id;
    });
});

it('validates champion and runner-up must be different teams', function () {
    Event::fake();
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();

    $this->actingAs($this->admin)->post('/admin/tournament/finalize', [
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $champ->id, // same!
        'top_scorer_player_id' => $scorer->id,
    ])->assertSessionHasErrors('runner_up_team_id');

    Event::assertNotDispatched(TournamentFinalized::class);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/CalculateSpecialPredictionsTest.php tests/Feature/Admin/TournamentControllerTest.php
```
Expected: FAIL.

- [ ] **Step 3: Create `app/Listeners/CalculateSpecialPredictions.php`**

```php
<?php

namespace App\Listeners;

use App\Events\TournamentFinalized;
use App\Models\SpecialPrediction;
use App\Models\User;

class CalculateSpecialPredictions
{
    public function handle(TournamentFinalized $event): void
    {
        $specials = SpecialPrediction::all();

        foreach ($specials as $special) {
            $ptsChampion  = $special->champion_team_id === $event->championTeamId ? 30 : 0;
            $ptsRunnerUp  = $special->runner_up_team_id === $event->runnerUpTeamId ? 10 : 0;
            $ptsTopScorer = $special->top_scorer_player_id === $event->topScorerPlayerId ? 15 : 0;

            $special->update([
                'pts_champion'    => $ptsChampion,
                'pts_runner_up'   => $ptsRunnerUp,
                'pts_top_scorer'  => $ptsTopScorer,
                'is_locked'       => true,
                'calculated_at'   => now(),
            ]);

            User::recalculateTotalPoints($special->user_id);
        }
    }
}
```

- [ ] **Step 4: Create `app/Http/Controllers/Admin/TournamentController.php`**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Events\TournamentFinalized;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Admin/Tournament', [
            'teams'   => Team::with('group')->orderBy('name')->get(),
            'players' => Player::with('team')->orderBy('name')->get(),
        ]);
    }

    public function finalize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'champion_team_id'     => ['required', 'exists:teams,id'],
            'runner_up_team_id'    => ['required', 'exists:teams,id', 'different:champion_team_id'],
            'top_scorer_player_id' => ['required', 'exists:players,id'],
        ]);

        TournamentFinalized::dispatch(
            $data['champion_team_id'],
            $data['runner_up_team_id'],
            $data['top_scorer_player_id']
        );

        return redirect()->route('admin.dashboard')
            ->with('status', 'Torneo finalizado. Puntos especiales calculados.');
    }
}
```

- [ ] **Step 5: Add routes to `routes/web.php`**

Add import near the top with other admin controller imports:

```php
use App\Http\Controllers\Admin\TournamentController;
```

Add inside the admin route group, after the Users section:

```php
    // Tournament
    Route::get('tournament', [TournamentController::class, 'show'])->name('tournament');
    Route::post('tournament/finalize', [TournamentController::class, 'finalize'])->name('tournament.finalize');
```

- [ ] **Step 6: Create `resources/js/Pages/Admin/Tournament.jsx`**

```jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Tournament({ teams, players }) {
    const { data, setData, post, processing, errors } = useForm({
        champion_team_id:     '',
        runner_up_team_id:    '',
        top_scorer_player_id: '',
    });

    function handleSubmit(e) {
        e.preventDefault();
        if (!confirm('¿Finalizar torneo? Esta acción calculará los puntos especiales de todos los usuarios.')) return;
        post(route('admin.tournament.finalize'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Finalizar Torneo</h2>}>
            <Head title="Finalizar Torneo" />
            <div className="py-12">
                <div className="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white shadow rounded-lg p-6 space-y-5">
                        <p className="text-sm text-gray-600">
                            Ingresá el campeón, sub-campeón y goleador real del torneo. Los puntos especiales de todos
                            los usuarios se calcularán y las predicciones quedarán bloqueadas.
                        </p>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Campeón</label>
                                <select
                                    value={data.champion_team_id}
                                    onChange={e => setData('champion_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
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
                                <label className="block text-sm font-medium text-gray-700">Sub-campeón</label>
                                <select
                                    value={data.runner_up_team_id}
                                    onChange={e => setData('runner_up_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
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
                                <label className="block text-sm font-medium text-gray-700">Goleador</label>
                                <select
                                    value={data.top_scorer_player_id}
                                    onChange={e => setData('top_scorer_player_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
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

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-2 px-4 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 disabled:opacity-50"
                            >
                                Finalizar torneo y calcular puntos
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/CalculateSpecialPredictionsTest.php tests/Feature/Admin/TournamentControllerTest.php
```
Expected: DEPR (7 tests passing).

- [ ] **Step 8: Commit**

```bash
git add app/Listeners/CalculateSpecialPredictions.php \
    app/Http/Controllers/Admin/TournamentController.php \
    resources/js/Pages/Admin/Tournament.jsx \
    routes/web.php \
    tests/Feature/CalculateSpecialPredictionsTest.php \
    tests/Feature/Admin/TournamentControllerTest.php
git commit -m "feat: add tournament finalization, CalculateSpecialPredictions listener"
```

---

## Task 8: Artisan command points:recalculate

**Files:**
- Create: `app/Console/Commands/PointsRecalculate.php`
- Create: `tests/Feature/PointsRecalculateCommandTest.php`

**Behavior:**
- `--match={id}`: dispatch MatchScoreUpdated for that fixture (only if scores are set).
- `--round={id}`: dispatch MatchScoreUpdated for every scored fixture in the round, then dispatch RoundFinalized if the round is locked.
- Both options can be combined.

- [ ] **Step 1: Write the failing tests**

```php
// tests/Feature/PointsRecalculateCommandTest.php
<?php

use App\Events\MatchScoreUpdated;
use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches MatchScoreUpdated for a specific match with --match option', function () {
    Event::fake();
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create();
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
        'home_score' => 2, 'away_score' => 1, 'status' => 'finished',
        'match_number' => 1,
    ]);

    $this->artisan('points:recalculate', ['--match' => $fixture->id])
        ->assertSuccessful();

    Event::assertDispatched(MatchScoreUpdated::class, fn ($e) => $e->fixture->id === $fixture->id);
});

it('does not dispatch MatchScoreUpdated for match without score', function () {
    Event::fake();
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create();
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_score' => null, 'away_score' => null, 'match_number' => 1,
    ]);

    $this->artisan('points:recalculate', ['--match' => $fixture->id])
        ->assertSuccessful();

    Event::assertNotDispatched(MatchScoreUpdated::class);
});

it('dispatches MatchScoreUpdated for every scored fixture in round with --round option', function () {
    Event::fake();
    $round = Round::factory()->r1()->create(['is_locked' => true]);
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);

    $scored   = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
        'home_score' => 1, 'away_score' => 0, 'status' => 'finished',
        'match_number' => 1,
    ]);
    $unscored = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_score' => null, 'away_score' => null, 'match_number' => 2,
    ]);

    $this->artisan('points:recalculate', ['--round' => $round->id])
        ->assertSuccessful();

    Event::assertDispatched(MatchScoreUpdated::class, fn ($e) => $e->fixture->id === $scored->id);
    Event::assertDispatched(RoundFinalized::class, fn ($e) => $e->round->id === $round->id);
});

it('does not dispatch RoundFinalized when round is not locked', function () {
    Event::fake();
    $round = Round::factory()->r1()->create(['is_locked' => false]);
    $group = Group::factory()->create();
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_score' => 1, 'away_score' => 0, 'match_number' => 1,
    ]);

    $this->artisan('points:recalculate', ['--round' => $round->id])
        ->assertSuccessful();

    Event::assertNotDispatched(RoundFinalized::class);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/PointsRecalculateCommandTest.php
```
Expected: FAIL — command doesn't exist.

- [ ] **Step 3: Create `app/Console/Commands/PointsRecalculate.php`**

```php
<?php

namespace App\Console\Commands;

use App\Events\MatchScoreUpdated;
use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Round;
use Illuminate\Console\Command;

class PointsRecalculate extends Command
{
    protected $signature = 'points:recalculate {--match=} {--round=}';
    protected $description = 'Recalculate points for a specific match or all matches in a round';

    public function handle(): int
    {
        $matchId = $this->option('match');
        $roundId = $this->option('round');

        if ($matchId) {
            $fixture = Fixture::findOrFail($matchId);
            if ($fixture->home_score !== null && $fixture->away_score !== null) {
                MatchScoreUpdated::dispatch($fixture);
                $this->info("Recalculated points for match #{$fixture->match_number}.");
            } else {
                $this->warn("Match #{$fixture->match_number} has no score. Skipping.");
            }
        }

        if ($roundId) {
            $round    = Round::findOrFail($roundId);
            $fixtures = Fixture::where('round_id', $round->id)
                ->whereNotNull('home_score')
                ->whereNotNull('away_score')
                ->get();

            foreach ($fixtures as $fixture) {
                MatchScoreUpdated::dispatch($fixture);
            }

            $this->info("Recalculated points for {$fixtures->count()} matches in round '{$round->name}'.");

            if ($round->is_locked) {
                RoundFinalized::dispatch($round);
                $this->info("Dispatched RoundFinalized for round '{$round->name}'.");
            }
        }

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/PointsRecalculateCommandTest.php
```
Expected: DEPR (4 tests passing).

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/PointsRecalculate.php \
    tests/Feature/PointsRecalculateCommandTest.php
git commit -m "feat: add points:recalculate artisan command"
```

---

## Task 9: Full test suite + final verification

**Files:** None (verification only)

- [ ] **Step 1: Run the full test suite**

```bash
./vendor/bin/sail test
```
Expected: all tests passing (zero FAIL).

- [ ] **Step 2: Fix any failures**

If factory uniqueness issues arise on `match_number`, add explicit `match_number` values in failing tests.

If `User::recalculateTotalPoints` raises "Call to undefined method" in some tests, verify User.php has the method and the correct imports (Prediction, PredictionSubmission, SpecialPrediction models).

- [ ] **Step 3: Confirm final count**

Expected: 140+ tests, zero failures.

- [ ] **Step 4: Commit if any fixes needed**

```bash
git add -p
git commit -m "fix: address full suite failures in Plan 4 tests"
```

---

## Self-Review

**Spec coverage check:**

| Spec requirement | Covered by |
|---|---|
| MatchScoreUpdated event | Task 2 + 4 |
| pts_exact (exact 90-min score) | Task 3 |
| pts_result group stage (1/X/2) | Task 3 |
| pts_result knockout (winner_team_id) | Task 3 |
| Live scoring (in_progress → MatchScoreUpdated) | Task 4 (FixtureController dispatches on every score update) |
| Skip draft predictions | Task 3 |
| Skip null scores | Task 3 |
| User.total_points updated after match calc | Task 3 |
| RoundFinalized event + dispatch from admin | Task 2 + 6 |
| R1 group table simulation (pts→GD→GF) | Task 5 |
| R1 top-2 classifiers per group | Task 5 |
| R1 8 best third-place teams | Task 5 (thirds sorted by pts→GD→GF, take 8) |
| R1 pts_classifier stored on submission | Task 5 |
| R2 classifiers: 8 predicted QF teams vs real | Task 6 (R16 = last 8 R2 fixtures by match_number) |
| TournamentFinalized event | Task 2 + 7 |
| pts_champion (30), pts_runner_up (10), pts_top_scorer (15) | Task 7 |
| SpecialPrediction.is_locked after calculation | Task 7 |
| Admin tournament finalization form | Task 7 |
| points:recalculate --match= | Task 8 |
| points:recalculate --round= | Task 8 |
| pts_classifier stored per submission (not per prediction) | Task 1 migration |

**Placeholder scan:** None.

**Type consistency:**
- `MatchScoreUpdated::$fixture` → `Fixture` → used in CalculateMatchPoints as `$event->fixture` ✓
- `RoundFinalized::$round` → `Round` → used in CalculateClassifierPoints as `$event->round` ✓
- `TournamentFinalized::$championTeamId` → `int` → used in CalculateSpecialPredictions as `$event->championTeamId` ✓
- `User::recalculateTotalPoints(int $userId)` → called everywhere with `$prediction->user_id` / `$submission->user_id` / `$special->user_id` — all int ✓
- `PredictionSubmission::pts_classifier` → set from `$round->points_classifier * $correct` — both int ✓
