# Componentes Compuestos — Paso 4 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Portar 12 componentes compuestos presentacionales del handoff de diseño al stack React + Tailwind.

**Architecture:** Un archivo por componente en `Components/composed/`, todos puramente presentacionales (sin routing, sin formularios interactivos, sin lógica de backend). Los compuestos importan primitivos de `Components/ui/` e íconos de `Components/icons/`. Banderas de equipos via prop `flagUrl` (URL de `flagcdn.com`). Tests en `tests/composed/` con TDD.

**Tech Stack:** React 18, Tailwind CSS v3 (tokens custom: `pop-yel`, `pop-red`, `pop-teal`, `ink`, `navy`, `cream`; sombras: `shadow-pop`, `shadow-pop-sm`, `shadow-pop-md`; borde: `border-2.5`), Vitest + React Testing Library (ya instalados), Laravel Sail (pnpm).

**Spec:** `docs/superpowers/specs/2026-05-25-composites-design.md`

**Tests iniciales:** 68 tests pasando al inicio de este plan.

---

## File Map

| Archivo | Estado | Responsabilidad |
|---|---|---|
| `resources/js/Components/composed/ScoreBox.jsx` | Crear | Caja de marcador 30×34px, display-only |
| `resources/js/Components/composed/PtsBadge.jsx` | Crear | Pill puntos + ranking |
| `resources/js/Components/composed/TabBar.jsx` | Crear | Nav inferior 4 tabs, presentacional |
| `resources/js/Components/composed/StatCard.jsx` | Crear | Card de estadística con halftone corner |
| `resources/js/Components/composed/BetCard.jsx` | Crear | Cromo pequeño rotado, apuesta |
| `resources/js/Components/composed/MatchCard.jsx` | Crear | Tarjeta partido: live/ft/upcoming |
| `resources/js/Components/composed/MatchPredRow.jsx` | Crear | Fila predicción con ScoreBoxes |
| `resources/js/Components/composed/PodiumStep.jsx` | Crear | Escalón de podio con avatares apilados |
| `resources/js/Components/composed/RankRow.jsx` | Crear | Fila de ranking |
| `resources/js/Components/composed/PozoCard.jsx` | Crear | Card pozo total + premios |
| `resources/js/Components/composed/ChatBubble.jsx` | Crear | Burbuja de chat (propio / ajeno) |
| `resources/js/Components/composed/GroupStandingCard.jsx` | Crear | Tabla de posiciones de grupo |
| `resources/js/Components/composed/index.js` | Crear | Barrel export |
| `resources/js/tests/composed/ScoreBox.test.jsx` | Crear | Tests ScoreBox |
| `resources/js/tests/composed/PtsBadge.test.jsx` | Crear | Tests PtsBadge |
| `resources/js/tests/composed/TabBar.test.jsx` | Crear | Tests TabBar |
| `resources/js/tests/composed/StatCard.test.jsx` | Crear | Tests StatCard |
| `resources/js/tests/composed/BetCard.test.jsx` | Crear | Tests BetCard |
| `resources/js/tests/composed/MatchCard.test.jsx` | Crear | Tests MatchCard |
| `resources/js/tests/composed/MatchPredRow.test.jsx` | Crear | Tests MatchPredRow |
| `resources/js/tests/composed/PodiumStep.test.jsx` | Crear | Tests PodiumStep |
| `resources/js/tests/composed/RankRow.test.jsx` | Crear | Tests RankRow |
| `resources/js/tests/composed/PozoCard.test.jsx` | Crear | Tests PozoCard |
| `resources/js/tests/composed/ChatBubble.test.jsx` | Crear | Tests ChatBubble |
| `resources/js/tests/composed/GroupStandingCard.test.jsx` | Crear | Tests GroupStandingCard |

---

## Task 1: ScoreBox

**Files:**
- Create: `resources/js/Components/composed/ScoreBox.jsx`
- Create: `resources/js/tests/composed/ScoreBox.test.jsx`

- [ ] **Step 1.1: Crear test**

Crear `resources/js/tests/composed/ScoreBox.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import ScoreBox from '../../Components/composed/ScoreBox';

describe('ScoreBox', () => {
    it('renders value when provided', () => {
        const { getByText } = render(<ScoreBox value={2} filled />);
        expect(getByText('2')).toBeInTheDocument();
    });

    it('renders "—" when value is null', () => {
        const { getByText } = render(<ScoreBox value={null} />);
        expect(getByText('—')).toBeInTheDocument();
    });

    it('applies bg-pop-yel when filled', () => {
        const { container } = render(<ScoreBox value={1} filled />);
        expect(container.firstChild).toHaveClass('bg-pop-yel');
    });
});
```

- [ ] **Step 1.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/ScoreBox'`.

- [ ] **Step 1.3: Crear `ScoreBox.jsx`**

Crear `resources/js/Components/composed/ScoreBox.jsx`:

```jsx
export default function ScoreBox({ value, filled = false }) {
    return (
        <div
            className={[
                'w-[30px] h-[34px] border-2.5 border-ink shadow-pop-sm',
                'flex items-center justify-center font-display text-[18px]',
                filled ? 'bg-pop-yel text-ink' : 'bg-white text-black/25',
            ].join(' ')}
        >
            {value !== null && value !== undefined ? value : '—'}
        </div>
    );
}
```

- [ ] **Step 1.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `71 tests passed`.

- [ ] **Step 1.5: Commit**

```bash
git add resources/js/Components/composed/ScoreBox.jsx resources/js/tests/composed/ScoreBox.test.jsx
git commit -m "feat: add ScoreBox composed component"
```

---

## Task 2: PtsBadge

**Files:**
- Create: `resources/js/Components/composed/PtsBadge.jsx`
- Create: `resources/js/tests/composed/PtsBadge.test.jsx`

- [ ] **Step 2.1: Crear test**

Crear `resources/js/tests/composed/PtsBadge.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import PtsBadge from '../../Components/composed/PtsBadge';

describe('PtsBadge', () => {
    it('renders value', () => {
        const { getByText } = render(<PtsBadge value="124" rank="#12" />);
        expect(getByText('124')).toBeInTheDocument();
    });

    it('renders rank', () => {
        const { getByText } = render(<PtsBadge value="124" rank="#12" />);
        expect(getByText('· #12')).toBeInTheDocument();
    });
});
```

- [ ] **Step 2.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/PtsBadge'`.

- [ ] **Step 2.3: Crear `PtsBadge.jsx`**

Crear `resources/js/Components/composed/PtsBadge.jsx`:

```jsx
export default function PtsBadge({ value, rank }) {
    return (
        <div className="inline-flex items-center gap-1.5 py-1 pl-1.5 pr-2.5 bg-ink border-2 border-ink rounded-full shadow-pop-sm">
            <span className="w-5 h-5 rounded-full bg-pop-yel text-ink font-display text-[11px] flex items-center justify-center flex-shrink-0">
                P
            </span>
            <span className="font-display text-[13px] text-pop-yel">{value}</span>
            <span className="font-mono text-[10px] text-cream opacity-70">· {rank}</span>
        </div>
    );
}
```

- [ ] **Step 2.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `73 tests passed`.

- [ ] **Step 2.5: Commit**

```bash
git add resources/js/Components/composed/PtsBadge.jsx resources/js/tests/composed/PtsBadge.test.jsx
git commit -m "feat: add PtsBadge composed component"
```

---

## Task 3: TabBar

**Files:**
- Create: `resources/js/Components/composed/TabBar.jsx`
- Create: `resources/js/tests/composed/TabBar.test.jsx`

- [ ] **Step 3.1: Crear test**

Crear `resources/js/tests/composed/TabBar.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import TabBar from '../../Components/composed/TabBar';

describe('TabBar', () => {
    it('renders 4 tab buttons', () => {
        const { container } = render(<TabBar />);
        expect(container.querySelectorAll('button')).toHaveLength(4);
    });

    it('active tab has aria-current="page"', () => {
        const { container } = render(<TabBar active="matches" />);
        const buttons = container.querySelectorAll('button');
        const activeBtn = Array.from(buttons).find(b => b.getAttribute('aria-current') === 'page');
        expect(activeBtn).toHaveAttribute('aria-label', 'PARTIDOS');
    });

    it('inactive tabs do not have aria-current', () => {
        const { container } = render(<TabBar active="home" />);
        const buttons = container.querySelectorAll('button');
        const inactiveBtns = Array.from(buttons).filter(b => b.getAttribute('aria-label') !== 'PARCHE');
        inactiveBtns.forEach(b => expect(b).not.toHaveAttribute('aria-current'));
    });
});
```

- [ ] **Step 3.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/TabBar'`.

- [ ] **Step 3.3: Crear `TabBar.jsx`**

Crear `resources/js/Components/composed/TabBar.jsx`:

```jsx
import { NavStadium, NavVS, NavTrophy, NavFire } from '@/Components/icons/NavIcons';

const TABS = [
    { id: 'home',    label: 'PARCHE',   Icon: NavStadium },
    { id: 'matches', label: 'PARTIDOS', Icon: NavVS },
    { id: 'rank',    label: 'RANKING',  Icon: NavTrophy },
    { id: 'chat',    label: 'CHAT',     Icon: NavFire },
];

export default function TabBar({ active = 'home' }) {
    return (
        <nav className="fixed bottom-0 left-0 right-0 bg-cream border-t-[3px] border-ink px-3 pt-2.5 pb-[22px] flex justify-between gap-1.5">
            {TABS.map((tab) => {
                const isActive = tab.id === active;
                return (
                    <button
                        key={tab.id}
                        aria-label={tab.label}
                        aria-current={isActive ? 'page' : undefined}
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

- [ ] **Step 3.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `76 tests passed`.

- [ ] **Step 3.5: Commit**

```bash
git add resources/js/Components/composed/TabBar.jsx resources/js/tests/composed/TabBar.test.jsx
git commit -m "feat: add TabBar composed component"
```

---

## Task 4: StatCard

**Files:**
- Create: `resources/js/Components/composed/StatCard.jsx`
- Create: `resources/js/tests/composed/StatCard.test.jsx`

- [ ] **Step 4.1: Crear test**

Crear `resources/js/tests/composed/StatCard.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import StatCard from '../../Components/composed/StatCard';

describe('StatCard', () => {
    it('renders label', () => {
        const { getByText } = render(<StatCard label="POSICIÓN" value="#12" sub="/ 84" icon="trophy" />);
        expect(getByText('POSICIÓN')).toBeInTheDocument();
    });

    it('renders value', () => {
        const { getByText } = render(<StatCard label="POSICIÓN" value="#12" sub="/ 84" icon="trophy" />);
        expect(getByText('#12')).toBeInTheDocument();
    });

    it('renders svg icon', () => {
        const { container } = render(<StatCard label="EXACTOS" value="2" sub="+10 pts" icon="ball" />);
        expect(container.querySelector('svg')).toBeInTheDocument();
    });
});
```

- [ ] **Step 4.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/StatCard'`.

- [ ] **Step 4.3: Crear `StatCard.jsx`**

Crear `resources/js/Components/composed/StatCard.jsx`:

```jsx
import { Trophy, SoccerBall, Boot } from '@/Components/icons/football';

export default function StatCard({ label, value, sub, color = 'red', icon }) {
    const valueColor = color === 'yel' ? 'text-ink' : `text-pop-${color}`;

    return (
        <div className="border-2.5 border-ink bg-white p-2.5 text-center shadow-pop relative overflow-hidden">
            <div
                className={`halftone halftone-${color} absolute top-0 right-0 w-[30px] h-[30px]`}
                style={{
                    WebkitMaskImage: 'radial-gradient(circle at 100% 0%, #000, transparent 70%)',
                    maskImage: 'radial-gradient(circle at 100% 0%, #000, transparent 70%)',
                }}
            />
            <div className="absolute top-1 left-1 opacity-90">
                {icon === 'trophy' && <Trophy size={16} color={`var(--c-${color})`} />}
                {icon === 'ball'   && <SoccerBall size={16} />}
                {icon === 'boot'   && <Boot size={14} color={`var(--c-${color})`} />}
            </div>
            <div className="font-mono text-[9px] tracking-[.1em] opacity-80 mt-3.5">{label}</div>
            <div className={`font-display text-[22px] mt-0.5 ${valueColor}`}>{value}</div>
            <div className="font-mono text-[10px] opacity-60">{sub}</div>
        </div>
    );
}
```

- [ ] **Step 4.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `79 tests passed`.

- [ ] **Step 4.5: Commit**

```bash
git add resources/js/Components/composed/StatCard.jsx resources/js/tests/composed/StatCard.test.jsx
git commit -m "feat: add StatCard composed component"
```

---

## Task 5: BetCard

**Files:**
- Create: `resources/js/Components/composed/BetCard.jsx`
- Create: `resources/js/tests/composed/BetCard.test.jsx`

- [ ] **Step 5.1: Crear test**

Crear `resources/js/tests/composed/BetCard.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import BetCard from '../../Components/composed/BetCard';

const baseProps = {
    teamA: 'ARG', teamB: 'ALE',
    flagUrlA: 'https://flagcdn.com/w80/ar.png',
    flagUrlB: 'https://flagcdn.com/w80/de.png',
    pick: '2-1', pts: '+10', time: 'EN 2H',
};

describe('BetCard', () => {
    it('renders pick', () => {
        const { getByText } = render(<BetCard {...baseProps} />);
        expect(getByText('2-1')).toBeInTheDocument();
    });

    it('renders team names', () => {
        const { getByText } = render(<BetCard {...baseProps} />);
        expect(getByText('ARG vs ALE')).toBeInTheDocument();
    });

    it('shows corner "¡EN VIVO!" when hot=true', () => {
        const { getByText } = render(<BetCard {...baseProps} hot />);
        expect(getByText('¡EN VIVO!')).toBeInTheDocument();
    });

    it('does not show corner when hot=false', () => {
        const { queryByText } = render(<BetCard {...baseProps} />);
        expect(queryByText('¡EN VIVO!')).not.toBeInTheDocument();
    });
});
```

- [ ] **Step 5.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/BetCard'`.

- [ ] **Step 5.3: Crear `BetCard.jsx`**

Crear `resources/js/Components/composed/BetCard.jsx`:

```jsx
import Cromo from '@/Components/ui/Cromo';

export default function BetCard({ teamA, teamB, flagUrlA, flagUrlB, pick, pts, time, hot = false }) {
    return (
        <div style={{ minWidth: 158, transform: `rotate(${hot ? -2 : 1}deg)`, flexShrink: 0 }}>
            <Cromo corner={hot ? '¡EN VIVO!' : undefined} className="p-2.5">
                <div className="flex justify-between items-center gap-2">
                    <img src={flagUrlA} alt={teamA} className="h-4 w-6 object-cover border border-ink" />
                    <span className="font-display text-[16px] text-pop-red">{pick}</span>
                    <img src={flagUrlB} alt={teamB} className="h-4 w-6 object-cover border border-ink" />
                </div>
                <div className="flex justify-between mt-2 font-mono text-[10px] font-bold tracking-[.06em]">
                    <span>{teamA} vs {teamB}</span>
                    <span>{time}</span>
                </div>
                <div className="mt-1.5 pt-1.5 border-t border-dashed border-ink flex justify-between items-center font-mono text-[10px]">
                    <span className="opacity-70 tracking-[.06em]">POSIBLES</span>
                    <b className="font-display text-[12px] text-pop-red">{pts} PTS</b>
                </div>
            </Cromo>
        </div>
    );
}
```

- [ ] **Step 5.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `83 tests passed`.

- [ ] **Step 5.5: Commit**

```bash
git add resources/js/Components/composed/BetCard.jsx resources/js/tests/composed/BetCard.test.jsx
git commit -m "feat: add BetCard composed component"
```

---

## Task 6: MatchCard

**Files:**
- Create: `resources/js/Components/composed/MatchCard.jsx`
- Create: `resources/js/tests/composed/MatchCard.test.jsx`

- [ ] **Step 6.1: Crear test**

Crear `resources/js/tests/composed/MatchCard.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import MatchCard from '../../Components/composed/MatchCard';

const baseProps = {
    teamA: 'COL', teamB: 'BRA',
    flagUrlA: 'https://flagcdn.com/w80/co.png',
    flagUrlB: 'https://flagcdn.com/w80/br.png',
    group: 'D', venue: 'MIAMI',
    time: '13:00',
};

describe('MatchCard', () => {
    it('renders teamA name', () => {
        const { getAllByText } = render(<MatchCard {...baseProps} status="upcoming" />);
        expect(getAllByText('COL')[0]).toBeInTheDocument();
    });

    it('live: muestra minuto', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="live" scoreA={1} scoreB={0} minute="43'" />);
        expect(getByText("43'")).toBeInTheDocument();
    });

    it('ft: muestra FT', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="ft" scoreA={2} scoreB={1} />);
        expect(getByText('FT')).toBeInTheDocument();
    });

    it('upcoming: muestra VS', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="upcoming" />);
        expect(getByText('VS')).toBeInTheDocument();
    });

    it('con myPick: muestra pick en footer', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="upcoming" myPick="2-1" />);
        expect(getByText(/TUS GOLES: 2-1/)).toBeInTheDocument();
    });

    it('sin myPick: muestra FALTAN TUS GOLES', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="upcoming" />);
        expect(getByText(/FALTAN TUS GOLES/)).toBeInTheDocument();
    });
});
```

- [ ] **Step 6.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/MatchCard'`.

- [ ] **Step 6.3: Crear `MatchCard.jsx`**

Crear `resources/js/Components/composed/MatchCard.jsx`:

```jsx
export default function MatchCard({
    status,
    time,
    teamA, teamB,
    flagUrlA, flagUrlB,
    scoreA, scoreB,
    minute,
    group, venue,
    myPick, myPts,
}) {
    const isLive = status === 'live';
    const isFT   = status === 'ft';
    const isUp   = status === 'upcoming';

    return (
        <div className={[
            'border-2.5 border-ink shadow-pop p-[10px_12px] relative overflow-hidden',
            isLive ? 'bg-navy text-cream' : 'bg-white text-ink',
        ].join(' ')}>
            {isLive && (
                <div className="halftone halftone-red absolute inset-0 opacity-15 pointer-events-none" />
            )}

            <div className="flex items-center gap-2 relative">
                {/* Status indicator */}
                <div className="w-[52px] text-center flex-shrink-0">
                    {isLive && (
                        <div>
                            <div className="font-display text-[11px] text-pop-red flex items-center gap-1 justify-center">
                                <span className="w-1.5 h-1.5 rounded-full bg-pop-red animate-pulse" />
                                LIVE
                            </div>
                            <div className="font-display text-[13px] text-pop-yel mt-0.5">{minute}</div>
                        </div>
                    )}
                    {isFT && (
                        <div>
                            <div className="font-display text-[13px] text-pop-teal">FT</div>
                            <div className="font-mono text-[9px] opacity-55 mt-0.5">{time}</div>
                        </div>
                    )}
                    {isUp && (
                        <div className="font-display text-[13px]">{time}</div>
                    )}
                </div>

                {/* Teams + score */}
                <div className="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-1.5">
                    <div className="flex items-center gap-1.5 justify-end">
                        <span className="font-display text-[13px]">{teamA}</span>
                        <img src={flagUrlA} alt={teamA} className="h-4 w-6 object-cover border border-ink" />
                    </div>
                    <div className="flex items-center gap-1 px-1">
                        {isUp ? (
                            <span className="font-display text-[14px] opacity-50">VS</span>
                        ) : (
                            <>
                                <span className={`font-display text-[20px] ${isLive ? 'text-pop-yel' : 'text-ink'}`}>{scoreA}</span>
                                <span className="opacity-50 mx-0.5">—</span>
                                <span className={`font-display text-[20px] ${isLive ? 'text-cream' : 'text-ink'}`}>{scoreB}</span>
                            </>
                        )}
                    </div>
                    <div className="flex items-center gap-1.5">
                        <img src={flagUrlB} alt={teamB} className="h-4 w-6 object-cover border border-ink" />
                        <span className="font-display text-[13px]">{teamB}</span>
                    </div>
                </div>
            </div>

            {/* Footer */}
            <div className={[
                'mt-2 pt-2 flex items-center justify-between gap-1.5',
                'font-mono text-[9px] font-bold tracking-[.06em]',
                isLive
                    ? 'border-t border-dashed border-cream/30'
                    : 'border-t border-dashed border-black/20',
            ].join(' ')}>
                <span className={isLive ? 'opacity-80' : 'opacity-65'}>
                    GRUPO {group} · {venue}
                </span>
                {myPick ? (
                    <span className={[
                        'inline-flex items-center gap-1 px-1.5 py-0.5 border-[1.5px] border-ink',
                        isFT && myPts != null
                            ? 'bg-pop-teal text-white'
                            : 'bg-pop-yel text-ink',
                    ].join(' ')}>
                        TUS GOLES: {myPick}{isFT && myPts != null ? ` · +${myPts} PTS` : ''}
                    </span>
                ) : (
                    <span className={[
                        'px-1.5 py-0.5 border-[1.5px] border-dashed',
                        isLive
                            ? 'border-cream/60 text-cream'
                            : 'border-pop-red text-pop-red',
                    ].join(' ')}>
                        ! FALTAN TUS GOLES
                    </span>
                )}
            </div>
        </div>
    );
}
```

- [ ] **Step 6.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `89 tests passed`.

- [ ] **Step 6.5: Commit**

```bash
git add resources/js/Components/composed/MatchCard.jsx resources/js/tests/composed/MatchCard.test.jsx
git commit -m "feat: add MatchCard composed component (live/ft/upcoming)"
```

---

## Task 7: MatchPredRow

**Files:**
- Create: `resources/js/Components/composed/MatchPredRow.jsx`
- Create: `resources/js/tests/composed/MatchPredRow.test.jsx`

- [ ] **Step 7.1: Crear test**

Crear `resources/js/tests/composed/MatchPredRow.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import MatchPredRow from '../../Components/composed/MatchPredRow';

const baseProps = {
    date: '11 JUN · 19:00', venue: 'AZTECA',
    teamHome: 'MEX', teamAway: 'KOR',
    flagUrlHome: 'https://flagcdn.com/w80/mx.png',
    flagUrlAway: 'https://flagcdn.com/w80/kr.png',
    scoreHome: 2, scoreAway: 1,
    status: 'ok',
};

describe('MatchPredRow', () => {
    it('renders teamHome name', () => {
        const { getByText } = render(<MatchPredRow {...baseProps} />);
        expect(getByText('MEX')).toBeInTheDocument();
    });

    it('status ok → muestra GUARDADO', () => {
        const { getByText } = render(<MatchPredRow {...baseProps} status="ok" />);
        expect(getByText(/GUARDADO/)).toBeInTheDocument();
    });

    it('status empty → muestra FALTAN TUS GOLES', () => {
        const { getByText } = render(
            <MatchPredRow {...baseProps} status="empty" scoreHome={null} scoreAway={null} />
        );
        expect(getByText(/FALTAN TUS GOLES/)).toBeInTheDocument();
    });

    it('ScoreBox filled cuando status=ok', () => {
        const { container } = render(<MatchPredRow {...baseProps} status="ok" />);
        const scoreBoxes = container.querySelectorAll('.bg-pop-yel');
        expect(scoreBoxes.length).toBeGreaterThan(0);
    });
});
```

- [ ] **Step 7.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/MatchPredRow'`.

- [ ] **Step 7.3: Crear `MatchPredRow.jsx`**

Crear `resources/js/Components/composed/MatchPredRow.jsx`:

```jsx
import ScoreBox from './ScoreBox';

export default function MatchPredRow({
    date, venue,
    teamHome, teamAway,
    flagUrlHome, flagUrlAway,
    scoreHome, scoreAway,
    status,
    last = false,
}) {
    const filled = status === 'ok';

    return (
        <div className={[
            'px-2.5 py-2 relative',
            !last ? 'border-b border-dashed border-black/20' : '',
        ].join(' ')}>
            <div className="font-mono text-[8.5px] tracking-[.08em] opacity-55 mb-1">
                {date} · {venue}
            </div>
            <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                <div className="flex items-center justify-end gap-1.5">
                    <span className="font-display text-[12px]">{teamHome}</span>
                    <img src={flagUrlHome} alt={teamHome} className="h-4 w-6 object-cover border border-ink" />
                </div>
                <div className="flex items-center gap-0.5">
                    <ScoreBox value={scoreHome} filled={filled} />
                    <span className="font-display text-[13px] opacity-55 mx-0.5">—</span>
                    <ScoreBox value={scoreAway} filled={filled} />
                </div>
                <div className="flex items-center gap-1.5">
                    <img src={flagUrlAway} alt={teamAway} className="h-4 w-6 object-cover border border-ink" />
                    <span className="font-display text-[12px]">{teamAway}</span>
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
```

- [ ] **Step 7.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `93 tests passed`.

- [ ] **Step 7.5: Commit**

```bash
git add resources/js/Components/composed/MatchPredRow.jsx resources/js/tests/composed/MatchPredRow.test.jsx
git commit -m "feat: add MatchPredRow composed component"
```

---

## Task 8: PodiumStep

**Files:**
- Create: `resources/js/Components/composed/PodiumStep.jsx`
- Create: `resources/js/tests/composed/PodiumStep.test.jsx`

- [ ] **Step 8.1: Crear test**

Crear `resources/js/tests/composed/PodiumStep.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import PodiumStep from '../../Components/composed/PodiumStep';

const singleUser = [{ name: 'LUCHO M.', color: 'var(--c-yel)' }];
const tiedUsers  = [
    { name: 'LUCHO M.', color: 'var(--c-yel)' },
    { name: 'BRENDA',   color: 'var(--c-teal)' },
    { name: 'PEPE B.',  color: 'var(--c-red)' },
];
const manyUsers = [...tiedUsers,
    { name: 'EXTRA1', color: 'var(--c-cream)' },
    { name: 'EXTRA2', color: 'var(--c-navy)' },
];

describe('PodiumStep', () => {
    it('renders pts', () => {
        const { getByText } = render(<PodiumStep place={1} pts="48" tied={singleUser} />);
        expect(getByText('48 pts')).toBeInTheDocument();
    });

    it('place=1 con tied.length=3: muestra chip empate', () => {
        const { getByText } = render(<PodiumStep place={1} pts="48" tied={tiedUsers} />);
        expect(getByText('3 EMPATAN')).toBeInTheDocument();
    });

    it('place=1 con tied.length=1: no muestra chip empate', () => {
        const { queryByText } = render(<PodiumStep place={1} pts="48" tied={singleUser} />);
        expect(queryByText(/EMPATAN/)).not.toBeInTheDocument();
    });

    it('tied.length=5: muestra "+2" pill', () => {
        const { getByText } = render(<PodiumStep place={1} pts="48" tied={manyUsers} />);
        expect(getByText('+2')).toBeInTheDocument();
    });
});
```

- [ ] **Step 8.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/PodiumStep'`.

- [ ] **Step 8.3: Crear `PodiumStep.jsx`**

Crear `resources/js/Components/composed/PodiumStep.jsx`:

```jsx
import { Trophy, SoccerBall } from '@/Components/icons/football';

const STEP = {
    1: { bg: 'bg-pop-yel', textColor: 'text-ink',   h: 108 },
    2: { bg: 'bg-cream',   textColor: 'text-ink',   h: 80 },
    3: { bg: 'bg-pop-red', textColor: 'text-white',  h: 64 },
};

export default function PodiumStep({ place, pts, tied, crown = false }) {
    const isTie = tied.length > 1;
    const { bg, textColor, h } = STEP[place];

    return (
        <div className="flex-1 text-center flex flex-col items-center relative">
            {crown && (
                <div className="mb-0.5">
                    <Trophy size={26} color="var(--c-yel)" />
                </div>
            )}

            {/* Avatar stack */}
            <div className="relative mb-1 h-[50px] flex items-center justify-center">
                {tied.slice(0, 3).map((u, i) => (
                    <div
                        key={i}
                        className="w-11 h-11 rounded-full border-2.5 border-ink shadow-pop-sm font-display text-[16px] text-ink flex items-center justify-center flex-shrink-0"
                        style={{ background: u.color, marginLeft: i > 0 ? -16 : 0, zIndex: 3 - i }}
                    >
                        {u.name[0]}
                    </div>
                ))}
                {tied.length > 3 && (
                    <div
                        className="w-7 h-7 rounded-full bg-ink text-pop-yel border-2.5 border-ink font-mono text-[10px] font-bold flex items-center justify-center flex-shrink-0"
                        style={{ marginLeft: -10, zIndex: 0 }}
                    >
                        +{tied.length - 3}
                    </div>
                )}
            </div>

            {isTie && (
                <div className="inline-block font-mono text-[9px] font-bold tracking-[.08em] bg-pop-red text-white px-1.5 py-0.5 border-[1.5px] border-ink mb-0.5">
                    {tied.length} EMPATAN
                </div>
            )}

            <div className="font-display text-[10px] mt-0.5 min-h-[12px]">
                {isTie ? '···' : tied[0].name}
            </div>
            <div className="font-mono text-[10px] opacity-80 font-bold mt-0.5">{pts} pts</div>

            {/* Step block */}
            <div
                className={[
                    'mt-1 w-full border-2.5 border-ink shadow-pop',
                    'flex items-start justify-center pt-2',
                    'font-display text-[28px] relative overflow-hidden',
                    bg, textColor,
                ].join(' ')}
                style={{ height: h }}
            >
                {place}°
                <div className="halftone absolute inset-0 opacity-[.12]" />
                {place === 1 && (
                    <div className="absolute bottom-1.5 left-1/2 -translate-x-1/2 opacity-35">
                        <SoccerBall size={32} />
                    </div>
                )}
            </div>
        </div>
    );
}
```

- [ ] **Step 8.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `97 tests passed`.

- [ ] **Step 8.5: Commit**

```bash
git add resources/js/Components/composed/PodiumStep.jsx resources/js/tests/composed/PodiumStep.test.jsx
git commit -m "feat: add PodiumStep composed component"
```

---

## Task 9: RankRow

**Files:**
- Create: `resources/js/Components/composed/RankRow.jsx`
- Create: `resources/js/tests/composed/RankRow.test.jsx`

- [ ] **Step 9.1: Crear test**

Crear `resources/js/tests/composed/RankRow.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import RankRow from '../../Components/composed/RankRow';

describe('RankRow', () => {
    it('renders name', () => {
        const { getByText } = render(
            <RankRow position={4} name="LUCHO M." pts="31" delta="+3" />
        );
        expect(getByText('LUCHO M.')).toBeInTheDocument();
    });

    it('renders pts', () => {
        const { getByText } = render(
            <RankRow position={4} name="LUCHO M." pts="31" delta="+3" />
        );
        expect(getByText('31')).toBeInTheDocument();
    });

    it('isYou=true aplica bg-pop-yel', () => {
        const { container } = render(
            <RankRow position={12} name="JHON M." pts="12" delta="+2" isYou />
        );
        expect(container.firstChild).toHaveClass('bg-pop-yel');
    });

    it('delta "+3" → chip tiene clase bg-pop-teal', () => {
        const { container } = render(
            <RankRow position={4} name="LUCHO M." pts="31" delta="+3" />
        );
        const chip = container.querySelector('.bg-pop-teal');
        expect(chip).toBeInTheDocument();
    });
});
```

- [ ] **Step 9.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/RankRow'`.

- [ ] **Step 9.3: Crear `RankRow.jsx`**

Crear `resources/js/Components/composed/RankRow.jsx`:

```jsx
export default function RankRow({ position, name, pts, delta, isYou = false, tiedCount }) {
    const isPositive = delta.startsWith('+');
    const absVal = delta.replace(/[+-]/, '');

    return (
        <div className={[
            'flex items-center gap-2.5 px-2.5 py-2 border-2.5 border-ink shadow-pop relative',
            isYou ? 'bg-pop-yel' : 'bg-white',
        ].join(' ')}>
            <div className="w-9 text-center flex-shrink-0">
                <div className={`font-display text-[16px] leading-none ${isYou ? 'text-pop-red' : 'text-ink'}`}>
                    {position}°
                </div>
                {tiedCount && (
                    <div className="font-mono text-[7px] font-bold text-pop-red tracking-[.06em] mt-0.5">
                        ={tiedCount}
                    </div>
                )}
            </div>
            <div className="w-7 h-7 rounded-full bg-pop-teal text-white border-2 border-ink font-display text-[12px] flex items-center justify-center flex-shrink-0">
                {name[0]}
            </div>
            <div className="flex-1 min-w-0">
                <div className="font-display text-[13px] leading-none">{name}</div>
            </div>
            <div className="text-right flex-shrink-0">
                <div className="font-display text-[16px] leading-none">{pts}</div>
                <div className="font-mono text-[8px] opacity-70 tracking-[.05em]">PUNTOS</div>
            </div>
            <div className={[
                'font-mono font-bold text-[10px] px-1.5 py-0.5 border-[1.5px] border-ink min-w-[32px] text-center flex-shrink-0',
                isPositive ? 'bg-pop-teal text-white' : 'bg-pop-red text-white',
            ].join(' ')}>
                {isPositive ? '▲' : '▼'}{absVal}
            </div>
        </div>
    );
}
```

- [ ] **Step 9.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `101 tests passed`.

- [ ] **Step 9.5: Commit**

```bash
git add resources/js/Components/composed/RankRow.jsx resources/js/tests/composed/RankRow.test.jsx
git commit -m "feat: add RankRow composed component"
```

---

## Task 10: PozoCard

**Files:**
- Create: `resources/js/Components/composed/PozoCard.jsx`
- Create: `resources/js/tests/composed/PozoCard.test.jsx`

- [ ] **Step 10.1: Crear test**

Crear `resources/js/tests/composed/PozoCard.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import PozoCard from '../../Components/composed/PozoCard';

const baseProps = {
    total: '4.200K',
    players: 84,
    amountPerPlayer: '50K',
    prize1: '2.940K',
    prize2: '1.260K',
};

describe('PozoCard', () => {
    it('renders total', () => {
        const { getByText } = render(<PozoCard {...baseProps} />);
        expect(getByText('4.200K')).toBeInTheDocument();
    });

    it('renders prize1', () => {
        const { getByText } = render(<PozoCard {...baseProps} />);
        expect(getByText('2.940K')).toBeInTheDocument();
    });

    it('renders prize2', () => {
        const { getByText } = render(<PozoCard {...baseProps} />);
        expect(getByText('1.260K')).toBeInTheDocument();
    });
});
```

- [ ] **Step 10.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/PozoCard'`.

- [ ] **Step 10.3: Crear `PozoCard.jsx`**

Crear `resources/js/Components/composed/PozoCard.jsx`:

```jsx
import Cromo from '@/Components/ui/Cromo';
import { Trophy } from '@/Components/icons/football';

function PrizeSlot({ place, pct, amount, color }) {
    return (
        <div className="bg-black/35 border-2 border-ink p-[6px_8px]">
            <div className="flex items-center gap-1.5">
                <span className="font-display text-[18px]" style={{ color }}>{place}</span>
                <span className="font-mono text-[9px] opacity-70 tracking-[.08em]">{pct}</span>
            </div>
            <div className="font-mono font-bold text-[14px] mt-0.5" style={{ color }}>{amount}</div>
        </div>
    );
}

export default function PozoCard({ total, players, amountPerPlayer, prize1, prize2 }) {
    return (
        <Cromo className="bg-navy text-cream p-[10px_12px]">
            <div className="halftone halftone-yel absolute inset-0 opacity-35" />
            <div className="absolute right-[-6px] bottom-[-10px] -rotate-[8deg] opacity-95">
                <Trophy size={62} color="var(--c-yel)" />
            </div>
            <div className="relative">
                <div className="font-mono text-[9px] text-pop-yel tracking-[.12em]">POZO TOTAL</div>
                <div className="font-display text-[30px] leading-none mt-0.5 text-pop-yel">{total}</div>
                <div className="font-mono text-[10px] opacity-75 mt-0.5">
                    {players} jugadores · {amountPerPlayer} c/u
                </div>
            </div>
            <div className="grid grid-cols-2 gap-1.5 mt-2.5 relative">
                <PrizeSlot place="1°" pct="70%" amount={prize1} color="var(--c-yel)" />
                <PrizeSlot place="2°" pct="30%" amount={prize2} color="var(--c-cream)" />
            </div>
        </Cromo>
    );
}
```

- [ ] **Step 10.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `104 tests passed`.

- [ ] **Step 10.5: Commit**

```bash
git add resources/js/Components/composed/PozoCard.jsx resources/js/tests/composed/PozoCard.test.jsx
git commit -m "feat: add PozoCard composed component"
```

---

## Task 11: ChatBubble

**Files:**
- Create: `resources/js/Components/composed/ChatBubble.jsx`
- Create: `resources/js/tests/composed/ChatBubble.test.jsx`

- [ ] **Step 11.1: Crear test**

Crear `resources/js/tests/composed/ChatBubble.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import ChatBubble from '../../Components/composed/ChatBubble';

const baseProps = {
    name: 'LUCHO',
    color: 'var(--c-teal)',
    text: '¡Eh ave maría!',
    time: '18:42',
};

describe('ChatBubble', () => {
    it('renders text', () => {
        const { getByText } = render(<ChatBubble {...baseProps} />);
        expect(getByText('¡Eh ave maría!')).toBeInTheDocument();
    });

    it('isMe=false → avatar visible con inicial', () => {
        const { getByText } = render(<ChatBubble {...baseProps} isMe={false} />);
        expect(getByText('L')).toBeInTheDocument();
    });

    it('isMe=true → no hay avatar con inicial del nombre', () => {
        const { queryByText } = render(<ChatBubble {...baseProps} isMe />);
        // No debe haber elemento con solo la inicial visible como avatar
        const avatarEl = queryByText('L');
        expect(avatarEl).not.toBeInTheDocument();
    });

    it('pinned=true → badge FIJO visible', () => {
        const { getByText } = render(<ChatBubble {...baseProps} pinned />);
        expect(getByText('FIJO')).toBeInTheDocument();
    });

    it('sticker → Burst visible con texto del sticker', () => {
        const { getByText } = render(<ChatBubble {...baseProps} sticker="BERRACO!" />);
        expect(getByText('BERRACO!')).toBeInTheDocument();
    });
});
```

- [ ] **Step 11.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/ChatBubble'`.

- [ ] **Step 11.3: Crear `ChatBubble.jsx`**

Crear `resources/js/Components/composed/ChatBubble.jsx`:

```jsx
import { Burst } from '@/Components/ui';

export default function ChatBubble({ name, color, text, time, isMe = false, pinned = false, sticker }) {
    return (
        <div className={`flex gap-2 items-end ${isMe ? 'flex-row-reverse' : 'flex-row'}`}>
            {!isMe && (
                <div
                    className="w-8 h-8 rounded-full border-2 border-ink font-display text-[12px] text-white flex items-center justify-center flex-shrink-0"
                    style={{ background: color }}
                >
                    {name[0]}
                </div>
            )}
            <div className={`max-w-[78%] flex flex-col ${isMe ? 'items-end' : 'items-start'}`}>
                {!isMe && (
                    <div className="flex items-center gap-1.5 mb-0.5">
                        <span className="font-display text-[10px]" style={{ color }}>{name}</span>
                        <span className="font-mono text-[9px] opacity-55">{time}</span>
                    </div>
                )}
                <div className={[
                    'border-2.5 border-ink p-[8px_12px] rounded-[4px] text-[14px] leading-snug relative',
                    isMe
                        ? 'bg-pop-yel shadow-[-3px_3px_0_var(--c-ink)]'
                        : 'bg-white shadow-[3px_3px_0_var(--c-ink)]',
                ].join(' ')}>
                    {pinned && (
                        <div className="absolute -top-2 -right-2 bg-pop-red text-white border-2 border-ink px-1.5 font-display text-[9px] rotate-[6deg]">
                            FIJO
                        </div>
                    )}
                    {text}
                </div>
                {sticker && (
                    <div className="mt-1 -rotate-[4deg]">
                        <Burst color="var(--c-red)" size="sm" rotate={0}>
                            {sticker}
                        </Burst>
                    </div>
                )}
                {isMe && (
                    <span className="font-mono text-[9px] opacity-55 mt-0.5">{time} · enviado</span>
                )}
            </div>
        </div>
    );
}
```

- [ ] **Step 11.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `109 tests passed`.

- [ ] **Step 11.5: Commit**

```bash
git add resources/js/Components/composed/ChatBubble.jsx resources/js/tests/composed/ChatBubble.test.jsx
git commit -m "feat: add ChatBubble composed component"
```

---

## Task 12: GroupStandingCard

**Files:**
- Create: `resources/js/Components/composed/GroupStandingCard.jsx`
- Create: `resources/js/tests/composed/GroupStandingCard.test.jsx`

- [ ] **Step 12.1: Crear test**

Crear `resources/js/tests/composed/GroupStandingCard.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import GroupStandingCard from '../../Components/composed/GroupStandingCard';

const teams = [
    { flagUrl: 'https://flagcdn.com/w80/mx.png', name: 'MÉXICO',    pj: 1, g: 1, e: 0, p: 0, gf: 2, gc: 1, pts: 3 },
    { flagUrl: 'https://flagcdn.com/w80/kr.png', name: 'COREA',     pj: 1, g: 0, e: 0, p: 1, gf: 1, gc: 2, pts: 0 },
    { flagUrl: 'https://flagcdn.com/w80/cr.png', name: 'C.RICA',    pj: 0, g: 0, e: 0, p: 0, gf: 0, gc: 0, pts: 0 },
    { flagUrl: 'https://flagcdn.com/w80/ma.png', name: 'MARRUECOS', pj: 0, g: 0, e: 0, p: 0, gf: 0, gc: 0, pts: 0 },
];

describe('GroupStandingCard', () => {
    it('renders group name', () => {
        const { getByText } = render(<GroupStandingCard group="A" played="1 / 6 JUGADOS" teams={teams} />);
        expect(getByText('GRUPO A')).toBeInTheDocument();
    });

    it('renders first team name', () => {
        const { getByText } = render(<GroupStandingCard group="A" played="1 / 6 JUGADOS" teams={teams} />);
        expect(getByText('MÉXICO')).toBeInTheDocument();
    });

    it('top 2 muestran flecha ↑', () => {
        const { getAllByText } = render(<GroupStandingCard group="A" played="1 / 6 JUGADOS" teams={teams} />);
        expect(getAllByText('↑')).toHaveLength(2);
    });

    it('equipo con live=true muestra chip LIVE', () => {
        const teamsWithLive = teams.map((t, i) => i === 0 ? { ...t, live: true } : t);
        const { getByText } = render(<GroupStandingCard group="A" played="1 / 6 JUGADOS" teams={teamsWithLive} />);
        expect(getByText('LIVE')).toBeInTheDocument();
    });
});
```

- [ ] **Step 12.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/composed/GroupStandingCard'`.

- [ ] **Step 12.3: Crear `GroupStandingCard.jsx`**

Crear `resources/js/Components/composed/GroupStandingCard.jsx`:

```jsx
export default function GroupStandingCard({ group, played, teams }) {
    return (
        <div className="border-2.5 border-ink shadow-pop-md bg-white relative overflow-hidden">
            {/* Header */}
            <div className="flex justify-between items-center px-3 py-1.5 bg-pop-red text-white border-b-[2.5px] border-ink">
                <div className="font-display text-[14px]">GRUPO {group}</div>
                <div className="font-mono text-[9px] tracking-[.06em] opacity-90">{played}</div>
            </div>

            {/* Column headers */}
            <div className="grid grid-cols-[20px_1fr_24px_50px_28px_28px] gap-1 px-2.5 py-1.5 font-mono text-[9px] font-bold tracking-[.06em] opacity-55 border-b border-dashed border-black/20">
                <span>#</span>
                <span>EQUIPO</span>
                <span className="text-center">PJ</span>
                <span className="text-center">G-E-P</span>
                <span className="text-center">GD</span>
                <span className="text-right">PTS</span>
            </div>

            {/* Teams */}
            {teams.map((t, i) => {
                const gd = t.gf - t.gc;
                const isTop  = i < 2;
                const isLast = i === teams.length - 1;
                return (
                    <div
                        key={i}
                        className={[
                            'grid grid-cols-[20px_1fr_24px_50px_28px_28px] gap-1 px-2.5 py-2 items-center',
                            !isLast ? 'border-b border-dashed border-black/10' : '',
                        ].join(' ')}
                        style={isTop ? { background: 'rgba(255,210,63,.18)' } : {}}
                    >
                        <span className="font-mono font-bold text-[11px] opacity-70">
                            {i + 1}°{isTop && <span className="ml-0.5 text-[8px] text-pop-teal">↑</span>}
                        </span>
                        <div className="flex items-center gap-1.5">
                            <img src={t.flagUrl} alt={t.name} className="h-3 w-[18px] object-cover" />
                            <span className="font-display text-[11px] tracking-[.02em]">{t.name}</span>
                            {t.live && (
                                <span className="font-mono text-[7px] font-bold text-pop-red tracking-[.06em] px-0.5 border border-pop-red leading-tight">
                                    LIVE
                                </span>
                            )}
                        </div>
                        <span className="font-mono font-bold text-[11px] text-center">{t.pj}</span>
                        <span className="font-mono text-[10px] text-center tracking-[.04em]">
                            {t.g}-{t.e}-{t.p}
                        </span>
                        <span className={[
                            'font-mono text-[10px] text-center font-bold',
                            gd > 0 ? 'text-pop-teal' : gd < 0 ? 'text-pop-red' : 'text-ink',
                        ].join(' ')}>
                            {gd >= 0 ? '+' : ''}{gd}
                        </span>
                        <span className="font-display text-[14px] text-right">{t.pts}</span>
                    </div>
                );
            })}

            {/* Footer */}
            <div className="px-2.5 py-1.5 bg-black/[.04] font-mono text-[9px] opacity-65 tracking-[.06em] flex justify-between">
                <span>TOP 2 → R32</span>
                <span>+ 8 mejores 3°</span>
            </div>
        </div>
    );
}
```

- [ ] **Step 12.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `113 tests passed`.

- [ ] **Step 12.5: Commit**

```bash
git add resources/js/Components/composed/GroupStandingCard.jsx resources/js/tests/composed/GroupStandingCard.test.jsx
git commit -m "feat: add GroupStandingCard composed component"
```

---

## Task 13: Barrel export

**Files:**
- Create: `resources/js/Components/composed/index.js`

- [ ] **Step 13.1: Crear `index.js`**

Crear `resources/js/Components/composed/index.js`:

```js
export { default as TabBar }             from './TabBar';
export { default as PtsBadge }           from './PtsBadge';
export { default as StatCard }           from './StatCard';
export { default as BetCard }            from './BetCard';
export { default as MatchCard }          from './MatchCard';
export { default as ScoreBox }           from './ScoreBox';
export { default as MatchPredRow }       from './MatchPredRow';
export { default as PodiumStep }         from './PodiumStep';
export { default as RankRow }            from './RankRow';
export { default as PozoCard }           from './PozoCard';
export { default as ChatBubble }         from './ChatBubble';
export { default as GroupStandingCard }  from './GroupStandingCard';
```

- [ ] **Step 13.2: Verificar que todos los tests siguen pasando**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `113 tests passed`.

- [ ] **Step 13.3: Commit**

```bash
git add resources/js/Components/composed/index.js
git commit -m "chore: add barrel export for composed components"
```

---

## Notas para el implementador

**Tailwind tokens disponibles:**
- Colores bg: `bg-ink`, `bg-navy`, `bg-cream`, `bg-pop-yel`, `bg-pop-red`, `bg-pop-teal`, `bg-white`
- Colores text: `text-ink`, `text-cream`, `text-pop-yel`, `text-pop-red`, `text-pop-teal`
- Sombras: `shadow-pop-sm`, `shadow-pop`, `shadow-pop-md`, `shadow-pop-lg`
- Border: `border-2.5` (2.5px), `border-ink`
- Fuentes: `font-display` (Bungee), `font-mono` (JetBrains Mono), `font-pixel` (VT323)
- Texturas: `halftone`, `halftone-red`, `halftone-yel`, `halftone-teal`, `halftone-navy`

**Importaciones frecuentes:**
```jsx
import { Trophy, SoccerBall, Boot } from '@/Components/icons/football';
import { NavStadium, NavVS, NavTrophy, NavFire } from '@/Components/icons/NavIcons';
import { Burst } from '@/Components/ui';
import Cromo from '@/Components/ui/Cromo';
```

**Banderas de equipos:** siempre `<img src={flagUrl} alt={teamName} />` — nunca hardcodear URLs.

**No agregar:** PropTypes, TypeScript, lógica de routing, handlers de formulario, estado interno (excepto lo que ya existe en los primitivos).
