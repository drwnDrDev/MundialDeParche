# Spec: Componentes Compuestos — Paso 4

**Fecha:** 2026-05-25
**Proyecto:** PollaMundial
**Alcance:** Paso 4 del handoff de diseño — 12 componentes compuestos presentacionales

---

## Contexto

El handoff de diseño provee pantallas completas construidas con componentes compuestos. Este paso los porta al stack React + Tailwind, todos como componentes **puramente presentacionales** (sin lógica de negocio, sin routing, sin formularios interactivos).

Prerrequisitos completados:
- Paso 1: tokens Tailwind + fuentes + texturas CSS
- Paso 2: primitivos (`Button`, `Chip`, `Cromo`, `Burst`, `Halftone`)
- Paso 3: íconos SVG (`football/`, `NavIcons.jsx`)

---

## Decisiones de arquitectura

### Ubicación
```
resources/js/Components/composed/   ← componentes de este paso
resources/js/tests/composed/        ← tests
```
Separado de `Components/ui/` (primitivos) para distinguir claramente primitivos de compuestos.

### Banderas de equipos
Prop `flagUrl` recibe URL completa de `flagcdn.com` (ya almacenada en `teams.flag_url`). Se renderiza con `<img src={flagUrl} alt={teamName} />`. No hay componente `Flag` propio para países del torneo.

### TabBar — solo presentacional (Paso 4)
Acepta prop `active` pero no conecta a routing. La integración con Inertia `<Link>` queda para Paso 5.

### ScoreBox — solo display (Paso 4)
Sin `onChange`, sin estado interno. La lógica de formulario de predicciones queda para Paso 5.

### Tests
Smoke tests de render + 1-2 assertions de comportamiento clave por componente. Sin mocks de lógica de negocio.

---

## Estructura de archivos

```
resources/js/Components/composed/
├── index.js
├── TabBar.jsx
├── PtsBadge.jsx
├── StatCard.jsx
├── BetCard.jsx
├── MatchCard.jsx
├── ScoreBox.jsx
├── MatchPredRow.jsx
├── PodiumStep.jsx
├── RankRow.jsx
├── PozoCard.jsx
├── ChatBubble.jsx
└── GroupStandingCard.jsx

resources/js/tests/composed/
├── TabBar.test.jsx
├── PtsBadge.test.jsx
├── StatCard.test.jsx
├── BetCard.test.jsx
├── MatchCard.test.jsx
├── ScoreBox.test.jsx
├── MatchPredRow.test.jsx
├── PodiumStep.test.jsx
├── RankRow.test.jsx
├── PozoCard.test.jsx
├── ChatBubble.test.jsx
└── GroupStandingCard.test.jsx
```

---

## Componentes

### `TabBar.jsx`

**Props:**
```jsx
TabBar({ active = "home" })
// active: "home" | "matches" | "rank" | "chat"
```

**Estructura:** `<nav>` con 4 botones flex-1. Tab activo: `bg-ink border-2.5 border-ink shadow-[3px_3px_0_var(--c-red)]`. Tab inactivo: fondo transparente, sin sombra.

**Tabs (fijos):**
| id | label | Icon |
|---|---|---|
| `home` | PARCHE | `NavStadium` |
| `matches` | PARTIDOS | `NavVS` |
| `rank` | RANKING | `NavTrophy` |
| `chat` | CHAT | `NavFire` |

**Estilos container:** `fixed bottom-0 left-0 right-0 bg-cream border-t-[3px] border-ink px-3 pt-2.5 pb-[22px] flex justify-between gap-1.5`

**Importa:** `NavStadium`, `NavVS`, `NavTrophy`, `NavFire` de `@/Components/icons/NavIcons`

**Tests:**
- Renders 4 tab buttons
- Tab activo tiene `aria-current="page"`
- Tab inactivo no tiene `aria-current`

---

### `PtsBadge.jsx`

**Props:**
```jsx
PtsBadge({ value, rank })
// value="124"  rank="#12"
```

**Estructura:** `<div>` pill (`rounded-full`) con avatar circular yel "P" + valor display + rank mono cream/70%.

**Clases container:** `inline-flex items-center gap-1.5 py-1 pl-1.5 pr-2.5 bg-ink border-2 border-ink rounded-full shadow-pop-sm`

**Avatar "P":** `w-5 h-5 rounded-full bg-pop-yel text-ink font-display text-[11px] flex items-center justify-center`

**Value:** `font-display text-[13px] text-pop-yel`

**Rank:** `font-mono text-[10px] text-cream opacity-70`

**Tests:**
- Renders value
- Renders rank

---

### `StatCard.jsx`

**Props:**
```jsx
StatCard({ label, value, sub, color = "red", icon })
// color: "red" | "teal" | "yel"
// icon: "trophy" | "ball" | "boot"
```

**Estructura:** card blanca con:
- Halftone corner `absolute top-0 right-0 w-[30px] h-[30px]` clase `halftone halftone-{color}` enmascarado a esquina TR
- Ícono `absolute top-1 left-1 opacity-90`: `<Trophy size={16} color="var(--c-{color})">` / `<SoccerBall size={16}>` / `<Boot size={14} color="var(--c-{color})">`
- Label: `font-mono text-[9px] tracking-[.1em] opacity-80 mt-3.5`
- Value: `font-display text-[22px]` color `var(--c-{color})` (excepción: `yel` → `var(--c-ink)`)
- Sub: `font-mono text-[10px] opacity-60`

**Clases container:** `border-2.5 border-ink bg-white p-2.5 text-center shadow-pop relative overflow-hidden`

**Importa:** `Trophy`, `SoccerBall`, `Boot` de `@/Components/icons/football`

**Tests:**
- Renders label
- Renders value
- Renders svg icon

---

### `BetCard.jsx`

**Props:**
```jsx
BetCard({ teamA, teamB, flagUrlA, flagUrlB, pick, pts, time, hot = false })
// pick="2-1"  pts="+10"  time="EN 2H"
// hot=true → corner "¡EN VIVO!" + rotate(-2deg), hot=false → rotate(1deg)
```

**Estructura:** `<Cromo>` con `minWidth: 158px`, `transform: rotate({hot ? -2 : 1}deg)`, `padding: 10px`.
- Corner label "¡EN VIVO!" si `hot` (via prop `corner` de `<Cromo>`)
- Row: `<img flagUrlA>` + pick display red + `<img flagUrlB>` — `justify-between items-center`
- Meta: `teamA vs teamB` mono + `time` mono — `justify-between mt-2`
- Footer dashed: "POSIBLES" mono/70% + pts display red

**Flag img:** `h-4 w-6 object-cover border border-ink`

**Tests:**
- Renders pick
- Renders teamA name
- Corner "¡EN VIVO!" visible cuando hot=true
- Corner no visible cuando hot=false

---

### `MatchCard.jsx`

**Props:**
```jsx
MatchCard({
  status,                   // "live" | "ft" | "upcoming"
  time,                     // "13:00"
  teamA, teamB,             // "COL", "BRA"
  flagUrlA, flagUrlB,       // URLs flagcdn
  scoreA, scoreB,           // null si upcoming
  minute,                   // "43'" — solo live
  group,                    // "D"
  venue,                    // "MIAMI"
  myPick,                   // "2-1" o null
  myPts,                    // 5 — solo ft con pts
})
```

**Container:** `border-2.5 border-ink shadow-pop p-[10px_12px] relative overflow-hidden`
- live: `bg-navy text-cream` + halftone-red inset opacity-15
- ft / upcoming: `bg-white text-ink`

**Status indicator (izquierda, w-[52px]):**
- live: dot rojo animado + "LIVE" display red + minuto display yel
- ft: "FT" display teal + hora mono/55%
- upcoming: hora display

**Teams grid:** `grid grid-cols-[1fr_auto_1fr] items-center gap-1.5`
- Cada equipo: flag img + nombre display
- Centro: "VS" display/50% (upcoming) o `scoreA — scoreB` display

**Footer dashed:** grupo + venue + pick badge
- Con pick, ft con pts: chip teal `TUS GOLES: 2-1 · +5 PTS`
- Con pick, live/upcoming: chip yel `TUS GOLES: 2-1`
- Sin pick: dashed red `! FALTAN TUS GOLES`

**Tests:**
- Renders teamA name
- live: muestra minuto
- ft: muestra "FT"
- upcoming: muestra "VS"
- Con myPick: muestra el pick en footer
- Sin myPick: muestra "FALTAN TUS GOLES"

---

### `ScoreBox.jsx`

**Props:**
```jsx
ScoreBox({ value, filled = false })
// value=2 o null → "—"
// filled=true → bg-pop-yel, false → bg-white
```

**Clases:** `w-[30px] h-[34px] border-2.5 border-ink shadow-pop-sm flex items-center justify-center font-display text-[18px]`
- filled: `bg-pop-yel text-ink`
- !filled: `bg-white text-black/25`

**Tests:**
- Renders value cuando filled
- Renders "—" cuando value es null
- Tiene clase bg-pop-yel cuando filled

---

### `MatchPredRow.jsx`

**Props:**
```jsx
MatchPredRow({
  date,                        // "11 JUN · 19:00"
  venue,                       // "AZTECA"
  teamHome, teamAway,
  flagUrlHome, flagUrlAway,
  scoreHome, scoreAway,        // null si no rellenado
  status,                      // "ok" | "empty"
  last = false,
})
```

**Estructura:**
- Meta line: `{date} · {venue}` mono/55% 8.5px
- Grid `grid-cols-[1fr_auto_1fr] items-center gap-2`:
  - Home: `flex items-center justify-end gap-1.5` — nombre display + flag img
  - Centro: `flex items-center gap-0.5` — `<ScoreBox>` + "—" + `<ScoreBox>`
  - Away: `flex items-center gap-1.5` — flag img + nombre display
- Status pill centrado: ✓ GUARDADO (chip teal) / ! FALTAN TUS GOLES (dashed red)
- `border-b border-dashed border-black/20` si !last

**Flag img:** `h-4 w-6 object-cover border border-ink`

**Tests:**
- Renders teamHome
- Status "ok" → muestra "GUARDADO"
- Status "empty" → muestra "FALTAN TUS GOLES"
- filled=true en ScoreBox cuando status="ok"

---

### `PodiumStep.jsx`

**Props:**
```jsx
PodiumStep({ place, pts, tied, crown = false })
// place: 1 | 2 | 3
// pts: "48"
// tied: [{ name, color }]  — uno o más
// crown=true → Trophy size=26 arriba (solo place=1)
```

**Colores de escalón:**
- 1° → `var(--c-yel)`, label color: ink
- 2° → `var(--c-cream)`, label color: ink
- 3° → `var(--c-red)`, label color: white

**Stack de avatares** (máx 3 visibles):
- Cada avatar: `w-11 h-11 rounded-full border-2.5 border-ink shadow-pop-sm font-display text-[16px] flex items-center justify-center`
- Overlap: `ml-[-16px]` desde el segundo, `z-index` decreciente
- Si `tied.length > 3`: pill ink `+{tied.length - 3}` mono 10px al final

**Chip empate** (si `tied.length > 1`): `font-mono text-[9px] font-bold tracking-[.08em] bg-pop-red text-white px-1.5 border-[1.5px] border-ink`

**Nombre** (si !isTie): display 10px. Si isTie: "···"

**Bloque escalón:** `w-full mt-1 border-2.5 border-ink shadow-pop flex items-start justify-center pt-2 font-display text-[28px] relative overflow-hidden`
- Halftone interno opacity-12
- SoccerBall size=32 fantasma opacity-35 en bottom cuando place=1

**Tests:**
- Renders pts
- place=1 con tied.length=3: muestra chip "3 EMPATAN"
- place=1 con tied.length=1: no muestra chip empate
- tied.length=5: muestra "+2" pill

---

### `RankRow.jsx`

**Props:**
```jsx
RankRow({ position, name, pts, delta, isYou = false, tiedCount })
// delta: "+3" | "-1" | "+0"
// isYou=true → bg yel, posición en red
// tiedCount=84 → muestra "=84" debajo de posición
```

**Container:** `flex items-center gap-2.5 px-2.5 py-2 border-2.5 border-ink shadow-pop relative`
- isYou: `bg-pop-yel`
- !isYou: `bg-white`

**Posición** (w-9): display 16px, color red si isYou sino ink. `tiedCount` abajo: mono 7px red `={tiedCount}`

**Avatar** (w-7 h-7): círculo teal/20% ink border. Inicial del nombre. display 12px.

**Nombre:** display 13px, flex-1

**Puntos:** display 16px + "PUNTOS" mono 8px/70%

**Delta chip:** mono bold 10px, padding 2px 6px, border-[1.5px] border-ink
- delta positivo (+): `bg-pop-teal text-white`  
- delta negativo o cero: `bg-pop-red text-white`
- Texto: `▲{n}` o `▼{n}`

**Tests:**
- Renders name
- Renders pts
- isYou=true → container tiene clase bg-pop-yel
- delta "+3" → chip teal con ▲

---

### `PozoCard.jsx`

**Props:**
```jsx
PozoCard({ total, players, amountPerPlayer, prize1, prize2 })
// total="4.200K"  players=84  amountPerPlayer="50K"
// prize1="2.940K"  prize2="1.260K"
```

**Container:** `<Cromo>` con `bg-navy text-cream p-[10px_12px] relative overflow-hidden`

**Decoraciones:**
- Halftone yel inset opacity-35
- `<Trophy size={62} color="var(--c-yel)">` absolute right/bottom rotado -8°

**Contenido:**
- Label "POZO TOTAL" mono yel tracking-[.12em]
- Total display 30px yel
- Sub: `{players} jugadores · {amountPerPlayer} c/u` mono/75%

**Grid premios** (`grid grid-cols-2 gap-1.5 mt-2.5 relative`):
- PrizeSlot 1°: 70% — `{prize1}`
- PrizeSlot 2°: 30% — `{prize2}`

**PrizeSlot** (interno, no exportado):
```jsx
PrizeSlot({ place, pct, amount, color })
// color: "var(--c-yel)" | "var(--c-cream)"
```
`bg-black/35 border-2 border-ink p-[6px_8px]`

**Tests:**
- Renders total
- Renders prize1
- Renders prize2

---

### `ChatBubble.jsx`

**Props:**
```jsx
ChatBubble({ name, color, text, time, isMe = false, pinned = false, sticker })
// color: color CSS del avatar ("var(--c-teal)", etc.)
// isMe=true → bubble yel, sin avatar, alineado a la derecha
// pinned=true → badge "FIJO" rotado(6°) en esquina superior derecha del bubble
// sticker → string para Burst debajo del bubble
```

**Layout outer:** `flex gap-2 items-end` + `flex-row-reverse` si isMe

**Avatar** (solo !isMe): `w-8 h-8 rounded-full border-2 border-ink flex-shrink-0 font-display text-[12px] text-white flex items-center justify-center` — bg: `color` prop — muestra inicial de `name`

**Inner:** `flex flex-col max-w-[78%]` + `items-end` si isMe

**Header** (solo !isMe): nombre display 10px en `color` + hora mono/55%

**Bubble:** `border-2.5 border-ink p-[8px_12px] rounded-[4px] text-[14px] leading-snug relative`
- isMe: `bg-pop-yel shadow-[-3px_3px_0_var(--c-ink)]`
- !isMe: `bg-white shadow-[3px_3px_0_var(--c-ink)]`
- Badge pinned: `absolute -top-2 -right-2 bg-pop-red text-white border-2 border-ink px-1.5 font-display text-[9px] rotate-[6deg]` texto "FIJO"

**Sticker** (si presente): `<Burst color="var(--c-red)" size={60} rotate={0} fontSize={11}>{sticker}</Burst>` con `mt-1 -rotate-[4deg]`

**Hora propia** (solo isMe): mono/55% mt-0.5

**Importa:** `Burst` de `@/Components/ui`

**Tests:**
- Renders text
- isMe=false → avatar visible con inicial
- isMe=true → no hay avatar
- pinned=true → badge "FIJO" visible
- sticker → Burst visible con texto del sticker

---

### `GroupStandingCard.jsx`

**Props:**
```jsx
GroupStandingCard({ group, played, teams })
// group: "A"
// played: "1 / 6 JUGADOS"
// teams: [{ flagUrl, name, pj, g, e, p, gf, gc, pts, live }]
// gd se calcula internamente: gf - gc
```

**Container:** `border-2.5 border-ink shadow-pop-md bg-white relative overflow-hidden`

**Header:** `flex justify-between items-center px-3 py-1.5 bg-pop-red text-white border-b-2.5 border-ink`
- "GRUPO {group}" display 14px
- played mono/90% 9px

**Col headers:** `grid grid-cols-[20px_1fr_24px_50px_28px_28px] gap-1 px-2.5 py-1.5 font-mono text-[9px] font-bold tracking-[.06em] opacity-55 border-b border-dashed border-black/20`
- `#` / `EQUIPO` / `PJ` / `G-E-P` / `GD` / `PTS`

**Team rows** (map sobre teams):
- `grid grid-cols-[20px_1fr_24px_50px_28px_28px] gap-1 px-2.5 py-2 items-center`
- Top 2 (índice 0 y 1): `bg-pop-yel/18`
- `border-b border-dashed border-black/10` excepto el último
- Col 1: posición mono bold/70% + `↑` teal si top 2
- Col 2: `<img flagUrl>` h-3 + nombre display 11px + chip "LIVE" si `live`
- Col 3: pj mono bold centrado
- Col 4: `{g}-{e}-{p}` mono centrado
- Col 5: gd = gf-gc, coloreado: teal si >0, red si <0, ink si 0
- Col 6: pts display 14px alineado a derecha

**Footer:** `px-2.5 py-1.5 bg-black/4 font-mono text-[9px] opacity-65 flex justify-between`
- "TOP 2 → R32" / "+ 8 mejores 3°"

**Tests:**
- Renders group name
- Renders nombre del primer equipo
- Top 2 tienen marca ↑
- Equipo con live=true muestra chip "LIVE"

---

### `index.js` (barrel)

```js
export { default as TabBar } from './TabBar';
export { default as PtsBadge } from './PtsBadge';
export { default as StatCard } from './StatCard';
export { default as BetCard } from './BetCard';
export { default as MatchCard } from './MatchCard';
export { default as ScoreBox } from './ScoreBox';
export { default as MatchPredRow } from './MatchPredRow';
export { default as PodiumStep } from './PodiumStep';
export { default as RankRow } from './RankRow';
export { default as PozoCard } from './PozoCard';
export { default as ChatBubble } from './ChatBubble';
export { default as GroupStandingCard } from './GroupStandingCard';
```

---

## Lo que NO cubre este spec

- Routing real del TabBar (Inertia `<Link>`) — Paso 5
- ScoreBox interactivo con onChange — Paso 5
- Lógica de estado (formularios de predicción, envío al backend) — Paso 5
- Pantallas completas compuestas — Paso 5
- Animación del dot "EN VIVO" (CSS keyframes) — puede incluirse o no, no es bloqueante
