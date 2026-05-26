# UI Screens — Progress Tracker

> Paso 5: Pantallas completas con compuestos | Scope: Opción B (7 pantallas)
> Referencia handoff: `/mnt/c/Users/dwndz/OneDrive/Escritorio/Mundial de parche_/design_handoff_mundial_parche/`

---

## Estado global

| # | Pantalla | Sección | Archivo | Controller | Estado |
|---|---|---|---|---|---|
| 1 | Home | B | `Pages/Home.jsx` | `HomeController` | ✅ DONE |
| 2 | Ranking | F | `Pages/Ranking.jsx` | `RankingController` | ✅ DONE |
| 3 | Chat | G | `Pages/Chat.jsx` | `ChatController` | ✅ DONE |
| 4 | Partidos | C | `Pages/Matches.jsx` | `MatchesController` | ✅ DONE |
| 5 | Mis Goles | D | `Pages/Predictions/Round.jsx` | `PredictionController` | ✅ DONE |
| 6 | Splash | A4 | `Pages/Splash.jsx` | route `/` | ✅ DONE |
| 7 | Login | A5 | `Pages/Auth/Login.jsx` | Breeze | ✅ DONE |
| 8 | Alerta Locked | E3 | `Pages/Predictions/Locked.jsx` | `PredictionController` | ✅ DONE |

**Progreso: 8 / 8 pantallas DONE ✅**

---

## Detalle por pantalla

### ✅ 1 · Home (`ScreenHomeA`) — DONE
- Commit: `98bca73`
- Componentes: `FeaturedMatchCard`, `StatCard`, `BetCard`, `PtsBadge`, `TabBar`
- Controller: `HomeController@index` — props: `user`, `featured`, `stats`, `phase`, `nextBets`
- Handoff ref: `screen-home.jsx`

---

### 🔄 2 · Ranking (`ScreenRankingA`) — PENDIENTE
- Archivo actual: `Pages/Ranking.jsx` (usa `AuthenticatedLayout` viejo)
- Componentes a usar: `PodiumStep`, `RankRow`, `PozoCard`, `TabBar`
- Controller: `RankingController` — props: `users`, `currentUserId`, `pozo`
- Handoff ref: `screen-ranking.jsx`
- Estados del diseño: `allTied` / `partialTie` / `live` (derivado de la data, no prop manual)
- Notas: no usar banderas en avatares de usuarios; solo círculo con inicial

---

### 🔄 3 · Chat (`ScreenChatA`) — PENDIENTE
- Archivo actual: `Pages/Chat.jsx` (usa `AuthenticatedLayout` viejo)
- Componentes a usar: `ChatBubble`, `TabBar`
- Controller: `ChatController` — props: `messages`
- Handoff ref: `screen-chat.jsx`
- Lógica real-time: Echo `presence-quinela` `.MessageSent` ya implementado en el archivo actual
- Notas: conservar la lógica Reverb existente; solo cambiar la UI

---

### ❌ 4 · Partidos (`ScreenMatches`) — PENDIENTE
- Archivo a crear: `Pages/Matches.jsx`
- Vistas: Calendar (MatchCards por día) + Standings (GroupStandingCards)
- Componentes: `MatchCard`, `GroupStandingCard`, `TabBar`
- Controller: `MatchesController` — por crear
- Handoff ref: `screen-matches.jsx`
- Ruta sugerida: `GET /matches`

---

### 🔄 5 · Mis Goles (`ScreenPredict`) — PENDIENTE
- Archivos actuales: `Pages/Predictions/Index.jsx`, `Pages/Predictions/Round.jsx`
- Componentes a usar: `MatchPredRow`, `ScoreBox`, `TabBar`
- Controller: `PredictionController` — props ya definidos en Plan 3
- Handoff ref: `screen-predict.jsx`
- Notas: revisar si el Round.jsx existente es refactorable o hay que reescribir

---

### ❌ 6 · Splash (`ScreenSplash`) — PENDIENTE
- Archivo a crear: `Pages/Splash.jsx`
- Componentes: `Burst`, íconos `Trophy`, `HostStrip`, `Mark26`
- Assets: `assets/fifa_cover.png`, `assets/wc26_logo.avif`
- Controller: pantalla estática o middleware redirect; no necesita datos
- Handoff ref: `screen-onboarding.jsx` (sección Splash)
- Ruta: `/` para usuarios no autenticados o ruta pública

---

### 🔄 7 · Login (`ScreenLogin`) — PENDIENTE
- Archivo actual: `Pages/Auth/Login.jsx` (Breeze scaffold)
- Componentes: `Field` (primitivo), `Burst`, `PitchSwoosh` (ícono)
- Controller: Breeze — no tocar lógica, solo UI
- Handoff ref: `screen-onboarding.jsx` (sección Login)

---

### ❌ 8 · Alerta Fase Bloqueada (`ScreenAlertLocked`) — PENDIENTE
- Archivo a crear: `Pages/PhaseLocked.jsx`
- Componentes: `TabBar` (o sin TabBar — pantalla bloqueante fullscreen)
- Assets: ícono de candado SVG
- Controller: middleware que redirige aquí si fase está locked + `round` sin abrir
- Handoff ref: `screen-alerts.jsx` (sección E3)
- Ruta: `/phase-locked` o como middleware interceptor

---

## Componentes compuestos disponibles

Todos en `resources/js/Components/composed/` — importar desde `@/Components/composed`.

| Componente | Usado en |
|---|---|
| `TabBar` | Todas las pantallas |
| `PtsBadge` | Home ✅, Ranking |
| `StatCard` | Home ✅ |
| `BetCard` | Home ✅ |
| `FeaturedMatchCard` | Home ✅ |
| `MatchCard` | Partidos |
| `MatchPredRow` | Mis Goles |
| `ScoreBox` | Mis Goles |
| `PodiumStep` | Ranking |
| `RankRow` | Ranking |
| `PozoCard` | Ranking |
| `ChatBubble` | Chat |
| `GroupStandingCard` | Partidos (standings view) |

## Primitivos UI disponibles

En `resources/js/Components/ui/`: `Button`, `Cromo`, `Burst`, `HalftoneCorner`, etc.

## Pantallas fuera de scope (para después)

- A1 Welcome, A2 HowTo, A3 Rules, A6 Activation
- E1 Phase Open, E2 Deadline
- Plan 6 (Admin Panel UI)
- Plan 7 (User Frontend avanzado)
