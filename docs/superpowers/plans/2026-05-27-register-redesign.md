# Register Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reemplazar el Register.jsx genérico de Breeze con la versión pop-art del proyecto, coherente con Login.jsx, e incluir el indicador de flujo "PASO 1 / 2".

**Architecture:** Reescritura completa de `Auth/Register.jsx` copiando el patrón visual de `Auth/Login.jsx` (sin GuestLayout, sin componentes Breeze, `Field` inline, halftones + PitchSwoosh + SoccerBall). Ajuste menor en `Auth/Login.jsx` para quitar el label de pasos. Sin cambios de backend, rutas ni tests de comportamiento.

**Tech Stack:** React 18 · Inertia.js v2 · Tailwind · design system pop-art (CSS vars, font-display, font-mono, font-pixel) · pnpm · Laravel Sail

---

## File Map

**Modificar:**
- `resources/js/Pages/Auth/Register.jsx` — reescritura completa
- `resources/js/Pages/Auth/Login.jsx` — eliminar label "PASO 1 / 2"

---

## Task 1: Reescribir Register.jsx

**Files:**
- Modify: `resources/js/Pages/Auth/Register.jsx`

- [ ] **Reemplazar TODO el contenido del archivo con:**

```jsx
import { Head, Link, useForm } from '@inertiajs/react';
import { SoccerBall, Mark26, PitchSwoosh } from '@/Components/icons/football';

function Field({ label, id, error, ...inputProps }) {
    return (
        <div>
            <div className="font-mono text-[11px] font-bold tracking-[.1em] mb-1.5 text-ink">
                {label}
            </div>
            <input
                id={id}
                className="w-full border-[2.5px] border-ink bg-white px-[14px] py-[12px] font-mono font-bold text-[14px] focus:outline-none focus:border-pop-red"
                style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                {...inputProps}
            />
            {error && (
                <div className="font-mono text-[11px] text-pop-red mt-1">{error}</div>
            )}
        </div>
    );
}

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Entra al Parche — Registro" />
            <div className="min-h-screen bg-cream relative overflow-hidden">

                {/* Halftone corners */}
                <div className="halftone halftone-red absolute top-0 left-0 w-[220px] h-[220px] pointer-events-none" style={{ opacity: .35 }} />
                <div className="halftone halftone-teal absolute bottom-0 right-0 w-[260px] h-[260px] pointer-events-none" style={{ opacity: .35 }} />

                {/* Pitch swoosh al fondo */}
                <div className="absolute bottom-0 left-0 right-0 opacity-85 pointer-events-none">
                    <PitchSwoosh width={390} height={120} />
                </div>

                {/* Header */}
                <div className="relative px-6 pt-3">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2.5">
                            <Link
                                href="/"
                                className="w-8 h-8 border-[2px] border-ink rounded-[6px] flex items-center justify-center font-display text-[14px]"
                            >
                                ←
                            </Link>
                            <div className="font-pixel text-[18px]">PASO 1 / 2</div>
                        </div>
                        <div style={{ transform: 'rotate(8deg)' }}>
                            <Mark26 size={32} fill="var(--c-red)" accent="var(--c-yel)" />
                        </div>
                    </div>

                    <div className="mt-5">
                        <div className="font-display text-[36px] leading-none">
                            Entra al<br />
                            <span className="text-pop-red" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                                Parche!
                            </span>
                        </div>
                        <div className="font-body text-[14px] mt-2 opacity-80">
                            Crea tu cuenta y mídete con los duros el Mundial '26.
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={submit} className="relative z-10 px-6 pt-6 flex flex-col gap-3.5">
                    <Field
                        label="NICKNAME"
                        id="name"
                        type="text"
                        name="name"
                        value={data.name}
                        placeholder="ej: Tigre7, GolazoKing"
                        autoComplete="nickname"
                        autoFocus
                        onChange={e => setData('name', e.target.value)}
                        error={errors.name}
                    />

                    <Field
                        label="EMAIL"
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        autoComplete="username"
                        onChange={e => setData('email', e.target.value)}
                        error={errors.email}
                    />

                    <Field
                        label="CONTRASEÑA"
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoComplete="new-password"
                        onChange={e => setData('password', e.target.value)}
                        error={errors.password}
                    />

                    <Field
                        label="CONFIRMAR CONTRASEÑA"
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        onChange={e => setData('password_confirmation', e.target.value)}
                        error={errors.password_confirmation}
                    />

                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-2 w-full py-4 bg-pop-red text-white font-display text-[18px] border-[2.5px] border-ink tracking-[.02em] disabled:opacity-60"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        DALE, REGISTRARME →
                    </button>
                </form>

                {/* Burst badge */}
                <div
                    className="absolute top-[80px] right-[18px] z-10 bg-pop-yel border-[2.5px] border-ink px-3 py-2 font-display text-[12px] text-ink text-center"
                    style={{ transform: 'rotate(12deg)', boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    Mundial<br />2026
                </div>

                {/* Ghost ball */}
                <div className="absolute top-[270px] right-[-8px] opacity-15">
                    <SoccerBall size={120} />
                </div>

                {/* Footer */}
                <div className="absolute bottom-[18px] left-0 right-0 text-center font-mono text-[12px] z-10">
                    ¿Ya tienes cuenta?{' '}
                    <Link href={route('login')} className="font-bold underline">
                        Entra aquí
                    </Link>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Verificar build:**

```bash
cd /home/dwndz/Projects/PollaMundial && ./vendor/bin/sail pnpm run build 2>&1 | tail -5
```

Esperado: `✓ built in X.XXs` sin errores.

- [ ] **Commit:**

```bash
cd /home/dwndz/Projects/PollaMundial && git add resources/js/Pages/Auth/Register.jsx && git commit -m "feat: redesign Register page with pop-art style matching Login"
```

---

## Task 2: Quitar "PASO 1 / 2" de Login.jsx

**Files:**
- Modify: `resources/js/Pages/Auth/Login.jsx`

El header de Login actualmente tiene (líneas 51-64):

```jsx
<div className="flex items-center justify-between">
    <div className="flex items-center gap-2.5">
        <Link
            href="/"
            className="w-8 h-8 border-[2px] border-ink rounded-[6px] flex items-center justify-center font-display text-[14px]"
        >
            ←
        </Link>
        <div className="font-pixel text-[18px]">PASO 1 / 2</div>
    </div>
    <div style={{ transform: 'rotate(8deg)' }}>
        <Mark26 size={32} fill="var(--c-red)" accent="var(--c-yel)" />
    </div>
</div>
```

- [ ] **Eliminar solo la línea del label de pasos.** Buscar y quitar:

```jsx
        <div className="font-pixel text-[18px]">PASO 1 / 2</div>
```

El resultado del bloque del header queda:

```jsx
<div className="flex items-center justify-between">
    <div className="flex items-center gap-2.5">
        <Link
            href="/"
            className="w-8 h-8 border-[2px] border-ink rounded-[6px] flex items-center justify-center font-display text-[14px]"
        >
            ←
        </Link>
    </div>
    <div style={{ transform: 'rotate(8deg)' }}>
        <Mark26 size={32} fill="var(--c-red)" accent="var(--c-yel)" />
    </div>
</div>
```

- [ ] **Verificar build:**

```bash
cd /home/dwndz/Projects/PollaMundial && ./vendor/bin/sail pnpm run build 2>&1 | tail -5
```

Esperado: `✓ built in X.XXs` sin errores.

- [ ] **Correr suite de tests para confirmar sin regresiones:**

```bash
cd /home/dwndz/Projects/PollaMundial && ./vendor/bin/sail test 2>&1 | grep "Tests:"
```

Esperado: mismo conteo que antes (217), sin failures nuevos.

- [ ] **Commit:**

```bash
cd /home/dwndz/Projects/PollaMundial && git add resources/js/Pages/Auth/Login.jsx && git commit -m "fix: remove step indicator from Login (only Register/Activation have steps)"
```

---

## Self-Review

**Spec coverage:**
- ✅ Register.jsx reescrito: heading "Entra al Parche!", subtítulo, PASO 1/2, campo NICKNAME con placeholder, 4 campos, botón "DALE, REGISTRARME →", burst "Mundial 2026", footer "¿Ya tienes cuenta? Entra aquí"
- ✅ Login.jsx: label "PASO 1/2" eliminado
- ✅ Copy en español estándar (sin voseo): "tienes", "mídete", "Crea"
- ✅ Sin cambios de backend, rutas ni modelos
- ✅ Sin imports de GuestLayout ni componentes Breeze en Register

**Placeholder scan:** Ninguno.

**Type consistency:** El `Field` inline de Register es idéntico al de Login — mismas props, mismas clases. ✅
