# Paso 5: Screens/Views — Design Spec

**Fecha:** 2026-05-26
**Scope:** Opción B — 7 pantallas nuevas/refactorizadas (Home ya completado en `98bca73`)
**Referencia handoff:** `/mnt/c/Users/dwndz/OneDrive/Escritorio/Mundial de parche_/design_handoff_mundial_parche/`
**Tracker de progreso:** `docs/superpowers/ui-screens-progress.md`

---

## Objetivo

Portar 7 pantallas al design system pop-art del handoff, usando los compuestos del Paso 4. Cada pantalla conserva su lógica de negocio existente; solo se reemplaza la capa visual. Se crean 4 pantallas nuevas (Matches, Splash, Locked, MobileShell) y se refactorizan 4 existentes (Ranking, Chat, Login, Round).

---

## Stack técnico

- React 18 + Inertia.js v2
- Tailwind CSS v3 con tokens custom (`pop-yel`, `pop-red`, `pop-teal`, `ink`, `navy`, `cream`)
- Compuestos disponibles en `@/Components/composed`
- Íconos en `@/Components/icons/football` y `@/Components/icons/NavIcons`
- Laravel Sail, pnpm

---

## Arquitectura general

### MobileShell — shell compartido

Componente presentacional `resources/js/Components/MobileShell.jsx`. Reemplaza `AuthenticatedLayout` en todas las pantallas de usuario.

**Responsabilidad única:** proveer base visual (`bg-cream min-h-screen overflow-x-hidden pb-28`). Nada más. Cada página controla su `<Head>`, header y contenido.

```jsx
export default function MobileShell({ children }) {
    return (
        <div className="bg-cream min-h-screen overflow-x-hidden pb-28 relative">
            {children}
        </div>
    );
}
```

### TabBar — navegación Inertia

`TabBar.jsx` se actualiza para manejar navegación internamente:

- Importa `router` de `@inertiajs/react`
- Map interno fijo: `{ home: '/dashboard', matches: '/matches', rank: '/ranking', chat: '/chat' }`
- Al presionar un tab inactivo: `router.visit(url)`
- Al presionar el tab activo: no-op
- La prop `active` sigue siendo string para el highlight visual

La firma del componente no cambia (`active` prop, mismos tabs). No hay callbacks.

### Rutas nuevas/modificadas (`routes/web.php`)

```php
// Splash pública (reemplaza la Welcome de Breeze)
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : inertia('Splash');
})->name('home');

// Partidos
Route::get('/matches', [MatchesController::class, 'index'])
    ->middleware(['auth'])
    ->name('matches');
```

`PredictionController@show`: si `!$round->is_open && !$round->is_locked`, renderiza `Predictions/Locked` con props de la ronda. Si locked (predicciones cerradas permanentemente), también renderiza `Predictions/Locked`.

---

## Pantallas

### 1. Ranking (`Pages/Ranking.jsx`)

**Controller existente:** `RankingController@index`
**Props esperadas del controller:**
```php
[
    'users' => User::select(['id','name','total_points','avatar_color'])
                   ->where('is_active', true)
                   ->orderByDesc('total_points')
                   ->get(),
    'me'    => auth()->id(),
    'pozo'  => [
        'total'    => '4.200K',   // formateado
        'players'  => 84,
        'prize1'   => '2.940K',
        'prize2'   => '1.260K',
    ],
]
```

**Estado derivado en React:**
```js
const allTied = users.every(u => u.total_points === 0);
```

**Layout:**
```
MobileShell
  halftone-red absoluto top-right (decoración)
  Header
    "RANKING" display 36px yellow WebkitTextStroke 1.5px ink + textShadow red
    "POR PUNTOS · N JUGADORES" mono 11px
    Trophy size=40 rotado 8° (absoluto top-right del header)
  PozoCard (recibe props de 'pozo')
  [si allTied]
    AllTiedHero — componente interno de la página
  [si !allTied]
    Podium: orden visual 2°-1°-3° (PodiumStep del Paso 4)
  SectionHead "TODOS EN CERO" o "LOS DEMÁS"
  Lista scrollable RankRow[]  ← desde pos 4, o todos si allTied
  TabBar active="rank"
```

**AllTiedHero (componente interno):** card `bg-pop-yel border-[3px] border-ink shadow-[5px_5px_0_var(--c-ink)]`, cluster de 6 avatares + "+N" pill negro/amarillo, texto "N ARRANCAN / EMPATADOS" display (EMPATADOS con WebkitTextStroke), body text explicativo. SoccerBall decorativo bottom-right.

**Podium:** PodiumStep ya está implementado. El orden DOM es: `place=2` izquierda, `place=1` centro (con `crown`), `place=3` derecha. Los datos del podium se derivan de `users.slice(0, 3)`.

**RankRows:** componente ya implementado en Paso 4. Props del array `users` mapeadas a `{ position, name, pts, delta, isYou, tiedCount }`. `delta` se deriva client-side como "+0" para allTied o del campo `delta` si el controller lo provee.

**Nota:** avatarColor se asigna en el controller o se deriva por posición con un array de colores predefinidos.

---

### 2. Chat (`Pages/Chat.jsx`)

**Controller existente:** `ChatController@index`
**Props esperadas (ya existen, posible extensión):**
```php
[
    'messages'    => Message::with('user')->latest()->take(50)->get()->reverse()->values(),
    'me'          => auth()->id(),
    'liveMatch'   => null, // Fixture live si existe: {teamA, teamB, scoreA, scoreB, minute}
]
```

**Lógica Echo preservada integralmente.** Solo cambia el JSX.

**Layout:**
```
MobileShell (pero sin pb-28 propio — el layout del Chat es full-height)
  Header amarillo
    halftone overlay opacity-15
    Avatar cuadrado rojo border-2.5 ink con SoccerBall size=22 dentro
    "EL PARCHE" display 18px
    "● N conectados" mono 10px opacity-75
    Botón "⋯" cuadrado 32px
  [si liveMatch]
    Banner navy: dot rojo pulsante + "EN VIVO" pixel yel + teamA score - score teamB + minuto
  Lista mensajes (overflow-y-auto, flex-col gap-12, pb-24)
    ChatBubble[] (componente del Paso 4)
    System messages: pill teal centrado (inline chip)
  Input bar (fija bottom: 78px desde abajo, encima del TabBar)
    Botón "+" 40×40 bg-pop-yel border-2.5 ink shadow-pop-sm
    Input bg-white border-2.5 ink shadow-pop-sm placeholder "Escribí algo, parcero…"
    Botón "▶" 44×44 bg-pop-red text-white border-2.5 ink shadow-pop-sm
  TabBar active="chat"
```

**onlineCount:** obtenido del canal Presence de Echo (`.here()` da los usuarios actuales). Si el canal no provee esta info en el contexto actual, usar el total de `users activos` del controller como fallback.

**liveMatch:** si el controller no lo provee en esta fase, se muestra `null` (el banner no aparece). Se puede añadir en iteración futura.

---

### 3. Partidos — nueva pantalla (`Pages/Matches.jsx`)

**Nuevo controller:** `app/Http/Controllers/MatchesController.php`

**Props del controller:**
```php
[
    'matchDays' => [
        [
            'date'    => 'MIÉ 11 JUN',    // formateado en español
            'dateKey' => '2026-06-11',     // para comparar con hoy
            'live'    => true,             // si algún match del día está live
            'matches' => [
                [
                    'id'       => 1,
                    'time'     => '13:00',
                    'teamA'    => 'COL',  'teamB'    => 'BRA',
                    'flagUrlA' => '...',  'flagUrlB' => '...',
                    'scoreA'   => 1,      'scoreB'   => 0,
                    'status'   => 'live', // live | ft | upcoming
                    'minute'   => "43'",
                    'group'    => 'D',
                    'venue'    => 'MIAMI',
                    'myPick'   => '2-1',  // null si no predijo
                    'myPts'    => null,   // int si FT y ya hay puntos
                ],
                ...
            ],
        ],
        ...
    ],
    'groups' => [
        [
            'id'    => 'A',
            'teams' => [
                [
                    'name'    => 'MÉXICO',
                    'flagUrl' => '...',
                    'pj' => 1, 'g' => 1, 'e' => 0, 'p' => 0,
                    'gf' => 2, 'gc' => 1, 'pts' => 3,
                    'live' => false,
                ],
                ...
            ],
        ],
        ...
    ],
    'currentRound' => ['name' => 'Grupos', 'totalMatches' => 72],
]
```

**Layout:**
```
MobileShell
  halftone-teal absoluto top-right (decoración)
  Header
    "WC 2026" mono 10px opacity-70
    "PARTIDOS" display 32px red WebkitTextStroke 1.5px ink
    chip navy "FASE 1 · GRUPOS" + "72 partidos" mono
  Toggle CALENDARIO / POSICIONES
    div flex border-2.5 ink shadow-pop
    ViewTab activo: bg-ink text-pop-yel
    ViewTab inactivo: bg-white text-ink
    border-right 2.5px ink entre los dos
  [vista calendar]
    Date strip scroll horizontal: DateChip[]
      Chip activo: bg-pop-red text-white shadow-pop
      Chip inactivo: bg-white shadow-pop-sm
      "HOY" auto-seleccionado al montar si hoy tiene matches
    Contenido scrollable:
      DayBlock[] → MatchCard[] por cada match del día seleccionado
      Footer "· · · N partidos más · · ·"
  [vista standings]
    12 chips grupo A-L scroll horizontal
    Contenido scrollable:
      GroupStandingCard[] (componente Paso 4, todos los grupos)
  TabBar active="matches"
```

**Estado React:** `const [view, setView] = useState('calendar')` + `const [selectedDate, setSelectedDate] = useState(todayKey)`.

**DateChip "HOY":** se auto-selecciona si `matchDays` contiene un día con `dateKey === today`. El label del chip es "HOY" si es hoy, o el día corto (MIÉ, JUE, etc.).

**MatchCard:** usa el componente del Paso 4 con todos los props mapeados.

---

### 4. Mis Goles (`Pages/Predictions/Round.jsx`)

**Props existentes (sin cambio):** `round`, `fixtures`, `predictions`, `submission`.

**Toda la lógica form preservada:** `groupFixtures()`, `initialScores`, `useState` de scores, `handleChange`, submit via Inertia router.

**Nueva pieza: ScoreBoxInput**

Componente interno de la página (no compartido). Mismo visual que ScoreBox, pero funcional:

```jsx
function ScoreBoxInput({ value, onChange, disabled }) {
    return (
        <div className="relative w-[30px] h-[34px] border-2.5 border-ink shadow-pop-sm flex items-center justify-center bg-pop-yel font-display text-[18px] text-ink">
            {value !== null && value !== undefined ? value : '—'}
            <input
                type="number" min="0" max="20"
                value={value ?? ''}
                onChange={e => onChange(parseInt(e.target.value, 10) || 0)}
                disabled={disabled}
                className="absolute inset-0 opacity-0 cursor-pointer"
                aria-label="goles"
            />
        </div>
    );
}
```

**Layout:**
```
MobileShell (sin pb-28 propio, el sticky CTA provee el clearance)
  halftone-yel absoluto top-right
  Header
    "MUNDIAL 2026" mono 10px opacity-70
    "MIS GOLES" display 32px, "GOLES" en red WebkitTextStroke
    Badge teal "✓ ENTRADA 50K PAGA"
    Mark26 size=26 rotado 6° (top-right)
  PhaseLadder — componente interno
    grid 4 cols
    Fase activa: bg-pop-yel border-2.5 ink shadow-pop, barra progreso roja
    Fases locked: opacity-55, border-2.5 ink, emoji 🔒 top-right
  Card navy "FASE EN CURSO"
    "N / 72 goles metidos" + barra yel
    "CIERRE: fecha · POZO: 4.200K"
  3 PointChips (EXACTO +5 / GANADOR +2 / CLASIFICA +3)
    flex gap-6, cada uno: bg-white border-2.5 ink shadow-pop-sm
    label mono 9px + badge coloreado con puntos
  Chips grupos A-L (scroll horizontal)
    Activo: bg-ink text-pop-yel shadow-[3px_3px_0_var(--c-red)]
    Completo: bg-pop-teal text-white + ✓ pill yel
    Default: bg-white
    Cada chip muestra letra del grupo + 4 mini banderas
  GroupPanel (componente interno) para el grupo activo
    corner banner rojo "GRUPO X" + "N/6 GOLES METIDOS"
    "TUS CLASIFICADOS": grid 2×2 con top 2 en bg-pop-yel + "→R32"
    6 MatchPredRows usando ScoreBoxInput en lugar de ScoreBox
  Sticky CTA bar (bottom, encima del TabBar)
    "TU PUNTAJE ACTUAL" mono + "N PTS · #12" display 18px
    Botón rojo "GUARDAR MIS GOLES →"
  TabBar active="matches" (o "home" — depende del flujo de navegación)
```

**Estado de los grupos:** un `useState` para el grupo activo (default: primer grupo de la ronda). Los chips A-L navegan entre grupos mostrando el GroupPanel correspondiente. No hay navegación de Inertia entre grupos — es todo estado React.

**"TUS CLASIFICADOS":** se construye desde `fixtures` del grupo: los 4 equipos únicos del grupo, los 2 primeros marcados como clasificados. En Fase 1 (grupos) aplica. En otras fases no se muestra esta sección.

---

### 5. Splash (`Pages/Splash.jsx`)

**Sin datos del controller.** Pantalla estática.

**Assets en `public/assets/`:** `fifa_cover.png`, `wc26_logo.avif` (copiar desde handoff).

**Layout:**
```
div bg-navy text-cream min-h-screen overflow-hidden relative
  halftone cream absoluto inset (opacidad baja)
  speedlines absoluto inset (CSS utility, opacity-22)
  Círculo FIFA cover
    absoluto top-[70px] left-1/2 -translate-x-1/2
    w-[360px] h-[360px] rounded-full border-[5px] border-ink shadow-[8px_8px_0_var(--c-ink)]
    background-image url('/assets/fifa_cover.png') cover center
  Burst "¡GOOOL!" teal size=86 absoluto top-[78px] right-[14px] rotado 12°
  Trophy size=56 absoluto top-[84px] left-[18px] rotado -10°
  Título (absoluto top-[360px], centrado)
    "MUNDIAL DE" display 30px cream pop-shadow
    "PARCHE" display 68px yel WebkitTextStroke 2.5px ink textShadow 5px ink
    "★ EL JUEGO DEL MUNDIAL ★" font-pixel 20px cream
  HostStrip absoluto bottom-[188px] centrado
  CTA absoluto bottom-[90px] izq/der 24px
    <Link href="/login"> btn full lg yel "ENTRÁ AL PARCHE →"
    <Link href="/login"> mono 12px cream "¿Ya estás dentro? Iniciá sesión"
  SoccerBall size=36 absoluto bottom-[28px] left-[12px]
  chip yel "v1.0 · BETA" absoluto bottom-[36px] right-[14px] rotado -12°
```

**HostStrip:** componente que muestra "🇺🇸 USA · 🇨🇦 CAN · 🇲🇽 MEX" usando los SVGs de `FlagSmall` del Paso 3 (o banderas inline).

**Speedlines:** utility CSS ya definida en `pop-textures.css` (de Paso 1). Si no existe, se agrega.

---

### 6. Login (`Pages/Auth/Login.jsx`)

**Lógica Breeze integralmente preservada:** `useForm`, `post(route('login'))`, `errors`, `reset('password')`.

Se elimina: `<GuestLayout>`, `<InputLabel>`, `<PrimaryButton>`, `<TextInput>` de Breeze en el JSX.
Se conserva: toda la lógica del `submit`, los estados, el `<Head>`.

**Layout:**
```
div className="min-h-screen bg-cream relative overflow-hidden"
  halftone-red absoluto top-0 left-0 w-[220px] h-[220px]
  halftone-teal absoluto bottom-0 right-0 w-[260px] h-[260px]
  PitchSwoosh absoluto bottom-0 izq/der opacity-85 (SVG ícono del Paso 3)
  div padding "8px 24px 0"
    Fila header: "← PASO 1/2" pixel 18px + Mark26 rotado 8°
    "¡HOLA," display 36px
    "PARCERO!" display 36px red WebkitTextStroke 1.5px ink
    Subtitle mono 14px opacity-80
  Form (padding "22px 24px 0", flex-col gap-14)
    Field "EMAIL" — label mono uppercase + input border-2.5 ink shadow-pop bg-white
    Field "CONTRASEÑA" — ídem type=password
    Fila: checkbox cuadrado yel 16×16 border-2 ink + "Recordame" | link "¿Se te olvidó?"
    Btn rojo full lg "DALE, ENTRAR" (type=submit, disabled={processing})
  Burst yel "+500K BIENVENIDA" absoluto top-[80px] right-[18px] rotado 12°
  SoccerBall size=120 absoluto top-[270px] right-[-8px] opacity-15
  Footer absoluto bottom-[18px] centrado: "¿Nuevo en el parche? Creá cuenta" → /register
```

**Field:** componente interno del Login (no compartido):
```jsx
function Field({ label, id, error, ...inputProps }) {
    return (
        <div>
            <div className="font-mono text-[11px] font-bold tracking-[.1em] mb-1.5">{label}</div>
            <input
                id={id}
                className="w-full border-[2.5px] border-ink bg-white px-[14px] py-[12px] font-mono font-bold text-[14px] shadow-[3px_3px_0_var(--c-ink)] focus:outline-none"
                {...inputProps}
            />
            {error && <div className="font-mono text-[11px] text-pop-red mt-1">{error}</div>}
        </div>
    );
}
```

---

### 7. Fase Bloqueada (`Pages/Predictions/Locked.jsx`)

**Props del controller:**
```php
[
    'round'          => $round->name,        // "R32 + R16"
    'roundOrder'     => $round->order,       // 2
    'previousRound'  => $previousRound->name, // "Grupos"
    'opensAt'        => null,                // Carbon o null
]
```

**Layout:**
```
div bg-navy text-cream min-h-screen overflow-hidden flex flex-col relative
  scanlines absoluto inset (CSS utility)
  halftone-yel absoluto top-0 right-0 w-[200px] h-[200px] opacity-20
  Contenido centrado (flex-1 flex flex-col justify-center px-7 text-center)
    Lock display:
      relative flex justify-center mb-[14px]
      halftone-red absoluto w-[200px] h-[200px] rounded-full opacity-40
      cuadro 140×140 bg-navy border-[4px] border-pop-yel shadow-[6px_6px_0_var(--c-red)] rotado -4°
      Lock SVG inline 80×80 (rect + path + rect, colores yel/ink)
    "ESPERÁ UN TOQUE —" display 14px yel tracking-.08em
    "FASE N" display 38px cream textShadow red 3px
    "BLOQUEADA" display 38px cream textShadow red 3px
    Body 14px cream opacity-85 lineHeight 1.4
    Mini countdown (si opensAt != null):
      bg-black/40 border-2 dashed border-pop-yel padding 10px 12px
      "SE ABRE EN" mono 10px yel
      "N DÍAS · NH" display 24px cream
  CTA bar (padding "0 24px 30px" flex flex-col gap-8)
    <Link href="/chat"> btn full lg yel "MIENTRAS, AL CHAT"
    <Link href="/ranking"> btn ghost full cream "VER RANKING"
```

**opensAt:** si la ronda tiene `start_date` definido, se calcula `Carbon::now()->diffForHumans($round->start_date)` o se pasa el número de días/horas. Si es null, no se muestra el contador.

---

## Detalles de implementación transversales

### Flags de equipos

Todos los equipos tienen `flag_url` en la BD (seeder de banderas o generado con `https://flagcdn.com/w80/{code}.png`). El controller formatea esta URL al pasar las props.

### CSS utilities requeridas

Las siguientes clases CSS deben existir en `resources/css/pop-textures.css`:
- `.speedlines` — `repeating-linear-gradient(75deg, transparent 0 14px, rgba(0,0,0,.18) 14px 16px, transparent 16px 30px)`
- `.scanlines` — `repeating-linear-gradient(0deg, rgba(0,0,0,.08) 0 1px, transparent 1px 3px)`
- `.halftone`, `.halftone-red`, `.halftone-yel`, `.halftone-teal`, `.halftone-navy` — ya existentes del Paso 1

### AvatarColor

Los usuarios no tienen campo `avatar_color` en la BD actualmente. Se asigna en el controller de forma determinista basado en `user->id % 4` mapeado a `['yel', 'teal', 'red', 'cream']`. No requiere migración.

### Ticker (Home)

El Ticker de Home usa `animate-[ticker_22s_linear_infinite]`. Esta keyframe debe estar en `tailwind.config.js`:
```js
keyframes: {
    ticker: { '0%': { transform: 'translateX(0)' }, '100%': { transform: 'translateX(-50%)' } }
}
```

### Inertia routing en TabBar

```jsx
import { router } from '@inertiajs/react';
const ROUTES = { home: '/dashboard', matches: '/matches', rank: '/ranking', chat: '/chat' };
// En el onClick: if (tab.id !== active) router.visit(ROUTES[tab.id]);
```

---

## File map completo

| Acción | Archivo |
|---|---|
| **Crear** | `resources/js/Components/MobileShell.jsx` |
| **Crear** | `resources/js/Pages/Matches.jsx` |
| **Crear** | `resources/js/Pages/Splash.jsx` |
| **Crear** | `resources/js/Pages/Predictions/Locked.jsx` |
| **Crear** | `app/Http/Controllers/MatchesController.php` |
| **Refactorizar** | `resources/js/Pages/Ranking.jsx` |
| **Refactorizar** | `resources/js/Pages/Chat.jsx` |
| **Refactorizar** | `resources/js/Pages/Auth/Login.jsx` |
| **Refactorizar** | `resources/js/Pages/Predictions/Round.jsx` |
| **Modificar** | `resources/js/Components/composed/TabBar.jsx` |
| **Modificar** | `routes/web.php` |
| **Modificar** | `app/Http/Controllers/RankingController.php` (añadir prop `pozo` y `me`) |
| **Modificar** | `app/Http/Controllers/ChatController.php` (añadir prop `me`) |
| **Modificar** | `app/Http/Controllers/PredictionController.php` (redirect a Locked si round no abierto) |
| **Verificar/crear** | `resources/css/pop-textures.css` (speedlines, scanlines) |
| **Copiar** | `public/assets/fifa_cover.png`, `public/assets/wc26_logo.avif` |

---

## Fuera de scope (Paso 5)

- A1 Welcome, A2 HowTo, A3 Rules, A6 Activation
- E1 Phase Open, E2 Deadline
- `Predictions/Index.jsx` (selector de ronda) — mantiene layout actual
- `SpecialPredictionController` y su vista
- Tests de feature PHP para las nuevas rutas (se agregan en Plan 6)
- Tests Vitest para las pantallas (páginas son integraciones, no unit-tested)
