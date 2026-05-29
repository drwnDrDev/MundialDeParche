# PollaMundial — Admin Hardening & UX Fixes

**Date:** 2026-05-29
**Status:** Approved

## Scope

7 issues identified after full review of the admin flow (score entry, round lifecycle, ordering, UI, roles, real-time, and mobile layout). Grouped into 3 independent clusters.

---

## Cluster 1: Data Integrity Guards

### 1a. Round finalization idempotency

**Problem:** `RoundController::finalize()` dispatches `RoundFinalized` (which recalculates classifier points) every time it is called. There is no idempotency guard. A second call re-runs point calculation on already-scored predictions.

**Fix:**
- Migration: add `is_finalized boolean default false` to `rounds` table.
- `Round` model: add `is_finalized` to `$fillable` and casts.
- `RoundController::finalize()`: if `$round->is_finalized` → return `back()->with('error', 'Esta fase ya fue finalizada.')`. No event dispatched.
- On successful finalization: `$round->update(['is_finalized' => true])`.
- `Rounds/Index.jsx`: "Finalizar" button is disabled (grayed, lock icon) when `is_finalized === true`. Tooltip: "Fase ya finalizada".

### 1b. Score entry blocked for finished fixtures

**Problem:** `ScoreEntryController::update()` accepts writes on fixtures with `status = 'finished'`. No guard exists.

**Fix:**
- `ScoreEntryController::update()`: if `$fixture->status === 'finished'` → return `back()->with('error', 'Este partido ya está finalizado. Usa la vista de edición para corregir.')`.
- Frontend guard is secondary (see Cluster 2 ScoreEntry UI section).

### 1c. Correction path via FixtureController::update()

`FixtureController::update()` remains unrestricted — it is the explicit correction channel. Use cases:
- Fixing a wrong score after finalization.
- Setting `winner_team_id` for knockout draws (ET/penalties).

`Admin/Fixtures/Edit.jsx`: when `fixture.status === 'finished'`, show a prominent amber warning banner:
> "Estás editando un partido finalizado. Los cambios recalcularán los puntos automáticamente."

No additional confirmation dialog required — the banner is sufficient friction.

---

## Cluster 2: UI/UX

### 2a. Chronological fixture ordering

**Problem:** Three places use `orderBy('match_number')` instead of `orderBy('match_date')`. Match number reflects the FIFA schedule numbering, not necessarily play order (e.g. match 37–48 are all played simultaneously in the group stage).

**Fix:** Change `orderBy('match_number')` → `orderBy('match_date')` in:
- `FixtureController::index()`
- `ScoreEntryController::index()`
- `PredictionController::receipt()` (confirm current order and fix)

### 2b. ScoreEntry UI — one fixture per row, finished fixtures separated

**Problem:** Current layout is a 2–3 column card grid. Dense layout increases risk of editing the wrong fixture.

**New layout:**
- **List, full-width** — one fixture card per row. No column grid.
- **Sort order within round:** `in_progress` first → `scheduled` → `finished` last. Applied in the controller via `orderByRaw("FIELD(status, 'in_progress', 'scheduled', 'finished')")` then `orderBy('match_date')` as secondary sort.
- **Active card (scheduled / in_progress):** same editing UI as current, adapted to full-width row. Score inputs remain large and centered.
- **Finished card:** read-only compact row. Background: `bg-slate-50 border border-slate-200`. Shows teams, final score, status badge. A small "Corregir →" link navigates to `admin.fixtures.edit`. No inputs rendered. Cannot be submitted.
- **Visual separator** between active and finished sections: a labeled divider "— Finalizados ({n}) —".

### 2c. MobileShell max-width

**Problem:** The user-facing shell has no max-width, so it stretches on desktop/large screens and loses the mobile pop-art aesthetic.

**Fix:** `MobileShell.jsx` — wrap children in a `max-w-3xl mx-auto w-full` container (Tailwind `max-w-3xl` = 48rem = 768px). The outer `bg-cream min-h-screen` div keeps the background color edge-to-edge; only the content column is constrained.

```jsx
export default function MobileShell({ children }) {
    return (
        <div className="bg-cream min-h-screen overflow-x-hidden">
            <div className="max-w-3xl mx-auto w-full pb-28 relative">
                {children}
            </div>
        </div>
    );
}
```

---

## Cluster 3: Roles & Real-time

### 3a. Admin excluded from ranking and position counts

**Problem:** `RankingController` and `HomeController` query `where('is_active', true)` which includes the admin user. Admin appears in the ranking and skews position calculations.

**Fix:** Add `->where('role', 'user')` filter to every user query in:
- `RankingController::index()` — users collection and `activated` count.
- `HomeController::index()` — position query (`total_points > X`), `totalActive` count.

### 3b. Admin blocked from making predictions

**Problem:** Admin can navigate to `/predictions` and submit predictions, which would affect ranking via the points engine.

**Fix:** Add an `isAdmin()` guard at the top of these controller actions:
- `PredictionController::index()`, `show()`, `save()`, `submit()`, `receipt()`
- `SpecialPredictionController::show()`, `save()`

Guard: `if (auth()->user()->isAdmin()) return redirect()->route('admin.dashboard');`

A route-level middleware is not used here (admin shares the same auth middleware group as users) — per-action guards keep it explicit and easy to reason about.

### 3c. Admin navigation — Chat link

**Problem:** Admin is directed to `/admin` and has no path to `/chat`. Since the admin should be able to communicate with players via chat, a Chat link must exist in the admin navigation.

**Fix:** Add a "Chat" nav link to `AdminLayout.jsx` pointing to `route('chat.index')` (existing user-facing chat route). The link opens in the same tab. Style consistent with existing admin nav items.

### 3d. LiveScoreUpdated broadcast from ScoreEntry

**Problem:** `ScoreEntryController::update()` dispatches `MatchScoreUpdated` (which triggers point recalculation) but never dispatches `LiveScoreUpdated`. The `LiveScoreUpdated` event exists and the frontend `Home.jsx` listens to it via Echo, but it is never fired. Result: the live score banner on the home screen never updates in real-time.

**Fix:** In `ScoreEntryController::update()`, after saving the fixture, conditionally dispatch `LiveScoreUpdated`:
- If new status is `in_progress`: dispatch with current scores and `is_live = true`.
- If new status is `finished`: dispatch with final scores and `is_live = false` (clears the live banner).
- If status is `scheduled`: no dispatch needed (no live state change).

`LiveScoreUpdated` already has the correct constructor signature and broadcasts on `presence-quinela`.

---

## Files affected (summary)

| File | Change |
|---|---|
| `database/migrations/…_add_is_finalized_to_rounds_table.php` | New migration |
| `app/Models/Round.php` | Add `is_finalized` to fillable + casts |
| `app/Http/Controllers/Admin/RoundController.php` | Guard + set `is_finalized` |
| `app/Http/Controllers/Admin/ScoreEntryController.php` | Guard finished + dispatch LiveScoreUpdated + ordering |
| `app/Http/Controllers/Admin/FixtureController.php` | orderBy match_date |
| `app/Http/Controllers/RankingController.php` | role=user filter |
| `app/Http/Controllers/HomeController.php` | role=user filter |
| `app/Http/Controllers/PredictionController.php` | Admin guard + receipt ordering |
| `app/Http/Controllers/SpecialPredictionController.php` | Admin guard |
| `resources/js/Pages/Admin/Rounds/Index.jsx` | Disable finalize button when is_finalized |
| `resources/js/Pages/Admin/ScoreEntry.jsx` | Full redesign: 1-per-row, finished section, lock UI |
| `resources/js/Pages/Admin/Fixtures/Edit.jsx` | Amber banner for finished fixtures |
| `resources/js/Layouts/AdminLayout.jsx` | Chat nav link |
| `resources/js/Components/MobileShell.jsx` | max-w-3xl mx-auto wrapper |

---

## Out of scope

- Reverb/Echo connection setup (already handled in Plan 5).
- Changes to the points recalculation logic (already hardened in Plan 6a).
- New tests beyond happy-path and the new guard paths.
