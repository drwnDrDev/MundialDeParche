# Plan 5: Real-time & Chat

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add real-time broadcasting (Reverb) and chat grupal to PollaMundial — users receive live score updates, point changes, and round notifications; a global chat channel lets everyone participate.

**Architecture:** Six dedicated broadcast events (ShouldBroadcast, no ShouldQueue) are dispatched from existing controllers and listeners after their primary work is done. Channel authorization guards `presence-quinela` (active users only) and `private-user.{id}`. `CalculateMatchPoints` dispatches `LiveScoreUpdated`, `PointsUpdated` (per user), and `ExactScoreAlert`. `RoundController` dispatches `RoundOpened`/`RoundLocked`. `ChatController` stores messages and dispatches `MessageSent`. The frontend uses `window.Echo` (already wired in `bootstrap.js`) to subscribe in React components.

**Tech Stack:** Laravel 11 · Laravel Reverb · Pest v3 · React 18 + Inertia.js v2 · `laravel-echo` + `pusher-js` (already installed)

---

## Codebase context (read before starting)

- `bootstrap/app.php` already declares `channels: __DIR__.'/../routes/channels.php'` — the `/broadcasting/auth` route is registered automatically.
- `resources/js/bootstrap.js` already imports `./echo` → `window.Echo` is available globally.
- `BROADCAST_CONNECTION=reverb` in `.env` — Reverb is configured.
- Existing plain events: `app/Events/MatchScoreUpdated.php`, `RoundFinalized.php`, `TournamentFinalized.php` — these stay as pure PHP events (no ShouldBroadcast). New broadcast events are separate classes.
- `app/Listeners/CalculateMatchPoints.php` — modifying this in Task 3. Current structure: `foreach ($predictions) { ... update prediction ... }; foreach ($affectedUserIds) { User::recalculateTotalPoints($userId); }`.
- `app/Http/Controllers/Admin/RoundController.php` — has `open()`, `lock()`, `finalize()` methods. Adding broadcasts in Task 4.
- `app/Models/Message.php` — `fillable: ['user_id', 'content']`, `user()` BelongsTo. Factory exists.
- `app/Models/User.php` — `is_active` (boolean cast), `total_points` (integer cast), `name`, `avatar`.
- Admin route pattern: `Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')`
- User route pattern: `Route::middleware(['auth'])->...`
- Tests: `uses(RefreshDatabase::class)`, Inertia GETs need `$this->withoutVite()`.
- **No Co-Authored-By in commits.**

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `routes/channels.php` | Modify | Authorize `presence-quinela` + `private-user.{id}` channels |
| `app/Events/LiveScoreUpdated.php` | Create | ShouldBroadcast → presence-quinela: match score + is_live flag |
| `app/Events/PointsUpdated.php` | Create | ShouldBroadcast → presence-quinela + private-user.{id}: total_points + rank |
| `app/Events/RoundOpened.php` | Create | ShouldBroadcast → presence-quinela: round name |
| `app/Events/RoundLocked.php` | Create | ShouldBroadcast → presence-quinela: round name |
| `app/Events/MessageSent.php` | Create | ShouldBroadcast → presence-quinela: user + content + timestamp |
| `app/Events/ExactScoreAlert.php` | Create | ShouldBroadcast → presence-quinela: username + score |
| `app/Listeners/CalculateMatchPoints.php` | Modify | After calculation dispatch LiveScoreUpdated + PointsUpdated + ExactScoreAlert |
| `app/Http/Controllers/Admin/RoundController.php` | Modify | Dispatch RoundOpened on `open()`, RoundLocked on `lock()` |
| `app/Http/Controllers/ChatController.php` | Create | GET /chat (Inertia + history), POST /chat/messages (store + broadcast) |
| `app/Http/Controllers/RankingController.php` | Create | GET /ranking (Inertia with ranked user list) |
| `routes/web.php` | Modify | Add /chat, /chat/messages, /ranking routes |
| `resources/js/Pages/Chat.jsx` | Create | Chat page: history + real-time message reception + send form |
| `resources/js/Pages/Ranking.jsx` | Create | Ranking table: initial list + real-time PointsUpdated updates |
| `tests/Feature/Channels/ChannelAuthorizationTest.php` | Create | Auth endpoint tests for presence-quinela and private-user |
| `tests/Feature/Broadcast/BroadcastEventShapesTest.php` | Create | Unit tests: broadcastOn() + broadcastWith() for all 6 events |
| `tests/Feature/Broadcast/MatchScoreBroadcastTest.php` | Create | CalculateMatchPoints dispatches correct broadcast events |
| `tests/Feature/Broadcast/RoundBroadcastTest.php` | Create | RoundController dispatches RoundOpened / RoundLocked |
| `tests/Feature/ChatControllerTest.php` | Create | GET /chat + POST /chat/messages tests |
| `tests/Feature/RankingControllerTest.php` | Create | GET /ranking tests |

---

## Key design decisions

- **Separate broadcast events from calculation events.** `MatchScoreUpdated` stays a plain event (calculation trigger). `LiveScoreUpdated` is a separate class that ShouldBroadcast. This avoids mixing responsibilities.
- **Dispatch order in `CalculateMatchPoints`:** (1) collect exact-score hitters during the main loop, (2) update all predictions, (3) for each affected user: recalculate total_points then dispatch `PointsUpdated`, (4) dispatch `LiveScoreUpdated` once, (5) dispatch `ExactScoreAlert` for each exact hitter.
- **`PointsUpdated` rank formula:** `User::where('total_points', '>', $user->total_points)->count() + 1`. Simple and correct (ties share the same rank and no gap is needed for MVP).
- **Presence channel name:** `quinela` in `routes/channels.php` (no prefix). Laravel maps this to `presence-quinela` automatically when using `new PresenceChannel('quinela')`.
- **No ShouldQueue on any broadcast event** — synchronous, consistent with Plan 4 listeners.
- **Chat POST returns `back()`** — Inertia form POST pattern; no JSON API needed.

---

## Task 1: Channel authorization

**Files:**
- Modify: `routes/channels.php`
- Create: `tests/Feature/Channels/ChannelAuthorizationTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Channels/ChannelAuthorizationTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('active user can join presence-quinela', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'presence-quinela',
            'socket_id'    => '123.456',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['auth', 'channel_data']);
});

it('inactive user cannot join presence-quinela', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'presence-quinela',
            'socket_id'    => '123.456',
        ])
        ->assertForbidden();
});

it('user can authorize their own private channel', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.' . $user->id,
            'socket_id'    => '123.456',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['auth']);
});

it('user cannot authorize another user private channel', function () {
    $user  = User::factory()->create(['is_active' => true]);
    $other = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.' . $other->id,
            'socket_id'    => '123.456',
        ])
        ->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Channels/ChannelAuthorizationTest.php
```
Expected: FAIL — channels not defined yet.

- [ ] **Step 3: Update `routes/channels.php`**

Replace the entire file:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Presence channel for the global quinela room
// Laravel maps this to 'presence-quinela' on the client
Broadcast::channel('quinela', function (User $user) {
    if (! $user->is_active) {
        return false;
    }

    return [
        'id'     => $user->id,
        'name'   => $user->name,
        'avatar' => $user->avatar,
    ];
});

// Private channel per user (points, lock notifications)
Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Channels/ChannelAuthorizationTest.php
```
Expected: 4 tests passing.

- [ ] **Step 5: Commit**

```bash
git add routes/channels.php tests/Feature/Channels/ChannelAuthorizationTest.php
git commit -m "feat: authorize presence-quinela and private-user channels"
```

---

## Task 2: Broadcast event classes

**Files:**
- Create: `app/Events/LiveScoreUpdated.php`
- Create: `app/Events/PointsUpdated.php`
- Create: `app/Events/RoundOpened.php`
- Create: `app/Events/RoundLocked.php`
- Create: `app/Events/MessageSent.php`
- Create: `app/Events/ExactScoreAlert.php`
- Create: `tests/Feature/Broadcast/BroadcastEventShapesTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Broadcast/BroadcastEventShapesTest.php`:

```php
<?php

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\MessageSent;
use App\Events\PointsUpdated;
use App\Events\RoundLocked;
use App\Events\RoundOpened;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

it('LiveScoreUpdated broadcasts to presence-quinela with correct payload', function () {
    $event = new LiveScoreUpdated(matchId: 7, homeScore: 2, awayScore: 1, isLive: true);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PresenceChannel::class);
    expect($channels[0]->name)->toBe('quinela');

    expect($event->broadcastWith())->toBe([
        'match_id'   => 7,
        'home_score' => 2,
        'away_score' => 1,
        'is_live'    => true,
    ]);
});

it('PointsUpdated broadcasts to presence-quinela and private-user', function () {
    $event = new PointsUpdated(userId: 3, totalPoints: 42, position: 5);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(2);

    $channelClasses = array_map(fn($c) => get_class($c), $channels);
    expect($channelClasses)->toContain(PresenceChannel::class);
    expect($channelClasses)->toContain(PrivateChannel::class);

    $privateChannel = collect($channels)->first(fn($c) => $c instanceof PrivateChannel);
    expect($privateChannel->name)->toBe('user.3');

    expect($event->broadcastWith())->toBe([
        'user_id'      => 3,
        'total_points' => 42,
        'position'     => 5,
    ]);
});

it('RoundOpened broadcasts to presence-quinela with round name', function () {
    $event = new RoundOpened(roundName: 'Fase de Grupos');

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);

    $channels = $event->broadcastOn();
    expect($channels[0])->toBeInstanceOf(PresenceChannel::class);
    expect($event->broadcastWith())->toBe(['round' => 'Fase de Grupos']);
});

it('RoundLocked broadcasts to presence-quinela with round name', function () {
    $event = new RoundLocked(roundName: 'R32-R16');

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
    expect($event->broadcastOn()[0])->toBeInstanceOf(PresenceChannel::class);
    expect($event->broadcastWith())->toBe(['round' => 'R32-R16']);
});

it('MessageSent broadcasts to presence-quinela with full message payload', function () {
    $event = new MessageSent(
        messageId: 1,
        userId: 5,
        userName: 'Juan',
        userAvatar: null,
        content: 'Hola a todos',
        createdAt: '2026-06-01T12:00:00.000000Z',
    );

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
    expect($event->broadcastOn()[0])->toBeInstanceOf(PresenceChannel::class);

    expect($event->broadcastWith())->toBe([
        'id'         => 1,
        'user_id'    => 5,
        'user_name'  => 'Juan',
        'user_avatar'=> null,
        'content'    => 'Hola a todos',
        'created_at' => '2026-06-01T12:00:00.000000Z',
    ]);
});

it('ExactScoreAlert broadcasts to presence-quinela with username and score', function () {
    $event = new ExactScoreAlert(userName: 'Pedro', matchId: 3, homeScore: 3, awayScore: 0);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
    expect($event->broadcastOn()[0])->toBeInstanceOf(PresenceChannel::class);

    expect($event->broadcastWith())->toBe([
        'user_name'  => 'Pedro',
        'match_id'   => 3,
        'home_score' => 3,
        'away_score' => 0,
    ]);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Broadcast/BroadcastEventShapesTest.php
```
Expected: FAIL — classes don't exist.

- [ ] **Step 3: Create `app/Events/LiveScoreUpdated.php`**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class LiveScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int  $matchId,
        public readonly ?int $homeScore,
        public readonly ?int $awayScore,
        public readonly bool $isLive,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return [
            'match_id'   => $this->matchId,
            'home_score' => $this->homeScore,
            'away_score' => $this->awayScore,
            'is_live'    => $this->isLive,
        ];
    }
}
```

- [ ] **Step 4: Create `app/Events/PointsUpdated.php`**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class PointsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $userId,
        public readonly int $totalPoints,
        public readonly int $position,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('quinela'),
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'      => $this->userId,
            'total_points' => $this->totalPoints,
            'position'     => $this->position,
        ];
    }
}
```

- [ ] **Step 5: Create `app/Events/RoundOpened.php`**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class RoundOpened implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly string $roundName) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return ['round' => $this->roundName];
    }
}
```

- [ ] **Step 6: Create `app/Events/RoundLocked.php`**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class RoundLocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly string $roundName) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return ['round' => $this->roundName];
    }
}
```

- [ ] **Step 7: Create `app/Events/MessageSent.php`**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int     $messageId,
        public readonly int     $userId,
        public readonly string  $userName,
        public readonly ?string $userAvatar,
        public readonly string  $content,
        public readonly string  $createdAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->messageId,
            'user_id'     => $this->userId,
            'user_name'   => $this->userName,
            'user_avatar' => $this->userAvatar,
            'content'     => $this->content,
            'created_at'  => $this->createdAt,
        ];
    }
}
```

- [ ] **Step 8: Create `app/Events/ExactScoreAlert.php`**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ExactScoreAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $userName,
        public readonly int    $matchId,
        public readonly int    $homeScore,
        public readonly int    $awayScore,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return [
            'user_name'  => $this->userName,
            'match_id'   => $this->matchId,
            'home_score' => $this->homeScore,
            'away_score' => $this->awayScore,
        ];
    }
}
```

- [ ] **Step 9: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Broadcast/BroadcastEventShapesTest.php
```
Expected: 6 tests passing.

- [ ] **Step 10: Commit**

```bash
git add app/Events/LiveScoreUpdated.php app/Events/PointsUpdated.php \
    app/Events/RoundOpened.php app/Events/RoundLocked.php \
    app/Events/MessageSent.php app/Events/ExactScoreAlert.php \
    tests/Feature/Broadcast/BroadcastEventShapesTest.php
git commit -m "feat: add broadcast event classes (LiveScore, Points, Round, Chat, Alert)"
```

---

## Task 3: Dispatch broadcast events from CalculateMatchPoints

**Files:**
- Modify: `app/Listeners/CalculateMatchPoints.php`
- Create: `tests/Feature/Broadcast/MatchScoreBroadcastTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Broadcast/MatchScoreBroadcastTest.php`:

```php
<?php

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\MatchScoreUpdated;
use App\Events\PointsUpdated;
use App\Listeners\CalculateMatchPoints;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function makeGroupFixtureWithScore(Round $round, int $home, int $away, int $matchNum = 1): Fixture
{
    $group   = Group::factory()->create();
    $homeTeam = Team::factory()->create(['group_id' => $group->id]);
    $awayTeam = Team::factory()->create(['group_id' => $group->id]);

    return Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $homeTeam->id,
        'away_team_id' => $awayTeam->id,
        'home_score'   => $home,
        'away_score'   => $away,
        'status'       => 'finished',
        'match_number' => $matchNum,
    ]);
}

it('dispatches LiveScoreUpdated after match points calculation', function () {
    // Fake only the broadcast events (not MatchScoreUpdated itself)
    Event::fake([LiveScoreUpdated::class, PointsUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->r1()->create();
    $user    = User::factory()->create();
    $fixture = makeGroupFixtureWithScore($round, 2, 1);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 0,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertDispatched(LiveScoreUpdated::class, function ($e) use ($fixture) {
        return $e->matchId === $fixture->id
            && $e->homeScore === 2
            && $e->awayScore === 1
            && $e->isLive === false; // status = finished
    });
});

it('dispatches PointsUpdated for each affected user after calculation', function () {
    Event::fake([LiveScoreUpdated::class, PointsUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->r1()->create();
    $userA   = User::factory()->create();
    $userB   = User::factory()->create();
    $fixture = makeGroupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $userA->id, 'round_id' => $round->id]);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $userB->id, 'round_id' => $round->id]);

    Prediction::factory()->create([
        'user_id' => $userA->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 1,
    ]);
    Prediction::factory()->create([
        'user_id' => $userB->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 0,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertDispatchedTimes(PointsUpdated::class, 2);
});

it('dispatches ExactScoreAlert when a user gets pts_exact', function () {
    Event::fake([LiveScoreUpdated::class, PointsUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->r1()->create();
    $user    = User::factory()->create(['name' => 'Ana']);
    $fixture = makeGroupFixtureWithScore($round, 3, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 3, 'predicted_away' => 1, // exact match
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertDispatched(ExactScoreAlert::class, function ($e) use ($fixture) {
        return $e->userName === 'Ana'
            && $e->matchId === $fixture->id
            && $e->homeScore === 3
            && $e->awayScore === 1;
    });
});

it('does not dispatch ExactScoreAlert when no user gets pts_exact', function () {
    Event::fake([LiveScoreUpdated::class, PointsUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->r1()->create();
    $user    = User::factory()->create();
    $fixture = makeGroupFixtureWithScore($round, 3, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 0, // correct result, not exact
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertNotDispatched(ExactScoreAlert::class);
});

it('dispatches LiveScoreUpdated with isLive=true when match is in_progress', function () {
    Event::fake([LiveScoreUpdated::class, PointsUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create();
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => 1,
        'away_score'   => 0,
        'status'       => 'in_progress',
        'match_number' => 1,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertDispatched(LiveScoreUpdated::class, fn ($e) => $e->isLive === true);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Broadcast/MatchScoreBroadcastTest.php
```
Expected: FAIL — listener doesn't dispatch broadcast events yet.

- [ ] **Step 3: Modify `app/Listeners/CalculateMatchPoints.php`**

Replace the full file:

```php
<?php

namespace App\Listeners;

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\MatchScoreUpdated;
use App\Events\PointsUpdated;
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

        $affectedUserIds  = [];
        $exactScoreHitters = []; // user IDs who got pts_exact

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
                if ($fixture->winner_team_id !== null && $prediction->predicted_home !== $prediction->predicted_away) {
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

            if ($ptsExact > 0) {
                $exactScoreHitters[] = $prediction->user_id;
            }
        }

        // Recalculate totals and broadcast per-user updates
        foreach (array_unique($affectedUserIds) as $userId) {
            User::recalculateTotalPoints($userId);

            $user     = User::find($userId);
            $position = User::where('total_points', '>', $user->total_points)->count() + 1;

            PointsUpdated::dispatch($userId, $user->total_points, $position);
        }

        // Broadcast live score once
        LiveScoreUpdated::dispatch(
            matchId:   $fixture->id,
            homeScore: $fixture->home_score,
            awayScore: $fixture->away_score,
            isLive:    $fixture->status === 'in_progress',
        );

        // Broadcast exact score alerts
        foreach ($exactScoreHitters as $userId) {
            $user = User::find($userId);
            ExactScoreAlert::dispatch(
                userName:  $user->name,
                matchId:   $fixture->id,
                homeScore: $fixture->home_score,
                awayScore: $fixture->away_score,
            );
        }
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Broadcast/MatchScoreBroadcastTest.php
```
Expected: 5 tests passing.

- [ ] **Step 5: Run the existing CalculateMatchPoints tests to confirm nothing broke**

```bash
./vendor/bin/sail test tests/Feature/CalculateMatchPointsTest.php
```
Expected: all 9 existing tests still passing.

- [ ] **Step 6: Commit**

```bash
git add app/Listeners/CalculateMatchPoints.php \
    tests/Feature/Broadcast/MatchScoreBroadcastTest.php
git commit -m "feat: dispatch LiveScoreUpdated, PointsUpdated, ExactScoreAlert from CalculateMatchPoints"
```

---

## Task 4: Dispatch RoundOpened + RoundLocked from RoundController

**Files:**
- Modify: `app/Http/Controllers/Admin/RoundController.php`
- Create: `tests/Feature/Broadcast/RoundBroadcastTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Broadcast/RoundBroadcastTest.php`:

```php
<?php

use App\Events\RoundLocked;
use App\Events\RoundOpened;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
});

it('dispatches RoundOpened when admin opens a round', function () {
    Event::fake([RoundOpened::class]);
    $round = Round::factory()->r1()->create(['is_open' => false, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->id}/open");

    Event::assertDispatched(RoundOpened::class, fn ($e) => $e->roundName === $round->name);
});

it('does not dispatch RoundOpened when round is already locked', function () {
    Event::fake([RoundOpened::class]);
    $round = Round::factory()->r1()->create(['is_open' => false, 'is_locked' => true]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->id}/open");

    Event::assertNotDispatched(RoundOpened::class);
});

it('dispatches RoundLocked when admin locks a round', function () {
    Event::fake([RoundLocked::class]);
    $round = Round::factory()->r1()->create(['is_open' => true, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->id}/lock");

    Event::assertDispatched(RoundLocked::class, fn ($e) => $e->roundName === $round->name);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/Broadcast/RoundBroadcastTest.php
```
Expected: FAIL — events not dispatched yet.

- [ ] **Step 3: Modify `app/Http/Controllers/Admin/RoundController.php`**

Replace the full file:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Events\RoundFinalized;
use App\Events\RoundLocked;
use App\Events\RoundOpened;
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
        if ($round->is_locked) {
            return back()->with('status', 'No se puede abrir una ronda bloqueada.');
        }

        $round->update(['is_open' => true]);

        RoundOpened::dispatch($round->name);

        return back()->with('status', "Ronda '{$round->name}' abierta.");
    }

    public function lock(Round $round): RedirectResponse
    {
        $round->update(['is_open' => false, 'is_locked' => true]);

        RoundLocked::dispatch($round->name);

        return back()->with('status', "Ronda '{$round->name}' bloqueada.");
    }

    public function finalize(Round $round): RedirectResponse
    {
        if ($round->is_locked) {
            return back()->with('status', 'Esta ronda ya está finalizada.');
        }

        $round->update(['is_open' => false, 'is_locked' => true]);

        RoundLocked::dispatch($round->name);
        RoundFinalized::dispatch($round);

        return back()->with('status', "Ronda '{$round->name}' finalizada.");
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/Broadcast/RoundBroadcastTest.php
```
Expected: 3 tests passing.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/RoundController.php \
    tests/Feature/Broadcast/RoundBroadcastTest.php
git commit -m "feat: dispatch RoundOpened and RoundLocked from RoundController"
```

---

## Task 5: Chat backend

**Files:**
- Create: `app/Http/Controllers/ChatController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/ChatControllerTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/ChatControllerTest.php`:

```php
<?php

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true]);
});

it('shows the chat page with last 50 messages in chronological order', function () {
    // Create 60 messages; only last 50 should appear
    Message::factory(60)->create(['user_id' => $this->user->id]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/chat');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Chat')
        ->has('messages', 50)
    );
});

it('messages are returned in chronological order (oldest first)', function () {
    $old = Message::factory()->create(['user_id' => $this->user->id, 'created_at' => now()->subMinutes(5)]);
    $new = Message::factory()->create(['user_id' => $this->user->id, 'created_at' => now()]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/chat');

    $messages = $response->inertia('messages');
    expect($messages[0]['id'])->toBe($old->id);
    expect($messages[1]['id'])->toBe($new->id);
});

it('messages include user name and avatar', function () {
    Message::factory()->create(['user_id' => $this->user->id]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/chat');

    $response->assertInertia(fn ($page) => $page
        ->has('messages.0.user.name')
        ->has('messages.0.user.avatar')
    );
});

it('stores a new message and dispatches MessageSent', function () {
    Event::fake([MessageSent::class]);

    $this->actingAs($this->user)->post('/chat/messages', [
        'content' => 'Vamos Argentina!',
    ])->assertRedirect();

    expect(Message::count())->toBe(1);
    expect(Message::first()->content)->toBe('Vamos Argentina!');
    expect(Message::first()->user_id)->toBe($this->user->id);

    Event::assertDispatched(MessageSent::class, function ($e) {
        return $e->userId === $this->user->id
            && $e->userName === $this->user->name
            && $e->content === 'Vamos Argentina!';
    });
});

it('rejects empty message content', function () {
    $this->actingAs($this->user)->post('/chat/messages', [
        'content' => '',
    ])->assertSessionHasErrors('content');

    expect(Message::count())->toBe(0);
});

it('rejects message content over 500 characters', function () {
    $this->actingAs($this->user)->post('/chat/messages', [
        'content' => str_repeat('a', 501),
    ])->assertSessionHasErrors('content');
});

it('guests cannot access chat', function () {
    $this->get('/chat')->assertRedirect('/login');
    $this->post('/chat/messages', ['content' => 'hi'])->assertRedirect('/login');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/ChatControllerTest.php
```
Expected: FAIL — routes don't exist.

- [ ] **Step 3: Create `app/Http/Controllers/ChatController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(): Response
    {
        $messages = Message::with('user:id,name,avatar')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return Inertia::render('Chat', [
            'messages' => $messages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:500'],
        ]);

        $message = Message::create([
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        $message->load('user:id,name,avatar');

        MessageSent::dispatch(
            messageId:  $message->id,
            userId:     $request->user()->id,
            userName:   $request->user()->name,
            userAvatar: $request->user()->avatar,
            content:    $data['content'],
            createdAt:  $message->created_at->toISOString(),
        );

        return back();
    }
}
```

- [ ] **Step 4: Add routes to `routes/web.php`**

Read `routes/web.php` first. Add import at the top with other controller imports:

```php
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RankingController;
```

Add inside the `auth` middleware group (after the profile routes, before `require __DIR__.'/auth.php'`):

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.store');
});
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/ChatControllerTest.php
```
Expected: 7 tests passing.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ChatController.php routes/web.php \
    tests/Feature/ChatControllerTest.php
git commit -m "feat: add ChatController with MessageSent broadcast"
```

---

## Task 6: Ranking backend

**Files:**
- Create: `app/Http/Controllers/RankingController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/RankingControllerTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/RankingControllerTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true, 'total_points' => 0]);
});

it('shows ranking page with active users ordered by total_points desc', function () {
    $first  = User::factory()->create(['is_active' => true, 'total_points' => 100]);
    $second = User::factory()->create(['is_active' => true, 'total_points' => 80]);
    $third  = User::factory()->create(['is_active' => true, 'total_points' => 60]);
    User::factory()->create(['is_active' => false, 'total_points' => 999]); // excluded

    $response = $this->withoutVite()->actingAs($this->user)->get('/ranking');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Ranking')
        ->has('users', 4) // 3 created + $this->user
    );

    $users = $response->inertia('users');
    expect($users[0]['id'])->toBe($first->id);
    expect($users[0]['position'])->toBe(1);
    expect($users[1]['id'])->toBe($second->id);
    expect($users[1]['position'])->toBe(2);
    expect($users[2]['id'])->toBe($third->id);
    expect($users[2]['position'])->toBe(3);
});

it('includes id, name, avatar, total_points, position in each row', function () {
    $response = $this->withoutVite()->actingAs($this->user)->get('/ranking');

    $response->assertInertia(fn ($page) => $page
        ->has('users.0.id')
        ->has('users.0.name')
        ->has('users.0.total_points')
        ->has('users.0.position')
    );
});

it('guests cannot access ranking', function () {
    $this->get('/ranking')->assertRedirect('/login');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail test tests/Feature/RankingControllerTest.php
```
Expected: FAIL — route doesn't exist.

- [ ] **Step 3: Create `app/Http/Controllers/RankingController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(): Response
    {
        $users = User::where('is_active', true)
            ->orderByDesc('total_points')
            ->select(['id', 'name', 'avatar', 'total_points'])
            ->get()
            ->values()
            ->map(fn ($user, $index) => array_merge($user->toArray(), ['position' => $index + 1]));

        return Inertia::render('Ranking', [
            'users' => $users,
        ]);
    }
}
```

- [ ] **Step 4: Add ranking route to `routes/web.php`**

Inside the same `auth` middleware group added in Task 5 (alongside `/chat` and `/chat/messages`), add:

```php
    Route::get('/ranking', [RankingController::class, 'index'])->name('ranking');
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
./vendor/bin/sail test tests/Feature/RankingControllerTest.php
```
Expected: 3 tests passing.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/RankingController.php routes/web.php \
    tests/Feature/RankingControllerTest.php
git commit -m "feat: add RankingController with position-sorted user list"
```

---

## Task 7: Frontend — Chat.jsx

**Files:**
- Create: `resources/js/Pages/Chat.jsx`

**Note:** `window.Echo` is already available (imported via `resources/js/bootstrap.js` → `./echo`). The presence channel name `quinela` maps to `presence-quinela` on the client. Laravel Echo broadcasts with the short class name as event key: `MessageSent` becomes `.MessageSent` (dot-prefixed by Echo convention). `broadcastWith()` fields are the event payload.

- [ ] **Step 1: Create `resources/js/Pages/Chat.jsx`**

```jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function Chat({ messages: initialMessages }) {
    const [messages, setMessages] = useState(initialMessages);
    const bottomRef = useRef(null);
    const { data, setData, post, processing, reset } = useForm({ content: '' });

    // Subscribe to presence-quinela and listen for new messages
    useEffect(() => {
        const channel = window.Echo.join('quinela');

        channel.listen('.MessageSent', (event) => {
            setMessages((prev) => [
                ...prev,
                {
                    id:         event.id,
                    content:    event.content,
                    created_at: event.created_at,
                    user: {
                        id:     event.user_id,
                        name:   event.user_name,
                        avatar: event.user_avatar,
                    },
                },
            ]);
        });

        return () => {
            window.Echo.leave('quinela');
        };
    }, []);

    // Scroll to bottom when messages change
    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    function handleSubmit(e) {
        e.preventDefault();
        if (!data.content.trim()) return;
        post(route('chat.store'), {
            preserveScroll: true,
            onSuccess: () => reset('content'),
        });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Chat</h2>}>
            <Head title="Chat" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 flex flex-col gap-4">

                    {/* Message history */}
                    <div className="bg-white shadow rounded-lg p-4 h-96 overflow-y-auto flex flex-col gap-3">
                        {messages.length === 0 && (
                            <p className="text-center text-sm text-gray-400 mt-auto mb-auto">
                                Aún no hay mensajes. ¡Sé el primero!
                            </p>
                        )}
                        {messages.map((msg) => (
                            <div key={msg.id} className="flex items-start gap-2">
                                <div className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-200 flex items-center justify-center text-xs font-bold text-indigo-700">
                                    {msg.user.name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <span className="text-xs font-semibold text-gray-700">{msg.user.name}</span>
                                    <p className="text-sm text-gray-800">{msg.content}</p>
                                    <span className="text-xs text-gray-400">
                                        {new Date(msg.created_at).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })}
                                    </span>
                                </div>
                            </div>
                        ))}
                        <div ref={bottomRef} />
                    </div>

                    {/* Send form */}
                    <form onSubmit={handleSubmit} className="flex gap-2">
                        <input
                            type="text"
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            placeholder="Escribí un mensaje..."
                            maxLength={500}
                            className="flex-1 rounded-md border-gray-300 shadow-sm text-sm"
                        />
                        <button
                            type="submit"
                            disabled={processing || !data.content.trim()}
                            className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Enviar
                        </button>
                    </form>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
```

- [ ] **Step 2: Verify the page renders (no build errors)**

```bash
./vendor/bin/sail pnpm run build 2>&1 | tail -10
```
Expected: Build completes with no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Chat.jsx
git commit -m "feat: add Chat.jsx page with real-time message reception"
```

---

## Task 8: Frontend — Ranking.jsx

**Files:**
- Create: `resources/js/Pages/Ranking.jsx`

- [ ] **Step 1: Create `resources/js/Pages/Ranking.jsx`**

```jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Ranking({ users: initialUsers }) {
    const { auth } = usePage().props;
    const [users, setUsers] = useState(initialUsers);

    // Listen for PointsUpdated events and update the ranking table in real-time
    useEffect(() => {
        const channel = window.Echo.join('quinela');

        channel.listen('.PointsUpdated', (event) => {
            setUsers((prev) => {
                // Update the affected user's total_points
                const updated = prev.map((u) =>
                    u.id === event.user_id
                        ? { ...u, total_points: event.total_points }
                        : u
                );
                // Re-sort and re-assign positions
                const sorted = [...updated].sort((a, b) => b.total_points - a.total_points);
                return sorted.map((u, i) => ({ ...u, position: i + 1 }));
            });
        });

        return () => {
            window.Echo.leave('quinela');
        };
    }, []);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Ranking</h2>}>
            <Head title="Ranking" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jugador</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Puntos</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {users.map((user) => (
                                    <tr
                                        key={user.id}
                                        className={user.id === auth.user.id ? 'bg-indigo-50' : ''}
                                    >
                                        <td className="px-4 py-3 text-sm font-bold text-gray-700">
                                            {user.position}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-800">
                                            {user.name}
                                            {user.id === auth.user.id && (
                                                <span className="ml-2 text-xs text-indigo-500">(vos)</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-sm font-semibold text-right text-indigo-700">
                                            {user.total_points}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
```

- [ ] **Step 2: Verify build succeeds**

```bash
./vendor/bin/sail pnpm run build 2>&1 | tail -10
```
Expected: Build completes with no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Ranking.jsx
git commit -m "feat: add Ranking.jsx page with real-time PointsUpdated updates"
```

---

## Task 9: Full test suite verification

**Files:** None (verification only)

- [ ] **Step 1: Run the full test suite**

```bash
./vendor/bin/sail test
```
Expected: all tests passing (zero FAIL). Target: 170+ tests.

- [ ] **Step 2: Fix any failures**

Common issues and fixes:

**Broadcast events dispatched in tests that use Event::fake() globally:**
If a test in another file uses `Event::fake()` without specifying which events to fake, it will also fake the new broadcast events. Check that existing tests still pass with the modified `CalculateMatchPoints` listener. Use `Event::fake([BroadcastEvent::class])` (targeted faking) in the new tests and verify existing tests are unaffected.

**`inertia()` helper missing in test assertions:**
If `$response->inertia('key')` fails, use `$response->original->getData()['page']['props']['key']` as fallback, or add the `inertia-testing` package assertion style.

**`makeGroupFixtureWithScore` function name collision:**
The helper defined in `MatchScoreBroadcastTest.php` uses a different name than helpers in other test files (`groupFixtureWithScore` in `CalculateMatchPointsTest.php`). They should not conflict since PHP test functions are file-scoped in Pest.

- [ ] **Step 3: Confirm final count**

Expected: 170+ tests, zero failures.

- [ ] **Step 4: Commit any fixes**

```bash
git add -p
git commit -m "fix: address full suite failures after Plan 5"
```

---

## Self-Review

**Spec coverage check:**

| Spec requirement | Covered by |
|---|---|
| `presence-quinela` channel auth (active users only) | Task 1 |
| `private-user.{userId}` channel auth | Task 1 |
| Channel returns user id/name/avatar for presence | Task 1 (broadcastWith in channel callback) |
| `MatchScoreUpdated` live score broadcast | Task 3 (LiveScoreUpdated) |
| `PointsUpdated` to presence + private channels | Task 2 + 3 |
| `RoundOpened` broadcast | Task 4 |
| `RoundLocked` broadcast | Task 4 |
| `RoundFinalized` → also locks → dispatches RoundLocked | Task 4 (finalize calls RoundLocked too) |
| `MessageSent` broadcast to presence-quinela | Task 2 + 5 |
| `ExactScoreAlert` broadcast | Task 2 + 3 |
| Chat history: last 50 messages | Task 5 + 7 |
| Chat history: chronological order (oldest first) | Task 5 |
| Chat messages include user name + avatar | Task 5 + 7 |
| Chat POST stores message + broadcasts | Task 5 |
| Chat max 500 chars | Task 5 |
| Ranking: ordered by total_points desc | Task 6 + 8 |
| Ranking: excludes inactive users | Task 6 |
| Ranking: includes position | Task 6 + 8 |
| Real-time ranking updates via PointsUpdated | Task 8 (Ranking.jsx listener) |
| Real-time chat via MessageSent | Task 7 (Chat.jsx listener) |
| `PointsUpdated` includes position field | Task 2 (broadcastWith) + Task 3 (rank formula) |
| `isLive: true` when match is in_progress | Task 3 |

**Placeholder scan:** None found.

**Type consistency:**

- `LiveScoreUpdated(matchId: int, homeScore: ?int, awayScore: ?int, isLive: bool)` — dispatched with named args in Task 3 ✓
- `PointsUpdated(userId: int, totalPoints: int, position: int)` — dispatched in Task 3 ✓
- `RoundOpened(roundName: string)` / `RoundLocked(roundName: string)` — dispatched with `$round->name` in Task 4 ✓
- `MessageSent(messageId, userId, userName, userAvatar, content, createdAt)` — dispatched in Task 5 ✓
- `ExactScoreAlert(userName, matchId, homeScore, awayScore)` — dispatched in Task 3 ✓
- Frontend `event.user_id`, `event.user_name`, `event.user_avatar`, `event.content`, `event.created_at` in Chat.jsx match `broadcastWith()` keys in MessageSent ✓
- Frontend `event.user_id`, `event.total_points` in Ranking.jsx match `broadcastWith()` keys in PointsUpdated ✓
- Channel name `'quinela'` used consistently in `routes/channels.php` and all `broadcastOn()` methods ✓
