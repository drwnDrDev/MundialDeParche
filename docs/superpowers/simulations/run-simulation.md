# Layer 2 Simulation — Run Guide

End-to-end tournament lifecycle simulation using a Claude Code orchestrator that launches sub-agents running real HTTP requests against the local app.

---

## Preconditions

**1. Docker Desktop must be running.**

**2. Start the Sail containers:**

```bash
./vendor/bin/sail up -d
```

**3. Reset the database with simulation seed data:**

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

**4. Verify the sim users exist:**

```bash
./vendor/bin/sail artisan tinker --execute="App\Models\User::where('email', 'like', '%@sim.test')->pluck('email')"
```

Expected output:

```
Illuminate\Support\Collection {#XXXX
  all: [
    "admin@sim.test",
    "alice@sim.test",
    "bob@sim.test",
    "carlos@sim.test",
    "diana@sim.test",
    "ernesto@sim.test",
  ],
}
```

**Credentials for all sim accounts:** `simpassword`

---

## How the Simulation Works

The orchestrator is a Claude Code agent that spawns sub-agents in chronological order. Each sub-agent authenticates as a specific user and performs HTTP requests (via `curl`) against `http://localhost`.

### Agents

| Agent | Authenticated As | Role |
|-------|-----------------|------|
| `admin-agent` | `admin@sim.test` | Opens rounds, loads scores, locks/finalizes rounds, finalizes tournament |
| `user-1-agent` | `alice@sim.test` | Predicts all R1 matches, submits specials, predicts R2 |
| `user-2-agent` | `bob@sim.test` | Predicts only some R1 matches, skips specials entirely |
| `user-3-agent` | `carlos@sim.test` | Predicts R1 just before lock deadline |
| `observer-agent` | `alice@sim.test` (read-only) | After each admin action, verifies ranking/points/round status via GET requests |

---

## Chronological Flow

```
[T+0]  admin-agent:          opens R1
[T+0]  user-1, user-2, user-3: predict R1 (run in parallel)
[T+0]  user-1-agent:         submits special predictions (must happen BEFORE R1 lock)
[T+1]  admin-agent:          locks R1
[T+1]  user-1-agent:         attempts to edit a R1 prediction → must fail (verify 403 or redirect)
[T+2]  admin-agent:          loads 3 R1 match scores (triggers CalculateMatchPoints)
[T+2]  observer-agent:       verifies points and ranking via GET /ranking
[T+3]  admin-agent:          finalizes R1 (triggers CalculateClassifierPoints)
[T+3]  observer-agent:       verifies pts_classifier awarded in submissions
[T+4]  admin-agent:          opens R2
[T+4]  user-1, user-2:       predict R2 (run in parallel)
[T+5]  admin-agent:          loads R2 match scores, finalizes R2
[T+5]  observer-agent:       verifies final ranking totals
```

---

## How Agents Authenticate

Agents use `curl` cookie jars. All requests go to `http://localhost`.

### Step 1 — Fetch the login page and capture the CSRF token

```bash
curl -s -c /tmp/cookies-alice.txt http://localhost/login -o /tmp/login-page.html
grep -o 'name="_token" value="[^"]*"' /tmp/login-page.html | grep -o 'value="[^"]*"' | cut -d'"' -f2
```

Save the token value to a variable:

```bash
TOKEN=$(curl -s -c /tmp/cookies-alice.txt http://localhost/login \
  | grep -o 'name="_token" value="[^"]*"' \
  | grep -o 'value="[^"]*"' \
  | cut -d'"' -f2)
```

### Step 2 — POST to `/login`

```bash
curl -s -b /tmp/cookies-alice.txt -c /tmp/cookies-alice.txt \
  -X POST http://localhost/login \
  -d "_token=$TOKEN&email=alice@sim.test&password=simpassword" \
  -L -o /dev/null -w "%{http_code}"
```

A `200` or `302` followed by a redirect to `/` or `/predictions` confirms successful login.

### Step 3 — Use the cookie jar for subsequent requests

```bash
# Example: GET the predictions index
curl -s -b /tmp/cookies-alice.txt -c /tmp/cookies-alice.txt \
  http://localhost/predictions

# Example: POST a prediction (requires CSRF token from the form)
curl -s -b /tmp/cookies-alice.txt -c /tmp/cookies-alice.txt \
  -X POST http://localhost/predictions/1/save \
  -H "X-XSRF-TOKEN: $XSRF" \
  -d "predicted_home=2&predicted_away=1"
```

For JSON API endpoints, extract `XSRF-TOKEN` from the cookie jar:

```bash
XSRF=$(grep XSRF-TOKEN /tmp/cookies-alice.txt | awk '{print $NF}' | python3 -c "import sys, urllib.parse; print(urllib.parse.unquote(sys.stdin.read().strip()))")

curl -s -b /tmp/cookies-alice.txt -c /tmp/cookies-alice.txt \
  -X POST http://localhost/some-endpoint \
  -H "Content-Type: application/json" \
  -H "X-XSRF-TOKEN: $XSRF" \
  -d '{"key": "value"}'
```

Each agent should use its own cookie file (e.g., `/tmp/cookies-admin.txt`, `/tmp/cookies-alice.txt`, etc.).

### Step 4 — Refresh XSRF-TOKEN before each POST/PATCH

Laravel rotates the XSRF-TOKEN after each state-changing request. Before every POST or PATCH, hit `/sanctum/csrf-cookie` to get a fresh token:

```bash
curl -s -b /tmp/cookies-alice.txt -c /tmp/cookies-alice.txt \
  http://localhost/sanctum/csrf-cookie -o /dev/null

XSRF=$(grep XSRF-TOKEN /tmp/cookies-alice.txt | awk '{print $NF}' | python3 -c "import sys, urllib.parse; print(urllib.parse.unquote(sys.stdin.read().strip()))")
```

Then use `$XSRF` as `X-XSRF-TOKEN` header in the next request. Skipping this step causes 419 (CSRF token mismatch) errors.

---

## Running the Orchestrator

Open Claude Code and paste the following prompt to launch the simulation:

```
You are the orchestrator for a Layer 2 end-to-end simulation of PollaMundial,
a FIFA World Cup 2026 quinela app running locally at http://localhost.

Your job is to execute the full tournament lifecycle in chronological order by
running sub-agents (via the Agent tool or by issuing curl commands directly).
Follow the chronological flow below exactly:

Credentials (all use password: simpassword):
- admin@sim.test       → administrator
- alice@sim.test       → user-1 (predicts everything)
- bob@sim.test         → user-2 (predicts partially)
- carlos@sim.test      → user-3 (late predictor)
- diana@sim.test       → user-4 (observer / read-only checks)

Chronological flow:
[T+0]  admin opens R1 (POST /admin/rounds/{id}/open)
[T+0]  alice, bob, carlos each predict their R1 matches (in parallel if possible)
[T+0]  alice submits special predictions (POST /predictions/especiales/save — MUST be before R1 lock)
[T+1]  admin locks R1 (POST /admin/rounds/{id}/lock)
[T+1]  alice attempts to edit a R1 prediction → verify it fails (403 or redirect)
[T+2]  admin loads 3 R1 match scores (POST /admin/fixtures/{id}/score for each)
[T+2]  diana (observer) checks GET /ranking and reports points for each user
[T+3]  admin finalizes R1 (POST /admin/rounds/{id}/finalize)
[T+3]  diana checks pts_classifier via tinker or GET /ranking
[T+4]  admin opens R2
[T+4]  alice and bob predict their R2 matches
[T+5]  admin loads R2 scores and finalizes R2
[T+5]  diana checks final ranking and reports total_points per user

For each step:
1. Authenticate the relevant user using the cookie-jar curl flow.
2. Perform the action via HTTP request.
3. Log the HTTP response code and any relevant JSON/HTML snippet.
4. If a step fails unexpectedly, report the error and stop.

Start now with T+0: admin opens R1.
```

---

## What to Verify After the Simulation

Run these checks in `./vendor/bin/sail artisan tinker` after the simulation completes.

### All 5 players have at least one R1 submission

```php
use App\Models\PredictionSubmission;
use App\Models\Round;

$r1 = Round::where('slug', 'group-stage')->first();
PredictionSubmission::where('round_id', $r1->id)
    ->with('user:id,email')
    ->get(['user_id', 'status'])
    ->map(fn($s) => ['email' => $s->user->email, 'status' => $s->status]);
```

### Players who predicted exactly have pts_exact > 0

```php
use App\Models\Prediction;

Prediction::where('pts_exact', '>', 0)
    ->with('user:id,email')
    ->get(['user_id', 'pts_exact', 'pts_result'])
    ->groupBy('user_id')
    ->map(fn($rows, $uid) => [
        'email'     => $rows->first()->user->email,
        'pts_exact' => $rows->sum('pts_exact'),
        'pts_result'=> $rows->sum('pts_result'),
    ]);
```

### Special predictions are locked

```php
use App\Models\SpecialPrediction;
SpecialPrediction::pluck('is_locked', 'user_id');
```

Expected: all values `true` (or `1`).

### R1 is finalized

```php
use App\Models\Round;
Round::where('slug', 'group-stage')->first(['name', 'is_open', 'is_locked', 'is_finalized']);
```

Expected: `is_open = false`, `is_locked = true`, `is_finalized = true`.

### R2 has submissions for alice and bob

```php
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\User;

$r2    = Round::where('slug', 'round-of-16')->first();
$users = User::whereIn('email', ['alice@sim.test', 'bob@sim.test'])->pluck('id');

PredictionSubmission::where('round_id', $r2->id)
    ->whereIn('user_id', $users)
    ->with('user:id,email')
    ->get(['user_id', 'status'])
    ->map(fn($s) => ['email' => $s->user->email, 'status' => $s->status]);
```

### Final ranking matches expected point totals

```php
use App\Models\User;
User::where('email', 'like', '%@sim.test')
    ->where('role', 'user')
    ->orderByDesc('total_points')
    ->get(['email', 'total_points']);
```

alice (predicted everything) should rank highest. carlos (late) and bob (partial) should rank lower. diana and ernesto (no predictions) should have 0.

---

## Checklist

- [ ] All 5 sim players have a submission record for R1
- [ ] Players who predicted exact scores have `pts_exact > 0`
- [ ] `special_predictions.is_locked = true` for all rows
- [ ] R1: `is_open = false`, `is_locked = true`, `is_finalized = true`
- [ ] R2 has submissions for alice and bob
- [ ] `users.total_points` ordering matches expected ranking (alice > bob/carlos > diana/ernesto)
- [ ] Attempting to edit a locked prediction returns 403 or redirects to Locked screen
- [ ] `GET /ranking` returns updated scores after admin loads match scores

---

## Resetting Between Runs

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

This drops all tables, re-runs all migrations, and re-runs all seeders (including `SimulationSeeder`). Cookie files in `/tmp/` can be removed manually:

```bash
rm -f /tmp/cookies-*.txt
```
