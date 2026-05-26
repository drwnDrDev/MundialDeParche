# UI Primitivos Pop-Art — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear 5 componentes React primitivos (`Button`, `Chip`, `Cromo`, `Burst`, `Halftone`) en `resources/js/Components/ui/` siguiendo el sistema visual pop-art del handoff de diseño.

**Architecture:** Props de variantes sin librerías externas — cada componente acepta props semánticas (`variant`, `size`, `full`, `color`, etc.) y resuelve clases Tailwind internamente. Sin CSS-in-JS. Sin cva/clsx.

**Tech Stack:** React 18, Tailwind CSS v3, Vitest + React Testing Library (setup en Task 1), Laravel Sail (pnpm).

**Spec:** `docs/superpowers/specs/2026-05-25-ui-primitives-design.md`

---

## File Map

| Archivo | Estado | Responsabilidad |
|---|---|---|
| `vite.config.js` | Modificar | Agregar config de Vitest |
| `resources/js/tests/setup.js` | Crear | Setup de jest-dom matchers |
| `resources/js/Components/ui/Button.jsx` | Crear | Botón pop-art con variantes |
| `resources/js/Components/ui/Chip.jsx` | Crear | Pill badge con variantes |
| `resources/js/Components/ui/Cromo.jsx` | Crear | Card estilo sticker de álbum |
| `resources/js/Components/ui/Burst.jsx` | Crear | Estrella pop-art decorativa |
| `resources/js/Components/ui/Halftone.jsx` | Crear | Wrapper de textura halftone |
| `resources/js/tests/ui/Button.test.jsx` | Crear | Tests de Button |
| `resources/js/tests/ui/Chip.test.jsx` | Crear | Tests de Chip |
| `resources/js/tests/ui/Cromo.test.jsx` | Crear | Tests de Cromo |
| `resources/js/tests/ui/Burst.test.jsx` | Crear | Tests de Burst |
| `resources/js/tests/ui/Halftone.test.jsx` | Crear | Tests de Halftone |

---

## Task 1: Setup Vitest + React Testing Library

**Files:**
- Modify: `vite.config.js`
- Modify: `package.json` (vía pnpm add)
- Create: `resources/js/tests/setup.js`

- [ ] **Step 1.1: Instalar dependencias de testing**

```bash
./vendor/bin/sail pnpm add -D vitest jsdom @testing-library/react @testing-library/jest-dom
```

Salida esperada: paquetes instalados sin errores.

- [ ] **Step 1.2: Agregar config de Vitest a `vite.config.js`**

Reemplazar el contenido completo:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./resources/js/tests/setup.js'],
    },
});
```

- [ ] **Step 1.3: Crear archivo de setup**

Crear `resources/js/tests/setup.js`:

```js
import '@testing-library/jest-dom';
```

- [ ] **Step 1.4: Agregar script de test a `package.json`**

En la sección `"scripts"`, agregar:

```json
"test": "vitest run",
"test:watch": "vitest"
```

- [ ] **Step 1.5: Verificar que Vitest funciona**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `No test files found` o `0 tests passed` sin errores de configuración.

- [ ] **Step 1.6: Commit**

```bash
git add vite.config.js package.json pnpm-lock.yaml resources/js/tests/setup.js
git commit -m "chore: add Vitest + React Testing Library for UI component tests"
```

---

## Task 2: Button

**Files:**
- Create: `resources/js/Components/ui/Button.jsx`
- Create: `resources/js/tests/ui/Button.test.jsx`

- [ ] **Step 2.1: Crear test**

Crear `resources/js/tests/ui/Button.test.jsx`:

```jsx
import { render, screen } from '@testing-library/react';
import Button from '../../Components/ui/Button';

describe('Button', () => {
    it('renders children', () => {
        render(<Button>JUGAR</Button>);
        expect(screen.getByRole('button')).toHaveTextContent('JUGAR');
    });

    it('applies yel variant by default', () => {
        render(<Button>X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-pop-yel');
    });

    it('applies red variant', () => {
        render(<Button variant="red">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-pop-red');
    });

    it('applies teal variant', () => {
        render(<Button variant="teal">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-pop-teal');
    });

    it('applies navy variant', () => {
        render(<Button variant="navy">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-navy');
    });

    it('applies ghost variant', () => {
        render(<Button variant="ghost">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-transparent');
    });

    it('applies lg size', () => {
        render(<Button size="lg">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('text-lg');
    });

    it('applies full width', () => {
        render(<Button full>X</Button>);
        expect(screen.getByRole('button')).toHaveClass('w-full');
    });

    it('passes through native props', () => {
        render(<Button type="submit" disabled>X</Button>);
        const btn = screen.getByRole('button');
        expect(btn).toHaveAttribute('type', 'submit');
        expect(btn).toBeDisabled();
    });

    it('merges className', () => {
        render(<Button className="mt-4">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('mt-4');
    });
});
```

- [ ] **Step 2.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/ui/Button'`.

- [ ] **Step 2.3: Crear `Button.jsx`**

Crear `resources/js/Components/ui/Button.jsx`:

```jsx
const VARIANTS = {
    yel:   'bg-pop-yel text-ink',
    red:   'bg-pop-red text-white',
    teal:  'bg-pop-teal text-white',
    navy:  'bg-navy text-cream',
    ghost: 'bg-transparent border-transparent shadow-none',
};

const SIZES = {
    md: 'px-4 py-2.5 text-sm',
    lg: 'px-[26px] py-[18px] text-lg',
};

export default function Button({
    variant = 'yel',
    size = 'md',
    full = false,
    className = '',
    children,
    ...props
}) {
    return (
        <button
            className={[
                'font-display uppercase tracking-[.01em] leading-none',
                'border-2.5 border-ink shadow-pop-md rounded-none',
                'transition-transform',
                'active:translate-x-[3px] active:translate-y-[3px] active:shadow-pop-sm',
                'disabled:opacity-50 disabled:pointer-events-none',
                VARIANTS[variant] ?? VARIANTS.yel,
                SIZES[size] ?? SIZES.md,
                full ? 'w-full flex justify-center' : '',
                className,
            ].join(' ')}
            {...props}
        >
            {children}
        </button>
    );
}
```

- [ ] **Step 2.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `10 tests passed`.

- [ ] **Step 2.5: Commit**

```bash
git add resources/js/Components/ui/Button.jsx resources/js/tests/ui/Button.test.jsx
git commit -m "feat: add Button primitive (pop-art variants)"
```

---

## Task 3: Chip

**Files:**
- Create: `resources/js/Components/ui/Chip.jsx`
- Create: `resources/js/tests/ui/Chip.test.jsx`

- [ ] **Step 3.1: Crear test**

Crear `resources/js/tests/ui/Chip.test.jsx`:

```jsx
import { render, screen } from '@testing-library/react';
import Chip from '../../Components/ui/Chip';

describe('Chip', () => {
    it('renders children', () => {
        render(<Chip>EN VIVO</Chip>);
        expect(screen.getByText('EN VIVO')).toBeInTheDocument();
    });

    it('applies white variant by default', () => {
        const { container } = render(<Chip>X</Chip>);
        expect(container.firstChild).toHaveClass('bg-white');
    });

    it('applies red variant', () => {
        const { container } = render(<Chip variant="red">X</Chip>);
        expect(container.firstChild).toHaveClass('bg-pop-red');
    });

    it('applies yel variant', () => {
        const { container } = render(<Chip variant="yel">X</Chip>);
        expect(container.firstChild).toHaveClass('bg-pop-yel');
    });

    it('applies teal variant', () => {
        const { container } = render(<Chip variant="teal">X</Chip>);
        expect(container.firstChild).toHaveClass('bg-pop-teal');
    });

    it('applies navy variant', () => {
        const { container } = render(<Chip variant="navy">X</Chip>);
        expect(container.firstChild).toHaveClass('bg-navy');
    });

    it('merges className', () => {
        const { container } = render(<Chip className="mt-2">X</Chip>);
        expect(container.firstChild).toHaveClass('mt-2');
    });
});
```

- [ ] **Step 3.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/ui/Chip'`.

- [ ] **Step 3.3: Crear `Chip.jsx`**

Crear `resources/js/Components/ui/Chip.jsx`:

```jsx
const VARIANTS = {
    white: 'bg-white text-ink',
    red:   'bg-pop-red text-white',
    yel:   'bg-pop-yel text-ink',
    teal:  'bg-pop-teal text-ink',
    navy:  'bg-navy text-cream',
};

export default function Chip({ variant = 'white', className = '', children }) {
    return (
        <span
            className={[
                'inline-flex items-center gap-1',
                'rounded-full border-2 border-ink',
                'font-mono text-xs uppercase tracking-[.04em]',
                'px-2.5 py-0.5',
                VARIANTS[variant] ?? VARIANTS.white,
                className,
            ].join(' ')}
        >
            {children}
        </span>
    );
}
```

- [ ] **Step 3.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `17 tests passed`.

- [ ] **Step 3.5: Commit**

```bash
git add resources/js/Components/ui/Chip.jsx resources/js/tests/ui/Chip.test.jsx
git commit -m "feat: add Chip primitive (pill badge)"
```

---

## Task 4: Cromo

**Files:**
- Create: `resources/js/Components/ui/Cromo.jsx`
- Create: `resources/js/tests/ui/Cromo.test.jsx`

- [ ] **Step 4.1: Crear test**

Crear `resources/js/tests/ui/Cromo.test.jsx`:

```jsx
import { render, screen } from '@testing-library/react';
import Cromo from '../../Components/ui/Cromo';

describe('Cromo', () => {
    it('renders children', () => {
        render(<Cromo><p>Contenido</p></Cromo>);
        expect(screen.getByText('Contenido')).toBeInTheDocument();
    });

    it('has pop-xl shadow class', () => {
        const { container } = render(<Cromo>X</Cromo>);
        expect(container.firstChild).toHaveClass('shadow-pop-xl');
    });

    it('does not render corner label when prop is absent', () => {
        render(<Cromo>X</Cromo>);
        expect(screen.queryByTestId('cromo-corner')).not.toBeInTheDocument();
    });

    it('renders corner label when prop is provided', () => {
        render(<Cromo corner="GRUPO A">X</Cromo>);
        expect(screen.getByTestId('cromo-corner')).toHaveTextContent('GRUPO A');
    });

    it('merges className', () => {
        const { container } = render(<Cromo className="p-4">X</Cromo>);
        expect(container.firstChild).toHaveClass('p-4');
    });
});
```

- [ ] **Step 4.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/ui/Cromo'`.

- [ ] **Step 4.3: Crear `Cromo.jsx`**

Crear `resources/js/Components/ui/Cromo.jsx`:

```jsx
export default function Cromo({ corner = null, className = '', children }) {
    return (
        <div
            className={[
                'border-[3px] border-ink shadow-pop-xl rounded-[3px]',
                'relative overflow-hidden',
                className,
            ].join(' ')}
        >
            {corner && (
                <span
                    data-testid="cromo-corner"
                    className={[
                        'absolute top-2 right-0',
                        'bg-pop-red text-white',
                        'font-display text-[10px] uppercase',
                        'px-2 py-0.5 border-l-2 border-b-2 border-ink',
                    ].join(' ')}
                >
                    {corner}
                </span>
            )}
            {children}
        </div>
    );
}
```

- [ ] **Step 4.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `22 tests passed`.

- [ ] **Step 4.5: Commit**

```bash
git add resources/js/Components/ui/Cromo.jsx resources/js/tests/ui/Cromo.test.jsx
git commit -m "feat: add Cromo primitive (pop-art sticker card)"
```

---

## Task 5: Burst

**Files:**
- Create: `resources/js/Components/ui/Burst.jsx`
- Create: `resources/js/tests/ui/Burst.test.jsx`

- [ ] **Step 5.1: Crear test**

Crear `resources/js/tests/ui/Burst.test.jsx`:

```jsx
import { render, screen } from '@testing-library/react';
import Burst from '../../Components/ui/Burst';

describe('Burst', () => {
    it('renders children', () => {
        render(<Burst>¡GOOOL!</Burst>);
        expect(screen.getByText('¡GOOOL!')).toBeInTheDocument();
    });

    it('applies yel color by default', () => {
        const { container } = render(<Burst>X</Burst>);
        // inner layer carries the color class
        const inner = container.querySelector('[data-burst-inner]');
        expect(inner).toHaveClass('bg-pop-yel');
    });

    it('applies red color', () => {
        const { container } = render(<Burst color="red">X</Burst>);
        const inner = container.querySelector('[data-burst-inner]');
        expect(inner).toHaveClass('bg-pop-red');
    });

    it('applies teal color', () => {
        const { container } = render(<Burst color="teal">X</Burst>);
        const inner = container.querySelector('[data-burst-inner]');
        expect(inner).toHaveClass('bg-pop-teal');
    });

    it('applies md size by default', () => {
        const { container } = render(<Burst>X</Burst>);
        expect(container.firstChild).toHaveClass('w-20');
    });

    it('applies sm size', () => {
        const { container } = render(<Burst size="sm">X</Burst>);
        expect(container.firstChild).toHaveClass('w-12');
    });

    it('applies lg size', () => {
        const { container } = render(<Burst size="lg">X</Burst>);
        expect(container.firstChild).toHaveClass('w-28');
    });

    it('applies rotation via inline style', () => {
        const { container } = render(<Burst rotate={14}>X</Burst>);
        expect(container.firstChild).toHaveStyle({ transform: 'rotate(14deg)' });
    });

    it('merges className', () => {
        const { container } = render(<Burst className="absolute top-2">X</Burst>);
        expect(container.firstChild).toHaveClass('absolute');
    });
});
```

- [ ] **Step 5.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/ui/Burst'`.

- [ ] **Step 5.3: Crear `Burst.jsx`**

Crear `resources/js/Components/ui/Burst.jsx`:

```jsx
const STAR_CLIP = 'polygon(50% 0%, 54% 8%, 60% 4%, 61% 12%, 68% 9%, 67% 17%, 75% 16%, 72% 24%, 80% 25%, 75% 32%, 83% 35%, 76% 41%, 84% 46%, 76% 50%, 83% 55%, 75% 58%, 80% 65%, 72% 66%, 75% 74%, 67% 73%, 68% 81%, 61% 78%, 60% 86%, 54% 82%, 50% 90%, 46% 82%, 40% 86%, 39% 78%, 32% 81%, 33% 73%, 25% 74%, 28% 66%, 20% 65%, 25% 58%, 17% 55%, 24% 50%, 17% 46%, 25% 41%, 17% 35%, 24% 32%, 20% 25%, 28% 24%, 25% 16%, 33% 17%, 32% 9%, 39% 12%, 40% 4%, 46% 8%)';

const COLORS = {
    yel:  { bg: 'bg-pop-yel', text: 'text-ink' },
    red:  { bg: 'bg-pop-red', text: 'text-white' },
    teal: { bg: 'bg-pop-teal', text: 'text-ink' },
};

const SIZES = {
    sm: { outer: 'w-12 h-12', text: 'text-[10px]' },
    md: { outer: 'w-20 h-20', text: 'text-xs' },
    lg: { outer: 'w-28 h-28', text: 'text-sm' },
};

export default function Burst({
    color = 'yel',
    size = 'md',
    rotate = 0,
    className = '',
    children,
}) {
    const c = COLORS[color] ?? COLORS.yel;
    const s = SIZES[size] ?? SIZES.md;

    return (
        <div
            className={['relative flex items-center justify-center', s.outer, className].join(' ')}
            style={{ transform: rotate ? `rotate(${rotate}deg)` : undefined }}
        >
            {/* Capa ink (exterior) */}
            <div
                className="absolute inset-0 bg-ink"
                style={{ clipPath: STAR_CLIP }}
            />
            {/* Capa color (interior, ~94% escala) */}
            <div
                data-burst-inner
                className={['absolute', c.bg].join(' ')}
                style={{
                    clipPath: STAR_CLIP,
                    inset: '3%',
                }}
            />
            {/* Texto */}
            <span
                className={[
                    'relative z-10 font-display uppercase text-center leading-tight',
                    c.text,
                ].join(' ')}
            >
                {children}
            </span>
        </div>
    );
}
```

- [ ] **Step 5.4: Correr tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `31 tests passed`.

- [ ] **Step 5.5: Commit**

```bash
git add resources/js/Components/ui/Burst.jsx resources/js/tests/ui/Burst.test.jsx
git commit -m "feat: add Burst primitive (24-point pop-art star)"
```

---

## Task 6: Halftone

**Files:**
- Create: `resources/js/Components/ui/Halftone.jsx`
- Create: `resources/js/tests/ui/Halftone.test.jsx`

- [ ] **Step 6.1: Crear test**

Crear `resources/js/tests/ui/Halftone.test.jsx`:

```jsx
import { render, screen } from '@testing-library/react';
import Halftone from '../../Components/ui/Halftone';

describe('Halftone', () => {
    it('renders children', () => {
        render(<Halftone><p>Contenido</p></Halftone>);
        expect(screen.getByText('Contenido')).toBeInTheDocument();
    });

    it('wrapper is relative', () => {
        const { container } = render(<Halftone>X</Halftone>);
        expect(container.firstChild).toHaveClass('relative');
    });

    it('overlay has pointer-events-none', () => {
        const { container } = render(<Halftone>X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('pointer-events-none');
    });

    it('applies ink texture by default', () => {
        const { container } = render(<Halftone>X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone');
    });

    it('applies red texture', () => {
        const { container } = render(<Halftone color="red">X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone-red');
    });

    it('applies yel texture', () => {
        const { container } = render(<Halftone color="yel">X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone-yel');
    });

    it('applies teal texture', () => {
        const { container } = render(<Halftone color="teal">X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone-teal');
    });

    it('applies navy texture', () => {
        const { container } = render(<Halftone color="navy">X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone-navy');
    });

    it('merges className on wrapper', () => {
        const { container } = render(<Halftone className="p-6">X</Halftone>);
        expect(container.firstChild).toHaveClass('p-6');
    });
});
```

- [ ] **Step 6.2: Correr test para confirmar que falla**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: error `Cannot find module '../../Components/ui/Halftone'`.

- [ ] **Step 6.3: Crear `Halftone.jsx`**

Crear `resources/js/Components/ui/Halftone.jsx`:

```jsx
const COLOR_MAP = {
    ink:  'halftone',
    red:  'halftone-red',
    yel:  'halftone-yel',
    teal: 'halftone-teal',
    navy: 'halftone-navy',
};

export default function Halftone({ color = 'ink', className = '', children }) {
    return (
        <div className={['relative', className].join(' ')}>
            <div
                data-halftone-overlay
                className={[
                    'absolute inset-0 pointer-events-none',
                    COLOR_MAP[color] ?? COLOR_MAP.ink,
                ].join(' ')}
            />
            {children}
        </div>
    );
}
```

- [ ] **Step 6.4: Correr todos los tests y confirmar que pasan**

```bash
./vendor/bin/sail pnpm test
```

Salida esperada: `40 tests passed`.

- [ ] **Step 6.5: Commit final**

```bash
git add resources/js/Components/ui/Halftone.jsx resources/js/tests/ui/Halftone.test.jsx
git commit -m "feat: add Halftone primitive (Ben-Day dots overlay wrapper)"
```

---

## Verificación visual (post-implementación)

Después de completar los 6 tasks, verificar visualmente en el browser antes de continuar con Paso 3 (íconos SVG).

Agregar temporalmente en cualquier página existente (ej. `Dashboard.jsx`) un bloque de prueba:

```jsx
import Button from '@/Components/ui/Button';
import Chip from '@/Components/ui/Chip';
import Cromo from '@/Components/ui/Cromo';
import Burst from '@/Components/ui/Burst';
import Halftone from '@/Components/ui/Halftone';

// dentro del render:
<div className="p-8 bg-cream flex flex-col gap-6">
    <div className="flex gap-3 flex-wrap">
        <Button>JUGAR</Button>
        <Button variant="red">CERRAR</Button>
        <Button variant="teal">CONFIRMAR</Button>
        <Button variant="navy">VER MÁS</Button>
        <Button variant="ghost">CANCELAR</Button>
        <Button size="lg" full>DALE, ENTRAR →</Button>
    </div>
    <div className="flex gap-2 flex-wrap">
        <Chip>EN CURSO</Chip>
        <Chip variant="red">EN VIVO</Chip>
        <Chip variant="yel">FASE 1</Chip>
        <Chip variant="teal">✓ GUARDADO</Chip>
        <Chip variant="navy">BLOQUEADO</Chip>
    </div>
    <Cromo corner="GRUPO A" className="p-4 bg-white w-48">
        <p className="font-body text-sm">Colombia vs Brasil</p>
    </Cromo>
    <div className="flex gap-4">
        <Burst>¡GOOOL!</Burst>
        <Burst color="red" rotate={14}>EN VIVO</Burst>
        <Burst color="teal" size="lg">+10 PTS</Burst>
    </div>
    <Halftone color="yel" className="p-4 bg-navy rounded">
        <p className="font-display text-cream text-xl relative z-10">MUNDIAL 2026</p>
    </Halftone>
</div>
```

Correr: `./vendor/bin/sail pnpm run dev` y abrir el dashboard en el browser.

Remover el bloque de prueba antes de continuar al Paso 3.
