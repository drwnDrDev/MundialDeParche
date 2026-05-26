# Spec: Biblioteca de Íconos SVG — Paso 3

**Fecha:** 2026-05-25
**Proyecto:** PollaMundial
**Alcance:** Paso 3 del handoff de diseño — íconos SVG de fútbol + íconos de navegación

---

## Contexto

El handoff de diseño provee dos archivos con íconos SVG:
- `football-graphics.jsx` — 12 íconos de fútbol decorativos
- `bits.jsx` — 4 íconos del TabBar de navegación

Este paso los porta al stack React + Tailwind del proyecto, usando CSS variables para el sistema de color (opción A acordada con el usuario).

---

## CSS Variables

Agregar en `resources/css/app.css` bajo `@layer base`, antes de las utility classes existentes:

```css
@layer base {
  :root {
    --c-navy:  #0a1f3d;
    --c-red:   #ff3d3d;
    --c-yel:   #ffd23f;
    --c-teal:  #00c2a8;
    --c-cream: #f4ecd8;
    --c-ink:   #0e1320;
  }
}
```

Estas variables mapean 1:1 con los tokens Tailwind definidos en `tailwind.config.js`. Se usan como valores por defecto en los props de `fill` y `stroke` de todos los íconos.

---

## Estructura de archivos

```
resources/js/Components/icons/
├── football/
│   ├── index.js          ← barrel export
│   ├── Trophy.jsx
│   ├── SoccerBall.jsx
│   ├── Jersey.jsx
│   ├── Boot.jsx
│   ├── Whistle.jsx
│   ├── Stadium.jsx
│   ├── GoalNet.jsx
│   ├── Mark26.jsx
│   ├── HostStrip.jsx
│   ├── FlagSmall.jsx
│   ├── Pennant.jsx
│   └── PitchSwoosh.jsx
└── NavIcons.jsx           ← 4 íconos del TabBar en un solo archivo

resources/js/tests/icons/
├── football.test.jsx
└── NavIcons.test.jsx
```

---

## Íconos de fútbol

### Reglas generales

- Props de color usan `"var(--c-xxx)"` como string CSS variable (no hex hardcodeado)
- Se elimina el `Object.assign(window, {...})` del final del handoff
- Se agrega `key` prop a todos los elementos generados con `.map()` (corrección React)
- Las referencias internas entre componentes del mismo archivo se resuelven por importación explícita (ej. `HostStrip` importa `FlagSmall`)
- Cada archivo exporta su componente como `export default`
- Sin PropTypes, sin TypeScript — consistente con el resto del proyecto

### Componentes

#### `Trophy.jsx`
Props: `size=40`, `color="var(--c-yel)"`, `stroke="var(--c-ink)"`, `sw=2.5`
SVG viewBox: `0 0 60 72`, width=`{size}`, height=`{size * 1.2}`

#### `SoccerBall.jsx`
Props: `size=40`, `stroke="var(--c-ink)"`, `sw=2.5`
SVG viewBox: `0 0 60 60`, cuadrado `{size}x{size}`

#### `Jersey.jsx`
Props: `size=40`, `color="var(--c-red)"`, `stroke="var(--c-ink)"`, `sw=2.5`, `num="10"`
SVG viewBox: `0 0 60 60`
Usa `<text>` con `fontFamily="Bungee, sans-serif"`

#### `Boot.jsx`
Props: `size=40`, `color="var(--c-ink)"`, `stroke="var(--c-ink)"`, `sw=2.5`
SVG viewBox: `0 0 84 60`, width=`{size * 1.4}`, height=`{size}`
Los laces usan `stroke="var(--c-yel)"` hardcodeado como string literal dentro del SVG

#### `Whistle.jsx`
Props: `size=36`, `color="var(--c-yel)"`, `stroke="var(--c-ink)"`, `sw=2.5`
SVG viewBox: `0 0 84 60`, width=`{size * 1.4}`, height=`{size}`

#### `Stadium.jsx`
Props: `size=80`, `color="var(--c-teal)"`, `stroke="var(--c-ink)"`, `sw=2.5`
SVG viewBox: `0 0 160 100`, width=`{size * 1.6}`, height=`{size}`
Pitch fill: `"#1f7a3a"` (color fijo, no variable)

#### `GoalNet.jsx`
Props: `size=80`, `stroke="var(--c-ink)"`, `sw=2.5`
SVG viewBox: `0 0 120 80`, width=`{size * 1.5}`, height=`{size}`
**Fix React**: las líneas de la red se generan con `.map()` — agregar `key` prop:
```jsx
Array.from({ length: 11 }).map((_, i) => (
  <line key={`v-${i}`} ... />
))
Array.from({ length: 7 }).map((_, i) => (
  <line key={`h-${i}`} ... />
))
```

#### `Mark26.jsx`
Props: `size=60`, `fill="var(--c-red)"`, `stroke="var(--c-ink)"`, `sw=2.5`, `accent="var(--c-yel)"`
SVG viewBox: `0 0 96 60`, width=`{size * 1.6}`, height=`{size}`

#### `HostStrip.jsx`
Props: `height=22`
Componente compuesto (div, no SVG). Importa `FlagSmall` desde `./FlagSmall`.
Estilos portados de inline-styles a Tailwind classes:
- Container: `inline-flex items-center gap-1 px-2 py-0.5 bg-white border-2 border-ink shadow-pop-sm`
- Labels: `font-mono font-bold text-[9px] tracking-[.1em]`

#### `FlagSmall.jsx`
Props: `code`, `h=10`
Flags disponibles: `us`, `ca`, `mx` (inline SVG por código)
Container del flag: `border border-ink inline-flex leading-none`

#### `Pennant.jsx`
Props: `color="var(--c-red)"`, `text="GOL"`, `stroke="var(--c-ink)"`, `w=60`, `h=36`, `rotate=0`
Wrapper div con `style={{ transform: \`rotate(${rotate}deg)\` }}` cuando `rotate !== 0`
Usa `<text>` con `fontFamily="Bungee, sans-serif"`

#### `PitchSwoosh.jsx`
Props: `width=200`, `height=80`
SVG con `<defs><pattern>` para franjas de césped.
El `id="stripes"` del pattern puede colisionar si se renderizan múltiples instancias — usar un id único con `useId()` de React o un valor fijo con prefijo: `id="pitch-stripes"`.

---

## Íconos de NavBar

Archivo: `resources/js/Components/icons/NavIcons.jsx`

Exporta 4 named exports:

```jsx
export function NavStadium({ active = false }) { ... }
export function NavVS({ active = false }) { ... }
export function NavTrophy({ active = false }) { ... }
export function NavFire({ active = false }) { ... }
```

Cada ícono usa el prop `active` para cambiar colores:

| Estado | stroke | fill/accent |
|---|---|---|
| Inactivo | `var(--c-ink)` | ver handoff por ícono |
| Activo | `var(--c-yel)` | ver handoff por ícono |

Los valores exactos de fill/accent por estado activo/inactivo están en `bits.jsx` del handoff y se transcriben sin cambios.

---

## Barrel export

`resources/js/Components/icons/football/index.js`:

```js
export { default as Trophy } from './Trophy';
export { default as SoccerBall } from './SoccerBall';
export { default as Jersey } from './Jersey';
export { default as Boot } from './Boot';
export { default as Whistle } from './Whistle';
export { default as Stadium } from './Stadium';
export { default as GoalNet } from './GoalNet';
export { default as Mark26 } from './Mark26';
export { default as HostStrip } from './HostStrip';
export { default as FlagSmall } from './FlagSmall';
export { default as Pennant } from './Pennant';
export { default as PitchSwoosh } from './PitchSwoosh';
```

Uso en componentes compuestos:
```jsx
import { Trophy, SoccerBall } from '@/Components/icons/football';
import { NavStadium, NavFire } from '@/Components/icons/NavIcons';
```

---

## Testing

### `resources/js/tests/icons/football.test.jsx`

Smoke tests de render para cada ícono + test específico de GoalNet:

```jsx
import { render } from '@testing-library/react';
import Trophy from '../../Components/icons/football/Trophy';
// ... resto de imports

describe('Football icons', () => {
  it('Trophy renders without crashing', () => {
    const { container } = render(<Trophy />);
    expect(container.querySelector('svg')).toBeInTheDocument();
  });
  // ... idem para los 12 íconos

  it('GoalNet renders 11 vertical lines', () => {
    const { container } = render(<GoalNet />);
    const lines = container.querySelectorAll('line');
    expect(lines.length).toBe(18); // 11 verticales + 7 horizontales
  });
});
```

### `resources/js/tests/icons/NavIcons.test.jsx`

```jsx
import { render } from '@testing-library/react';
import { NavStadium, NavVS, NavTrophy, NavFire } from '../../Components/icons/NavIcons';

describe('NavIcons', () => {
  it.each([
    ['NavStadium', NavStadium],
    ['NavVS', NavVS],
    ['NavTrophy', NavTrophy],
    ['NavFire', NavFire],
  ])('%s renders without crashing (inactive)', (_, Icon) => {
    const { container } = render(<Icon />);
    expect(container.querySelector('svg')).toBeInTheDocument();
  });

  it.each([
    ['NavStadium', NavStadium],
    ['NavVS', NavVS],
    ['NavTrophy', NavTrophy],
    ['NavFire', NavFire],
  ])('%s renders without crashing (active)', (_, Icon) => {
    const { container } = render(<Icon active />);
    expect(container.querySelector('svg')).toBeInTheDocument();
  });

  it('NavStadium uses yel stroke when active', () => {
    const { container } = render(<NavStadium active />);
    const ellipse = container.querySelector('ellipse');
    expect(ellipse).toHaveAttribute('stroke', 'var(--c-yel)');
  });
});
```

---

## Lo que NO cubre este spec

- Flags de países del torneo (más de 3) — se manejan en los compuestos del Paso 4
- Animaciones de íconos (pulsing live dot, etc.) — son responsabilidad de los compuestos
- Componentes compuestos (MatchCard, PodiumStep, TabBar) — Paso 4

---

## Archivos de referencia

- Handoff: `football-graphics.jsx` y `bits.jsx` en el bundle de diseño
- Tokens Tailwind: `tailwind.config.js`
- Spec primitivos: `docs/superpowers/specs/2026-05-25-ui-primitives-design.md`
