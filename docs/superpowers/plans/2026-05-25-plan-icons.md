# Íconos SVG — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Portar 12 íconos SVG de fútbol y 4 íconos de NavBar del handoff de diseño al stack React + Tailwind del proyecto.

**Architecture:** Un archivo por ícono de fútbol para tree-shaking (`Components/icons/football/`), un archivo con los 4 íconos de nav (`Components/icons/NavIcons.jsx`). Los colores se resuelven via CSS variables `:root` que mapean 1:1 con los tokens Tailwind.

**Tech Stack:** React 18, Tailwind CSS v3, Vitest + React Testing Library (ya instalados), Laravel Sail (pnpm).

**Spec:** `docs/superpowers/specs/2026-05-25-icons-design.md`

---

## File Map

| Archivo | Estado | Responsabilidad |
|---|---|---|
| `resources/css/app.css` | Modificar | Agregar CSS variables `:root` |
| `resources/js/Components/icons/football/FlagSmall.jsx` | Crear | Banderas SVG inline (us/ca/mx) |
| `resources/js/Components/icons/football/Trophy.jsx` | Crear | Ícono trofeo |
| `resources/js/Components/icons/football/SoccerBall.jsx` | Crear | Ícono pelota |
| `resources/js/Components/icons/football/Jersey.jsx` | Crear | Ícono camiseta |
| `resources/js/Components/icons/football/Boot.jsx` | Crear | Ícono botín |
| `resources/js/Components/icons/football/Whistle.jsx` | Crear | Ícono silbato |
| `resources/js/Components/icons/football/Stadium.jsx` | Crear | Ícono estadio |
| `resources/js/Components/icons/football/GoalNet.jsx` | Crear | Ícono arco (con fix React key) |
| `resources/js/Components/icons/football/Mark26.jsx` | Crear | Numerales "26" pop-art |
| `resources/js/Components/icons/football/Pennant.jsx` | Crear | Banderín |
| `resources/js/Components/icons/football/PitchSwoosh.jsx` | Crear | Swoosh de cancha |
| `resources/js/Components/icons/football/HostStrip.jsx` | Crear | Strip USA·CAN·MEX (importa FlagSmall) |
| `resources/js/Components/icons/football/index.js` | Crear | Barrel export |
| `resources/js/Components/icons/NavIcons.jsx` | Crear | 4 íconos del TabBar |
| `resources/js/tests/icons/football.test.jsx` | Crear | Tests de íconos de fútbol |
| `resources/js/tests/icons/NavIcons.test.jsx` | Crear | Tests de NavIcons |

---

## Task 1: CSS Variables

**Files:**
- Modify: `resources/css/app.css`

- [ ] **Step 1.1: Agregar variables CSS a `app.css`**

El archivo actual empieza con las directivas Tailwind y las utilities de textura. Agregar `@layer base` con las variables **antes** de `@layer utilities`:

Reemplazar el contenido completo de `resources/css/app.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

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

@layer utilities {
    /* Halftone (puntos Ben-Day) */
    .halftone {
        background-image: radial-gradient(rgba(0, 0, 0, 0.9) 1.2px, transparent 1.6px);
        background-size: 8px 8px;
        opacity: 0.18;
    }
    .halftone-red {
        background-image: radial-gradient(#ff3d3d 1.2px, transparent 1.6px);
        background-size: 8px 8px;
        opacity: 0.18;
    }
    .halftone-yel {
        background-image: radial-gradient(#ffd23f 1.2px, transparent 1.6px);
        background-size: 8px 8px;
        opacity: 0.18;
    }
    .halftone-teal {
        background-image: radial-gradient(#00c2a8 1.2px, transparent 1.6px);
        background-size: 8px 8px;
        opacity: 0.18;
    }
    .halftone-navy {
        background-image: radial-gradient(#0a1f3d 1.2px, transparent 1.6px);
        background-size: 8px 8px;
        opacity: 0.18;
    }

    /* Scanlines (CRT) */
    .scanlines {
        background-image: repeating-linear-gradient(
            0deg,
            rgba(0, 0, 0, 0.08) 0 1px,
            transparent 1px 3px
        );
    }

    /* Speedlines (anime) */
    .speedlines {
        background-image: repeating-linear-gradient(
            75deg,
            transparent 0 14px,
            rgba(0, 0, 0, 0.18) 14px 16px,
            transparent 16px 30px
        );
    }
}
```

- [ ] **Step 1.2: Verificar que los tests existentes siguen pasando**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `43 tests passed` — sin errores de CSS (Vitest no parsea CSS, pero verifica que el build no se rompe).

- [ ] **Step 1.3: Commit**

```bash
git add resources/css/app.css
git commit -m "feat: add CSS color variables for SVG icon theming"
```

---

## Task 2: FlagSmall

**Files:**
- Create: `resources/js/Components/icons/football/FlagSmall.jsx`
- Create: `resources/js/tests/icons/football.test.jsx`

`FlagSmall` debe existir antes que `HostStrip` porque `HostStrip` lo importa.

- [ ] **Step 2.1: Crear test**

Crear `resources/js/tests/icons/football.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import FlagSmall from '../../Components/icons/football/FlagSmall';

describe('Football icons', () => {
    describe('FlagSmall', () => {
        it('renders US flag svg', () => {
            const { container } = render(<FlagSmall code="us" />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('renders CA flag svg', () => {
            const { container } = render(<FlagSmall code="ca" />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('renders MX flag svg', () => {
            const { container } = render(<FlagSmall code="mx" />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('returns null for unknown code', () => {
            const { container } = render(<FlagSmall code="zz" />);
            expect(container.querySelector('svg')).not.toBeInTheDocument();
        });
    });
});
```

- [ ] **Step 2.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/icons/football/FlagSmall'`.

- [ ] **Step 2.3: Crear `FlagSmall.jsx`**

Crear `resources/js/Components/icons/football/FlagSmall.jsx`:

```jsx
const FLAGS = {
    us: (h) => (
        <svg width={h * 1.6} height={h} viewBox="0 0 16 10">
            <rect width="16" height="10" fill="#bf0a30" />
            <rect y="2" width="16" height="2" fill="#fff" />
            <rect y="6" width="16" height="2" fill="#fff" />
            <rect width="7" height="6" fill="#002868" />
        </svg>
    ),
    ca: (h) => (
        <svg width={h * 1.6} height={h} viewBox="0 0 16 10">
            <rect width="16" height="10" fill="#fff" />
            <rect width="5" height="10" fill="#d52b1e" />
            <rect x="11" width="5" height="10" fill="#d52b1e" />
            <path d="M8 3 L9 5 L11 5 L9.5 6.5 L10 8.5 L8 7.5 L6 8.5 L6.5 6.5 L5 5 L7 5 Z" fill="#d52b1e" />
        </svg>
    ),
    mx: (h) => (
        <svg width={h * 1.6} height={h} viewBox="0 0 16 10">
            <rect width="5.33" height="10" fill="#006847" />
            <rect x="5.33" width="5.34" height="10" fill="#fff" />
            <rect x="10.67" width="5.33" height="10" fill="#ce1126" />
        </svg>
    ),
};

export default function FlagSmall({ code, h = 10 }) {
    const flag = FLAGS[code];
    if (!flag) return null;
    return (
        <span className="border border-ink inline-flex leading-none">
            {flag(h)}
        </span>
    );
}
```

- [ ] **Step 2.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `47 tests passed` (43 + 4 FlagSmall).

- [ ] **Step 2.5: Commit**

```bash
git add resources/js/Components/icons/football/FlagSmall.jsx resources/js/tests/icons/football.test.jsx
git commit -m "feat: add FlagSmall icon (us/ca/mx inline SVG flags)"
```

---

## Task 3: Trophy, SoccerBall, Jersey

**Files:**
- Create: `resources/js/Components/icons/football/Trophy.jsx`
- Create: `resources/js/Components/icons/football/SoccerBall.jsx`
- Create: `resources/js/Components/icons/football/Jersey.jsx`
- Modify: `resources/js/tests/icons/football.test.jsx`

- [ ] **Step 3.1: Agregar tests**

Agregar al final del bloque `describe('Football icons', ...)` en `resources/js/tests/icons/football.test.jsx`, **antes del cierre `});`**:

```jsx
    describe('Trophy', () => {
        it('renders svg', () => {
            const { container } = render(<Trophy />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('SoccerBall', () => {
        it('renders svg', () => {
            const { container } = render(<SoccerBall />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('Jersey', () => {
        it('renders svg', () => {
            const { container } = render(<Jersey />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });
```

Y agregar los imports al inicio del archivo:

```jsx
import Trophy from '../../Components/icons/football/Trophy';
import SoccerBall from '../../Components/icons/football/SoccerBall';
import Jersey from '../../Components/icons/football/Jersey';
```

- [ ] **Step 3.2: Correr tests para confirmar que fallan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: 3 errores `Cannot find module`.

- [ ] **Step 3.3: Crear `Trophy.jsx`**

Crear `resources/js/Components/icons/football/Trophy.jsx`:

```jsx
export default function Trophy({ size = 40, color = 'var(--c-yel)', stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size} height={size * 1.2} viewBox="0 0 60 72" fill="none">
            <path d="M14 16 C 4 16, 4 36, 16 36" stroke={stroke} strokeWidth={sw} fill="none" strokeLinecap="round" />
            <path d="M46 16 C 56 16, 56 36, 44 36" stroke={stroke} strokeWidth={sw} fill="none" strokeLinecap="round" />
            <path d="M14 8 H46 V28 C 46 40, 38 48, 30 48 C 22 48, 14 40, 14 28 Z" fill={color} stroke={stroke} strokeWidth={sw} strokeLinejoin="round" />
            <path d="M30 18 L32 24 L38 24 L33 28 L35 34 L30 30 L25 34 L27 28 L22 24 L28 24 Z" fill={stroke} />
            <rect x="26" y="48" width="8" height="8" fill={color} stroke={stroke} strokeWidth={sw} />
            <rect x="18" y="56" width="24" height="6" fill={color} stroke={stroke} strokeWidth={sw} />
            <rect x="14" y="62" width="32" height="6" fill={stroke} />
        </svg>
    );
}
```

- [ ] **Step 3.4: Crear `SoccerBall.jsx`**

Crear `resources/js/Components/icons/football/SoccerBall.jsx`:

```jsx
export default function SoccerBall({ size = 40, stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size} height={size} viewBox="0 0 60 60" fill="none">
            <circle cx="30" cy="30" r="26" fill="#fff" stroke={stroke} strokeWidth={sw} />
            <path d="M30 18 L40 24 L36 36 L24 36 L20 24 Z" fill={stroke} />
            <path d="M30 18 L30 8" stroke={stroke} strokeWidth={sw} />
            <path d="M40 24 L50 22" stroke={stroke} strokeWidth={sw} />
            <path d="M36 36 L42 46" stroke={stroke} strokeWidth={sw} />
            <path d="M24 36 L18 46" stroke={stroke} strokeWidth={sw} />
            <path d="M20 24 L10 22" stroke={stroke} strokeWidth={sw} />
        </svg>
    );
}
```

- [ ] **Step 3.5: Crear `Jersey.jsx`**

Crear `resources/js/Components/icons/football/Jersey.jsx`:

```jsx
export default function Jersey({ size = 40, color = 'var(--c-red)', stroke = 'var(--c-ink)', sw = 2.5, num = '10' }) {
    return (
        <svg width={size} height={size} viewBox="0 0 60 60" fill="none">
            <path
                d="M14 10 L22 6 L26 12 L34 12 L38 6 L46 10 L52 22 L44 26 L44 54 L16 54 L16 26 L8 22 Z"
                fill={color} stroke={stroke} strokeWidth={sw} strokeLinejoin="round"
            />
            <text x="30" y="42" textAnchor="middle" fontFamily="Bungee, sans-serif" fontSize="14" fill={stroke}>
                {num}
            </text>
        </svg>
    );
}
```

- [ ] **Step 3.6: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `50 tests passed`.

- [ ] **Step 3.7: Commit**

```bash
git add resources/js/Components/icons/football/Trophy.jsx resources/js/Components/icons/football/SoccerBall.jsx resources/js/Components/icons/football/Jersey.jsx resources/js/tests/icons/football.test.jsx
git commit -m "feat: add Trophy, SoccerBall, Jersey icons"
```

---

## Task 4: Boot, Whistle, Stadium

**Files:**
- Create: `resources/js/Components/icons/football/Boot.jsx`
- Create: `resources/js/Components/icons/football/Whistle.jsx`
- Create: `resources/js/Components/icons/football/Stadium.jsx`
- Modify: `resources/js/tests/icons/football.test.jsx`

- [ ] **Step 4.1: Agregar tests**

Agregar imports al inicio de `football.test.jsx`:

```jsx
import Boot from '../../Components/icons/football/Boot';
import Whistle from '../../Components/icons/football/Whistle';
import Stadium from '../../Components/icons/football/Stadium';
```

Agregar describes al final del bloque `describe('Football icons', ...)`, antes del cierre `});`:

```jsx
    describe('Boot', () => {
        it('renders svg', () => {
            const { container } = render(<Boot />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('Whistle', () => {
        it('renders svg', () => {
            const { container } = render(<Whistle />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('Stadium', () => {
        it('renders svg', () => {
            const { container } = render(<Stadium />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });
```

- [ ] **Step 4.2: Correr tests para confirmar que fallan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: 3 errores `Cannot find module`.

- [ ] **Step 4.3: Crear `Boot.jsx`**

Crear `resources/js/Components/icons/football/Boot.jsx`:

```jsx
export default function Boot({ size = 40, color = 'var(--c-ink)', stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size * 1.4} height={size} viewBox="0 0 84 60" fill="none">
            <path
                d="M6 32 C 6 22, 14 18, 24 18 L 56 18 C 66 18, 76 24, 78 36 L 78 44 L 12 44 C 8 44, 6 40, 6 36 Z"
                fill={color} stroke={stroke} strokeWidth={sw} strokeLinejoin="round"
            />
            <path d="M28 22 L36 30 M36 22 L44 30 M44 22 L52 30" stroke="var(--c-yel)" strokeWidth="2" />
            <circle cx="18" cy="48" r="2.5" fill={stroke} />
            <circle cx="34" cy="48" r="2.5" fill={stroke} />
            <circle cx="50" cy="48" r="2.5" fill={stroke} />
            <circle cx="66" cy="48" r="2.5" fill={stroke} />
        </svg>
    );
}
```

- [ ] **Step 4.4: Crear `Whistle.jsx`**

Crear `resources/js/Components/icons/football/Whistle.jsx`:

```jsx
export default function Whistle({ size = 36, color = 'var(--c-yel)', stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size * 1.4} height={size} viewBox="0 0 84 60" fill="none">
            <circle cx="34" cy="30" r="20" fill={color} stroke={stroke} strokeWidth={sw} />
            <rect x="44" y="22" width="34" height="16" fill={color} stroke={stroke} strokeWidth={sw} />
            <circle cx="34" cy="30" r="6" fill={stroke} />
            <path d="M80 16 L86 12 M82 30 L90 30 M80 44 L86 48" stroke={stroke} strokeWidth={sw} strokeLinecap="round" />
        </svg>
    );
}
```

- [ ] **Step 4.5: Crear `Stadium.jsx`**

Crear `resources/js/Components/icons/football/Stadium.jsx`:

```jsx
export default function Stadium({ size = 80, color = 'var(--c-teal)', stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size * 1.6} height={size} viewBox="0 0 160 100" fill="none">
            <ellipse cx="80" cy="60" rx="74" ry="32" fill={color} stroke={stroke} strokeWidth={sw} />
            <ellipse cx="80" cy="60" rx="50" ry="18" fill="#1f7a3a" stroke={stroke} strokeWidth={sw} />
            <line x1="80" y1="42" x2="80" y2="78" stroke="#fff" strokeWidth="1.5" />
            <circle cx="80" cy="60" r="6" fill="none" stroke="#fff" strokeWidth="1.5" />
            <line x1="10" y1="48" x2="10" y2="18" stroke={stroke} strokeWidth={sw} />
            <rect x="4" y="10" width="12" height="10" fill="var(--c-yel)" stroke={stroke} strokeWidth={sw} />
            <line x1="150" y1="48" x2="150" y2="18" stroke={stroke} strokeWidth={sw} />
            <rect x="144" y="10" width="12" height="10" fill="var(--c-yel)" stroke={stroke} strokeWidth={sw} />
        </svg>
    );
}
```

- [ ] **Step 4.6: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `53 tests passed`.

- [ ] **Step 4.7: Commit**

```bash
git add resources/js/Components/icons/football/Boot.jsx resources/js/Components/icons/football/Whistle.jsx resources/js/Components/icons/football/Stadium.jsx resources/js/tests/icons/football.test.jsx
git commit -m "feat: add Boot, Whistle, Stadium icons"
```

---

## Task 5: GoalNet

**Files:**
- Create: `resources/js/Components/icons/football/GoalNet.jsx`
- Modify: `resources/js/tests/icons/football.test.jsx`

GoalNet tiene un fix React importante: las líneas de la red se generan con `.map()` y necesitan `key` prop.

- [ ] **Step 5.1: Agregar tests**

Agregar import al inicio de `football.test.jsx`:

```jsx
import GoalNet from '../../Components/icons/football/GoalNet';
```

Agregar describe al final del bloque `describe('Football icons', ...)`, antes del cierre `});`:

```jsx
    describe('GoalNet', () => {
        it('renders svg', () => {
            const { container } = render(<GoalNet />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('renders 18 net lines (11 vertical + 7 horizontal)', () => {
            const { container } = render(<GoalNet />);
            const lines = container.querySelectorAll('line');
            expect(lines).toHaveLength(18);
        });
    });
```

- [ ] **Step 5.2: Correr tests para confirmar que fallan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/icons/football/GoalNet'`.

- [ ] **Step 5.3: Crear `GoalNet.jsx`**

Crear `resources/js/Components/icons/football/GoalNet.jsx`:

```jsx
export default function GoalNet({ size = 80, stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size * 1.5} height={size} viewBox="0 0 120 80" fill="none">
            <path d="M8 12 L8 70 M112 12 L112 70 M8 12 L112 12" stroke={stroke} strokeWidth={sw + 1} strokeLinecap="square" />
            <g stroke={stroke} strokeWidth="1" opacity=".7">
                {Array.from({ length: 11 }).map((_, i) => (
                    <line key={`v-${i}`} x1={8 + i * 10.4} y1="12" x2={8 + i * 10.4} y2="70" />
                ))}
                {Array.from({ length: 7 }).map((_, i) => (
                    <line key={`h-${i}`} x1="8" y1={12 + i * 8.5} x2="112" y2={12 + i * 8.5} />
                ))}
            </g>
        </svg>
    );
}
```

- [ ] **Step 5.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `55 tests passed`.

- [ ] **Step 5.5: Commit**

```bash
git add resources/js/Components/icons/football/GoalNet.jsx resources/js/tests/icons/football.test.jsx
git commit -m "feat: add GoalNet icon (with React key fix on net lines)"
```

---

## Task 6: Mark26, Pennant, PitchSwoosh

**Files:**
- Create: `resources/js/Components/icons/football/Mark26.jsx`
- Create: `resources/js/Components/icons/football/Pennant.jsx`
- Create: `resources/js/Components/icons/football/PitchSwoosh.jsx`
- Modify: `resources/js/tests/icons/football.test.jsx`

- [ ] **Step 6.1: Agregar tests**

Agregar imports al inicio de `football.test.jsx`:

```jsx
import Mark26 from '../../Components/icons/football/Mark26';
import Pennant from '../../Components/icons/football/Pennant';
import PitchSwoosh from '../../Components/icons/football/PitchSwoosh';
```

Agregar describes al final del bloque `describe('Football icons', ...)`, antes del cierre `});`:

```jsx
    describe('Mark26', () => {
        it('renders svg', () => {
            const { container } = render(<Mark26 />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('Pennant', () => {
        it('renders svg', () => {
            const { container } = render(<Pennant />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('PitchSwoosh', () => {
        it('renders svg', () => {
            const { container } = render(<PitchSwoosh />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });
```

- [ ] **Step 6.2: Correr tests para confirmar que fallan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: 3 errores `Cannot find module`.

- [ ] **Step 6.3: Crear `Mark26.jsx`**

Crear `resources/js/Components/icons/football/Mark26.jsx`:

```jsx
export default function Mark26({ size = 60, fill = 'var(--c-red)', stroke = 'var(--c-ink)', sw = 2.5, accent = 'var(--c-yel)' }) {
    return (
        <svg width={size * 1.6} height={size} viewBox="0 0 96 60" fill="none">
            <path
                d="M6 18 C 6 6, 18 2, 28 6 C 38 10, 38 22, 30 28 L 12 46 L 38 46 L 38 54 L 4 54 L 4 46 L 24 26 C 28 22, 28 14, 22 14 C 16 14, 14 18, 14 22 L 6 22 Z"
                fill={fill} stroke={stroke} strokeWidth={sw} strokeLinejoin="round"
            />
            <path
                d="M84 6 C 76 6, 68 14, 64 24 C 60 34, 60 46, 70 52 C 80 58, 92 52, 92 40 C 92 30, 84 26, 76 28 C 72 30, 68 34, 70 40 C 72 44, 78 44, 80 40 C 82 36, 76 34, 76 38"
                fill={fill} stroke={stroke} strokeWidth={sw} strokeLinejoin="round"
            />
            <path
                d="M48 8 L50 14 L56 14 L51 18 L53 24 L48 20 L43 24 L45 18 L40 14 L46 14 Z"
                fill={accent} stroke={stroke} strokeWidth="1.5"
            />
        </svg>
    );
}
```

- [ ] **Step 6.4: Crear `Pennant.jsx`**

Crear `resources/js/Components/icons/football/Pennant.jsx`:

```jsx
export default function Pennant({ color = 'var(--c-red)', text = 'GOL', stroke = 'var(--c-ink)', w = 60, h = 36, rotate = 0 }) {
    return (
        <div style={{ transform: rotate ? `rotate(${rotate}deg)` : undefined, display: 'inline-block' }}>
            <svg width={w} height={h} viewBox="0 0 60 36">
                <path
                    d="M0 2 L52 2 L60 18 L52 34 L0 34 Z"
                    fill={color} stroke={stroke} strokeWidth="2.5" strokeLinejoin="round"
                />
                <text x="22" y="22" textAnchor="middle" fontFamily="Bungee, sans-serif" fontSize="11" fill="#fff" letterSpacing=".05em">
                    {text}
                </text>
            </svg>
        </div>
    );
}
```

- [ ] **Step 6.5: Crear `PitchSwoosh.jsx`**

Crear `resources/js/Components/icons/football/PitchSwoosh.jsx`:

El pattern `id` debe ser único para evitar colisiones si se renderizan múltiples instancias. Usar `"pitch-stripes"` en lugar de `"stripes"`.

```jsx
export default function PitchSwoosh({ width = 200, height = 80 }) {
    return (
        <svg width={width} height={height} viewBox="0 0 200 80" style={{ display: 'block' }}>
            <defs>
                <pattern id="pitch-stripes" x="0" y="0" width="20" height="80" patternUnits="userSpaceOnUse">
                    <rect width="10" height="80" fill="#1f7a3a" />
                    <rect x="10" width="10" height="80" fill="#226b34" />
                </pattern>
            </defs>
            <path d="M0 80 L0 50 C 60 10, 140 10, 200 50 L 200 80 Z" fill="url(#pitch-stripes)" stroke="var(--c-ink)" strokeWidth="2.5" />
            <line x1="60" y1="32" x2="140" y2="32" stroke="#fff" strokeWidth="1.5" strokeDasharray="3 3" />
        </svg>
    );
}
```

- [ ] **Step 6.6: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `58 tests passed`.

- [ ] **Step 6.7: Commit**

```bash
git add resources/js/Components/icons/football/Mark26.jsx resources/js/Components/icons/football/Pennant.jsx resources/js/Components/icons/football/PitchSwoosh.jsx resources/js/tests/icons/football.test.jsx
git commit -m "feat: add Mark26, Pennant, PitchSwoosh icons"
```

---

## Task 7: HostStrip

**Files:**
- Create: `resources/js/Components/icons/football/HostStrip.jsx`
- Modify: `resources/js/tests/icons/football.test.jsx`

`HostStrip` es el único ícono compuesto — importa `FlagSmall` y usa Tailwind classes (no inline styles).

- [ ] **Step 7.1: Agregar test**

Agregar import al inicio de `football.test.jsx`:

```jsx
import HostStrip from '../../Components/icons/football/HostStrip';
```

Agregar describe al final del bloque `describe('Football icons', ...)`, antes del cierre `});`:

```jsx
    describe('HostStrip', () => {
        it('renders with country labels', () => {
            const { getByText } = render(<HostStrip />);
            expect(getByText('USA')).toBeInTheDocument();
            expect(getByText('CAN')).toBeInTheDocument();
            expect(getByText('MEX')).toBeInTheDocument();
        });
    });
```

- [ ] **Step 7.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/icons/football/HostStrip'`.

- [ ] **Step 7.3: Crear `HostStrip.jsx`**

Crear `resources/js/Components/icons/football/HostStrip.jsx`:

```jsx
import FlagSmall from './FlagSmall';

export default function HostStrip({ height = 22 }) {
    return (
        <div className="inline-flex items-center gap-1 px-2 py-0.5 bg-white border-2 border-ink shadow-pop-sm">
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">USA</span>
            <FlagSmall code="us" h={height - 8} />
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">·</span>
            <FlagSmall code="ca" h={height - 8} />
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">CAN</span>
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">·</span>
            <FlagSmall code="mx" h={height - 8} />
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">MEX</span>
        </div>
    );
}
```

- [ ] **Step 7.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `59 tests passed`.

- [ ] **Step 7.5: Commit**

```bash
git add resources/js/Components/icons/football/HostStrip.jsx resources/js/tests/icons/football.test.jsx
git commit -m "feat: add HostStrip icon (USA·CAN·MEX with Tailwind classes)"
```

---

## Task 8: Football barrel export

**Files:**
- Create: `resources/js/Components/icons/football/index.js`

No requiere tests — es solo re-exportación.

- [ ] **Step 8.1: Crear `index.js`**

Crear `resources/js/Components/icons/football/index.js`:

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

- [ ] **Step 8.2: Verificar que los tests siguen pasando**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `59 tests passed`.

- [ ] **Step 8.3: Commit**

```bash
git add resources/js/Components/icons/football/index.js
git commit -m "chore: add barrel export for football icons"
```

---

## Task 9: NavIcons

**Files:**
- Create: `resources/js/Components/icons/NavIcons.jsx`
- Create: `resources/js/tests/icons/NavIcons.test.jsx`

Los 4 íconos del TabBar en un solo archivo. Cada uno acepta `active` (bool, default `false`).

- [ ] **Step 9.1: Crear test**

Crear `resources/js/tests/icons/NavIcons.test.jsx`:

```jsx
import { render } from '@testing-library/react';
import { NavStadium, NavVS, NavTrophy, NavFire } from '../../Components/icons/NavIcons';

const ALL_ICONS = [
    ['NavStadium', NavStadium],
    ['NavVS', NavVS],
    ['NavTrophy', NavTrophy],
    ['NavFire', NavFire],
];

describe('NavIcons', () => {
    it.each(ALL_ICONS)('%s renders inactive', (_, Icon) => {
        const { container } = render(<Icon />);
        expect(container.querySelector('svg')).toBeInTheDocument();
    });

    it.each(ALL_ICONS)('%s renders active', (_, Icon) => {
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

- [ ] **Step 9.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/icons/NavIcons'`.

- [ ] **Step 9.3: Crear `NavIcons.jsx`**

Crear `resources/js/Components/icons/NavIcons.jsx`:

```jsx
export function NavStadium({ active = false }) {
    const stroke = active ? 'var(--c-yel)' : 'var(--c-ink)';
    const fill   = active ? 'var(--c-red)' : 'var(--c-yel)';
    return (
        <svg width="30" height="22" viewBox="0 0 30 22" fill="none">
            <ellipse cx="15" cy="13" rx="13" ry="7.5" fill={fill} stroke={stroke} strokeWidth="2.2" />
            <ellipse cx="15" cy="13" rx="7" ry="3.5" fill="var(--c-teal)" stroke={stroke} strokeWidth="1.6" />
            <line x1="15" y1="9.7" x2="15" y2="16.3" stroke={active ? 'var(--c-cream)' : 'var(--c-ink)'} strokeWidth="1" />
            <circle cx="15" cy="13" r="1.4" fill="none" stroke={active ? 'var(--c-cream)' : 'var(--c-ink)'} strokeWidth="1" />
            <line x1="3" y1="9" x2="3" y2="3" stroke={stroke} strokeWidth="1.8" />
            <rect x="1" y="1" width="4" height="3" fill={active ? 'var(--c-yel)' : 'var(--c-red)'} stroke={stroke} strokeWidth="1.2" />
            <line x1="27" y1="9" x2="27" y2="3" stroke={stroke} strokeWidth="1.8" />
            <rect x="25" y="1" width="4" height="3" fill={active ? 'var(--c-yel)' : 'var(--c-red)'} stroke={stroke} strokeWidth="1.2" />
        </svg>
    );
}

export function NavVS({ active = false }) {
    const stroke = active ? 'var(--c-yel)' : 'var(--c-ink)';
    const accent = active ? 'var(--c-red)' : 'var(--c-yel)';
    return (
        <svg width="30" height="22" viewBox="0 0 30 22" fill="none">
            <path
                d="M15 0 L18 5 L24 3 L22 8 L28 9 L23 13 L27 18 L20 18 L19 22 L15 18 L11 22 L10 18 L3 18 L7 13 L2 9 L8 8 L6 3 L12 5 Z"
                fill={accent} stroke={stroke} strokeWidth="2" strokeLinejoin="round"
            />
            <text
                x="15" y="15"
                textAnchor="middle"
                fontFamily="Bungee, sans-serif"
                fontSize="9"
                fontWeight="700"
                fill={active ? 'var(--c-cream)' : 'var(--c-ink)'}
                letterSpacing="0.5"
            >
                VS
            </text>
        </svg>
    );
}

export function NavTrophy({ active = false }) {
    const stroke = active ? 'var(--c-yel)' : 'var(--c-ink)';
    const cup    = 'var(--c-yel)';
    return (
        <svg width="22" height="24" viewBox="0 0 22 24" fill="none">
            <path d="M5 4 C 1 4, 1 11, 6 11" stroke={stroke} strokeWidth="1.8" fill="none" strokeLinecap="round" />
            <path d="M17 4 C 21 4, 21 11, 16 11" stroke={stroke} strokeWidth="1.8" fill="none" strokeLinecap="round" />
            <path
                d="M5 2 H17 V10 C 17 14, 14 16, 11 16 C 8 16, 5 14, 5 10 Z"
                fill={cup} stroke={stroke} strokeWidth="2" strokeLinejoin="round"
            />
            <path
                d="M11 6 L12 8.5 L14.5 8.5 L12.5 10 L13 12.5 L11 11 L9 12.5 L9.5 10 L7.5 8.5 L10 8.5 Z"
                fill={active ? 'var(--c-red)' : 'var(--c-ink)'}
            />
            <rect x="9.5" y="16" width="3" height="3" fill={cup} stroke={stroke} strokeWidth="1.5" />
            <rect x="6" y="19" width="10" height="2.5" fill={cup} stroke={stroke} strokeWidth="1.5" />
            <rect x="4.5" y="21.5" width="13" height="2" fill={stroke} />
        </svg>
    );
}

export function NavFire({ active = false }) {
    const stroke = active ? 'var(--c-yel)' : 'var(--c-ink)';
    return (
        <svg width="20" height="24" viewBox="0 0 20 24" fill="none">
            <path
                d="M10 1 C 6 5, 4 8, 4 12 C 4 18, 7 22, 10 22 C 13 22, 16 18, 16 12 C 16 9, 14 7, 12 5 C 12 8, 11 9, 10 9 C 10 7, 10 4, 10 1 Z"
                fill="var(--c-red)" stroke={stroke} strokeWidth="2" strokeLinejoin="round"
            />
            <path
                d="M10 9 C 8 11, 7 13, 7 16 C 7 19, 8 21, 10 21 C 12 21, 13 19, 13 16 C 13 14, 12 12, 11 11 C 11 13, 10 14, 10 14 C 10 12, 10 11, 10 9 Z"
                fill="var(--c-yel)" stroke={stroke} strokeWidth="1.4" strokeLinejoin="round"
            />
        </svg>
    );
}
```

- [ ] **Step 9.4: Correr todos los tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `68 tests passed` (59 + 9 NavIcons).

- [ ] **Step 9.5: Commit**

```bash
git add resources/js/Components/icons/NavIcons.jsx resources/js/tests/icons/NavIcons.test.jsx
git commit -m "feat: add NavIcons (Stadium, VS, Trophy, Fire) for TabBar"
```

---

## Verificación visual (post-implementación)

Agregar temporalmente en `Dashboard.jsx` para verificar en browser:

```jsx
import { Trophy, SoccerBall, Jersey, Boot, Whistle, Stadium, GoalNet, Mark26, HostStrip, Pennant, PitchSwoosh } from '@/Components/icons/football';
import { NavStadium, NavVS, NavTrophy, NavFire } from '@/Components/icons/NavIcons';

// dentro del render:
<div className="p-8 bg-cream flex flex-col gap-6">
    <div className="flex gap-4 flex-wrap items-end">
        <Trophy />
        <SoccerBall />
        <Jersey />
        <Boot />
        <Whistle />
        <Stadium />
        <GoalNet />
        <Mark26 />
        <Pennant text="GOL" rotate={-8} />
        <PitchSwoosh width={160} height={60} />
    </div>
    <HostStrip />
    <div className="flex gap-4">
        <NavStadium />         <NavStadium active />
        <NavVS />              <NavVS active />
        <NavTrophy />          <NavTrophy active />
        <NavFire />            <NavFire active />
    </div>
</div>
```

Correr: `./vendor/bin/sail pnpm run dev` y verificar en browser.

Remover el bloque de prueba antes de continuar al Paso 4.
