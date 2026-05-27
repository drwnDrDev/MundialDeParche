# Plan 6 — User UI · Pantallas Restantes

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar las 6 pantallas restantes del handoff: Welcome, HowTo, Rules, Activation, PhaseOpenAlert overlay y DeadlineAlert overlay.

**Architecture:** 4 páginas Inertia nuevas (Welcome, HowTo, Rules, Activation) + 2 overlays full-screen (PhaseOpenAlert, DeadlineAlert) renderizados condicionalmente en Home. Backend minimal: ActivationController, redirect post-login, dos métodos de detección de alertas en HomeController. Migration para `closes_at` en rounds.

**Tech Stack:** Laravel 11, Inertia.js v2, React 18, Tailwind CSS, Pest v3, componentes existentes en `resources/js/Components/`.

---

## Archivos

**Crear:**
- `database/migrations/2026_05_26_170000_add_closes_at_to_rounds_table.php`
- `app/Http/Controllers/ActivationController.php`
- `resources/js/Pages/Welcome.jsx`
- `resources/js/Pages/HowTo.jsx`
- `resources/js/Pages/Rules.jsx`
- `resources/js/Pages/Activation.jsx`
- `resources/js/Components/overlays/PhaseOpenAlert.jsx`
- `resources/js/Components/overlays/DeadlineAlert.jsx`
- `tests/Feature/WelcomeTest.php`
- `tests/Feature/HowToRulesTest.php`
- `tests/Feature/ActivationTest.php`
- `tests/Feature/HomeAlertsTest.php`

**Modificar:**
- `app/Models/Round.php` — añadir `closes_at` a `$casts`
- `config/app.php` — añadir `admin_name`, `admin_phone`, `admin_whatsapp`
- `.env.example` — añadir variables `ADMIN_*`
- `routes/web.php` — rutas Welcome, HowTo, Rules, Activation; `/` → Welcome para guests
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — redirect a `/activation` si `!is_activated`
- `app/Http/Controllers/HomeController.php` — añadir `phaseAlert` y `deadlineAlert` props
- `resources/js/Components/ui/Burst.jsx` — añadir tamaño `xl`
- `resources/js/Pages/Home.jsx` — importar y renderizar overlays

---

## Task 1: Migración `closes_at` en rounds + cast en modelo

**Files:**
- Create: `database/migrations/2026_05_26_170000_add_closes_at_to_rounds_table.php`
- Modify: `app/Models/Round.php`

- [ ] **Step 1: Crear la migración**

```php
<?php
// database/migrations/2026_05_26_170000_add_closes_at_to_rounds_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dateTime('closes_at')->nullable()->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn('closes_at');
        });
    }
};
```

- [ ] **Step 2: Añadir `closes_at` a `$casts` y `$fillable` en `app/Models/Round.php`**

En `$fillable` agregar `'closes_at'` al final del array.

En el método `casts()` agregar:
```php
'closes_at' => 'datetime',
```

El método `casts()` quedará:
```php
protected function casts(): array
{
    return [
        'is_open'    => 'boolean',
        'is_locked'  => 'boolean',
        'closes_at'  => 'datetime',
    ];
}
```

- [ ] **Step 3: Correr la migración**

```bash
./vendor/bin/sail artisan migrate
```

Expected: `Migrating: 2026_05_26_170000_add_closes_at_to_rounds_table` → `Migrated`

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_26_170000_add_closes_at_to_rounds_table.php app/Models/Round.php
git commit -m "feat: add closes_at column to rounds table"
```

---

## Task 2: Config admin + .env.example

**Files:**
- Modify: `config/app.php`
- Modify: `.env.example`

- [ ] **Step 1: Añadir claves admin al final de `config/app.php`**, antes del cierre del array `return [...]`:

```php
    /*
    |--------------------------------------------------------------------------
    | Admin Contact
    |--------------------------------------------------------------------------
    */
    'admin_name'      => env('ADMIN_NAME', 'Admin'),
    'admin_phone'     => env('ADMIN_PHONE', '+57 300 000 0000'),
    'admin_whatsapp'  => env('ADMIN_WHATSAPP', '573000000000'),
```

- [ ] **Step 2: Añadir al final de `.env.example`**

```
ADMIN_NAME="Edisson Á."
ADMIN_PHONE="+57 300 000 0000"
ADMIN_WHATSAPP="573000000000"
```

- [ ] **Step 3: Commit**

```bash
git add config/app.php .env.example
git commit -m "feat: add admin contact config keys"
```

---

## Task 3: Tests rutas públicas (Welcome, HowTo, Rules)

**Files:**
- Create: `tests/Feature/WelcomeTest.php`
- Create: `tests/Feature/HowToRulesTest.php`

- [ ] **Step 1: Escribir `WelcomeTest.php`**

```php
<?php
// tests/Feature/WelcomeTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders Welcome page for guests', function () {
    $this->withoutVite()
        ->get('/')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Welcome'));
});

it('redirects authenticated users from / to dashboard', function () {
    $user = User::factory()->activated()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
```

- [ ] **Step 2: Escribir `HowToRulesTest.php`**

```php
<?php
// tests/Feature/HowToRulesTest.php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders HowTo page without authentication', function () {
    $this->withoutVite()
        ->get('/how-to-play')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('HowTo'));
});

it('renders Rules page without authentication', function () {
    $this->withoutVite()
        ->get('/rules')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Rules'));
});
```

- [ ] **Step 3: Correr los tests para verificar que fallan**

```bash
./vendor/bin/sail test tests/Feature/WelcomeTest.php tests/Feature/HowToRulesTest.php
```

Expected: FAILED — rutas no existen todavía.

- [ ] **Step 4: Commit de los tests**

```bash
git add tests/Feature/WelcomeTest.php tests/Feature/HowToRulesTest.php
git commit -m "test: add failing tests for public routes Welcome, HowTo, Rules"
```

---

## Task 4: Rutas públicas + Welcome.jsx

**Files:**
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Welcome.jsx`

- [ ] **Step 1: Actualizar la ruta `/` y añadir HowTo/Rules en `routes/web.php`**

Reemplazar el bloque de la ruta `/` actual:

```php
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Splash');
})->name('home');
```

Por:

```php
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome');
})->name('home');

Route::inertia('/how-to-play', 'HowTo')->name('how-to-play');
Route::inertia('/rules', 'Rules')->name('rules');
```

- [ ] **Step 2: Crear `resources/js/Pages/Welcome.jsx`**

```jsx
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Trophy, SoccerBall } from '@/Components/icons/football';
import Burst from '@/Components/ui/Burst';

function HostStrip() {
    return (
        <div className="flex items-center gap-3 font-mono text-[11px] tracking-[.06em] text-cream opacity-80">
            <span>🇺🇸 USA</span>
            <span className="opacity-50">·</span>
            <span>🇨🇦 CAN</span>
            <span className="opacity-50">·</span>
            <span>🇲🇽 MEX</span>
        </div>
    );
}

export default function Welcome() {
    return (
        <>
            <Head title="Bienvenido · Mundial de Parche" />
            <div className="bg-navy text-cream min-h-screen overflow-hidden relative flex flex-col">

                {/* Halftone cream overlay */}
                <div
                    className="absolute inset-0 pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(var(--c-cream) 1.4px, transparent 1.8px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.08,
                    }}
                />

                {/* Speedlines */}
                <div className="speedlines absolute inset-0 pointer-events-none" style={{ opacity: .18 }} />

                {/* Stamp — top center */}
                <div className="absolute top-[50px] left-0 right-0 flex justify-center z-10">
                    <div
                        className="bg-pop-red text-white border-[2.5px] border-ink px-3.5 py-1 font-pixel text-[18px] tracking-[.06em]"
                        style={{ transform: 'rotate(-2deg)', boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        ★ INVITACIÓN OFICIAL ★
                    </div>
                </div>

                {/* Burst — top right */}
                <div className="absolute top-[130px] right-3 z-10" style={{ transform: 'rotate(14deg)' }}>
                    <Burst color="yel" size="lg">
                        ¡DALE, PARCERO!
                    </Burst>
                </div>

                {/* Main content */}
                <div className="relative z-10 flex-1 flex flex-col justify-center text-center px-[26px] pt-10">
                    <div className="font-display text-[32px] leading-none text-cream">
                        HOLA,
                    </div>
                    <div
                        className="font-display text-[74px] leading-[.9] text-pop-yel mt-0.5"
                        style={{
                            WebkitTextStroke: '2.5px var(--c-ink)',
                            textShadow: '5px 5px 0 var(--c-ink)',
                        }}
                    >
                        PARCERO
                    </div>

                    {/* Divider */}
                    <div className="flex items-center gap-1.5 my-3.5">
                        <div className="flex-1 h-0.5 bg-pop-yel" />
                        <SoccerBall size={20} />
                        <div className="flex-1 h-0.5 bg-pop-yel" />
                    </div>

                    <div className="font-body text-[15px] leading-[1.4] text-cream">
                        Has sido <b className="text-pop-yel">elegido</b> para demostrar de qué estás hecho.
                    </div>

                    {/* Red welcome card */}
                    <div
                        className="mt-[18px] px-3.5 py-2.5 bg-pop-red text-white border-[2.5px] border-ink relative overflow-hidden"
                        style={{ transform: 'rotate(-1.5deg)', boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        <div
                            className="absolute inset-0 pointer-events-none"
                            style={{
                                backgroundImage: 'radial-gradient(var(--c-yel) 1.2px, transparent 1.6px)',
                                backgroundSize: '8px 8px',
                                opacity: 0.18,
                            }}
                        />
                        <div className="relative font-pixel text-[18px] tracking-[.02em]">BIENVENIDO AL</div>
                        <div className="relative font-display text-[20px] leading-none mt-1">
                            PARCHE DE LOS<br />DUROS DEL MUNDIAL
                        </div>
                    </div>
                </div>

                {/* WC26 logo */}
                <div className="relative z-10 flex justify-center mb-2.5">
                    <img src="/assets/wc26_logo.avif" alt="WC26" className="w-[70px] h-auto block" />
                </div>

                {/* Host strip */}
                <div className="relative z-10 flex justify-center mb-4">
                    <HostStrip />
                </div>

                {/* CTAs */}
                <div className="relative z-10 flex flex-col gap-2.5 px-[26px] pb-7 flex-shrink-0">
                    <button
                        onClick={() => router.visit('/register')}
                        className="w-full py-[18px] bg-pop-yel text-ink font-display text-[17px] tracking-[.01em] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        ACEPTO EL RETO →
                    </button>
                    <button
                        onClick={() => router.visit('/how-to-play')}
                        className="w-full py-2 text-cream font-display text-[11px] tracking-[.01em] opacity-80"
                    >
                        ¿CÓMO SE JUEGA?
                    </button>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 3: Correr los tests de Welcome y rutas públicas**

```bash
./vendor/bin/sail test tests/Feature/WelcomeTest.php tests/Feature/HowToRulesTest.php
```

Expected: `WelcomeTest` pasa. `HowToRulesTest` FALLA porque HowTo y Rules no tienen componente aún.

- [ ] **Step 4: Commit**

```bash
git add routes/web.php resources/js/Pages/Welcome.jsx
git commit -m "feat: add Welcome page and public routes"
```

---

## Task 5: HowTo.jsx y Rules.jsx

**Files:**
- Create: `resources/js/Pages/HowTo.jsx`
- Create: `resources/js/Pages/Rules.jsx`

- [ ] **Step 1: Crear `resources/js/Pages/HowTo.jsx`**

```jsx
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Trophy, SoccerBall } from '@/Components/icons/football';
import Cromo from '@/Components/ui/Cromo';

function SectionHead({ title, accent = 'red' }) {
    return (
        <div className="flex items-center gap-2 py-2.5">
            <span className={`w-3.5 h-3.5 flex-shrink-0 bg-pop-${accent} border-2 border-ink`} />
            <div className="font-display text-[14px] tracking-[.02em]">{title}</div>
            <div className="flex-1 h-[3px] bg-ink" />
        </div>
    );
}

function KIcon() {
    return (
        <div
            className="w-8 h-8 rounded-full bg-pop-yel text-ink border-[2.5px] border-ink flex items-center justify-center font-display text-[16px]"
            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
        >
            K
        </div>
    );
}

function Step({ n, color, title, copy, icon }) {
    return (
        <div
            className="flex items-stretch gap-2.5 mb-3 bg-white border-[2.5px] border-ink p-2.5 relative overflow-hidden"
            style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
        >
            <div
                className="w-[60px] flex-shrink-0 border-[2.5px] border-ink flex flex-col items-center justify-center relative"
                style={{ background: color }}
            >
                <div
                    className="absolute inset-0 pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(rgba(0,0,0,.9) 1.2px, transparent 1.6px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.08,
                    }}
                />
                <div className="font-display text-[36px] text-ink leading-none relative">{n}</div>
                <div className="mt-1 relative">{icon}</div>
            </div>
            <div className="flex-1 min-w-0 flex flex-col justify-center">
                <div className="font-display text-[16px] leading-tight">{title}</div>
                <div className="font-body text-[13px] mt-1.5 leading-[1.4] opacity-85">{copy}</div>
            </div>
        </div>
    );
}

function ScoreLine({ pts, label, sub, color, dark = false }) {
    return (
        <div
            className="flex items-center gap-2.5 px-3 py-2.5 bg-white border-[2.5px] border-ink"
            style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
        >
            <div
                className={`flex-shrink-0 min-w-[50px] text-center border-2 border-ink px-2.5 py-1.5 font-display text-[18px] ${dark ? 'text-ink' : 'text-white'}`}
                style={{ background: color }}
            >
                {pts}
            </div>
            <div className="flex-1 min-w-0">
                <div className="font-display text-[12px] leading-none">{label}</div>
                <div className="font-mono text-[10px] opacity-70 mt-1">{sub}</div>
            </div>
        </div>
    );
}

function PrizeBlock({ place, pct }) {
    return (
        <div className="flex-1 p-2 text-center border-2 border-pop-yel" style={{ background: 'rgba(0,0,0,.3)' }}>
            <div className="font-display text-[20px] text-pop-yel">{place}</div>
            <div className="font-mono font-bold text-[14px] text-cream mt-0.5 tracking-[.04em]">{pct}</div>
            <div className="font-mono text-[9px] opacity-65 mt-0.5 tracking-[.06em]">DEL POZO</div>
        </div>
    );
}

export default function HowTo() {
    return (
        <>
            <Head title="Cómo se juega · Mundial de Parche" />
            <div className="bg-cream min-h-screen overflow-hidden relative flex flex-col">

                {/* Halftone yel — top right */}
                <div
                    className="absolute top-0 right-0 w-[200px] h-[200px] pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(var(--c-yel) 1.2px, transparent 1.6px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.22,
                        WebkitMaskImage: 'radial-gradient(circle at 100% 0%, #000 40%, transparent 70%)',
                        maskImage: 'radial-gradient(circle at 100% 0%, #000 40%, transparent 70%)',
                    }}
                />

                {/* Header */}
                <div className="relative px-[18px] pt-3 pb-0 flex-shrink-0">
                    <div className="flex items-center justify-between">
                        <button
                            onClick={() => window.history.back()}
                            className="w-8 h-8 border-2 border-ink flex items-center justify-center font-display text-[14px]"
                        >
                            ←
                        </button>
                        <div className="bg-pop-yel text-ink border-2 border-ink px-2 py-0.5 font-mono text-[9px] font-bold tracking-[.06em]" style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}>
                            EN 3 PASOS
                        </div>
                    </div>
                    <div className="font-display text-[30px] leading-none mt-3.5">ASÍ FUNCIONA</div>
                    <div
                        className="font-display text-[44px] leading-[.9] mt-0.5 text-pop-red"
                        style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}
                    >
                        EL PARCHE
                    </div>
                </div>

                {/* Scroll area */}
                <div className="flex-1 overflow-y-auto px-[18px] pt-4 pb-2" style={{ WebkitOverflowScrolling: 'touch' }}>
                    <Step
                        n="1"
                        color="var(--c-red)"
                        title="ENTRÁ AL POZO"
                        copy="Aportás 50K una sola vez. El admin te activa y quedás dentro."
                        icon={<KIcon />}
                    />
                    <Step
                        n="2"
                        color="var(--c-teal)"
                        title="METÉ TUS GOLES"
                        copy="En cada fase predecís los marcadores de todos los partidos. Tenés tiempo hasta el cierre de la fase."
                        icon={<SoccerBall size={38} />}
                    />
                    <Step
                        n="3"
                        color="var(--c-yel)"
                        title="SUMÁ PUNTOS"
                        copy="Por cada acierto te caen puntos. El que más sume al final, se lleva el pozo."
                        icon={<Trophy size={36} />}
                    />

                    <SectionHead title="LA PUNTUACIÓN" accent="red" />
                    <div className="flex flex-col gap-2">
                        <ScoreLine pts="+5" label="MARCADOR EXACTO" sub="Le pegaste al 2-1 clavado" color="var(--c-red)" />
                        <ScoreLine pts="+2" label="GANADOR" sub="Acertaste quién gana (sin el score exacto)" color="var(--c-teal)" />
                        <ScoreLine pts="+3" label="CLASIFICA A LA SIGUIENTE" sub="Adivinaste qué equipo avanza" color="var(--c-yel)" dark />
                    </div>

                    <SectionHead title="EL POZO" accent="teal" />
                    <Cromo className="bg-navy text-cream p-3.5">
                        <div
                            className="absolute inset-0 pointer-events-none"
                            style={{
                                backgroundImage: 'radial-gradient(var(--c-yel) 1.2px, transparent 1.6px)',
                                backgroundSize: '8px 8px',
                                opacity: 0.22,
                            }}
                        />
                        <div className="absolute right-[-8px] bottom-[-10px]" style={{ transform: 'rotate(-8deg)' }}>
                            <Trophy size={56} color="var(--c-yel)" />
                        </div>
                        <div className="relative">
                            <div className="font-mono text-[10px] tracking-[.1em] text-pop-yel">SE REPARTE EN</div>
                            <div className="flex gap-2 mt-2">
                                <PrizeBlock place="1°" pct="70%" />
                                <PrizeBlock place="2°" pct="30%" />
                            </div>
                            <div className="font-mono text-[10px] opacity-80 mt-2 leading-[1.4] max-w-[230px]">
                                Con 84 parceros son <b className="text-pop-yel">4.200K</b> al final.
                            </div>
                        </div>
                    </Cromo>

                    {/* Rules teaser */}
                    <div
                        className="mt-4 px-3 py-2.5 bg-white border-[2.5px] border-dashed border-ink flex items-center gap-2.5"
                        onClick={() => router.visit('/rules')}
                    >
                        <div className="font-display text-[22px]">📖</div>
                        <div className="flex-1 font-mono text-[11px] leading-[1.4]">
                            ¿Querés los detalles? <b><u>Mirá las reglas completas →</u></b>
                        </div>
                    </div>

                    <div className="pb-3.5 mt-2 text-center font-pixel text-[16px] text-ink opacity-50">
                        ★ JUEGA LIMPIO ★
                    </div>
                </div>

                {/* Sticky CTA */}
                <div className="flex-shrink-0 px-[18px] py-2.5 pb-[22px] bg-cream border-t-[3px] border-ink">
                    <button
                        onClick={() => router.visit('/register')}
                        className="w-full py-[18px] bg-pop-red text-white font-display text-[16px] tracking-[.01em] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        ENTENDÍ, A METER GOLES →
                    </button>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Crear `resources/js/Pages/Rules.jsx`**

```jsx
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';

function Rule({ n, title, children }) {
    return (
        <div className="mb-[18px]">
            <div className="flex items-center gap-2 mb-1.5">
                <span
                    className="bg-pop-red text-white border-2 border-ink px-[7px] py-0.5 font-display text-[13px]"
                    style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                >
                    {n}
                </span>
                <div className="font-display text-[15px]">{title}</div>
                <div className="flex-1 h-0.5 bg-ink" />
            </div>
            <div className="font-body text-[13px] leading-[1.5] text-ink opacity-92">
                {children}
            </div>
        </div>
    );
}

function RuleList({ items, ordered = false }) {
    return (
        <ul className={`mt-2 mb-0 ${ordered ? 'pl-[18px] list-decimal' : 'pl-0 list-none'}`}>
            {items.map((text, i) => (
                <li key={i} className="font-mono text-[11px] font-semibold py-[3px] tracking-[.02em] relative" style={{ paddingLeft: ordered ? '4px' : '14px' }}>
                    {!ordered && (
                        <span className="absolute left-0 top-[6px] w-2 h-2 bg-pop-yel border-[1.5px] border-ink" />
                    )}
                    {text}
                </li>
            ))}
        </ul>
    );
}

export default function Rules() {
    return (
        <>
            <Head title="Reglas · Mundial de Parche" />
            <div className="bg-cream min-h-screen overflow-hidden relative flex flex-col">

                {/* Halftone navy — bottom left */}
                <div
                    className="absolute bottom-0 left-0 w-[200px] h-[200px] pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(var(--c-navy) 1.2px, transparent 1.6px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.08,
                        WebkitMaskImage: 'radial-gradient(circle at 0% 100%, #000 40%, transparent 70%)',
                        maskImage: 'radial-gradient(circle at 0% 100%, #000 40%, transparent 70%)',
                    }}
                />

                {/* Header */}
                <div className="relative px-[18px] pt-3 pb-0 flex-shrink-0">
                    <div className="flex items-center justify-between">
                        <button
                            onClick={() => window.history.back()}
                            className="w-8 h-8 border-2 border-ink flex items-center justify-center font-display text-[14px]"
                        >
                            ←
                        </button>
                        <div
                            className="bg-navy text-cream border-2 border-ink px-2 py-0.5 font-mono text-[9px] font-bold tracking-[.06em]"
                            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                        >
                            v1.0 · 25 MAY 2026
                        </div>
                    </div>
                    <div className="flex items-end gap-2 mt-3">
                        <div className="font-display text-[40px] leading-[.9] text-ink">REGLAS</div>
                        <div className="font-pixel text-[20px] text-pop-red pb-1">★ FULL ★</div>
                    </div>
                    <div className="font-mono text-[11px] opacity-70 tracking-[.06em] mt-1">
                        Lo que toca saber para no tener líos
                    </div>
                </div>

                {/* Index strip */}
                <div className="flex-shrink-0 px-3.5 pt-3 pb-0">
                    <div className="flex gap-1 overflow-x-auto pb-1.5" style={{ WebkitOverflowScrolling: 'touch' }}>
                        {['INSCRIPCIÓN', 'FASES', 'PUNTOS', 'EMPATES', 'PREMIOS', 'CONDUCTA'].map((s, i) => (
                            <div
                                key={s}
                                className={`flex-shrink-0 px-2 py-1 border-2 border-ink font-mono text-[9px] font-bold tracking-[.06em] ${i === 0 ? 'bg-pop-yel text-ink' : 'bg-white text-ink'}`}
                                style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                            >
                                {i + 1}. {s}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Scroll area */}
                <div className="flex-1 overflow-y-auto px-[18px] pt-3" style={{ WebkitOverflowScrolling: 'touch' }}>
                    <Rule n="1" title="INSCRIPCIÓN">
                        Para entrar al parche aportás <b>50K una sola vez</b>, antes del primer partido de la fase de grupos.
                        El admin verifica el pago y te activa la cuenta. Si no estás activado al cierre de la Fase 1, no podés participar.
                    </Rule>

                    <Rule n="2" title="FASES Y CIERRES">
                        El torneo tiene 4 fases. Cada una se cierra <b>antes del pitazo inicial del primer partido</b> de esa fase.
                        Después del cierre, los goles guardados quedan en piedra — no se cambian.
                        <RuleList items={[
                            'Fase 1 · Grupos · 72 partidos',
                            'Fase 2 · R32 + R16 · 24 partidos',
                            'Fase 3 · 8vos + 4tos · 12 partidos',
                            'Fase 4 · Semis + Final · 3 partidos',
                        ]} />
                    </Rule>

                    <Rule n="3" title="PUNTUACIÓN">
                        Sumás puntos por cada acierto, según lo que metiste:
                        <RuleList items={[
                            '+5 pts · marcador exacto (ej: si pusiste 2-1 y queda 2-1)',
                            '+2 pts · ganador correcto (si pusiste 2-1 y queda 3-0, igual sumás)',
                            '+3 pts · clasificado correcto a la siguiente ronda',
                        ]} />
                        <span className="inline-block mt-1.5 font-mono text-[10px] opacity-70 leading-[1.5]">
                            Los goles de tiempos extra y penales <b>no cuentan</b> para el marcador.
                        </span>
                    </Rule>

                    <Rule n="4" title="EMPATES DE PUNTAJE">
                        Si al final dos o más parceros quedan con los mismos puntos, se desempata así:
                        <RuleList ordered items={[
                            'Más marcadores exactos durante todo el torneo',
                            'Mejor predicción de la Final',
                            'Sorteo arbitrado por el admin (en último caso)',
                        ]} />
                    </Rule>

                    <Rule n="5" title="PREMIOS">
                        El pozo se forma con los 50K de cada parche que entra. Se reparte así al final:
                        <RuleList items={[
                            '1° lugar · 70% del pozo',
                            '2° lugar · 30% del pozo',
                        ]} />
                        <span className="inline-block mt-1.5 font-mono text-[10px] opacity-70 leading-[1.5]">
                            El admin coordina el pago dentro de los 7 días siguientes a la final. Cero comisión.
                        </span>
                    </Rule>

                    <Rule n="6" title="CONDUCTA Y CHAT">
                        El chat es para alentar, picar al rival y celebrar — no para insultos ni faltas de respeto.
                        El admin puede silenciar o eliminar a un parcero que se pase de la raya. Cero reembolso en ese caso.
                    </Rule>

                    <Rule n="7" title="LO IMPREVISTO">
                        Si la FIFA cambia un partido, una sede, o suspende algo, el admin <b>ajusta el calendario</b> dentro de la app.
                        Los goles ya guardados se mantienen como estaban.
                    </Rule>

                    {/* Admin card */}
                    <div className="mt-[18px] mb-3.5 px-3 py-2.5 bg-navy text-cream border-[2.5px] border-ink flex items-center gap-2.5">
                        <div className="text-[18px]">📞</div>
                        <div className="flex-1 font-mono text-[10px] leading-[1.5] tracking-[.02em]">
                            ¿Dudas? Hablale al admin <b className="text-pop-yel">Edisson Á.</b> por WhatsApp.
                        </div>
                    </div>

                    <div className="pb-5 text-center font-pixel text-[14px] opacity-45">
                        ★ MUNDIAL DE PARCHE · v1.0 ★
                    </div>
                </div>

                {/* Sticky CTAs */}
                <div className="flex-shrink-0 flex gap-2 px-[18px] py-2.5 pb-[22px] bg-cream border-t-[3px] border-ink">
                    <button
                        onClick={() => {
                            if (navigator.share) {
                                navigator.share({ title: 'Mundial de Parche · Reglas', url: window.location.href });
                            } else {
                                navigator.clipboard.writeText(window.location.href);
                            }
                        }}
                        className="flex-1 py-2.5 bg-white text-ink font-display text-[12px] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        COMPARTIR
                    </button>
                    <button
                        onClick={() => router.visit('/')}
                        className="flex-[2] py-2.5 bg-pop-red text-white font-display text-[13px] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        VOLVER AL PARCHE →
                    </button>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 3: Correr los tests**

```bash
./vendor/bin/sail test tests/Feature/WelcomeTest.php tests/Feature/HowToRulesTest.php
```

Expected: todos en verde (4 tests pasando).

- [ ] **Step 4: Correr toda la suite para no romper nada**

```bash
./vendor/bin/sail test
```

Expected: todos los tests previos siguen pasando.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/HowTo.jsx resources/js/Pages/Rules.jsx
git commit -m "feat: add HowTo and Rules pages"
```

---

## Task 6: Tests + ActivationController + ruta

**Files:**
- Create: `tests/Feature/ActivationTest.php`
- Create: `app/Http/Controllers/ActivationController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Escribir `tests/Feature/ActivationTest.php`**

```php
<?php
// tests/Feature/ActivationTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects unauthenticated users to login', function () {
    $this->get('/activation')->assertRedirect('/login');
});

it('redirects already-activated users to dashboard', function () {
    $user = User::factory()->activated()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get('/activation')
        ->assertRedirect(route('dashboard'));
});

it('renders Activation page for non-activated users', function () {
    $user = User::factory()->create(['is_activated' => false]);

    $this->withoutVite()
        ->actingAs($user)
        ->get('/activation')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Activation'));
});

it('passes admin contact props to Activation page', function () {
    config([
        'app.admin_name'      => 'Test Admin',
        'app.admin_phone'     => '+57 300 111 2222',
        'app.admin_whatsapp'  => '573001112222',
    ]);

    $user = User::factory()->create(['is_activated' => false]);

    $this->withoutVite()
        ->actingAs($user)
        ->get('/activation')
        ->assertInertia(fn ($page) => $page
            ->where('adminName', 'Test Admin')
            ->where('adminPhone', '+57 300 111 2222')
            ->where('adminWhatsApp', '573001112222')
        );
});
```

- [ ] **Step 2: Correr el test para verificar que falla**

```bash
./vendor/bin/sail test tests/Feature/ActivationTest.php
```

Expected: FAILED — ruta no existe.

- [ ] **Step 3: Crear `app/Http/Controllers/ActivationController.php`**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivationController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->is_activated) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Activation', [
            'adminName'      => config('app.admin_name'),
            'adminPhone'     => config('app.admin_phone'),
            'adminWhatsApp'  => config('app.admin_whatsapp'),
        ]);
    }
}
```

- [ ] **Step 4: Añadir la ruta en `routes/web.php`**

Añadir dentro del grupo `Route::middleware('auth')->group(...)` existente, junto a las otras rutas protegidas:

```php
Route::get('/activation', [ActivationController::class, 'show'])->name('activation');
```

También añadir el import al inicio del archivo:

```php
use App\Http\Controllers\ActivationController;
```

- [ ] **Step 5: Correr los tests**

```bash
./vendor/bin/sail test tests/Feature/ActivationTest.php
```

Expected: 4 tests pasando.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ActivationController.php routes/web.php tests/Feature/ActivationTest.php
git commit -m "feat: add ActivationController and route"
```

---

## Task 7: Activation.jsx

**Files:**
- Create: `resources/js/Pages/Activation.jsx`

- [ ] **Step 1: Crear `resources/js/Pages/Activation.jsx`**

```jsx
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Trophy } from '@/Components/icons/football';
import Cromo from '@/Components/ui/Cromo';
import { Mark26 } from '@/Components/icons/football';

function SectionHead({ title, accent = 'red' }) {
    return (
        <div className="flex items-center gap-2 py-2.5">
            <span className={`w-3.5 h-3.5 flex-shrink-0 bg-pop-${accent} border-2 border-ink`} />
            <div className="font-display text-[14px] tracking-[.02em]">{title}</div>
            <div className="flex-1 h-[3px] bg-ink" />
        </div>
    );
}

function PayMethod({ label, sub, color, dark = false }) {
    return (
        <div
            className="bg-white border-[2.5px] border-ink px-2.5 py-2 relative overflow-hidden"
            style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
        >
            <div className="flex items-center gap-1.5">
                <span className="w-4 h-4 border-2 border-ink flex-shrink-0" style={{ background: color }} />
                <span className="font-display text-[11px] text-ink tracking-[.02em]">{label}</span>
            </div>
            <div className="font-mono text-[10px] font-bold mt-1 opacity-80 tracking-[.02em]">{sub}</div>
            <div className="absolute top-1.5 right-1.5 font-mono text-[9px] opacity-50">copiar</div>
        </div>
    );
}

export default function Activation({ adminName, adminPhone, adminWhatsApp }) {
    const initials = adminName
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();

    return (
        <>
            <Head title="Activación · Mundial de Parche" />
            <div className="bg-cream min-h-screen overflow-hidden relative flex flex-col">

                {/* Halftone yel — top right */}
                <div
                    className="absolute top-0 right-0 w-[200px] h-[200px] pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(var(--c-yel) 1.2px, transparent 1.6px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.22,
                        WebkitMaskImage: 'radial-gradient(circle at 100% 0%, #000 40%, transparent 70%)',
                        maskImage: 'radial-gradient(circle at 100% 0%, #000 40%, transparent 70%)',
                    }}
                />

                {/* Halftone teal — bottom left */}
                <div
                    className="absolute bottom-0 left-0 w-[240px] h-[240px] pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(var(--c-teal) 1.2px, transparent 1.6px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.14,
                        WebkitMaskImage: 'radial-gradient(circle at 0% 100%, #000 40%, transparent 70%)',
                        maskImage: 'radial-gradient(circle at 0% 100%, #000 40%, transparent 70%)',
                    }}
                />

                {/* Header */}
                <div className="relative px-6 pt-1 flex-shrink-0">
                    <div className="flex items-center gap-2.5 justify-between">
                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => window.history.back()}
                                className="w-8 h-8 border-2 border-ink flex items-center justify-center font-display text-[14px]"
                            >
                                ←
                            </button>
                            <div className="font-pixel text-[18px]">PASO 2 / 2</div>
                        </div>
                        <div className="bg-pop-yel text-ink border-2 border-ink px-2 py-0.5 font-mono text-[9px] font-bold" style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}>
                            ÚLTIMO PASO
                        </div>
                    </div>

                    <div className="font-display text-[32px] leading-none mt-[18px]">
                        METELE LOS
                        <br />
                        <span className="text-pop-red" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                            50K AL POZO
                        </span>
                    </div>
                    <div className="font-body text-[13px] mt-2 opacity-80 leading-[1.35]">
                        Tu aporte de entrada se suma al pozo del parche. Cuando el admin lo confirme, quedás adentro y podés meter goles.
                    </div>
                </div>

                {/* Big amount card */}
                <div className="px-[18px] pt-[18px] flex-shrink-0">
                    <Cromo className="bg-navy text-cream p-3.5">
                        <div
                            className="absolute inset-0 pointer-events-none"
                            style={{
                                backgroundImage: 'radial-gradient(var(--c-yel) 1.2px, transparent 1.6px)',
                                backgroundSize: '8px 8px',
                                opacity: 0.22,
                            }}
                        />
                        <div className="absolute right-[-8px] bottom-[-12px] opacity-95" style={{ transform: 'rotate(-8deg)' }}>
                            <Trophy size={70} color="var(--c-yel)" />
                        </div>
                        <div className="relative">
                            <div className="font-mono text-[10px] tracking-[.12em] text-pop-yel">TU APORTE</div>
                            <div
                                className="font-display text-[54px] leading-none text-pop-yel"
                                style={{ textShadow: '4px 4px 0 var(--c-ink)' }}
                            >
                                50K
                            </div>
                            <div className="font-mono text-[10px] opacity-80 mt-1">1 sola vez · va 100% al pozo</div>
                        </div>
                    </Cromo>
                </div>

                {/* Scroll area */}
                <div className="flex-1 overflow-y-auto px-[18px] pt-3.5 min-h-0" style={{ WebkitOverflowScrolling: 'touch' }}>
                    <SectionHead title="PAGÁ POR" accent="red" />
                    <div className="grid grid-cols-2 gap-2">
                        <PayMethod label="NEQUI" sub="300 123 4567" color="#ff006e" />
                        <PayMethod label="DAVIPLATA" sub="300 123 4567" color="#ed1c24" />
                        <PayMethod label="BANCOLOMBIA" sub="© ahorros 123-456" color="#fdda24" dark />
                        <PayMethod label="EFECTIVO" sub="al admin, en persona" color="var(--c-teal)" />
                    </div>

                    {/* Admin card */}
                    <div
                        className="mt-3.5 px-3 py-2.5 border-[2.5px] border-ink bg-white flex items-center gap-2.5"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        <div
                            className="w-[42px] h-[42px] flex-shrink-0 rounded-full bg-pop-yel border-[2.5px] border-ink flex items-center justify-center font-display text-[16px] text-ink"
                        >
                            {initials}
                        </div>
                        <div className="flex-1 min-w-0">
                            <div className="font-mono text-[9px] opacity-65 tracking-[.1em]">ADMIN DEL PARCHE</div>
                            <div className="font-display text-[14px] leading-none">{adminName}</div>
                            <div className="font-mono text-[10px] opacity-70 mt-0.5">{adminPhone} · WhatsApp</div>
                        </div>
                    </div>

                    {/* Status pending */}
                    <div className="mt-3.5 mb-2 px-3 py-2.5 bg-pop-yel border-[2.5px] border-dashed border-ink flex items-center gap-2.5">
                        <div
                            className="w-6 h-6 flex-shrink-0 border-[2.5px] border-ink rounded-full"
                            style={{
                                borderTopColor: 'transparent',
                                animation: 'spin 1.6s linear infinite',
                            }}
                        />
                        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
                        <div className="flex-1">
                            <div className="font-display text-[13px] leading-none">ESPERANDO AL ADMIN</div>
                            <div className="font-mono text-[10px] opacity-75 mt-0.5">Te avisamos apenas activen tu cuenta</div>
                        </div>
                    </div>
                </div>

                {/* Sticky CTAs */}
                <div className="flex-shrink-0 flex flex-col gap-2 px-[18px] py-2.5 pb-[22px] bg-cream border-t-[3px] border-ink">
                    <a
                        href={`https://wa.me/${adminWhatsApp}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="w-full py-[18px] bg-pop-teal text-white font-display text-[16px] text-center tracking-[.01em] border-[2.5px] border-ink flex items-center justify-center active:translate-x-[3px] active:translate-y-[3px]"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        AVISAR POR WHATSAPP →
                    </a>
                    <button
                        onClick={() => router.visit(route('dashboard'))}
                        className="w-full py-2 text-ink font-display text-[11px] tracking-[.01em] opacity-80"
                    >
                        MIENTRAS, EXPLORAR EL PARCHE
                    </button>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Verificar que todos los tests siguen pasando**

```bash
./vendor/bin/sail test
```

Expected: todos pasan.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Activation.jsx
git commit -m "feat: add Activation page"
```

---

## Task 8: Redirect post-login para usuarios no activados

**Files:**
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- (Tests incluidos aquí mismo)

- [ ] **Step 1: Añadir test a `tests/Feature/ActivationTest.php`**

Añadir estos dos tests al archivo existente:

```php
it('redirects non-activated user to activation after login', function () {
    $user = User::factory()->create([
        'email'        => 'test@example.com',
        'password'     => bcrypt('password'),
        'is_activated' => false,
    ]);

    $this->withoutVite()
        ->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'password',
        ])
        ->assertRedirect(route('activation'));
});

it('redirects activated user to dashboard after login', function () {
    $user = User::factory()->activated()->create([
        'email'    => 'activated@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->withoutVite()
        ->post('/login', [
            'email'    => 'activated@example.com',
            'password' => 'password',
        ])
        ->assertRedirect(route('dashboard'));
});
```

- [ ] **Step 2: Correr los tests para verificar que fallan**

```bash
./vendor/bin/sail test tests/Feature/ActivationTest.php
```

Expected: los 2 nuevos tests fallan (ambos redirigen a `dashboard` actualmente).

- [ ] **Step 3: Modificar `AuthenticatedSessionController@store`**

Reemplazar el método `store` por:

```php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    if (! $request->user()->is_activated) {
        return redirect()->route('activation');
    }

    return redirect()->intended(route('dashboard', absolute: false));
}
```

- [ ] **Step 4: Correr los tests**

```bash
./vendor/bin/sail test tests/Feature/ActivationTest.php
```

Expected: 6 tests pasando.

- [ ] **Step 5: Correr toda la suite**

```bash
./vendor/bin/sail test
```

Expected: todos pasando.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Auth/AuthenticatedSessionController.php tests/Feature/ActivationTest.php
git commit -m "feat: redirect non-activated users to activation page after login"
```

---

## Task 9: Tests + HomeController alert methods

**Files:**
- Create: `tests/Feature/HomeAlertsTest.php`
- Modify: `app/Http/Controllers/HomeController.php`

- [ ] **Step 1: Escribir `tests/Feature/HomeAlertsTest.php`**

```php
<?php
// tests/Feature/HomeAlertsTest.php

use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->activated()->create(['is_active' => true]);
});

// --- phaseAlert ---

it('phaseAlert is null when no round is open', function () {
    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('phaseAlert', null));
});

it('phaseAlert is null when open round was updated more than 24h ago', function () {
    $round = Round::factory()->create(['is_open' => true, 'order' => 1]);
    \DB::table('rounds')->where('id', $round->id)->update(['updated_at' => now()->subHours(25)]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('phaseAlert', null));
});

it('phaseAlert is null when user already has submitted predictions for the round', function () {
    $round = Round::factory()->create(['is_open' => true, 'order' => 2]);

    PredictionSubmission::factory()->submitted()->create([
        'user_id'  => $this->user->id,
        'round_id' => $round->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('phaseAlert', null));
});

it('phaseAlert is present when round opened recently and user has no submission', function () {
    Round::factory()->create(['is_open' => false, 'order' => 1, 'name' => 'Grupos']);
    Round::factory()->create(['is_open' => true, 'order' => 2, 'name' => 'R32+R16']);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $props = $response->original->getData()['page']['props'];
    expect($props['phaseAlert'])->not->toBeNull();
    expect($props['phaseAlert']['fromRound'])->toBe('Grupos');
    expect($props['phaseAlert']['toRound'])->toBe('R32+R16');
});

// --- deadlineAlert ---

it('deadlineAlert is null when no round has closes_at set', function () {
    Round::factory()->create(['is_open' => true, 'is_locked' => false, 'closes_at' => null]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('deadlineAlert', null));
});

it('deadlineAlert is null when closes_at is more than 24h away', function () {
    Round::factory()->create([
        'is_open'    => true,
        'is_locked'  => false,
        'closes_at'  => now()->addHours(30),
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('deadlineAlert', null));
});

it('deadlineAlert is null when user has no draft submission', function () {
    Round::factory()->create([
        'is_open'   => true,
        'is_locked' => false,
        'closes_at' => now()->addHours(2),
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('deadlineAlert', null));
});

it('deadlineAlert is present when closes_at is within 24h and user has draft submission', function () {
    $round = Round::factory()->create([
        'is_open'   => true,
        'is_locked' => false,
        'closes_at' => now()->addHours(2),
        'name'      => 'Grupos',
    ]);

    PredictionSubmission::factory()->create([
        'user_id'  => $this->user->id,
        'round_id' => $round->id,
        'status'   => 'draft',
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $props = $response->original->getData()['page']['props'];
    expect($props['deadlineAlert'])->not->toBeNull();
    expect($props['deadlineAlert']['round'])->toBe('Grupos');
    expect($props['deadlineAlert']['hoursLeft'])->toBeLessThanOrEqual(2);
});
```

- [ ] **Step 2: Correr los tests para verificar que fallan**

```bash
./vendor/bin/sail test tests/Feature/HomeAlertsTest.php
```

Expected: FAILED — HomeController no pasa `phaseAlert` ni `deadlineAlert` todavía.

- [ ] **Step 3: Añadir los imports necesarios al inicio de `HomeController.php`**

Añadir al bloque de `use` que ya existe:

```php
use App\Models\PredictionSubmission;
```

- [ ] **Step 4: Añadir los métodos privados en `HomeController` y actualizar `Inertia::render`**

Añadir al final del `return Inertia::render(...)` en `index()` las dos nuevas props:

```php
return Inertia::render('Home', [
    'user'         => [ /* ... igual que antes ... */ ],
    'featured'     => $featuredData,
    'stats'        => [ /* ... igual que antes ... */ ],
    'phase'        => $phaseData,
    'nextBets'     => $nextBets,
    'phaseAlert'   => $this->detectPhaseAlert($user),
    'deadlineAlert' => $this->detectDeadlineAlert($user),
]);
```

Añadir los dos métodos privados al final de la clase, antes del cierre `}`:

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
        ->whereHas('fixture', fn ($q) => $q->where('round_id', $round->id))
        ->count();

    $hoursLeft = (int) now()->diffInHours($round->closes_at, false);

    if ($hoursLeft < 0) return null;

    return [
        'round'       => $round->name,
        'hoursLeft'   => $hoursLeft,
        'minutesLeft' => (int) now()->diffInMinutes($round->closes_at, false) % 60,
        'pending'     => max(0, $totalMatches - $predicted),
        'total'       => $totalMatches,
    ];
}
```

- [ ] **Step 5: Correr los tests**

```bash
./vendor/bin/sail test tests/Feature/HomeAlertsTest.php
```

Expected: 8 tests pasando.

- [ ] **Step 6: Correr toda la suite**

```bash
./vendor/bin/sail test
```

Expected: todos los tests pasando (~197).

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/HomeAlertsTest.php app/Http/Controllers/HomeController.php
git commit -m "feat: add phaseAlert and deadlineAlert detection to HomeController"
```

---

## Task 10: Añadir tamaño `xl` a Burst + overlays PhaseOpenAlert y DeadlineAlert

**Files:**
- Modify: `resources/js/Components/ui/Burst.jsx`
- Create: `resources/js/Components/overlays/PhaseOpenAlert.jsx`
- Create: `resources/js/Components/overlays/DeadlineAlert.jsx`

- [ ] **Step 1: Añadir tamaño `xl` en `Burst.jsx`**

En el objeto `SIZES`, añadir:

```js
const SIZES = {
    sm: { outer: 'w-12 h-12',  text: 'text-[10px]' },
    md: { outer: 'w-20 h-20',  text: 'text-xs' },
    lg: { outer: 'w-28 h-28',  text: 'text-sm' },
    xl: { outer: 'w-36 h-36',  text: 'text-base' },
};
```

- [ ] **Step 2: Crear `resources/js/Components/overlays/PhaseOpenAlert.jsx`**

```jsx
import { router } from '@inertiajs/react';
import { Trophy, SoccerBall } from '@/Components/icons/football';
import Burst from '@/Components/ui/Burst';

export default function PhaseOpenAlert({ phaseAlert, onDismiss }) {
    const { fromRound, toRound, closeDate } = phaseAlert;
    // Si el nombre tiene '+' (ej: "R32+R16") mostramos "FASE 2" + subtítulo
    // Si no (ej: "Grupos", "Final") mostramos solo el nombre
    const hasSubtitle = toRound.includes('+');

    return (
        <div className="fixed inset-0 z-50 bg-pop-red text-cream overflow-hidden flex flex-col">

            {/* Halftone overlay */}
            <div
                className="absolute inset-0 pointer-events-none"
                style={{
                    backgroundImage: 'radial-gradient(rgba(0,0,0,.9) 1.2px, transparent 1.6px)',
                    backgroundSize: '8px 8px',
                    opacity: 0.28,
                }}
            />

            {/* Speedlines */}
            <div className="speedlines absolute inset-0 pointer-events-none" style={{ opacity: .22 }} />

            {/* Burst — top right */}
            <div className="absolute top-[96px] right-[-30px]" style={{ transform: 'rotate(14deg)' }}>
                <Burst color="yel" size="xl">
                    ¡NUEVA FASE!
                </Burst>
            </div>

            {/* Trophy — top left */}
            <div className="absolute top-[110px] left-[22px]" style={{ transform: 'rotate(-10deg)' }}>
                <Trophy size={56} />
            </div>

            {/* Content */}
            <div className="relative z-10 flex-1 flex flex-col justify-center px-7 pt-[60px]">

                {/* fromRound chip */}
                <div className="inline-flex items-center gap-2 self-start border-[2.5px] border-ink px-3 py-1.5 font-mono text-[11px] font-bold tracking-[.1em]" style={{ background: 'rgba(0,0,0,.35)' }}>
                    {fromRound.toUpperCase()}
                    <span className="bg-ink text-pop-yel px-1.5 font-display text-[10px]">CERRADA ✓</span>
                </div>

                {/* Arrow */}
                <div className="ml-3.5 my-3.5 font-display text-[32px] text-pop-yel leading-none">↓</div>

                {/* New round */}
                <div className="font-display text-[14px] text-pop-yel tracking-[.06em] mb-1.5">ABRIÓ ▶</div>
                <div
                    className="font-display text-[48px] leading-none text-cream"
                    style={{ textShadow: '4px 4px 0 var(--c-ink)' }}
                >
                    {hasSubtitle ? 'NUEVA FASE' : toRound.toUpperCase()}
                </div>
                {hasSubtitle && (
                    <div
                        className="font-display text-[30px] leading-[.95] text-pop-yel mt-1"
                        style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}
                    >
                        {toRound.toUpperCase()}
                    </div>
                )}

                {/* Info card */}
                <div className="mt-[18px] p-3 bg-navy border-[2.5px] border-ink" style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}>
                    <div className="font-mono text-[10px] text-pop-yel tracking-[.1em]">QUÉ TENÉS QUE HACER</div>
                    <div className="font-body text-[13px] mt-1 leading-[1.35]">
                        Metele los goles a los partidos de <b className="text-pop-yel">{toRound}</b> antes del cierre.
                    </div>
                    <div className="mt-2 flex justify-between font-mono text-[10px] tracking-[.06em]">
                        <span className="opacity-70">CIERRE:</span>
                        <b className="text-pop-yel">{closeDate}</b>
                    </div>
                </div>
            </div>

            {/* CTA */}
            <div className="relative z-10 flex-shrink-0 px-6 pb-[30px]">
                <button
                    onClick={() => {
                        onDismiss();
                        router.visit(route('predictions.index'));
                    }}
                    className="w-full py-[18px] bg-pop-yel text-ink font-display text-[18px] tracking-[.01em] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                    style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                >
                    ARRANCAR A METER GOLES →
                </button>
                <div className="text-center mt-2.5 font-mono text-[11px] text-cream opacity-85">
                    <u>Después, no se puede.</u>
                </div>
            </div>

            {/* Corner ball */}
            <div className="absolute bottom-[90px] right-3.5 opacity-85">
                <SoccerBall size={48} />
            </div>
        </div>
    );
}
```

- [ ] **Step 3: Crear `resources/js/Components/overlays/DeadlineAlert.jsx`**

```jsx
import { router } from '@inertiajs/react';
import { Whistle } from '@/Components/icons/football';

export default function DeadlineAlert({ deadlineAlert, onDismiss }) {
    const { round, hoursLeft, minutesLeft, pending, total } = deadlineAlert;
    const filled = total > 0 ? Math.round(((total - pending) / total) * 100) : 0;

    const pad = (n) => String(n).padStart(2, '0');

    return (
        <div className="fixed inset-0 z-50 bg-pop-yel overflow-hidden flex flex-col">

            {/* Halftone red overlay */}
            <div
                className="absolute inset-0 pointer-events-none"
                style={{
                    backgroundImage: 'radial-gradient(var(--c-red) 1.2px, transparent 1.6px)',
                    backgroundSize: '8px 8px',
                    opacity: 0.25,
                }}
            />

            {/* Diagonal stripes — top */}
            <div
                className="absolute top-0 left-0 right-0 h-[80px] pointer-events-none"
                style={{
                    background: 'repeating-linear-gradient(-45deg, var(--c-ink) 0 20px, transparent 20px 40px)',
                    opacity: 0.15,
                }}
            />

            {/* Diagonal stripes — bottom */}
            <div
                className="absolute bottom-0 left-0 right-0 h-[80px] pointer-events-none"
                style={{
                    background: 'repeating-linear-gradient(-45deg, var(--c-ink) 0 20px, transparent 20px 40px)',
                    opacity: 0.15,
                }}
            />

            {/* Whistle — top right */}
            <div className="absolute top-[100px] right-[-10px]" style={{ transform: 'rotate(18deg)' }}>
                <Whistle size={56} />
            </div>

            {/* Content */}
            <div className="relative z-10 flex-1 flex flex-col justify-center px-6 pt-10">
                <div
                    className="font-display text-[60px] leading-none text-pop-red"
                    style={{
                        WebkitTextStroke: '2.5px var(--c-ink)',
                        textShadow: '5px 5px 0 var(--c-ink)',
                    }}
                >
                    ¡PILAS,
                </div>
                <div
                    className="font-display text-[56px] leading-none text-cream mt-0.5"
                    style={{
                        WebkitTextStroke: '2.5px var(--c-ink)',
                        textShadow: '5px 5px 0 var(--c-red)',
                    }}
                >
                    PARCERO!
                </div>

                {/* Countdown */}
                <div className="mt-6 flex gap-1.5 justify-center">
                    {[
                        { v: pad(hoursLeft),   l: 'HORAS' },
                        { v: pad(minutesLeft), l: 'MIN' },
                        { v: '00',             l: 'SEG' },
                    ].map((u, i) => (
                        <div
                            key={i}
                            className="flex-1 text-center bg-ink text-pop-yel border-[3px] border-ink py-2.5 px-1"
                            style={{ boxShadow: '4px 4px 0 var(--c-red)' }}
                        >
                            <div className="font-display text-[36px] leading-none">{u.v}</div>
                            <div className="font-mono text-[9px] text-cream tracking-[.1em] mt-0.5">{u.l}</div>
                        </div>
                    ))}
                </div>

                {/* Missing card */}
                <div
                    className="mt-6 px-3.5 py-3 bg-white border-[3px] border-ink"
                    style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                >
                    <div className="flex justify-between items-baseline">
                        <div className="font-display text-[16px]">TE FALTAN</div>
                        <div className="font-display text-[36px] text-pop-red leading-none">{pending}</div>
                    </div>
                    <div className="font-mono text-[11px] tracking-[.06em] mt-1">
                        goles por meter en <b>{round.toUpperCase()}</b>
                    </div>
                    {/* Progress bar */}
                    <div className="mt-2.5 h-2 bg-cream border border-ink overflow-hidden">
                        <div
                            className="h-full bg-pop-red"
                            style={{ width: `${filled}%` }}
                        />
                    </div>
                    <div className="font-mono text-[9px] opacity-60 mt-1 text-right">{total - pending} / {total}</div>
                </div>
            </div>

            {/* CTAs */}
            <div className="relative z-10 flex-shrink-0 flex flex-col gap-2 px-6 pb-[30px]">
                <button
                    onClick={() => {
                        onDismiss();
                        router.visit(route('predictions.index'));
                    }}
                    className="w-full py-[18px] bg-pop-red text-white font-display text-[18px] tracking-[.01em] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                    style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                >
                    TERMINAR YA →
                </button>
                <button
                    onClick={onDismiss}
                    className="w-full py-2 text-ink font-display text-[12px] tracking-[.01em] opacity-80"
                >
                    AVISARME EN 1 HORA
                </button>
            </div>
        </div>
    );
}
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/ui/Burst.jsx \
        resources/js/Components/overlays/PhaseOpenAlert.jsx \
        resources/js/Components/overlays/DeadlineAlert.jsx
git commit -m "feat: add PhaseOpenAlert and DeadlineAlert overlay components"
```

---

## Task 11: Integrar overlays en Home.jsx

**Files:**
- Modify: `resources/js/Pages/Home.jsx`

- [ ] **Step 1: Añadir imports al inicio de `Home.jsx`**

Añadir junto a los imports existentes:

```jsx
import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import PhaseOpenAlert from '@/Components/overlays/PhaseOpenAlert';
import DeadlineAlert from '@/Components/overlays/DeadlineAlert';
```

- [ ] **Step 2: Actualizar la firma de la función `Home` para recibir las nuevas props**

Cambiar:
```jsx
export default function Home({ user, featured, stats, phase, nextBets }) {
```

Por:
```jsx
export default function Home({ user, featured, stats, phase, nextBets, phaseAlert, deadlineAlert }) {
```

- [ ] **Step 3: Añadir el estado de dismiss inmediatamente después de la línea `const firstName = ...`**

```jsx
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
    if (phaseAlert) {
        localStorage.setItem(`alert_phase_${phaseAlert.toRound}`, '1');
    } else if (deadlineAlert) {
        localStorage.setItem(`alert_deadline_${deadlineAlert.round}`, '1');
    }
    setAlertDismissed(true);
};
```

- [ ] **Step 4: Añadir los overlays al inicio del JSX retornado, antes del `<Head>`**

```jsx
return (
    <>
        {!alertDismissed && phaseAlert && (
            <PhaseOpenAlert phaseAlert={phaseAlert} onDismiss={handleDismiss} />
        )}
        {!alertDismissed && !phaseAlert && deadlineAlert && (
            <DeadlineAlert deadlineAlert={deadlineAlert} onDismiss={handleDismiss} />
        )}
        <Head title="PARCHE" />
        {/* ... resto del JSX sin cambios ... */}
    </>
);
```

- [ ] **Step 5: Correr toda la suite de tests para confirmar que nada se rompió**

```bash
./vendor/bin/sail test
```

Expected: todos pasando.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Home.jsx
git commit -m "feat: integrate PhaseOpenAlert and DeadlineAlert overlays into Home"
```

---

## Task 12: Copiar assets públicos

**Files:**
- `public/assets/fifa_cover.png`
- `public/assets/wc26_logo.avif`

- [ ] **Step 1: Crear el directorio y copiar los assets desde el design handoff**

```bash
mkdir -p public/assets

cp "/mnt/c/Users/dwndz/OneDrive/Escritorio/Mundial de parche_/design_handoff_mundial_parche/assets/fifa_cover.png" public/assets/
cp "/mnt/c/Users/dwndz/OneDrive/Escritorio/Mundial de parche_/design_handoff_mundial_parche/assets/wc26_logo.avif" public/assets/
```

- [ ] **Step 2: Verificar que los archivos existen**

```bash
ls -lh public/assets/
```

Expected:
```
-rw-r--r-- 1 ... fifa_cover.png
-rw-r--r-- 1 ... wc26_logo.avif
```

- [ ] **Step 3: Commit**

```bash
git add public/assets/
git commit -m "feat: add public assets fifa_cover.png and wc26_logo.avif"
```

---

## Task 13: Verificación final

- [ ] **Step 1: Correr toda la suite de tests**

```bash
./vendor/bin/sail test
```

Expected: ~197 tests pasando, 0 failed.

- [ ] **Step 2: Correr el build de producción para detectar errores de compilación JSX**

```bash
./vendor/bin/sail pnpm run build
```

Expected: sin errores de compilación.

- [ ] **Step 3: Levantar el servidor de dev y verificar visualmente**

```bash
./vendor/bin/sail up -d
./vendor/bin/sail pnpm run dev
```

Verificar manualmente en el browser:
- `http://localhost/` → Welcome page (navy, HOLA PARCERO)
- `http://localhost/how-to-play` → HowTo (3 pasos)
- `http://localhost/rules` → Rules (7 reglas)
- Login con usuario `is_activated = false` → redirige a `/activation`
- Login con usuario `is_activated = true` → redirige a `/dashboard`

- [ ] **Step 4: Commit final si todo está correcto**

```bash
git add .
git commit -m "feat: complete Plan 6 — all 6 remaining user UI screens implemented"
```
