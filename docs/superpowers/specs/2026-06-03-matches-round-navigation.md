# Spec: Matches — Round Navigation + Knockout UI

**Date:** 2026-06-03  
**Status:** Approved

---

## Goal

Replace the per-day date chips in the Matches calendar view with 6 FIFA-round chips (GRUPOS / R32 / OCTAVOS / CUARTOS / SEMIS / FINAL). The 6 chips are **derived from `match_number` ranges** — no schema change required. The 4 DB rounds (`grupos`, `r32`, `f3`, `f4`) remain the prediction phases; the 6 FIFA rounds are view-only presentation logic. Auto-position to the current FIFA round on load. Improve knockout MatchCards to show match number in the footer and surface the ET/PEN winner when applicable.

---

## FIFA Round Map (derived, read-only)

| FIFA chip | Slug | Match numbers | Count |
|---|---|---|---|
| GRUPOS | `grupos` | M1–M72 | 72 |
| R32 | `r32` | M73–M88 | 16 |
| OCTAVOS | `octavos` | M89–M96 | 8 |
| CUARTOS | `cuartos` | M97–M100 | 4 |
| SEMIS | `semis` | M101–M102 | 2 |
| FINAL | `final` | M104 | 1 |

No M103 (no 3rd-place match seeded). Total: 103 fixtures.

---

## Files Changed

| File | Change |
|---|---|
| `app/Http/Controllers/MatchesController.php` | Add `fifaRounds` prop; add `fifaRound`, `matchNumber`, `wentToET`, `winner` to `formatFixture`; eager-load `round` relation |
| `resources/js/Pages/Matches.jsx` | Replace date chips with FIFA round chips; auto-scroll active chip + today's DayBlock; fix LiveScoreUpdated status |
| `resources/js/Components/composed/MatchCard.jsx` | Add `matchNumber`, `fifaRound`, `wentToET`, `winner` props; update footer logic; ET/PEN badge |

---

## 1. Backend — `MatchesController`

### 1a. `fifaRoundSlug()` helper

Private method — pure match_number derivation:

```php
private function fifaRoundSlug(int $n): string
{
    return match(true) {
        $n <= 72  => 'grupos',
        $n <= 88  => 'r32',
        $n <= 96  => 'octavos',
        $n <= 100 => 'cuartos',
        $n <= 102 => 'semis',
        default   => 'final',
    };
}
```

### 1b. New prop `fifaRounds`

Built in `index()` after loading fixtures. Derives the 6 virtual rounds from match distribution:

```php
$fifaRoundDefs = [
    'grupos'  => 'GRUPOS',
    'r32'     => 'R32',
    'octavos' => 'OCTAVOS',
    'cuartos' => 'CUARTOS',
    'semis'   => 'SEMIS',
    'final'   => 'FINAL',
];

$fifaRounds = collect($fifaRoundDefs)
    ->map(fn ($label, $slug) => [
        'slug'       => $slug,
        'label'      => $label,
        'matchCount' => $fixtures->filter(fn ($f) => $this->fifaRoundSlug($f->match_number) === $slug)->count(),
    ])
    ->filter(fn ($r) => $r['matchCount'] > 0)
    ->values();
```

### 1c. Default FIFA round

Determines which chip to auto-select on load. Logic: find the FIFA round that contains the earliest non-finished fixture; fall back to the last round.

```php
$defaultFifaRound = $fixtures
    ->filter(fn ($f) => $f->status !== 'finished')
    ->sortBy('match_number')
    ->first();

$defaultFifaRoundSlug = $defaultFifaRound
    ? $this->fifaRoundSlug($defaultFifaRound->match_number)
    : $this->fifaRoundSlug($fixtures->max('match_number'));
```

Passed to the frontend as part of the `fifaRounds` prop or as a separate `defaultFifaRound` prop.

### 1d. `formatFixture` additions

```php
'matchNumber' => $f->match_number,
'fifaRound'   => $this->fifaRoundSlug($f->match_number),
'wentToET'    => (bool) $f->went_to_extra_time,
'winner'      => $f->winnerTeam?->fifa_code,
```

`winner` is the FIFA code from `winner_team_id`. Non-null only when the match is finished and a winner was recorded (always in knockout; null for group draws).

`teamA` / `teamB` retain existing fallback — unassigned knockout slots auto-show "Ganador E", "3° mejor A/B/C/D/F", "Ganador M73", etc.:

```php
'teamA' => $f->homeTeam?->fifa_code ?? $f->home_placeholder ?? 'TBD',
```

### 1e. Eager-load `round` relation (removed — no longer needed)

`roundSlug` was replaced by `fifaRound` derived from `match_number`. No `->with('round')` needed.

---

## 2. Frontend — `Matches.jsx`

### 2a. Props

```js
export default function Matches({ matchDays: initialMatchDays, groups, fifaRounds, defaultFifaRound })
```

`currentRound` prop removed — no longer needed for chips.

### 2b. State

Remove `selectedDate`. Add `selectedFifaRound`:

```js
const [selectedFifaRound, setSelectedFifaRound] = useState(defaultFifaRound);
```

### 2c. `visibleDays` filtering

```js
const visibleDays = matchDays
    .map(day => ({
        ...day,
        matches: day.matches.filter(m => m.fifaRound === selectedFifaRound),
    }))
    .filter(day => day.matches.length > 0);
```

### 2d. `RoundChip` component (local, same file)

Replaces `DateChip`. Uses `forwardRef` so the active chip can receive `activeChipRef`:

```jsx
const RoundChip = forwardRef(function RoundChip({ label, count, active, onClick }, ref) {
    return (
        <button
            ref={ref}
            onClick={onClick}
            className={[
                'flex-shrink-0 px-3 py-1.5 border-[2.5px] border-ink text-center',
                active ? 'bg-pop-red text-white shadow-pop' : 'bg-white text-ink shadow-pop-sm',
            ].join(' ')}
        >
            <div className="font-display text-[13px] leading-none">{label}</div>
            <div className="font-mono text-[9px] font-bold opacity-80 mt-0.5 tracking-[.06em]">{count}P</div>
        </button>
    );
});
```

### 2e. Chip strip

```jsx
<div className="pt-3 pl-[14px]">
    <div className="flex gap-1.5 overflow-x-auto pr-[14px] pb-1">
        {fifaRounds.map(round => (
            <RoundChip
                key={round.slug}
                label={round.label}
                count={round.matchCount}
                active={selectedFifaRound === round.slug}
                ref={selectedFifaRound === round.slug ? activeChipRef : null}
                onClick={() => setSelectedFifaRound(round.slug)}
            />
        ))}
    </div>
</div>
```

### 2f. Auto-scroll — active chip

```js
const activeChipRef = useRef(null);
useEffect(() => {
    activeChipRef.current?.scrollIntoView({ inline: 'center', behavior: 'instant', block: 'nearest' });
}, [selectedFifaRound]);
```

Fires on mount and on chip change.

### 2g. Auto-scroll — today's DayBlock

`DayBlock` wrapped with `forwardRef`:

```js
const DayBlock = forwardRef(function DayBlock({ day }, ref) {
    return <div ref={ref} className="mb-3">...</div>;
});
```

In the render loop, pass `todayRef` to the block whose `dateKey === today`:

```jsx
{visibleDays.map(day => (
    <DayBlock
        key={day.dateKey}
        day={day}
        ref={day.dateKey === today ? todayRef : null}
    />
))}
```

Effect:

```js
const todayRef = useRef(null);
useEffect(() => {
    todayRef.current?.scrollIntoView({ behavior: 'instant', block: 'start' });
}, [selectedFifaRound]);
```

If no match today in the selected round, `todayRef.current` is null — nothing scrolls.

### 2h. Fix LiveScoreUpdated status

Use `e.status` (fixes same bug corrected in Home.jsx):

```js
channel.listen('.LiveScoreUpdated', (event) => {
    setMatchDays(prev => prev.map(day => ({
        ...day,
        matches: day.matches.map(m =>
            m.id === event.match_id
                ? { ...m, home_score: event.home_score, away_score: event.away_score, status: event.status }
                : m
        ),
    })));
});
```

### 2i. Header cleanup

Remove the `currentRound` badge from the header (no longer meaningful — the chips communicate this). Simplify header to just title + total match count.

---

## 3. `MatchCard.jsx`

### 3a. New props

```js
export default function MatchCard({
    // existing: status, time, teamA, teamB, flagUrlA, flagUrlB,
    //           scoreA, scoreB, minute, group, venue, myPick, myPts
    matchNumber, fifaRound, wentToET, winner,
})
```

### 3b. Footer — match info

```js
const matchInfo = fifaRound === 'grupos'
    ? `GRUPO ${group} · ${venue}`
    : `M${matchNumber} · ${venue}`;
```

### 3c. ET/PEN badge

Rendered inside the footer's left column, below `matchInfo`:

```jsx
<div className={['mt-2 pt-2 flex items-center justify-between gap-1.5 font-mono text-[9px] font-bold tracking-[.06em]',
    isLive ? 'border-t border-dashed border-cream/30' : 'border-t border-dashed border-black/20',
].join(' ')}>
    <div className="flex flex-col gap-0.5">
        <span className={isLive ? 'opacity-80' : 'opacity-65'}>{matchInfo}</span>
        {isFT && wentToET && winner && (
            <span className="inline-flex items-center gap-1 px-1.5 py-0.5 border-[1.5px] border-ink bg-pop-yel text-ink">
                {winner} · ET/PEN
            </span>
        )}
    </div>
    {/* myPick / FALTAN TUS GOLES — unchanged */}
</div>
```

---

## Behaviour Summary

| Scenario | Result |
|---|---|
| Load Matches mid-group-stage | GRUPOS chip selected, page scrolled to today's DayBlock |
| Load Matches in R32 | R32 chip selected and scrolled into view |
| Load after tournament ends | FINAL chip selected |
| Chip tap | Round filter updates, page scrolls to today's block (or top) |
| Group match footer | `GRUPO C · SoFi Stadium` |
| Knockout match footer | `M74 · AT&T Stadium` |
| Knockout decided in 90min | No badge — score tells the story |
| Knockout decided by ET/PEN | `ARG · ET/PEN` badge below match info |
| Unassigned knockout slot | teamA/B shows "Ganador E" / "3° mejor A/B/C/D/F" |

---

## Out of Scope

- POSICIONES tab: advancing markers / best-thirds highlighting — deferred
- `MatchCard` score bolding for winner — deferred (badge is sufficient)
- 3rd place match (M103 not in DB) — no action needed
