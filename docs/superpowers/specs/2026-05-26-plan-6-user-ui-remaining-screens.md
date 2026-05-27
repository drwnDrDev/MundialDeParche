# Spec: Plan 6 — User UI · Pantallas Restantes

**Fecha:** 2026-05-26
**Estado:** Aprobado

---

## Contexto

Planes 1–5 y Pasos 3–5 completados (189 tests). El design system pop-art está implementado: tokens Tailwind, fuentes Google, primitivos (`Button`, `Chip`, `Cromo`, `Burst`, `Halftone`), íconos SVG (`Trophy`, `SoccerBall`, `Whistle`, `HostStrip`, etc.), compuestos (`StatCard`, `MatchCard`, `RankRow`, etc.), y 8 pantallas principales (`Splash`, `Login`, `Home`, `Matches`, `Predictions/Round`, `Predictions/Locked`, `Ranking`, `Chat`).

**Referencia visual:** `/mnt/c/Users/dwndz/OneDrive/Escritorio/Mundial de parche_/design_handoff_mundial_parche/`
- `screen-onboarding.jsx` — Welcome, Login, Activation
- `screen-info.jsx` — HowTo, Rules
- `screen-alerts.jsx` — PhaseOpen, Deadline, Locked

---

## Alcance

6 pantallas pendientes del handoff de diseño:

| ID | Pantalla | Tipo | Auth |
|---|---|---|---|
| A1 | Welcome | Página Inertia | No |
| A2 | HowTo | Página Inertia | No |
| A3 | Rules | Página Inertia | No |
| A6 | Activation | Página Inertia | Sí (`!is_activated`) |
| E1 | PhaseOpenAlert | Overlay component | Sí (en Home) |
| E2 | DeadlineAlert | Overlay component | Sí (en Home) |

**Fuera de alcance:** acceso público a rutas protegidas (Plan futuro), live countdown timer en DeadlineAlert, notificaciones push.

---

## Rutas y Controllers

### Rutas públicas (sin auth)

```php
// routes/web.php
Route::get('/welcome', fn() => inertia('Welcome'))->name('welcome');
Route::inertia('/how-to-play', 'HowTo')->name('how-to-play');
Route::inertia('/rules', 'Rules')->name('rules');
```

La ruta `/` para guests ya apunta a `Splash` — se actualiza para retornar `Welcome` en su lugar. `Splash.jsx` queda deprecado.

```php
// SplashController o route closure en web.php:
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }
    return inertia('Welcome');
})->name('splash');
```

### Ruta protegida: Activation

```php
Route::get('/activation', [ActivationController::class, 'show'])
    ->middleware('auth')
    ->name('activation');
```

**`ActivationController@show`:**
- Si `$user->is_activated` → redirect a `/home`
- Retorna `Inertia::render('Activation', ['adminName' => ..., 'adminPhone' => ..., 'adminWhatsApp' => ...])`
- Datos del admin desde `config('app.admin_name')`, `config('app.admin_phone')`, `config('app.admin_whatsapp')`

### Redirect post-login para usuarios no activados

En `LoginController` (o `AuthenticatedSessionController`) — después de login exitoso:

```php
if (! $user->is_activated) {
    return redirect()->route('activation');
}
return redirect()->intended(route('home'));
```

### HomeController — alert props

`HomeController@index` evalúa el estado de las rondas y añade dos props:

```php
return Inertia::render('Home', [
    // ... props existentes ...
    'phaseAlert'    => $this->detectPhaseAlert($user),
    'deadlineAlert' => $this->detectDeadlineAlert($user),
]);
```

**`detectPhaseAlert($user)`:**
- Busca la ronda abierta más reciente (última en abrir, `is_open = true`)
- Si `updated_at` de la ronda (cuando pasó a `is_open`) está dentro de las últimas 24h Y el usuario no tiene `PredictionSubmission` con `status != 'draft'` para esa ronda
- Retorna `['fromRound' => '...', 'toRound' => '...', 'closeDate' => '...']` o `null`

**`detectDeadlineAlert($user)`:**
- Busca ronda abierta (`is_open = true, is_locked = false`) cuya `closes_at` sea en < 24h
- Si el usuario tiene predicciones en `draft` (pendientes de submit) en esa ronda
- Retorna `['round' => '...', 'hoursLeft' => N, 'pending' => N, 'total' => N]` o `null`
- Nota: `closes_at` puede no existir aún en el modelo `Round` — agregar campo nullable si falta

---

## Páginas React

### A1 — `Pages/Welcome.jsx`

**Visual:** Navy bg + halftone cream + speedlines. Estructura idéntica al handoff `ScreenWelcome`.

**Estructura:**
```
MobileShell (sin TabBar, bg navy)
├── Halftone overlay (cream, absolute, inset)
├── Speedlines (absolute, inset, opacity .18)
├── Stamp "★ INVITACIÓN OFICIAL ★" (rojo, rotado -2°, top center)
├── Burst "¡DALE, PARCERO!" (yel, top right, rotado 14°)
├── Contenido central (flex col, centered)
│   ├── "HOLA," (display 32px cream)
│   ├── "PARCERO" (display 74px yel, stroke ink 2.5px, shadow 5px)
│   ├── Divider: línea yel — SoccerBall — línea yel
│   ├── Subtexto "Has sido elegido..."
│   └── Card roja rotada -1.5° "BIENVENIDO AL PARCHE DE LOS DUROS DEL MUNDIAL"
├── WC26 logo (img assets/wc26_logo.avif, 70px, centrado)
├── HostStrip
└── CTAs (padding 26px 28px)
    ├── Button yel lg full "ACEPTO EL RETO →" → router.visit('/register')
    └── Button ghost full sm "¿CÓMO SE JUEGA?" → router.visit('/how-to-play')
```

**Assets:** `wc26_logo.avif` — copiar desde design handoff a `public/assets/`.

### A2 — `Pages/HowTo.jsx`

**Visual:** Cream bg, halftone yel top-right corner.

**Estructura:**
```
MobileShell (sin TabBar)
├── Halftone (yel, top right 200×200, absolute)
├── Header
│   ├── Back button (←) + Chip yel "EN 3 PASOS"
│   ├── "ASÍ FUNCIONA" (display 30px)
│   └── "EL PARCHE" (display 44px red, stroke ink)
├── Scroll area (flex 1, overflow-y auto)
│   ├── Step 1: n=1 color=red title="ENTRÁ AL POZO" icon=KIcon (círculo K yel)
│   ├── Step 2: n=2 color=teal title="METÉ TUS GOLES" icon=<SoccerBall size={42}/>
│   ├── Step 3: n=3 color=yel title="SUMÁ PUNTOS" icon=<Trophy size={40}/>
│   ├── SectionHead "LA PUNTUACIÓN"
│   ├── ScoreLine: +5 MARCADOR EXACTO (red)
│   ├── ScoreLine: +2 GANADOR (teal)
│   ├── ScoreLine: +3 CLASIFICA A LA SIGUIENTE (yel, dark text)
│   ├── SectionHead "EL POZO"
│   ├── Cromo navy: Trophy bg + "TU APORTE 50K" + PrizeBlock 70%/30%
│   ├── Teaser dashed "¿Querés los detalles? Mirá las reglas completas →"
│   │   → Link router.visit('/rules')
│   └── Footer pixel "★ JUEGA LIMPIO ★"
└── Sticky CTA
    └── Button red full lg "ENTENDÍ, A METER GOLES →" → router.visit('/register')
```

**Componentes locales** (definidos en el mismo archivo):
- `Step({ n, color, title, copy, icon })` — card con número grande + ícono a la izq, texto a la der
- `ScoreLine({ pts, label, sub, color, dark })` — fila con badge pts + label + descripción
- `PrizeBlock({ place, pct })` — bloque 70%/30% dentro del cromo pozo
- `KIcon()` — círculo yel con "K" (ícono de inscripción al pozo)
- `SectionHead({ title, accent })` — label mono uppercase + línea separadora. Componente local inline (no existe en `Components/`)

### A3 — `Pages/Rules.jsx`

**Visual:** Cream bg, halftone navy bottom-left.

**Estructura:**
```
MobileShell (sin TabBar)
├── Halftone (navy, bottom left 200×200, absolute, opacity .12)
├── Header
│   ├── Back button (←) + Chip navy "v1.0 · 25 MAY 2026"
│   ├── "REGLAS ★ FULL ★" (display 40px + pixel red)
│   └── "Lo que toca saber para no tener líos" (mono, opacity .7)
├── Index strip (overflow-x auto, flex, gap 4)
│   └── 6 chips: 1.INSCRIPCIÓN  2.FASES  3.PUNTOS  4.EMPATES  5.PREMIOS  6.CONDUCTA
│       (primero activo bg-yel, resto bg-white, border ink, shadow pop-sm)
├── Scroll area (flex 1, overflow-y auto)
│   ├── Rule 1: INSCRIPCIÓN
│   ├── Rule 2: FASES Y CIERRES (con RuleList de 4 fases)
│   ├── Rule 3: PUNTUACIÓN (con RuleList de 3 tipos de puntos)
│   ├── Rule 4: EMPATES (con RuleList ordered)
│   ├── Rule 5: PREMIOS (con RuleList + nota admin)
│   ├── Rule 6: CONDUCTA Y CHAT
│   ├── Rule 7: LO IMPREVISTO
│   ├── Card admin navy: "¿Dudas? Hablale al admin Edisson Á. por WhatsApp"
│   └── Footer pixel "★ MUNDIAL DE PARCHE · v1.0 ★"
└── Sticky CTAs (flex row, gap 8)
    ├── Button white flex-1 "COMPARTIR" (navigator.share o copy link)
    └── Button red flex-2 "VOLVER AL PARCHE →" → router.visit('/')

**Back button (←) en HowTo y Rules:** `window.history.back()` — no `router.visit()` para preservar historial.
```

**Componentes locales:**
- `Rule({ n, title, children })` — badge rojo con número + título + línea separadora + cuerpo
- `RuleList({ items, ordered })` — lista con bullet cuadrado yel (o decimal si `ordered`)

### A6 — `Pages/Activation.jsx`

**Props desde controller:** `adminName`, `adminPhone`, `adminWhatsApp`

**Visual:** Cream bg, halftone yel top-right + halftone teal bottom-left.

**Estructura:**
```
MobileShell (sin TabBar)
├── Halftone (yel, top right, absolute)
├── Halftone (teal, bottom left, absolute)
├── Header
│   ├── Back button (←) + pixel "PASO 2 / 2" + Chip yel "ÚLTIMO PASO"
│   ├── "METELE LOS" / "50K AL POZO" (display 32px, "50K" red stroke)
│   └── Subtexto "Tu aporte de entrada se suma al pozo del parche..."
├── Big amount card (Cromo navy, halftone yel, Trophy bg)
│   ├── "TU APORTE" (mono 10px yel)
│   ├── "50K" (display 54px yel, shadow ink)
│   └── "1 sola vez · va 100% al pozo" (mono 10px)
├── Scroll area
│   ├── SectionHead "PAGÁ POR"
│   ├── Grid 2×2 PayMethod:
│   │   ├── NEQUI (#ff006e)
│   │   ├── DAVIPLATA (#ed1c24)
│   │   ├── BANCOLOMBIA (#fdda24, dark text)
│   │   └── EFECTIVO (teal)
│   ├── Admin card (avatar iniciales + adminName + adminPhone)
│   └── Status pending (spinner CSS + "ESPERANDO AL ADMIN")
└── Sticky CTAs
    ├── Button teal full lg "AVISAR POR WHATSAPP →"
    │   → href={`https://wa.me/${adminWhatsApp}`} (link externo)
    └── Button ghost full sm "MIENTRAS, EXPLORAR EL PARCHE"
        → router.visit('/home')
```

**Componente local:** `PayMethod({ label, sub, color, dark })` — card con color swatch + label + número/instrucción.

**Spinner:** CSS `@keyframes spin` inline, `border-top: transparent` para efecto circular.

### E1 — `Components/overlays/PhaseOpenAlert.jsx`

**Props:** `phaseAlert: { fromRound, toRound, closeDate }`, `onDismiss: () => void`

**Visual:** Fullscreen overlay, fondo rojo + halftone + speedlines. Se renderiza como `position: fixed, inset: 0, z-index: 50`.

```
Overlay (fixed, inset-0, z-50, bg-pop-red)
├── Halftone (red, absolute, inset, opacity .35)
├── Speedlines (absolute, inset, opacity .22)
├── Burst yel "¡NUEVA FASE!" (top right, rotado 14°, size 140)
├── Trophy (top left, rotado -10°, size 56)
├── Contenido (flex col, justify-center, padding 60px 28px 0)
│   ├── Chip semitransparente: "{fromRound} · CERRADA ✓"
│   ├── "↓" (display 32px yel)
│   ├── "ABRIÓ ▶" (display 14px yel)
│   ├── "FASE N" (display 48px cream, shadow ink)
│   ├── "{toRound}" (display 30px yel, stroke ink)
│   └── Card navy: instrucciones + cierre "{closeDate}"
└── CTAs (padding 0 24px 30px)
    ├── Button yel full lg "ARRANCAR A METER GOLES →"
    │   → onDismiss() + router.visit('/predictions')
    └── Footer mono "Después, no se puede."
```

### E2 — `Components/overlays/DeadlineAlert.jsx`

**Props:** `deadlineAlert: { round, hoursLeft, minutesLeft, pending, total }`, `onDismiss: () => void`

**Visual:** Fullscreen overlay, fondo yel + halftone red + franjas diagonales.

```
Overlay (fixed, inset-0, z-50, bg-pop-yel)
├── Halftone (red, absolute, inset, opacity .35)
├── Franjas diagonales (top 0 height 80px + bottom 0 height 80px, repeating-linear-gradient, opacity .18)
├── Whistle (top right, rotado 18°, size 56)
├── Contenido (flex col, justify-center, padding 40px 24px 0)
│   ├── "¡PILAS," (display 60px red, stroke ink 2.5px, shadow ink)
│   ├── "PARCERO!" (display 56px cream, stroke ink, shadow red)
│   ├── Countdown boxes (flex, gap 6): HORAS · MIN · SEG
│   │   (bg ink, text yel, shadow red, display 36px)
│   │   Valores: Math.floor(hoursLeft), minutesLeft (estáticos, no live)
│   └── Card blanca: "TE FALTAN {pending} goles por meter en {round}"
│       + barra roja: width={(total-pending)/total*100}%
│       + "x / {total}" (mono, right)
└── CTAs (padding 0 24px 30px)
    ├── Button red full lg "TERMINAR YA →"
    │   → onDismiss() + router.visit('/predictions')
    └── Button ghost full sm "AVISARME EN 1 HORA" (dismiss, sin lógica real)
        → onDismiss()
```

### Actualización — `Pages/Home.jsx`

```jsx
// Nuevas props recibidas desde HomeController
const { /* ... props existentes ... */, phaseAlert, deadlineAlert } = usePage().props;

// Estado local para dismiss
const [alertDismissed, setAlertDismissed] = useState(() => {
    if (phaseAlert) {
        return localStorage.getItem(`alert_phase_${phaseAlert.toRound}`) === '1';
    }
    if (deadlineAlert) {
        return localStorage.getItem(`alert_deadline_${deadlineAlert.round}`) === '1';
    }
    return true;
});

const handleDismiss = () => {
    if (phaseAlert) localStorage.setItem(`alert_phase_${phaseAlert.toRound}`, '1');
    if (deadlineAlert) localStorage.setItem(`alert_deadline_${deadlineAlert.round}`, '1');
    setAlertDismissed(true);
};

// En el render, antes del return del MobileShell:
{!alertDismissed && phaseAlert && (
    <PhaseOpenAlert phaseAlert={phaseAlert} onDismiss={handleDismiss} />
)}
{!alertDismissed && !phaseAlert && deadlineAlert && (
    <DeadlineAlert deadlineAlert={deadlineAlert} onDismiss={handleDismiss} />
)}
```

---

## Backend — Cambios necesarios

### Config admin

En `config/app.php` agregar (o en `.env`):
```php
'admin_name'      => env('ADMIN_NAME', 'Edisson Á.'),
'admin_phone'     => env('ADMIN_PHONE', '+57 300 000 0000'),
'admin_whatsapp'  => env('ADMIN_WHATSAPP', '573000000000'),
```

### `ActivationController`

```php
class ActivationController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->is_activated) {
            return redirect()->route('home');
        }

        return Inertia::render('Activation', [
            'adminName'      => config('app.admin_name'),
            'adminPhone'     => config('app.admin_phone'),
            'adminWhatsApp'  => config('app.admin_whatsapp'),
        ]);
    }
}
```

### `HomeController` — alert detection

```php
private function detectPhaseAlert(User $user): ?array
{
    $round = Round::where('is_open', true)
        ->where('updated_at', '>=', now()->subHours(24))
        ->latest('updated_at')
        ->first();

    if (! $round) return null;

    $hasSubmission = $user->predictionSubmissions()
        ->where('round_id', $round->id)
        ->whereIn('status', ['submitted', 'locked'])
        ->exists();

    if ($hasSubmission) return null;

    $prevRound = Round::where('order', $round->order - 1)->first();

    return [
        'fromRound' => $prevRound?->name ?? 'Fase anterior',
        'toRound'   => $round->name,
        'closeDate' => $round->closes_at?->format('d M · H:i') ?? 'Por confirmar',
    ];
}

private function detectDeadlineAlert(User $user): ?array
{
    $round = Round::where('is_open', true)
        ->where('is_locked', false)
        ->whereNotNull('closes_at')
        ->where('closes_at', '<=', now()->addHours(24))
        ->first();

    if (! $round) return null;

    $submission = $user->predictionSubmissions()
        ->where('round_id', $round->id)
        ->where('status', 'draft')
        ->first();

    if (! $submission) return null;

    $totalMatches = $round->fixtures()->count();
    $predicted    = $user->predictions()
        ->whereHas('fixture', fn($q) => $q->where('round_id', $round->id))
        ->count();

    $hoursLeft = (int) now()->diffInHours($round->closes_at, false);
    if ($hoursLeft < 0) return null;

    return [
        'round'       => $round->name,
        'hoursLeft'   => $hoursLeft,
        'minutesLeft' => (int) now()->diffInMinutes($round->closes_at, false) % 60,
        'pending'     => $totalMatches - $predicted,
        'total'       => $totalMatches,
    ];
}
```

### Migración — campo `closes_at` en rounds

Si no existe, agregar en nueva migración:
```php
$table->dateTime('closes_at')->nullable()->after('is_locked');
```

En `app/Models/Round.php`, agregar al array `$casts`:
```php
'closes_at' => 'datetime',
```

### Redirect post-login

En `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, método `store`:
```php
// Después de Auth::login($user):
if (! $user->is_activated) {
    return redirect()->route('activation');
}
return redirect()->intended(route('home'));
```

---

## Assets

Copiar desde el design handoff a `public/assets/`:
- `fifa_cover.png`
- `wc26_logo.avif`

Ya referenciados en `Splash.jsx` existente como `assets/fifa_cover.png` y `assets/wc26_logo.avif`.

---

## Tests

Feature tests nuevos en `tests/Feature/`:

| Test | Cobertura |
|---|---|
| `WelcomeTest` | GET `/` para guest retorna Welcome; GET `/` para auth redirect home |
| `HowToRulesTest` | GET `/how-to-play` y `/rules` son públicas (200) |
| `ActivationTest` | Auth requerida; is_activated redirect home; props admin presentes |
| `HomeAlertsTest` | phaseAlert null cuando no hay ronda abierta reciente; deadlineAlert null sin cierre próximo |

Tests existentes no deben romperse. Total esperado: ~189 + 8 = **~197 tests**.

---

## Archivos a crear / modificar

**Crear:**
- `app/Http/Controllers/ActivationController.php`
- `resources/js/Pages/Welcome.jsx`
- `resources/js/Pages/HowTo.jsx`
- `resources/js/Pages/Rules.jsx`
- `resources/js/Pages/Activation.jsx`
- `resources/js/Components/overlays/PhaseOpenAlert.jsx`
- `resources/js/Components/overlays/DeadlineAlert.jsx`
- `database/migrations/XXXX_add_closes_at_to_rounds_table.php`
- `tests/Feature/WelcomeTest.php`
- `tests/Feature/HowToRulesTest.php`
- `tests/Feature/ActivationTest.php`
- `tests/Feature/HomeAlertsTest.php`
- `public/assets/fifa_cover.png` (copiar)
- `public/assets/wc26_logo.avif` (copiar)

**Modificar:**
- `routes/web.php` — rutas Welcome, HowTo, Rules, Activation; `/` guest → Welcome
- `config/app.php` — admin_name, admin_phone, admin_whatsapp
- `app/Http/Controllers/HomeController.php` — phaseAlert + deadlineAlert props
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — redirect activation
- `resources/js/Pages/Home.jsx` — importar y renderizar overlays
