# Spec: Matches — Round Navigation + Knockout UI

**Date:** 2026-06-03  
**Status:** Approved

---

## Goal

Replace the per-day date chips in the Matches calendar view with per-round chips driven by the 4 DB rounds (Fase de Grupos / Round of 32 / Octavos+Cuartos / Semis+Final). Auto-position to the current round on load. Improve knockout MatchCards to show match number in the footer and surface the ET/PEN winner when applicable.

---

## Files Changed

| File | Change |
|---|---|
| `app/Http/Controllers/MatchesController.php` | Add `rounds` prop; add `matchNumber`, `roundSlug`, `wentToET`, `winner` to `formatFixture` |
| `resources/js/Pages/Matches.jsx` | Replace date chips with round chips; auto-scroll to active chip + today's DayBlock; fix LiveScoreUpdated status |
| `resources/js/Components/composed/MatchCard.jsx` | Add `matchNumber`, `roundSlug`, `wentToET`, `winner` props; update footer logic; ET/PEN badge |

---

## 1. Backend — `MatchesController`

### 1a. New prop `rounds`

Added to `index()` alongside `matchDays` and `groups`:

```php
$rounds = Round::orderBy('order')
    ->withCount('fixtures')
    ->get()
    ->filter(fn ($r) => $r->fixtures_count > 0)
    ->map(fn ($r) => [
        'slug'        => $r->slug,
        'name'        => $r->name,
        'matchCount'  => $r->fixtures_count,
        'isOpen'      => (bool) $r->is_open,
    ])
    ->values();
```

The frontend uses `isOpen` to determine the default selected chip.

### 1b. `formatFixture` additions

```php
'matchNumber' => $f->match_number,
'roundSlug'   => $f->round->slug,
'wentToET'    => (bool) $f->went_to_extra_time,
'winner'      => $f->winnerTeam?->fifa_code,
```

`winner` is the FIFA code of the team in `winner_team_id`. It is non-null only when the match is finished and a winner was recorded (always in knockout, never in group draws).

The `teamA` / `teamB` fields retain the existing fallback logic:
```php
$f->homeTeam?->fifa_code ?? $f->home_placeholder ?? 'TBD'
```
This means unassigned knockout slots show "Ganador E", "3° mejor A/B/C/D/F", "Ganador M73", etc. — no change needed here.

### 1c. Round eager-load

Add `->with('round')` to the fixtures query so `$f->round->slug` is available without N+1:

```php
$fixtures = Fixture::with(['homeTeam', 'awayTeam', 'group', 'round'])
    ->orderBy('match_date')
    ->orderBy('match_number')
    ->get();
```

---

## 2. Frontend — `Matches.jsx`

### 2a. State changes

Remove `selectedDate`. Add `selectedRound`.

```js
// Default: slug of the open round, fallback to first round
const defaultRound = rounds.find(r => r.isOpen)?.slug ?? rounds[0]?.slug ?? null;
const [selectedRound, setSelectedRound] = useState(defaultRound);
```

### 2b. `visibleDays` filtering

```js
const visibleDays = matchDays
    .map(day => ({
        ...day,
        matches: day.matches.filter(m => m.roundSlug === selectedRound),
    }))
    .filter(day => day.matches.length > 0);
```

### 2c. Round chips (replace date chips)

The 4 rounds from the DB (slugs: `grupos`, `r32`, `f3`, `f4`) drive the chips dynamically — labels come from `round.name`, not hardcoded strings.

```jsx
<div className="pt-3 pl-[14px]">
    <div className="flex gap-1.5 overflow-x-auto pr-[14px] pb-1">
        {rounds.map(round => (
            <RoundChip
                key={round.slug}
                label={round.name.toUpperCase()}
                count={round.matchCount}
                active={selectedRound === round.slug}
                ref={selectedRound === round.slug ? activeChipRef : null}
                onClick={() => setSelectedRound(round.slug)}
            />
        ))}
    </div>
</div>
```

`RoundChip` is a local component (same file). Same visual style as `DateChip` was:
- Active: `bg-pop-red text-white shadow-pop`
- Inactive: `bg-white text-ink shadow-pop-sm`
- Label: `font-display text-[13px]`
- Sub-label: match count in `font-mono text-[9px]`

### 2d. Auto-scroll — active chip

```js
const activeChipRef = useRef(null);
useEffect(() => {
    activeChipRef.current?.scrollIntoView({ inline: 'center', behavior: 'instant', block: 'nearest' });
}, [selectedRound]);
```

Fires on mount (initial render) and whenever the user taps a chip.

### 2e. Auto-scroll — today's DayBlock

```js
const todayRef = useRef(null);
useEffect(() => {
    todayRef.current?.scrollIntoView({ behavior: 'instant', block: 'start' });
}, [selectedRound]);
```

`todayRef` is passed as a `ref` prop to the `DayBlock` whose `dateKey === today`. Only one DayBlock gets it; if today has no matches in the selected round, nothing scrolls.

`DayBlock` needs to accept a `ref` — wrap with `forwardRef`:

```js
const DayBlock = forwardRef(function DayBlock({ day }, ref) {
    return <div ref={ref} className="mb-3"> ... </div>;
});
```

### 2f. Fix LiveScoreUpdated status

Same fix applied in the previous session to `Home.jsx` — use `e.status` instead of `e.is_live`:

```js
channel.listen('.LiveScoreUpdated', (event) => {
    setMatchDays(prev => prev.map(day => ({
        ...day,
        matches: day.matches.map(m =>
            m.id === event.match_id
                ? {
                    ...m,
                    home_score: event.home_score,
                    away_score: event.away_score,
                    status: event.status === 'in_progress' ? 'in_progress' : event.status,
                  }
                : m
        ),
    })));
});
```

---

## 3. `MatchCard.jsx`

### 3a. New props

```js
export default function MatchCard({
    // existing...
    matchNumber, roundSlug, wentToET, winner,
})
```

### 3b. Footer — match info section

Replace the current `GRUPO {group} · {venue}` with:

```js
const matchInfo = roundSlug === 'grupos'
    ? `GRUPO ${group} · ${venue}`
    : `M${matchNumber} · ${venue}`;
```

### 3c. ET/PEN badge

Shown in the footer when `isFT && wentToET && winner`.

```jsx
{isFT && wentToET && winner && (
    <span className="inline-flex items-center gap-1 px-1.5 py-0.5 border-[1.5px] border-ink bg-pop-yel text-ink font-mono text-[9px] font-bold tracking-[.06em]">
        {winner} · ET/PEN
    </span>
)}
```

Positioned in the footer row on the left (before the match info), or right — wherever `myPick` is not. Since `myPick` is right-aligned, the ET/PEN badge goes left alongside `matchInfo`.

**Footer layout (final):**

```
[ matchInfo ]                    [ myPick / FALTAN TUS GOLES ]
[ winner ET/PEN badge — if applicable ]
```

Actually, to avoid crowding a single row, place them in two rows only when badge is present:

```jsx
<div className="footer flex items-center justify-between gap-1.5 ...">
    <div className="flex flex-col gap-0.5">
        <span className="opacity-65">{matchInfo}</span>
        {isFT && wentToET && winner && (
            <span className="... bg-pop-yel">{winner} · ET/PEN</span>
        )}
    </div>
    {/* myPick / missing pick */}
</div>
```

---

## Behaviour Summary

| Scenario | Result |
|---|---|
| Load Matches on match day | Round chip = open round, page scrolled to today's DayBlock |
| Load Matches between rounds | Round chip = last open round (or first if none open) |
| Tournament in semis | "SEMIS + FINAL" chip auto-selected and scrolled into view |
| Group match footer | `GRUPO C · SoFi Stadium` |
| Knockout match footer | `M74 · AT&T Stadium` |
| Knockout decided in 90min | No badge — score tells the story |
| Knockout decided by ET/PEN | `ARG · ET/PEN` badge below match info |
| Unassigned knockout slot | teamA/B shows "Ganador E" / "3° mejor A/B/C/D/F" |

---

## Out of Scope

- POSICIONES tab: adding advancing markers / best-thirds highlighting — deferred
- `MatchCard` score bolding for winner — deferred (badge is sufficient)
