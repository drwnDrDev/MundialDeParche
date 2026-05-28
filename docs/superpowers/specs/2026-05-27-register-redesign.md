# Register Redesign — Spec

**Fecha:** 2026-05-27
**Alcance:** Rediseño de `Auth/Register.jsx` al estilo pop-art del proyecto + ajuste menor en `Auth/Login.jsx`
**Stack:** React + Inertia.js + Tailwind + design system pop-art existente

---

## Contexto

`Auth/Register.jsx` usa `GuestLayout` de Breeze con componentes genéricos (`InputLabel`, `TextInput`, `PrimaryButton`) y labels en inglés. `Auth/Login.jsx` ya tiene el diseño pop-art completo. Register debe ser su contraparte visual como `PASO 1 / 2` del flujo de onboarding de nuevos usuarios.

**Flujo de onboarding:**
1. `Register.jsx` → `PASO 1 / 2`
2. `Activation.jsx` → `PASO 2 / 2`
3. `Login.jsx` → pantalla standalone para usuarios recurrentes (sin indicador de pasos)

---

## Cambios

### 1. `Auth/Register.jsx` — Reescritura completa

**Estructura base** (misma que Login): `bg-cream`, sin GuestLayout, sin componentes Breeze. Usa el componente `Field` inline igual al de Login.

**Decoración:**
- Halftone rojo `top-0 left-0` (opacity 0.35)
- Halftone teal `bottom-0 right-0` (opacity 0.35)
- `PitchSwoosh` al fondo (width=390, height=120, opacity 0.85)
- `SoccerBall` ghost a la derecha (size=120, opacity 0.15)
- Burst badge rotado: texto `"Mundial\n2026"` en `bg-pop-yel`, `rotate(12deg)`

**Header:**
```
← (Link a /)     PASO 1 / 2     [Mark26 size=32, fill=red, accent=yel, rotate 8deg]
```

**Heading:**
```
Entra al
Parche!
```
- `font-display text-[36px] leading-none`
- Subtítulo: `"Crea tu cuenta y mídete con los duros el Mundial '26"` — `font-body text-[14px] mt-2 opacity-80`

**Campos** (mismo componente `Field` inline de Login):

| Label | id/name | type | extras |
|---|---|---|---|
| NICKNAME | name | text | placeholder="ej: Tigre7, GolazoKing" · autoComplete="nickname" · autoFocus |
| EMAIL | email | email | autoComplete="username" |
| CONTRASEÑA | password | password | autoComplete="new-password" |
| CONFIRMAR CONTRASEÑA | password_confirmation | password | autoComplete="new-password" |

El campo se sigue enviando como `name` al backend — sin cambio de BD ni validaciones.

**Botón submit:**
```
DALE, REGISTRARME →
```
- `bg-pop-red text-white font-display text-[18px] border-[2.5px] border-ink py-4 w-full`
- `boxShadow: '4px 4px 0 var(--c-ink)'`
- `disabled={processing}`

**Footer (bottom-[18px], centrado, font-mono text-[12px]):**
```
¿Ya tienes cuenta?  [Entra aquí] (Link a route('login'), bold underline)
```

**Copy:** español estándar, sin voseo rioplatense.

---

### 2. `Auth/Login.jsx` — Ajuste menor

Eliminar el label `PASO 1 / 2` del header. El header queda solo con la flecha ← y el Mark26.

---

## Fuera de alcance

- Cambios al backend de registro (validaciones, campos adicionales)
- Cambios a `ForgotPassword.jsx`, `ResetPassword.jsx` u otras pantallas auth
- Cambios al campo `name` en la BD o en el modelo User
