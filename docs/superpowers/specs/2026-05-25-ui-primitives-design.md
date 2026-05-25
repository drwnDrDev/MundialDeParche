# Spec: UI Primitivos — Sistema Pop-Art

**Fecha:** 2026-05-25
**Proyecto:** PollaMundial
**Alcance:** Paso 2 del handoff de diseño — 5 componentes primitivos base

---

## Contexto

El handoff de diseño (`design_handoff_mundial_parche/`) define un sistema visual pop-art para la UI del usuario (no admin). Antes de portar las 20 pantallas, se construyen los primitivos que todos los compuestos y pantallas consumen.

Los tokens de diseño (colores, fuentes, sombras, texturas) ya fueron integrados en `tailwind.config.js` y `resources/css/app.css` en el Paso 1.

---

## Ubicación

```
resources/js/Components/ui/
├── Button.jsx
├── Chip.jsx
├── Cromo.jsx
├── Burst.jsx
└── Halftone.jsx
```

Los componentes Breeze existentes (`PrimaryButton`, `TextInput`, etc.) en `resources/js/Components/` no se tocan.

---

## Decisiones de diseño

- **Props de variantes** (sin `cva` ni `clsx`): cada componente acepta props semánticas y resuelve las clases Tailwind internamente con un mapa de variantes.
- **Sin border-radius** salvo excepciones explícitas: avatares circulares (`rounded-full`), chips/pills (`rounded-full`), inputs (`rounded-[2px]`). El resto: `rounded-none` o `rounded-[3px]`.
- **Sombras pop-art**: bloques sólidos sin blur, definidos en `tailwind.config.js` como `shadow-pop-sm` → `shadow-pop-cta`.
- **Borde ink**: `border-2.5 border-ink` en botones, `border-[3px] border-ink` en cromos.
- **Tipografía**: texto de botones y etiquetas en `font-display uppercase`.
- **Spread de props nativos**: todos los componentes aceptan `className` para extensión y pasan `...props` al elemento raíz cuando corresponde.

---

## Componentes

### 1. `Button`

Elemento raíz: `<button>`.

**Props:**

| Prop | Tipo | Default | Valores |
|---|---|---|---|
| `variant` | string | `'yel'` | `'yel'` \| `'red'` \| `'teal'` \| `'navy'` \| `'ghost'` |
| `size` | string | `'md'` | `'md'` \| `'lg'` |
| `full` | bool | `false` | — |
| `className` | string | `''` | — |
| `...props` | — | — | `onClick`, `type`, `disabled`, etc. |

**Estilos base (todas las variantes):**
```
font-display uppercase tracking-[.01em] leading-none
border-2.5 border-ink shadow-pop-md rounded-none
transition-transform
active:translate-x-[3px] active:translate-y-[3px] active:shadow-pop-sm
disabled:opacity-50 disabled:pointer-events-none
```

**Mapa de variantes (`variant`):**
```
yel   → bg-pop-yel text-ink
red   → bg-pop-red text-white
teal  → bg-pop-teal text-white
navy  → bg-navy text-cream
ghost → bg-transparent border-transparent shadow-none
```

**Mapa de tamaños (`size`):**
```
md → px-4 py-2.5 text-sm
lg → px-[26px] py-[18px] text-lg
```

**`full`:** añade `w-full flex justify-center`.

---

### 2. `Chip`

Elemento raíz: `<span>`.

Pill con borde ink. Usado como badge de estado, label de categoría, indicador de fase.

**Props:**

| Prop | Tipo | Default | Valores |
|---|---|---|---|
| `variant` | string | `'white'` | `'white'` \| `'red'` \| `'yel'` \| `'teal'` \| `'navy'` |
| `className` | string | `''` | — |
| `children` | node | — | — |

**Estilos base:**
```
inline-flex items-center gap-1
rounded-full border-2 border-ink
font-mono text-xs uppercase tracking-[.04em]
px-2.5 py-0.5
```

**Mapa de variantes:**
```
white → bg-white text-ink
red   → bg-pop-red text-white
yel   → bg-pop-yel text-ink
teal  → bg-pop-teal text-ink
navy  → bg-navy text-cream
```

---

### 3. `Cromo`

Elemento raíz: `<div>`.

Card estilo sticker de álbum del mundial. Todos los componentes destacados (partidos, predicciones, ranking) se construyen sobre Cromo.

**Props:**

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `corner` | string | `null` | Texto del label de esquina (ej. `"GRUPO A"`) |
| `className` | string | `''` | — |
| `children` | node | — | — |

**Estilos base:**
```
border-[3px] border-ink shadow-pop-xl rounded-[3px]
relative overflow-hidden
```

**Corner label** (si `corner` está presente):
```
absolute top-2 right-0
bg-pop-red text-white
font-display text-[10px] uppercase
px-2 py-0.5 border-l-2 border-b-2 border-ink
rotate-0  (sin rotación en el label; el caller puede rotarlo si quiere)
```

---

### 4. `Burst`

Elemento raíz: `<div>`.

Figura de estrella de 24 puntas, doble capa (ink detrás + color adelante). Usado para destacar texto corto ("¡GOOOL!", "+10 PTS", etc.).

**Props:**

| Prop | Tipo | Default | Valores |
|---|---|---|---|
| `color` | string | `'yel'` | `'yel'` \| `'red'` \| `'teal'` |
| `size` | string | `'md'` | `'sm'` \| `'md'` \| `'lg'` |
| `rotate` | number | `0` | Grados de rotación (ej. `14`) |
| `className` | string | `''` | — |
| `children` | node | — | — |

**Implementación:**
- Capa exterior: `<div>` con `bg-ink` + `clip-path` de estrella
- Capa interior: `<div>` con color de variante + `clip-path` de estrella, levemente más pequeña (escala ~94%)
- Texto: `<span>` centrado con `font-display uppercase text-center leading-tight`

**Clip-path estrella 24 puntas** (definido como constante en el archivo):
```js
const STAR_CLIP = 'polygon(50% 0%, 54% 8%, 60% 4%, 61% 12%, 68% 9%, 67% 17%, 75% 16%, 72% 24%, 80% 25%, 75% 32%, 83% 35%, 76% 41%, 84% 46%, 76% 50%, 83% 55%, 75% 58%, 80% 65%, 72% 66%, 75% 74%, 67% 73%, 68% 81%, 61% 78%, 60% 86%, 54% 82%, 50% 90%, 46% 82%, 40% 86%, 39% 78%, 32% 81%, 33% 73%, 25% 74%, 28% 66%, 20% 65%, 25% 58%, 17% 55%, 24% 50%, 17% 46%, 25% 41%, 17% 35%, 24% 32%, 20% 25%, 28% 24%, 25% 16%, 33% 17%, 32% 9%, 39% 12%, 40% 4%, 46% 8%)'
```

**Mapa de tamaños:**
```
sm → w-12 h-12  text-[10px]
md → w-20 h-20  text-xs
lg → w-28 h-28  text-sm
```

**Mapa de colores (capa frontal):**
```
yel  → bg-pop-yel  text-ink
red  → bg-pop-red  text-white
teal → bg-pop-teal text-ink
```

---

### 5. `Halftone`

Elemento raíz: `<div className="relative">`.

Wrapper que inyecta un overlay de puntos Ben-Day sobre su contenido. No impone dimensiones ni padding.

**Props:**

| Prop | Tipo | Default | Valores |
|---|---|---|---|
| `color` | string | `'ink'` | `'ink'` \| `'red'` \| `'yel'` \| `'teal'` \| `'navy'` |
| `className` | string | `''` | Clases para el wrapper exterior |
| `children` | node | — | — |

**Implementación:**
```jsx
<div className={`relative ${className}`}>
  <div className={`absolute inset-0 pointer-events-none ${colorMap[color]}`} />
  {children}
</div>
```

**Mapa de colores** (clases de `app.css`):
```
ink   → halftone
red   → halftone-red
yel   → halftone-yel
teal  → halftone-teal
navy  → halftone-navy
```

---

## Lo que NO cubre este spec

- Íconos SVG de fútbol (`football-graphics`) — Paso 3
- Componentes compuestos (`MatchCard`, `PodiumStep`, `TabBar`, etc.) — Paso 4
- Pantallas completas — Paso 5
- Restyling de páginas existentes (`Dashboard`, `Chat`, `Ranking`, etc.) — Planes 6/7

---

## Archivos de referencia

- Handoff completo: `C:\Users\dwndz\OneDrive\Escritorio\Mundial de parche_\design_handoff_mundial_parche\`
- Tokens Tailwind: `tailwind.config.js`
- Texturas CSS: `resources/css/app.css`
- Bits del prototipo: `bits.jsx` en el handoff (referencia estructural, no copiar directamente)
